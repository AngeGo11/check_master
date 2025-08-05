<?php

// Connexion à la base de données
require_once '../config/config.php';

// Initialisation du contrôleur
require_once '../app/Controllers/MessageController.php';
$controller = new MessageController($pdo);

// Récupération des données via le contrôleur
$data = $controller->index($_SESSION['user_id']);

// Extraction des variables pour la vue
$messages = $data['messages'] ?? [];
$messages_envoyes = $data['messages_envoyes'] ?? [];
$messages_non_lus = $data['messages_non_lus'] ?? [];
$messages_archives = $data['messages_archives'] ?? [];
$contacts = $data['contacts'] ?? [];
$rappels = $data['rappels'] ?? [];
$statistics = $data['statistics'] ?? [
    'total_messages' => 0,
    'evolution_total' => 0,
    'nouveaux_messages' => 0,
    'evolution_nouveaux' => 0,
    'messages_non_lus' => 0,
    'evolution_non_lus' => 0,
    'messages_repondus' => 0,
    'evolution_repondus' => 0
];
$pagination_messages = $data['pagination_messages'] ?? ['current_page' => 1, 'total_pages' => 1, 'total_items' => 0, 'items_per_page' => 5];
$pagination_contacts = $data['pagination_contacts'] ?? ['current_page' => 1, 'total_pages' => 1, 'total_items' => 0, 'items_per_page' => 9];
$pagination_non_lus = $data['pagination_non_lus'] ?? ['current_page' => 1, 'total_pages' => 1, 'total_items' => 0, 'items_per_page' => 5];
$pagination_archives = $data['pagination_archives'] ?? ['current_page' => 1, 'total_pages' => 1, 'total_items' => 0, 'items_per_page' => 5];
$filters_messages = $data['filters_messages'] ?? ['search' => '', 'filter_statut' => '', 'filter_priorite' => '', 'filter_date' => ''];
$filters_contacts = $data['filters_contacts'] ?? ['search_contact' => ''];
$filters_non_lus = $data['filters_non_lus'] ?? ['search' => '', 'filter_priorite' => '', 'filter_date' => ''];
$filters_archives = $data['filters_archives'] ?? ['search' => '', 'filter_priorite' => '', 'filter_date' => ''];

