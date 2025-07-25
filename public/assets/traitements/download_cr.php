<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    exit("Utilisateur non connecté.");
}

$stmt = $pdo->prepare("
    SELECT cr.fichier_cr 
    FROM compte_rendu cr
    JOIN rapport_etudiant re ON cr.id_rapport_etd = re.id_rapport_etd
    JOIN utilisateur u ON re.num_etd = (
        SELECT e.num_etd FROM etudiants e WHERE e.email_etd = u.login_utilisateur AND u.id_utilisateur = ?
    )
    ORDER BY cr.date_cr DESC LIMIT 1
");
$stmt->execute([$user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result || empty($result['fichier_cr'])) {
    exit("Aucun fichier disponible.");
}

$file_path = "C:/wamp64/www/GSCV/pages/" . "". $result['fichier_cr']; // corrige selon ton chemin réel

if (!file_exists($file_path)) {
    exit("Fichier introuvable.");
}

// Envoyer les headers AVANT tout affichage
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($file_path);
exit;
