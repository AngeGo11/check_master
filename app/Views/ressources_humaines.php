<?php
require_once __DIR__ . '/../Controllers/GestionRhController.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Controllers/EnseignantController.php';
require_once __DIR__ . '/../Controllers/PersonnelAdministratifController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Instanciation et gestion de la logique RH
$gestionRh = new GestionRhController($pdo);
$gestionRh->handleRequest();

// Initialisation des contrôleurs
$enseignantController = new EnseignantController($pdo);
$personnelController = new PersonnelAdministratifController($pdo);

// Récupération des statistiques via les contrôleurs
$stats_enseignants = $enseignantController->getStatistics();
$stats_personnel = $personnelController->getStatistics();

// Paramètres de pagination
$utilisateurs_par_page = 5;
$page_courante_ens = isset($_GET['page_ens']) && is_numeric($_GET['page_ens']) ? (int)$_GET['page_ens'] : 1;
$page_courante_pers = isset($_GET['page_pers']) && is_numeric($_GET['page_pers']) ? (int)$_GET['page_pers'] : 1;

// Récupération des données via les contrôleurs
$enseignants = $enseignantController->getEnseignantsWithPagination($page_courante_ens, $utilisateurs_par_page);
$personnel = $personnelController->getPersonnelWithPagination($page_courante_pers, $utilisateurs_par_page);

// Calcul du nombre de pages
$total_enseignants_count = $stats_enseignants['total'];
$total_personnel_count = $stats_personnel['total'];
$nb_pages_enseignants = ceil($total_enseignants_count / $utilisateurs_par_page);
$nb_pages_personnel = ceil($total_personnel_count / $utilisateurs_par_page);

// Récupération des listes pour les filtres
$grades = $enseignantController->getGrades();
$fonctions = $enseignantController->getFonctions();
$specialites = $enseignantController->getSpecialites();
$groupes = $personnelController->getGroupes();

// Extraction des statistiques pour l'affichage
$total_enseignants = $stats_enseignants['total'];
$total_vacataires = $stats_enseignants['vacataires'];
$total_professeurs = $stats_enseignants['professeurs'];

$total_personnel = $stats_personnel['total'];
$total_secretaires = $stats_personnel['secretaires'];
$total_communication = $stats_personnel['communication'];
$total_scolarite = $stats_personnel['scolarite'];

// Déterminer l'onglet actif (par défaut 'pers_admin')
$activeTab = $_GET['tab'] ?? 'pers_admin';
if (!in_array($activeTab, ['pers_admin', 'enseignant'])) {
    $activeTab = 'pers_admin';
}

// Récupération des messages depuis le contrôleur
$messageErreur = $GLOBALS['messageErreur'] ?? '';
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';

// Récupération des données depuis le contrôleur
$personnel_admin = $GLOBALS['listePersAdmin'] ?? [];
// Ne pas écraser les données paginées des enseignants
// $enseignants = $GLOBALS['listeEnseignants'] ?? [];
$listeGrades = $GLOBALS['listeGrades'] ?? [];
$listeFonctions = $GLOBALS['listeFonctions'] ?? [];
$listeSpecialites = $GLOBALS['listeSpecialites'] ?? [];

// Récupération des données pour édition
$pers_admin_a_modifier = $GLOBALS['pers_admin_a_modifier'] ?? null;
$enseignant_a_modifier = $GLOBALS['enseignant_a_modifier'] ?? null;

