<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

$id_semestre = $_GET['id_semestre'] ?? null;

if (!$id_semestre) {
    echo json_encode(['success' => false, 'message' => 'ID du semestre manquant.']);
    exit;
}

try {
    // Requête simple pour récupérer les UE du semestre
    $stmt = $pdo->prepare("
        SELECT id_ue, lib_ue, credit_ue 
        FROM ue 
        WHERE id_semestre = ?
        ORDER BY lib_ue
    ");
    $stmt->execute([$id_semestre]);
    $ues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $ues]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
} 