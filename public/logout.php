<?php

session_start();
require_once __DIR__ . '/../config/paths.php';

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Rediriger vers la page de connexion avec un message de succès
redirect('pageConnexion.php?logout=success');