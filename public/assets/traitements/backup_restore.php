<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';



// Vérification des permissions (seul l'administrateur peut faire des sauvegardes)
$stmt = $pdo->prepare("
    SELECT gu.lib_gu 
    FROM posseder p 
    JOIN groupe_utilisateur gu ON p.id_gu = gu.id_gu 
    WHERE p.id_util = ? AND gu.lib_gu = 'Administrateur plateforme'
");
$stmt->execute([$_SESSION['user_id']]);
$is_admin = $stmt->fetch();

if (!$is_admin) {
    $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires pour effectuer cette action.";
    header('Location: ../../index_commission.php?page=sauvegardes_et_restaurations');
    exit();
}

// Traitement des actions
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_backup':
        createBackup();
        break;
    case 'restore_backup':
        restoreBackup();
        break;
    case 'delete_backup':
        deleteBackup();
        break;
    default:
        $_SESSION['error'] = "Action non reconnue.";
        header('Location: ../../index_commission.php?page=sauvegardes_et_restaurations');
        exit();
}

function createBackup() {
    global $pdo;
    
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($name)) {
        $_SESSION['error'] = "Le nom de la sauvegarde est requis.";
        header('Location: ../../index_commission.php?page=sauvegardes_et_restaurations');
        exit();
    }
    
    try {
        // Créer le dossier de sauvegarde s'il n'existe pas
        $backupDir = dirname(dirname(dirname(__FILE__))) . '/sauvegardes/';
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
        
        // Générer le nom du fichier
        $filename = $backupDir . date('Y-m-d_H-i-s') . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $name) . '.sql';
        
        // Commande pour créer la sauvegarde
        $command = sprintf(
            'mysqldump --host=localhost --user=root --password= %s > %s',
            escapeshellarg('check_master_db'),
            escapeshellarg($filename)
        );
        
        // Exécuter la commande et capturer la sortie
        exec($command . ' 2>&1', $output, $returnVar);
        
        if ($returnVar === 0) {
            // Enregistrer les informations dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO sauvegardes (nom_sauvegarde, description, nom_fichier, date_creation, taille_fichier) 
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $name,
                $description,
                $filename,
                filesize($filename)
            ]);
            
            $_SESSION['success'] = "La sauvegarde a été créée avec succès.";
        } else {
            error_log("Erreur mysqldump: " . implode("\n", $output));
            $_SESSION['error'] = "Erreur lors de la création de la sauvegarde. Code d'erreur: " . $returnVar;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de la création de la sauvegarde: " . $e->getMessage();
    }
    
    header('Location: ../../index_commission.php?page=sauvegardes_et_restaurations');
    exit();
}

function restoreBackup() {
    global $pdo;
    
    $backupId = $_POST['backup_id'] ?? null;
    $backupFile = $_FILES['backup_file'] ?? null;
    
    try {
        if ($backupId) {
            // Restaurer depuis une sauvegarde existante
            $stmt = $pdo->prepare("SELECT nom_fichier FROM sauvegardes WHERE id_sauvegarde = ?");
            $stmt->execute([$backupId]);
            $backup = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$backup) {
                throw new Exception("Sauvegarde non trouvée.");
            }
            
            $filename = $backup['nom_fichier'];
        } elseif ($backupFile && $backupFile['error'] === UPLOAD_ERR_OK) {
            // Restaurer depuis un fichier uploadé
            $filename = $backupFile['tmp_name'];
        } else {
            throw new Exception("Aucune sauvegarde sélectionnée.");
        }
        
        // Commande pour restaurer la sauvegarde
        $command = sprintf(
            'mysql --host=localhost --user=root --password= %s < %s',
            escapeshellarg('check_master_db'),
            escapeshellarg($filename)
        );
        
        // Exécuter la commande et capturer la sortie
        exec($command . ' 2>&1', $output, $returnVar);
        
        if ($returnVar === 0) {
            $_SESSION['success'] = "La restauration a été effectuée avec succès.";
        } else {
            error_log("Erreur mysql: " . implode("\n", $output));
            throw new Exception("Erreur lors de la restauration. Code d'erreur: " . $returnVar);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de la restauration : " . $e->getMessage();
    }
    
    header('Location: ../../index_commission.php?page=sauvegardes_et_restaurations');
    exit();
}

function deleteBackup() {
    global $pdo;
    
    $backupId = $_POST['backup_id'] ?? null;
    
    if (!$backupId) {
        $_SESSION['error'] = "ID de sauvegarde non spécifié.";
        header('Location: ../../index_commission.php?page=sauvegardes_et_restaurations');
        exit();
    }
    
    try {
        // Récupérer les informations de la sauvegarde
        $stmt = $pdo->prepare("SELECT nom_fichier FROM sauvegardes WHERE id_sauvegarde = ?");
        $stmt->execute([$backupId]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$backup) {
            throw new Exception("Sauvegarde non trouvée.");
        }
        
        // Supprimer le fichier
        if (file_exists($backup['nom_fichier'])) {
            unlink($backup['nom_fichier']);
        }
        
        // Supprimer l'enregistrement de la base de données
        $stmt = $pdo->prepare("DELETE FROM sauvegardes WHERE id_sauvegarde = ?");
        $stmt->execute([$backupId]);
        
        $_SESSION['success'] = "La sauvegarde a été supprimée avec succès.";
    } catch (Exception $e) {
        error_log($e->getMessage());
        $_SESSION['error'] = "Une erreur est survenue lors de la suppression de la sauvegarde.";
    }
    
    header('Location: ../../index_commission.php?page=sauvegardes_et_restaurations');
    exit();
} 