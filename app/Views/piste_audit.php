<?php
// === SECTION PHP CORRIGÉE ===

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Controllers/PisteAuditController.php';

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['user_fullname'] ?? 'Utilisateur';
$lib_user_type = $_SESSION['lib_user_type'] ?? 'Utilisateur';

// Initialisation du contrôleur
$auditController = new PisteAuditController($pdo);

// === GESTION DES PARAMÈTRES DE FILTRAGE ET PAGINATION ===
$page = isset($_GET['num']) && is_numeric($_GET['num']) && $_GET['num'] > 0 ? (int)$_GET['num'] : 1;
$limit = isset($_GET['limit']) && in_array($_GET['limit'], [10, 25, 50, 100]) ? (int)$_GET['limit'] : 10;

// Paramètres de filtrage avec valeurs par défaut
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$date_debut = isset($_GET['date_debut']) && !empty($_GET['date_debut']) ? $_GET['date_debut'] : date('Y-m-d', strtotime('-7 days'));
$date_fin = isset($_GET['date_fin']) && !empty($_GET['date_fin']) ? $_GET['date_fin'] : date('Y-m-d');
$type_action = isset($_GET['type_action']) ? $_GET['type_action'] : '';
$type_utilisateur = isset($_GET['type_utilisateur']) ? $_GET['type_utilisateur'] : '';
$module_filtre = isset($_GET['module']) ? $_GET['module'] : '';

// Construction des filtres
$filters = [
    'date_debut' => $date_debut,
    'date_fin' => $date_fin,
    'type_action' => $type_action,
    'type_utilisateur' => $type_utilisateur,
    'module' => $module_filtre
];

// Récupération des données via le contrôleur
$audit_records = $auditController->getAuditRecordsWithPagination($page, $limit, $filters);
$stats = $auditController->getAuditStatistics();

// Récupération des options pour les filtres
$available_actions = $auditController->getAvailableActions();
$available_modules = $auditController->getAvailableModules();
$types_utilisateurs = $auditController->getAvailableUserTypes();

// Calcul des pourcentages d'évolution
$evolution_actions = $stats['actions_hier'] > 0 ?
    round((($stats['actions_aujourdhui'] - $stats['actions_hier']) / $stats['actions_hier']) * 100, 1) : 0;

$evolution_connexions = $stats['connexions_hier'] > 0 ?
    round((($stats['connexions_aujourdhui'] - $stats['connexions_hier']) / $stats['connexions_hier']) * 100, 1) : 0;

$evolution_echecs = $stats['echecs_hier'] > 0 ?
    round((($stats['echecs_aujourdhui'] - $stats['echecs_hier']) / $stats['echecs_hier']) * 100, 1) : 0;

// === PARAMÈTRES ACTUELS POUR LA PAGINATION ===
$current_params = [
    'search' => $search,
    'date_debut' => $date_debut,
    'date_fin' => $date_fin,
    'type_action' => $type_action,
    'type_utilisateur' => $type_utilisateur,
    'module' => $module_filtre,
    'limit' => $limit
];

