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
$data = $controller->viewReclamations();

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


        <!-- Contenu principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
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
                                <p class="text-3xl font-bold text-gray-900"><?php echo '0'; ?></p>
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
                                    <option value="En attente" <?php echo (isset($filters['status_filter']) && $filters['status_filter'] === 'En attente') ? 'selected' : ''; ?>>En attente de traitement</option>
                                    <option value="Traitée" <?php echo (isset($filters['status_filter']) && $filters['status_filter'] === 'Traitée') ? 'selected' : ''; ?>>Traitée</option>
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
                                            switch(strtolower(str_replace(' ', '-', $status))) {
                                                case 'en-attente':
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'traitée':
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
                        fetch('./assets/traitements/supprimer_reclamations.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: 'reclamation_ids=' + JSON.stringify(reclamationIds)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                openConfirmationModal(data.message || 'Réclamations supprimées avec succès.', function(){ location.reload(); });
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

        // Fonctions pour les actions sur les réclamations
        function voirReclamation(reclamationId) {
            window.open('?page=reclamations_etudiants&action=view&id=' + reclamationId, '_blank');
        }

        function traiterReclamation(reclamationId) {
            window.open('?page=reclamations_etudiants&action=treat&id=' + reclamationId, '_blank');
        }

        function supprimerReclamation(reclamationId) {
            openConfirmationModal(
                'Voulez-vous vraiment supprimer cette réclamation ?',
                function() {
                    fetch('./assets/traitements/supprimer_reclamations.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'reclamation_ids=' + JSON.stringify([reclamationId])
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