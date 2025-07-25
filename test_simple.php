<?php
// Test simple pour vérifier les scripts de suppression
echo "<h2>Test des scripts de suppression</h2>";

// Test 1: Vérifier que les fichiers existent
$script_enseignant = __DIR__ . '/public/assets/traitements/supprimer_enseignant.php';
$script_personnel = __DIR__ . '/public/assets/traitements/supprimer_personnel.php';

echo "<h3>1. Vérification des fichiers</h3>";
echo "Script enseignant: " . (file_exists($script_enseignant) ? "✅ Existe" : "❌ N'existe pas") . "<br>";
echo "Script personnel: " . (file_exists($script_personnel) ? "✅ Existe" : "❌ N'existe pas") . "<br>";

// Test 2: Vérifier la syntaxe PHP
echo "<h3>2. Vérification de la syntaxe PHP</h3>";
$output = [];
$return_var = 0;
exec("php -l " . escapeshellarg($script_enseignant), $output, $return_var);
echo "Script enseignant: " . ($return_var === 0 ? "✅ Syntaxe OK" : "❌ Erreur de syntaxe") . "<br>";

$output = [];
$return_var = 0;
exec("php -l " . escapeshellarg($script_personnel), $output, $return_var);
echo "Script personnel: " . ($return_var === 0 ? "✅ Syntaxe OK" : "❌ Erreur de syntaxe") . "<br>";

// Test 3: Simuler une requête POST
echo "<h3>3. Test de simulation de requête</h3>";
echo "<p>Pour tester les scripts, vous pouvez utiliser ces commandes curl :</p>";
echo "<code>curl -X POST -d 'ids=1' http://localhost/GSCV+/public/assets/traitements/supprimer_enseignant.php</code><br>";
echo "<code>curl -X POST -d 'ids=1' http://localhost/GSCV+/public/assets/traitements/supprimer_personnel.php</code><br>";

// Test 4: Vérifier les chemins d'inclusion
echo "<h3>4. Vérification des chemins d'inclusion</h3>";
$config_path = __DIR__ . '/config/config.php';
$enseignant_controller_path = __DIR__ . '/app/Controllers/EnseignantController.php';
$personnel_controller_path = __DIR__ . '/app/Controllers/PersonnelAdministratifController.php';

echo "Config: " . (file_exists($config_path) ? "✅ Existe" : "❌ N'existe pas") . "<br>";
echo "EnseignantController: " . (file_exists($enseignant_controller_path) ? "✅ Existe" : "❌ N'existe pas") . "<br>";
echo "PersonnelAdministratifController: " . (file_exists($personnel_controller_path) ? "✅ Existe" : "❌ N'existe pas") . "<br>";
?> 