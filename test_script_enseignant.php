<?php
// Test simple du script de suppression des enseignants
echo "<h2>Test du script de suppression des enseignants</h2>";

// Simuler une requête POST
$_POST['ids'] = '1'; // ID de test
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capturer la sortie
ob_start();

// Inclure le script
include __DIR__ . '/public/assets/traitements/supprimer_enseignant.php';

// Récupérer la sortie
$output = ob_get_clean();

echo "<h3>Sortie du script :</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Essayer de décoder le JSON
$json = json_decode($output, true);
if ($json) {
    echo "<h3>JSON décodé :</h3>";
    echo "<pre>" . print_r($json, true) . "</pre>";
} else {
    echo "<h3>Erreur de décodage JSON :</h3>";
    echo "<p>La sortie n'est pas un JSON valide.</p>";
    echo "<p>Erreur JSON : " . json_last_error_msg() . "</p>";
}
?> 