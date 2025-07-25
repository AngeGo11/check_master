<?php
header('Content-Type: application/json');
require_once '../config/db_connect.php';

$id_ecue = $_GET['id_ecue'] ?? '';

if (empty($id_ecue)) {
    echo json_encode(['success' => false, 'message' => 'ID ECUE manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT ue.id_ue, ue.lib_ue, ue.credit
        FROM ue
        JOIN ecue ON ecue.id_ue = ue.id_ue
        WHERE ecue.id_ecue = ?
    ");
    $stmt->execute([$id_ecue]);
    $ues = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($ues);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 