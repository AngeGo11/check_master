<?php
/**
 * Configuration des chemins de l'application
 * Centralise tous les chemins pour éviter les problèmes de liens relatifs
 */

// Déterminer le chemin de base de l'application
function getBasePath() {
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $requestUri = $_SERVER['REQUEST_URI'];

    // Vérifier si on est dans un environnement Docker (port 8083)
    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '8083') {
        return '/';
    }
    
    // Si on est dans le dossier public
    if (strpos($requestUri, '/public/') !== false) {
        return '/GSCV+/public/';
    }
    
    // Si on est dans un sous-dossier
    if (strpos($scriptName, '/public/') !== false) {
        return '/GSCV+/public/';
    }
    
    // Par défaut
    return '/GSCV+/public/';
}

// Chemins absolus pour les ressources
define('BASE_PATH', getBasePath());
define('ASSETS_PATH', BASE_PATH . 'assets/');
define('CSS_PATH', ASSETS_PATH . 'css/');
define('JS_PATH', ASSETS_PATH . 'js/');
define('IMAGES_PATH', ASSETS_PATH . 'images/');

// Chemins pour les pages principales
define('LOGIN_PAGE', BASE_PATH . 'pageConnexion.php');
define('LOGOUT_PAGE', BASE_PATH . 'logout.php');
define('APP_PAGE', BASE_PATH . 'app.php');
define('HOME_PAGE', BASE_PATH . 'index.php');

// Fonction utilitaire pour générer des liens
function url($path = '') {
    return BASE_PATH . ltrim($path, '/');
}

// Fonction pour rediriger avec le bon chemin
function redirect($path) {
    header('Location: ' . url($path));
    exit();
}
?> 