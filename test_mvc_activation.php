<?php
require_once 'config/config.php';
require_once 'app/Controllers/UtilisateurController.php';
require_once 'app/Models/Utilisateur.php';

echo "=== TEST MVC ACTIVATION ===\n\n";

try {
    // 1. Connexion à la base de données
    $db = DataBase::getConnection();
    echo "✅ Connexion à la base de données réussie\n";
    
    // 2. Créer le contrôleur
    $utilisateurController = new UtilisateurController($db);
    echo "✅ Contrôleur créé\n";
    
    // 3. Récupérer les utilisateurs inactifs
    $inactiveUsers = $utilisateurController->getInactiveUsers();
    echo "Nombre d'utilisateurs inactifs: " . count($inactiveUsers) . "\n";
    
    if (empty($inactiveUsers)) {
        echo "❌ Aucun utilisateur inactif trouvé\n";
        exit;
    }
    
    // 4. Prendre le premier utilisateur inactif pour le test
    $testUser = $inactiveUsers[0];
    echo "Test avec l'utilisateur: " . $testUser['login_utilisateur'] . " (ID: " . $testUser['id_utilisateur'] . ")\n";
    
    // 5. Tester l'activation via generatePasswords
    echo "Tentative d'activation via generatePasswords...\n";
    $result = $utilisateurController->generatePasswords([$testUser['id_utilisateur']]);
    
    echo "Résultat generatePasswords:\n";
    echo "  - success_count: " . $result['success_count'] . "\n";
    echo "  - error_count: " . $result['error_count'] . "\n";
    echo "  - error_messages: " . implode(', ', $result['error_messages']) . "\n";
    echo "  - passwords générés: " . count($result['passwords']) . "\n";
    
    if ($result['success_count'] > 0) {
        echo "✅ ACTIVATION RÉUSSIE via generatePasswords!\n";
    } else {
        echo "❌ ACTIVATION ÉCHOUÉE via generatePasswords\n";
    }
    
    // 6. Tester l'activation via assignMultipleUsers
    echo "\nTentative d'activation via assignMultipleUsers...\n";
    $result2 = $utilisateurController->assignMultipleUsers(
        [$testUser['id_utilisateur']], 
        1, // type_utilisateur (1 = Étudiant par exemple)
        null, // groupe_utilisateur
        null  // niveau_acces
    );
    
    echo "Résultat assignMultipleUsers:\n";
    echo "  - success_count: " . $result2['success_count'] . "\n";
    echo "  - error_count: " . $result2['error_count'] . "\n";
    echo "  - error_messages: " . implode(', ', $result2['error_messages']) . "\n";
    echo "  - passwords générés: " . count($result2['passwords']) . "\n";
    
    if ($result2['success_count'] > 0) {
        echo "✅ ACTIVATION RÉUSSIE via assignMultipleUsers!\n";
    } else {
        echo "❌ ACTIVATION ÉCHOUÉE via assignMultipleUsers\n";
    }
    
    // 7. Vérifier que l'utilisateur est maintenant actif
    $stmt = $db->prepare("SELECT statut_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$testUser['id_utilisateur']]);
    $status = $stmt->fetchColumn();
    
    echo "Statut final de l'utilisateur: " . $status . "\n";
    
    if ($status === 'Actif') {
        echo "✅ Statut correctement mis à jour!\n";
    } else {
        echo "❌ Le statut n'a pas été mis à jour correctement\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
} 