// Gestion des actions CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// Structure pour le formulaire d'édition
$admin_edit = $pers_admin_a_modifier ?? null;
// Structure pour le formulaire d'édition enseignant
$enseignant_edit = $enseignant_a_modifier ?? null;
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Ressources Humaines - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',        // Bleu de la sidebar
                        'primary-light': '#2980b9', // Bleu plus clair
                        'primary-lighter': '#3498db', // Encore plus clair
                        secondary: '#ff8c00',      // Orange de l'app
                        accent: '#4caf50',         // Vert de l'app
                        success: '#4caf50',        // Vert
                        warning: '#f39c12',        // Jaune/Orange
                        danger: '#e74c3c',         // Rouge
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'bounce-in': 'bounceIn 0.6s ease-out',
                        'slide-in-left': 'slideInLeft 0.3s ease-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(26, 82, 118, 0.1), 0 10px 10px -5px rgba(26, 82, 118, 0.04);
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">


        <!-- Contenu principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Messages de notification -->
            <?php if (!empty($messageSuccess)): ?>
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg animate-slide-in-left">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3"></i>
                        <span><?= htmlspecialchars($messageSuccess) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($messageErreur)): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg animate-slide-in-left">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3"></i>
                        <span><?= htmlspecialchars($messageErreur) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation par onglets -->
            <div class="mb-8">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap active" 
                                data-tab="enseignants">
                            <i class="fas fa-chalkboard-teacher mr-2"></i>
                            Enseignants
                        </button>
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap" 
                                data-tab="personnel">
                            <i class="fas fa-user-tie mr-2"></i>
                            Personnel Administratif
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Section Enseignants -->
            <section id="enseignants" class="tab-content active">
                <!-- KPI Cards Enseignants -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-primary-light"><?= $total_enseignants ?></p>
                                <p class="text-sm font-medium text-gray-600 mt-1">Total enseignants</p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-users text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.1s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-primary-light"><?= $total_vacataires ?></p>
                                <p class="text-sm font-medium text-gray-600 mt-1">Enseignants vacataires</p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-user-clock text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.2s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-primary-light"><?= $total_professeurs ?></p>
                                <p class="text-sm font-medium text-gray-600 mt-1">Professeurs d'université</p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-user-graduate text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions et filtres pour enseignants -->
                <div class="bg-white rounded-2xl shadow-lg mb-8 animate-fade-in">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                            <!-- Recherche et filtres -->
                            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 flex-1">
                                <div class="relative flex-1 max-w-md">
                                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" placeholder="Rechercher un enseignant..." 
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                
                                <select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Tous les grades</option>
                                    <?php foreach ($grades as $grade): ?>
                                        <option value="<?= $grade['id_grd'] ?>">
                                            <?= htmlspecialchars($grade['nom_grd'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Toutes les fonctions</option>
                                    <?php foreach ($fonctions as $fonction): ?>
                                        <option value="<?= $fonction['id_fonction'] ?>">
                                            <?= htmlspecialchars($fonction['nom_fonction'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-3">
                                <button class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors flex items-center bulk-delete-btn" id="bulk-delete-btn">
                                    <i class="fas fa-trash mr-2"></i> Supprimer la sélection
                                </button>
                                <button class="px-4 py-2 bg-secondary text-white rounded-lg hover:bg-orange-600 transition-colors flex items-center bulk-export-btn" id="bulk-export-btn">
                                    <i class="fas fa-file mr-2"></i> Exporter
                                </button>
                                <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center" id="add_enseignant">
                                    <i class="fas fa-plus mr-2"></i> Ajouter un Enseignant
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table des enseignants -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="select-all-enseignants" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matricule</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom complet</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fonction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spécialité</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($enseignants as $enseignant): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" class="enseignant-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                   value="<?= $enseignant['id_ens'] ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= $enseignant['id_ens'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                                    <span class="text-primary font-medium">
                                                        <?= strtoupper(substr($enseignant['nom_ens'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars(($enseignant['nom_ens'] ?? '') . ' ' . ($enseignant['prenoms_ens'] ?? '')) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?= htmlspecialchars($enseignant['email_ens'] ?? '') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($enseignant['nom_fonction'] ?? 'Non défini') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($enseignant['nom_grd'] ?? 'Non défini') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($enseignant['lib_spe'] ?? 'Non défini') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors edit-enseignant" 
                                                        title="Modifier"
                                                        data-id="<?= $enseignant['id_ens'] ?>"
                                                        data-nom="<?= htmlspecialchars($enseignant['nom_ens'] ?? '') ?>"
                                                        data-prenoms="<?= htmlspecialchars($enseignant['prenoms_ens'] ?? '') ?>"
                                                        data-email="<?= htmlspecialchars($enseignant['email_ens'] ?? '') ?>"
                                                        data-grade="<?= htmlspecialchars($enseignant['nom_grd'] ?? '') ?>"
                                                        data-specialite="<?= htmlspecialchars($enseignant['lib_spe'] ?? '') ?>"
                                                        data-fonction="<?= htmlspecialchars($enseignant['nom_fonction'] ?? '') ?>"
                                                        data-date-entree="<?= $enseignant['date_entree_fonction'] ?? '' ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-colors delete-enseignant" 
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination enseignants -->
                    <?php if ($nb_pages_enseignants > 1): ?>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="flex items-center justify-center space-x-2">
                                <?php if ($page_courante_ens > 1): ?>
                                    <a href="?page=ressources_humaines&page_ens=<?= $page_courante_ens - 1 ?>&tab=enseignants#enseignants" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $nb_pages_enseignants; $i++): ?>
                                    <a href="?page=ressources_humaines&page_ens=<?= $i ?>&tab=enseignants#enseignants" 
                                       class="px-3 py-2 text-sm font-medium <?= $i === $page_courante_ens ? 'bg-primary text-white' : 'text-gray-500 bg-white hover:bg-gray-50' ?> border border-gray-300 rounded-md">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page_courante_ens < $nb_pages_enseignants): ?>
                                    <a href="?page=ressources_humaines&page_ens=<?= $page_courante_ens + 1 ?>&tab=enseignants#enseignants" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Section Personnel Administratif -->
            <section id="personnel" class="tab-content hidden">
                <!-- KPI Cards Personnel -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-primary-light"><?= $total_personnel ?></p>
                                <p class="text-sm font-medium text-gray-600 mt-1">Total personnel</p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-users text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.1s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-primary-light"><?= $total_secretaires ?></p>
                                <p class="text-sm font-medium text-gray-600 mt-1">Secrétaires</p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-user-tie text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.2s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-primary-light"><?= $total_communication ?></p>
                                <p class="text-sm font-medium text-gray-600 mt-1">Chargés de communication</p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-bullhorn text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.3s">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-primary-light"><?= $total_scolarite ?></p>
                                <p class="text-sm font-medium text-gray-600 mt-1">Responsables scolarité</p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-user-cog text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions et filtres pour personnel -->
                <div class="bg-white rounded-2xl shadow-lg mb-8 animate-fade-in">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
                            <!-- Recherche et filtres -->
                            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 flex-1">
                                <div class="relative flex-1 max-w-md">
                                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <input type="text" placeholder="Rechercher un membre du personnel..." 
                                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                
                                <select name="poste" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Poste occupé</option>
                                    <?php foreach ($groupes as $poste): ?>
                                        <option value="<?= htmlspecialchars($poste['lib_gu'] ?? '') ?>">
                                            <?= htmlspecialchars($poste['lib_gu'] ?? '') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                
                                <select class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Genre</option>
                                    <?php
                                    $genres = $pdo->query("SELECT DISTINCT sexe_personnel_adm FROM personnel_administratif WHERE sexe_personnel_adm IS NOT NULL ORDER BY sexe_personnel_adm")->fetchAll(PDO::FETCH_COLUMN);
                                    foreach ($genres as $genre) {
                                        echo '<option value="' . htmlspecialchars($genre ?? '') . '">' . htmlspecialchars($genre ?? '') . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-wrap gap-3">
                                <button class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors flex items-center bulk-delete-btn" id="bulk-delete-btn">
                                    <i class="fas fa-trash mr-2"></i> Supprimer la sélection
                                </button>
                                <button class="px-4 py-2 bg-secondary text-white rounded-lg hover:bg-orange-600 transition-colors flex items-center bulk-export-btn" id="bulk-export-btn">
                                    <i class="fas fa-file mr-2"></i> Exporter
                                </button>
                                <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center" id="add_personnel">
                                    <i class="fas fa-plus mr-2"></i> Ajouter un membre du personnel
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table du personnel -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="select-all-personnel" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matricule</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom complet</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Poste</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Genre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'embauche</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($personnel as $pers): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" class="personnel-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                   value="<?= $pers['id_personnel_adm'] ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?= $pers['id_personnel_adm'] ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                                    <span class="text-primary font-medium">
                                                        <?= strtoupper(substr($pers['nom_personnel_adm'], 0, 1)) ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?= htmlspecialchars($pers['nom_personnel_adm'] . ' ' . $pers['prenoms_personnel_adm']) ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?= htmlspecialchars($pers['email_personnel_adm'] ?? '') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($pers['poste'] ?? 'Non défini') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($pers['sexe_personnel_adm'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= htmlspecialchars($pers['tel_personnel_adm'] ?? '') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?= $pers['date_embauche'] ? date('d/m/Y', strtotime($pers['date_embauche'])) : 'Non renseignée' ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors edit-personnel" 
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-colors delete-personnel" 
                                                        title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination personnel -->
                    <?php if ($nb_pages_personnel > 1): ?>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="flex items-center justify-center space-x-2">
                                <?php if ($page_courante_pers > 1): ?>
                                    <a href="?page=ressources_humaines&page_pers=<?= $page_courante_pers - 1 ?>&tab=personnel#personnel" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $nb_pages_personnel; $i++): ?>
                                    <a href="?page=ressources_humaines&page_pers=<?= $i ?>&tab=personnel#personnel" 
                                       class="px-3 py-2 text-sm font-medium <?= $i === $page_courante_pers ? 'bg-primary text-white' : 'text-gray-500 bg-white hover:bg-gray-50' ?> border border-gray-300 rounded-md">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page_courante_pers < $nb_pages_personnel): ?>
                                    <a href="?page=ressources_humaines&page_pers=<?= $page_courante_pers + 1 ?>&tab=personnel#personnel" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Modal Ajouter un Enseignant -->
    <div class="fixed inset-0 z-50 overflow-y-auto hidden" id="add-enseignant-modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form action="../public/assets/traitements/enregistrer_enseignant.php" method="post" id="ens-form">
                    <div class="bg-white px-6 pt-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <div class="bg-primary/10 rounded-lg p-2 mr-3">
                                    <i class="fas fa-chalkboard-teacher text-primary"></i>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900">Ajouter un Enseignant</h2>
                            </div>
                            <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" id="close-modal-enseignant-btn">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 pb-6 space-y-6">
                        <!-- Informations générales -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations générales</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                                    <input type="text" id="nom_ens" name="nom_ens" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prénoms *</label>
                                    <input type="text" id="prenoms_ens" name="prenoms_ens" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                    <input type="email" id="email_ens" name="email_ens" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sexe *</label>
                                    <div class="flex space-x-4 mt-2">
                                        <label class="flex items-center">
                                            <input type="radio" name="sexe_ens" value="Homme" class="mr-2 text-primary focus:ring-primary">
                                            Homme
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="sexe_ens" value="Femme" class="mr-2 text-primary focus:ring-primary">
                                            Femme
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Carrière -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Carrière</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Grade *</label>
                                    <select id="grade" name="grade" required 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="">Sélectionner un grade</option>
                                        <?php
                                        $grades_list = $pdo->query("SELECT nom_grd FROM grade")->fetchAll();
                                        foreach ($grades_list as $grd) {
                                            echo "<option value=\"" . htmlspecialchars($grd['nom_grd'] ?? '') . "\">" . htmlspecialchars($grd['nom_grd'] ?? '') . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Spécialité *</label>
                                    <select id="specialite" name="specialite" required 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="">Sélectionner une spécialité</option>
                                        <?php
                                        $specialites_list = $pdo->query("SELECT lib_spe FROM specialite")->fetchAll();
                                        foreach ($specialites_list as $sp) {
                                            echo "<option value=\"" . htmlspecialchars($sp['lib_spe'] ?? '') . "\">" . htmlspecialchars($sp['lib_spe'] ?? '') . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'entrée en fonction *</label>
                                    <input type="date" id="date_entree" name="date_entree" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fonction *</label>
                                    <select id="fonction" name="fonction" required 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="">Sélectionner une fonction</option>
                                        <?php
                                        $fonctions_list = $pdo->query("SELECT nom_fonction FROM fonction")->fetchAll();
                                        foreach ($fonctions_list as $fonc) {
                                            echo "<option value=\"" . htmlspecialchars($fonc['nom_fonction'] ?? '') . "\">" . htmlspecialchars($fonc['nom_fonction'] ?? '') . "</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <button type="button" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors" id="close-modal-enseignant-btn">
                                Annuler
                            </button>
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter un membre du personnel -->
    <div class="fixed inset-0 z-50 overflow-y-auto hidden" id="add-personnel-modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <form action="../public/assets/traitements/enregistrer_personnel.php" method="post" id="pers-form">
                    <div class="bg-white px-6 pt-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <div class="bg-primary/10 rounded-lg p-2 mr-3">
                                    <i class="fas fa-user-tie text-primary"></i>
                                </div>
                                <h2 class="text-2xl font-bold text-gray-900">Ajouter un membre du personnel</h2>
                            </div>
                            <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors" id="close-modal-personnel-btn">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 pb-6 space-y-6">
                        <!-- Informations générales -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations générales</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                                    <input type="text" id="nom_personnel" name="nom_personnel" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Prénoms *</label>
                                    <input type="text" id="prenoms_personnel" name="prenoms_personnel" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
                                    <input type="email" id="email_personnel" name="email_personnel" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sexe *</label>
                                    <div class="flex space-x-4 mt-2">
                                        <label class="flex items-center">
                                            <input type="radio" name="sexe_personnel" value="Homme" class="mr-2 text-primary focus:ring-primary">
                                            Homme
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="sexe_personnel" value="Femme" class="mr-2 text-primary focus:ring-primary">
                                            Femme
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Détails professionnels -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails professionnels</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Poste *</label>
                                    <select name="poste" id="poste" required 
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="">Sélectionner un poste</option>
                                        <?php foreach ($groupes as $groupe): ?>
                                            <option value="<?= $groupe['id_gu'] ?>">
                                                <?= htmlspecialchars($groupe['lib_gu'] ?? '') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date d'embauche *</label>
                                    <input type="date" id="date_embauche" name="date_embauche" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
                                    <input type="tel" id="telephone" name="telephone" required 
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <button type="button" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors" id="close-modal-personnel-btn">
                                Annuler
                            </button>
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                Enregistrer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation générique -->
    <div class="fixed inset-0 z-50 overflow-y-auto hidden" id="confirmation-modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="bg-red-100 rounded-lg p-2 mr-3">
                                <i class="fas fa-question-circle text-red-600"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">Confirmation</h2>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600 transition-colors" id="close-confirmation-modal-btn">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="mb-6">
                        <p id="confirmation-text" class="text-gray-600">Voulez-vous vraiment effectuer cette action ?</p>
                    </div>
                    
                    <div class="flex justify-end space-x-4">
                        <button class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors" id="cancel-modal-btn">
                            Non
                        </button>
                        <button class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors" id="confirm-modal-btn">
                            Oui
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gestion des onglets
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        // Fonction pour activer un onglet
        function activateTab(tabId) {
            // Décocher toutes les cases à cocher
            uncheckAllCheckboxes();

            // Mise à jour des boutons
            tabButtons.forEach(btn => {
                if (btn.dataset.tab === tabId) {
                    btn.classList.add('border-primary', 'text-primary');
                    btn.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                } else {
                    btn.classList.remove('border-primary', 'text-primary');
                    btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                }
            });

            // Mise à jour du contenu
            tabContents.forEach(content => {
                if (content.id === tabId) {
                    content.classList.remove('hidden');
                    content.classList.add('active');
                } else {
                    content.classList.add('hidden');
                    content.classList.remove('active');
                }
            });
        }

        // Gestion des clics sur les onglets
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tabId = button.getAttribute('data-tab');
                activateTab(tabId);
            });
        });

        // Activer l'onglet approprié au chargement de la page
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab && (tab === 'enseignants' || tab === 'personnel')) {
                activateTab(tab);
            }
        });

        function uncheckAllCheckboxes() {
            const allCheckboxes = document.querySelectorAll('tbody input[type="checkbox"]');
            allCheckboxes.forEach(cb => cb.checked = false);
        }

        // === FONCTIONS D'EXPORTATION ===

        // Fonction principale d'exportation
        function exportData() {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 overflow-y-auto';
            modal.innerHTML = `
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-6 pt-6 pb-6">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <div class="bg-secondary/10 rounded-lg p-2 mr-3">
                                        <i class="fas fa-file-export text-secondary"></i>
                                    </div>
                                    <h2 class="text-xl font-bold text-gray-900">Export des données RH</h2>
                                </div>
                                <button class="text-gray-400 hover:text-gray-600 transition-colors" onclick="this.closest('.fixed').remove()">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            <div class="mb-6">
                                <p class="text-gray-600 mb-4">Choisissez le format d'export pour les données actuellement affichées :</p>
                                <div class="space-y-3">
                                    <button class="w-full px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center" onclick="exportToCSV(); this.closest('.fixed').remove();">
                                        <i class="fas fa-file-csv mr-3"></i>
                                        <div class="text-left">
                                            <div class="font-medium">Export Excel/CSV</div>
                                            <div class="text-sm text-white/80">Format tableur compatible Excel</div>
                                        </div>
                                    </button>
                                    <button class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center" onclick="exportToPDF(); this.closest('.fixed').remove();">
                                        <i class="fas fa-file-pdf mr-3"></i>
                                        <div class="text-left">
                                            <div class="font-medium">Export PDF</div>
                                            <div class="text-sm text-white/80">Format document imprimable</div>
                                        </div>
                                    </button>
                                </div>
                                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-600"><strong>Note :</strong> L'export inclura uniquement les données visibles dans l'onglet actif.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Export CSV/Excel
        function exportToCSV() {
            const activeTab = document.querySelector('.tab-content:not(.hidden)');
            const table = activeTab.querySelector('table');
            let csv = [];

            // En-têtes du tableau
            const headers = Array.from(table.querySelectorAll('thead th')).slice(1, -1); // Exclure checkbox et actions
            csv.push(headers.map(th => th.textContent.trim().replace(/\r?\n|\r/g, ' ')).join(';'));

            // Données visibles uniquement
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cells = Array.from(row.querySelectorAll('td')).slice(1, -1); // Exclure checkbox et actions
                    const rowData = cells.map(td => {
                        let text = td.textContent.trim().replace(/\s+/g, ' ');
                        text = text.replace(/\r?\n|\r/g, ' '); // Supprimer les retours à la ligne
                        text = text.replace(/"/g, '""');
                        return '"' + text + '"';
                    });
                    csv.push(rowData.join(';'));
                }
            });

            // Télécharger
            const csvContent = csv.join('\n');
            const blob = new Blob(['\ufeff' + csvContent], {
                type: 'text/csv;charset=utf-8;'
            });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `rh_${activeTab.id}_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showFeedback('Export CSV terminé avec succès', 'success');
        }

        // Export PDF
        function exportToPDF() {
            const activeTab = document.querySelector('.tab-content:not(.hidden)');
            const sectionName = activeTab.id === 'enseignants' ? 'Enseignants' : 'Personnel Administratif';
            
            // Préparer le tableau sans les colonnes checkbox et actions
            function getTableWithoutCheckboxAndActions(table) {
                const clone = table.cloneNode(true);
                // Supprimer la colonne checkbox et actions dans thead
                const ths = clone.querySelectorAll('thead th');
                if (ths.length > 2) {
                    ths[0].remove(); // checkbox
                    ths[ths.length - 1].remove(); // actions
                }
                // Supprimer la colonne checkbox et actions dans tbody
                clone.querySelectorAll('tbody tr').forEach(tr => {
                    if (tr.children.length > 2) {
                        tr.children[0].remove();
                        tr.children[tr.children.length - 1].remove();
                    }
                });
                return clone.outerHTML;
            }
            
            const table = activeTab.querySelector('table');
            const tableHtml = getTableWithoutCheckboxAndActions(table);
            
            // Créer une nouvelle fenêtre pour l'impression
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Export RH - ${sectionName}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #1a5276; padding-bottom: 10px; }
                        .header h1 { color: #1a5276; margin: 0; }
                        .header p { color: #666; margin: 5px 0; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; font-weight: bold; }
                        .stats { margin-bottom: 20px; }
                        .stats h3 { color: #1a5276; }
                        @media print {
                            body { margin: 0; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Ressources Humaines - ${sectionName}</h1>
                        <p>Université Félix Houphouët-Boigny</p>
                        <p>Date d'export: ${new Date().toLocaleDateString('fr-FR')}</p>
                    </div>
                    <h3>Liste des ${sectionName.toLowerCase()}</h3>
                    ${tableHtml}
                    <div style="margin-top: 30px; text-align: center; color: #666; font-size: 12px;">
                        Document généré automatiquement par le système GSCV+
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            
            // Attendre que le contenu soit chargé puis imprimer
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
            
            showFeedback('Export PDF terminé avec succès', 'success');
        }

        // Messages de feedback
        function showFeedback(message, type = 'info') {
            const feedback = document.createElement('div');
            feedback.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg animate-slide-in-left`;
            
            const colors = {
                'success': 'bg-green-100 border border-green-400 text-green-700',
                'error': 'bg-red-100 border border-red-400 text-red-700',
                'info': 'bg-blue-100 border border-blue-400 text-blue-700'
            };
            
            const icons = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'info': 'fa-info-circle'
            };
            
            feedback.className += ` ${colors[type]}`;
            feedback.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${icons[type]} mr-3"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(feedback);

            setTimeout(() => {
                feedback.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (feedback.parentNode) {
                        document.body.removeChild(feedback);
                    }
                }, 300);
            }, 4000);
        }

        // --- GESTION DES MODALES ---

        // Fonction pour préparer la modale en mode AJOUT
        function preparerModaleAjout(modalId, titre, texteBouton) {
            const modal = document.getElementById(modalId);
            if (!modal) return;

            const form = modal.querySelector('form');
            if (form) form.reset();

            const champCache = form.querySelector('input[name="id_enseignant"], input[name="id_personnel"]');
            if (champCache) champCache.remove();

            modal.querySelector('h2').textContent = titre;
            modal.querySelector('button[type="submit"]').textContent = texteBouton;

            modal.classList.remove('hidden');
        }

        // Événements pour les boutons "Ajouter"
        document.getElementById('add_enseignant').addEventListener('click', (e) => {
            e.preventDefault();
            preparerModaleAjout('add-enseignant-modal', 'Ajouter un Enseignant', 'Enregistrer');
        });

        document.getElementById('add_personnel').addEventListener('click', (e) => {
            e.preventDefault();
            preparerModaleAjout('add-personnel-modal', 'Ajouter un membre du personnel', 'Enregistrer');
        });

        // Fermeture des modales
        document.querySelectorAll('[id^="close-modal"]').forEach(button => {
            button.addEventListener('click', () => {
                button.closest('.fixed').classList.add('hidden');
            });
        });

        // Fermer en cliquant à l'extérieur
        document.addEventListener('click', (event) => {
            if (event.target.classList.contains('fixed') && event.target.classList.contains('inset-0')) {
                event.target.classList.add('hidden');
            }
        });

        // ➤ MODIFIER ENSEIGNANT
        document.querySelectorAll('.edit-enseignant').forEach(button => {
            button.addEventListener('click', () => {
                try {
                    // Récupérer les données
                    const id = button.dataset.id;
                    const nom = button.dataset.nom;
                    const prenoms = button.dataset.prenoms;
                    const email = button.dataset.email;
                    const grade = button.dataset.grade;
                    const specialite = button.dataset.specialite;
                    const fonction = button.dataset.fonction;
                    const dateEntree = button.dataset.dateEntree;

                    // Remplir les champs du formulaire
                    const nomField = document.getElementById('nom_ens');
                    const prenomsField = document.getElementById('prenoms_ens');
                    const emailField = document.getElementById('email_ens');
                    const dateField = document.getElementById('date_entree');

                    if (nomField) nomField.value = nom || '';
                    if (prenomsField) prenomsField.value = prenoms || '';
                    if (emailField) emailField.value = email || '';
                    if (dateField) dateField.value = dateEntree || '';

                    // Sélectionner le bon grade
                    const gradeSelect = document.getElementById('grade');
                    if (gradeSelect && grade) {
                        Array.from(gradeSelect.options).forEach(opt => {
                            opt.selected = opt.textContent.trim() === grade;
                        });
                    }

                    // Sélectionner la bonne spécialité
                    const speSelect = document.getElementById('specialite');
                    if (speSelect && specialite) {
                        Array.from(speSelect.options).forEach(opt => {
                            opt.selected = opt.textContent.trim() === specialite;
                        });
                    }

                    // Sélectionner la bonne fonction
                    const fctSelect = document.getElementById('fonction');
                    if (fctSelect && fonction) {
                        Array.from(fctSelect.options).forEach(opt => {
                            opt.selected = opt.textContent.trim() === fonction;
                        });
                    }

                    // Ajout d'un champ caché pour savoir qu'on modifie
                    let hidden = document.getElementById('id_enseignant');
                    if (!hidden) {
                        hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'id_enseignant';
                        hidden.id = 'id_enseignant';
                        const form = document.getElementById('ens-form');
                        if (form) form.appendChild(hidden);
                    }
                    if (hidden) hidden.value = id;

                    // Changer le titre et le bouton pour le mode "Modifier"
                    const modal = document.getElementById('add-enseignant-modal');
                    modal.querySelector('h2').textContent = 'Modifier l\'enseignant';
                    modal.querySelector('button[type="submit"]').textContent = 'Enregistrer les modifications';

                    // Ouvrir la modale
                    if (modal) modal.classList.remove('hidden');
                } catch (error) {
                    console.error('Erreur lors de l\'ouverture de la modale de modification:', error);
                    alert('Erreur lors de l\'ouverture de la modale de modification');
                }
            });
        });

        // ➤ MODIFIER PERSONNEL ADMINISTRATIF
        document.querySelectorAll('.edit-personnel').forEach(button => {
            button.addEventListener('click', () => {
                const row = button.closest('tr');
                const id = row.querySelector('input[type="checkbox"]').value;
                const nomComplet = row.querySelector('.text-sm.font-medium').textContent.trim();
                const email = row.querySelector('.text-sm.text-gray-500').textContent.trim();
                const tel = row.children[5].textContent.trim();
                const poste = row.children[3].textContent.trim();
                const genre = row.children[4].textContent.trim();
                const date_embauche = row.children[6].textContent.trim();
                const [nom, ...prenomsArr] = nomComplet.split(' ');
                const prenoms = prenomsArr.join(' ');
                const parts = date_embauche.split('/');
                const dateISO = parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : '';

                document.getElementById('nom_personnel').value = nom;
                document.getElementById('prenoms_personnel').value = prenoms;
                document.getElementById('email_personnel').value = email;
                document.getElementById('telephone').value = tel;
                document.getElementById('date_embauche').value = dateISO;

                const posteSelect = document.getElementById('poste');
                Array.from(posteSelect.options).forEach(option => {
                    option.selected = option.textContent.trim() === poste;
                });

                let hidden = document.getElementById('id_personnel');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'id_personnel';
                    hidden.id = 'id_personnel';
                    document.getElementById('pers-form').appendChild(hidden);
                }
                hidden.value = id;

                // Changer le titre et le bouton pour le mode "Modifier"
                const modal = document.getElementById('add-personnel-modal');
                modal.querySelector('h2').textContent = 'Modifier le membre du personnel';
                modal.querySelector('button[type="submit"]').textContent = 'Enregistrer les modifications';

                document.getElementById('add-personnel-modal').classList.remove('hidden');
            });
        });

        // ➤ SUPPRESSION INDIVIDUELLE
        // Sélection des éléments
        const deleteEnseignantButtons = document.querySelectorAll('.delete-enseignant');
        const deletePersonnelButtons = document.querySelectorAll('.delete-personnel');

        // Suppression individuelle - Enseignant
        deleteEnseignantButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const row = btn.closest('tr');
                const id = row.querySelector('input[type="checkbox"]').value;
                const nom = row.querySelector('.text-sm.font-medium').textContent.trim();

                openConfirmationModal(`Voulez-vous vraiment supprimer l'enseignant "${nom}" ?`, function() {
                    fetch('../public/assets/traitements/supprimer_enseignant.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'ids=' + id
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showFeedback('Enseignant supprimé avec succès', 'success');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                showFeedback('Erreur lors de la suppression: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            showFeedback('Erreur lors de la suppression: ' + error, 'error');
                        });
                });
            });
        });

        // Suppression individuelle - Personnel
        deletePersonnelButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const row = btn.closest('tr');
                const id = row.querySelector('input[type="checkbox"]').value;
                const nom = row.querySelector('.text-sm.font-medium').textContent.trim();

                openConfirmationModal(`Voulez-vous vraiment supprimer le membre du personnel "${nom}" ?`, function() {
                    fetch('../public/assets/traitements/supprimer_personnel.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'ids=' + id
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showFeedback('Membre du personnel supprimé avec succès', 'success');
                                setTimeout(() => window.location.reload(), 1500);
                            } else {
                                showFeedback('Erreur lors de la suppression: ' + data.message, 'error');
                            }
                        })
                        .catch(error => {
                            showFeedback('Erreur lors de la suppression: ' + error, 'error');
                        });
                });
            });
        });

        // Modale de confirmation générique
        let confirmCallback = null;

        function openConfirmationModal(message, onConfirm) {
            const modal = document.getElementById('confirmation-modal');
            const text = document.getElementById('confirmation-text');

            if (!modal || !text) return;

            text.textContent = message;
            modal.classList.remove('hidden');
            confirmCallback = onConfirm;
        }

        function closeConfirmationModal() {
            const modal = document.getElementById('confirmation-modal');
            if (modal) {
                modal.classList.add('hidden');
            }
            confirmCallback = null;
        }

        // Gestionnaires pour les boutons de la modale
        document.addEventListener('DOMContentLoaded', function() {
            const confirmBtn = document.getElementById('confirm-modal-btn');
            const cancelBtn = document.getElementById('cancel-modal-btn');
            const closeBtn = document.getElementById('close-confirmation-modal-btn');

            if (confirmBtn) {
                confirmBtn.onclick = function() {
                    if (typeof confirmCallback === 'function') {
                        confirmCallback();
                    }
                    closeConfirmationModal();
                };
            }

            if (cancelBtn) {
                cancelBtn.onclick = function() {
                    closeConfirmationModal();
                };
            }

            if (closeBtn) {
                closeBtn.onclick = function() {
                    closeConfirmationModal();
                };
            }

            // Pour enseignants
            const selectAllEnseignants = document.getElementById('select-all-enseignants');
            if (selectAllEnseignants) {
                selectAllEnseignants.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('#enseignants tbody input[type="checkbox"]');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            }

            // Pour personnel
            const selectAllPersonnel = document.getElementById('select-all-personnel');
            if (selectAllPersonnel) {
                selectAllPersonnel.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('#personnel tbody input[type="checkbox"]');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            }

            // Lier le bouton d'export à la fonction exportData
            document.querySelectorAll('.bulk-export-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    exportData();
                });
            });

            // Logique de suppression pour les deux tableaux
            function setupBulkDelete(tabId, checkboxClass, idType, endpoint) {
                const tab = document.getElementById(tabId);
                const selectAll = tab.querySelector('thead input[type="checkbox"]');
                const bulkDeleteBtn = tab.querySelector('.bulk-delete-btn');

                selectAll.addEventListener('change', function() {
                    tab.querySelectorAll(`.${checkboxClass}`).forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });

                bulkDeleteBtn.addEventListener('click', function() {
                    const checkedBoxes = tab.querySelectorAll(`.${checkboxClass}:checked`);
                    const ids = Array.from(checkedBoxes).map(cb => cb.value);

                    if (ids.length === 0) {
                        showFeedback('Veuillez sélectionner au moins un élément à supprimer.', 'error');
                        return;
                    }

                    const typeName = tabId === 'enseignants' ? 'enseignant(s)' : 'membre(s) du personnel';
                    openConfirmationModal(`ATTENTION : Vous allez supprimer ${ids.length} ${typeName} et toutes leurs données associées. Cette action est irréversible. Confirmez-vous ?`, function() {
                        fetch(endpoint, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'ids=' + ids.join(',')
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showFeedback(`${ids.length} élément(s) supprimé(s) avec succès`, 'success');
                                    setTimeout(() => window.location.reload(), 1500);
                                } else {
                                    showFeedback('Erreur lors de la suppression: ' + (data.message || data.error), 'error');
                                }
                            })
                            .catch(error => {
                                showFeedback('Erreur lors de la suppression: ' + error, 'error');
                            });
                    });
                });
            }

            // Initialiser pour les enseignants
            setupBulkDelete('enseignants', 'enseignant-checkbox', 'enseignant_ids', '../public/assets/traitements/supprimer_enseignant.php');

            // Initialiser pour le personnel administratif
            setupBulkDelete('personnel', 'personnel-checkbox', 'personnel_ids', '../public/assets/traitements/supprimer_personnel.php');
        });
    </script>
</body>
</html>