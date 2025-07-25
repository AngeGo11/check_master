<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

header('Content-Type: application/json');



// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

if (!isset($_POST['ids'])) {
    echo json_encode(['success' => false, 'error' => 'Aucun document sélectionné.']);
    exit;
}

$ids_json = $_POST['ids'];
$ids_array = json_decode($ids_json, true);

if (empty($ids_array)) {
    echo json_encode(['success' => false, 'error' => 'Aucun document valide sélectionné.']);
    exit;
}

$rapport_ids = [];
$cr_ids = [];

foreach ($ids_array as $composite_id) {
    // S'assurer que le format est correct avant de décomposer
    if (strpos($composite_id, ':') !== false) {
        list($type, $id) = explode(':', $composite_id, 2);
        if ($type === 'Rapport' && filter_var($id, FILTER_VALIDATE_INT)) {
            $rapport_ids[] = (int)$id;
        } elseif ($type === 'Compte rendu' && filter_var($id, FILTER_VALIDATE_INT)) {
            $cr_ids[] = (int)$id;
        }
    }
}

// Vérifier que l'utilisateur a le droit de supprimer ces archives
$user_id = $_SESSION['user_id'];

$pdo->beginTransaction();

try {
    $deleted_count = 0;

    if (!empty($rapport_ids)) {
        $in_rapport = implode(',', array_fill(0, count($rapport_ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM archives WHERE id_rapport_etd IN ($in_rapport) AND id_utilisateur = ?");
        $params = array_merge($rapport_ids, [$user_id]);
        $stmt->execute($params);
        $deleted_count += $stmt->rowCount();
    }

    if (!empty($cr_ids)) {
        $in_cr = implode(',', array_fill(0, count($cr_ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM archives WHERE id_cr IN ($in_cr) AND id_utilisateur = ?");
        $params = array_merge($cr_ids, [$user_id]);
        $stmt->execute($params);
        $deleted_count += $stmt->rowCount();
    }

    $pdo->commit();

    if ($deleted_count > 0) {
        echo json_encode(['success' => true, 'message' => "$deleted_count document(s) supprimé(s) avec succès."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucun document n\'a été supprimé. Vérifiez vos permissions.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur suppression archives: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données lors de la suppression.']);
}

?> 