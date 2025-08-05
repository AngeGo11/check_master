<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'utilisateurs') {
    return;
}


// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');


// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../Controllers/UtilisateurController.php';
require_once __DIR__ . '/../../Models/Utilisateur.php';

// Initialiser le contrôleur
$utilisateurController = new UtilisateurController($pdo);

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
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
                    // Traitement des valeurs pour éviter les erreurs de contrainte
                    $type_utilisateur = !empty($_POST['id_type_utilisateur']) ? $_POST['id_type_utilisateur'] : null;
                    $groupe_utilisateur = !empty($_POST['id_GU']) ? $_POST['id_GU'] : null;
                    $niveau_acces = !empty($_POST['id_niveau_acces']) ? $_POST['id_niveau_acces'] : null;
                    
                    $result = $utilisateurController->assignMultipleUsers(
                        $_POST['selected_inactive_users'],
                        $type_utilisateur,
                        $groupe_utilisateur,
                        $niveau_acces
                    );
                    if ($result['success_count'] > 0) {
                        $message = $result['success_count'] . " utilisateur(s) affecté(s) et activé(s) avec succès.";
                        if ($result['error_count'] > 0) {
                            $message .= " " . $result['error_count'] . " erreur(s) rencontrée(s).";
                        }
                        $_SESSION['success'] = $message;
                    } else {
                        $_SESSION['error'] = "Erreur lors de l'affectation des utilisateurs.";
                    }
                    
                    // Afficher les erreurs détaillées si il y en a
                    if (!empty($result['error_messages'])) {
                        $_SESSION['error_details'] = $result['error_messages'];
                    }
                }
                break;
        }
    }
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
if (isset($_GET['action']) && isset($_GET['id'])) {
    $selected_user = $utilisateurController->getUtilisateurDetails($_GET['id']);
}

// Récupération des statistiques
$stats = $utilisateurController->getUtilisateursStats();

$fullname = $_SESSION['user_fullname'] ?? 'Utilisateur';
$lib_user_type = $_SESSION['lib_user_type'] ?? '';

