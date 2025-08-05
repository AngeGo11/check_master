<?php
require_once './app/config/config.php';
require_once 'config/mail.php';
require_once './app/Controllers/UtilisateurController.php';

echo "<h1>Test d'activation des utilisateurs</h1>";

// Initialiser le contrôleur
$utilisateurController = new UtilisateurController($pdo);

// Récupérer les utilisateurs inactifs
$inactiveUsers = $utilisateurController->getInactiveUsers();

echo "<h2>Utilisateurs inactifs trouvés : " . count($inactiveUsers) . "</h2>";

if (empty($inactiveUsers)) {
    echo "<p>Aucun utilisateur inactif trouvé.</p>";
    exit;
}

// Afficher les utilisateurs inactifs
echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>ID</th><th>Email</th><th>Nom complet</th><th>Type</th></tr>";
foreach ($inactiveUsers as $user) {
    echo "<tr>";
    echo "<td>" . $user['id_utilisateur'] . "</td>";
    echo "<td>" . $user['login_utilisateur'] . "</td>";
    echo "<td>" . $user['nom_complet'] . "</td>";
    echo "<td>" . $user['type_source'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Tester l'activation du premier utilisateur
$firstUser = $inactiveUsers[0];
echo "<h2>Test d'activation pour l'utilisateur : " . $firstUser['nom_complet'] . " (" . $firstUser['login_utilisateur'] . ")</h2>";

try {
    $result = $utilisateurController->generatePasswords([$firstUser['id_utilisateur']]);
    
    echo "<h3>Résultat de l'activation :</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if ($result['success_count'] > 0) {
        echo "<p style='color: green;'>✅ Activation réussie !</p>";
        echo "<p>Nombre d'utilisateurs activés : " . $result['success_count'] . "</p>";
        
        if (!empty($result['passwords'])) {
            echo "<h3>Mots de passe générés :</h3>";
            foreach ($result['passwords'] as $passwordData) {
                echo "<p><strong>Email :</strong> " . $passwordData['login'] . "</p>";
                echo "<p><strong>Mot de passe :</strong> " . $passwordData['password'] . "</p>";
                echo "<p><strong>Nom complet :</strong> " . $passwordData['nom_complet'] . "</p>";
                echo "<hr>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Échec de l'activation</p>";
        if (!empty($result['error_messages'])) {
            echo "<h3>Erreurs :</h3>";
            foreach ($result['error_messages'] as $error) {
                echo "<p style='color: red;'>" . $error . "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception : " . $e->getMessage() . "</p>";
    echo "<p>Fichier : " . $e->getFile() . "</p>";
    echo "<p>Ligne : " . $e->getLine() . "</p>";
}

// Test d'envoi d'email direct
echo "<h2>Test d'envoi d'email direct</h2>";
try {
    $testResult = sendEmail(
        "Administrateur GSCV", 
        "axelangegomez2004@gmail.com", 
        "axelangegomez2004@gmail.com", 
        "Test d'activation", 
        "Ceci est un test d'envoi d'email pour l'activation."
    );
    
    if ($testResult) {
        echo "<p style='color: green;'>✅ Email de test envoyé avec succès</p>";
    } else {
        echo "<p style='color: red;'>❌ Échec de l'envoi de l'email de test</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exception lors de l'envoi d'email : " . $e->getMessage() . "</p>";
}

echo "<h2>Instructions</h2>";
echo "<p>1. Si l'activation réussit, le bouton 'Donner accès' devrait fonctionner dans l'interface.</p>";
echo "<p>2. Si l'activation échoue, vérifiez les erreurs ci-dessus.</p>";
echo "<p>3. Si l'email ne s'envoie pas, vérifiez la configuration SMTP dans config/mail.php</p>";
echo "<p><a href='?liste=utilisateurs'>Retour à la liste des utilisateurs</a></p>";
?> 