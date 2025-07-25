<?php
/**
 * Script de test pour vérifier le ValidationModel corrigé
 * avec la structure réelle de la base de données
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/app/Models/ValidationModel.php';

echo "=== Test du ValidationModel corrigé ===\n\n";

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
    echo "📊 Statistiques :\n";
    echo "   - Total rapports : " . $stats['total_rapports'] . "\n";
    echo "   - En attente : " . $stats['en_attente'] . "\n";
    echo "   - Validés : " . $stats['valides'] . "\n";
    echo "   - Rejetés : " . $stats['rejetes'] . "\n\n";
    
    // Test de récupération des rapports en attente
    echo "4. Test de récupération des rapports en attente...\n";
    $rapports_en_attente = $validationModel->getRapportsEnAttente('', '', 1, 5);
    echo "📋 Rapports en attente trouvés : " . $rapports_en_attente['total'] . "\n";
    echo "   - Pages totales : " . $rapports_en_attente['pages'] . "\n";
    echo "   - Rapports sur cette page : " . count($rapports_en_attente['rapports']) . "\n\n";
    
    // Afficher les premiers rapports
    if (!empty($rapports_en_attente['rapports'])) {
        echo "   Détails des premiers rapports :\n";
        foreach (array_slice($rapports_en_attente['rapports'], 0, 3) as $rapport) {
            echo "   - ID: " . $rapport['id_rapport_etd'] . 
                 " | Étudiant: " . $rapport['nom_etd'] . " " . $rapport['prenom_etd'] .
                 " | Titre: " . $rapport['nom_rapport'] .
                 " | Validations: " . $rapport['nb_validations'] . "\n";
        }
        echo "\n";
    }
    
    // Test de recherche
    echo "5. Test de recherche de rapports...\n";
    $recherche = $validationModel->rechercherRapports('', ['statut' => 'En attente de validation']);
    echo "🔍 Résultats de recherche : " . count($recherche) . " rapports trouvés\n\n";
    
    // Test de récupération par statut
    echo "6. Test de récupération par statut...\n";
    $rapports_valides = $validationModel->getRapportsByStatut('Validé', 1, 5);
    echo "✅ Rapports validés trouvés : " . $rapports_valides['total'] . "\n\n";
    
    // Test de récupération des détails d'un rapport (si disponible)
    if (!empty($rapports_en_attente['rapports'])) {
        $premier_rapport = $rapports_en_attente['rapports'][0];
        echo "7. Test de récupération des détails d'un rapport...\n";
        $details = $validationModel->getRapportDetails($premier_rapport['id_rapport_etd']);
        if ($details) {
            echo "📄 Détails du rapport ID " . $premier_rapport['id_rapport_etd'] . " :\n";
            echo "   - Titre : " . $details['nom_rapport'] . "\n";
            echo "   - Étudiant : " . $details['nom_etd'] . " " . $details['prenom_etd'] . "\n";
            echo "   - Statut : " . $details['statut_rapport'] . "\n";
            echo "   - Nombre de validations : " . $details['nb_validations'] . "\n";
            echo "   - Nombre de rejets : " . $details['nb_rejets'] . "\n\n";
            
            // Test de récupération des validations
            echo "8. Test de récupération des validations...\n";
            $validations = $validationModel->getValidationsRapport($premier_rapport['id_rapport_etd']);
            echo "👥 Validations trouvées : " . count($validations) . "\n";
            foreach ($validations as $validation) {
                echo "   - Enseignant : " . $validation['nom_ens'] . " " . $validation['prenoms_ens'] .
                     " | Décision : " . $validation['decision'] . "\n";
            }
            echo "\n";
        }
    }
    
    // Test de vérification des permissions
    echo "9. Test de vérification des permissions...\n";
    if (!empty($rapports_en_attente['rapports'])) {
        $premier_rapport = $rapports_en_attente['rapports'][0];
        $peut_valider = $validationModel->canValidateRapport($premier_rapport['id_rapport_etd'], 1);
        echo "🔐 L'enseignant ID 1 peut-il valider le rapport " . $premier_rapport['id_rapport_etd'] . " ? " . 
             ($peut_valider ? "Oui" : "Non") . "\n\n";
    }
    
    echo "✅ Tous les tests ont été exécutés avec succès!\n";
    echo "🎉 Le ValidationModel fonctionne correctement avec la structure de la base de données.\n\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors du test : " . $e->getMessage() . "\n";
    echo "📍 Fichier : " . $e->getFile() . "\n";
    echo "📍 Ligne : " . $e->getLine() . "\n";
} finally {
    // Fermer la connexion
    DataBase::close();
    echo "🔒 Connexion fermée.\n";
} 