// Les variables suivantes sont maintenant disponibles grâce au contrôleur MVC :
// $utilisateurs - Liste des utilisateurs avec pagination et filtres
// $total_utilisateurs - Nombre total d'utilisateurs
// $nb_pages - Nombre total de pages
// $page_courante - Page courante
// $types - Types d'utilisateurs pour les selects
// $groupes - Groupes d'utilisateurs pour les selects
// $grades - Grades pour les selects
// $fonctions - Fonctions pour les selects
// $specialites - Spécialités pour les selects
// $niveaux_acces - Niveaux d'accès pour les selects
// $inactive_users - Utilisateurs inactifs pour le modal
// $selected_user - Utilisateur sélectionné pour l'édition (si applicable)
// $stats - Statistiques des utilisateurs (total, actifs, inactifs)
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Utilisateurs - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2980b9',
                        'primary-lighter': '#3498db',
                        secondary: '#ff8c00',
                        accent: '#4caf50',
                        success: '#4caf50',
                        warning: '#f39c12',
                        danger: '#e74c3c',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'bounce-in': 'bounceIn 0.6s ease-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }

            50% {
                opacity: 1;
                transform: scale(1.05);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(26, 82, 118, 0.1), 0 10px 10px -5px rgba(26, 82, 118, 0.04);
        }

        /* Styles pour la modale */
        .modal-transition {
            transition: all 0.3s ease-in-out;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        .btn-icon {
            transition: all 0.2s ease-in-out;
        }

        .btn-icon:hover {
            transform: scale(1.1);
        }

        .bg-gradient {
            background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">
        <!-- Contenu principal -->
        <main class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- En-tête de page -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                <div class="border-l-4 border-primary bg-white rounded-r-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-primary/10 rounded-lg p-3 mr-4">
                                <i class="fas fa-users text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Utilisateurs</h1>
                                <p class="text-gray-600">Gestion des utilisateurs du système</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Connecté en tant que</div>
                                <div class="font-semibold text-gray-900"><?php echo $fullname; ?></div>
                                <div class="text-sm text-primary"><?php echo $lib_user_type; ?></div>
                            </div>
                            <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold text-lg">
                                <?php echo substr($fullname, 0, 1); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 animate-slide-up">
                <!-- Total utilisateurs -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des utilisateurs</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_utilisateurs']); ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-users text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Utilisateurs actifs -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Utilisateurs actifs</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['utilisateurs_actifs']); ?></p>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-user-check text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Utilisateurs inactifs -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-warning overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Utilisateurs inactifs</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['utilisateurs_inactifs']); ?></p>
                            </div>
                            <div class="bg-warning/10 rounded-full p-4">
                                <i class="fas fa-user-times text-2xl text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des utilisateurs -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                <!-- Barre d'actions -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                        <!-- Bouton de retour -->
                        <a href="?page=parametres_generaux"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Retour aux paramètres
                        </a>

                        <!-- Recherche -->
                        <div class="flex-1 w-full lg:w-auto">
                            <form method="GET" class="flex gap-3">
                                <input type="hidden" name="liste" value="utilisateurs">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text"
                                        name="search"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Rechercher un utilisateur..."
                                        value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                                </div>
                                <button type="submit"
                                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                    <i class="fas fa-search mr-2"></i>
                                    Rechercher
                                </button>
                            </form>
                        </div>

                        <!-- Bouton d'ajout -->
                        <button onclick="showAddModal()"
                            class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-green-600 transition-colors duration-200 flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter un utilisateur
                        </button>
                        
                       
                    </div>
                </div>

                <!-- Messages d'alerte -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-green-800"><?php echo $_SESSION['success'];
                                                            unset($_SESSION['success']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <span class="text-red-800"><?php echo $_SESSION['error'];
                                                        unset($_SESSION['error']); ?></span>
                        </div>
                        <?php if (isset($_SESSION['error_details'])): ?>
                            <div class="mt-3 pl-6">
                                <details class="text-sm">
                                    <summary class="cursor-pointer text-red-700 font-medium">Voir les détails des erreurs</summary>
                                    <ul class="mt-2 space-y-1">
                                        <?php foreach ($_SESSION['error_details'] as $error): ?>
                                            <li class="text-red-600">• <?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            </div>
                            <?php unset($_SESSION['error_details']); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Filtres -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center">
                        <select class="px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            id="typeFilter" onchange="applyFilters()">
                            <option value="">Tous les types</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?php echo $type['id_tu']; ?>" <?php echo (isset($_GET['type']) && $_GET['type'] == $type['id_tu']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['lib_tu']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select class="px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            id="groupeFilter" onchange="applyFilters()">
                            <option value="">Tous les groupes</option>
                            <?php foreach ($groupes as $groupe): ?>
                                <option value="<?php echo $groupe['id_gu']; ?>" <?php echo (isset($_GET['groupe']) && $_GET['groupe'] == $groupe['id_gu']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($groupe['lib_gu']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <select class="px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            id="statutFilter" onchange="applyFilters()">
                            <option value="">Tous les statuts</option>
                            <option value="Actif" <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'Actif') ? 'selected' : ''; ?>>Actif</option>
                            <option value="Inactif" <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'Inactif') ? 'selected' : ''; ?>>Inactif</option>
                        </select>

                        <div class="flex gap-2">
                            <button class="px-4 py-2 bg-warning text-white rounded-lg hover:bg-orange-600 transition-colors duration-200 flex items-center"
                                onclick="activateSelectedUsers()" id="generatePasswordsBtn" style="display: none;">
                                <i class="fas fa-key mr-2"></i>
                                Donner accès
                            </button>
                            <button class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center">
                                <i class="fas fa-power-off mr-2"></i>
                                Désactiver
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table des utilisateurs -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nom complet
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($utilisateurs) === 0): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-gray-500 text-lg">Aucun utilisateur trouvé</p>
                                            <p class="text-gray-400 text-sm">Essayez de modifier vos critères de recherche</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($utilisateurs as $utilisateur): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" class="row-checkbox rounded border-gray-300 text-primary focus:ring-primary" value="<?= $utilisateur['id_utilisateur']; ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                #<?= $utilisateur['id_utilisateur']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($utilisateur['nom_complet'] ?? ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($utilisateur['login_utilisateur'] ?? ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?= htmlspecialchars($utilisateur['lib_gu'] ?? 'Non défini'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $utilisateur['statut_utilisateur'] === 'Actif' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?= htmlspecialchars($utilisateur['statut_utilisateur'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="?page=liste_utilisateurs&action=view&id=<?php echo $utilisateur['id_utilisateur']; ?>" class="action-button view-button" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?page=liste_utilisateurs&action=edit&id=<?php echo $utilisateur['id_utilisateur']; ?>" class="action-button edit-button" title="Modifier">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <button type="button" class="<?= $utilisateur['statut_utilisateur'] === 'Actif' ? 'text-danger hover:text-red-600' : 'text-accent hover:text-green-600'; ?> transition-colors duration-200"
                                                    title="<?= $utilisateur['statut_utilisateur'] === 'Actif' ? 'Désactiver' : 'Activer'; ?>"
                                                    onclick="toggleUserStatus(<?= $utilisateur['id_utilisateur']; ?>, '<?= $utilisateur['statut_utilisateur']; ?>')">
                                                    <i class="fas fa-power-off"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($nb_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Affichage de <span class="font-medium"><?php echo (($page_courante - 1) * 75 + 1); ?></span> à <span class="font-medium"><?php echo min($page_courante * 75, $total_utilisateurs); ?></span> sur <span class="font-medium"><?php echo $total_utilisateurs; ?></span> résultats
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($page_courante > 1): ?>
                                    <a href="?liste=utilisateurs&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&type=<?php echo urlencode($_GET['type'] ?? ''); ?>&groupe=<?php echo urlencode($_GET['groupe'] ?? ''); ?>&statut=<?php echo urlencode($_GET['statut'] ?? ''); ?>&page=1"
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?liste=utilisateurs&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&type=<?php echo urlencode($_GET['type'] ?? ''); ?>&groupe=<?php echo urlencode($_GET['groupe'] ?? ''); ?>&statut=<?php echo urlencode($_GET['statut'] ?? ''); ?>&page=<?php echo $page_courante - 1; ?>"
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $page_courante - 2);
                                $end = min($nb_pages, $page_courante + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <a href="?liste=utilisateurs&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&type=<?php echo urlencode($_GET['type'] ?? ''); ?>&groupe=<?php echo urlencode($_GET['groupe'] ?? ''); ?>&statut=<?php echo urlencode($_GET['statut'] ?? ''); ?>&page=<?php echo $i; ?>"
                                        class="px-3 py-2 text-sm font-medium <?php if ($i == $page_courante): ?>text-white bg-primary border-primary<?php else: ?>text-gray-500 bg-white border-gray-300 hover:bg-gray-50<?php endif; ?> border rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page_courante < $nb_pages): ?>
                                    <a href="?liste=utilisateurs&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&type=<?php echo urlencode($_GET['type'] ?? ''); ?>&groupe=<?php echo urlencode($_GET['groupe'] ?? ''); ?>&statut=<?php echo urlencode($_GET['statut'] ?? ''); ?>&page=<?php echo $page_courante + 1; ?>"
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?liste=utilisateurs&search=<?php echo urlencode($_GET['search'] ?? ''); ?>&type=<?php echo urlencode($_GET['type'] ?? ''); ?>&groupe=<?php echo urlencode($_GET['groupe'] ?? ''); ?>&statut=<?php echo urlencode($_GET['statut'] ?? ''); ?>&page=<?php echo $nb_pages; ?>"
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal d'ajout d'utilisateur -->
    <div id="addUser"
        class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex hidden items-center justify-center transition-all duration-300 ease-in-out">
        <div class="relative w-full max-w-4xl mx-4 bg-white rounded-2xl shadow-2xl transform transition-all duration-300 scale-95 opacity-0" id="modalContent">
            <!-- Header avec gradient -->
            <div class="relative bg-primary from-blue-600 to-indigo-700 rounded-t-2xl p-6">
                <div class="absolute top-4 right-4">
                    <button onclick="closeAddModal()"
                        class="text-white hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600 rounded-full p-2 transition-all duration-200">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                <div class="flex items-center">
                    <div class="bg-white bg-opacity-20 p-3 rounded-full mr-4 backdrop-blur-sm">
                        <i class="fas fa-user-plus text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-white">Ajouter des utilisateurs</h3>
                        <p class="text-blue-100 mt-1">Sélectionnez les nouveaux utilisateurs à ajouter au système</p>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="p-6 max-h-96 overflow-y-auto">
                <form method="POST" action="?liste=utilisateurs" class="space-y-6" id="userForm">
                    <input type="hidden" name="action" value="assign_multiple">
                    <!-- Barre de recherche améliorée -->
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 group-focus-within:text-blue-500 transition-colors duration-200"></i>
                        </div>
                        <input type="text" id="searchUsers" placeholder="Rechercher par nom ou email..."
                            class="block w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 focus:bg-white transition-all duration-200 text-gray-700 placeholder-gray-400">
                    </div>

                    <!-- Filtres avec design moderne -->
                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="filter-btn px-5 py-2 rounded-full active bg-gradient text-white shadow-lg" data-filter="all">
                            <i class="fas fa-users mr-2"></i>Tous
                        </button>
                        <button type="button" class="filter-btn px-5 py-2 rounded-full bg-white text-gray-700 hover:bg-gray-50 border border-gray-200" data-filter="Étudiant">
                            <i class="fas fa-user-graduate mr-2"></i>Étudiants
                        </button>
                        <button type="button" class="filter-btn px-5 py-2 rounded-full bg-white text-gray-700 hover:bg-gray-50 border border-gray-200" data-filter="Enseignant">
                            <i class="fas fa-chalkboard-teacher mr-2"></i>Enseignants
                        </button>
                        <button type="button" class="filter-btn px-5 py-2 rounded-full bg-white text-gray-700 hover:bg-gray-50 border border-gray-200" data-filter="Personnel Administratif">
                            <i class="fas fa-user-tie mr-2"></i>Personnel
                        </button>
                    </div>

                    <!-- Section de sélection -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-users text-gradient mr-3 text-xl"></i>Sélectionner les personnes
                            </label>
                            <div class="text-sm text-gray-500">
                                <span id="totalFiltered">0</span> sur <span id="totalUsers">0</span> utilisateurs
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <?php if (!empty($inactive_users)): ?>
                                <!-- Section tout sélectionner -->
                                <div class="px-6 py-4 bg-gradient-to-r from-gray-50 to-blue-50 border-b border-gray-200">
                                    <div class="flex items-center">
                                        <div class="relative">
                                            <input type="checkbox" id="selectAllInactiveUsers"
                                                class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                            <div class="absolute inset-0 rounded pointer-events-none"></div>
                                        </div>
                                        <label for="selectAllInactiveUsers" class="ml-3 text-sm font-semibold text-gray-700">
                                            Tout sélectionner (<span id="totalUsers"><?php echo count($inactive_users); ?></span> utilisateurs)
                                        </label>
                                    </div>
                                </div>

                                <!-- Liste des utilisateurs -->
                                <div id="usersList" class="max-h-60 overflow-y-auto">
                                    <?php foreach ($inactive_users as $inactive_user): ?>
                                        <div class="user-item p-4 border-b border-gray-100 hover:bg-blue-50 transition-all duration-200 group"
                                            data-type="<?php echo htmlspecialchars($inactive_user['type_source']); ?>"
                                            data-name="<?php echo htmlspecialchars(strtolower($inactive_user['nom_complet'])); ?>"
                                            data-email="<?php echo htmlspecialchars(strtolower($inactive_user['login_utilisateur'])); ?>">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-4">
                                                    <div class="relative">
                                                        <input type="checkbox"
                                                            name="selected_inactive_users[]"
                                                            value="<?php echo htmlspecialchars($inactive_user['id_utilisateur']); ?>"
                                                            class="inactive-user-checkbox w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                                                            id="user_<?php echo htmlspecialchars($inactive_user['id_utilisateur']); ?>">
                                                    </div>
                                                    <div class="flex items-center space-x-3">
                                                        <span class="user-type-badge <?php echo strtolower(str_replace(' ', '-', $inactive_user['type_source'])); ?> inline-flex items-center px-3 py-1 rounded-full text-base font-medium">
                                                            <?php
                                                            $icon = '';
                                                            switch ($inactive_user['type_source']) {
                                                                case 'Étudiant':
                                                                    $icon = 'fas fa-user-graduate';
                                                                    break;
                                                                case 'Enseignant':
                                                                    $icon = 'fas fa-chalkboard-teacher';
                                                                    break;
                                                                case 'Personnel Administratif':
                                                                    $icon = 'fas fa-user-tie';
                                                                    break;
                                                                default:
                                                                    $icon = 'fas fa-user';
                                                            }
                                                            ?>
                                                            <i class="<?php echo $icon; ?> mr-2"></i>
                                                            <?php echo htmlspecialchars($inactive_user['type_source']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <div class="font-semibold text-gray-900 text-lg">
                                                        <?php echo htmlspecialchars($inactive_user['nom_complet']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500 flex items-center justify-end mt-1">
                                                        <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                                        <?php echo htmlspecialchars($inactive_user['login_utilisateur']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Pagination améliorée -->
                                <div id="pagination" class="px-6 py-4 bg-gradient-to-r from-gray-50 to-blue-50 border-t border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-600">
                                            Affichage de <span id="startRange" class="font-semibold text-blue-600">1</span> à <span id="endRange" class="font-semibold text-blue-600">12</span> sur <span id="totalFiltered" class="font-semibold text-blue-600"><?php echo count($inactive_users); ?></span> utilisateurs
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <button type="button" id="prevPage" class="pagination-btn inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200" disabled>
                                                <i class="fas fa-chevron-left mr-1"></i>Précédent
                                            </button>
                                            <span id="pageInfo" class="text-sm font-medium text-gray-700 bg-white px-3 py-2 rounded-md border border-gray-200">Page 1</span>
                                            <button type="button" id="nextPage" class="pagination-btn inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200">
                                                Suivant<i class="fas fa-chevron-right ml-1"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="p-12 text-center">
                                    <div class="bg-gray-50 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-users-slash text-3xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Aucun nouvel utilisateur</h3>
                                    <p class="text-gray-500">Tous les utilisateurs sont déjà actifs dans le système.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Configuration des utilisateurs -->
                    <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-cog text-gradient mr-3"></i>Configuration des utilisateurs
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="space-y-2">
                                <label for="mass_type_utilisateur" class="block text-sm font-semibold text-gray-700 flex items-center">
                                    <i class="fas fa-id-badge text-gradient mr-2"></i>Type utilisateur
                                </label>
                                <select name="id_type_utilisateur" id="mass_type_utilisateur" required
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-all duration-200 text-gray-700">
                                    <option value="">Sélectionner un type</option>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['id_tu']); ?>">
                                            <?php echo htmlspecialchars($type['lib_tu']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="mass_groupe_utilisateur" class="block text-sm font-semibold text-gray-700 flex items-center">
                                    <i class="fas fa-users text-gradient mr-2"></i>Groupe utilisateur
                                </label>
                                <select name="id_GU" id="mass_groupe_utilisateur" required
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-all duration-200 text-gray-700">
                                    <option value="">Sélectionner un groupe</option>
                                    <?php foreach ($groupes as $groupe): ?>
                                        <option value="<?php echo htmlspecialchars($groupe['id_gu']); ?>">
                                            <?php echo htmlspecialchars($groupe['lib_gu']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="mass_niveau_acces" class="block text-sm font-semibold text-gray-700 flex items-center">
                                    <i class="fas fa-lock text-gradient mr-2"></i>Niveau d'accès
                                </label>
                                <select name="id_niveau_acces" id="mass_niveau_acces" 
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-all duration-200 text-gray-700">
                                    <option value="">Sélectionner un niveau</option>
                                    <?php foreach ($niveaux_acces as $niveau): ?>
                                        <option value="<?php echo htmlspecialchars($niveau['id_niveau_acces']); ?>">
                                            <?php echo htmlspecialchars($niveau['lib_niveau_acces']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer avec boutons -->
            <div class="bg-gray-50 px-6 py-4 rounded-b-2xl border-t border-gray-200">
                <div class="flex justify-between items-center">
                    <button type="button" onclick="closeMasseModal()"
                        class="px-6 py-3 border border-gray-300 text-sm font-semibold rounded-xl shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                    <button type="submit" name="btn_add_multiple" form="userForm" onclick="return validateForm()"
                        class="px-8 py-3 bg-gradient from-blue-600 to-indigo-600 border border-transparent text-sm font-semibold rounded-xl shadow-lg text-white hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:scale-105 transition-all duration-200">
                        <i class="fa-solid fa-key mr-2"></i>Appliquer les affectations & donner les accès
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour activer un utilisateur -->
    <div id="activateUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-power-off"></i> Confirmer l'activation</h2>
                <span class="close" onclick="closeModal('activateUserModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir activer cet utilisateur ?</p>
                <p class="info-text">Cette action permettra à l'utilisateur de se connecter au système.</p>
            </div>
            <form id="activateUserForm" method="POST">
                <input type="hidden" name="action" value="activate_user">
                <input type="hidden" name="id_utilisateur" id="activateUserId">
                <div class="form-actions">
                    <button type="submit" class="button">
                        <i class="fas fa-power-off"></i> Confirmer l'activation
                    </button>
                    <button type="button" class="button" onclick="closeModal('activateUserModal')">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="./assets/js/gs_liste_utilisateur.js"></script>

    <script>
        // Fonctions pour la gestion des modals
        function showAddModal() {
            const modal = document.getElementById('addUser');
            const modalContent = document.getElementById('modalContent');

            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Animation d'entrée
            setTimeout(() => {
                modalContent.style.transform = 'scale(1) translateY(0)';
                modalContent.style.opacity = '1';
            }, 10);
        }

        function closeAddModal() {
            const modal = document.getElementById('addUser');
            const modalContent = document.getElementById('modalContent');

            // Animation de sortie
            modalContent.style.transform = 'scale(0.95) translateY(-20px)';
            modalContent.style.opacity = '0';

            setTimeout(() => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }, 300);
        }

        function closeMasseModal() {
            closeAddModal();
        }

        function editUser(id) {
            // Implémenter la modification d'utilisateur
            console.log('Modifier utilisateur:', id);
        }

        function viewtUser(id) {
            // Implémenter la visualisation d'utilisateur
            console.log('Voir utilisateur:', id);
        }

        function resetPassword(id) {
            // Implémenter la réinitialisation de mot de passe
            console.log('Réinitialiser mot de passe:', id);
        }

        function toggleUserStatus(id, currentStatus) {
            // Implémenter le changement de statut
            console.log('Changer statut:', id, currentStatus);
        }

        // Sélection/désélection toutes les cases
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
            updateGenerateButton();
        });

        // Mise à jour du bouton de génération de mots de passe
        function updateGenerateButton() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const btn = document.getElementById('generatePasswordsBtn');
            if (checked.length > 0) {
                btn.style.display = 'flex';
            } else {
                btn.style.display = 'none';
            }
        }

        // Écouter les changements sur les checkboxes individuelles
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('row-checkbox')) {
                updateGenerateButton();
            }
        });



        function applyFilters() {
            const type = document.getElementById('typeFilter').value;
            const groupe = document.getElementById('groupeFilter').value;
            const statut = document.getElementById('statutFilter').value;

            let url = '?liste=utilisateurs';
            if (type) url += '&type=' + encodeURIComponent(type);
            if (groupe) url += '&groupe=' + encodeURIComponent(groupe);
            if (statut) url += '&statut=' + encodeURIComponent(statut);

            // Garder la recherche si présente
            const searchParams = new URLSearchParams(window.location.search);
            if (searchParams.get('search')) {
                url += '&search=' + encodeURIComponent(searchParams.get('search'));
            }

            window.location.href = url;
        }

        // Fermer la modale si on clique en dehors
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('addUser')) {
                closeAddModal();
            }
        });

        // Fermer la modale avec la touche Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAddModal();
            }
        });

        // =================================
        // GESTION DE LA PAGINATION ET RECHERCHE
        // =================================

        // Variables globales pour la pagination
        let currentPage = 1;
        let itemsPerPage = 12;
        let filteredUsers = [];
        let allUsers = [];

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            initializeUserManagement();
        });

        function initializeUserManagement() {
            // Récupérer tous les utilisateurs
            allUsers = Array.from(document.querySelectorAll('.user-item'));
            filteredUsers = [...allUsers];

            // Initialiser les événements
            setupSearchEvent();
            setupFilterEvents();
            setupPaginationEvents();
            setupSelectAllEvent();

            // Afficher la première page
            updateDisplay();
        }

        // Configuration de la recherche
        function setupSearchEvent() {
            const searchInput = document.getElementById('searchUsers');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    filterUsers(searchTerm);
                });
            }
        }

        // Configuration des filtres
        function setupFilterEvents() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            filterButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Retirer la classe active de tous les boutons
                    filterButtons.forEach(b => b.classList.remove('active'));
                    // Ajouter la classe active au bouton cliqué
                    this.classList.add('active');

                    const filterType = this.getAttribute('data-filter');
                    filterUsers(document.getElementById('searchUsers')?.value || '', filterType);
                });
            });
        }

        // Configuration de la pagination
        function setupPaginationEvents() {
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');

            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    if (currentPage > 1) {
                        currentPage--;
                        updateDisplay();
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    const maxPage = Math.ceil(filteredUsers.length / itemsPerPage);
                    if (currentPage < maxPage) {
                        currentPage++;
                        updateDisplay();
                    }
                });
            }
        }

        // Configuration de "Tout sélectionner"
        function setupSelectAllEvent() {
            const selectAllCheckbox = document.getElementById('selectAllInactiveUsers');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const visibleCheckboxes = document.querySelectorAll('.user-item:not(.hidden) .inactive-user-checkbox');
                    visibleCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                    updateSelectAllState();
                });
            }
        }

        // Filtrer les utilisateurs
        function filterUsers(searchTerm = '', filterType = 'all') {
            filteredUsers = allUsers.filter(user => {
                const name = user.getAttribute('data-name') || '';
                const email = user.getAttribute('data-email') || '';
                const type = user.getAttribute('data-type') || '';

                // Filtre par recherche
                const matchesSearch = !searchTerm ||
                    name.includes(searchTerm) ||
                    email.includes(searchTerm);

                // Filtre par type
                const matchesType = filterType === 'all' || type === filterType;

                return matchesSearch && matchesType;
            });

            // Réinitialiser à la première page
            currentPage = 1;
            updateDisplay();
        }

        // Mettre à jour l'affichage
        function updateDisplay() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const usersToShow = filteredUsers.slice(startIndex, endIndex);

            // Masquer tous les utilisateurs
            allUsers.forEach(user => {
                user.classList.add('hidden');
            });

            // Afficher seulement les utilisateurs de la page courante
            usersToShow.forEach(user => {
                user.classList.remove('hidden');
            });

            // Mettre à jour la pagination
            updatePagination();

            // Mettre à jour les compteurs
            updateCounters();

            // Mettre à jour l'état de "Tout sélectionner"
            updateSelectAllState();
        }

        // Mettre à jour la pagination
        function updatePagination() {
            const totalPages = Math.ceil(filteredUsers.length / itemsPerPage);
            const prevBtn = document.getElementById('prevPage');
            const nextBtn = document.getElementById('nextPage');
            const pageInfo = document.getElementById('pageInfo');

            if (prevBtn) {
                prevBtn.disabled = currentPage <= 1;
            }

            if (nextBtn) {
                nextBtn.disabled = currentPage >= totalPages;
            }

            if (pageInfo) {
                pageInfo.textContent = `Page ${currentPage} sur ${totalPages}`;
            }
        }

        // Mettre à jour les compteurs
        function updateCounters() {
            const startRange = document.getElementById('startRange');
            const endRange = document.getElementById('endRange');
            const totalFiltered = document.getElementById('totalFiltered');
            const totalUsers = document.getElementById('totalUsers');

            const startIndex = (currentPage - 1) * itemsPerPage + 1;
            const endIndex = Math.min(currentPage * itemsPerPage, filteredUsers.length);

            if (startRange) startRange.textContent = startIndex;
            if (endRange) endRange.textContent = endIndex;
            if (totalFiltered) totalFiltered.textContent = filteredUsers.length;
            if (totalUsers) totalUsers.textContent = filteredUsers.length;
        }

        // Mettre à jour l'état de "Tout sélectionner"
        function updateSelectAllState() {
            const selectAllCheckbox = document.getElementById('selectAllInactiveUsers');
            const visibleCheckboxes = document.querySelectorAll('.user-item:not(.hidden) .inactive-user-checkbox');
            const checkedVisibleCheckboxes = document.querySelectorAll('.user-item:not(.hidden) .inactive-user-checkbox:checked');

            if (selectAllCheckbox && visibleCheckboxes.length > 0) {
                selectAllCheckbox.checked = checkedVisibleCheckboxes.length === visibleCheckboxes.length;
                selectAllCheckbox.indeterminate = checkedVisibleCheckboxes.length > 0 && checkedVisibleCheckboxes.length < visibleCheckboxes.length;
            }
        }

        // Écouter les changements sur les checkboxes individuelles
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('inactive-user-checkbox')) {
                updateSelectAllState();
            }
        });

        // Fonction de validation du formulaire
        function validateForm() {
            const selectedUsers = document.querySelectorAll('.inactive-user-checkbox:checked');
            const typeUtilisateur = document.getElementById('mass_type_utilisateur').value;
            const groupeUtilisateur = document.getElementById('mass_groupe_utilisateur').value;
            
            // Vérifier qu'au moins un utilisateur est sélectionné
            if (selectedUsers.length === 0) {
                alert('Veuillez sélectionner au moins un utilisateur.');
                return false;
            }
            
            // Vérifier que le type utilisateur est sélectionné
            if (!typeUtilisateur) {
                alert('Veuillez sélectionner un type d\'utilisateur.');
                document.getElementById('mass_type_utilisateur').focus();
                return false;
            }
            
            // Vérifier que le groupe utilisateur est sélectionné
            if (!groupeUtilisateur) {
                alert('Veuillez sélectionner un groupe d\'utilisateur.');
                document.getElementById('mass_groupe_utilisateur').focus();
                return false;
            }
            
            // Confirmation avant envoi
            return confirm(`Êtes-vous sûr de vouloir affecter ${selectedUsers.length} utilisateur(s) avec les paramètres sélectionnés ?`);
        }
    </script>
</body>

</html>