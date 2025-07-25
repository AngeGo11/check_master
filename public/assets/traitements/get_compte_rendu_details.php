<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
require_once 'traitements_comptes_rendus.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

try {
    $details = getCompteRenduDetails($_GET['id']);
    if ($details) {
        echo json_encode($details);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Compte rendu non trouvé']);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des détails : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des détails']);
} 