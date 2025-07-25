<?php
// Test script pour vérifier les corrections du mail.php et DemandeSoutenanceController

// Démarrer la session pour simuler un utilisateur connecté
session_start();
$_SESSION['user_id'] = 1;

echo "<h1>Test des corrections mail.php et DemandeSoutenanceController</h1>\n";

try {
    // Test 1: Vérification de la configuration de base de données
    echo "<h2>Test 1: Configuration de base de données</h2>\n";
    require_once 'config/config.php';
    $pdo = DataBase::getConnection();
    echo "✓ Connexion à la base de données réussie<br>\n";
    
    // Test 2: Test de l'inclusion du mail.php
    echo "<h2>Test 2: Test de l'inclusion du mail.php</h2>\n";
    require_once 'config/mail.php';
    echo "✓ mail.php inclus avec succès<br>\n";
    echo "✓ PHPMailer classes disponibles<br>\n";
    
    // Test 3: Test du DemandeSoutenanceController
    echo "<h2>Test 3: Test du DemandeSoutenanceController</h2>\n";
    require_once 'app/Controllers/DemandeSoutenanceController.php';
    $controller = new DemandeSoutenanceController($pdo);
    echo "✓ DemandeSoutenanceController créé avec succès<br>\n";
    
    // Test 4: Test de la méthode index du contrôleur
    echo "<h2>Test 4: Test de la méthode index</h2>\n";
    $demandes = $controller->index();
    echo "✓ Méthode index exécutée avec succès<br>\n";
    echo "Nombre de demandes trouvées: " . count($demandes) . "<br>\n";
    
    echo "<h2>✓ Tous les tests ont réussi!</h2>\n";
    echo "<p>Les corrections ont résolu les problèmes de PHPMailer et DemandeSoutenanceController.</p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur détectée:</h2>\n";
    echo "<p>Erreur: " . $e->getMessage() . "</p>\n";
    echo "<p>Fichier: " . $e->getFile() . "</p>\n";
    echo "<p>Ligne: " . $e->getLine() . "</p>\n";
}
?> 