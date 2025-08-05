<?php
require_once '../app/Controllers/UtilisateurController.php';

// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log',  '../storage/logs/php-error.log');

// Initialiser le contrôleur
$utilisateurController = new UtilisateurController($pdo);

// Traitement des actions POST
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
                        $_SESSION['success'] = $result['success_count'] . " utilisateur(s) activé(s) avec succès. Les mots de passe ont été générés et envoyés par email.";
                    } else {
                        $_SESSION['error'] = "Erreur lors de l'activation des utilisateurs.";
                    }
                }
                break;

            case 'edit_user':
                if (isset($_POST['id_utilisateur']) && isset($_POST['type_utilisateur'])) {
                    $result = $utilisateurController->editUser(
                        $_POST['id_utilisateur'],
                        $_POST['type_utilisateur'],
                        $_POST['groupe_utilisateur'] ?? null,
                        $_POST['niveaux_acces'] ?? [],
                        $_POST['fonction'] ?? null,
                        $_POST['grade'] ?? null,
                        $_POST['specialite'] ?? null
                    );
                    if ($result['success']) {
                        $_SESSION['success'] = $result['message'];
                    } else {
                        $_SESSION['error'] = $result['message'];
                    }
                }
                break;

            case 'desactivate_user':
                if (isset($_POST['id_utilisateur'])) {
                    $result = $utilisateurController->desactivateUser($_POST['id_utilisateur']);
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
                if (isset($_POST['selected_inactive_users']) && is_array($_POST['selected_inactive_users'])) {
                    $result = $utilisateurController->assignMultipleUsers(
                        $_POST['selected_inactive_users'],
                        $_POST['assign_type_utilisateur'] ?? null,
                        $_POST['assign_groupe_utilisateur'] ?? null,
                        $_POST['assign_niveau_acces'] ?? null
                    );
                    if ($result['success_count'] > 0) {
                        $_SESSION['success'] = $result['success_count'] . " utilisateur(s) affecté(s) et activé(s) avec succès.";
                    } else {
                        $_SESSION['error'] = "Erreur lors de l'affectation des utilisateurs.";
                    }
                }
                break;
        }
    }

    // Redirection pour éviter le repost
    header('Location: ?liste=utilisateurs');
    exit;
}

// Récupération des données pour l'affichage
$page_courante = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$filters = [];

// Filtres de recherche
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $filters['type'] = $_GET['type'];
}
if (isset($_GET['groupe']) && !empty($_GET['groupe'])) {
    $filters['groupe'] = $_GET['groupe'];
}
if (isset($_GET['statut']) && !empty($_GET['statut'])) {
    $filters['statut'] = $_GET['statut'];
}

// Récupération des données via le contrôleur
$utilisateurs_data = $utilisateurController->getUtilisateursWithFilters($page_courante, 75, $filters);
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

// Récupération des détails d'un utilisateur sélectionné (si applicable)
$selected_user = null;
if (isset($_GET['edit_user']) && !empty($_GET['edit_user'])) {
    $selected_user = $utilisateurController->getUtilisateurDetails($_GET['edit_user']);
}

// Récupération des statistiques
$stats = $utilisateurController->getUtilisateursStats();