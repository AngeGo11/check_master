<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/Controllers/PersonnelAdministratifController.php';

$controller = new PersonnelAdministratifController($pdo);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = [];
    if (!empty($_POST['ids'])) {
        // Suppression multiple (ids séparés par virgule ou tableau JSON)
        if (is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
        } else if (strpos($_POST['ids'], ',') !== false) {
            $ids = explode(',', $_POST['ids']);
        } else {
            $ids = [$_POST['ids']];
        }
    } elseif (!empty($_POST['personnel_ids'])) {
        $ids = json_decode($_POST['personnel_ids'], true);
    }
    $success = true;
    foreach ($ids as $id) {
        if (!$controller->delete($id)) {
            $success = false;
        }
    }
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Suppression réussie.' : 'Erreur lors de la suppression.'
    ]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
exit;
