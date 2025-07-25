<?php
require_once '../../../config/db_connect.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté.']);
    exit;
}

if (!isset($_POST['cr_ids'])) {
    echo json_encode(['success' => false, 'error' => 'Aucun compte rendu sélectionné.']);
    exit;
}

$cr_ids_json = $_POST['cr_ids'];
$cr_ids = json_decode($cr_ids_json, true);

if (empty($cr_ids)) {
    echo json_encode(['success' => false, 'error' => 'Aucun compte rendu valide sélectionné.']);
    exit;
}

$cr_ids = array_map('intval', $cr_ids);
$placeholders = implode(',', array_fill(0, count($cr_ids), '?'));

$pdo->beginTransaction();

try {
    $sql = "DELETE FROM compte_rendu WHERE id_cr IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute($cr_ids);
    $deleted_count = $stmt->rowCount();

    $pdo->commit();

    if ($deleted_count > 0) {
        echo json_encode(['success' => true, 'message' => "$deleted_count compte(s) rendu(s) ont été supprimé(s)."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucun compte rendu n\'a été supprimé.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression de comptes rendus : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données.']);
}
?> 