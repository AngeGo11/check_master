<?php
// Vérification de sécurité
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_groups'])) {
    header('Location: ../public/pageConnection.php');
    exit;
}

// Initialisation du contrôleur
require_once '../app/Controllers/AnalyseController.php';
$controller = new AnalyseController();

// Récupération des données via le contrôleur
$data = $controller->viewAnalyses();

// Extraction des variables pour la vue
$rapports = $data['rapports'];
$total_rapports = $data['total_rapports'];
$rapports_evalues = $data['rapports_evalues'];
$rapports_attente = $data['rapports_attente'];
$pagination = $data['pagination'];
$filters = $data['filters'];
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation et Analyse des Rapports Étudiants</title>
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


            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 animate-slide-up">
                <!-- Total rapports -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des rapports</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $total_rapports; ?></p>
                                <p class="text-xs text-gray-500 mt-1">Depuis le début de l'année</p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-file-alt text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapports évalués -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Rapports évalués</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $rapports_evalues; ?></p>
                                <p class="text-xs text-gray-500 mt-1">Traités avec succès</p>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-check-circle text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapports en attente -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-warning overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">En attente</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $rapports_attente; ?></p>
                                <p class="text-xs text-gray-500 mt-1">À examiner</p>
                            </div>
                            <div class="bg-warning/10 rounded-full p-4">
                                <i class="fas fa-hourglass-half text-2xl text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barre d'actions et filtres -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8 animate-slide-up">
                <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                    <!-- Filtres de recherche -->
                    <div class="flex-1 w-full lg:w-auto">
                        <form method="GET" id="filter-form" class="flex flex-col sm:flex-row gap-4">
                            <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'analyses'; ?>">
                            <input type="hidden" name="page_num" id="page_num_input" value="<?php echo $pagination['current_page']; ?>">
                            
                            <!-- Recherche -->
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       id="search-input" 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="Rechercher un rapport..." 
                                       value="<?php echo htmlspecialchars($filters['search']); ?>">
                            </div>
                            
                            <!-- Filtre date -->
                            <select name="date_filter" 
                                    class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Date de soumission</option>
                                <option value="today" <?php echo $filters['date_filter'] === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                <option value="week" <?php echo $filters['date_filter'] === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                                <option value="month" <?php echo $filters['date_filter'] === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                                <option value="year" <?php echo $filters['date_filter'] === 'year' ? 'selected' : ''; ?>>Cette année</option>
                            </select>
                            
                            <button type="submit" 
                                    class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                <i class="fas fa-search mr-2"></i>
                                Filtrer
                            </button>
                        </form>
                    </div>

                    <!-- Actions en lot -->
                    <div class="flex gap-3">
                        <button class="px-4 py-3 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center" 
                                id="bulk-delete-btn">
                            <i class="fas fa-trash mr-2"></i>
                            Supprimer sélection
                        </button>
                    </div>
                </div>
            </div>

            <!-- Liste des rapports -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-list mr-2 text-primary"></i>
                        Rapports en attente de validation
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" id="select-all" 
                                           class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rapport N°
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Étudiant
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Titre du rapport
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date de soumission
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (!empty($rapports)): ?>
                                <?php foreach ($rapports as $rapport): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" 
                                                   class="rapport-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                   value="<?php echo $rapport['id_rapport_etd']; ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                #<?php echo $rapport['id_rapport_etd']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mr-3">
                                                    <span class="text-sm font-medium text-primary">
                                                        <?php echo substr($rapport['nom_etd'], 0, 1) . substr($rapport['prenom_etd'], 0, 1); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($rapport['nom_etd'] . ' ' . $rapport['prenom_etd']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($rapport['theme_memoire']); ?>">
                                                <?php echo htmlspecialchars($rapport['theme_memoire']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                                <?php echo $rapport['date_approbation']; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="?page=validations&rapport=<?php echo $rapport['id_rapport_etd']; ?>" 
                                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-accent hover:bg-green-600 transition-colors duration-200">
                                                <i class="fas fa-check-circle mr-2"></i>
                                                Valider
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun rapport trouvé</h3>
                                            <p class="text-gray-500">Il n'y a pas de rapports correspondant à vos critères de recherche.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($pagination['current_page'] > 1): ?>
                                    <a href="#" class="page-item relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" 
                                       data-page="<?php echo $pagination['current_page'] - 1; ?>">
                                        Précédent
                                    </a>
                                <?php endif; ?>
                                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                    <a href="#" class="page-item ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" 
                                       data-page="<?php echo $pagination['current_page'] + 1; ?>">
                                        Suivant
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Page <span class="font-medium"><?php echo $pagination['current_page']; ?></span>
                                        sur <span class="font-medium"><?php echo $pagination['total_pages']; ?></span>
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <?php if ($pagination['current_page'] > 1): ?>
                                            <a href="#" class="page-item relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" 
                                               data-page="<?php echo $pagination['current_page'] - 1; ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $start_page = max(1, $pagination['current_page'] - 2);
                                        $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <a href="#" class="page-item relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $pagination['current_page'] ? 'bg-primary border-primary text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?>" 
                                               data-page="<?php echo $i; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                            <a href="#" class="page-item relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" 
                                               data-page="<?php echo $pagination['current_page'] + 1; ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="confirmation-modal">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-bounce-in">
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
        // Pagination dynamique
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('filter-form');
            document.querySelectorAll('.page-item').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    if (page) {
                        document.getElementById('page_num_input').value = page;
                        form.submit();
                    }
                });
            });

            // Sélection de tous les rapports
            const selectAllCheckbox = document.getElementById('select-all');
            const rapportCheckboxes = document.querySelectorAll('.rapport-checkbox');

            selectAllCheckbox.addEventListener('change', function() {
                rapportCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
            });

            // Vérifier si tous les rapports sont sélectionnés
            rapportCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(rapportCheckboxes).every(cb => cb.checked);
                    selectAllCheckbox.checked = allChecked;
                });
            });
        });

        // Modal de confirmation
        let confirmCallback = null;
        function openConfirmationModal(message, onConfirm) {
            document.getElementById('confirmation-text').textContent = message;
            document.getElementById('confirmation-modal').classList.remove('hidden');
            document.getElementById('confirmation-modal').classList.add('flex');
            confirmCallback = onConfirm;
        }

        function closeConfirmationModal() {
            document.getElementById('confirmation-modal').classList.add('hidden');
            document.getElementById('confirmation-modal').classList.remove('flex');
            confirmCallback = null;
        }

        document.getElementById('confirm-modal-btn').addEventListener('click', function() {
            if (typeof confirmCallback === 'function') confirmCallback();
            closeConfirmationModal();
        });

        document.getElementById('cancel-modal-btn').addEventListener('click', closeConfirmationModal);
        document.getElementById('close-confirmation-modal-btn').addEventListener('click', closeConfirmationModal);

        // Suppression en lot
        document.getElementById('bulk-delete-btn').addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.rapport-checkbox:checked');
            const rapportIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            if (rapportIds.length === 0) {
                openConfirmationModal('Veuillez sélectionner au moins un rapport à supprimer.', null);
                return;
            }
            
            openConfirmationModal(
                `Voulez-vous vraiment supprimer les ${rapportIds.length} rapports sélectionnés ?`,
                function() {
                    fetch('./assets/traitements/supprimer_rapports_analyses.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'rapport_ids=' + JSON.stringify(rapportIds)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            openConfirmationModal(data.message || 'Rapports supprimés avec succès.', function(){ location.reload(); });
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
    </script>
</body>
</html>