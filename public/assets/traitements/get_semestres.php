<?php
require_once '../../../config/db_connect.php';

// Vérifier si l'ID du niveau est fourni
if (!isset($_GET['niveau_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID du niveau non fourni']);
    exit;
}

$niveau_id = $_GET['niveau_id'];

try {
    // Préparer et exécuter la requête pour obtenir les semestres du niveau
    $query = "SELECT * FROM semestre WHERE id_niv_etd = :niveau_id ORDER BY lib_semestre";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['niveau_id' => $niveau_id]);
    
    // Récupérer les résultats
    $semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Renvoyer les résultats en JSON
    header('Content-Type: application/json');
    echo json_encode($semestres);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des semestres: ' . $e->getMessage()]);
} 