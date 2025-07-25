<?php
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../../storage/logs/php-error.log');
error_reporting(E_ALL);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/Controllers/EnseignantController.php';

$controller = new EnseignantController($pdo);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = [];
    if (!empty($_POST['ids'])) {
        if (is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
        } elseif (strpos($_POST['ids'], ',') !== false) {
            $ids = explode(',', $_POST['ids']);
        } else {
            $ids = [$_POST['ids']];
        }
    } elseif (!empty($_POST['enseignant_ids'])) {
        $ids = json_decode($_POST['enseignant_ids'], true);
    }

    $success = true;
    $errors = [];
    foreach ($ids as $id) {
        try {
            if (!$controller->delete($id)) {
                $success = false;
                $errors[] = "Échec de suppression pour l'ID: $id";
            }
        } catch (Exception $e) {
            $success = false;
            $errors[] = "Exception lors de la suppression de l'ID $id: " . $e->getMessage();
        }
    }
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Suppression réussie.' : 'Erreur lors de la suppression.',
        'errors' => $errors
    ]);
    exit;
}
echo json_encode(['success' => false, 'message' => 'Requête invalide.']);
exit;
