<?php

require_once 'messages_functions.php';

// Activation du logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../../../logs/php_errors.log');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Définir le header JSON dès le début
header('Content-Type: application/json');

try {
    // Initialisation session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Vérification méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }

    // Vérification utilisateur
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentification requise']);
        exit;
    }

    // Chargement config DB & Mail
    require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/mail.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/includes/audit_utils.php';

    
    // Test connexion DB
    try {
        $pdo->query("SELECT 1")->fetch();
        error_log("Connexion DB vérifiée");
    } catch (PDOException $e) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Validation des données
    $required = ['titre', 'type', 'date', 'heure', 'lieu'];
    $missing = [];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Champs requis manquants',
            'missing_fields' => $missing
        ]);
        exit;
    }

    // Nettoyage données
    $data = [
        'titre' => trim($_POST['titre']),
        'type' => trim($_POST['type']),
        'date' => trim($_POST['date']),
        'heure' => trim($_POST['heure']),
        'duree' => !empty($_POST['duree']) ? (float)$_POST['duree'] : 1.5,
        'lieu' => trim($_POST['lieu']),
        'description' => trim($_POST['description'] ?? ''),
        'rapports' => (int)($_POST['rapports'] ?? 0),
        'status' => trim($_POST['status'] ?? 'scheduled'),
        'participants' => $_POST['participants'] ?? [],
        'notify_email' => isset($_POST['notify-email'])
    ];

    // Validation supplémentaire
    if (!in_array($data['type'], ['normale', 'urgente'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Type de réunion invalide']);
        exit;
    }

    if (!DateTime::createFromFormat('Y-m-d', $data['date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format de date invalide (YYYY-MM-DD attendu)']);
        exit;
    }

    if (!DateTime::createFromFormat('H:i', $data['heure'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Format d\'heure invalide (HH:MM attendu)']);
        exit;
    }

    // Début transaction
    $pdo->beginTransaction();

    try {
        // Insertion réunion
        $stmt = $pdo->prepare("
            INSERT INTO reunions 
            (titre, type, date_reunion, heure_debut, duree, lieu, description, rapports_count, status) 
            VALUES (:titre, :type, :date, :heure, :duree, :lieu, :description, :rapports, :status)
        ");
        
        $status_db = $data['status'] === 'scheduled' ? 'programmée' : 'en cours';
        
        $stmt->execute([
            ':titre' => $data['titre'],
            ':type' => $data['type'],
            ':date' => $data['date'],
            ':heure' => $data['heure'],
            ':duree' => $data['duree'],
            ':lieu' => $data['lieu'],
            ':description' => $data['description'],
            ':rapports' => $data['rapports'],
            ':status' => $status_db
        ]);
        
        $reunion_id = $pdo->lastInsertId();
        error_log("Réunion insérée avec ID: $reunion_id");

        // Participants
        if (!empty($data['participants'])) {
            $stmt_part = $pdo->prepare("
                INSERT IGNORE INTO participants 
                (reunion_id, id_utilisateur, status, date) 
                VALUES (:reunion_id, :user_id, 'en attente', NOW())
            ");
            
            foreach ($data['participants'] as $participant_id) {
                $participant_id = (int)$participant_id;
                if ($participant_id > 0) {
                    $stmt_part->execute([
                        ':reunion_id' => $reunion_id,
                        ':user_id' => $participant_id
                    ]);
                }
            }
            error_log("Participants ajoutés: " . count($data['participants']));
        }

        // Fichiers
        if (!empty($_FILES['files']['name'][0])) {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/GSCV/pages/assets/uploads/reunions_docs/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                throw new Exception('Impossible de créer le dossier d\'upload');
            }

            $stmt_doc = $pdo->prepare("
                INSERT INTO documents 
                (reunion_id, nom_fichier, chemin_fichier, type_fichier, taille_fichier, telecharger_par, date_creation) 
                VALUES (:reunion_id, :nom, :chemin, :type, :taille, :user_id, NOW())
            ");

            foreach ($_FILES['files']['name'] as $i => $name) {
                if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
                    if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        error_log("Erreur upload fichier $name: " . $_FILES['files']['error'][$i]);
                    }
                    continue;
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
                    error_log("Extension non autorisée: $ext");
                    continue;
                }

                if ($_FILES['files']['size'][$i] > 10485760) { // 10MB
                    error_log("Fichier trop volumineux: $name");
                    continue;
                }

                $new_name = uniqid('doc_') . '.' . $ext;
                $dest_path = $upload_dir . $new_name;

                if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $dest_path)) {
                    $stmt_doc->execute([
                        ':reunion_id' => $reunion_id,
                        ':nom' => $name,
                        ':chemin' => 'uploads/reunions_docs/' . $new_name,
                        ':type' => $_FILES['files']['type'][$i],
                        ':taille' => $_FILES['files']['size'][$i],
                        ':user_id' => $_SESSION['user_id']
                    ]);
                } else {
                    error_log("Échec déplacement fichier: $name");
                }
            }
            error_log("Traitement des fichiers terminé.");
        }

        $pdo->commit();
        
        // Enregistrer la piste d'audit
        enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'reunions', 'Ajout réunion', 1);
        
        // Définir le message de succès en session pour la page suivante
        $_SESSION['success_message'] = "Réunion créée avec succès.";

       
        
        // Récupérer les détails complets de la réunion pour la réponse
        $stmt = $pdo->prepare("SELECT * FROM reunions WHERE id = ?");
        $stmt->execute([$reunion_id]);
        $reunion_data = $stmt->fetch(PDO::FETCH_ASSOC);

        // Envoi de notifications par e-mail après le commit
        if ($data['notify_email'] && !empty($data['participants'])) {
            ob_start();
            $placeholders = implode(',', array_fill(0, count($data['participants']), '?'));
            $stmt_emails = $pdo->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur IN ($placeholders)");
            $stmt_emails->execute(array_map('intval', $data['participants']));
            $emails = $stmt_emails->fetchAll(PDO::FETCH_COLUMN);

            if ($emails) {
                $subject = "Invitation à une nouvelle réunion : " . htmlspecialchars($data['titre']);
                
                foreach ($emails as $email) {
                    $message = "Bonjour,<br><br>" .
                               "Vous avez été convié(e) à la réunion suivante :<br><br>" .
                               "<strong>Titre :</strong> " . htmlspecialchars($data['titre']) . "<br>" .
                               "<strong>Date :</strong> " . date('d/m/Y', strtotime($data['date'])) . "<br>" .
                               "<strong>Heure :</strong> " . htmlspecialchars($data['heure']) . "<br>" .
                               "<strong>Lieu :</strong> " . htmlspecialchars($data['lieu']) . "<br>";
                    
                    if (!empty($data['description'])) {
                        $message .= "<strong>Description :</strong> " . nl2br(htmlspecialchars($data['description'])) . "<br>";
                    }

                    $message .= "<br>Cordialement,<br>L'équipe Check Master";

                    sendEmail("Administrateur GSCV", "axelangegomez2004@gscv.com", $email, $subject, $message);
                }
                error_log("Notifications par e-mail envoyées à " . count($emails) . " participant(s).");
            }
            ob_end_clean();
        }

        // Envoi d'un message interne à chaque participant
        $objet = "Invitation à une réunion par " . $_SESSION['user_fullname'] . " : " . $data['titre'];
        $contenu = "Vous êtes invité(e) à la réunion « " . htmlspecialchars($data['titre']) . " » le " . date('d/m/Y', strtotime($data['date'])) . " à " . htmlspecialchars($data['heure']) . " à " . htmlspecialchars($data['lieu']) . ".";
        foreach ($data['participants'] as $participant_id) {
            try {
                sendMessage(
                    $_SESSION['user_id'], // expediteur_id
                    $participant_id,      // destinataire_id
                    $objet,
                    $contenu,
                    'notification',       // type_message
                    'réunion',            // catégorie
                    'haute'          // priorité
                );
            } catch (Exception $e) {
                error_log("Erreur lors de l'envoi du message interne à l'utilisateur $participant_id : " . $e->getMessage());
            }
        }
          
        // Réponse JSON complète
        echo json_encode([
            'success' => true,
            'message' => 'Réunion enregistrée avec succès',
            'reunion_id' => $reunion_id,
            'reunion' => $reunion_data
        ]);

        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Erreur PDO: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement',
            'error' => $e->getMessage()
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log("Erreur globale: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}
?>