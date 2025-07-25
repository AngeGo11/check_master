<?php
// Test script pour vérifier les corrections du etudiants.php et fichiers associés

// Démarrer la session pour simuler un utilisateur connecté
session_start();
$_SESSION['user_id'] = 1;

echo "<h1>Test des corrections etudiants.php et fichiers associés</h1>\n";

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
    
    // Test 3: Test de l'inclusion du messages_functions.php
    echo "<h2>Test 3: Test de l'inclusion du messages_functions.php</h2>\n";
    require_once 'public/pages/assets/traitements/messages_functions.php';
    echo "✓ messages_functions.php inclus avec succès<br>\n";
    
    // Test 4: Test du EtudiantsController
    echo "<h2>Test 4: Test du EtudiantsController</h2>\n";
    require_once 'app/Controllers/EtudiantsController.php';
    $controller = new EtudiantsController();
    echo "✓ EtudiantsController créé avec succès<br>\n";
    
    // Test 5: Test de la méthode index du contrôleur
    echo "<h2>Test 5: Test de la méthode index</h2>\n";
    $etudiants = $controller->index();
    echo "✓ Méthode index exécutée avec succès<br>\n";
    echo "Nombre d'étudiants trouvés: " . count($etudiants) . "<br>\n";
    
    // Test 6: Test du modèle Etudiant
    echo "<h2>Test 6: Test du modèle Etudiant</h2>\n";
    require_once 'app/Models/Etudiant.php';
    $model = new \App\Models\Etudiant($pdo);
    echo "✓ Modèle Etudiant créé avec succès<br>\n";
    
    // Test 7: Test des statistiques
    echo "<h2>Test 7: Test des statistiques</h2>\n";
    $stats = $model->getStatistiques();
    echo "✓ Statistiques récupérées avec succès<br>\n";
    echo "Total étudiants: " . $stats['total'] . "<br>\n";
    echo "En attente: " . $stats['en_attente'] . "<br>\n";
    echo "Validés: " . $stats['valides'] . "<br>\n";
    echo "Refusés: " . $stats['refuses'] . "<br>\n";
    
    echo "<h2>✓ Tous les tests ont réussi!</h2>\n";
    echo "<p>Les corrections ont résolu les problèmes de fichiers manquants et de structure MVC.</p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur détectée:</h2>\n";
    echo "<p>Erreur: " . $e->getMessage() . "</p>\n";
    echo "<p>Fichier: " . $e->getFile() . "</p>\n";
    echo "<p>Ligne: " . $e->getLine() . "</p>\n";
}
?> 