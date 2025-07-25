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

if (!isset($_POST['demande_ids'])) {
    echo json_encode(['success' => false, 'error' => 'Aucune demande sélectionnée.']);
    exit;
}

$demande_ids_json = $_POST['demande_ids'];
$demande_ids = json_decode($demande_ids_json, true);

if (empty($demande_ids)) {
    echo json_encode(['success' => false, 'error' => 'Aucune demande valide sélectionnée.']);
    exit;
}

$demande_ids = array_map('intval', $demande_ids);
$placeholders = implode(',', array_fill(0, count($demande_ids), '?'));

$pdo->beginTransaction();

try {
    // La suppression se fait sur la table 'demande_soutenance'
    $sql = "DELETE FROM demande_soutenance WHERE id_demande IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute($demande_ids);
    $deleted_count = $stmt->rowCount();

    $pdo->commit();

    if ($deleted_count > 0) {
        echo json_encode(['success' => true, 'message' => "$deleted_count demande(s) ont été supprimée(s)."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucune demande n\'a été supprimée.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression de demandes de soutenance : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données.']);
}
?> 