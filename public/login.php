<?php
session_start();

// Debug pour voir si le fichier est appelé
error_log("=== LOGIN.PHP APPELE ===");
error_log("Méthode: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . print_r($_POST, true));

// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php'; // pour la classe DataBase
require_once __DIR__ . '/../app/Controllers/AuthController.php';

// Créer la connexion PDO
$pdo = DataBase::getConnection();
$authController = new AuthController($pdo);

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['connexion'], $_POST['login'], $_POST['password'])
) {
    error_log("=== TRAITEMENT CONNEXION ===");
    $login = trim($_POST['login']);
    $pass  = $_POST['password'];

    // Debug
    error_log("Tentative de connexion - Login: " . $login);
    error_log("POST data: " . print_r($_POST, true));

    if (empty($login) || empty($pass)) {
        $_SESSION['error_message'] = "Veuillez remplir tous les champs";
        header('Location: pageConnexion.php');
        exit();
    }

    $userRow = $authController->verifyLogin($login, $pass);
    
    // Debug
    error_log("Resultat verifyLogin: " . ($userRow ? "SUCCESS" : "FAILED"));

    if ($userRow) {
        $uid = (int)$userRow['id_utilisateur'];

        // Exemple après une connexion réussie
       // enregistrer_piste_audit($pdo, $userRow['id_utilisateur'], 'dashboard', 'Tentative connexion réussie', 1);
        $groups = $authController->getGroups($uid);
        $groupIds = array_column($groups, 'id_gu');
        $types = $authController->getTypes($uid);
        $typesIds = array_column($types, 'id_tu');

        // Vérification des permissions minimales
        if (empty($groupIds) || empty($typesIds)) {
            $_SESSION['error_message'] = "Permissions insuffisantes pour accéder au système";
            header('Location: pageConnexion.php');
            exit();
        }

        /*─── SESSION ───*/
        $_SESSION['user_permissions'] = $authController->getUserPermissions($uid);
        $_SESSION['user_id'] = $uid;
        $_SESSION['user_fullname'] = $authController->getUserFullName($uid);
        $_SESSION['id_user_group'] = $authController->getUserGroupId($uid);
        $_SESSION['photo_profil'] = $authController->getProfilUser($uid);

        $_SESSION['user_groups'] = $groupIds; //Stocker tous les groupes
        $_SESSION['user_types'] = $typesIds;
        $_SESSION['id_user_type'] = $authController->getUserTypeId($uid);
        $_SESSION['lib_user_type'] = $authController->getUserType($uid);
        $_SESSION['login_utilisateur'] = $authController->getUserLogin($uid);
        $_SESSION['last_activity'] = time();

        /*─── Redirection ───*/
        // Redirection vers la page appropriée selon le type d'utilisateur
        $mainGroup = $authController->pickMainGroup($uid);
        
        // Debug temporaire
        error_log("MainGroup: " . $mainGroup);
        error_log("User groups: " . print_r($groupIds, true));
        error_log("User types: " . print_r($typesIds, true));
        
        if ($mainGroup === null) {
            $_SESSION['error_message'] = "Type d'utilisateur non reconnu";
            header('Location: pageConnexion.php');
            exit();
        }

        // Redirection vers la page appropriée selon le type d'utilisateur
        switch ($mainGroup) {
            case 1: // Étudiant
                header('Location: app.php?page=soutenances&type=etudiant');
                break;
            case 2: // Chargé de communication
            case 3: // Responsable scolarité
            case 4: // Secrétaire
                header('Location: app.php?page=etudiants&type=personnel');
                break;
            case 5: // Enseignant sans responsabilité
            case 6: // Responsable niveau
            case 7: // Responsable filière
            case 8: // Administrateur plateforme
            case 9: // Commission de validation
                header('Location: app.php?page=dashboard&type=commission');
                break;
            default:
                $_SESSION['error_message'] = "Type d'utilisateur non reconnu";
                header('Location: pageConnexion.php');
        }
        exit();
    } else {
        // Si la connexion échoue, on reste sur la page de connexion
        // Le message d'erreur est déjà défini dans verifyLogin()
        header('Location: pageConnexion.php');
        exit();
    }
} else {
    error_log("=== CONDITIONS NON REMPLIES ===");
    error_log("Méthode POST: " . ($_SERVER['REQUEST_METHOD'] === 'POST' ? 'OUI' : 'NON'));
    error_log("connexion existe: " . (isset($_POST['connexion']) ? 'OUI' : 'NON'));
    error_log("login existe: " . (isset($_POST['login']) ? 'OUI' : 'NON'));
    error_log("password existe: " . (isset($_POST['password']) ? 'OUI' : 'NON'));
}
?>



