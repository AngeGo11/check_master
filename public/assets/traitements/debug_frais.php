<?php
// Debug simple pour tester get_frais_inscription.php
echo "<h2>Debug get_frais_inscription.php</h2>";

// Simuler une session
session_start();
$_SESSION['user_id'] = 1; // Simuler un utilisateur connecté

// Simuler les paramètres GET
$_GET['niveau_id'] = 1;
$_GET['annee_id'] = 1;

echo "<h3>Paramètres de test:</h3>";
echo "niveau_id: " . $_GET['niveau_id'] . "<br>";
echo "annee_id: " . $_GET['annee_id'] . "<br>";

// Capturer la sortie
ob_start();
include 'get_frais_inscription.php';
$output = ob_get_clean();

echo "<h3>Réponse brute:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Décoder JSON
$response = json_decode($output, true);
if ($response) {
    echo "<h3>Réponse décodée:</h3>";
    echo "<pre>" . print_r($response, true) . "</pre>";
    
    if ($response['success']) {
        echo "<p style='color: green; font-weight: bold;'>✅ SUCCÈS! Montant: {$response['montant']} FCFA</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ ÉCHEC: {$response['message']}</p>";
    }
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Erreur de décodage JSON</p>";
}
?>
