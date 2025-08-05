<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/Controllers/SoutenanceController.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['user_id']) || !isset($data['rapport_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit();
}

try {
    // Initialiser la connexion à la base de données
    $pdo = DataBase::getConnection();
    
    // Initialiser le contrôleur
    $soutenanceController = new SoutenanceController($pdo);
    
    // Vérifier la disponibilité du compte rendu
    $result = $soutenanceController->checkCompteRenduAvailability($data['user_id'], $data['rapport_id']);
    
    // Retourner la réponse JSON
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Erreur lors de la vérification du compte rendu: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur interne du serveur']);
}
?> 