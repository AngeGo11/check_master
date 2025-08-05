<?php
require_once '../../config/db_connect.php';
require_once '../../app/Controllers/UtilisateurController.php';

// Initialiser le contrôleur
$utilisateurController = new UtilisateurController($pdo);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                if (isset($_POST['login']) && isset($_POST['type_utilisateur'])) {
                    $result = $utilisateurController->addUser($_POST['login'], $_POST['type_utilisateur']);
                    
                    if ($result['success']) {
                        $_SESSION['success'] = $result['message'];
                    } else {
                        $_SESSION['error'] = $result['message'];
                    }
                }
                break;

            case 'generate_passwords':
                if (isset($_POST['selected_users']) && is_array($_POST['selected_users'])) {
                    $result = $utilisateurController->generatePasswords($_POST['selected_users']);
                    
                    if ($result['success_count'] > 0) {
                        $_SESSION['success'] = $result['success_count'] . " mot(s) de passe généré(s) et envoyé(s) avec succès.";
                    }
                    if ($result['error_count'] > 0) {
                        $_SESSION['error'] = implode("<br>", $result['error_messages']);
                    }
                }
                break;

            case 'edit_user':
                if (isset($_POST['id_utilisateur'], $_POST['type_utilisateur'])) {
                    $id_utilisateur = (int)$_POST['id_utilisateur'];
                    $type_utilisateur = (int)$_POST['type_utilisateur'];
                    $groupe_utilisateur = isset($_POST['groupe_utilisateur']) ? (int)$_POST['groupe_utilisateur'] : null;
                    $niveaux_acces = $_POST['niveaux_acces'] ?? [];
                    $fonction = $_POST['fonction'] ?? null;
                    $grade = $_POST['grade'] ?? null;
                    $specialite = $_POST['specialite'] ?? null;

                    $result = $utilisateurController->editUser(
                        $id_utilisateur, 
                        $type_utilisateur, 
                        $groupe_utilisateur, 
                        $niveaux_acces, 
                        $fonction, 
                        $grade, 
                        $specialite
                    );
                    
                    if ($result['success']) {
                        $_SESSION['success'] = $result['message'];
                        header("Location: ?page=liste_utilisateurs");
                        exit();
                    } else {
                        $_SESSION['error'] = $result['message'];
                        header("Location: ?page=liste_utilisateurs&action=edit&id=" . $id_utilisateur);
                        exit();
                    }
                }
                break;

            case 'desactivate_user':
                if (isset($_POST['id_utilisateur'])) {
                    $result = $utilisateurController->deactivateUser($_POST['id_utilisateur']);
                    
                    if ($result['success']) {
                        $_SESSION['success'] = $result['message'];
                    } else {
                        $_SESSION['error'] = $result['message'];
                    }
                }
                break;

            case 'activate_user':
                if (isset($_POST['id_utilisateur'])) {
                    $result = $utilisateurController->activateUser($_POST['id_utilisateur']);
                    
                    if ($result['success']) {
                        $_SESSION['success'] = $result['message'];
                    } else {
                        $_SESSION['error'] = $result['message'];
                    }
                }
                break;

            case 'assign_multiple':
                if (isset($_POST['selected_inactive_users'], $_POST['assign_type_utilisateur'])) {
                    $selected_users = $_POST['selected_inactive_users'];
                    $type_utilisateur = (int)$_POST['assign_type_utilisateur'];
                    $groupe_utilisateur = isset($_POST['assign_groupe_utilisateur']) ? (int)$_POST['assign_groupe_utilisateur'] : null;
                    $niveau_acces = isset($_POST['assign_niveau_acces']) ? (int)$_POST['assign_niveau_acces'] : null;

                    $result = $utilisateurController->assignMultipleUsers(
                        $selected_users, 
                        $type_utilisateur, 
                        $groupe_utilisateur, 
                        $niveau_acces
                    );
                    
                    if ($result['success_count'] > 0) {
                        $_SESSION['success'] = $result['success_count'] . " utilisateur(s) affecté(s) et activé(s) avec succès.";
                    }
                    if ($result['error_count'] > 0) {
                        $_SESSION['error'] = "Erreurs lors de l'affectation :<br>" . implode("<br>", $result['error_messages']);
                    }
                }
                break;
        }
    }
}

// Récupération des données pour l'affichage
$utilisateurs_par_page = 75;
$page_courante = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Récupération des paramètres de filtrage
$filters = [
    'type' => isset($_GET['type']) ? (int)$_GET['type'] : null,
    'groupe' => isset($_GET['groupe']) ? (int)$_GET['groupe'] : null,
    'statut' => isset($_GET['statut']) ? $_GET['statut'] : null,
    'search' => isset($_GET['search']) ? trim($_GET['search']) : ''
];

// Récupération des utilisateurs avec filtres et pagination
$utilisateurs_data = $utilisateurController->getUtilisateursWithFilters($page_courante, $utilisateurs_par_page, $filters);
$utilisateurs = $utilisateurs_data['utilisateurs'];
$total_utilisateurs = $utilisateurs_data['total'];
$nb_pages = $utilisateurs_data['pages'];

// Récupération des données pour les selects
$types = $utilisateurController->getTypesUtilisateurs();
$groupes = $utilisateurController->getGroupesUtilisateurs();
$grades = $utilisateurController->getGrades();
$fonctions = $utilisateurController->getFonctions();
$specialites = $utilisateurController->getSpecialites();
$niveaux_acces = $utilisateurController->getNiveauxAcces();

// Récupération des utilisateurs inactifs pour le modal
$inactive_users = $utilisateurController->getInactiveUsers();

// Récupération des données pour les modales
$selected_user = null;
if (isset($_GET['action'], $_GET['id'])) {
    $selected_user = $utilisateurController->getUtilisateurDetails($_GET['id']);
}
?> 