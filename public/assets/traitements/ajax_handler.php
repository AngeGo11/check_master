<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/Controllers/InscriptionsEtudiantsController.php';

header(header: 'Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $controller = new InscriptionsEtudiantsController($pdo);
    
    // Détecter si c'est une requête GET ou POST
    $action = $_REQUEST['action'] ?? '';
    
    if (empty($action)) {
        echo json_encode(['error' => 'Action non spécifiée']);
        exit();
    }
    
    // Gérer les différentes actions
    switch ($action) {
        case 'get_etudiant_info':
            $num_carte = $_GET['num_carte'] ?? '';
            if (empty($num_carte)) {
                echo json_encode(['error' => 'Numéro carte requis']);
                exit();
            }
            $result = $controller->getEtudiantInfo($num_carte);
            echo json_encode($result);
            break;
            
        case 'get_montant_tarif':
            $niveau_id = $_POST['niveau'] ?? '';
            if (empty($niveau_id)) {
                echo json_encode(['error' => 'Niveau requis']);
                exit();
            }
            $result = $controller->getMontantTarif($niveau_id);
            echo json_encode($result);
            break;
            
        case 'get_payment_history':
            $numero_reglement = $_POST['numero_reglement'] ?? '';
            if (empty($numero_reglement)) {
                echo json_encode(['error' => 'Numéro de règlement requis']);
                exit();
            }
            $result = $controller->getPaymentHistory($numero_reglement);
            echo json_encode($result);
            break;
            
        case 'enregistrer_reglement':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['error' => 'Méthode non autorisée']);
                exit();
            }
            $result = $controller->handleAjaxRequest();
            echo json_encode($result);
            break;
            
        case 'supprimer_reglement':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['error' => 'Méthode non autorisée']);
                exit();
            }
            $result = $controller->handleAjaxRequest();
            echo json_encode($result);
            break;
            
        case 'supprimer_reglements':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['error' => 'Méthode non autorisée']);
                exit();
            }
            $result = $controller->handleAjaxRequest();
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['error' => 'Action non reconnue']);
            break;
    }
    
} catch (Exception $e) {
    error_log("Erreur AJAX handler: " . $e->getMessage());
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
} 