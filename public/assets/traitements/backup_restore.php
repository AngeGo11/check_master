<?php
session_start();

// Désactiver l'affichage des erreurs pour éviter de polluer les réponses
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../storage/logs/php-error.log');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/Controllers/SauvegardesEtRestaurationsController.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../pageConnexion.php');
    exit();
}

// Vérifier que l'utilisateur est administrateur
$pdo = DataBase::getConnection();
$stmt = $pdo->prepare("
    SELECT gu.lib_gu 
    FROM posseder p 
    JOIN groupe_utilisateur gu ON p.id_gu = gu.id_gu 
    WHERE p.id_util = ? AND gu.lib_gu = 'Administrateur plateforme'
");
$stmt->execute([$_SESSION['user_id']]);
$is_admin = $stmt->fetch();

if (!$is_admin) {
    $_SESSION['error_message'] = "Accès non autorisé. Seuls les administrateurs peuvent gérer les sauvegardes.";
    header('Location: ../../sauvegardes_et_restaurations.php');
    exit();
}

$sauvegardeController = new SauvegardesEtRestaurationsController($pdo);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Création d'une nouvelle sauvegarde
    if (isset($_POST['create_backup'])) {
        $backup_name = trim($_POST['backup_name'] ?? '');
        $backup_type = $_POST['backup_type'] ?? 'full';
        $backup_description = trim($_POST['backup_description'] ?? '');
        
        if (empty($backup_name)) {
            $_SESSION['error_message'] = "Le nom de la sauvegarde est requis.";
            header('Location: ../../sauvegardes_et_restaurations.php');
            exit();
        }
        
        $result = $sauvegardeController->createBackup($backup_name, $backup_type, $backup_description);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['error'];
        }
        
        header('Location: ../../?page=sauvegardes_et_restaurations');
        exit();
    }
    
    // Restauration depuis un fichier uploadé
    if (isset($_POST['restore_backup'])) {
        if (!isset($_FILES['restore_file']) || $_FILES['restore_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = "Erreur lors du téléchargement du fichier.";
        header('Location: ../../?page=sauvegardes_et_restaurations');
            exit();
        }
        
        $file = $_FILES['restore_file'];
        $result = $sauvegardeController->restoreBackup($file);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['error'];
        }
        
        header('Location: ../../?page=sauvegardes_et_restaurations');
        exit();
    }
}

// Traitement des actions GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    // Téléchargement d'une sauvegarde
    if (isset($_GET['download'])) {
        $filename = $_GET['download'];
        $result = $sauvegardeController->downloadBackup($filename);
        
        if (!$result['success']) {
            $_SESSION['error_message'] = $result['error'];
            header('Location: ../../sauvegardes_et_restaurations.php');
            exit();
        }
        // Le téléchargement se fait directement, pas de redirection
        exit();
    }
    
    // Restauration d'une sauvegarde existante
    if (isset($_GET['restore'])) {
        $filename = $_GET['restore'];
        $result = $sauvegardeController->restoreBackup($filename);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['error'];
        }
        
        header('Location: ../../?page=sauvegardes_et_restaurations');
        exit();
    }
    
    // Suppression
    if (isset($_GET['delete'])) {
        $filename = $_GET['delete'];
        $result = $sauvegardeController->deleteBackup($filename);
        
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
        } else {
            $_SESSION['error_message'] = $result['error'];
        }
        
        header('Location: ../../?page=sauvegardes_et_restaurations');
        exit();
    }
}

// Si aucune action n'est spécifiée, rediriger vers la page principale
header('Location: ../../?page=sauvegardes_et_restaurations');
exit();
?>