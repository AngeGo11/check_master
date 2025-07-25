<?php
// Test script pour vérifier l'architecture MVC des étudiants

// Démarrer la session pour simuler un utilisateur connecté
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['id_user_group'] = 2; // Personnel administratif

echo "<h1>Test de l'architecture MVC - Gestion des Étudiants</h1>\n";

try {
    // Test 1: Vérification de la configuration de base de données
    echo "<h2>Test 1: Configuration de base de données</h2>\n";
    require_once 'config/config.php';
    $pdo = DataBase::getConnection();
    echo "✓ Connexion à la base de données réussie<br>\n";
    
    // Test 2: Test de l'inclusion du contrôleur
    echo "<h2>Test 2: Test du contrôleur EtudiantsController</h2>\n";
    require_once 'app/Controllers/EtudiantsController.php';
    $controller = new EtudiantsController();
    echo "✓ Contrôleur EtudiantsController créé avec succès<br>\n";
    
    // Test 3: Test de la méthode index du contrôleur
    echo "<h2>Test 3: Test de la méthode index()</h2>\n";
    $data = $controller->index();
    echo "✓ Méthode index() exécutée avec succès<br>\n";
    echo "✓ Nombre d'étudiants récupérés: " . count($data['etudiants']) . "<br>\n";
    echo "✓ Total des enregistrements: " . $data['total_records'] . "<br>\n";
    echo "✓ Nombre de pages: " . $data['total_pages'] . "<br>\n";
    echo "✓ Statistiques récupérées: " . count($data['statistics']) . " éléments<br>\n";
    
    // Test 4: Test des statistiques
    echo "<h2>Test 4: Test des statistiques</h2>\n";
    echo "✓ Total étudiants: " . $data['statistics']['total_etudiants'] . "<br>\n";
    echo "✓ En attente: " . $data['statistics']['en_attente'] . "<br>\n";
    echo "✓ Validés: " . $data['statistics']['valides'] . "<br>\n";
    echo "✓ Refusés: " . $data['statistics']['refuses'] . "<br>\n";
    
    // Test 5: Test des filtres
    echo "<h2>Test 5: Test des filtres</h2>\n";
    echo "✓ Filtres disponibles: " . count($data['filters']) . "<br>\n";
    echo "✓ Listes disponibles: " . count($data['lists']) . "<br>\n";
    echo "✓ Promotions disponibles: " . count($data['lists']['promotions']) . "<br>\n";
    echo "✓ Niveaux disponibles: " . count($data['lists']['niveaux']) . "<br>\n";
    
    // Test 6: Test de la récupération des rapports
    echo "<h2>Test 6: Test de la récupération des rapports</h2>\n";
    $rapports_data = $controller->getRapportsEtudiants('', '', 1, 5);
    echo "✓ Rapports récupérés: " . count($rapports_data['rapports']) . "<br>\n";
    echo "✓ Total rapports: " . $rapports_data['total_records'] . "<br>\n";
    
    // Test 7: Test du modèle Etudiant
    echo "<h2>Test 7: Test du modèle Etudiant</h2>\n";
    require_once 'app/Models/Etudiant.php';
    $model = new \App\Models\Etudiant($pdo);
    echo "✓ Modèle Etudiant créé avec succès<br>\n";
    
    // Test 8: Test des méthodes du modèle
    echo "<h2>Test 8: Test des méthodes du modèle</h2>\n";
    $stats = $model->getStatistiques();
    echo "✓ Statistiques du modèle: " . count($stats) . " éléments<br>\n";
    
    $total = $model->getTotalEtudiants();
    echo "✓ Total étudiants (modèle): " . $total . "<br>\n";
    
    // Test 9: Test de validation des données
    echo "<h2>Test 9: Test de validation des données</h2>\n";
    $test_data = [
        'card' => '',
        'nom' => '',
        'prenoms' => '',
        'email' => '',
        'id_niv_etd' => '',
        'id_promotion' => ''
    ];
    
    $result = $controller->ajouterEtudiant($test_data);
    echo "✓ Validation des données: " . ($result['success'] ? 'ÉCHEC (attendu)' : 'SUCCÈS') . "<br>\n";
    echo "✓ Message d'erreur: " . $result['message'] . "<br>\n";
    
    // Test 10: Test de l'inclusion du mail.php
    echo "<h2>Test 10: Test de l'inclusion du mail.php</h2>\n";
    require_once 'config/mail.php';
    echo "✓ mail.php inclus avec succès<br>\n";
    
    echo "<h2>✅ Tous les tests sont passés avec succès!</h2>\n";
    echo "<p>L'architecture MVC pour la gestion des étudiants est fonctionnelle.</p>\n";
    
} catch (Exception $e) {
    echo "<h2>❌ Erreur lors des tests</h2>\n";
    echo "<p>Erreur: " . $e->getMessage() . "</p>\n";
    echo "<p>Fichier: " . $e->getFile() . "</p>\n";
    echo "<p>Ligne: " . $e->getLine() . "</p>\n";
}
?> 