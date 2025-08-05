<?php
// Configuration de la base de données
$host = 'db';
$dbname = 'check_master_db';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Erreur de connexion à la base de données',
        'error' => $e->getMessage()
    ]);
    exit;
}

// Configuration de l'URL de base
define('BASE_URL', '/GSCV+/public');

// Configuration des chemins
define('APP_ROOT', dirname(__DIR__));
define('VIEWS_PATH', APP_ROOT . '/Views');
define('CONTROLLERS_PATH', APP_ROOT . '/Controllers');
define('MODELS_PATH', APP_ROOT . '/Models'); 