<?php
// Test de l'endpoint AJAX get_frais_inscription.php
echo "<h2>Test de l'endpoint AJAX get_frais_inscription.php</h2>";

// Simuler les paramètres GET
$_GET['niveau_id'] = 1; // Remplacez par un ID de niveau valide
$_GET['annee_id'] = 1;  // Remplacez par un ID d'année valide

// Inclure le fichier à tester
ob_start();
include 'get_frais_inscription.php';
$output = ob_get_clean();

echo "<h3>Réponse de l'endpoint:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Décoder la réponse JSON
$response = json_decode($output, true);
if ($response) {
    echo "<h3>Réponse décodée:</h3>";
    echo "<pre>" . print_r($response, true) . "</pre>";
    
    if ($response['success']) {
        echo "<p style='color: green;'>✅ Test réussi! Montant: {$response['montant']} FCFA</p>";
    } else {
        echo "<p style='color: red;'>❌ Test échoué: {$response['message']}</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Erreur de décodage JSON</p>";
}
?>
