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
    // Cette action supprime définitivement les rapports de la base de données.
    // Cela peut avoir des conséquences si d'autres tables y font référence (ex: comptes rendus).
    // Assurez-vous que des cascades sont en place ou que ce comportement est souhaité.
    $sql = "DELETE FROM rapport_etudiant WHERE id_rapport_etd IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute($rapport_ids);
    $deleted_count = $stmt->rowCount();

    $pdo->commit();

    if ($deleted_count > 0) {
        echo json_encode(['success' => true, 'message' => "$deleted_count rapport(s) ont été supprimé(s)."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucun rapport n\'a été supprimé.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression de rapports : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données. Il se peut que d\'autres données (comme des comptes rendus) soient liées à ces rapports.']);
}
?> 