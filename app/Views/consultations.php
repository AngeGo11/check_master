<?php
// Vérification de sécurité
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_groups'])) {
    header('Location: ../pageConnection.php');
    exit;
}
// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');
// Initialisation du contrôleur
require_once '../app/Controllers/ConsultationController.php';

$controller = new ConsultationController();

// Récupération des données via le contrôleur
$data = $controller->viewConsultations();

// Extraction des variables pour la vue
$rapports = $data['rapports'];
$comptes_rendus = $data['comptes_rendus'];
$statistics = $data['statistics'];
$pagination_rapports = $data['pagination_rapports'];
$pagination_cr = $data['pagination_cr'];
$filters_rapports = $data['filters_rapports'];
$filters_cr = $data['filters_cr'];

$responsable_compte_rendu = $controller->getResponsableCompteRendu($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation des Rapports et Comptes Rendus - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
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
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(26, 82, 118, 0.1), 0 10px 10px -5px rgba(26, 82, 118, 0.04);
        }
        .template-alert {
            animation: slideInRight 0.3s ease;
        }
        .template-selector.template-loading {
            opacity: 0.7;
            pointer-events: none;
        }
        .template-selector.template-success {
            background: rgba(76, 175, 80, 0.1);
            border-color: #4caf50;
        }
        .template-select.template-selected {
            border-color: #2980b9;
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.1);
        }
        .template-description {
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .template-description.show {
            opacity: 1;
            max-height: 300px;
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">


        <!-- Contenu principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total comptes rendus -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-primary-light"><?php echo $statistics['total_cr']; ?></p>
                            <p class="text-sm font-medium text-gray-600 mt-1">Total comptes rendus</p>
                        </div>
                        <div class="bg-primary-light/10 rounded-full p-4">
                            <i class="fas fa-file-alt text-2xl text-primary-light"></i>
                        </div>
                    </div>
                </div>

                <!-- Comptes rendus validés -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.1s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-primary-light"><?php echo $statistics['cr_valides']; ?></p>
                            <p class="text-sm font-medium text-gray-600 mt-1">Comptes rendus validés</p>
                        </div>
                        <div class="bg-primary-light/10 rounded-full p-4">
                            <i class="fas fa-check-circle text-2xl text-primary-light"></i>
                        </div>
                    </div>
                </div>

                <!-- Comptes rendus en cours -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.2s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-primary-light"><?php echo $statistics['cr_en_cours']; ?></p>
                            <p class="text-sm font-medium text-gray-600 mt-1">Comptes rendus en cours</p>
                        </div>
                        <div class="bg-primary-light/10 rounded-full p-4">
                            <i class="fas fa-hourglass-half text-2xl text-primary-light"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Rapports -->
            <div class="bg-white rounded-2xl shadow-lg mb-8 animate-fade-in">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-primary/10 rounded-lg p-2 mr-3">
                                <i class="fas fa-file-text text-primary"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900">Consultation des Rapports</h2>
                        </div>
                    </div>
                </div>

                <!-- Filtres pour les rapports -->
                <div class="p-6 bg-gray-50 border-b border-gray-200">
                    <form method="GET" id="filter-form-rapports" class="flex flex-wrap items-center gap-4">
                        <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'consultations'; ?>">
                        <input type="hidden" name="page_num" id="page_num_input_rapports" value="<?php echo $pagination_rapports['current_page']; ?>">
                        
                        <!-- Recherche -->
                        <div class="flex-1 min-w-64">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="search" id="search-input-rapports" 
                                       placeholder="Rechercher un rapport..." 
                                       value="<?php echo htmlspecialchars($filters_rapports['search'] ?? ''); ?>"
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                        </div>
                        
                        <!-- Filtres -->
                        <select name="date_filter" id="date-filter-rapports" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Date de rapport</option>
                            <option value="today" <?php echo ($filters_rapports['date_filter'] ?? '') === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                            <option value="week" <?php echo ($filters_rapports['date_filter'] ?? '') === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                            <option value="month" <?php echo ($filters_rapports['date_filter'] ?? '') === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                            <option value="semester" <?php echo ($filters_rapports['date_filter'] ?? '') === 'semester' ? 'selected' : ''; ?>>Ce semestre</option>
                        </select>
                        
                        <select name="status_filter" id="status-filter-rapports" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Statut</option>
                            <option value="Validé" <?php echo ($filters_rapports['status_filter'] ?? '') === 'Validé' ? 'selected' : ''; ?>>Validé</option>
                            <option value="Rejeté" <?php echo ($filters_rapports['status_filter'] ?? '') === 'Rejeté' ? 'selected' : ''; ?>>Rejeté</option>
                            <option value="En attente de validation" <?php echo ($filters_rapports['status_filter'] ?? '') === 'En attente de validation' ? 'selected' : ''; ?>>En attente</option>
                        </select>
                        
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center">
                            <i class="fas fa-search mr-2"></i> Filtrer
                        </button>
                    </form>
                </div>

                <!-- Actions groupées -->
                <?php if ($responsable_compte_rendu > 0): ?>
                    <div class="p-6 bg-blue-50 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <button class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center" id="bulk-cr-btn">
                                <i class="fas fa-pen-nib mr-2"></i> Rédiger un compte rendu pour la sélection
                            </button>
                            <span class="text-sm text-gray-600 font-medium" id="selected-count">0 rapport(s) sélectionné(s)</span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Table des rapports -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <?php if ($responsable_compte_rendu > 0): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="select-all-rapports" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </th>
                                <?php endif; ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Rapport</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thème du mémoire</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de dépôt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($rapports)): ?>
                                <?php foreach ($rapports as $rapport): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <?php if ($responsable_compte_rendu > 0): ?>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" class="rapport-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                       value="<?php echo $rapport['id_rapport_etd']; ?>"
                                                       data-rapport-id="<?php echo $rapport['id_rapport_etd']; ?>"
                                                       data-num-etd="<?php echo $rapport['num_etd']; ?>"
                                                       data-nom-etd="<?php echo htmlspecialchars($rapport['nom_etd']); ?>"
                                                       data-prenom-etd="<?php echo htmlspecialchars($rapport['prenom_etd']); ?>"
                                                       data-nom-rapport="<?php echo htmlspecialchars($rapport['nom_rapport'] ?? ''); ?>"
                                                       data-theme-memoire="<?php echo htmlspecialchars($rapport['theme_memoire']); ?>"
                                                       data-date-depot="<?php echo $rapport['date_depot']; ?>"
                                                       data-statut="<?php echo $rapport['statut_rapport']; ?>">
                                            </td>
                                        <?php endif; ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo $rapport['id_rapport_etd']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($rapport['nom_etd'] . ' ' . $rapport['prenom_etd']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                            <?php echo htmlspecialchars($rapport['theme_memoire']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $rapport['date_depot'] ? date('d/m/Y', strtotime($rapport['date_depot'])) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $rapport['statut_rapport'] === 'Validé' ? 'bg-green-100 text-green-800' : 
                                                    ($rapport['statut_rapport'] === 'Rejeté' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                                <?php echo $rapport['statut_rapport']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <?php if ($rapport['fichier_rapport']): ?>
                                                    <a href="?page=consultations&action=download_rapport&id=<?php echo $rapport['id_rapport_etd']; ?>"
                                                       class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors" title="Télécharger">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?page=consultations&action=view_rapport&id=<?php echo $rapport['id_rapport_etd']; ?>"
                                                   class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors" title="Consulter">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($responsable_compte_rendu > 0): ?>
                                                    <button class="text-purple-600 hover:text-purple-900 p-2 rounded-lg hover:bg-purple-50 transition-colors create-cr-single"
                                                            data-rapport-id="<?php echo $rapport['id_rapport_etd']; ?>"
                                                            data-num-etd="<?php echo $rapport['num_etd']; ?>"
                                                            data-nom-etd="<?php echo htmlspecialchars($rapport['nom_etd']); ?>"
                                                            data-prenom-etd="<?php echo htmlspecialchars($rapport['prenom_etd']); ?>"
                                                            data-nom-rapport="<?php echo htmlspecialchars($rapport['nom_rapport'] ?? ''); ?>"
                                                            data-theme-memoire="<?php echo htmlspecialchars($rapport['theme_memoire']); ?>"
                                                            data-date-depot="<?php echo $rapport['date_depot']; ?>"
                                                            title="Rédiger un compte rendu">
                                                        <i class="fas fa-pen-nib"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $responsable_compte_rendu > 0 ? '7' : '6'; ?>" class="px-6 py-4 text-center text-gray-500">
                                        <div class="flex flex-col items-center py-8">
                                            <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-lg font-medium">Aucun rapport trouvé</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination pour les rapports -->
                <?php if ($pagination_rapports['total_pages'] > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-center space-x-2">
                            <?php if ($pagination_rapports['current_page'] > 1): ?>
                                <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 page-item" data-page="<?php echo $pagination_rapports['current_page'] - 1; ?>" data-form="rapports">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $pagination_rapports['current_page'] - 2);
                            $end_page = min($pagination_rapports['total_pages'], $pagination_rapports['current_page'] + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="#" class="px-3 py-2 text-sm font-medium <?php echo $i === $pagination_rapports['current_page'] ? 'bg-primary text-white' : 'text-gray-500 bg-white hover:bg-gray-50'; ?> border border-gray-300 rounded-md page-item" data-page="<?php echo $i; ?>" data-form="rapports">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($pagination_rapports['current_page'] < $pagination_rapports['total_pages']): ?>
                                <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 page-item" data-page="<?php echo $pagination_rapports['current_page'] + 1; ?>" data-form="rapports">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section Comptes Rendus -->
            <div class="bg-white rounded-2xl shadow-lg animate-fade-in">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-accent/10 rounded-lg p-2 mr-3">
                                <i class="fas fa-file-signature text-accent"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900">Consultation des Comptes Rendus</h2>
                        </div>
                    </div>
                </div>

                <!-- Filtres pour les comptes rendus -->
                <div class="p-6 bg-gray-50 border-b border-gray-200">
                    <form method="GET" id="filter-form-cr" class="flex flex-wrap items-center gap-4">
                        <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'consultations'; ?>">
                        <input type="hidden" name="page_cr" id="page_cr_input" value="<?php echo $pagination_cr['current_page']; ?>">
                        
                        <!-- Recherche -->
                        <div class="flex-1 min-w-64">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" name="search_cr" id="search-input-cr" 
                                       placeholder="Rechercher un compte rendu..." 
                                       value="<?php echo htmlspecialchars($filters_cr['search_cr'] ?? ''); ?>"
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                        </div>
                        
                        <!-- Filtres -->
                        <select name="date_filter_cr" id="date-filter-cr" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Date de compte rendu</option>
                            <option value="today" <?php echo ($filters_cr['date_filter_cr'] ?? '') === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                            <option value="week" <?php echo ($filters_cr['date_filter_cr'] ?? '') === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                            <option value="month" <?php echo ($filters_cr['date_filter_cr'] ?? '') === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                            <option value="semester" <?php echo ($filters_cr['date_filter_cr'] ?? '') === 'semester' ? 'selected' : ''; ?>>Ce semestre</option>
                        </select>
                        
                        <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center">
                            <i class="fas fa-search mr-2"></i> Filtrer
                        </button>
                    </form>
                </div>

                <!-- Table des comptes rendus -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Compte Rendu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enseignant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre du compte rendu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date du compte rendu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rapport associé</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut du rapport</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($comptes_rendus)): ?>
                                <?php foreach ($comptes_rendus as $cr): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo $cr['id_cr']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($cr['nom_etd'] . ' ' . $cr['prenom_etd']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($cr['nom_ens'] . ' ' . $cr['prenoms_ens']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                            <?php echo htmlspecialchars($cr['nom_cr']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo $cr['date_cr'] ? date('d/m/Y', strtotime($cr['date_cr'])) : '-'; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                                            <?php echo htmlspecialchars($cr['nom_rapport']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $cr['statut_rapport'] === 'Validé' ? 'bg-green-100 text-green-800' : 
                                                    ($cr['statut_rapport'] === 'Rejeté' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                                <?php echo $cr['statut_rapport']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="?page=consultations&action=view_cr&id=<?php echo $cr['id_cr']; ?>"
                                                   class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors" title="Consulter">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="?page=consultations&action=download_cr&id=<?php echo $cr['id_cr']; ?>"
                                                   class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors" title="Télécharger">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                        <div class="flex flex-col items-center py-8">
                                            <i class="fas fa-file-signature text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-lg font-medium">Aucun compte rendu trouvé</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination pour les comptes rendus -->
                <?php if ($pagination_cr['total_pages'] > 1): ?>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-center space-x-2">
                            <?php if ($pagination_cr['current_page'] > 1): ?>
                                <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 page-item" data-page="<?php echo $pagination_cr['current_page'] - 1; ?>" data-form="cr">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $pagination_cr['current_page'] - 2);
                            $end_page = min($pagination_cr['total_pages'], $pagination_cr['current_page'] + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <a href="#" class="px-3 py-2 text-sm font-medium <?php echo $i === $pagination_cr['current_page'] ? 'bg-primary text-white' : 'text-gray-500 bg-white hover:bg-gray-50'; ?> border border-gray-300 rounded-md page-item" data-page="<?php echo $i; ?>" data-form="cr">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($pagination_cr['current_page'] < $pagination_cr['total_pages']): ?>
                                <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 page-item" data-page="<?php echo $pagination_cr['current_page'] + 1; ?>" data-form="cr">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal de rédaction de compte rendu pour sélection multiple -->
    <div class="fixed inset-0 z-50 overflow-y-auto hidden" id="multi-cr-modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-6 pt-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="bg-primary/10 rounded-lg p-2 mr-3">
                                <i class="fas fa-pen-nib text-primary"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900">Rédiger un compte rendu</h2>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600 transition-colors" id="close-multi-cr-modal">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="px-6 pb-6">
                    <form id="multi-cr-form" method="POST" action="?page=consultations&action=create_multi_cr">
                        <!-- Rapports sélectionnés -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-list mr-2 text-primary"></i>
                                Rapports sélectionnés
                            </h3>
                            <div id="selected-rapports-list" class="space-y-3 max-h-64 overflow-y-auto">
                                <!-- Les rapports sélectionnés seront affichés ici -->
                            </div>
                        </div>

                        <!-- Section template -->
                        <div class="mb-8 p-6 bg-gray-50 rounded-xl">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-file-alt mr-2 text-primary"></i>
                                Informations du compte rendu
                            </h3>
                            
                            <!-- Sélection de template -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-magic mr-1"></i> 
                                    Modèle de compte rendu
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary text-white ml-2">2</span>
                                </label>
                                <div class="template-selector border border-gray-300 rounded-lg p-4">
                                    <div class="flex gap-4 mb-4">
                                        <select id="cr-template" name="cr_template" class="template-select flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">🎯 Sélectionner un modèle...</option>
                                            <option value="template_cr_html" data-icon="📄" data-type="html">📄 Modèle HTML - Procès-verbal de validation de thèmes</option>
                                            <option value="modele_compte_rendu_docx" data-icon="📋" data-type="docx">📋 Modèle DOCX - Compte rendu standard</option>
                                        </select>
                                        <button type="button" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center" id="load-template-btn">
                                            <i class="fas fa-magic mr-2"></i> Charger le modèle
                                        </button>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-info-circle mr-1"></i> 
                                        Choisissez entre le modèle HTML pour les procès-verbaux ou le modèle DOCX pour les comptes rendus standards
                                    </p>
                                    
                                    <!-- Description dynamique du template -->
                                    <div id="template-description" class="template-description mt-4 p-4 bg-white rounded-lg border border-gray-200">
                                        <h4 class="font-semibold text-gray-900 mb-2 flex items-center">
                                            <i class="fas fa-lightbulb mr-2 text-yellow-500"></i>
                                            À propos de ce modèle
                                        </h4>
                                        <p id="template-desc-text" class="text-gray-600 mb-2">Choisissez un modèle pour voir sa description détaillée.</p>
                                        <ul id="template-features" class="space-y-1 text-sm text-gray-600" style="display: none;">
                                            <!-- Les fonctionnalités seront ajoutées dynamiquement -->
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Titre -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Titre du compte rendu <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="cr-title" name="cr_title" required 
                                       placeholder="Ex: Évaluation des rapports de stage - Session 2024"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>

                            <!-- Contenu -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Contenu du compte rendu <span class="text-red-500">*</span>
                                </label>
                                <div id="cr-editor-container" class="border border-gray-300 rounded-lg">
                                    <div id="cr-editor" style="height: 300px;"></div>
                                </div>
                                <textarea id="cr-content" name="cr_content" style="display: none;" required></textarea>
                                <p class="text-sm text-gray-600 mt-2">Utilisez l'éditeur pour rédiger le contenu de votre compte rendu</p>
                            </div>

                            <!-- Date -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date du compte rendu</label>
                                <input type="date" id="cr-date" name="cr_date" value="<?php echo date('Y-m-d'); ?>"
                                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                        </div>

                        <!-- Options d'export -->
                        <div class="mb-8 p-6 bg-gray-50 rounded-xl">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-cog mr-2 text-primary"></i>
                                Options d'export
                            </h3>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Format d'export du compte rendu</label>
                                <select id="export-format" name="export_format" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="html">HTML (visualisation web)</option>
                                    <option value="pdf">PDF (document imprimable)</option>
                                    <option value="docx">DOCX (Microsoft Word)</option>
                                </select>
                                <p class="text-sm text-gray-600 mt-2">Le compte rendu sera généré dans le format sélectionné</p>
                            </div>
                        </div>

                        <input type="hidden" id="selected-rapport-ids" name="rapport_ids" value="">

                        <!-- Actions -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <button type="button" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors" id="cancel-multi-cr">
                                <i class="fas fa-times mr-2"></i> Annuler
                            </button>
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                <i class="fas fa-save mr-2"></i> Créer le compte rendu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation d'envoi -->
    <div class="fixed inset-0 z-50 overflow-y-auto hidden" id="email-confirmation-modal">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-6 pt-6 pb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="bg-green-100 rounded-lg p-2 mr-3">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">Confirmation</h2>
                        </div>
                        <button class="text-gray-400 hover:text-gray-600 transition-colors" id="close-confirmation-modal-btn">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-green-600 mb-4">
                            <i class="fas fa-check-circle text-6xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Email envoyé avec succès !</h3>
                        <p class="text-gray-600 mb-6">Votre email a été envoyé avec succès au destinataire.</p>
                        <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                            <p class="text-sm text-gray-600"><strong>Destinataire :</strong> <span id="confirmation-email"></span></p>
                            <p class="text-sm text-gray-600"><strong>Sujet :</strong> <span id="confirmation-subject"></span></p>
                        </div>
                        <button type="button" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors" id="close-confirmation-btn">
                            <i class="fas fa-times mr-2"></i> Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script src="https://unpkg.com/mammoth@1.4.21/mammoth.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html-docx-js@0.4.1/dist/html-docx.min.js"></script>

    <script>
        // Variables globales
        let selectedRapports = new Set();
        let quillEditor = null;

        // Descriptions des templates
        const templateDescriptions = {
            'template_cr_html': {
                title: 'Modèle HTML - Procès-verbal de validation de thèmes',
                description: 'Template HTML professionnel pour les procès-verbaux de séances de validation de thèmes avec mise en forme complète.',
                features: [
                    'Format procès-verbal officiel',
                    'En-tête université intégré',
                    'Sections structurées pour validation',
                    'Mise en forme professionnelle',
                    'Prêt pour impression/export'
                ]
            },
            'modele_compte_rendu_docx': {
                title: 'Modèle DOCX - Compte rendu standard',
                description: 'Template Microsoft Word professionnel pour les comptes rendus avec formatage avancé.',
                features: [
                    'Format Word natif (.docx)',
                    'Mise en forme professionnelle',
                    'Styles et formatage avancés',
                    'Compatible avec OnlyOffice',
                    'Facilement modifiable'
                ]
            }
        };

        // Templates de compte rendu
        const crTemplates = {
            'template_cr_html': {
                title: 'Procès-verbal de séance de validation de thèmes - {{date}}',
                file: 'template_cr.html',
                type: 'html'
            },
            'modele_compte_rendu_docx': {
                title: 'Compte rendu standard - {{date}}',
                file: 'modele_compte_rendu.docx',
                type: 'docx'
            }
        };

        // Pagination dynamique pour les rapports
        document.addEventListener('DOMContentLoaded', function() {
            const formRapports = document.getElementById('filter-form-rapports');
            document.querySelectorAll('.pagination .page-item[data-form="rapports"]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    if (page) {
                        document.getElementById('page_num_input_rapports').value = page;
                        formRapports.submit();
                    }
                });
            });

            // Pagination dynamique pour les comptes rendus
            const formCR = document.getElementById('filter-form-cr');
            document.querySelectorAll('.pagination .page-item[data-form="cr"]').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    if (page) {
                        document.getElementById('page_cr_input').value = page;
                        formCR.submit();
                    }
                });
            });

            // Gestion de la sélection multiple des rapports
            initMultiSelection();
            
            // Gestion des modales
            initModalHandlers();

            // Initialiser les gestionnaires de templates
            initTemplateHandlers();
        });

        // Initialiser l'éditeur Quill
        function initQuillEditor() {
            if (quillEditor) {
                return; // Déjà initialisé
            }

            quillEditor = new Quill('#cr-editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        ['link'],
                        [{ 'align': [] }],
                        ['clean']
                    ]
                },
                placeholder: 'Rédigez le contenu de votre compte rendu...'
            });

            // Synchroniser avec le textarea caché
            quillEditor.on('text-change', function() {
                const content = quillEditor.root.innerHTML;
                document.getElementById('cr-content').value = content;
            });
        }

        // Initialiser les gestionnaires de templates
        function initTemplateHandlers() {
            const loadTemplateBtn = document.getElementById('load-template-btn');
            const templateSelect = document.getElementById('cr-template');
            const templateDescription = document.getElementById('template-description');
            const templateDescText = document.getElementById('template-desc-text');
            const templateFeatures = document.getElementById('template-features');

            // Gestion du changement de sélection de template
            if (templateSelect) {
                templateSelect.addEventListener('change', function() {
                    const selectedTemplate = this.value;
                    updateTemplateDescription(selectedTemplate);
                    
                    // Effet visuel sur la sélection
                    this.classList.remove('template-selected');
                    if (selectedTemplate) {
                        setTimeout(() => {
                            this.classList.add('template-selected');
                        }, 100);
                    }
                });
            }

            // Gestion du bouton de chargement
            if (loadTemplateBtn) {
                loadTemplateBtn.addEventListener('click', function() {
                    const selectedTemplate = templateSelect.value;
                    if (!selectedTemplate) {
                        showTemplateAlert('Veuillez sélectionner un modèle.', 'warning');
                        return;
                    }
                    loadTemplate(selectedTemplate);
                });

                // Effet hover sur le bouton
                loadTemplateBtn.addEventListener('mouseenter', function() {
                    this.querySelector('i').style.transform = 'rotate(360deg)';
                });

                loadTemplateBtn.addEventListener('mouseleave', function() {
                    this.querySelector('i').style.transform = 'rotate(0deg)';
                });
            }

            // Fonction pour mettre à jour la description du template
            function updateTemplateDescription(templateKey) {
                if (!templateKey) {
                    templateDescription.classList.remove('show');
                    templateDescText.textContent = 'Choisissez un modèle pour voir sa description détaillée.';
                    templateFeatures.style.display = 'none';
                    return;
                }

                const desc = templateDescriptions[templateKey];
                if (desc) {
                    templateDescText.textContent = desc.description;
                    
                    // Mise à jour des fonctionnalités
                    templateFeatures.innerHTML = '';
                    desc.features.forEach(feature => {
                        const li = document.createElement('li');
                        li.innerHTML = `<i class="fas fa-check-circle text-green-500 mr-2"></i>${feature}`;
                        templateFeatures.appendChild(li);
                    });
                    
                    templateFeatures.style.display = 'block';
                    templateDescription.classList.add('show');
                }
            }
        }

        // Charger un template avec animations
        async function loadTemplate(templateKey) {
            const template = crTemplates[templateKey];
            const loadBtn = document.getElementById('load-template-btn');
            const templateSelector = document.querySelector('.template-selector');
            
            if (!template) {
                showTemplateAlert('Modèle non trouvé.', 'error');
                return;
            }

            // Animation de chargement
            templateSelector.classList.add('template-loading');
            loadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Chargement...';
            loadBtn.disabled = true;

            try {
                // Préparer les variables de remplacement
                const currentDate = new Date().toLocaleDateString('fr-FR');
                const rapportIds = Array.from(selectedRapports);
                const evaluateur = '<?php echo $_SESSION["user_fullname"] ?? "Utilisateur"; ?>';

                // Construire la liste des rapports avec style
                let rapportsList = '<div style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin: 1rem 0;">';
                rapportsList += '<h4 style="color: #1a5276; margin: 0 0 0.75rem 0;"><i class="fas fa-list"></i> Rapports concernés :</h4><ul style="margin: 0; padding-left: 1.5rem;">';
                
                rapportIds.forEach((rapportId, index) => {
                    const checkbox = document.querySelector(`input[value="${rapportId}"]`);
                    if (checkbox) {
                        const data = checkbox.dataset;
                        rapportsList += `<li style="margin: 0.5rem 0; color: #495057;">
                            <strong style="color: #1a5276;">Rapport #${data.rapportId}</strong> - 
                            ${data.nomEtd} ${data.prenomEtd} - 
                            <em>${data.themeMemoire}</em>
                            <span style="font-size: 0.8rem; color: #6c757d;"> (${data.statut})</span>
                        </li>`;
                    }
                });
                rapportsList += '</ul></div>';

                let content = '';
                let title = template.title
                    .replace(/\{\{date\}\}/g, currentDate)
                    .replace(/\{\{nb_rapports\}\}/g, rapportIds.length);

                // Charger selon le type de template
                if (template.type === 'html') {
                    // Charger le template HTML
                    const templateUrl = `/GSCV+/storage/templates/${template.file}`;
                    console.log('Chargement du template HTML depuis:', templateUrl);
                    console.log('Template object:', template);

                    const response = await fetch(templateUrl);
                    console.log('Réponse du serveur:', response.status, response.statusText);
                    if (!response.ok) {
                        throw new Error(`Impossible de charger le modèle HTML (${response.status}) - ${response.statusText}`);
                    }

                    const htmlContent = await response.text();
                    
                    // Pour les templates HTML complets, on extrait tout le contenu
                    // en gardant la structure et les styles
                    let processedContent = htmlContent;
                    
                    // Remplacer les placeholders dans le contenu HTML
                    processedContent = processedContent
                        .replace(/\[DATE DU JOUR\]/g, `<strong>${currentDate}</strong>`)
                        .replace(/\[nombre de dossiers examinés\]/g, `<strong>${rapportIds.length}</strong>`)
                        .replace(/\[ÉNUMÉRER LES MEMBRES PRÉSENTS\]/g, `<strong>${evaluateur} et les membres de la commission</strong>`)
                        .replace(/\[NOM DE L'ÉTUDIANT\]/g, rapportIds.length > 0 ? 'Étudiants sélectionnés' : '[NOM DE L\'ÉTUDIANT]')
                        .replace(/\[HEURE DE FIN\]/g, new Date().toLocaleTimeString('fr-FR'))
                        .replace(/\[DATE\]/g, `<strong>${currentDate}</strong>`)
                        .replace(/\[Heure début\]/g, '14h00')
                        .replace(/\[Heure fin\]/g, new Date().toLocaleTimeString('fr-FR'));

                    // Extraire le contenu du body en nettoyant pour Quill
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(processedContent, 'text/html');
                    let bodyContent = doc.body.innerHTML;
                    
                    // Nettoyer le contenu pour Quill (enlever les balises style intégrées)
                    // Quill ne supporte pas les balises <style> dans le contenu
                    bodyContent = bodyContent.replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '');
                    
                    // Garder les styles essentiels séparément pour l'injection dans la page
                    const styles = doc.head.querySelector('style');
                    const styleContent = styles ? styles.innerHTML : '';
                    
                    // Pour Quill, on utilise seulement le contenu HTML sans les styles
                    content = bodyContent;
                    
                    // Injecter les styles dans la page (pas dans l'éditeur)
                    if (styleContent) {
                        let existingStyle = document.getElementById('template-styles');
                        if (!existingStyle) {
                            existingStyle = document.createElement('style');
                            existingStyle.id = 'template-styles';
                            document.head.appendChild(existingStyle);
                        }
                        existingStyle.innerHTML = styleContent;
                    }
                    
                    // Debug: Afficher des informations sur le contenu chargé
                    console.log('Template HTML chargé avec succès');
                    console.log('Longueur du contenu HTML:', htmlContent.length);
                    console.log('Longueur du contenu body original:', doc.body.innerHTML.length);
                    console.log('Longueur du contenu nettoyé pour Quill:', content.length);
                    console.log('Styles trouvés:', !!styleContent);
                    console.log('Contenu final (100 premiers caractères):', content.substring(0, 100));

                } else if (template.type === 'docx') {
                    // Charger le template DOCX
                    const templateUrl = `/GSCV+/storage/templates/${template.file}`;
                    console.log('Chargement du template DOCX depuis:', templateUrl);

                    const response = await fetch(templateUrl);
                    if (!response.ok) {
                        throw new Error(`Impossible de charger le modèle DOCX (${response.status})`);
                    }

                    const arrayBuffer = await response.arrayBuffer();
                    
                    // Vérifier si mammoth est disponible
                    if (typeof mammoth === 'undefined') {
                        throw new Error('La bibliothèque mammoth n\'est pas chargée. Veuillez ajouter mammoth.js à votre page.');
                    }

                    console.log('Conversion DOCX vers HTML avec mammoth...');
                    const result = await mammoth.convertToHtml({ arrayBuffer });
                    
                    // Remplacer les variables dans le contenu DOCX converti
                    content = result.value
                        .replace(/\[DATE\]/g, `<strong>${currentDate}</strong>`)
                        .replace(/\[NOMBRE_RAPPORTS\]/g, `<strong>${rapportIds.length}</strong>`)
                        .replace(/\[EVALUATEUR\]/g, `<strong>${evaluateur}</strong>`)
                        .replace(/\[RAPPORTS_LIST\]/g, rapportsList);
                }

                // Effet d'écriture progressive pour le titre
                const titleInput = document.getElementById('cr-title');
                titleInput.value = '';
                typeWriter(titleInput, title, 50);
                
                // Initialiser l'éditeur si nécessaire
                if (!quillEditor) {
                    initQuillEditor();
                }
                
                // Insérer le contenu dans l'éditeur avec animation
                setTimeout(() => {
                    console.log('Insertion du contenu dans l\'éditeur Quill...');
                    console.log('Éditeur Quill disponible:', !!quillEditor);
                    console.log('Longueur du contenu à insérer:', content.length);
                    
                    // Test temporaire : insérer un contenu minimal pour vérifier que l'éditeur fonctionne
                    if (content.length === 0) {
                        console.log('Contenu vide détecté, insertion d\'un contenu de test');
                        content = '<h1>Test de contenu</h1><p>Si vous voyez ceci, l\'éditeur fonctionne mais le template n\'a pas pu être chargé.</p>';
                    }
                    
                    quillEditor.root.innerHTML = content;
                    document.getElementById('cr-content').value = content;
                    
                    console.log('Contenu inséré. Longueur du contenu dans l\'éditeur:', quillEditor.root.innerHTML.length);
                    
                    // Animation de succès
                    templateSelector.classList.remove('template-loading');
                    templateSelector.classList.add('template-success');
                    
                    loadBtn.innerHTML = '<i class="fas fa-check mr-2"></i> Modèle chargé !';
                    loadBtn.disabled = false;
                    
                    // Notification de succès
                    showTemplateAlert(`Template "${templateDescriptions[templateKey].title}" chargé avec succès !`, 'success');
                    
                    // Réinitialiser l'état après 2 secondes
                    setTimeout(() => {
                        templateSelector.classList.remove('template-success');
                        loadBtn.innerHTML = '<i class="fas fa-magic mr-2"></i> Charger le modèle';
                    }, 2000);
                    
                }, 500);
                
            } catch (error) {
                console.error('Erreur lors du chargement du template:', error);
                templateSelector.classList.remove('template-loading');
                loadBtn.innerHTML = '<i class="fas fa-exclamation-triangle mr-2"></i> Erreur';
                loadBtn.disabled = false;
                showTemplateAlert(`Erreur lors du chargement du modèle: ${error.message}`, 'error');
                
                setTimeout(() => {
                    loadBtn.innerHTML = '<i class="fas fa-magic mr-2"></i> Charger le modèle';
                }, 2000);
            }
        }

        // Fonction d'effet machine à écrire
        function typeWriter(element, text, speed = 100) {
            let i = 0;
            function typing() {
                if (i < text.length) {
                    element.value += text.charAt(i);
                    i++;
                    setTimeout(typing, speed);
                }
            }
            typing();
        }

        // Système de notification amélioré
        function showTemplateAlert(message, type = 'info') {
            // Supprimer les alertes précédentes
            const existingAlert = document.querySelector('.template-alert');
            if (existingAlert) {
                existingAlert.remove();
            }

            const alert = document.createElement('div');
            alert.className = `template-alert template-alert-${type}`;
            alert.innerHTML = `
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas ${getAlertIcon(type)}" style="font-size: 1.2rem;"></i>
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; margin-left: auto;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            // Styles inline pour l'alerte
            alert.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
                z-index: 10000;
                animation: slideInRight 0.3s ease;
                max-width: 400px;
                background: ${getAlertColor(type)};
                border-left: 4px solid ${getAlertBorderColor(type)};
            `;

            document.body.appendChild(alert);

            // Auto-suppression après 4 secondes
            setTimeout(() => {
                if (alert.parentElement) {
                    alert.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 4000);
        }

        function getAlertIcon(type) {
            const icons = {
                'success': 'fa-check-circle',
                'warning': 'fa-exclamation-triangle', 
                'error': 'fa-times-circle',
                'info': 'fa-info-circle'
            };
            return icons[type] || icons.info;
        }

        function getAlertColor(type) {
            const colors = {
                'success': 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
                'warning': 'linear-gradient(135deg, #ffc107 0%, #fd7e14 100%)',
                'error': 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)',
                'info': 'linear-gradient(135deg, #17a2b8 0%, #138496 100%)'
            };
            return colors[type] || colors.info;
        }

        function getAlertBorderColor(type) {
            const colors = {
                'success': '#1e7e34',
                'warning': '#e0a800',
                'error': '#bd2130',
                'info': '#117a8b'
            };
            return colors[type] || colors.info;
        }

        // Initialisation de la sélection multiple
        function initMultiSelection() {
            const selectAllCheckbox = document.getElementById('select-all-rapports');
            const rapportCheckboxes = document.querySelectorAll('.rapport-checkbox');
            const selectedCountElement = document.getElementById('selected-count');
            const bulkCrBtn = document.getElementById('bulk-cr-btn');

            // Sélectionner/désélectionner tous
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    rapportCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                        updateSelection(checkbox);
                    });
                    updateSelectedCount();
                });
            }

            // Gestion des cases individuelles
            rapportCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    updateSelection(this);
                    updateSelectedCount();
                    
                    // Mettre à jour la case "Sélectionner tout"
                    if (selectAllCheckbox) {
                        const allChecked = Array.from(rapportCheckboxes).every(cb => cb.checked);
                        selectAllCheckbox.checked = allChecked;
                    }
                });
            });

            // Bouton de rédaction groupée
            if (bulkCrBtn) {
                bulkCrBtn.addEventListener('click', function() {
                    if (selectedRapports.size === 0) {
                        alert('Veuillez sélectionner au moins un rapport.');
                        return;
                    }
                    openMultiCrModal();
                });
            }

            // Boutons de rédaction individuelle
            document.querySelectorAll('.create-cr-single').forEach(button => {
                button.addEventListener('click', function() {
                    const rapportId = this.dataset.rapportId;
                    selectedRapports.clear();
                    selectedRapports.add(rapportId);
                    
                    // Décocher toutes les cases et cocher seulement celle-ci
                    rapportCheckboxes.forEach(cb => cb.checked = false);
                    const targetCheckbox = document.querySelector(`input[value="${rapportId}"]`);
                    if (targetCheckbox) {
                        targetCheckbox.checked = true;
                        updateSelection(targetCheckbox);
                    }
                    
                    updateSelectedCount();
                    openMultiCrModal();
                });
            });
        }

        // Mettre à jour la sélection
        function updateSelection(checkbox) {
            const rapportId = checkbox.value;
            if (checkbox.checked) {
                selectedRapports.add(rapportId);
            } else {
                selectedRapports.delete(rapportId);
            }
        }

        // Mettre à jour le compteur
        function updateSelectedCount() {
            const selectedCountElement = document.getElementById('selected-count');
            const bulkCrBtn = document.getElementById('bulk-cr-btn');
            
            if (selectedCountElement) {
                selectedCountElement.textContent = `${selectedRapports.size} rapport(s) sélectionné(s)`;
            }
            
            if (bulkCrBtn) {
                bulkCrBtn.disabled = selectedRapports.size === 0;
                bulkCrBtn.style.opacity = selectedRapports.size === 0 ? '0.5' : '1';
            }
        }

        // Ouvrir la modale de rédaction multiple
        function openMultiCrModal() {
            const modal = document.getElementById('multi-cr-modal');
            const selectedRapportsList = document.getElementById('selected-rapports-list');
            const selectedRapportIds = document.getElementById('selected-rapport-ids');
            
            // Construire la liste des rapports sélectionnés
            let rapportsHtml = '';
            const rapportIds = Array.from(selectedRapports);
            
            rapportIds.forEach(rapportId => {
                const checkbox = document.querySelector(`input[value="${rapportId}"]`);
                if (checkbox) {
                    const data = checkbox.dataset;
                    rapportsHtml += `
                        <div class="p-4 bg-white border border-gray-200 rounded-lg">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                        <i class="fas fa-file-alt text-primary"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900">Rapport #${data.rapportId}</h4>
                                    <p class="text-sm text-gray-600"><strong>Étudiant:</strong> ${data.nomEtd} ${data.prenomEtd}</p>
                                    <p class="text-sm text-gray-600"><strong>Thème:</strong> ${data.themeMemoire}</p>
                                    <p class="text-sm text-gray-600"><strong>Date de dépôt:</strong> ${data.dateDepot ? new Date(data.dateDepot).toLocaleDateString('fr-FR') : 'Non spécifiée'}</p>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full mt-2 ${data.statut === 'Validé' ? 'bg-green-100 text-green-800' : (data.statut === 'Rejeté' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')}">${data.statut}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });
            
            selectedRapportsList.innerHTML = rapportsHtml;
            selectedRapportIds.value = rapportIds.join(',');
            
            // Générer un titre par défaut
            const crTitleInput = document.getElementById('cr-title');
            const currentDate = new Date().toLocaleDateString('fr-FR');
            crTitleInput.value = `Compte rendu d'évaluation - ${rapportIds.length} rapport(s) - ${currentDate}`;
            
            // Initialiser l'éditeur Quill
            setTimeout(() => {
                initQuillEditor();
            }, 100);
            
            modal.classList.remove('hidden');
        }

        // Initialiser les gestionnaires de modales
        function initModalHandlers() {
            const multiCrModal = document.getElementById('multi-cr-modal');
            const closeMultiCrModal = document.getElementById('close-multi-cr-modal');
            const cancelMultiCr = document.getElementById('cancel-multi-cr');
            
            // Fermer la modale
            [closeMultiCrModal, cancelMultiCr].forEach(element => {
                if (element) {
                    element.addEventListener('click', function() {
                        multiCrModal.classList.add('hidden');
                    });
                }
            });
            
            // Fermer en cliquant à l'extérieur
            multiCrModal.addEventListener('click', function(e) {
                if (e.target === multiCrModal) {
                    multiCrModal.classList.add('hidden');
                }
            });

            // Gestionnaire du formulaire
            const multiCrForm = document.getElementById('multi-cr-form');
            if (multiCrForm) {
                multiCrForm.addEventListener('submit', function(e) {
                    // S'assurer que le contenu de l'éditeur est synchronisé
                    if (quillEditor) {
                        document.getElementById('cr-content').value = quillEditor.root.innerHTML;
                    }
                });
            }

            // Modal de confirmation
            const confirmationModal = document.getElementById('email-confirmation-modal');
            const closeConfirmationBtn = document.getElementById('close-confirmation-btn');
            const closeConfirmationModalBtn = document.getElementById('close-confirmation-modal-btn');
            
            [closeConfirmationBtn, closeConfirmationModalBtn].forEach(element => {
                if (element) {
                    element.addEventListener('click', function() {
                        confirmationModal.classList.add('hidden');
                    });
                }
            });
        }
    </script>
</body>
</html>