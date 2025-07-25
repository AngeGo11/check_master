<?php
// Test du contrôleur EnseignantController
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Controllers/EnseignantController.php';

echo "<h2>Test du contrôleur EnseignantController</h2>";

try {
    $controller = new EnseignantController($pdo);
    echo "<p>✅ Contrôleur créé avec succès</p>";
    
    // Test de récupération des enseignants
    $enseignants = $controller->getEnseignantsWithPagination(1, 5);
    echo "<p>✅ Enseignants récupérés : " . count($enseignants) . "</p>";
    
    if (count($enseignants) > 0) {
        $premier_enseignant = $enseignants[0];
        echo "<p>Premier enseignant : ID = " . $premier_enseignant['id_ens'] . ", Nom = " . $premier_enseignant['nom_ens'] . "</p>";
        
        // Test de la méthode delete (sans vraiment supprimer)
        echo "<h3>Test de la méthode delete (simulation)</h3>";
        echo "<p>⚠️ Note : Ce test ne supprime pas réellement l'enseignant</p>";
        
        // Vérifier que la méthode existe
        if (method_exists($controller, 'delete')) {
            echo "<p>✅ Méthode delete existe</p>";
        } else {
            echo "<p>❌ Méthode delete n'existe pas</p>";
        }
        
        // Vérifier le modèle
        $reflection = new ReflectionClass($controller);
        $model_property = $reflection->getProperty('model');
        $model_property->setAccessible(true);
        $model = $model_property->getValue($controller);
        
        if (method_exists($model, 'delete')) {
            echo "<p>✅ Méthode delete existe dans le modèle</p>";
        } else {
            echo "<p>❌ Méthode delete n'existe pas dans le modèle</p>";
        }
        
    } else {
        echo "<p>⚠️ Aucun enseignant trouvé dans la base de données</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erreur : " . $e->getMessage() . "</p>";
    echo "<p>Fichier : " . $e->getFile() . "</p>";
    echo "<p>Ligne : " . $e->getLine() . "</p>";
}
?> 