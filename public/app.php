<?php
session_start();

// Inclusion des fichiers de configuration
require_once __DIR__ . '/../app/config/config.php';

// Récupération de la page demandée
$page = $_GET['page'] ?? 'dashboard';

// Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pageConnexion.php');
    exit();
}

// Gestion spéciale pour les listes
if (isset($_GET['liste'])) {
    $liste = $_GET['liste'];
    $listes_map = [
        'actions' => 'liste_actions.php',
        'entreprises' => 'liste_entreprises.php',
        'enseignants' => 'liste_enseignants.php',
        'annees_academiques' => 'liste_annees_academiques.php',
        'ue' => 'liste_ue.php',
        'ecue' => 'liste_ecue.php',
        'utilisateurs' => 'liste_utilisateurs.php',
        'types_utilisateurs' => 'liste_types_utilisateurs.php',
        'groupes_utilisateurs' => 'liste_groupes_utilisateurs.php',
        'fonctions' => 'liste_fonctions.php',
        'grades' => 'liste_grades.php',
        'specialites' => 'liste_specialites.php',
        'niveaux_acces' => 'liste_niveaux_acces.php',
        'niveaux_approbation' => 'liste_niveaux_approbation.php',
        'niveaux_etudes' => 'liste_niveaux_etudes.php',
        'statuts_jury' => 'liste_statuts_jury.php',
        'traitements' => 'liste_traitements.php',
        'semestres' => 'liste_semestres.php',
        'frais_inscriptions' => 'liste_frais_inscriptions.php',
        'promotions' => 'liste_promotions.php'
    ];
    
    if (isset($listes_map[$liste])) {
        $file_path = __DIR__ . '/../app/Views/listes/' . $listes_map[$liste];
        if (file_exists($file_path)) {
            include $file_path;
            exit();
        }
    }
    
    // Si la liste n'existe pas, rediriger vers les paramètres généraux
    header('Location: ' . BASE_URL . '/app.php?page=parametres_generaux');
    exit();
}

// Redirection selon le type d'utilisateur
$userGroupId = $_SESSION['id_user_group'] ?? 0;

switch ($userGroupId) {
    case 1: // Étudiant
        include __DIR__ . '/../app/Views/index_etudiant.php';
        break;
    case 2: // Chargé de communication
    case 3: // Responsable scolarité
    case 4: // Secrétaire
        include __DIR__ . '/../app/Views/index_personnel_administratif.php';
        break;
    case 5: // Enseignant sans responsabilité
    case 6: // Responsable niveau
    case 7: // Responsable filière
    case 8: // Administrateur plateforme
    case 9: // Commission de validation
        include __DIR__ . '/../app/Views/index_commission.php';
        break;
    default:
        $_SESSION['error_message'] = "Type d'utilisateur non reconnu";
        header('Location: ' . BASE_URL . '/pageConnexion.php');
        exit();
}
?> 