?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Messages - GSCV+</title>
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

        .message-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .contact-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Styles pour les onglets */
        .tab-button {
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            color: #6b7280;
        }

        .tab-button:hover {
            color: #1a5276;
            border-bottom-color: #1a5276;
        }

        .tab-button.active {
            color: #1a5276;
            border-bottom-color: #1a5276;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">


        <!-- Contenu principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-slide-up">
                <!-- Total messages -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des messages</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['total_messages']; ?></p>
                                <div class="flex items-center mt-2">
                                    <i class="fas fa-arrow-up text-accent text-xs mr-1"></i>
                                    <span class="text-xs text-accent font-medium"><?php echo $statistics['evolution_total']; ?>%</span>
                                    <span class="text-xs text-gray-500 ml-1">ce mois</span>
                                </div>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-inbox text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Nouveaux messages -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-secondary overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Nouveaux messages</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['nouveaux_messages']; ?></p>
                                <div class="flex items-center mt-2">
                                    <i class="fas fa-arrow-up text-accent text-xs mr-1"></i>
                                    <span class="text-xs text-accent font-medium"><?php echo $statistics['evolution_nouveaux']; ?>%</span>
                                    <span class="text-xs text-gray-500 ml-1">cette semaine</span>
                                </div>
                            </div>
                            <div class="bg-secondary/10 rounded-full p-4">
                                <i class="fas fa-envelope text-2xl text-secondary"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages non lus -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-yellow-500 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Messages non lus</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['messages_non_lus']; ?></p>
                                <div class="flex items-center mt-2">
                                    <i class="fas fa-arrow-down text-red-500 text-xs mr-1"></i>
                                    <span class="text-xs text-red-500 font-medium"><?php echo $statistics['evolution_non_lus']; ?>%</span>
                                    <span class="text-xs text-gray-500 ml-1">ce mois</span>
                                </div>
                            </div>
                            <div class="bg-yellow-500/10 rounded-full p-4">
                                <i class="fas fa-exclamation-circle text-2xl text-yellow-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages répondus -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-green-500 overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Messages répondus</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['messages_repondus']; ?></p>
                                <div class="flex items-center mt-2">
                                    <i class="fas fa-arrow-up text-green-500 text-xs mr-1"></i>
                                    <span class="text-xs text-green-500 font-medium"><?php echo $statistics['evolution_repondus']; ?>%</span>
                                    <span class="text-xs text-gray-500 ml-1">taux de réponse</span>
                                </div>
                            </div>
                            <div class="bg-green-500/10 rounded-full p-4">
                                <i class="fas fa-envelope-open text-2xl text-green-500"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation par onglets -->
            <div class="mb-8">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap active"
                            data-tab="boites_reception">
                            <i class="fas fa-inbox mr-2"></i>
                            Boîte de réception
                        </button>
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
                            data-tab="messages_non_lus">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Message(s) non(s) lu(s)
                        </button>
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
                            data-tab="messages_envoyes">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Message(s) envoyé(s)
                        </button>
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
                            data-tab="contacts">
                            <i class="fas fa-users mr-2"></i>
                            Contacts
                        </button>
                        <button class="tab-button py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
                            data-tab="message_archives">
                            <i class="fas fa-archive mr-2"></i>
                            Message(s) archivé(s)
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Contenu des onglets -->
            <div id="tab-content">
                <!-- Onglet Boîte de réception -->
                <div id="boites_reception" class="tab-content active">
                    <!-- Section Messages Reçus -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                        <div class="border-l-4 border-primary bg-white rounded-r-lg shadow-sm p-6 mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-inbox text-primary mr-3"></i>
                                Messages Reçus
                            </h2>
                            <p class="text-gray-600">
                                Consultez et gérez vos messages reçus
                            </p>
                        </div>

                        <!-- Filtres pour les messages -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                                <!-- Filtres de recherche -->
                                <div class="flex-1 w-full lg:w-auto">
                                    <form method="GET" id="filter-form-messages" class="flex flex-col sm:flex-row gap-4">
                                        <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'messages'; ?>">
                                        <input type="hidden" name="page_messages" id="page_messages_input" value="<?php echo $pagination_messages['current_page']; ?>">

                                        <!-- Recherche -->
                                        <div class="relative flex-1">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </div>
                                            <input type="text"
                                                name="search"
                                                id="search-input-messages"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                                placeholder="Rechercher un message..."
                                                value="<?php echo htmlspecialchars($filters_messages['search']); ?>">
                                        </div>

                                        <!-- Filtre statut -->
                                        <select name="filter_statut"
                                            class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Statut</option>
                                            <option value="lu" <?php echo $filters_messages['filter_statut'] === 'lu' ? 'selected' : ''; ?>>Lu</option>
                                            <option value="non_lu" <?php echo $filters_messages['filter_statut'] === 'non_lu' ? 'selected' : ''; ?>>Non lu</option>
                                            <option value="repondu" <?php echo $filters_messages['filter_statut'] === 'repondu' ? 'selected' : ''; ?>>Répondu</option>
                                        </select>

                                        <!-- Filtre priorité -->
                                        <select name="filter_priorite"
                                            class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Priorité</option>
                                            <option value="haute" <?php echo $filters_messages['filter_priorite'] === 'haute' ? 'selected' : ''; ?>>Haute</option>
                                            <option value="normale" <?php echo $filters_messages['filter_priorite'] === 'normale' ? 'selected' : ''; ?>>Normale</option>
                                            <option value="basse" <?php echo $filters_messages['filter_priorite'] === 'basse' ? 'selected' : ''; ?>>Basse</option>
                                        </select>

                                        <!-- Filtre date -->
                                        <select name="filter_date"
                                            class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Date</option>
                                            <option value="today" <?php echo $filters_messages['filter_date'] === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                            <option value="week" <?php echo $filters_messages['filter_date'] === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                                            <option value="month" <?php echo $filters_messages['filter_date'] === 'month' ? 'selected' : ''; ?>>Ce mois</option>
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
                                    <button class="px-4 py-3 bg-secondary text-white rounded-lg hover:bg-orange-600 transition-colors duration-200 flex items-center"
                                        onclick="openNewMessageModal()">
                                        <i class="fas fa-plus mr-2"></i>
                                        Nouveau message
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Liste des messages -->
                        <div class="p-6">
                            <?php if (!empty($messages)) { ?>
                                <div class="mb-4 text-sm text-gray-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <?php echo count($messages); ?> message(s) affiché(s) sur cette page
                                </div>
                                <div class="space-y-4">
                                    <?php foreach ($messages as $message) { ?>
                                        <div class="message-card bg-gray-50 border border-gray-200 rounded-lg p-4 transition-all duration-200 <?php echo ($message['statut'] ?? '') === 'non_lu' ? 'border-l-4 border-l-secondary bg-secondary/5' : ''; ?>">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($message['objet'] ?? ''); ?></h3>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            <?php
                                                            $priorite = $message['priorite'] ?? 'normale';
                                                            if ($priorite === 'haute') echo 'bg-danger/10 text-danger';
                                                            elseif ($priorite === 'basse') echo 'bg-gray-100 text-gray-600';
                                                            else echo 'bg-primary/10 text-primary';
                                                            ?>">
                                                            <?php echo ucfirst($priorite); ?>
                                                        </span>
                                                        <?php if (($message['statut'] ?? '') === 'non_lu') { ?>
                                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-secondary/20 text-secondary">
                                                                <i class="fas fa-circle text-xs mr-1"></i>
                                                                Nouveau
                                                            </span>
                                                        <?php } ?>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mb-2">
                                                        <i class="fas fa-user mr-1"></i>
                                                        De: <span class="font-medium"><?php echo htmlspecialchars($message['expediteur_nom'] ?? ''); ?></span>
                                                    </p>
                                                    <p class="text-sm text-gray-500 mb-3">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        <?php echo isset($message['date_creation']) ? date('d/m/Y H:i', strtotime($message['date_creation'])) : ''; ?>
                                                    </p>
                                                    <p class="text-gray-700 text-sm">
                                                        <?php echo htmlspecialchars(substr($message['contenu'] ?? '', 0, 150)) . (strlen($message['contenu'] ?? '') > 150 ? '...' : ''); ?>
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2 ml-4">
                                                    <button class="p-2 text-gray-400 hover:text-primary hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="viewMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Voir le message">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="p-2 text-gray-400 hover:text-primary hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="replyToMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Répondre">
                                                        <i class="fas fa-reply"></i>
                                                    </button>
                                                    <button class="p-2 text-gray-400 hover:text-danger hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="deleteMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="text-center py-12">
                                    <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-inbox text-3xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun message trouvé</h3>
                                    <p class="text-gray-500">Vous n'avez aucun message correspondant aux critères de recherche.</p>
                                </div>
                            <?php } ?>
                        </div>

                        <!-- Pagination pour les messages -->
                        <?php if ($pagination_messages['total_pages'] > 1): ?>
                            <div class="px-6 py-4 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-700">
                                        Affichage de <?php echo (($pagination_messages['current_page'] - 1) * $pagination_messages['items_per_page']) + 1; ?> à <?php echo min($pagination_messages['current_page'] * $pagination_messages['items_per_page'], $pagination_messages['total_items']); ?> sur <?php echo $pagination_messages['total_items']; ?> messages
                                        <span class="text-gray-500 ml-2">(Page <?php echo $pagination_messages['current_page']; ?> sur <?php echo $pagination_messages['total_pages']; ?>)</span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if ($pagination_messages['current_page'] > 1): ?>
                                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200"
                                                data-page="<?php echo $pagination_messages['current_page'] - 1; ?>"
                                                data-form="messages">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $pagination_messages['current_page'] - 2); $i <= min($pagination_messages['total_pages'], $pagination_messages['current_page'] + 2); $i++): ?>
                                            <button class="px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $i === $pagination_messages['current_page'] ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>"
                                                data-page="<?php echo $i; ?>"
                                                data-form="messages">
                                                <?php echo $i; ?>
                                            </button>
                                        <?php endfor; ?>

                                        <?php if ($pagination_messages['current_page'] < $pagination_messages['total_pages']): ?>
                                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200"
                                                data-page="<?php echo $pagination_messages['current_page'] + 1; ?>"
                                                data-form="messages">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="px-6 py-4 border-t border-gray-200">
                                <div class="text-sm text-gray-700">
                                    <?php echo $pagination_messages['total_items']; ?> message(s) au total
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Onglet Messages non lus -->
                <div id="messages_non_lus" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                        <div class="border-l-4 border-warning bg-white rounded-r-lg shadow-sm p-6 mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-exclamation-circle text-warning mr-3"></i>
                                Messages Non Lus
                            </h2>
                            <p class="text-gray-600">
                                Messages nécessitant votre attention
                            </p>
                        </div>

                        <!-- Filtres pour les messages non lus -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                                <!-- Filtres de recherche -->
                                <div class="flex-1 w-full lg:w-auto">
                                    <form method="GET" id="filter-form-non-lus" class="flex flex-col sm:flex-row gap-4">
                                        <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'messages'; ?>">
                                        <input type="hidden" name="page_non_lus" id="page_non_lus_input" value="<?php echo $pagination_non_lus['current_page']; ?>">

                                        <!-- Recherche -->
                                        <div class="relative flex-1">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </div>
                                            <input type="text"
                                                name="search_non_lus"
                                                id="search-input-non-lus"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                                placeholder="Rechercher un message non lu..."
                                                value="<?php echo htmlspecialchars($filters_non_lus['search']); ?>">
                                        </div>

                                        <!-- Filtre priorité -->
                                        <select name="filter_priorite_non_lus"
                                            class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Priorité</option>
                                            <option value="haute" <?php echo $filters_non_lus['filter_priorite'] === 'haute' ? 'selected' : ''; ?>>Haute</option>
                                            <option value="normale" <?php echo $filters_non_lus['filter_priorite'] === 'normale' ? 'selected' : ''; ?>>Normale</option>
                                            <option value="basse" <?php echo $filters_non_lus['filter_priorite'] === 'basse' ? 'selected' : ''; ?>>Basse</option>
                                        </select>

                                        <!-- Filtre date -->
                                        <select name="filter_date_non_lus"
                                            class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Date</option>
                                            <option value="today" <?php echo $filters_non_lus['filter_date'] === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                            <option value="week" <?php echo $filters_non_lus['filter_date'] === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                                            <option value="month" <?php echo $filters_non_lus['filter_date'] === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                                        </select>

                                        <button type="submit"
                                            class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                            <i class="fas fa-search mr-2"></i>
                                            Filtrer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Liste des messages non lus -->
                        <div class="p-6">
                            <?php if (!empty($messages_non_lus)) { ?>
                                <div class="mb-4 text-sm text-gray-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <?php echo count($messages_non_lus); ?> message(s) non lu(s) affiché(s) sur cette page
                                </div>
                                <div class="space-y-4">
                                    <?php foreach ($messages_non_lus as $message) { ?>
                                        <div class="message-card bg-warning/5 border border-warning/20 rounded-lg p-4 transition-all duration-200 border-l-4 border-l-warning">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($message['objet'] ?? ''); ?></h3>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            <?php
                                                            $priorite = $message['priorite'] ?? 'normale';
                                                            if ($priorite === 'haute') echo 'bg-danger/10 text-danger';
                                                            elseif ($priorite === 'basse') echo 'bg-gray-100 text-gray-600';
                                                            else echo 'bg-primary/10 text-primary';
                                                            ?>">
                                                            <?php echo ucfirst($priorite); ?>
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-warning/20 text-warning">
                                                            <i class="fas fa-exclamation-circle text-xs mr-1"></i>
                                                            Non lu
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mb-2">
                                                        <i class="fas fa-user mr-1"></i>
                                                        De: <span class="font-medium"><?php echo htmlspecialchars($message['expediteur_nom'] ?? ''); ?></span>
                                                    </p>
                                                    <p class="text-sm text-gray-500 mb-3">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        <?php echo isset($message['date_creation']) ? date('d/m/Y H:i', strtotime($message['date_creation'])) : ''; ?>
                                                    </p>
                                                    <p class="text-gray-700 text-sm">
                                                        <?php echo htmlspecialchars(substr($message['contenu'] ?? '', 0, 150)) . (strlen($message['contenu'] ?? '') > 150 ? '...' : ''); ?>
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2 ml-4">
                                                    <button class="p-2 text-gray-400 hover:text-primary hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="viewMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Voir le message">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="p-2 text-gray-400 hover:text-primary hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="replyToMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Répondre">
                                                        <i class="fas fa-reply"></i>
                                                    </button>
                                                    <button class="p-2 text-gray-400 hover:text-warning hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="markAsRead(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Marquer comme lu">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="p-2 text-gray-400 hover:text-danger hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="deleteMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="text-center py-12">
                                    <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-exclamation-circle text-3xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun message non lu</h3>
                                    <p class="text-gray-500">Tous vos messages ont été lus.</p>
                                </div>
                            <?php } ?>
                        </div>

                        <!-- Pagination pour les messages non lus -->
                        <?php if ($pagination_non_lus['total_pages'] > 1): ?>
                            <div class="px-6 py-4 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-700">
                                        Affichage de <?php echo (($pagination_non_lus['current_page'] - 1) * $pagination_non_lus['items_per_page']) + 1; ?> à <?php echo min($pagination_non_lus['current_page'] * $pagination_non_lus['items_per_page'], $pagination_non_lus['total_items']); ?> sur <?php echo $pagination_non_lus['total_items']; ?> messages non lus
                                        <span class="text-gray-500 ml-2">(Page <?php echo $pagination_non_lus['current_page']; ?> sur <?php echo $pagination_non_lus['total_pages']; ?>)</span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if ($pagination_non_lus['current_page'] > 1): ?>
                                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200"
                                                data-page="<?php echo $pagination_non_lus['current_page'] - 1; ?>"
                                                data-form="non_lus">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $pagination_non_lus['current_page'] - 2); $i <= min($pagination_non_lus['total_pages'], $pagination_non_lus['current_page'] + 2); $i++): ?>
                                            <button class="px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $i === $pagination_non_lus['current_page'] ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>"
                                                data-page="<?php echo $i; ?>"
                                                data-form="non_lus">
                                                <?php echo $i; ?>
                                            </button>
                                        <?php endfor; ?>

                                        <?php if ($pagination_non_lus['current_page'] < $pagination_non_lus['total_pages']): ?>
                                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200"
                                                data-page="<?php echo $pagination_non_lus['current_page'] + 1; ?>"
                                                data-form="non_lus">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="px-6 py-4 border-t border-gray-200">
                                <div class="text-sm text-gray-700">
                                    <?php echo $pagination_non_lus['total_items']; ?> message(s) non lu(s) au total
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Onglet Messages envoyés -->
                <div id="messages_envoyes" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                        <div class="border-l-4 border-accent bg-white rounded-r-lg shadow-sm p-6 mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-paper-plane text-accent mr-3"></i>
                                Messages Envoyés
                            </h2>
                            <p class="text-gray-600">
                                Historique de vos messages envoyés
                            </p>
                        </div>

                        <!-- Liste des messages envoyés -->
                        <div class="p-6">
                            <?php if (!empty($messages_envoyes)) { ?>
                                <div class="space-y-4">
                                    <?php foreach ($messages_envoyes as $message) { ?>
                                        <div class="message-card bg-gray-50 border border-gray-200 rounded-lg p-4 transition-all duration-200">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($message['objet'] ?? ''); ?></h3>
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-accent/20 text-accent">
                                                            <i class="fas fa-check text-xs mr-1"></i>
                                                            Envoyé
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mb-2">
                                                        <i class="fas fa-user mr-1"></i>
                                                        À: <span class="font-medium"><?php echo htmlspecialchars($message['destinataire_nom'] ?? ''); ?></span>
                                                    </p>
                                                    <p class="text-sm text-gray-500 mb-3">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        <?php echo isset($message['date_creation']) ? date('d/m/Y H:i', strtotime($message['date_creation'])) : ''; ?>
                                                    </p>
                                                    <p class="text-gray-700 text-sm">
                                                        <?php echo htmlspecialchars(substr($message['contenu'] ?? '', 0, 150)) . (strlen($message['contenu'] ?? '') > 150 ? '...' : ''); ?>
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2 ml-4">
                                                    <button class="p-2 text-gray-400 hover:text-primary hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="viewMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Voir le message">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="p-2 text-gray-400 hover:text-danger hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="deleteMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="text-center py-12">
                                    <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-paper-plane text-3xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun message envoyé</h3>
                                    <p class="text-gray-500">Vous n'avez encore envoyé aucun message.</p>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <!-- Onglet Contacts -->
                <div id="contacts" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                        <div class="border-l-4 border-secondary bg-white rounded-r-lg shadow-sm p-6 mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-users text-secondary mr-3"></i>
                                Contacts
                            </h2>
                            <p class="text-gray-600">
                                Répertoire des utilisateurs du système
                            </p>
                        </div>

                        <!-- Filtres pour les contacts -->
                        <div class="p-6 border-b border-gray-200">
                            <form method="GET" id="filter-form-contacts" class="flex flex-col sm:flex-row gap-4">
                                <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'messages'; ?>">
                                <input type="hidden" name="page_contacts" id="page_contacts_input" value="<?php echo $pagination_contacts['current_page']; ?>">

                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text"
                                        name="search_contact"
                                        id="search-input-contacts"
                                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Rechercher un contact..."
                                        value="<?php echo htmlspecialchars($filters_contacts['search_contact'] ?? ''); ?>">
                                </div>

                                <button type="submit"
                                    class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                    <i class="fas fa-search mr-2"></i>
                                    Rechercher
                                </button>
                            </form>
                        </div>

                        <!-- Liste des contacts -->
                        <div class="p-6">
                            <?php if (!empty($contacts)) { ?>
                                <div class="mb-4 text-sm text-gray-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <?php echo count($contacts); ?> contact(s) affiché(s) sur cette page
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <?php foreach ($contacts as $contact) { ?>
                                        <div class="contact-card bg-gray-50 border border-gray-200 rounded-lg p-6 text-center transition-all duration-200">
                                            <div class="mb-4">
                                                <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-3">
                                                    <i class="fas fa-user text-2xl text-primary"></i>
                                                </div>
                                                <h3 class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($contact['nom_complet'] ?? ''); ?></h3>
                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($contact['email'] ?? ''); ?></p>
                                            </div>
                                            <button onclick="messageContact('<?php echo htmlspecialchars($contact['email'] ?? ''); ?>')"
                                                class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center justify-center">
                                                <i class="fas fa-envelope mr-2"></i>
                                                Envoyer un message
                                            </button>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="text-center py-12">
                                    <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-users text-3xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun contact trouvé</h3>
                                    <p class="text-gray-500">Aucun contact ne correspond aux critères de recherche.</p>
                                </div>
                            <?php } ?>
                        </div>

                        <!-- Pagination pour les contacts -->
                        <?php if ($pagination_contacts['total_pages'] > 1): ?>
                            <div class="px-6 py-4 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-700">
                                        Affichage de <?php echo (($pagination_contacts['current_page'] - 1) * $pagination_contacts['items_per_page']) + 1; ?> à <?php echo min($pagination_contacts['current_page'] * $pagination_contacts['items_per_page'], $pagination_contacts['total_items']); ?> sur <?php echo $pagination_contacts['total_items']; ?> contacts
                                        <span class="text-gray-500 ml-2">(Page <?php echo $pagination_contacts['current_page']; ?> sur <?php echo $pagination_contacts['total_pages']; ?>)</span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if ($pagination_contacts['current_page'] > 1): ?>
                                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200"
                                                data-page="<?php echo $pagination_contacts['current_page'] - 1; ?>"
                                                data-form="contacts">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $pagination_contacts['current_page'] - 2); $i <= min($pagination_contacts['total_pages'], $pagination_contacts['current_page'] + 2); $i++): ?>
                                            <button class="px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $i === $pagination_contacts['current_page'] ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>"
                                                data-page="<?php echo $i; ?>"
                                                data-form="contacts">
                                                <?php echo $i; ?>
                                            </button>
                                        <?php endfor; ?>

                                        <?php if ($pagination_contacts['current_page'] < $pagination_contacts['total_pages']): ?>
                                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200"
                                                data-page="<?php echo $pagination_contacts['current_page'] + 1; ?>"
                                                data-form="contacts">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="px-6 py-4 border-t border-gray-200">
                                <div class="text-sm text-gray-700">
                                    <?php echo $pagination_contacts['total_items']; ?> contact(s) au total
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Onglet Messages archivés -->
                <div id="message_archives" class="tab-content hidden">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                        <div class="border-l-4 border-gray-500 bg-white rounded-r-lg shadow-sm p-6 mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-archive text-gray-500 mr-3"></i>
                                Messages Archivés
                            </h2>
                            <p class="text-gray-600">
                                Messages archivés et anciens
                            </p>
                        </div>

                        <!-- Filtres pour les messages archivés -->
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                                <!-- Filtres de recherche -->
                                <div class="flex-1 w-full lg:w-auto">
                                    <form method="GET" id="filter-form-archives" class="flex flex-col sm:flex-row gap-4">
                                        <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'messages'; ?>">
                                        <input type="hidden" name="page_archives" id="page_archives_input" value="<?php echo $pagination_archives['current_page']; ?>">

                                        <!-- Recherche -->
                                        <div class="relative flex-1">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </div>
                                            <input type="text"
                                                name="search_archives"
                                                id="search-input-archives"
                                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                                placeholder="Rechercher un message archivé..."
                                                value="<?php echo htmlspecialchars($filters_archives['search']); ?>">
                                        </div>

                                        <!-- Filtre priorité -->
                                        <select name="filter_priorite_archives"
                                            class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Priorité</option>
                                            <option value="haute" <?php echo $filters_archives['filter_priorite'] === 'haute' ? 'selected' : ''; ?>>Haute</option>
                                            <option value="normale" <?php echo $filters_archives['filter_priorite'] === 'normale' ? 'selected' : ''; ?>>Normale</option>
                                            <option value="basse" <?php echo $filters_archives['filter_priorite'] === 'basse' ? 'selected' : ''; ?>>Basse</option>
                                        </select>

                                        <!-- Filtre date -->
                                        <select name="filter_date_archives"
                                            class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="">Date</option>
                                            <option value="today" <?php echo $filters_archives['filter_date'] === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                            <option value="week" <?php echo $filters_archives['filter_date'] === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                                            <option value="month" <?php echo $filters_archives['filter_date'] === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                                        </select>

                                        <button type="submit"
                                            class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                            <i class="fas fa-search mr-2"></i>
                                            Filtrer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Liste des messages archivés -->
                        <div class="p-6">
                            <?php if (!empty($messages_archives)) { ?>
                                <div class="mb-4 text-sm text-gray-600">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    <?php echo count($messages_archives); ?> message(s) archivé(s) affiché(s) sur cette page
                                </div>
                                <div class="space-y-4">
                                    <?php foreach ($messages_archives as $message) { ?>
                                        <div class="message-card bg-gray-50 border border-gray-200 rounded-lg p-4 transition-all duration-200 border-l-4 border-l-gray-500">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($message['objet'] ?? ''); ?></h3>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                            <?php
                                                            $priorite = $message['priorite'] ?? 'normale';
                                                            if ($priorite === 'haute') echo 'bg-danger/10 text-danger';
                                                            elseif ($priorite === 'basse') echo 'bg-gray-100 text-gray-600';
                                                            else echo 'bg-primary/10 text-primary';
                                                            ?>">
                                                            <?php echo ucfirst($priorite); ?>
                                                        </span>
                                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-500/20 text-gray-500">
                                                            <i class="fas fa-archive text-xs mr-1"></i>
                                                            Archivé
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mb-2">
                                                        <i class="fas fa-user mr-1"></i>
                                                        De: <span class="font-medium"><?php echo htmlspecialchars($message['expediteur_nom'] ?? ''); ?></span>
                                                    </p>
                                                    <p class="text-sm text-gray-500 mb-3">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        <?php echo isset($message['date_creation']) ? date('d/m/Y H:i', strtotime($message['date_creation'])) : ''; ?>
                                                    </p>
                                                    <p class="text-gray-700 text-sm">
                                                        <?php echo htmlspecialchars(substr($message['contenu'] ?? '', 0, 150)) . (strlen($message['contenu'] ?? '') > 150 ? '...' : ''); ?>
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2 ml-4">
                                                    <button class="p-2 text-gray-400 hover:text-primary hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="viewMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Voir le message">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="p-2 text-gray-400 hover:text-accent hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="restoreMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Restaurer">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                    <button class="p-2 text-gray-400 hover:text-danger hover:bg-gray-100 rounded-lg transition-colors duration-200"
                                                        onclick="deleteMessage(<?php echo $message['id_message'] ?? 0; ?>)"
                                                        title="Supprimer définitivement">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <div class="text-center py-12">
                                    <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-archive text-3xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun message archivé</h3>
                                    <p class="text-gray-500">Vous n'avez aucun message archivé.</p>
                                </div>
                            <?php } ?>
                        </div>

                        <!-- Pagination pour les messages archivés -->
                        <?php if ($pagination_archives['total_pages'] > 1): ?>
                            <div class="px-6 py-4 border-t border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="text-sm text-gray-700">
                                        Affichage de <?php echo (($pagination_archives['current_page'] - 1) * $pagination_archives['items_per_page']) + 1; ?> à <?php echo min($pagination_archives['current_page'] * $pagination_archives['items_per_page'], $pagination_archives['total_items']); ?> sur <?php echo $pagination_archives['total_items']; ?> messages archivés
                                        <span class="text-gray-500 ml-2">(Page <?php echo $pagination_archives['current_page']; ?> sur <?php echo $pagination_archives['total_pages']; ?>)</span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <?php if ($pagination_archives['current_page'] > 1): ?>
                                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200"
                                                data-page="<?php echo $pagination_archives['current_page'] - 1; ?>"
                                                data-form="archives">
                                                <i class="fas fa-chevron-left"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $pagination_archives['current_page'] - 2); $i <= min($pagination_archives['total_pages'], $pagination_archives['current_page'] + 2); $i++): ?>
                                            <button class="px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $i === $pagination_archives['current_page'] ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>"
                                                data-page="<?php echo $i; ?>"
                                                data-form="archives">
                                                <?php echo $i; ?>
                                            </button>
                                        <?php endfor; ?>

                                        <?php if ($pagination_archives['current_page'] < $pagination_archives['total_pages']): ?>
                                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200"
                                                data-page="<?php echo $pagination_archives['current_page'] + 1; ?>"
                                                data-form="archives">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="px-6 py-4 border-t border-gray-200">
                                <div class="text-sm text-gray-700">
                                    <?php echo $pagination_archives['total_items']; ?> message(s) archivé(s) au total
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


        </main>
    </div>

    <!-- Scripts JavaScript -->
    <script>
        // Pagination dynamique pour les messages
        document.addEventListener('DOMContentLoaded', function() {
            const formMessages = document.getElementById('filter-form-messages');
            document.querySelectorAll('[data-form="messages"]').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    if (page) {
                        document.getElementById('page_messages_input').value = page;
                        formMessages.submit();
                    }
                });
            });

            // Pagination dynamique pour les contacts
            const formContacts = document.getElementById('filter-form-contacts');
            document.querySelectorAll('[data-form="contacts"]').forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const page = this.getAttribute('data-page');
                    if (page) {
                        document.getElementById('page_contacts_input').value = page;
                        formContacts.submit();
                    }
                });
            });

            // Pagination dynamique pour les messages non lus
            const formNonLus = document.getElementById('filter-form-non-lus');
            if (formNonLus) {
                document.querySelectorAll('[data-form="non_lus"]').forEach(function(button) {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = this.getAttribute('data-page');
                        if (page) {
                            document.getElementById('page_non_lus_input').value = page;
                            formNonLus.submit();
                        }
                    });
                });
            }

            // Pagination dynamique pour les messages archivés
            const formArchives = document.getElementById('filter-form-archives');
            if (formArchives) {
                document.querySelectorAll('[data-form="archives"]').forEach(function(button) {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = this.getAttribute('data-page');
                        if (page) {
                            document.getElementById('page_archives_input').value = page;
                            formArchives.submit();
                        }
                    });
                });
            }
        });

        // Fonctions pour les actions sur les messages
        function viewMessage(messageId) {
            window.open('?page=messages&action=view&id=' + messageId, '_blank');
        }

        function replyToMessage(messageId) {
            window.open('?page=messages&action=reply&id=' + messageId, '_blank');
        }

        function deleteMessage(messageId) {
            if (confirm('Voulez-vous vraiment supprimer ce message ?')) {
                fetch('./assets/traitements/supprimer_messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'message_id=' + messageId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Message supprimé avec succès', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification('Erreur lors de la suppression : ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        showNotification('Une erreur de communication est survenue.', 'error');
                    });
            }
        }

        function markAsRead(messageId) {
            fetch('./assets/traitements/marquer_comme_lu.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'message_id=' + messageId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Message marqué comme lu', 'success');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showNotification('Erreur lors du marquage : ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Une erreur de communication est survenue.', 'error');
                });
        }

        function restoreMessage(messageId) {
            if (confirm('Voulez-vous restaurer ce message ?')) {
                fetch('./assets/traitements/restaurer_messages.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'message_id=' + messageId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Message restauré avec succès', 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            showNotification('Erreur lors de la restauration : ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        showNotification('Une erreur de communication est survenue.', 'error');
                    });
            }
        }

        function messageContact(email) {
            window.open('?page=messages&action=new&to=' + encodeURIComponent(email), '_blank');
        }

        function openNewMessageModal() {
            window.open('?page=messages&action=new', '_blank');
        }

        // Système de notifications moderne
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 max-w-sm bg-white border rounded-lg shadow-lg p-4 transform transition-all duration-300 translate-x-full`;

            const bgColor = type === 'success' ? 'border-l-4 border-l-accent' :
                type === 'error' ? 'border-l-4 border-l-danger' :
                'border-l-4 border-l-primary';

            const icon = type === 'success' ? 'fas fa-check-circle text-accent' :
                type === 'error' ? 'fas fa-exclamation-circle text-danger' :
                'fas fa-info-circle text-primary';

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

        // Gestion de la navigation par onglets
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            // Fonction pour changer d'onglet
            function switchTab(tabId) {
                // Masquer tous les contenus d'onglets
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                    content.classList.remove('active');
                });

                // Retirer la classe active de tous les boutons
                tabButtons.forEach(button => {
                    button.classList.remove('active', 'border-primary', 'text-primary');
                    button.classList.add('border-transparent', 'text-gray-500');
                });

                // Afficher le contenu de l'onglet sélectionné
                const activeContent = document.getElementById(tabId);
                if (activeContent) {
                    activeContent.classList.remove('hidden');
                    activeContent.classList.add('active');
                }

                // Activer le bouton correspondant
                const activeButton = document.querySelector(`[data-tab="${tabId}"]`);
                if (activeButton) {
                    activeButton.classList.add('active', 'border-primary', 'text-primary');
                    activeButton.classList.remove('border-transparent', 'text-gray-500');
                }
            }

            // Ajouter les événements de clic aux boutons d'onglets
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    switchTab(tabId);
                });
            });

            // Initialiser avec l'onglet actif
            const activeTab = document.querySelector('.tab-button.active');
            if (activeTab) {
                const tabId = activeTab.getAttribute('data-tab');
                switchTab(tabId);
            }
        });
    </script>
</body>

</html>