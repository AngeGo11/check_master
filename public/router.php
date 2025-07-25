<?php
session_start();

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../app/config/config.php';

// Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: pageConnexion.php');
    exit();
}

// Inclusion du routeur
require_once __DIR__ . '/../routes/web.php'; 