// Calcul du total pour la pagination (approximatif basé sur les données actuelles)
$total_records = count($audit_records) * 10; // Approximation
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piste d'Audit - Système de Gestion</title>
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
                        'fade-in': 'fadeIn 0.6s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'bounce-in': 'bounceIn 0.8s ease-out',
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
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


            <!-- Cartes de statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 animate-slide-up">
                <!-- Actions aujourd'hui -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Actions aujourd'hui</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['actions_aujourdhui']; ?></p>
                                <div class="flex items-center mt-2">
                                    <div class="flex items-center text-xs <?php echo $evolution_actions >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <i class="fas fa-arrow-<?php echo $evolution_actions >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                                        <?php echo abs($evolution_actions); ?>%
                                    </div>
                                    <span class="text-xs text-gray-500 ml-1">vs hier</span>
                                </div>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-chart-line text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Connexions aujourd'hui -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Connexions aujourd'hui</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['connexions_aujourdhui']; ?></p>
                                <div class="flex items-center mt-2">
                                    <div class="flex items-center text-xs <?php echo $evolution_connexions >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <i class="fas fa-arrow-<?php echo $evolution_connexions >= 0 ? 'up' : 'down'; ?> mr-1"></i>
                                        <?php echo abs($evolution_connexions); ?>%
                                    </div>
                                    <span class="text-xs text-gray-500 ml-1">vs hier</span>
                                </div>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-sign-in-alt text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Échecs aujourd'hui -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-danger overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Échecs aujourd'hui</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['echecs_aujourdhui']; ?></p>
                                <div class="flex items-center mt-2">
                                    <div class="flex items-center text-xs <?php echo $evolution_echecs <= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <i class="fas fa-arrow-<?php echo $evolution_echecs <= 0 ? 'down' : 'up'; ?> mr-1"></i>
                                        <?php echo abs($evolution_echecs); ?>%
                                    </div>
                                    <span class="text-xs text-gray-500 ml-1">vs hier</span>
                                </div>
                            </div>
                            <div class="bg-danger/10 rounded-full p-4">
                                <i class="fas fa-times-circle text-2xl text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Utilisateurs actifs -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-secondary overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Utilisateurs actifs</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['utilisateurs_actifs']; ?></p>
                                <p class="text-xs text-gray-500 mt-2">cette semaine</p>
                            </div>
                            <div class="bg-secondary/10 rounded-full p-4">
                                <i class="fas fa-users text-2xl text-secondary"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation par onglets -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button class="tab-btn active py-4 px-1 border-b-2 border-primary text-primary font-medium text-sm whitespace-nowrap" 
                                data-tab="all" onclick="switchTab('all')">
                            <i class="fas fa-list mr-2"></i>
                            Toutes les actions
                        </button>
                        <button class="tab-btn py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm whitespace-nowrap" 
                                data-tab="connexions" onclick="switchTab('connexions')">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Journal des connexions
                        </button>
                        <button class="tab-btn py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm whitespace-nowrap" 
                                data-tab="operations" onclick="switchTab('operations')">
                            <i class="fas fa-cogs mr-2"></i>
                            Opérations système
                        </button>
                        <button class="tab-btn py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm whitespace-nowrap" 
                                data-tab="modifications" onclick="switchTab('modifications')">
                            <i class="fas fa-edit mr-2"></i>
                            Modifications
                        </button>
                        <button class="tab-btn py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm whitespace-nowrap" 
                                data-tab="erreurs" onclick="switchTab('erreurs')">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Erreurs système
                        </button>
                    </nav>
                </div>

                <!-- Panneau de filtrage -->
                <div class="p-6">
                    <form method="GET" id="filterForm" action="index_commission.php" class="space-y-6">
                        <input type="hidden" name="page" value="piste_audit">
                        
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <i class="fas fa-filter mr-2 text-primary"></i>
                                Filtres avancés
                            </h3>
                            <div class="flex space-x-3">
                                <button type="button" onclick="resetFilters()" 
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-refresh mr-2"></i>
                                    Réinitialiser
                                </button>
                                <button type="submit" 
                                        class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                    <i class="fas fa-filter mr-2"></i>
                                    Appliquer
                                </button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Période -->
                            <div>
                                <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                                <input type="date" id="date_debut" name="date_debut" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                       value="<?php echo htmlspecialchars($date_debut); ?>">
                            </div>
                            
                            <div>
                                <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                                <input type="date" id="date_fin" name="date_fin" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                       value="<?php echo htmlspecialchars($date_fin); ?>">
                            </div>
                            
                            <div>
                                <label for="limit" class="block text-sm font-medium text-gray-700 mb-2">Éléments par page</label>
                                <select id="limit" name="limit" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="25" <?php echo $limit == 25 ? 'selected' : ''; ?>>25</option>
                                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="type_action" class="block text-sm font-medium text-gray-700 mb-2">Type d'action</label>
                                <select id="type_action" name="type_action" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Toutes les actions</option>
                                    <?php foreach ($available_actions as $action): ?>
                                        <option value="<?php echo htmlspecialchars($action); ?>"
                                                <?php echo $type_action === $action ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($action); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="type_utilisateur" class="block text-sm font-medium text-gray-700 mb-2">Type d'utilisateur</label>
                                <select id="type_utilisateur" name="type_utilisateur" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($types_utilisateurs as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>"
                                                <?php echo $type_utilisateur === $type ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="module" class="block text-sm font-medium text-gray-700 mb-2">Module</label>
                                <select id="module" name="module" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Tous les modules</option>
                                    <?php foreach ($available_modules as $module): ?>
                                        <option value="<?php echo htmlspecialchars($module['lib_traitement']); ?>"
                                                <?php echo $module_filtre === $module['lib_traitement'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($module['nom_traitement']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <div class="text-sm text-gray-600">
                                Date du jour: <span class="font-medium"><?php echo date('d/m/Y'); ?></span>
                            </div>
                            <div class="flex space-x-3">
                                <button type="button" onclick="exportData()" 
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-download mr-2"></i>
                                    Exporter
                                </button>
                                <button type="button" onclick="window.print()" 
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                    <i class="fas fa-print mr-2"></i>
                                    Imprimer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Section des résultats -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-history mr-2 text-primary"></i>
                            Historique d'audit
                        </h3>
                        <div class="flex items-center space-x-4">
                            <!-- Recherche en temps réel -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" name="search" placeholder="Rechercher utilisateur..." 
                                       class="pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       title="Recherche dans noms, prénoms, emails, actions et modules">
                            </div>
                            <div class="text-sm text-gray-600">
                                <?php echo number_format($total_records); ?> enregistrements - 
                                Page <?php echo $page; ?> sur <?php echo $total_pages; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Utilisateur
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Module
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Heure
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
                            <?php if (empty($audit_records)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-search text-4xl text-gray-400 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun enregistrement trouvé</h3>
                                            <p class="text-gray-500 mb-4">Essayez de modifier vos critères de recherche ou d'élargir la période.</p>
                                            <button onclick="resetFilters()" 
                                                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                                <i class="fas fa-refresh mr-2"></i>
                                                Réinitialiser les filtres
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($audit_records as $record): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                                    <span class="text-sm font-medium text-primary">
                                                        <?php echo substr($record['nom_utilisateur'], 0, 1) . substr($record['prenoms_utilisateur'] ?? '', 0, 1); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($record['nom_utilisateur']); ?>
                                                    </div>
                                                    <?php if (!empty($record['prenoms_utilisateur'])): ?>
                                                        <div class="text-sm text-gray-500">
                                                            <?php echo htmlspecialchars($record['prenoms_utilisateur']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="text-xs text-gray-400">ID: <?php echo $record['id_utilisateur']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $typeColors = [
                                                'administrateur' => 'bg-red-100 text-red-800',
                                'enseignant' => 'bg-blue-100 text-blue-800',
                                'étudiant' => 'bg-green-100 text-green-800',
                                'personnel' => 'bg-yellow-100 text-yellow-800'
                                            ];
                                            $type = strtolower($record['type_utilisateur']);
                                            $colorClass = $typeColors[$type] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $colorClass; ?>">
                                                <?php echo htmlspecialchars($record['type_utilisateur']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($record['lib_action']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" 
                                            title="<?php echo htmlspecialchars($record['lib_traitement']); ?>">
                                            <?php echo htmlspecialchars($record['nom_traitement']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                                <?php echo date('d/m/Y', strtotime($record['date_piste'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <i class="fas fa-clock mr-2 text-gray-400"></i>
                                                <?php echo date('H:i:s', strtotime($record['heure_piste'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $record['acceder'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <i class="fas fa-<?php echo $record['acceder'] ? 'check' : 'times'; ?> mr-1"></i>
                                                <?php echo $record['acceder'] ? 'Succès' : 'Échec'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="showDetails(<?php echo htmlspecialchars(json_encode($record)); ?>)" 
                                                    class="text-primary hover:text-primary-light" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Affichage de <?php echo (($page - 1) * $limit + 1); ?> à 
                                <?php echo min($page * $limit, $total_records); ?> sur 
                                <?php echo number_format($total_records); ?> enregistrements
                            </div>
                            
                            <!-- Navigation rapide -->
                            <div class="flex items-center space-x-4">
                                <form method="GET" action="index_commission.php" class="flex items-center space-x-2">
                                    <input type="hidden" name="page" value="piste_audit">
                                    <?php foreach ($current_params as $key => $value): ?>
                                        <?php if ($key !== 'num' && !empty($value)): ?>
                                            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" 
                                                   value="<?php echo htmlspecialchars($value); ?>">
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <span class="text-sm text-gray-600">Page :</span>
                                    <input type="number" name="num" min="1" max="<?php echo $total_pages; ?>" 
                                           value="<?php echo $page; ?>" 
                                           class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                                    <button type="submit" 
                                            class="px-3 py-1 bg-primary text-white rounded text-sm hover:bg-primary-light transition-colors">
                                        OK
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de détails -->
    <div id="detailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 animate-bounce-in">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-info-circle text-primary mr-2"></i>
                        Détails de l'action utilisateur
                    </h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="modalBody" class="space-y-6">
                    <!-- Le contenu sera rempli dynamiquement -->
                </div>
                <div class="flex justify-end space-x-4 mt-6 pt-6 border-t border-gray-200">
                    <button onclick="closeModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Fermer
                    </button>
                    <button onclick="exportDetails()" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Exporter les détails
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentRecord = null;

        // Gestion des onglets
        function switchTab(tabType) {
            // Désactiver tous les onglets
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active', 'border-primary', 'text-primary');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Activer l'onglet sélectionné
            const activeBtn = document.querySelector(`[data-tab="${tabType}"]`);
            activeBtn.classList.add('active', 'border-primary', 'text-primary');
            activeBtn.classList.remove('border-transparent', 'text-gray-500');
            
            // Filtrer les données selon l'onglet
            filterByTab(tabType);
        }

        // Filtrage par onglet
        function filterByTab(tabType) {
            const rows = document.querySelectorAll('tbody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                if (row.querySelector('.text-4xl')) return; // Ignorer la ligne vide
                
                const action = row.cells[2]?.textContent.toLowerCase() || '';
                const statut = row.cells[6]?.textContent.toLowerCase() || '';
                
                let show = true;
                
                switch(tabType) {
                    case 'all':
                        show = true;
                        break;
                    case 'connexions':
                        show = action.includes('connexion') || action.includes('déconnexion');
                        break;
                    case 'operations':
                        show = action.includes('ajout') || action.includes('modification') || 
                               action.includes('suppression') || action.includes('création') ||
                               action.includes('sauvegarde') || action.includes('export');
                        break;
                    case 'modifications':
                        show = action.includes('modification') || action.includes('mise à jour') ||
                               action.includes('changement');
                        break;
                    case 'erreurs':
                        show = statut.includes('échec') || action.includes('tentative') ||
                               action.includes('erreur');
                        break;
                }
                
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });
        }

        // Réinitialiser les filtres
        function resetFilters() {
            window.location.href = 'index_commission.php?page=piste_audit&date_debut=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&date_fin=<?php echo date('Y-m-d'); ?>';
        }

        // Auto-submit pour certains filtres
        document.getElementById('limit').addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });

        // Afficher les détails dans le modal
        function showDetails(record) {
            currentRecord = record;
            
            const modalBody = document.getElementById('modalBody');
            modalBody.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-info-circle text-primary mr-2"></i>
                            Informations générales
                        </h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Date :</span>
                                <span class="text-sm text-gray-900">${formatDate(record.date_piste)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Heure :</span>
                                <span class="text-sm text-gray-900">${record.heure_piste}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Statut :</span>
                                <span class="text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${record.acceder ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                        <i class="fas fa-${record.acceder ? 'check-circle' : 'times-circle'} mr-1"></i>
                                        ${record.acceder ? 'Succès' : 'Échec'}
                                    </span>
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Action :</span>
                                <span class="text-sm text-gray-900">${record.lib_action}</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900 flex items-center">
                            <i class="fas fa-user text-primary mr-2"></i>
                            Informations utilisateur
                        </h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">ID Utilisateur :</span>
                                <span class="text-sm text-gray-900">${record.id_utilisateur}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Nom complet :</span>
                                <span class="text-sm text-gray-900">${record.nom_utilisateur} ${record.prenoms_utilisateur || ''}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Type :</span>
                                <span class="text-sm text-gray-900">${record.type_utilisateur}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm font-medium text-gray-600">Email :</span>
                                <span class="text-sm text-gray-900">${record.email_utilisateur || 'Non renseigné'}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <h4 class="font-semibold text-gray-900 flex items-center mb-4">
                        <i class="fas fa-cogs text-primary mr-2"></i>
                        Informations système
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Module :</span>
                            <span class="text-sm text-gray-900">${record.nom_traitement}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Traitement :</span>
                            <span class="text-sm text-gray-900">${record.lib_traitement}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Résultat :</span>
                            <span class="text-sm text-gray-900">${record.acceder ? 'Opération réussie' : 'Opération échouée'}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm font-medium text-gray-600">Timestamp :</span>
                            <span class="text-sm text-gray-900">${record.date_piste} ${record.heure_piste}</span>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('detailsModal').classList.remove('hidden');
            document.getElementById('detailsModal').classList.add('flex');
        }

        // Fermer le modal
        function closeModal() {
            document.getElementById('detailsModal').classList.add('hidden');
            document.getElementById('detailsModal').classList.remove('flex');
            currentRecord = null;
        }

        // Export des données
        function exportData() {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-download text-primary mr-2"></i>
                            Export des données d'audit
                        </h3>
                        <p class="text-gray-600 mb-6">Choisissez le format d'export :</p>
                        <div class="space-y-3">
                            <button onclick="exportToCSV(); document.body.removeChild(this.closest('.fixed'))" 
                                    class="w-full px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center justify-center">
                                <i class="fas fa-file-csv mr-2"></i>
                                Export CSV
                            </button>
                            <button onclick="exportToJSON(); document.body.removeChild(this.closest('.fixed'))" 
                                    class="w-full px-4 py-3 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors flex items-center justify-center">
                                <i class="fas fa-file-code mr-2"></i>
                                Export JSON
                            </button>
                            <button onclick="window.print(); document.body.removeChild(this.closest('.fixed'))" 
                                    class="w-full px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
                                <i class="fas fa-print mr-2"></i>
                                Imprimer
                            </button>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <button onclick="document.body.removeChild(this.closest('.fixed'))" 
                                    class="w-full px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }

        // Export CSV
        function exportToCSV() {
            const table = document.querySelector('table');
            let csv = [];
            
            // En-têtes
            const headers = Array.from(table.querySelectorAll('thead th')).slice(0, -1);
            csv.push(headers.map(th => th.textContent.trim().replace(/\r?\n|\r/g, ' ')).join(';'));
            
            // Données visibles
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                if (row.style.display !== 'none' && !row.querySelector('.text-4xl')) {
                    const cells = Array.from(row.querySelectorAll('td')).slice(0, -1);
                    const rowData = cells.map(td => {
                        let text = td.textContent.trim().replace(/\s+/g, ' ');
                        text = text.replace(/\r?\n|\r/g, ' ');
                        text = text.replace(/"/g, '""');
                        return '"' + text + '"';
                    });
                    csv.push(rowData.join(';'));
                }
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `audit_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Export JSON
        function exportToJSON() {
            const visibleRows = Array.from(document.querySelectorAll('tbody tr'))
                .filter(row => row.style.display !== 'none' && !row.querySelector('.text-4xl'));
            
            const data = {
                export_info: {
                    date_export: new Date().toISOString(),
                    periode: {
                        debut: '<?php echo $date_debut; ?>',
                        fin: '<?php echo $date_fin; ?>'
                    },
                    filtres: {
                        type_action: '<?php echo $type_action; ?>',
                        type_utilisateur: '<?php echo $type_utilisateur; ?>',
                        module: '<?php echo $module_filtre; ?>',
                        recherche: '<?php echo $search; ?>'
                    },
                    total_records: visibleRows.length
                },
                records: visibleRows.map(row => {
                    const cells = row.querySelectorAll('td');
                    return {
                        utilisateur: cells[0]?.textContent.trim().split('\n')[0],
                        prenoms: cells[0]?.textContent.trim().split('\n')[1]?.trim(),
                        id_utilisateur: cells[0]?.textContent.match(/ID:\s*(\d+)/)?.[1],
                        type_utilisateur: cells[1]?.textContent.trim(),
                        action: cells[2]?.textContent.trim(),
                        module: cells[3]?.textContent.trim(),
                        date: cells[4]?.textContent.trim(),
                        heure: cells[5]?.textContent.trim(),
                        statut: cells[6]?.textContent.trim()
                    };
                })
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `audit_${new Date().toISOString().split('T')[0]}.json`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Export des détails
        function exportDetails() {
            if (!currentRecord) return;
            
            const data = {
                export_date: new Date().toISOString(),
                record: currentRecord
            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `audit_detail_${currentRecord.id_utilisateur}_${new Date().toISOString().split('T')[0]}.json`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Utilitaire pour formater les dates
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Animation au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observer les éléments animés
        document.querySelectorAll('.animate-slide-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            observer.observe(el);
        });

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Interface de piste d\'audit initialisée avec succès');
        });
    </script>

</body>
</html>