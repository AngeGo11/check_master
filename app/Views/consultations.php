<?php
// Vérification de sécurité
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_groups'])) {
    header('Location: ../pageConnexion.php');
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

$historiques = $controller->getHistory();

// Récupération du nom de l'utilisateur actuel
$user_name = $_SESSION['user_fullname'] ?? $_SESSION['user_fullname'] ?? 'Utilisateur';

// Groupement des comptes rendus par titre
$comptesRendusGroupes = [];
if (!empty($comptes_rendus)) {
    foreach ($comptes_rendus as $compteRendu) {
        $titre = $compteRendu['nom_cr'] ?? 'Sans titre';
        if (!isset($comptesRendusGroupes[$titre])) {
            $comptesRendusGroupes[$titre] = [
                'titre' => $titre,
                'nombre_total' => 0,
                'date_creation' => $compteRendu['date_cr'] ?? 'now',
                'auteur' => $user_name, // Utilisateur actuel
                'rapports' => []
            ];
        }
        $comptesRendusGroupes[$titre]['nombre_total']++;
        $comptesRendusGroupes[$titre]['rapports'][] = $compteRendu;
    }
}

// Récupération des vraies données de la commission pour le premier rapport (si disponible)
$commission_members = [];
$evaluation_stats = [];
if (!empty($rapports)) {
    $first_rapport = $rapports[0];
    $commission_members = $controller->getCommissionMembers($first_rapport['id_rapport_etd']);
    $evaluation_stats = $controller->getEvaluationStats($first_rapport['id_rapport_etd']);
}
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
                        primary: '#1a5276', // Bleu de la sidebar
                        'primary-light': '#2980b9', // Bleu plus clair
                        'primary-lighter': '#3498db', // Encore plus clair
                        secondary: '#ff8c00', // Orange de l'app
                        accent: '#4caf50', // Vert de l'app
                        success: '#4caf50', // Vert
                        warning: '#f39c12', // Jaune/Orange
                        danger: '#e74c3c', // Rouge
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

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }

            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(26, 82, 118, 0.1), 0 10px 10px -5px rgba(26, 82, 118, 0.04);
        }

        /* Styles pour le design inspiré de l'image */
        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(#1a5276 0deg, #1a5276 var(--progress), #e5e7eb var(--progress), #e5e7eb 360deg);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .progress-circle::before {
            content: '';
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: white;
            position: absolute;
        }

        .progress-text {
            position: relative;
            z-index: 1;
            font-weight: bold;
            color: #1a5276;
        }

        .task-item {
            transition: all 0.3s ease;
        }

        .task-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .evaluation-status {
            transition: all 0.3s ease;
        }

        .evaluation-status.completed {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
        }

        .evaluation-status.pending {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">

        <!-- Contenu principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- KPI Cards avec design inspiré de l'image -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Carte principale avec gradient bleu -->
                <div class="col-span-1 md:col-span-2">
                    <div class="bg-gradient-to-r from-primary-light to-primary rounded-2xl shadow-lg p-6 text-white transform transition-all duration-300 hover:scale-105 animate-slide-up">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-bold mb-2">Aujourd'hui</h2>
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <p class="text-4xl font-bold"><?php echo $statistics['rapports_en_attente'] ?? 0; ?></p>
                                        <p class="text-sm opacity-90">Rapports en attente</p>
                                    </div>
                                    <div>
                                        <p class="text-4xl font-bold"><?php echo $statistics['total_rapports'] ?? 0; ?></p>
                                        <p class="text-sm opacity-90">Total rapports</p>
                                    </div>
                                </div>
                            </div>
                            <div class="progress-circle" style="--progress: <?php
                                                                            $total = $statistics['total_rapports'] ?? 1;
                                                                            $completed = $statistics['rapports_valides'] ?? 0;
                                                                            echo ($completed / $total) * 360;
                                                                            ?>deg">
                                <span class="progress-text text-lg">
                                    <?php echo round(($completed / $total) * 100); ?>%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte des comptes rendus -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-accent transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.1s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-accent"><?php echo $statistics['total_cr']; ?></p>
                            <p class="text-sm font-medium text-gray-600 mt-1">Comptes rendus</p>
                        </div>
                        <div class="bg-accent/10 rounded-full p-4">
                            <i class="fas fa-file-signature text-2xl text-accent"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Rapports avec système de comptage de votes -->
            <div class="bg-white rounded-2xl shadow-lg mb-8 animate-fade-in">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-primary/10 rounded-lg p-2 mr-3">
                                <i class="fas fa-file-text text-primary"></i>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900">Consultation des Rapports</h2>
                        </div>
                        <?php if ($responsable_compte_rendu > 0): ?>
                            <a href="?page=redaction_compte_rendu" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center">
                                <i class="fas fa-pen-nib mr-2"></i> Rédiger un compte rendu
                            </a>
                        <?php endif; ?>
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

                <!-- Liste des rapports avec design inspiré de l'image -->
                <div class="p-6">
                    <?php if (!empty($rapports)): ?>
                        <div class="space-y-4">
                            <?php foreach ($rapports as $rapport): ?>
                                <?php
                                // Récupérer les vraies données d'évaluation pour ce rapport
                                $rapport_commission_members = $controller->getCommissionMembers($rapport['id_rapport_etd']);
                                $rapport_evaluation_stats = $controller->getEvaluationStats($rapport['id_rapport_etd']);

                                // Filtrer les évaluations complétées
                                $completed_evaluations = array_filter($rapport_commission_members, function ($member) {
                                    return $member['a_evalue'];
                                });
                                $total_members = count($rapport_commission_members);
                                $completed_count = count($completed_evaluations);
                                $progress_percentage = $total_members > 0 ? round(($completed_count / $total_members) * 100) : 0;
                                ?>

                                <div class="task-item bg-white border border-gray-200 rounded-xl p-6 hover:bg-gray-50 transition-all duration-300">
                                    <div class="flex items-start justify-between">
                                        <!-- Checkbox et informations principales -->
                                        <div class="flex items-start space-x-4 flex-1">
                                            <div class="flex-shrink-0 mt-1">
                                                <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center">
                                                    <?php if ($rapport['statut_rapport'] === 'Validé'): ?>
                                                        <i class="fas fa-check text-green-500 text-sm"></i>
                                                    <?php elseif ($rapport['statut_rapport'] === 'Rejeté'): ?>
                                                        <i class="fas fa-times text-red-500 text-sm"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                                    Rapport #<?php echo $rapport['id_rapport_etd']; ?> -
                                                    <?php echo htmlspecialchars($rapport['nom_etd'] . ' ' . $rapport['prenom_etd']); ?>
                                                </h3>
                                                <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($rapport['theme_memoire']); ?></p>

                                                <!-- Système de comptage de votes -->
                                                <div class="flex items-center space-x-4 mb-4">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-sm font-medium text-gray-700">Évaluations:</span>
                                                        <span class="text-sm text-gray-600"><?php echo $completed_count; ?>/<?php echo $total_members; ?></span>
                                                    </div>
                                                    <div class="flex-1 max-w-xs">
                                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                                            <div class="bg-primary h-2 rounded-full transition-all duration-300"
                                                                style="width: <?php echo $progress_percentage; ?>%"></div>
                                                        </div>
                                                    </div>
                                                    <span class="text-sm font-medium text-primary"><?php echo $progress_percentage; ?>%</span>
                                                </div>

                                                <!-- Statut et date -->
                                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $rapport['statut_rapport'] === 'Validé' ? 'bg-green-100 text-green-800' : ($rapport['statut_rapport'] === 'Rejeté' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                                        <?php echo $rapport['statut_rapport']; ?>
                                                    </span>
                                                    <span>Déposé le <?php echo $rapport['date_depot'] ? date('d/m/Y', strtotime($rapport['date_depot'])) : '-'; ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center space-x-2 ml-4">
                                            <?php if ($rapport['fichier_rapport']): ?>
                                                <a href="?page=consultations&action=download_rapport&id=<?php echo $rapport['id_rapport_etd']; ?>"
                                                    class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors" title="Télécharger">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <button class="text-purple-600 hover:text-purple-900 p-2 rounded-lg hover:bg-purple-50 transition-colors show-evaluations-btn"
                                                data-rapport-id="<?php echo $rapport['id_rapport_etd']; ?>"
                                                title="Voir les détails des évaluations">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Section des évaluations (cachée par défaut) -->
                                    <div class="evaluations-section hidden mt-6 pt-6 border-t border-gray-200">
                                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                            <i class="fas fa-users mr-2 text-primary"></i>
                                            Évaluations des membres de la commission
                                        </h4>

                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            <?php foreach ($rapport_commission_members as $member): ?>
                                                <?php
                                                $is_completed = $member['a_evalue'];
                                                ?>

                                                <div class="evaluation-status p-4 rounded-lg <?php echo $is_completed ? 'completed' : 'pending'; ?>">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <h5 class="font-semibold">
                                                            <?php echo htmlspecialchars($member['nom_ens'] . ' ' . $member['prenoms_ens']); ?>
                                                        </h5>
                                                        <span class="text-xs">
                                                            <?php if ($is_completed): ?>
                                                                <i class="fas fa-check-circle mr-1"></i>Terminé
                                                            <?php else: ?>
                                                                <i class="fas fa-clock mr-1"></i>Pas encore évalué
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>

                                                    <?php if ($is_completed): ?>
                                                        <div class="text-sm opacity-90">
                                                            <p class="mb-2"><strong>Décision:</strong> <?php echo $member['decision'] ?? 'Non spécifiée'; ?></p>
                                                            <p class="mb-2"><strong>Date:</strong> <?php echo $member['date_validation'] ? date('d/m/Y', strtotime($member['date_validation'])) : '-'; ?></p>
                                                            <p><strong>Commentaire:</strong></p>
                                                            <div class="mt-1 p-2 bg-white/20 rounded text-xs">
                                                                <?php echo htmlspecialchars($member['com_validation'] ?? 'Aucun commentaire'); ?>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="text-sm opacity-90">Pas encore évalué</p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-12">
                            <i class="fas fa-file-alt text-6xl text-gray-300 mb-4"></i>
                            <p class="text-xl font-medium text-gray-500">Aucun rapport trouvé</p>
                        </div>
                    <?php endif; ?>
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
                        <div class="flex items-center space-x-3">
                            <button onclick="showEmailHistory()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center">
                                <i class="fas fa-history mr-2"></i> Historique des emails
                            </button>
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

                <!-- Tableau des comptes rendus -->
                <div class="overflow-x-auto">
                    
                    
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Titre du compte rendu</th>
                                <th scope="col" class="px-6 py-3">Auteur</th>
                                <th scope="col" class="px-6 py-3">Date de création</th>
                                <th scope="col" class="px-6 py-3">Rapports associés</th>
                                <th scope="col" class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($comptesRendusGroupes)) : ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        <i class="fas fa-inbox text-2xl mb-2 opacity-50"></i>
                                        <p>Aucun compte rendu créé pour le moment</p>
                                        <?php if (!empty($comptes_rendus)): ?>
                                            <p class="text-sm text-gray-400 mt-2">
                                                Données brutes disponibles: <?php echo count($comptes_rendus); ?> éléments
                                            </p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach (array_slice($comptesRendusGroupes, 0, 5) as $groupe) : ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-file-signature text-blue-600"></i>
                                                <span><?php echo htmlspecialchars($groupe['titre'] ?? ''); ?></span>
                                                <?php if ($groupe['nombre_total'] > 1) : ?>
                                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                                        <?php echo $groupe['nombre_total']; ?> versions
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-user-edit text-green-600"></i>
                                                <span class="font-medium text-gray-700"><?php echo htmlspecialchars($groupe['auteur']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-calendar-alt text-purple-600"></i>
                                                <span><?php echo date('d/m/Y H:i', strtotime($groupe['date_creation'])); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-file-alt text-orange-600"></i>
                                                <span class="text-sm font-medium text-gray-700">
                                                    <?php
                                                    // Compter le nombre total de rapports associés à ce titre
                                                    $totalRapports = 0;
                                                    foreach ($groupe['rapports'] as $rapport) {
                                                        $totalRapports += $rapport['nombre_rapports'] ?? 1;
                                                    }
                                                    echo $totalRapports . ' rapport(s)';
                                                    ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <!-- Bouton pour voir tous les comptes rendus de ce titre -->
                                                <button type="button"
                                                    onclick="showCompteRenduDetails('<?php echo htmlspecialchars($groupe['titre']); ?>')"
                                                    class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                                    title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <!-- Bouton pour télécharger le dernier compte rendu -->
                                                <?php
                                                $dernierCompteRendu = end($groupe['rapports']);
                                                if ($dernierCompteRendu && isset($dernierCompteRendu['id_cr'])) :
                                                ?>
                                                    <button onclick="handleFileAction('../public/assets/traitements/gestion_comptes_rendus.php?action=download_cr&id=<?php echo $dernierCompteRendu['id_cr']; ?>', 'download')"
                                                        class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition-colors"
                                                        title="Télécharger le dernier">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                    
                                                    
                                                <?php endif; ?>

                                                <!-- Bouton pour envoyer par email -->
                                                <?php if ($dernierCompteRendu && isset($dernierCompteRendu['id_cr']) && $dernierCompteRendu['id_cr'] > 0): ?>
                                                    <button type="button"
                                                        onclick="console.log('Clic sur bouton email - titre: <?php echo addslashes($groupe['titre']); ?>, id: <?php echo $dernierCompteRendu['id_cr']; ?>'); console.log('Appel de showEmailModal...'); try { showEmailModal('<?php echo htmlspecialchars($groupe['titre']); ?>', <?php echo $dernierCompteRendu['id_cr']; ?>); } catch(e) { console.error('Erreur lors de l\'appel de showEmailModal:', e); alert('Erreur JavaScript: ' + e.message); }"
                                                        class="text-purple-600 hover:text-purple-800 p-2 rounded-lg hover:bg-purple-50 transition-colors"
                                                        title="Envoyer par email">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button"
                                                        onclick="console.log('Clic sur bouton email désactivé - aucun compte rendu disponible')"
                                                        class="text-gray-400 p-2 rounded-lg cursor-not-allowed"
                                                        title="Aucun compte rendu disponible pour l'envoi">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <!-- Bouton pour supprimer le groupe -->
                                                <button type="button"
                                                    onclick="deleteCompteRenduGroup('<?php echo htmlspecialchars($groupe['titre']); ?>')"
                                                    class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors"
                                                    title="Supprimer le groupe">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
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

    <!-- Scripts -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script src="https://unpkg.com/mammoth@1.4.21/mammoth.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html-docx-js@0.4.1/dist/html-docx.min.js"></script>

    <script>
        // Variables globales
        let selectedRapports = new Set();

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

            // Gestion des boutons d'affichage des évaluations
            initEvaluationHandlers();
        });

        // Initialiser les gestionnaires d'évaluations
        function initEvaluationHandlers() {
            document.querySelectorAll('.show-evaluations-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const rapportId = this.getAttribute('data-rapport-id');
                    const taskItem = this.closest('.task-item');
                    const evaluationsSection = taskItem.querySelector('.evaluations-section');

                    // Toggle de l'affichage
                    if (evaluationsSection.classList.contains('hidden')) {
                        // Charger les données d'évaluation via AJAX si pas encore chargées
                        if (!evaluationsSection.hasAttribute('data-loaded')) {
                            loadEvaluationData(rapportId, evaluationsSection);
                        }

                        evaluationsSection.classList.remove('hidden');
                        this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                        this.title = 'Masquer les évaluations';
                    } else {
                        evaluationsSection.classList.add('hidden');
                        this.innerHTML = '<i class="fas fa-users"></i>';
                        this.title = 'Voir les évaluations';
                    }
                });
            });
        }

        // Charger les données d'évaluation via AJAX
        function loadEvaluationData(rapportId, evaluationsSection) {
            // Afficher un indicateur de chargement
            evaluationsSection.innerHTML = `
                    <div class="flex items-center justify-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                        <span class="ml-2 text-gray-600">Chargement des évaluations...</span>
                    </div>
                `;

            const url = `ajax_consultations.php?action=getEvaluationData&rapport_id=${rapportId}`;
            console.log('URL de la requête AJAX:', url);

            // Faire la requête AJAX
            fetch(url)
                .then(response => {
                    console.log('Statut de la réponse:', response.status);
                    console.log('Headers de la réponse:', response.headers);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    return response.text().then(text => {
                        console.log('Réponse brute:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Erreur de parsing JSON:', e);
                            throw new Error('Réponse non-JSON reçue: ' + text.substring(0, 200));
                        }
                    });
                })
                .then(data => {
                    console.log('Données reçues:', data);

                    if (data.success) {
                        renderEvaluationData(data.commission_members, evaluationsSection);
                        evaluationsSection.setAttribute('data-loaded', 'true');
                    } else {
                        console.error('Erreur dans la réponse:', data.error);
                        evaluationsSection.innerHTML = `
                                <div class="text-center py-8 text-red-600">
                                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                    <p>Erreur lors du chargement des évaluations: ${data.error || 'Erreur inconnue'}</p>
                </div>
            `;
                    }
                })
                .catch(error => {
                    console.error('Erreur AJAX détaillée:', error);
                    evaluationsSection.innerHTML = `
                            <div class="text-center py-8 text-red-600">
                                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                <p>Erreur de connexion: ${error.message}</p>
                            </div>
                        `;
                });
        }

        // Rendre les données d'évaluation
        function renderEvaluationData(commissionMembers, evaluationsSection) {
            let html = `
                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-users mr-2 text-primary"></i>
                    Évaluations des membres de la commission
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            `;

            commissionMembers.forEach(member => {
                const isCompleted = member.a_evalue;
                const statusClass = isCompleted ? 'completed' : 'pending';
                const statusText = isCompleted ? 'Terminé' : 'Pas encore évalué';
                const statusIcon = isCompleted ? 'fa-check-circle' : 'fa-clock';

                html += `
                    <div class="evaluation-status p-4 rounded-lg ${statusClass}">
                        <div class="flex items-center justify-between mb-2">
                            <h5 class="font-semibold">
                                ${member.nom_ens} ${member.prenoms_ens}
                            </h5>
                            <span class="text-xs">
                                <i class="fas ${statusIcon} mr-1"></i>${statusText}
                            </span>
                        </div>
                `;

                if (isCompleted) {
                    html += `
                        <div class="text-sm opacity-90">
                            <p class="mb-2"><strong>Décision:</strong> ${member.decision || 'Non spécifiée'}</p>
                            <p class="mb-2"><strong>Date:</strong> ${member.date_validation ? new Date(member.date_validation).toLocaleDateString('fr-FR') : '-'}</p>
                            <p><strong>Commentaire:</strong></p>
                            <div class="mt-1 p-2 bg-white/20 rounded text-xs">
                                ${member.com_validation || 'Aucun commentaire'}
                            </div>
                        </div>
                    `;
                } else {
                    html += `<p class="text-sm opacity-90">Pas encore évalué</p>`;
                }

                html += `</div>`;
            });

            html += `</div>`;
            evaluationsSection.innerHTML = html;
        }

        // Fonction pour afficher les détails d'un compte rendu
        function showCompteRenduDetails(titre) {
            // Créer une modal pour afficher les détails
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Détails du compte rendu: ${titre}</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="compte-rendu-details-content">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                            <span class="ml-2 text-gray-600">Chargement des détails...</span>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Charger les détails via AJAX
            fetch(`ajax_consultations.php?action=getCompteRenduDetails&titre=${encodeURIComponent(titre)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderCompteRenduDetails(data.details, document.getElementById('compte-rendu-details-content'));
                    } else {
                        document.getElementById('compte-rendu-details-content').innerHTML = `
                            <div class="text-center py-8 text-red-600">
                                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                <p>Erreur: ${data.error || 'Impossible de charger les détails'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('compte-rendu-details-content').innerHTML = `
                        <div class="text-center py-8 text-red-600">
                            <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                            <p>Erreur de connexion: ${error.message}</p>
                        </div>
                    `;
                });
        }

        // Fonction pour afficher le modal d'envoi d'email
        function showEmailModal(titre, crId) {
            console.log('=== SHOW EMAIL MODAL START ===');
            console.log('showEmailModal appelé avec:', titre, crId); // Debug
            console.log('Type de titre:', typeof titre);
            console.log('Type de crId:', typeof crId);
            
            // Vérifier que les paramètres sont valides
            if (!titre || !crId || crId <= 0) {
                console.error('ERREUR: Paramètres invalides pour l\'envoi d\'email');
                console.error('titre:', titre);
                console.error('crId:', crId);
                alert('Erreur: Paramètres invalides pour l\'envoi d\'email');
                return;
            }
            
            // Échapper les caractères spéciaux dans le titre pour éviter les erreurs JavaScript
            const titreEscaped = titre.replace(/'/g, "\\'").replace(/"/g, '\\"');
            console.log('Titre échappé:', titreEscaped);
            
            console.log('Création de la modale...');
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            
            const modalHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Envoyer le compte rendu par email</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    
                    <form id="email-form" onsubmit="sendCompteRenduEmail(event, '${titreEscaped}', ${crId})">
                        <div class="mb-4">
                            <label for="email-to" class="block text-sm font-medium text-gray-700 mb-2">
                                Adresse email du destinataire *
                            </label>
                            <input type="email" id="email-to" name="email" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="exemple@email.com">
                        </div>
                        
                        <div class="mb-4">
                            <label for="email-subject" class="block text-sm font-medium text-gray-700 mb-2">
                                Objet de l'email
                            </label>
                            <input type="text" id="email-subject" name="subject"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="Compte rendu - ${titreEscaped}"
                                value="Compte rendu - ${titreEscaped}">
                        </div>
                        
                        <div class="mb-4">
                            <label for="email-message" class="block text-sm font-medium text-gray-700 mb-2">
                                Message additionnel (optionnel)
                            </label>
                            <textarea id="email-message" name="message" rows="4"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                placeholder="Ajoutez un message personnalisé..."></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="this.closest('.fixed').remove()"
                                class="px-4 py-2 text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                Annuler
                            </button>
                            <button type="submit" id="send-email-btn"
                                class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Envoyer
                            </button>
                        </div>
                    </form>
                </div>
            `;
            
            modal.innerHTML = modalHTML;
            console.log('Modale créée, ajout au DOM...');
            document.body.appendChild(modal);
        }

        // Fonction pour envoyer le compte rendu par email
        function sendCompteRenduEmail(event, titre, crId) {
            event.preventDefault();
            
            const form = event.target;
            const email = form.querySelector('#email-to').value;
            const subject = form.querySelector('#email-subject').value;
            const message = form.querySelector('#email-message').value;
            const sendBtn = form.querySelector('#send-email-btn');
            
            // Désactiver le bouton et afficher l'indicateur de chargement
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Envoi en cours...';
            
            fetch('ajax_consultations.php?action=sendCompteRenduEmail', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cr_id: crId,
                    titre: titre,
                    email: email,
                    subject: subject,
                    message: message
                })
            })
            .then(response => {
                console.log('Réponse reçue, statut:', response.status);
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Compte rendu envoyé avec succès !');
                    document.querySelector('.fixed').remove();
                } else {
                    alert('Erreur: ' + (data.error || 'Impossible d\'envoyer l\'email'));
                    // Réactiver le bouton
                    sendBtn.disabled = false;
                    sendBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Envoyer';
                }
            })
            .catch(error => {
                alert('Erreur de connexion: ' + error.message);
                // Réactiver le bouton
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Envoyer';
            });
        }

        // Fonction pour supprimer un groupe de comptes rendus
        function deleteCompteRenduGroup(titre) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer tous les comptes rendus avec le titre "${titre}" ?`)) {
                fetch('ajax_consultations.php?action=deleteCompteRenduGroup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ titre: titre })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Groupe de comptes rendus supprimé avec succès');
                        location.reload();
                    } else {
                        alert('Erreur: ' + (data.error || 'Impossible de supprimer le groupe'));
                    }
                })
                .catch(error => {
                    alert('Erreur de connexion: ' + error.message);
                });
            }
        }

        // Fonction pour rendre les détails d'un compte rendu
        function renderCompteRenduDetails(details, container) {
            let html = `
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900">Informations générales</h4>
                            ${details.rapports && details.rapports.length > 0 ? `
                                <button onclick="showCompteRenduPreview(${details.rapports[0].id_cr}, '${details.titre}')" 
                                        class="text-indigo-600 hover:text-indigo-800 p-2 rounded-lg hover:bg-indigo-50 transition-colors"
                                        title="Voir l'aperçu">
                                    <i class="fas fa-search mr-1"></i>Aperçu
                                </button>
                            ` : ''}
                        </div>
                        <p><strong>Titre:</strong> ${details.titre}</p>
                        <p><strong>Nombre de versions:</strong> ${details.nombre_total}</p>
                        <p><strong>Date de création:</strong> ${new Date(details.date_creation).toLocaleDateString('fr-FR')}</p>
                        <p><strong>Auteur:</strong> ${details.auteur}</p>
                    </div>
                    
                   
            `;

            details.rapports.forEach((rapport, index) => {
                html += `
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <p><strong>Version ${index + 1}:</strong></p>
                        <p><strong>Étudiant:</strong> ${rapport.nom_etd} ${rapport.prenom_etd}</p>
                        <p><strong>Rapport:</strong> ${rapport.theme_memoire || 'N/A'}</p>
                        <p><strong>Date:</strong> ${rapport.date_cr ? new Date(rapport.date_cr).toLocaleDateString('fr-FR') : 'N/A'}</p>
                        
                    </div>
                `;
            });

            html += `
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML = html;
        }

        // Fonction pour supprimer un compte rendu individuel
        function deleteCompteRendu(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce compte rendu ?')) {
                fetch('ajax_consultations.php?action=deleteCompteRendu', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Compte rendu supprimé avec succès');
                        location.reload();
                    } else {
                        alert('Erreur: ' + (data.error || 'Impossible de supprimer le compte rendu'));
                    }
                })
                .catch(error => {
                    alert('Erreur de connexion: ' + error.message);
                });
            }
        }

        // Fonction pour afficher l'aperçu d'un compte rendu
        function showCompteRenduPreview(crId, titre) {
            console.log('=== SHOW COMPTE RENDU PREVIEW START ===');
            console.log('showCompteRenduPreview appelé avec:', crId, titre);
            
            // Vérifier que les paramètres sont valides
            if (!crId || crId <= 0) {
                console.error('ERREUR: ID du compte rendu invalide');
                alert('Erreur: ID du compte rendu invalide');
                return;
            }
            
            // Créer la modal d'aperçu
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            
            const modalHTML = `
                <div class="bg-white rounded-lg max-w-6xl w-full mx-4 max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <h3 class="text-xl font-bold text-gray-900">Aperçu du compte rendu: ${titre}</h3>
                        <div class="flex items-center space-x-2">
                            <button onclick="downloadCompteRenduPreview(${crId})" 
                                    class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition-colors"
                                    title="Télécharger">
                                <i class="fas fa-download"></i>
                            </button>
                            <button onclick="this.closest('.fixed').remove()" 
                                    class="text-gray-500 hover:text-gray-700 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div id="preview-content" class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                            <span class="ml-2 text-gray-600">Chargement de l'aperçu...</span>
                        </div>
                    </div>
                </div>
            `;
            
            modal.innerHTML = modalHTML;
            document.body.appendChild(modal);
            
            // Charger l'aperçu via AJAX
            fetch(`ajax_consultations.php?action=getCompteRenduPreview&id=${crId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderCompteRenduPreview(data.content, data.fileType, document.getElementById('preview-content'), crId);
                    } else {
                        document.getElementById('preview-content').innerHTML = `
                            <div class="text-center py-8 text-red-600">
                                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                <p>Erreur: ${data.error || 'Impossible de charger l\'aperçu'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors du chargement de l\'aperçu:', error);
                    document.getElementById('preview-content').innerHTML = `
                        <div class="text-center py-8 text-red-600">
                            <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                            <p>Erreur de connexion: ${error.message}</p>
                        </div>
                    `;
                });
        }

        // Fonction pour télécharger depuis l'aperçu
        function downloadCompteRenduPreview(crId) {
            const downloadUrl = `../public/assets/traitements/gestion_comptes_rendus.php?action=download_cr&id=${crId}`;
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = ''; // Force le téléchargement
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Fonction pour rendre l'aperçu du compte rendu
        function renderCompteRenduPreview(content, fileType, container, crId) {
            if (fileType === 'pdf') {
                // Pour les PDF, utiliser une URL directe vers le fichier
                const pdfUrl = `../public/assets/traitements/gestion_comptes_rendus.php?action=view_cr&id=${crId}`;
                container.innerHTML = `
                    <div class="w-full h-full">
                        <div class="flex items-center justify-between mb-4">
                            <h5 class="text-lg font-semibold text-gray-900">Aperçu du PDF</h5>
                            <div class="flex space-x-2">
                                <button onclick="downloadCompteRenduPreview(${crId})" 
                                        class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition-colors"
                                        title="Télécharger">
                                    <i class="fas fa-download mr-1"></i>Télécharger
                                </button>
                                <button onclick="window.open('${pdfUrl}', '_blank')" 
                                        class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                        title="Ouvrir dans un nouvel onglet">
                                    <i class="fas fa-external-link-alt mr-1"></i>Ouvrir
                                </button>
                            </div>
                        </div>
                        <iframe src="${pdfUrl}" 
                                class="w-full h-[calc(90vh-250px)] border border-gray-300 rounded-lg"
                                frameborder="0">
                            <div class="flex items-center justify-center h-full">
                                <div class="text-center">
                                    <i class="fas fa-file-pdf text-4xl text-red-500 mb-2"></i>
                                    <p class="text-gray-600 mb-4">Votre navigateur ne supporte pas l'affichage des PDF.</p>
                                    <div class="flex justify-center space-x-2">
                                        <button onclick="downloadCompteRenduPreview(${crId})" 
                                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                            <i class="fas fa-download mr-1"></i>Télécharger
                                        </button>
                                        <button onclick="window.open('${pdfUrl}', '_blank')" 
                                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-external-link-alt mr-1"></i>Ouvrir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </iframe>
                    </div>
                `;
            } else if (fileType === 'html') {
                // Pour les fichiers HTML, afficher directement
                container.innerHTML = `
                    <div class="w-full h-full">
                        <div class="bg-white border border-gray-300 rounded-lg p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
                            ${content}
                        </div>
                    </div>
                `;
            } else if (fileType === 'docx' || fileType === 'doc') {
                // Pour les fichiers Word, afficher un message avec option de téléchargement
                container.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-file-word text-6xl text-blue-600 mb-4"></i>
                        <h4 class="text-xl font-semibold text-gray-900 mb-2">Fichier Word détecté</h4>
                        <p class="text-gray-600 mb-6">L'aperçu des fichiers Word n'est pas disponible dans le navigateur.</p>
                        <div class="flex justify-center space-x-4">
                            <button onclick="downloadCompteRenduPreview(${crId})" 
                                    class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                                <i class="fas fa-download mr-2"></i>
                                Télécharger le fichier
                            </button>
                            <button onclick="handleFileAction('../public/assets/traitements/gestion_comptes_rendus.php?action=view_cr&id=${crId}', 'view')" 
                                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                                <i class="fas fa-eye mr-2"></i>
                                Ouvrir dans un nouvel onglet
                            </button>
                        </div>
                    </div>
                `;
            } else {
                // Pour les autres types de fichiers
                container.innerHTML = `
                    <div class="text-center py-12">
                        <i class="fas fa-file text-6xl text-gray-400 mb-4"></i>
                        <h4 class="text-xl font-semibold text-gray-900 mb-2">Type de fichier non supporté</h4>
                        <p class="text-gray-600 mb-6">L'aperçu de ce type de fichier n'est pas disponible.</p>
                        <button onclick="downloadCompteRenduPreview(${crId})" 
                                class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center">
                            <i class="fas fa-download mr-2"></i>
                            Télécharger le fichier
                        </button>
                    </div>
                `;
            }
        }

        // Fonction pour gérer les erreurs de téléchargement/visualisation
        function handleFileAction(url, action) {
            if (action === 'download') {
                // Pour le téléchargement, utiliser une approche directe
                const link = document.createElement('a');
                link.href = url;
                link.download = ''; // Force le téléchargement
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                // Pour la visualisation, ouvrir dans un nouvel onglet
                window.open(url, '_blank');
            }
        }

        // Fonction pour afficher l'historique des emails
        function showEmailHistory() {
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Historique des emails envoyés</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="email-history-content">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                            <span class="ml-2 text-gray-600">Chargement de l'historique...</span>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Utiliser les données PHP directement au lieu d'AJAX
            const historiques = <?php echo json_encode($historiques ?? []); ?>;
            console.log('Historiques PHP:', historiques);
            console.log('Type de historiques:', typeof historiques);
            console.log('Longueur de historiques:', historiques ? historiques.length : 'null');
            renderEmailHistory(historiques, document.getElementById('email-history-content'));
        }

        // Fonction pour rendre l'historique des emails
        function renderEmailHistory(history, container) {
            console.log('renderEmailHistory appelé avec:', history);
            
            if (!history || history.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4 opacity-50"></i>
                        <p class="text-lg">Aucun email envoyé pour le moment</p>
                    </div>
                `;
                return;
            }

            let html = `
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Date d'envoi</th>
                                <th scope="col" class="px-6 py-3">Destinataire</th>
                                <th scope="col" class="px-6 py-3">Sujet</th>
                                <th scope="col" class="px-6 py-3">Compte rendu</th>
                                <th scope="col" class="px-6 py-3">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            history.forEach(log => {
                html += `
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar-alt text-blue-600"></i>
                                <span>${new Date(log.date_envoi).toLocaleString('fr-FR')}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-envelope text-green-600"></i>
                                <span class="font-medium text-gray-700">${log.email_destinataire}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm">${log.sujet || 'Aucun sujet'}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file-signature text-purple-600"></i>
                                <span class="text-sm">ID: ${log.id_cr}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${log.statut === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                ${log.statut === 'success' ? 'Envoyé' : 'Erreur'}
                            </span>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;
            container.innerHTML = html;
        }

        
    </script>
</body>

</html>