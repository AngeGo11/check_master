<?php
session_start();
require_once __DIR__ . '/app/config/config.php';

echo "Test de configuration<br>";
echo "BASE_URL: " . BASE_URL . "<br>";
echo "PDO connecté: " . ($pdo ? "Oui" : "Non") . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? "Non défini") . "<br>";
?> 