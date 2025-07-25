<?php
require_once '../../../config/db_connect.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de l\'UE non fourni']);
    exit;
}

$id_ue = $_GET['id'];

try {
    $query = "SELECT u.*, ens.nom_ens, ens.prenoms_ens, n.lib_niv_etd, s.lib_semestre 
              FROM ue u 
              LEFT JOIN enseignants ens ON ens.id_ens = u.id_ens
              JOIN niveau_etude n ON u.id_niv_etd = n.id_niv_etd 
              JOIN semestre s ON u.id_semestre = s.id_semestre 
              WHERE u.id_ue = :id_ue";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id_ue' => $id_ue]);
    $ue = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ue) {
        http_response_code(404);
        echo json_encode(['error' => 'UE non trouvée']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode($ue);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des détails de l\'UE']);
} 