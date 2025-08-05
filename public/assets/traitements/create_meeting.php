<?php
// Démarrer la session
session_start();

require_once __DIR__ . '/../../../config/config.php';

// Établir la connexion à la base de données
$pdo = DataBase::getConnection();

// Vérification de sécurité
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé - Session non valide']);
    exit;
}

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupération des données du formulaire
    $titre = $_POST['titre'] ?? '';
    $date_reunion = $_POST['date_reunion'] ?? '';
    $heure_debut = $_POST['heure_debut'] ?? '';
    $heure_fin = $_POST['heure_fin'] ?? '';
    $lieu = $_POST['lieu'] ?? 'Salle de réunion';
    $description = $_POST['description'] ?? '';
    $add_to_calendar = $_POST['add_to_calendar'] ?? '0';
    $send_notifications = $_POST['send_notifications'] ?? '0';
    
    // Validation des données
    if (empty($titre) || empty($date_reunion) || empty($heure_debut)) {
        throw new Exception('Titre, date et heure de début sont obligatoires');
    }
    
    // Vérifier que la date n'est pas dans le passé
    $meetingDateTime = new DateTime($date_reunion . ' ' . $heure_debut);
    $now = new DateTime();
    if ($meetingDateTime < $now) {
        throw new Exception('La date et heure de la réunion ne peuvent pas être dans le passé');
    }
    
    $pdo->beginTransaction();
    
    try {
        // Insérer la réunion dans la base de données
        $stmt = $pdo->prepare("
            INSERT INTO reunions_commission (titre, description, date_reunion, heure_debut, heure_fin, lieu, statut, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'planifiee', ?, NOW())
        ");
        
        $stmt->execute([
            $titre,
            $description,
            $date_reunion,
            $heure_debut,
            $heure_fin,
            $lieu,
            $_SESSION['user_id']
        ]);
        
        $meetingId = $pdo->lastInsertId();
        
        // Si demandé, ajouter au calendrier
        if ($add_to_calendar == '1') {
            addToCalendar($pdo, $meetingId, $titre, $date_reunion, $heure_debut, $heure_fin, $lieu, $description);
        }
        
        // Si demandé, envoyer des notifications aux membres de la commission
        if ($send_notifications == '1') {
            sendNotificationsToCommissionMembers($pdo, $meetingId, $titre, $date_reunion, $heure_debut, $lieu);
        }
        
        $pdo->commit();
        
        // Récupérer les données de la réunion créée pour le calendrier
        $stmt = $pdo->prepare("SELECT * FROM reunions_commission WHERE id = ?");
        $stmt->execute([$meetingId]);
        $meeting = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Réunion créée avec succès',
            'meeting' => $meeting
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Erreur création réunion: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function addToCalendar($pdo, $meetingId, $titre, $date_reunion, $heure_debut, $heure_fin, $lieu, $description) {
    try {
        // Créer la table calendrier si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS calendrier_commission (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reunion_id INT,
            titre VARCHAR(255),
            description TEXT,
            date_evenement DATE,
            heure_debut TIME,
            heure_fin TIME,
            lieu VARCHAR(255),
            type_evenement ENUM('reunion', 'autre') DEFAULT 'reunion',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reunion_id) REFERENCES reunions_commission(id) ON DELETE CASCADE
        )";
        $pdo->exec($sql);
        
        // Insérer l'événement dans le calendrier
        $stmt = $pdo->prepare("
            INSERT INTO calendrier_commission (reunion_id, titre, description, date_evenement, heure_debut, heure_fin, lieu, type_evenement)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'reunion')
        ");
        
        $stmt->execute([
            $meetingId,
            $titre,
            $description,
            $date_reunion,
            $heure_debut,
            $heure_fin,
            $lieu
        ]);
        
    } catch (Exception $e) {
        error_log('Erreur ajout au calendrier: ' . $e->getMessage());
    }
}

function sendNotificationsToCommissionMembers($pdo, $meetingId, $titre, $date_reunion, $heure_debut, $lieu) {
    try {
        // Créer la table messages si elle n'existe pas
        $sql = "CREATE TABLE IF NOT EXISTS messages (
            id_message INT AUTO_INCREMENT PRIMARY KEY,
            expediteur INT,
            destinataire INT,
            sujet VARCHAR(255),
            contenu TEXT,
            date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            lu BOOLEAN DEFAULT FALSE,
            type_message ENUM('systeme', 'utilisateur', 'notification') DEFAULT 'notification'
        )";
        $pdo->exec($sql);
        
        // Récupérer tous les membres de la commission (enseignants avec rôle commission)
        $stmt = $pdo->prepare("
            SELECT DISTINCT e.id_ens, e.nom_ens, e.prenom_ens 
            FROM enseignants e 
            INNER JOIN groupe_utilisateur gu ON e.id_ens = gu.id_utilisateur 
            INNER JOIN traitements t ON gu.id_gu = t.id_traitement 
            WHERE t.lib_traitement LIKE '%commission%' OR t.lib_traitement LIKE '%réunion%'
        ");
        $stmt->execute();
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Envoyer une notification à chaque membre
        foreach ($members as $member) {
            if ($member['id_ens'] != $_SESSION['user_id']) { // Ne pas s'envoyer de notification à soi-même
                $stmt = $pdo->prepare("
                    INSERT INTO messages (expediteur, destinataire, sujet, contenu, type_message)
                    VALUES (?, ?, ?, ?, 'notification')
                ");
                
                $sujet = "Nouvelle réunion de commission planifiée";
                $contenu = "Bonjour {$member['prenom_ens']} {$member['nom_ens']},\n\n" .
                          "Une nouvelle réunion de commission a été planifiée :\n\n" .
                          "📅 Titre : $titre\n" .
                          "📆 Date : " . date('d/m/Y', strtotime($date_reunion)) . "\n" .
                          "🕐 Heure : " . date('H:i', strtotime($heure_debut)) . "\n" .
                          "📍 Lieu : $lieu\n\n" .
                          "Merci de consulter le calendrier des réunions pour plus de détails.\n\n" .
                          "Cordialement,\n" .
                          "Système de gestion des réunions";
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $member['id_ens'],
                    $sujet,
                    $contenu
                ]);
            }
        }
        
    } catch (Exception $e) {
        error_log('Erreur envoi notifications: ' . $e->getMessage());
    }
}
?> 