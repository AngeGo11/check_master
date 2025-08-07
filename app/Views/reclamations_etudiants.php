<?php
// Vérification de sécurité
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_groups'])) {
    header('Location: ../login.php');
    exit;
}

// Initialisation du contrôleur
require_once __DIR__ . '/../Controllers/ReclamationController.php';
$controller = new ReclamationController($pdo);

// Récupération des données via le contrôleur
$data = $controller->viewReclamations($_SESSION['lib_user_group']);

// Extraction des variables pour la vue
$reclamations = $data['reclamations'];
$statistics = $data['statistics'];
$pagination = $data['pagination'];
$filters = $data['filters'];
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réclamations Étudiants - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">

        <!-- Contenu principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Messages de succès et d'erreur -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-3 text-green-600"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center justify-between animate-fade-in">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-3 text-red-600"></i>
                        <span class="font-medium"><?= htmlspecialchars($_SESSION['error_message']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 animate-slide-up">
                <!-- Total réclamations -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total réclamations</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo isset($statistics['total']) ? $statistics['total'] : '0'; ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-exclamation-triangle text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- En cours de traitement -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-warning overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">En cours de traitement</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo isset($statistics['en_cours']) ? $statistics['en_cours'] : '0'; ?></p>
                            </div>
                            <div class="bg-warning/10 rounded-full p-4">
                                <i class="fas fa-clock text-2xl text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résolues -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Réclamations résolues</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo isset($statistics['resolues']) ? $statistics['resolues'] : '0'; ?></p>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-check-circle text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des réclamations -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                <div class="border-l-4 border-primary bg-white rounded-r-lg shadow-sm p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-list text-primary mr-3"></i>
                        Liste des réclamations
                    </h2>
                    <p class="text-gray-600">
                        Gestion et traitement des réclamations étudiantes
                    </p>
                </div>

                <!-- Filtres et actions -->
                <div class="p-6 border-b border-gray-200">
                    <form method="GET" id="filter-form" class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                        <input type="hidden" name="page" id="page_input" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'reclamations_etudiants'; ?>">
                        <input type="hidden" name="page_num" id="page_num_input" value="<?php echo isset($pagination['current_page']) ? $pagination['current_page'] : '1'; ?>">

                        <!-- Filtres de recherche -->
                        <div class="flex-1 w-full lg:w-auto">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <!-- Recherche -->
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text"
                                        name="search"
                                        id="search-input"
                                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Rechercher une demande d'étudiant..."
                                        value="<?php echo isset($filters['search']) ? htmlspecialchars($filters['search']) : ''; ?>">
                                </div>

                                <!-- Filtre date -->
                                <select name="date_filter"
                                    id="date-filter"
                                    class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Date de création de la réclamation</option>
                                    <option value="today" <?php echo (isset($filters['date_filter']) && $filters['date_filter'] === 'today') ? 'selected' : ''; ?>>Aujourd'hui</option>
                                    <option value="week" <?php echo (isset($filters['date_filter']) && $filters['date_filter'] === 'week') ? 'selected' : ''; ?>>Cette semaine</option>
                                    <option value="month" <?php echo (isset($filters['date_filter']) && $filters['date_filter'] === 'month') ? 'selected' : ''; ?>>Ce mois</option>
                                </select>

                                <!-- Filtre statut -->
                                <select name="status_filter"
                                    id="status-filter"
                                    class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    <option value="">Statut</option>
                                    <option value="En attente de traitement" <?php echo (isset($filters['status_filter']) && $filters['status_filter'] === 'En attente de traitement') ? 'selected' : ''; ?>>En attente de traitement</option>
                                    <option value="Traitée par le responsable de scolarité" <?php echo (isset($filters['status_filter']) && $filters['status_filter'] === 'Traitée par le responsable de scolarité') ? 'selected' : ''; ?>>Traitée par le responsable de scolarité</option>
                                    <option value="Traitée par le responsable de filière" <?php echo (isset($filters['status_filter']) && $filters['status_filter'] === 'Traitée par le responsable de filière') ? 'selected' : ''; ?>>Traitée par le responsable de filière</option>
                                </select>

                                <button type="submit"
                                    class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                    <i class="fas fa-search mr-2"></i> Filtrer
                                </button>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <button type="button"
                                class="px-4 py-3 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center"
                                id="bulk-delete-btn">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer sélection
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table des réclamations -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" id="select-all"
                                        class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    N° Réclamation
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Étudiants
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Motifs de réclamation
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date de réception
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($reclamations)) { ?>
                                <?php foreach ($reclamations as $reclamation) { ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox"
                                                class="reclamation-checkbox rounded border-gray-300 text-primary focus:ring-primary"
                                                value="<?php echo $reclamation['id_reclamation']; ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <strong>REC-<?php echo str_pad($reclamation['id_reclamation'], 6, '0', STR_PAD_LEFT); ?></strong>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-primary">
                                                            <?php echo substr($reclamation['nom_etd'], 0, 1) . substr($reclamation['prenom_etd'], 0, 1); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($reclamation['nom_etd'] . ' ' . $reclamation['prenom_etd']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        Carte étudiante : <?php echo $reclamation['num_carte_etd']; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($reclamation['motif_reclamation']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status = htmlspecialchars($reclamation['statut_reclamation']);
                                            $statusClass = '';
                                            switch ($status) {
                                                case 'En attente de traitement':
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'Traitée par le responsable de scolarité':
                                                    $statusClass = 'bg-blue-100 text-blue-800';
                                                    break;
                                                case 'Traitée par le responsable de filière':
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-gray-100 text-gray-800';
                                            }
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $reclamation['date_reclamation']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors duration-200"
                                                    title="Voir détails"
                                                    onclick="voirReclamation(<?php echo $reclamation['id_reclamation']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors duration-200"
                                                    title="Traiter la réclamation"
                                                    onclick="traiterReclamation(<?php echo $reclamation['id_reclamation']; ?>)">
                                                    <i class="fas fa-pen-fancy"></i>
                                                </button>
                                                <button class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-colors duration-200"
                                                    title="Supprimer"
                                                    onclick="supprimerReclamation(<?php echo $reclamation['id_reclamation']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } else { ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                                            <h3 class="text-lg font-medium mb-2">Aucune réclamation trouvée</h3>
                                            <p>Aucune réclamation ne correspond aux critères de recherche.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (isset($pagination['total_pages']) && $pagination['total_pages'] > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Page <?php echo isset($pagination['current_page']) ? $pagination['current_page'] : 1; ?> sur <?php echo $pagination['total_pages']; ?>
                            </div>
                            <div class="flex space-x-2">
                                <?php if (isset($pagination['current_page']) && $pagination['current_page'] > 1): ?>
                                    <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200" data-page="<?php echo $pagination['current_page'] - 1; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>

                                <?php
                                $current_page = isset($pagination['current_page']) ? $pagination['current_page'] : 1;
                                $total_pages = isset($pagination['total_pages']) ? $pagination['total_pages'] : 1;
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);

                                if ($start_page > 1): ?>
                                    <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200" data-page="1">1</a>
                                    <?php if ($start_page > 2): ?>
                                        <span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="#" class="px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $i === $current_page ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>
                                    <?php endif; ?>
                                    <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200" data-page="<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
                                <?php endif; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <a href="#" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200" data-page="<?php echo $current_page + 1; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal de confirmation moderne -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="confirmation-modal">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 animate-bounce-in">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-question-circle text-primary text-lg"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmation</h3>
                </div>
                <p class="text-gray-600 mb-6" id="confirmation-text">
                    Voulez-vous vraiment effectuer cette action ?
                </p>
                <div class="flex gap-3 justify-end">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        id="cancel-modal-btn">
                        Annuler
                    </button>
                    <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors"
                        id="confirm-modal-btn">
                        Confirmer
                    </button>
                </div>
            </div>
            <button class="absolute top-4 right-4 text-gray-400 hover:text-gray-600" id="close-confirmation-modal-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Modal de traitement de réclamation -->
    <div id="treatmentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[95vh] flex flex-col">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <i class="fa-regular fa-pen-to-square text-2xl"></i>
                    Traitement de la réclamation
                </h2>
                <button onclick="closeModal('treatmentModal')" class="text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">N° Réclamation</label>
                        <span id="modal-id" class="text-lg font-mono text-blue-600"></span>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Étudiant</label>
                        <span id="modal-student" class="text-lg font-medium text-gray-900"></span>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Date de réception</label>
                        <span id="modal-date" class="text-lg text-gray-700"></span>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Statut actuel</label>
                        <span id="modal-status" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800"></span>
                    </div>
                </div>
                
                <div class="space-y-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Motif de la réclamation</label>
                        <p id="modal-motif" class="text-gray-800 leading-relaxed"></p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Fichiers joints</label>
                        <div id="modal-files" class="space-y-2">
                            <p class="text-gray-500 italic">Aucun fichier joint</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 flex-shrink-0">
                <form method="POST" action="./assets/traitements/ajax_reclamations.php" class="space-y-4">
                    <input type="hidden" name="reclamation_id" id="modal-reclamation-id">
                    <input type="hidden" name="user_group" value="<?php echo $_SESSION['lib_user_group']; ?>">
                    
                    <?php if($_SESSION['lib_user_group'] == 'Administrateur plateforme' || $_SESSION['lib_user_group'] == 'Responsable filière'): ?>
                    <input type="hidden" name="action" value="traiter_reclamation">
                    <?php else: ?>
                    <input type="hidden" name="action" value="traiter_reclamation">
                    <?php endif; ?>
                    
                    <?php if($_SESSION['lib_user_group'] == 'Administrateur plateforme' || $_SESSION['lib_user_group'] == 'Responsable filière'): ?>
                    <div>
                        <label for="commentaire" class="block text-sm font-semibold text-gray-700 mb-2">
                            Commentaire de traitement <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            name="retour_traitement" 
                            id="commentaire" 
                            rows="4" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-all duration-200"
                            placeholder="Saisissez votre commentaire de traitement..."
                        ></textarea>
                    </div>
                    <?php else: ?>
                    <div>
                        <label for="commentaire_transfert" class="block text-sm font-semibold text-gray-700 mb-2">
                            Commentaire de transfert <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            name="commentaire_transfert" 
                            id="commentaire_transfert" 
                            rows="4" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none transition-all duration-200"
                            placeholder="Saisissez votre commentaire pour le transfert..."
                        ></textarea>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-end gap-3">
                        <button 
                            type="button" 
                            onclick="closeModal('treatmentModal')"
                            class="px-6 py-2.5 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium">
                            <i class="fas fa-times mr-2"></i>Fermer
                        </button>
                        <?php if($_SESSION['lib_user_group'] == 'Administrateur plateforme' || $_SESSION['lib_user_group'] == 'Responsable filière'): ?>
                        <button 
                            type="submit"
                            class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium flex items-center">
                            <i class="fas fa-check mr-2"></i>Finaliser le traitement
                        </button>
                        <?php else: ?>
                            <button 
                                type="submit"
                                class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium flex items-center">
                                <i class="fas fa-share mr-2"></i>Transférer la réclamation
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Modal de détails de réclamation -->
    <div id="previewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex items-center justify-between">
                <h2 class="text-xl font-bold text-white flex items-center gap-3">
                    <i class="fa-solid fa-file-lines text-2xl"></i>
                    Détails de la réclamation
                </h2>
                <button onclick="closeModal('previewModal')" class="text-white hover:text-gray-200 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- Body -->
            <div class="p-6 overflow-y-auto max-h-[60vh]">
                <div class="space-y-6">
                    <!-- Informations étudiant -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-100">
                        <h3 class="text-lg font-semibold text-blue-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-user-graduate text-blue-600"></i>
                            Informations de l'étudiant
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col">
                                <label class="text-sm font-medium text-blue-700 mb-1">Numéro de la carte d'étudiant</label>
                                <span id="preview-num-carte" class="text-lg font-mono text-blue-900 bg-white px-3 py-2 rounded-lg border border-blue-200"><?= htmlspecialchars($reclamation_details['num_carte_etd'] ?? ''); ?></span>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-sm font-medium text-blue-700 mb-1">Nom et prénoms</label>
                                <span id="preview-nom-prenom" class="text-lg font-medium text-blue-900 bg-white px-3 py-2 rounded-lg border border-blue-200"><?= htmlspecialchars(($reclamation_details['nom_etd'] ?? '') . ' ' . ($reclamation_details['prenom_etd'] ?? '')); ?></span>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-sm font-medium text-blue-700 mb-1">Niveau d'études</label>
                                <span id="preview-niveau" class="text-lg text-blue-900 bg-white px-3 py-2 rounded-lg border border-blue-200"><?= htmlspecialchars($reclamation_details['lib_niv_etd'] ?? ''); ?></span>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-sm font-medium text-blue-700 mb-1">Date de réception</label>
                                <span id="preview-date" class="text-lg text-blue-900 bg-white px-3 py-2 rounded-lg border border-blue-200"><?= date('d/m/Y', strtotime($reclamation_details['date_reclamation'] ?? '')); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Détails de la réclamation -->
                    <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-6 border border-orange-100">
                        <h3 class="text-lg font-semibold text-orange-900 mb-4 flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-orange-600"></i>
                            Détails de la réclamation
                        </h3>
                        <div class="space-y-4">
                            <div class="flex flex-col">
                                <label class="text-sm font-medium text-orange-700 mb-2">Statut</label>
                                <span id="preview-statut" class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                                    <i class="fas fa-clock mr-2"></i>
                                    <?= htmlspecialchars($reclamation_details['statut_reclamation'] ?? ''); ?>
                                </span>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-sm font-medium text-orange-700 mb-2">Motif de la réclamation</label>
                                <div id="preview-motif" class="bg-white p-4 rounded-lg border border-orange-200 text-orange-900 leading-relaxed">
                                    <?= htmlspecialchars($reclamation_details['motif_reclamation'] ?? ''); ?>
                                </div>
                            </div>
                            <div class="flex flex-col">
                                <label class="text-sm font-medium text-orange-700 mb-2">Matières concernées</label>
                                <div id="preview-matieres" class="bg-white p-4 rounded-lg border border-orange-200">
                                    <?php
                                    if (!empty($reclamation_details['matieres'])) {
                                        $matieres = json_decode($reclamation_details['matieres'], true);
                                        if (is_array($matieres)) {
                                            echo '<div class="flex flex-wrap gap-2">';
                                            foreach ($matieres as $matiere) {
                                                echo '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800 border border-orange-200">' . htmlspecialchars($matiere) . '</span>';
                                            }
                                            echo '</div>';
                                        }
                                    } else {
                                        echo '<p class="text-gray-500 italic">Aucune matière spécifiée</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                <div class="flex justify-end gap-3">
                    <button 
                        onclick="closeModal('previewModal')"
                        class="px-6 py-2.5 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors duration-200 font-medium flex items-center"
                    >
                        <i class="fas fa-times mr-2"></i>Fermer
                    </button>
                    <button 
                        onclick="telechargerReclamation()"
                        class="px-6 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 font-medium flex items-center">
                        <i class="fas fa-eye mr-2"></i>Voir fiche de réclamation
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pagination dynamique pour garder les filtres/recherche
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filter-form');
            document.querySelectorAll('.pagination .page-item').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    if (page) {
                        document.getElementById('page_num_input').value = page;
                        form.submit();
                    }
                });
            });

            // Sélection/désélection de toutes les réclamations
            const selectAllCheckbox = document.getElementById('select-all');
            const reclamationCheckboxes = document.querySelectorAll('.reclamation-checkbox');

            selectAllCheckbox.addEventListener('change', function() {
                reclamationCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // Vérifier si toutes les réclamations sont sélectionnées
            reclamationCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(reclamationCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                });
            });
        });

        // Modale de confirmation générique
        let confirmCallback = null;

        function openConfirmationModal(message, onConfirm) {
            document.getElementById('confirmation-text').textContent = message;
            document.getElementById('confirmation-modal').style.display = 'flex';
            confirmCallback = onConfirm;
        }

        // Fonctions pour les nouvelles modales Tailwind
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                // Ajouter une animation d'entrée
                const modalContent = modal.querySelector('.bg-white');
                if (modalContent) {
                    modalContent.classList.add('animate-pulse');
                    setTimeout(() => {
                        modalContent.classList.remove('animate-pulse');
                    }, 300);
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        function closeConfirmationModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
            confirmCallback = null;
        }
        document.getElementById('confirm-modal-btn').onclick = function() {
            if (typeof confirmCallback === 'function') confirmCallback();
            closeConfirmationModal();
        };
        document.getElementById('cancel-modal-btn').onclick = closeConfirmationModal;
        document.getElementById('close-confirmation-modal-btn').onclick = closeConfirmationModal;
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('confirmation-modal');
            if (event.target === modal) closeConfirmationModal();
            
            // Fermer les nouvelles modales en cliquant à l'extérieur
            const treatmentModal = document.getElementById('treatmentModal');
            const previewModal = document.getElementById('previewModal');
            
            if (event.target === treatmentModal) closeModal('treatmentModal');
            if (event.target === previewModal) closeModal('previewModal');
        });

        // Fermer les modales avec la touche Échap
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const treatmentModal = document.getElementById('treatmentModal');
                const previewModal = document.getElementById('previewModal');
                
                if (!treatmentModal.classList.contains('hidden')) closeModal('treatmentModal');
                if (!previewModal.classList.contains('hidden')) closeModal('previewModal');
            }
        });

        // Suppression multiple
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                const checkedBoxes = document.querySelectorAll('.reclamation-checkbox:checked');
                const reclamationIds = Array.from(checkedBoxes).map(cb => cb.value);

                if (reclamationIds.length === 0) {
                    openConfirmationModal('Veuillez sélectionner au moins une réclamation à supprimer.', null);
                    return;
                }

                openConfirmationModal(
                    `Voulez-vous vraiment supprimer les ${reclamationIds.length} réclamations sélectionnées ?`,
                    function() {
                        fetch('./assets/traitements/ajax_reclamations.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'action=supprimer_multiple&reclamation_ids=' + JSON.stringify(reclamationIds)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    openConfirmationModal(data.message || 'Réclamations supprimées avec succès.', function() {
                                        location.reload();
                                    });
                                } else {
                                    openConfirmationModal('Une erreur est survenue lors de la suppression : ' + (data.error || 'Erreur inconnue'), null);
                                }
                            })
                            .catch(error => {
                                console.error('Erreur:', error);
                                openConfirmationModal('Une erreur de communication est survenue.', null);
                            });
                    }
                );
            });
        }

        let currentReclamationId = null;

        function voirReclamation(id) {
            currentReclamationId = id;
            // Récupérer les détails de la réclamation
            fetch(`./assets/traitements/ajax_reclamations.php?action=get_details&id=${id}`)
                .then(response => response.json())
                .then(response => {
                    if (!response.success) {
                        throw new Error(response.error || 'Erreur lors de la récupération des détails');
                    }
                    
                    const data = response.data;
                    
                    // Mettre à jour les champs avec les données reçues
                    document.getElementById('preview-num-carte').textContent = data.num_carte_etd || '';
                    document.getElementById('preview-nom-prenom').textContent = `${data.nom_etd || ''} ${data.prenom_etd || ''}`;
                    document.getElementById('preview-niveau').textContent = data.lib_niv_etd || '';
                    document.getElementById('preview-motif').textContent = data.motif_reclamation || '';
                    document.getElementById('preview-date').textContent = data.date_reclamation || '';

                    // Mise à jour du statut avec la classe appropriée
                    const statutElement = document.getElementById('preview-statut');
                    statutElement.textContent = data.statut_reclamation || '';
                    statutElement.className = `status status-${(data.statut_reclamation || '').toLowerCase().replace(' ', '-')}`;

                    // Mise à jour des matières
                    const matieresContainer = document.getElementById('preview-matieres');
                    matieresContainer.innerHTML = '';
                    if (data.matieres) {
                        try {
                            const matieres = JSON.parse(data.matieres);
                            if (Array.isArray(matieres) && matieres.length > 0) {
                                const wrapper = document.createElement('div');
                                wrapper.className = 'flex flex-wrap gap-2';
                                matieres.forEach(matiere => {
                                    const span = document.createElement('span');
                                    span.className = 'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-orange-100 text-orange-800 border border-orange-200';
                                    span.textContent = matiere;
                                    wrapper.appendChild(span);
                                });
                                matieresContainer.appendChild(wrapper);
                            } else {
                                matieresContainer.innerHTML = '<p class="text-gray-500 italic">Aucune matière spécifiée</p>';
                            }
                        } catch (e) {
                            console.error('Erreur lors du parsing des matières:', e);
                            matieresContainer.innerHTML = '<p class="text-gray-500 italic">Erreur lors du chargement des matières</p>';
                        }
                    } else {
                        matieresContainer.innerHTML = '<p class="text-gray-500 italic">Aucune matière spécifiée</p>';
                    }

                    // Afficher la modale
                    openModal('previewModal');
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la récupération des détails');
                });
        }

        function traiterReclamation(id) {
            currentReclamationId = id;
            // Récupérer les détails de la réclamation
            fetch(`./assets/traitements/ajax_reclamations.php?action=get_details&id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(response => {
                    if (!response.success) {
                        throw new Error(response.error || 'Erreur lors de la récupération des détails');
                    }
                    
                    const data = response.data;

                    // Mettre à jour les champs de la modale de traitement
                    const elements = {
                        'modal-id': `REC-${String(data.id_reclamation).padStart(6, '0')}`,
                        'modal-student': `${data.nom_etd} ${data.prenom_etd}`,
                        'modal-date': data.date_reclamation,
                        'modal-status': data.statut_reclamation,
                        'modal-motif': data.motif_reclamation,
                        'modal-reclamation-id': data.id_reclamation
                    };

                    // Mettre à jour chaque élément s'il existe
                    Object.entries(elements).forEach(([id, value]) => {
                        const element = document.getElementById(id);
                        if (element) {
                            if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
                                element.value = value;
                            } else {
                                element.textContent = value;
                            }
                        }
                    });

                    // Gestion des fichiers joints
                    const filesContainer = document.getElementById('modal-files');
                    if (filesContainer) {
                        if (data.fichiers && data.fichiers.length > 0) {
                            filesContainer.innerHTML = data.fichiers.map(file =>
                                `<a href="${file.chemin_fichier}" class="file-link" target="_blank">
                                    <i class="fas fa-file"></i> ${file.nom_fichier}
                                </a>`
                            ).join('');
                        } else {
                            filesContainer.innerHTML = '<p>Aucun fichier joint</p>';
                        }
                    }

                    // Afficher la modale de traitement
                    const modal = document.getElementById('treatmentModal');
                    if (modal) {
                        modal.style.display = 'flex';
                        setTimeout(() => {
                            modal.classList.add('show');
                        }, 10);
                    } else {
                        console.error('Modal non trouvée');
                        alert('Erreur: La modale de traitement n\'a pas été trouvée');
                    }
                })
                .catch(error => {
                    console.error('Erreur détaillée:', error);
                    alert('Une erreur est survenue lors de la récupération des détails: ' + error.message);
                });
        }

        function supprimerReclamation(reclamationId) {
            openConfirmationModal(
                'Voulez-vous vraiment supprimer cette réclamation ?',
                function() {
                    fetch('./assets/traitements/ajax_reclamations.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'action=supprimer&reclamation_ids=' + JSON.stringify([reclamationId])
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                alert('Erreur lors de la suppression : ' + data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            alert('Une erreur de communication est survenue.');
                        });
                }
            );
        }

          function telechargerReclamation() {
            if (currentReclamationId) {
                window.open(`assets/traitements/imprimer_reclamation.php?id_reclamation=${currentReclamationId}`, '_blank');
            }
        }

        // Système de notifications moderne
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 max-w-sm bg-white border rounded-lg shadow-lg p-4 transform transition-all duration-300 translate-x-full`;

            const bgColor = type === 'success' ? 'border-l-4 border-l-green-500' :
                type === 'error' ? 'border-l-4 border-l-red-500' :
                'border-l-4 border-l-blue-500';

            const icon = type === 'success' ? 'fas fa-check-circle text-green-500' :
                type === 'error' ? 'fas fa-exclamation-circle text-red-500' :
                'fas fa-info-circle text-blue-500';

            notification.className += ` ${bgColor}`;

            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="${icon} text-lg mr-3"></i>
                    <p class="text-gray-900 flex-1">${message}</p>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600 ml-2">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
        }
    </script>

</body>

</html>