<?php
require_once '../../../config/db_connect.php';
require_once '../../../includes/audit_utils.php';

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
    // Il faut également supprimer les données dépendantes dans d'autres tables
    $pdo->prepare("DELETE FROM valider WHERE id_rapport_etd IN ($placeholders)")->execute($rapport_ids);
    $pdo->prepare("DELETE FROM deposer WHERE id_rapport_etd IN ($placeholders)")->execute($rapport_ids);
    $pdo->prepare("DELETE FROM approuver WHERE id_rapport_etd IN ($placeholders)")->execute($rapport_ids);
    $pdo->prepare("DELETE FROM partage_rapport WHERE id_rapport_etd IN ($placeholders)")->execute($rapport_ids);
    $pdo->prepare("DELETE FROM compte_rendu WHERE id_rapport_etd IN ($placeholders)")->execute($rapport_ids);
    
    // Ensuite, supprimer les rapports eux-mêmes
    $sql = "DELETE FROM rapport_etudiant WHERE id_rapport_etd IN ($placeholders)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute($rapport_ids);
    $deleted_count = $stmt->rowCount();

    $pdo->commit();
    enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'etudiants', 'Suppression multiple rapports', $deleted_count);

    if ($deleted_count > 0) {
        echo json_encode(['success' => true, 'message' => "$deleted_count rapport(s) ont été supprimé(s)."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucun rapport n\'a été supprimé.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression de rapports étudiants : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données.']);
}
?> 