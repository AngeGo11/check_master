<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

// Vérification des permissions
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

// Récupération de l'ID de la sauvegarde
$backupId = $_GET['id'] ?? null;

if (!$backupId) {
    $_SESSION['error'] = "ID de sauvegarde non spécifié.";
    header('Location: ../../index_commission.php?page=sauvegardes_et_restaurations');
    exit();
}

try {
    // Récupérer les informations de la sauvegarde
    $stmt = $pdo->prepare("SELECT nom_sauvegarde, nom_fichier FROM sauvegardes WHERE id_sauvegarde = ?");
    $stmt->execute([$backupId]);
    $backup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$backup) {
        throw new Exception("Sauvegarde non trouvée.");
    }
    
    // Vérifier si le fichier existe
    if (!file_exists($backup['nom_fichier'])) {
        throw new Exception("Le fichier de sauvegarde n'existe plus.");
    }
    
    // Définir les en-têtes pour le téléchargement
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . basename($backup['nom_sauvegarde']) . '.sql"');
    header('Content-Length: ' . filesize($backup['nom_fichier']));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Lire et envoyer le fichier
    readfile($backup['nom_fichier']);
    exit();
    
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors du téléchargement : " . $e->getMessage();
    header('Location: ../../index_commission.php?page=sauvegardes_et_restaurations');
    exit();
} 