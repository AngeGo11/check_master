<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_clean();
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
session_start();

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si l'ID du compte rendu est fourni
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID du compte rendu manquant']);
    exit;
}

try {
    $crId = $_GET['id'];
    
    // Récupérer le chemin du fichier depuis la table compte_rendu
    $stmt = $pdo->prepare("
        SELECT fichier_cr FROM compte_rendu 
        WHERE id_cr = ?
    ");
    $stmt->execute([$crId]);
    $compteRendu = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($compteRendu && isset($compteRendu['fichier_cr'])) {
        echo json_encode(['success' => true, 'filePath' => $compteRendu['fichier_cr']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fichier compte rendu non trouvé']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération du fichier : ' . $e->getMessage()]);
}
?> 