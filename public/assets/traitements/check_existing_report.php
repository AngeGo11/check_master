<?php
// Désactiver l'affichage des erreurs PHP
error_reporting(0);
ini_set('display_errors', 0);

// Définir le type de contenu JSON dès le début
header('Content-Type: application/json');

session_start();

try {
    // Utiliser un chemin absolu pour éviter les problèmes de chemin relatif
    $config_path = $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
    if (!file_exists($config_path)) {
        // Essayer un chemin alternatif
        $config_path = dirname(dirname(dirname(dirname(__FILE__)))) . '/config/db_connect.php';
    }
    require_once $config_path;
    
    // Vérifier que la connexion PDO est bien établie
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Connexion PDO non disponible');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur de connexion à la base de données',
        'message' => 'Impossible de se connecter à la base de données'
    ]);
    exit;
}

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['student_id']) || empty($input['student_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID étudiant manquant']);
    exit;
}

$student_id = $input['student_id'];

try {
    // Vérifier si l'étudiant a déjà un rapport en cours d'évaluation
    // Un rapport est considéré "en cours d'évaluation" s'il n'est pas "Rejeté" ou "Désapprouvé"
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM rapport_etudiant 
        WHERE num_etd = ? 
        AND statut_rapport NOT IN ('Rejeté', 'Désapprouvé')
    ");
    
    $stmt->execute([$student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $hasExistingReport = ($result['count'] > 0);
    
    // Retourner le résultat
    echo json_encode([
        'success' => true,
        'hasExistingReport' => $hasExistingReport,
        'message' => $hasExistingReport ? 'Un rapport est déjà en cours d\'évaluation' : 'Aucun rapport en cours d\'évaluation'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la vérification',
        'message' => 'Une erreur est survenue lors de la vérification du rapport'
    ]);
}
?> 