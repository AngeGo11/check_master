<?php
// Démarrer la session
session_start();

require_once __DIR__ . '/../../../config/config.php';

// Établir la connexion à la base de données
$pdo = DataBase::getConnection();

// Vérification de sécurité
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            updateProfile();
            break;
        case 'change_password':
            changePassword();
            break;
        case 'update_notifications':
            updateNotifications();
            break;
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    error_log('Erreur mise à jour profil: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function updateProfile() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    
    $nom = $_POST['nom'] ?? '';
    $prenoms = $_POST['prenoms'] ?? '';
    $email = $_POST['adresse_mail'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $adresse = $_POST['adresse'] ?? '';
    $ville = $_POST['ville'] ?? '';
    $pays = $_POST['pays'] ?? '';

    // Validation des données
    if (empty($nom) || empty($prenoms) || empty($email)) {
        throw new Exception('Les champs nom, prénoms et email sont obligatoires');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Adresse email invalide');
    }

    try {
        $pdo->beginTransaction();

        // Mettre à jour selon le type d'utilisateur
        switch ($userType) {
            case 'enseignant':
                $stmt = $pdo->prepare("
                    UPDATE enseignants 
                    SET nom_ens = ?, prenoms_ens = ?, email_ens = ?, num_tel_ens = ?
                    WHERE id_ens = ?
                ");
                $stmt->execute([$nom, $prenoms, $email, $telephone, $userId]);
                break;
                
            case 'etudiant':
                $stmt = $pdo->prepare("
                    UPDATE etudiants 
                    SET nom_etd = ?, prenoms_etd = ?, email_etd = ?, num_tel_etd = ?,
                        adresse_etd = ?, ville_etd = ?, pays_etd = ?
                    WHERE num_etd = ?
                ");
                $stmt->execute([$nom, $prenoms, $email, $telephone, $adresse, $ville, $pays, $userId]);
                break;
                
            case 'personnel_adm':
                $stmt = $pdo->prepare("
                    UPDATE personnel_administratif 
                    SET nom_personnel_adm = ?, prenoms_personnel_adm = ?, 
                        email_personnel_adm = ?, tel_personnel_adm = ?
                    WHERE id_personnel_adm = ?
                ");
                $stmt->execute([$nom, $prenoms, $email, $telephone, $userId]);
                break;
                
            default:
                // La table utilisateur n'a que login et email
                $stmt = $pdo->prepare("
                    UPDATE utilisateur 
                    SET login_utilisateur = ?
                    WHERE id_utilisateur = ?
                ");
                $stmt->execute([$email, $userId]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function changePassword() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    $ancienMdp = $_POST['ancien_mdp'] ?? '';
    $nouveauMdp = $_POST['nouveau_mdp'] ?? '';
    $confirmMdp = $_POST['confirm_mdp'] ?? '';

    if (empty($ancienMdp) || empty($nouveauMdp) || empty($confirmMdp)) {
        throw new Exception('Tous les champs sont requis');
    }

    if ($nouveauMdp !== $confirmMdp) {
        throw new Exception('Les mots de passe ne correspondent pas');
    }

    if (strlen($nouveauMdp) < 8) {
        throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
    }

    try {
        // Vérifier l'ancien mot de passe
        $userInfo = getUserInfo($userId, $userType);
        if (!$userInfo) {
            throw new Exception('Utilisateur non trouvé');
        }

        // Vérifier l'ancien mot de passe (selon le type d'utilisateur)
        $passwordField = getPasswordField($userType);
        if (!password_verify($ancienMdp, $userInfo[$passwordField])) {
            throw new Exception('Ancien mot de passe incorrect');
        }

        // Mettre à jour le mot de passe
        $hashedPassword = password_hash($nouveauMdp, PASSWORD_DEFAULT);
        $table = getUserTable($userType);
        $idField = getIdField($userType);
        
        $stmt = $pdo->prepare("UPDATE $table SET $passwordField = ? WHERE $idField = ?");
        $stmt->execute([$hashedPassword, $userId]);

        echo json_encode(['success' => true, 'message' => 'Mot de passe changé avec succès']);
        
    } catch (Exception $e) {
        throw $e;
    }
}

function updateNotifications() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    
    $notifEmail = isset($_POST['notif_email']) ? 1 : 0;
    $notifSms = isset($_POST['notif_sms']) ? 1 : 0;
    $notifPush = isset($_POST['notif_push']) ? 1 : 0;

    try {
        // Supprimer les anciennes préférences de notification
        $stmt = $pdo->prepare("
            DELETE FROM messages 
            WHERE expediteur_id = ? 
            AND type_message = 'notification' 
            AND categorie = 'preferences'
        ");
        $stmt->execute([$userId]);
        
        // Créer un message pour stocker les préférences de notification
        $preferencesData = json_encode([
            'notif_email' => $notifEmail,
            'notif_sms' => $notifSms,
            'notif_push' => $notifPush,
            'user_type' => $userType
        ]);
        
        $stmt = $pdo->prepare("
            INSERT INTO messages (
                expediteur_id, 
                destinataire_id, 
                destinataire_type, 
                objet, 
                contenu, 
                type_message, 
                categorie, 
                priorite, 
                statut, 
                date_creation
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,           // expediteur_id (l'utilisateur lui-même)
            $userId,           // destinataire_id (l'utilisateur lui-même)
            'individuel',      // destinataire_type
            'Préférences de notification', // objet
            $preferencesData,  // contenu (JSON des préférences)
            'notification',    // type_message
            'preferences',     // categorie
            'basse',           // priorite
            'envoyé'           // statut
        ]);

        echo json_encode(['success' => true, 'message' => 'Préférences mises à jour avec succès']);
        
    } catch (Exception $e) {
        throw $e;
    }
}

// Fonctions utilitaires
function getUserInfo($userId, $userType) {
    global $pdo;
    $table = getUserTable($userType);
    $idField = getIdField($userType);
    
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE $idField = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserTable($userType) {
    switch ($userType) {
        case 'enseignant': return 'enseignants';
        case 'etudiant': return 'etudiants';
        case 'personnel_adm': return 'personnel_administratif';
        default: return 'utilisateur';
    }
}

function getIdField($userType) {
    switch ($userType) {
        case 'enseignant': return 'id_ens';
        case 'etudiant': return 'num_etd';
        case 'personnel_adm': return 'id_personnel_adm';
        default: return 'id_utilisateur';
    }
}

function getPasswordField($userType) {
    switch ($userType) {
        case 'enseignant': return 'mdp_ens';
        case 'etudiant': return 'mdp_etd';
        case 'personnel_adm': return 'mdp_personnel_adm';
        default: return 'mdp_utilisateur';
    }
}
?> 