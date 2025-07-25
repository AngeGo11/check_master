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

if (!isset($_POST['rapport_ids'])) {
    echo json_encode(['success' => false, 'error' => 'Aucun rapport sélectionné.']);
    exit;
}

$rapport_ids_json = $_POST['rapport_ids'];
$rapport_ids = json_decode($rapport_ids_json, true);

if (empty($rapport_ids)) {
    echo json_encode(['success' => false, 'error' => 'Aucun rapport valide sélectionné.']);
    exit;
}

$rapport_ids = array_map('intval', $rapport_ids);
$placeholders = implode(',', array_fill(0, count($rapport_ids), '?'));

$pdo->beginTransaction();

try {
    // La suppression se fait sur la table 'approuver' car cette page gère 
    // les rapports en attente de validation par les enseignants.
    $sql = "DELETE FROM approuver WHERE id_rapport_etd IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute($rapport_ids);
    $deleted_count = $stmt->rowCount();

    $pdo->commit();

    if ($deleted_count > 0) {
        echo json_encode(['success' => true, 'message' => "$deleted_count rapport(s) ont été retiré(s) de la liste d'analyse."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucun rapport n\'a été supprimé.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression de rapport d'analyse : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données.']);
}
?> 