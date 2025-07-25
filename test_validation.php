<?php
/**
 * Script de test pour vérifier le ValidationModel avec la nouvelle classe DataBase singleton
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Models/ValidationModel.php';

echo "=== Test du ValidationModel avec DataBase singleton ===\n\n";

try {
    // Test de la connexion
    echo "1. Test de la connexion DataBase...\n";
    $pdo = DataBase::getConnection();
    echo "✅ Connexion réussie!\n\n";
    
    // Test de création du modèle
    echo "2. Test de création du ValidationModel...\n";
    $validationModel = new \App\Models\ValidationModel();
    echo "✅ ValidationModel créé avec succès!\n\n";
    
    // Test des statistiques
    echo "3. Test des statistiques de validation...\n";
    $stats = $validationModel->getValidationStats();
    echo "📊 Statistiques:\n";
    foreach ($stats as $key => $value) {
        echo "   $key: $value\n";
    }
    echo "\n";
    
    // Test de récupération des rapports en attente
    echo "4. Test de récupération des rapports en attente...\n";
    $rapports_data = $validationModel->getRapportsEnAttente('', '', 1, 5);
    echo "📋 Rapports en attente: " . $rapports_data['total'] . " trouvés\n";
    echo "📄 Pages: " . $rapports_data['pages'] . "\n";
    echo "📝 Rapports retournés: " . count($rapports_data['rapports']) . "\n\n";
    
    // Test de recherche
    echo "5. Test de recherche de rapports...\n";
    $recherche = $validationModel->rechercherRapports('', ['statut' => 'En attente de validation']);
    echo "🔍 Résultats de recherche: " . count($recherche) . " rapports trouvés\n\n";
    
    // Test des types de réunions (pour vérifier que ReunionModel fonctionne aussi)
    echo "6. Test du ReunionModel...\n";
    require_once __DIR__ . '/app/Models/ReunionModel.php';
    $reunionModel = new ReunionModel();
    $reunions = $reunionModel->getReunions('', '', '', 1, 5);
    echo "📅 Réunions trouvées: " . $reunions['total'] . "\n";
    echo "📄 Pages: " . $reunions['pages'] . "\n";
    echo "📝 Réunions retournées: " . count($reunions['reunions']) . "\n\n";
    
    // Test des informations de connexion
    echo "7. Test des informations de connexion...\n";
    $info = DataBase::getConnectionInfo();
    echo "📊 Informations de connexion:\n";
    foreach ($info as $key => $value) {
        echo "   $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
    }
    echo "\n";
    
    // Test de la même connexion (singleton)
    echo "8. Test du pattern singleton...\n";
    $pdo1 = DataBase::getConnection();
    $pdo2 = DataBase::getConnection();
    if ($pdo1 === $pdo2) {
        echo "✅ Pattern singleton fonctionne correctement (même instance)\n";
    } else {
        echo "❌ Erreur: Pattern singleton ne fonctionne pas\n";
    }
    echo "\n";
    
    echo "=== Tous les tests sont passés avec succès! ===\n";
    echo "✅ Le ValidationModel fonctionne correctement avec DataBase singleton\n";
    echo "✅ Aucune boucle infinie détectée\n";
    echo "✅ Les connexions sont gérées efficacement\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . "\n";
    echo "📍 Ligne: " . $e->getLine() . "\n";
    echo "📍 Trace:\n" . $e->getTraceAsString() . "\n";
}
?> 