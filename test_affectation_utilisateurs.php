<?php
/**
 * Script de test pour vérifier la correction de l'erreur de contrainte de clé étrangère
 * lors de l'affectation d'utilisateurs
 */

// Inclure les fichiers nécessaires
require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/Controllers/UtilisateurController.php';
require_once __DIR__ . '/app/Models/Utilisateur.php';

echo "=== TEST DE CORRECTION DE L'ERREUR DE CONTRAINTE DE CLÉ ÉTRANGÈRE ===\n\n";

// Initialiser le contrôleur
$utilisateurController = new UtilisateurController($pdo);

// Test 1: Vérifier les niveaux d'accès disponibles
echo "1. Vérification des niveaux d'accès disponibles :\n";
$niveaux_acces = $utilisateurController->getNiveauxAcces();
foreach ($niveaux_acces as $niveau) {
    echo "   - ID: {$niveau['id_niveau_acces']}, Libellé: {$niveau['lib_niveau_acces']}\n";
}
echo "\n";

// Test 2: Vérifier les utilisateurs inactifs
echo "2. Vérification des utilisateurs inactifs :\n";
$inactive_users = $utilisateurController->getInactiveUsers();
echo "   Nombre d'utilisateurs inactifs : " . count($inactive_users) . "\n";
if (count($inactive_users) > 0) {
    echo "   Premier utilisateur inactif : ID {$inactive_users[0]['id_utilisateur']} - {$inactive_users[0]['nom_complet']}\n";
}
echo "\n";

// Test 3: Tester l'affectation avec des valeurs valides
if (count($inactive_users) > 0) {
    echo "3. Test d'affectation avec des valeurs valides :\n";
    
    // Récupérer les types et groupes disponibles
    $types = $utilisateurController->getTypesUtilisateurs();
    $groupes = $utilisateurController->getGroupesUtilisateurs();
    
    if (!empty($types) && !empty($groupes) && !empty($niveaux_acces)) {
        $test_user_ids = [$inactive_users[0]['id_utilisateur']];
        $test_type = $types[0]['id_tu'];
        $test_groupe = $groupes[0]['id_gu'];
        $test_niveau = $niveaux_acces[0]['id_niveau_acces'];
        
        echo "   Test avec :\n";
        echo "   - Utilisateur ID: {$test_user_ids[0]}\n";
        echo "   - Type: {$types[0]['lib_tu']} (ID: {$test_type})\n";
        echo "   - Groupe: {$groupes[0]['lib_gu']} (ID: {$test_groupe})\n";
        echo "   - Niveau d'accès: {$niveaux_acces[0]['lib_niveau_acces']} (ID: {$test_niveau})\n";
        
        try {
            $result = $utilisateurController->assignMultipleUsers(
                $test_user_ids,
                $test_type,
                $test_groupe,
                $test_niveau
            );
            
            echo "   Résultat :\n";
            echo "   - Succès : {$result['success_count']}\n";
            echo "   - Erreurs : {$result['error_count']}\n";
            
            if (!empty($result['error_messages'])) {
                echo "   - Messages d'erreur :\n";
                foreach ($result['error_messages'] as $error) {
                    echo "     * {$error}\n";
                }
            }
            
            if ($result['success_count'] > 0) {
                echo "   ✅ TEST RÉUSSI : L'affectation fonctionne correctement\n";
            } else {
                echo "   ❌ TEST ÉCHOUÉ : L'affectation a échoué\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ ERREUR : " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️ Impossible de tester : données manquantes (types, groupes ou niveaux d'accès)\n";
    }
} else {
    echo "3. ⚠️ Impossible de tester : aucun utilisateur inactif disponible\n";
}

echo "\n";

// Test 4: Tester l'affectation avec un niveau d'accès NULL
if (count($inactive_users) > 1) {
    echo "4. Test d'affectation avec niveau d'accès NULL :\n";
    
    $types = $utilisateurController->getTypesUtilisateurs();
    $groupes = $utilisateurController->getGroupesUtilisateurs();
    
    if (!empty($types) && !empty($groupes)) {
        $test_user_ids = [$inactive_users[1]['id_utilisateur']];
        $test_type = $types[0]['id_tu'];
        $test_groupe = $groupes[0]['id_gu'];
        
        echo "   Test avec niveau d'accès NULL :\n";
        echo "   - Utilisateur ID: {$test_user_ids[0]}\n";
        echo "   - Type: {$types[0]['lib_tu']} (ID: {$test_type})\n";
        echo "   - Groupe: {$groupes[0]['lib_gu']} (ID: {$test_groupe})\n";
        echo "   - Niveau d'accès: NULL\n";
        
        try {
            $result = $utilisateurController->assignMultipleUsers(
                $test_user_ids,
                $test_type,
                $test_groupe,
                null // Niveau d'accès NULL
            );
            
            echo "   Résultat :\n";
            echo "   - Succès : {$result['success_count']}\n";
            echo "   - Erreurs : {$result['error_count']}\n";
            
            if ($result['success_count'] > 0) {
                echo "   ✅ TEST RÉUSSI : L'affectation avec niveau NULL fonctionne\n";
            } else {
                echo "   ❌ TEST ÉCHOUÉ : L'affectation avec niveau NULL a échoué\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ ERREUR : " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== FIN DES TESTS ===\n";
?> 