<?php
// Vérification de sécurité
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_groups'])) {
    header('Location: ../login.php');
    exit;
}

// Initialisation du contrôleur
require_once '../app/Controllers/ReunionController.php';
$controller = new ReunionController();

// Récupération des données via le contrôleur
$data = $controller->viewReunions();

// Extraction des variables pour la vue
$reunions = $data['reunions'];
$statistics = $data['statistics'];
$pagination = $data['pagination'];
$filters = $data['filters'];
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réunions de la Commission</title>
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 animate-slide-up">
                <!-- Réunions planifiées -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Réunions planifiées</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['reunions_planifiees']; ?></p>
                                <p class="text-xs text-gray-500 mt-1">Pour le mois à venir</p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-calendar-alt text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapports à examiner -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-warning overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Rapports à examiner</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['rapports_a_examiner']; ?></p>
                                <p class="text-xs text-gray-500 mt-1">En attente de validation</p>
                            </div>
                            <div class="bg-warning/10 rounded-full p-4">
                                <i class="fas fa-file-alt text-2xl text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Membres actifs -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Membres actifs</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['membres_actifs']; ?></p>
                                <p class="text-xs text-gray-500 mt-1">3 derniers mois</p>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-users text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes de réunion -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-secondary overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Notes de réunion</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['notes_archives']; ?></p>
                                <p class="text-xs text-gray-500 mt-1">Archivées cette année</p>
                            </div>
                            <div class="bg-secondary/10 rounded-full p-4">
                                <i class="fas fa-clipboard text-2xl text-secondary"></i>
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
                            <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'reunions'; ?>">
                            
                            <!-- Recherche -->
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                       placeholder="Rechercher une réunion..." 
                                       value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                            </div>
                            
                            <!-- Filtre statut -->
                            <select name="status_filter" 
                                    class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Tous les statuts</option>
                                <option value="planifiee" <?php echo ($filters['status'] ?? '') === 'planifiee' ? 'selected' : ''; ?>>Planifiée</option>
                                <option value="en_cours" <?php echo ($filters['status'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                <option value="terminee" <?php echo ($filters['status'] ?? '') === 'terminee' ? 'selected' : ''; ?>>Terminée</option>
                                <option value="reportee" <?php echo ($filters['status'] ?? '') === 'reportee' ? 'selected' : ''; ?>>Reportée</option>
                            </select>
                            
                            <button type="submit" 
                                    class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                <i class="fas fa-search mr-2"></i>
                                Filtrer
                            </button>
                        </form>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3">
                        <button onclick="openNewMeetingModal()" 
                                class="px-6 py-3 bg-accent text-white rounded-lg hover:bg-green-600 transition-colors duration-200 flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Nouvelle réunion
                        </button>
                        <button onclick="updateMeetingStatuses()" 
                                class="px-4 py-3 bg-warning text-white rounded-lg hover:bg-yellow-600 transition-colors duration-200 flex items-center">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Mettre à jour statuts
                        </button>
                        <button onclick="exportMeetings()" 
                                class="px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors duration-200 flex items-center">
                            <i class="fas fa-download mr-2"></i>
                            Exporter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Onglets -->
            <div class="bg-white rounded-xl shadow-lg mb-8 animate-slide-up">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button onclick="switchTab('list')" id="tab-list" 
                                class="tab-button border-b-2 border-primary py-4 px-1 text-sm font-medium text-primary">
                            <i class="fas fa-list mr-2"></i>
                            Liste des réunions
                        </button>
                        <button onclick="switchTab('calendar')" id="tab-calendar" 
                                class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Calendrier
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Liste des réunions -->
            <div id="list-view" class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-calendar mr-2 text-primary"></i>
                        Réunions de la commission
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Réunion
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date & Heure
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Lieu
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Participants invités
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
                            <?php if (!empty($reunions)): ?>
                                <?php foreach ($reunions as $reunion): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($reunion['titre'] ?? 'Réunion de commission'); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($reunion['description'] ?? ''); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center text-sm text-gray-900">
                                                <i class="fas fa-calendar mr-2 text-gray-400"></i>
                                                <?php 
                                                $date_reunion = $reunion['date_reunion'] ?? $reunion['date'] ?? null;
                                                echo $date_reunion ? date('d/m/Y', strtotime($date_reunion)) : 'Date à définir';
                                                ?>
                                            </div>
                                            <div class="flex items-center text-sm text-gray-500">
                                                <i class="fas fa-clock mr-2 text-gray-400"></i>
                                                <?php 
                                                $heure_debut = $reunion['heure_debut'] ?? $reunion['heure'] ?? null;
                                                $heure_fin = $reunion['heure_fin'] ?? $reunion['duree'] ?? null;
                                                
                                                if ($heure_debut) {
                                                    echo date('H:i', strtotime($heure_debut));
                                                    if ($heure_fin) {
                                                        echo ' - ' . date('H:i', strtotime($heure_fin));
                                                    }
                                                } else {
                                                    echo 'Heure à définir';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                                                <?php echo htmlspecialchars($reunion['lieu'] ?? 'Salle de réunion'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex -space-x-1 overflow-hidden">
                                                <?php for ($i = 0; $i < min(3, $reunion['nb_participants'] ?? 0); $i++): ?>
                                                    <div class="inline-block h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-xs font-medium text-primary border-2 border-white">
                                                        <?php echo chr(65 + $i); ?>
                                                    </div>
                                                <?php endfor; ?>
                                                <?php if (($reunion['nb_participants'] ?? 0) > 3): ?>
                                                    <div class="inline-block h-8 w-8 rounded-full bg-gray-100 flex items-center justify-center text-xs font-medium text-gray-600 border-2 border-white">
                                                        +<?php echo (($reunion['nb_participants'] ?? 0) - 3); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <?php echo ($reunion['nb_participants'] ?? 0); ?> participant(s)
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status = $reunion['statut'] ?? $reunion['status'] ?? 'programmée';
                                            $statusColors = [
                                                'programmée' => 'bg-blue-100 text-blue-800',
                                                'planifiee' => 'bg-blue-100 text-blue-800',
                                                'en_cours' => 'bg-yellow-100 text-yellow-800',
                                                'en cours' => 'bg-yellow-100 text-yellow-800',
                                                'terminee' => 'bg-green-100 text-green-800',
                                                'terminée' => 'bg-green-100 text-green-800',
                                                'terminée' => 'bg-green-100 text-green-800',
                                                'annulée' => 'bg-red-100 text-red-800',
                                                'reportee' => 'bg-orange-100 text-orange-800'
                                            ];
                                            $statusLabels = [
                                                'programmée' => 'Programmée',
                                                'planifiee' => 'Planifiée',
                                                'en_cours' => 'En cours',
                                                'en cours' => 'En cours',
                                                'terminee' => 'Terminée',
                                                'terminée' => 'Terminée',
                                                'terminée' => 'Terminée',
                                                'annulée' => 'Annulée',
                                                'reportee' => 'Reportée'
                                            ];
                                            $statusIcons = [
                                                'programmée' => 'fas fa-clock',
                                                'planifiee' => 'fas fa-clock',
                                                'en_cours' => 'fas fa-play',
                                                'en cours' => 'fas fa-play',
                                                'terminee' => 'fas fa-check',
                                                'terminée' => 'fas fa-check',
                                                'terminée' => 'fas fa-check',
                                                'annulée' => 'fas fa-times',
                                                'reportee' => 'fas fa-pause'
                                            ];
                                            
                                            // Vérifier si la réunion est en cours maintenant
                                            $now = new DateTime();
                                            $reunionDateTime = new DateTime(($reunion['date_reunion'] ?? $reunion['date']) . ' ' . ($reunion['heure_debut'] ?? $reunion['heure']));
                                            $duree = floatval($reunion['duree'] ?? 1.5); // Durée par défaut 1.5h
                                            $finReunion = clone $reunionDateTime;
                                            $finReunion->add(new DateInterval('PT' . intval($duree * 60) . 'M'));
                                            
                                            // Mise à jour automatique du statut affiché
                                            if ($now < $reunionDateTime) {
                                                $status = 'programmée';
                                            } elseif ($now >= $reunionDateTime && $now <= $finReunion) {
                                                $status = 'en cours';
                                            } else {
                                                $status = 'terminée';
                                            }
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColors[$status] ?? 'bg-gray-100 text-gray-800'; ?>">
                                                <i class="<?php echo $statusIcons[$status] ?? 'fas fa-question'; ?> mr-1"></i>
                                                <?php echo $statusLabels[$status] ?? 'Inconnu'; ?>
                                            </span>
                                            <?php if ($status === 'en cours'): ?>
                                                <div class="text-xs text-yellow-600 mt-1">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    Réunion en cours
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="viewMeeting(<?php echo $reunion['id']; ?>)" 
                                                        class="text-primary hover:text-primary-light" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editMeeting(<?php echo $reunion['id']; ?>)" 
                                                        class="text-warning hover:text-yellow-600" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <button onclick="deleteMeeting(<?php echo $reunion['id']; ?>)" 
                                                        class="text-danger hover:text-red-600" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune réunion trouvée</h3>
                                            <p class="text-gray-500 mb-4">Il n'y a pas de réunions correspondant à vos critères.</p>
                                            <button onclick="openNewMeetingModal()" 
                                                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                                <i class="fas fa-plus mr-2"></i>
                                                Planifier une réunion
                                            </button>
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
                                    <a href="?page=reunions&page_num=<?php echo $pagination['current_page'] - 1; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Précédent
                                    </a>
                                <?php endif; ?>
                                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                    <a href="?page=reunions&page_num=<?php echo $pagination['current_page'] + 1; ?>" 
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                                            <a href="?page=reunions&page_num=<?php echo $pagination['current_page'] - 1; ?>" 
                                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $start_page = max(1, $pagination['current_page'] - 2);
                                        $end_page = min($pagination['total_pages'], $pagination['current_page'] + 2);
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <a href="?page=reunions&page_num=<?php echo $i; ?>" 
                                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $pagination['current_page'] ? 'bg-primary border-primary text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                                            <a href="?page=reunions&page_num=<?php echo $pagination['current_page'] + 1; ?>" 
                                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
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

        <!-- Vue Calendrier -->
        <div id="calendar-view" class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up" style="display: none;">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-calendar-alt mr-2 text-primary"></i>
                    Calendrier des réunions
                </h3>
            </div>
            
            <div class="p-6">
                <!-- Contrôles du calendrier -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <button onclick="previousMonth()" class="p-2 rounded-lg border border-gray-300 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h2 id="current-month" class="text-xl font-semibold text-gray-900">
                            <?php echo date('F Y'); ?>
                        </h2>
                        <button onclick="nextMonth()" class="p-2 rounded-lg border border-gray-300 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                    <button onclick="goToToday()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                        Aujourd'hui
                    </button>
                </div>

                <!-- Grille du calendrier -->
                <div class="grid grid-cols-7 gap-1">
                    <!-- En-têtes des jours -->
                    <div class="p-3 text-center text-sm font-medium text-gray-500">Dim</div>
                    <div class="p-3 text-center text-sm font-medium text-gray-500">Lun</div>
                    <div class="p-3 text-center text-sm font-medium text-gray-500">Mar</div>
                    <div class="p-3 text-center text-sm font-medium text-gray-500">Mer</div>
                    <div class="p-3 text-center text-sm font-medium text-gray-500">Jeu</div>
                    <div class="p-3 text-center text-sm font-medium text-gray-500">Ven</div>
                    <div class="p-3 text-center text-sm font-medium text-gray-500">Sam</div>
                </div>
                
                <div id="calendar-grid" class="grid grid-cols-7 gap-1">
                    <!-- Les jours seront générés par JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Modal Nouvelle Réunion -->
    <div id="newMeetingModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 animate-bounce-in">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-plus text-primary mr-2"></i>
                        Planifier une nouvelle réunion
                    </h3>
                    <button onclick="closeNewMeetingModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="newMeetingForm" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="meeting_title" class="block text-sm font-medium text-gray-700 mb-2">
                                Titre de la réunion *
                            </label>
                            <input type="text" id="meeting_title" name="titre" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                   placeholder="Ex: Commission de validation des stages">
                        </div>
                        <div>
                            <label for="meeting_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Date de la réunion *
                            </label>
                            <input type="date" id="meeting_date" name="date_reunion" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="meeting_start" class="block text-sm font-medium text-gray-700 mb-2">
                                Heure de début *
                            </label>
                            <input type="time" id="meeting_start" name="heure_debut" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label for="meeting_end" class="block text-sm font-medium text-gray-700 mb-2">
                                Heure de fin *
                            </label>
                            <input type="time" id="meeting_end" name="heure_fin" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>

                    <div>
                        <label for="meeting_location" class="block text-sm font-medium text-gray-700 mb-2">
                            Lieu de la réunion
                        </label>
                        <input type="text" id="meeting_location" name="lieu"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="Ex: Salle de conférence A">
                    </div>

                    <div>
                        <label for="meeting_description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description / Ordre du jour
                        </label>
                        <textarea id="meeting_description" name="description" rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                  placeholder="Décrivez l'ordre du jour de la réunion..."></textarea>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <button type="button" onclick="closeNewMeetingModal()"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Annuler
                        </button>
                        <button type="submit"
                                class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                            <i class="fas fa-save mr-2"></i>
                            Planifier la réunion
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Variables globales pour le calendrier
        let currentDate = new Date();
        let meetings = <?php echo json_encode($reunions); ?>;
        
        // Gestion des onglets
        function switchTab(tab) {
            // Masquer toutes les vues
            document.getElementById('list-view').style.display = 'none';
            document.getElementById('calendar-view').style.display = 'none';
            
            // Désactiver tous les onglets
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('border-primary', 'text-primary');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Afficher la vue sélectionnée
            if (tab === 'list') {
                document.getElementById('list-view').style.display = 'block';
                document.getElementById('tab-list').classList.remove('border-transparent', 'text-gray-500');
                document.getElementById('tab-list').classList.add('border-primary', 'text-primary');
            } else if (tab === 'calendar') {
                document.getElementById('calendar-view').style.display = 'block';
                document.getElementById('tab-calendar').classList.remove('border-transparent', 'text-gray-500');
                document.getElementById('tab-calendar').classList.add('border-primary', 'text-primary');
                renderCalendar();
            }
        }
        
        // Fonctions du calendrier
        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Mettre à jour le titre du mois
            const monthNames = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 
                               'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
            document.getElementById('current-month').textContent = `${monthNames[month]} ${year}`;
            
            // Obtenir le premier jour du mois et le nombre de jours
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());
            
            const calendarGrid = document.getElementById('calendar-grid');
            calendarGrid.innerHTML = '';
            
            // Générer les jours du calendrier
            for (let i = 0; i < 42; i++) {
                const currentDay = new Date(startDate);
                currentDay.setDate(startDate.getDate() + i);
                
                const dayElement = document.createElement('div');
                dayElement.className = 'min-h-[100px] p-2 border border-gray-200 relative';
                
                // Vérifier si c'est le mois actuel
                const isCurrentMonth = currentDay.getMonth() === month;
                if (!isCurrentMonth) {
                    dayElement.classList.add('bg-gray-50');
                }
                
                // Vérifier si c'est aujourd'hui
                const today = new Date();
                const isToday = currentDay.toDateString() === today.toDateString();
                if (isToday) {
                    dayElement.classList.add('bg-primary/5', 'border-primary');
                }
                
                // Numéro du jour
                const dayNumber = document.createElement('div');
                dayNumber.className = `text-sm font-medium ${isCurrentMonth ? 'text-gray-900' : 'text-gray-400'} ${isToday ? 'text-primary' : ''}`;
                dayNumber.textContent = currentDay.getDate();
                dayElement.appendChild(dayNumber);
                
                // Afficher les réunions pour ce jour
                const dayMeetings = getMeetingsForDate(currentDay);
                dayMeetings.forEach(meeting => {
                    const meetingElement = document.createElement('div');
                    meetingElement.className = 'text-xs p-1 mt-1 rounded bg-primary text-white cursor-pointer hover:bg-primary-light transition-colors';
                    meetingElement.textContent = meeting.titre || 'Réunion';
                    meetingElement.title = `${meeting.titre || 'Réunion'} - ${meeting.heure_debut || meeting.heure || ''} - ${meeting.lieu || ''}`;
                    meetingElement.onclick = () => viewMeeting(meeting.id);
                    dayElement.appendChild(meetingElement);
                });
                
                calendarGrid.appendChild(dayElement);
            }
        }
        
        function getMeetingsForDate(date) {
            return meetings.filter(meeting => {
                const meetingDate = new Date(meeting.date_reunion || meeting.date);
                return meetingDate.toDateString() === date.toDateString();
            });
        }
        
        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }
        
        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }
        
        function goToToday() {
            currentDate = new Date();
            renderCalendar();
        }
        
        // Gestion des modals
        function openNewMeetingModal() {
            document.getElementById('newMeetingModal').classList.remove('hidden');
            document.getElementById('newMeetingModal').classList.add('flex');
        }

        function closeNewMeetingModal() {
            document.getElementById('newMeetingModal').classList.add('hidden');
            document.getElementById('newMeetingModal').classList.remove('flex');
            document.getElementById('newMeetingForm').reset();
        }

        // Actions sur les réunions
        function viewMeeting(id) {
            // Rediriger vers la page de détails de la réunion
            window.location.href = `?page=reunion_details&id=${id}`;
        }

        function editMeeting(id) {
            // Ouvrir le modal d'édition avec les données pré-remplies
            console.log('Édition de la réunion:', id);
        }

        function startMeeting(id) {
            if (confirm('Démarrer cette réunion maintenant ?')) {
                // Mettre à jour le statut de la réunion
                fetch('./assets/traitements/update_meeting_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `meeting_id=${id}&status=en_cours`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors du démarrage de la réunion');
                    }
                });
            }
        }

        function deleteMeeting(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette réunion ?')) {
                fetch('./assets/traitements/delete_meeting.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `meeting_id=${id}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression');
                    }
                });
            }
        }

        function exportMeetings() {
            window.open('./assets/traitements/export_meetings.php', '_blank');
        }

        // Fonction pour mettre à jour automatiquement les statuts des réunions
        function updateMeetingStatuses() {
            // Afficher un indicateur de chargement
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Mise à jour...';
            button.disabled = true;
            
            fetch('./assets/traitements/update_meeting_status_auto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Recharger la page pour afficher les nouveaux statuts
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Erreur lors de la mise à jour: ' + (data.error || 'Erreur inconnue'), 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Une erreur est survenue lors de la mise à jour des statuts.', 'error');
            })
            .finally(() => {
                // Restaurer le bouton
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }

        // Fonction pour vérifier automatiquement les statuts toutes les minutes
        function startAutoStatusCheck() {
            setInterval(() => {
                // Vérifier silencieusement les statuts en arrière-plan
                fetch('./assets/traitements/update_meeting_status_auto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.updated_count > 0) {
                        // Si des statuts ont été mis à jour, recharger la page
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Erreur vérification automatique:', error);
                });
            }, 60000); // Vérifier toutes les minutes
        }

        // Gestion du formulaire de nouvelle réunion
        document.getElementById('newMeetingForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Ajouter l'action pour indiquer qu'il faut aussi enregistrer dans le calendrier
            formData.append('add_to_calendar', '1');
            formData.append('send_notifications', '1');
            
            fetch('./assets/traitements/create_meeting.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeNewMeetingModal();
                    
                    // Mettre à jour la liste des réunions pour le calendrier
                    if (data.meeting) {
                        meetings.push(data.meeting);
                    }
                    
                    // Afficher un message de succès
                    showNotification('Réunion créée avec succès ! Les membres de la commission ont été notifiés.', 'success');
                    
                    // Recharger la page après un délai
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Erreur lors de la création de la réunion: ' + (data.error || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la création de la réunion.');
            });
        });
        
        // Fonction pour afficher les notifications
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'success' ? 'bg-green-500 text-white' : 
                type === 'error' ? 'bg-red-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
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

        // Fermer les modals en cliquant en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('newMeetingModal');
            if (event.target === modal) {
                closeNewMeetingModal();
            }
        }

        // Démarrer la vérification automatique des statuts au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            // Vérifier les statuts une première fois au chargement
            updateMeetingStatuses();
            
            // Démarrer la vérification automatique
            startAutoStatusCheck();
        });
    </script>

</body>
</html>