<?php
// Script de test pour vérifier les scripts de suppression
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Controllers/EnseignantController.php';
require_once __DIR__ . '/app/Controllers/PersonnelAdministratifController.php';

echo "<h2>Test des scripts de suppression</h2>";

// Test du contrôleur Enseignant
$enseignantController = new EnseignantController($pdo);
echo "<h3>Test EnseignantController</h3>";
echo "Contrôleur créé: " . (isset($enseignantController) ? "OK" : "ERREUR") . "<br>";

// Test du contrôleur Personnel
$personnelController = new PersonnelAdministratifController($pdo);
echo "Contrôleur Personnel créé: " . (isset($personnelController) ? "OK" : "ERREUR") . "<br>";

// Test de récupération des données
$enseignants = $enseignantController->getEnseignantsWithPagination(1, 5);
echo "Enseignants récupérés: " . count($enseignants) . "<br>";

$personnel = $personnelController->getPersonnelWithPagination(1, 5);
echo "Personnel récupéré: " . count($personnel) . "<br>";

// Test des chemins des scripts
$script_enseignant = __DIR__ . '/public/assets/traitements/supprimer_enseignant.php';
$script_personnel = __DIR__ . '/public/assets/traitements/supprimer_personnel.php';

echo "<h3>Test des chemins</h3>";
echo "Script enseignant existe: " . (file_exists($script_enseignant) ? "OUI" : "NON") . "<br>";
echo "Script personnel existe: " . (file_exists($script_personnel) ? "OUI" : "NON") . "<br>";

// Test d'inclusion des scripts
echo "<h3>Test d'inclusion</h3>";
try {
    include_once $script_enseignant;
    echo "Script enseignant inclus: OK<br>";
} catch (Exception $e) {
    echo "Erreur inclusion script enseignant: " . $e->getMessage() . "<br>";
}

try {
    include_once $script_personnel;
    echo "Script personnel inclus: OK<br>";
} catch (Exception $e) {
    echo "Erreur inclusion script personnel: " . $e->getMessage() . "<br>";
}
?> 