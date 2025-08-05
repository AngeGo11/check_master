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
    $confirmDelete = $_POST['confirm_delete'] ?? '';
    
    if ($confirmDelete !== '1') {
        throw new Exception('Confirmation requise pour supprimer le compte');
    }
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    try {
        // Supprimer les données selon le type d'utilisateur
        switch ($userType) {
            case 'enseignant':
                deleteEnseignantData($userId);
                break;
            case 'etudiant':
                deleteEtudiantData($userId);
                break;
            case 'personnel_adm':
                deletePersonnelAdmData($userId);
                break;
            default:
                deleteUtilisateurData($userId);
        }
        
        // Supprimer les données de sécurité
        deleteSecurityData($userId, $userType);
        
        // Supprimer les fichiers associés
        deleteUserFiles($userId, $userType);
        
        // Valider la transaction
        $pdo->commit();
        
        // Détruire la session
        session_destroy();
        
        echo json_encode([
            'success' => true,
            'message' => 'Compte supprimé avec succès'
        ]);
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Erreur suppression compte: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function deleteEnseignantData($userId) {
    global $pdo;
    
    // Supprimer les données de l'enseignant
    $stmt = $pdo->prepare("DELETE FROM enseignants WHERE id_ens = ?");
    $stmt->execute([$userId]);
    
    // Supprimer les relations dans d'autres tables
    $stmt = $pdo->prepare("DELETE FROM commission_membres WHERE id_ens = ?");
    $stmt->execute([$userId]);
    
    $stmt = $pdo->prepare("UPDATE reunions SET id_organisateur = NULL WHERE id_organisateur = ?");
    $stmt->execute([$userId]);
}

function deleteEtudiantData($userId) {
    global $pdo;
    
    // Supprimer les données de l'étudiant
    $stmt = $pdo->prepare("DELETE FROM etudiants WHERE num_etd = ?");
    $stmt->execute([$userId]);
    
    // Supprimer les rapports de stage
    $stmt = $pdo->prepare("DELETE FROM rapport_etudiant WHERE num_etd = ?");
    $stmt->execute([$userId]);
    
    // Supprimer les comptes rendus
    $stmt = $pdo->prepare("DELETE FROM compte_rendu WHERE num_etd = ?");
    $stmt->execute([$userId]);
    
    // Supprimer les archives
    $stmt = $pdo->prepare("DELETE FROM archives WHERE id_rapport_etd IN (SELECT id FROM rapport_etudiant WHERE num_etd = ?)");
    $stmt->execute([$userId]);
    
    $stmt = $pdo->prepare("DELETE FROM archives WHERE id_cr IN (SELECT id FROM compte_rendu WHERE num_etd = ?)");
    $stmt->execute([$userId]);
}

function deletePersonnelAdmData($userId) {
    global $pdo;
    
    // Supprimer les données du personnel administratif
    $stmt = $pdo->prepare("DELETE FROM personnel_administratif WHERE id_personnel_adm = ?");
    $stmt->execute([$userId]);
    
    // Supprimer les relations dans d'autres tables
    $stmt = $pdo->prepare("DELETE FROM commission_membres WHERE id_personnel_adm = ?");
    $stmt->execute([$userId]);
}

function deleteUtilisateurData($userId) {
    global $pdo;
    
    // Supprimer les données de l'utilisateur
    $stmt = $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$userId]);
}

function deleteSecurityData($userId, $userType) {
    global $pdo;
    
    // Supprimer l'authentification à deux facteurs
    $stmt = $pdo->prepare("DELETE FROM two_factor_auth WHERE user_id = ? AND user_type = ?");
    $stmt->execute([$userId, $userType]);
    
    // Supprimer les sessions
    $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ? AND user_type = ?");
    $stmt->execute([$userId, $userType]);
    
    // Supprimer l'historique de connexion
    $stmt = $pdo->prepare("DELETE FROM login_history WHERE user_id = ? AND user_type = ?");
    $stmt->execute([$userId, $userType]);
    
    // Supprimer les messages
    $stmt = $pdo->prepare("DELETE FROM messages WHERE expediteur_id = ? OR destinataire_id = ?");
    $stmt->execute([$userId, $userId]);
    
    // Supprimer les notifications
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND user_type = ?");
    $stmt->execute([$userId, $userType]);
}

function deleteUserFiles($userId, $userType) {
    // Supprimer la photo de profil
    $profileDir = __DIR__ . '/../../../storage/uploads/profiles/';
    $files = glob($profileDir . "*_{$userId}_*");
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    // Supprimer les documents uploadés selon le type d'utilisateur
    if ($userType === 'etudiant') {
        // Supprimer les rapports de stage
        $rapportDir = __DIR__ . '/../../../storage/uploads/rapports/';
        $files = glob($rapportDir . "*_{$userId}_*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        // Supprimer les comptes rendus
        $crDir = __DIR__ . '/../../../storage/uploads/compte_rendu/';
        $files = glob($crDir . "*_{$userId}_*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}

// Fonction pour anonymiser les données au lieu de les supprimer (optionnel)
function anonymizeUserData($userId, $userType) {
    global $pdo;
    
    $anonymizedData = [
        'nom' => 'Utilisateur Supprimé',
        'prenoms' => 'N/A',
        'email' => 'deleted_' . time() . '@deleted.local',
        'telephone' => '0000000000'
    ];
    
    switch ($userType) {
        case 'enseignant':
            $stmt = $pdo->prepare("
                UPDATE enseignants 
                SET nom_ens = ?, prenom_ens = ?, email_ens = ?, num_tel_ens = ?, 
                    photo_ens = NULL, statut_ens = 'Supprimé'
                WHERE id_ens = ?
            ");
            $stmt->execute([
                $anonymizedData['nom'],
                $anonymizedData['prenoms'],
                $anonymizedData['email'],
                $anonymizedData['telephone'],
                $userId
            ]);
            break;
            
        case 'etudiant':
            $stmt = $pdo->prepare("
                UPDATE etudiants 
                SET nom_etd = ?, prenoms_etd = ?, email_etd = ?, num_tel_etd = ?, 
                    photo_etd = NULL, statut_etd = 'Supprimé'
                WHERE num_etd = ?
            ");
            $stmt->execute([
                $anonymizedData['nom'],
                $anonymizedData['prenoms'],
                $anonymizedData['email'],
                $anonymizedData['telephone'],
                $userId
            ]);
            break;
            
        case 'personnel_adm':
            $stmt = $pdo->prepare("
                UPDATE personnel_administratif 
                SET nom_personnel_adm = ?, prenoms_personnel_adm = ?, email_personnel_adm = ?, 
                    tel_personnel_adm = ?, photo_personnel_adm = NULL, statut_personnel_adm = 'Supprimé'
                WHERE id_personnel_adm = ?
            ");
            $stmt->execute([
                $anonymizedData['nom'],
                $anonymizedData['prenoms'],
                $anonymizedData['email'],
                $anonymizedData['telephone'],
                $userId
            ]);
            break;
            
        default:
            $stmt = $pdo->prepare("
                UPDATE utilisateur 
                SET login_utilisateur = ?, statut_utilisateur = 'Inactif'
                WHERE id_utilisateur = ?
            ");
            $stmt->execute([
                $anonymizedData['email'],
                $userId
            ]);
    }
}
?> 