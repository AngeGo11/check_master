<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/Controllers/ValidationController.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $controller = new ValidationController();
    $action = $_REQUEST['action'] ?? '';

    if (empty($action)) {
        echo json_encode(['success' => false, 'error' => 'Action non spécifiée']);
        exit();
    }

    switch ($action) {
        case 'valider':
            $id_rapport = $_POST['id_rapport'] ?? null;
            $id_ens = $_POST['id_ens'] ?? null;
            $commentaire = $_POST['commentaire_validation'] ?? '';
            $decision = $_POST['decision'] ?? null;

            if (empty($id_rapport) || empty($id_ens) || empty($decision)) {
                echo json_encode(['success' => false, 'error' => 'Champs obligatoires manquants']);
                exit();
            }

            $controller->validerRapports($id_rapport, $id_ens,  $commentaire, $decision);
            exit();

        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
            exit();
    }

} catch (Exception $e) {
    error_log('Erreur AJAX validation: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
    exit();
}
