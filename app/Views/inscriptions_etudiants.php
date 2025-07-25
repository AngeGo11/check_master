<?php

require_once __DIR__ . '/../Controllers/InscriptionsEtudiantsController.php';

// Initialiser le contrôleur
$controller = new InscriptionsEtudiantsController($pdo);

// Récupérer les données via le contrôleur
$data = $controller->index();

// Extraire les données
$reglements = $data['reglements'];
$total_pages = $data['total_pages'];
$current_page = $data['current_page'];
$stats = $data['stats'];
$niveaux = $data['niveaux'];
$filters = $data['filters'];

// Variables pour la compatibilité avec le code existant
$search = $filters['search'];
$filter_niveau = $filters['filter_niveau'];
$filter_statut = $filters['filter_statut'];
$filter_date = $filters['filter_date'];
$totalEtudiants = $stats['total_etudiants'];
$partiellementPaye = $stats['partiellement_paye'];
$toutPaye = $stats['tout_paye'];
$rienPaye = $stats['rien_paye'];

// Initialisation des variables pour les messages
$fullname = isset($_SESSION['user_fullname']) ? $_SESSION['user_fullname'] : 'Utilisateur';
$lib_user_type = isset($_SESSION['lib_user_type']) ? $_SESSION['lib_user_type'] : '';

?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscriptions Étudiants - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2e86c1',
                        'primary-dark': '#154360',
                        secondary: '#17a2b8',
                        accent: '#ffc107',
                        success: '#28a745',
                        danger: '#dc3545',
                        warning: '#ffc107',
                        info: '#17a2b8'
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-fade-in { animation: fadeIn 0.6s ease-out; }
        .animate-slide-in { animation: slideIn 0.5s ease-out; }
        .animate-pulse-custom { animation: pulse 2s infinite; }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }
        .notification.show {
            transform: translateX(0);
        }
        .notification.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .notification.info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">


        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-fade-in">
                <!-- Total Étudiants -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-info hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Étudiants</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $totalEtudiants; ?></p>
                            <p class="text-sm text-blue-600 flex items-center mt-1">
                                <i class="fas fa-users mr-1"></i>
                                Inscrits
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Partiellement Payé -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-warning hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Partiellement Payé</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $partiellementPaye; ?></p>
                            <p class="text-sm text-yellow-600 flex items-center mt-1">
                                <i class="fas fa-hourglass-half mr-1"></i>
                                En cours
                            </p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-hourglass-half text-yellow-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Tout Payé -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-success hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Tout Payé</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $toutPaye; ?></p>
                            <p class="text-sm text-green-600 flex items-center mt-1">
                                <i class="fas fa-check-circle mr-1"></i>
                                Soldé
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Rien Payé -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-danger hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Rien Payé</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $rienPaye; ?></p>
                            <p class="text-sm text-red-600 flex items-center mt-1">
                                <i class="fas fa-times-circle mr-1"></i>
                                Non payé
                            </p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-full">
                            <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8 animate-slide-in">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-search mr-3 text-primary"></i>
                    Rechercher et filtrer
                </h3>
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Search and Filters -->
                    <div class="flex-1">
                        <form method="GET" class="flex flex-col sm:flex-row gap-4" id="filter-form">
                            <input type="hidden" name="page" value="inscriptions_etudiants">
                            
                            <!-- Search Input -->
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" name="search" placeholder="Rechercher un étudiant..." 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"
                                       onkeydown="if(event.key==='Enter'){this.form.submit();}">
                            </div>
                            
                            <!-- Filters -->
                            <div class="flex gap-2">
                                <select name="filter_niveau" onchange="this.form.submit()"
                                        class="block px-4 py-3 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white transition-all duration-200">
                                    <option value="">Niveau</option>
                                    <option value="Licence 1" <?php echo $filter_niveau === 'Licence 1' ? 'selected' : ''; ?>>Licence 1</option>
                                    <option value="Licence 2" <?php echo $filter_niveau === 'Licence 2' ? 'selected' : ''; ?>>Licence 2</option>
                                    <option value="Licence 3" <?php echo $filter_niveau === 'Licence 3' ? 'selected' : ''; ?>>Licence 3</option>
                                    <option value="Master 1" <?php echo $filter_niveau === 'Master 1' ? 'selected' : ''; ?>>Master 1</option>
                                    <option value="Master 2" <?php echo $filter_niveau === 'Master 2' ? 'selected' : ''; ?>>Master 2</option>
                                </select>
                                <select name="filter_statut" onchange="this.form.submit()"
                                        class="block px-4 py-3 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white transition-all duration-200">
                                    <option value="">Statut de paiement</option>
                                    <option value="paye" <?php echo $filter_statut === 'paye' ? 'selected' : ''; ?>>Soldé</option>
                                    <option value="partiel" <?php echo $filter_statut === 'partiel' ? 'selected' : ''; ?>>Partiel</option>
                                    <option value="nonpaye" <?php echo $filter_statut === 'nonpaye' ? 'selected' : ''; ?>>Non payé</option>
                                </select>
                                <select name="filter_date" onchange="this.form.submit()"
                                        class="block px-4 py-3 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white transition-all duration-200">
                                    <option value="">Date de règlement</option>
                                    <option value="this-month" <?php echo $filter_date === 'this-month' ? 'selected' : ''; ?>>Ce mois</option>
                                    <option value="last-month" <?php echo $filter_date === 'last-month' ? 'selected' : ''; ?>>Mois dernier</option>
                                    <option value="custom" <?php echo $filter_date === 'custom' ? 'selected' : ''; ?>>Personnalisée</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button id="delete-all-reglement" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-trash mr-2"></i>Supprimer sélection
                        </button>
                        <button id="add_student" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-plus mr-2"></i>Nouveau règlement
                        </button>
                    </div>
                </div>
            </div>

            <!-- Règlements Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-fade-in">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-list mr-3 text-primary"></i>
                        Liste des règlements
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all-reglements" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Carte</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Règlement</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant à payer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total payé</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reste à payer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reglements as $r): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="reglement-checkbox rounded border-gray-300 text-primary focus:ring-primary" value="<?php echo $r['numero_reglement']; ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($r['num_carte_etd']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center text-sm font-medium">
                                                <?php echo substr($r['nom_etd'], 0, 1) . substr($r['prenom_etd'], 0, 1); ?>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($r['nom_etd']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($r['prenom_etd']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($r['lib_niv_etd']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($r['numero_reglement']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo number_format($r['montant_a_payer'], 0, ',', ' '); ?> FCFA
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 font-medium">
                                    <?php echo number_format($r['total_paye'], 0, ',', ' '); ?> FCFA
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600 font-medium">
                                    <?php echo number_format($r['reste_a_payer'], 0, ',', ' '); ?> FCFA
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($r['date_reglement'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClass = '';
                                    $statusIcon = '';
                                    switch(strtolower($r['statut'])) {
                                        case 'soldé':
                                            $statusClass = 'bg-green-100 text-green-800';
                                            $statusIcon = 'fa-check-circle';
                                            break;
                                        case 'partiel':
                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                            $statusIcon = 'fa-hourglass-half';
                                            break;
                                        case 'non payé':
                                            $statusClass = 'bg-red-100 text-red-800';
                                            $statusIcon = 'fa-times-circle';
                                            break;
                                        default:
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusIcon = 'fa-question-circle';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $statusIcon; ?> mr-1"></i>
                                        <?php echo htmlspecialchars($r['statut']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button class="history-button bg-blue-100 hover:bg-blue-200 text-blue-700 p-2 rounded-lg transition-all duration-200 hover:shadow-md transform hover:-translate-y-1" 
                                                data-reglement="<?php echo $r['numero_reglement']; ?>" 
                                                data-numcarte="<?php echo $r['num_carte_etd']; ?>" 
                                                data-nom="<?php echo $r['nom_etd']; ?>" 
                                                data-prenom="<?php echo $r['prenom_etd']; ?>"
                                                title="Voir historique">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button class="delete-reglement-btn bg-red-100 hover:bg-red-200 text-red-700 p-2 rounded-lg transition-all duration-200 hover:shadow-md transform hover:-translate-y-1" 
                                                data-reglement="<?php echo $r['numero_reglement']; ?>" 
                                                title="Supprimer règlement">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($reglements)): ?>
                            <tr>
                                <td colspan="11" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2"></i>
                                    <p>Aucun règlement trouvé</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 sm:px-6 rounded-xl shadow-lg mt-8">
                <div class="flex flex-1 justify-between sm:hidden">
                    <?php if ($current_page > 1): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page_num' => $current_page - 1])); ?>" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Précédent</a>
                    <?php endif; ?>
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page_num' => $current_page + 1])); ?>" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Suivant</a>
                    <?php endif; ?>
                </div>
                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Affichage de <span class="font-medium"><?php echo (($current_page - 1) * 10) + 1; ?></span> à 
                            <span class="font-medium"><?php echo min($current_page * 10, count($reglements)); ?></span> sur 
                            <span class="font-medium"><?php echo count($reglements); ?></span> résultats
                        </p>
                    </div>
                    <div>
                        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page_num' => $current_page - 1])); ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span class="relative z-10 inline-flex items-center bg-primary px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page_num' => $i])); ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page_num' => $current_page + 1])); ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal pour Nouveau Règlement -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="product-modal">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Enregistrement de règlement</h2>
                <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-6">
                <form action="/GSCV+/public/assets/traitements/ajax_handler.php" method="POST" id="std-form">
                    <input type="hidden" name="action" id="form-action" value="enregistrer_reglement">
                    <input type="hidden" name="old_reglement" id="old-reglement" value="">
                    <input type="hidden" id="numero_reglement" name="numero_reglement">

                    <?php
                    if (isset($_SESSION['error'])) {
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">' . htmlspecialchars($_SESSION['error']) . '</div>';
                        unset($_SESSION['error']);
                    }
                    if (isset($_SESSION['success'])) {
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">' . htmlspecialchars($_SESSION['success']) . '</div>';
                        unset($_SESSION['success']);
                    }
                    ?>

                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations Étudiant</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="card" class="block text-sm font-medium text-gray-700 mb-2">N° carte étudiant</label>
                            <input type="text" id="card" name="card" placeholder="Saisissez le numéro carte de l'étudiant..."
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">Nom</label>
                            <input type="text" id="nom" name="nom" placeholder="Saisissez le nom de l'étudiant..." readonly
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                        <div>
                            <label for="prenoms" class="block text-sm font-medium text-gray-700 mb-2">Prénoms</label>
                            <input type="text" id="prenoms" name="prenoms" placeholder="Saisissez le prénom de l'étudiant..." readonly
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                        <div>
                            <label for="niveau" class="block text-sm font-medium text-gray-700 mb-2">Niveau</label>
                            <select id="niveau" name="niveau" readonly
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Sélectionnez un niveau</option>
                                <?php foreach ($niveaux as $niv): ?>
                                    <option value="<?php echo htmlspecialchars($niv['id_niv_etd']); ?>">
                                        <?php echo htmlspecialchars($niv['lib_niv_etd']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations règlement</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="annee_academique" class="block text-sm font-medium text-gray-700 mb-2">Année académique</label>
                            <input type="text" id="annee_academique" name="annee_academique" value="<?php echo $_SESSION['current_year']; ?>" disabled
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                        <div>
                            <label for="montant_total" class="block text-sm font-medium text-gray-700 mb-2">Montant à payer (FCFA)</label>
                            <input type="number" id="montant_total" name="montant_total" readonly
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Mode de paiement</label>
                        <div class="flex items-center space-x-6">
                            <div class="flex items-center">
                                <input type="radio" id="cash" name="mode_paiement" value="espece" class="text-primary focus:ring-primary">
                                <label for="cash" class="ml-2 text-sm text-gray-700">Espèces</label>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="radio" id="cheque" name="mode_paiement" value="cheque" class="text-primary focus:ring-primary">
                                <label for="cheque" class="text-sm text-gray-700">Chèque N°</label>
                                <input type="text" id="numero_cheque" name="numero_cheque" placeholder="Numéro de chèque" disabled
                                       class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="motif_paiement" class="block text-sm font-medium text-gray-700 mb-2">Motif du versement</label>
                        <input type="text" id="motif_paiement" name="motif_paiement"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label for="montant_paye" class="block text-sm font-medium text-gray-700 mb-2">Montant payé (FCFA)</label>
                            <input type="number" id="montant_paye" name="montant_paye" placeholder="0.00"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label for="total_paye" class="block text-sm font-medium text-gray-700 mb-2">Total payé (FCFA)</label>
                            <input type="number" id="total_paye" name="total_paye" placeholder="0.00" readonly
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50">
                        </div>
                        <div>
                            <label for="reste_a_payer" class="block text-sm font-medium text-gray-700 mb-2">Reste à payer (FCFA)</label>
                            <input type="text" id="reste_a_payer" name="reste_a_payer" placeholder="0.00" readonly
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 font-bold text-right">
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" name="save_reglement"
                                class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-save mr-2"></i>Enregistrer le règlement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal pour l'historique des paiements -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="history-modal">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Historique des Paiements</h2>
                <button id="close-history-modal-btn" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Informations Étudiant</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <span class="text-sm font-medium text-gray-500">N° Étudiant:</span>
                            <span id="history-student-id" class="block text-gray-900"></span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Nom:</span>
                            <span id="history-student-name" class="block text-gray-900"></span>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Prénoms:</span>
                            <span id="history-student-firstname" class="block text-gray-900"></span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Historique des paiements</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant payé</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total payé</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reste à payer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reçu</th>
                                </tr>
                            </thead>
                            <tbody id="history-body" class="bg-white divide-y divide-gray-200">
                                <!-- Les données seront insérées ici dynamiquement -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modern Confirmation Modal -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="confirmation-modal">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Confirmation</h2>
                <button id="close-confirmation-modal-btn" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-6 text-center">
                <div class="mb-4">
                    <i class="fas fa-question-circle text-4xl text-primary"></i>
                </div>
                <p id="confirmation-text" class="text-gray-700 mb-6">Voulez-vous vraiment effectuer cette action ?</p>
                <div class="flex justify-center gap-3">
                    <button id="confirm-modal-btn" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium transition-all duration-200">
                        Oui
                    </button>
                    <button id="cancel-modal-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-medium transition-all duration-200">
                        Non
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ... existing code ...
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.querySelector('#product-modal');
            const openModalBtn = document.querySelector('#add_student');
            const closeModalBtn = document.querySelector('#close-modal-btn');
            const form = document.getElementById('std-form');

            const montantAPayer = document.getElementById('montant_total');
            const montantPaye = document.getElementById('montant_paye');
            const totalPaye = document.getElementById('total_paye');
            const resteAPayer = document.getElementById('reste_a_payer');

            function updateMontants() {
                const montantTotal = parseFloat(montantAPayer.value) || 0;
                const montantPayeValue = parseFloat(montantPaye.value) || 0;
                const totalPayePrecedent = parseFloat(totalPaye.dataset.initial) || 0;

                const nouveauTotalPaye = totalPayePrecedent + montantPayeValue;

                if (nouveauTotalPaye > montantTotal) {
                    showNotification(`Le montant total payé (${nouveauTotalPaye.toLocaleString()} FCFA) dépasse le montant à payer (${montantTotal.toLocaleString()} FCFA).`, 'error');
                    montantPaye.value = '';
                    resteAPayer.value = (montantTotal - totalPayePrecedent).toFixed(0);
                    return;
                }

                totalPaye.value = nouveauTotalPaye.toFixed(0);

                const nouveauReste = montantTotal - nouveauTotalPaye;
                resteAPayer.value = nouveauReste.toFixed(0);
            }

            function resetForm() {
                form.reset();

                // Réinitialiser tous les champs calculés
                if (montantAPayer) montantAPayer.value = '';
                if (montantPaye) montantPaye.value = '';
                if (totalPaye) {
                    totalPaye.value = '';
                    totalPaye.dataset.initial = '0';
                }
                if (resteAPayer) resteAPayer.value = '';

                // Réinitialiser les états des champs
                if (montantAPayer) montantAPayer.readOnly = false;
                if (totalPaye) totalPaye.readOnly = true;
                if (resteAPayer) resteAPayer.readOnly = true;

                // Réactiver le niveau
                const niveauSelect = document.getElementById('niveau');
                if (niveauSelect) {
                    niveauSelect.disabled = false;
                }

                // Réinitialiser les champs cachés
                const formAction = document.getElementById('form-action');
                const oldReglement = document.getElementById('old-reglement');
                const numeroReglement = document.getElementById('numero_reglement');

                if (formAction) formAction.value = 'enregistrer_reglement';
                if (oldReglement) oldReglement.value = '';
                if (numeroReglement) numeroReglement.value = '';
            }

            // Notification function
            function showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
                            <span>${message}</span>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Show notification
                setTimeout(() => notification.classList.add('show'), 100);
                
                // Auto hide after 5 seconds
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 300);
                }, 5000);
            }

            // Gestion de l'ouverture/fermeture du modal
            if (openModalBtn) {
                openModalBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    resetForm();
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            }

            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    resetForm();
                });
            }

            window.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    resetForm();
                }
            });

            // Mise à jour des montants quand on saisit un nouveau paiement
            if (montantPaye) {
                montantPaye.addEventListener('input', updateMontants);
            }

            // Validation du formulaire
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Validation des champs requis
                    const card = document.getElementById('card');
                    const niveau = document.getElementById('niveau');

                    const cardValue = card ? card.value.trim() : '';
                    const niveauValue = niveau ? niveau.value : '';
                    const montantTotal = parseFloat(montantAPayer ? montantAPayer.value : 0) || 0;
                    const montantPayeValue = parseFloat(montantPaye ? montantPaye.value : 0) || 0;
                    const totalPayePrecedent = parseFloat(totalPaye ? totalPaye.dataset.initial : 0) || 0;
                    const resteActuel = montantTotal - totalPayePrecedent;

                    if (!cardValue) {
                        showNotification("Veuillez saisir le numéro de carte étudiant", 'error');
                        return;
                    }

                    if (!niveauValue) {
                        showNotification("Veuillez sélectionner un niveau", 'error');
                        return;
                    }

                    if (montantTotal <= 0) {
                        showNotification("Le montant total doit être supérieur à 0", 'error');
                        return;
                    }

                    if (montantPayeValue <= 0) {
                        showNotification("Le montant payé doit être supérieur à 0", 'error');
                        return;
                    }

                    // Vérification que le montant payé ne dépasse pas le reste à payer
                    if (montantPayeValue > resteActuel) {
                        showNotification(`Le montant payé (${montantPayeValue.toLocaleString()} FCFA) ne peut pas dépasser le reste à payer (${resteActuel.toLocaleString()} FCFA)`, 'error');
                        return;
                    }

                    // Vérification finale des montants
                    const nouveauTotal = totalPayePrecedent + montantPayeValue;

                    if (nouveauTotal > montantTotal) {
                        showNotification("Le montant total payé ne peut pas dépasser le montant à payer", 'error');
                        return;
                    }

                    // Envoyer le formulaire via AJAX
                    const formData = new FormData(this);

                    fetch('/GSCV+/public/assets/traitements/ajax_handler.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification(data.message || 'Règlement enregistré avec succès', 'success');
                                modal.classList.add('hidden');
                                modal.classList.remove('flex');
                                resetForm();
                                location.reload();
                            } else {
                                showNotification(data.error || 'Erreur lors de l\'enregistrement du règlement', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            showNotification('Erreur lors de l\'enregistrement du règlement', 'error');
                        });
                });
            }

            // Gestion de la recherche d'étudiant par numéro de carte
            const cardInput = document.getElementById('card');
            if (cardInput) {
                cardInput.addEventListener('blur', function() {
                    const numCarte = this.value.trim();
                    if (!numCarte) return;

                    // Afficher un indicateur de chargement
                    const nomField = document.getElementById('nom');
                    const prenomsField = document.getElementById('prenoms');

                    if (nomField) nomField.value = 'Chargement...';
                    if (prenomsField) prenomsField.value = 'Chargement...';

                    // Récupérer les informations de l'étudiant via le contrôleur
                    fetch(`/GSCV+/public/assets/traitements/ajax_handler.php?action=get_etudiant_info&num_carte=${encodeURIComponent(numCarte)}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`Erreur HTTP: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            console.log('Données reçues:', data);

                            if (data.error) {
                                showNotification(data.error, 'error');
                                resetForm();
                                return;
                            }

                            if (!data.success) {
                                showNotification('Erreur lors de la récupération des informations', 'error');
                                resetForm();
                                return;
                            }

                            // Mettre à jour les champs de base
                            const fieldsToUpdate = {
                                'nom': data.nom_etd || '',
                                'prenoms': data.prenom_etd || '',
                                'niveau': data.id_niv_etd || ''
                            };

                            Object.entries(fieldsToUpdate).forEach(([id, value]) => {
                                const element = document.getElementById(id);
                                if (element) {
                                    element.value = value;
                                }
                            });

                            // Mettre à jour les montants
                            const montantTotal = parseFloat(data.montant) || 0;
                            const totalPayePrecedent = parseFloat(data.total_paye) || 0;
                            const reste = parseFloat(data.reste_a_payer) || 0;

                            if (montantAPayer) {
                                montantAPayer.value = montantTotal.toFixed(0);
                            }

                            // Mettre à jour le total payé et son dataset avec la valeur brute
                            if (totalPaye) {
                                totalPaye.value = totalPayePrecedent.toFixed(0);
                                totalPaye.dataset.initial = totalPayePrecedent.toString();
                            }

                            // Afficher la valeur brute du reste à payer reçue de l'API
                            if (resteAPayer) {
                                resteAPayer.value = reste.toFixed(0);
                                resteAPayer.dataset.initial = reste.toFixed(0);
                            }

                            // Vider le champ montant payé pour la nouvelle saisie
                            if (montantPaye) {
                                montantPaye.value = '';
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            showNotification("Erreur lors de la récupération des informations de l'étudiant : " + error.message, 'error');
                            resetForm();
                        });
                });
            }

            // Gestion du changement de niveau pour les nouveaux étudiants
            const niveauSelect = document.getElementById('niveau');
            if (niveauSelect) {
                niveauSelect.addEventListener('change', function() {
                    // Ne pas exécuter si le niveau est désactivé (mode édition)
                    if (this.disabled) return;

                    const niveauId = this.value;

                    if (!niveauId) {
                        if (montantAPayer) montantAPayer.value = '';
                        if (totalPaye) {
                            totalPaye.value = '';
                            totalPaye.dataset.initial = '0';
                        }
                        if (resteAPayer) resteAPayer.value = '';
                        return;
                    }

                    // Afficher un indicateur de chargement
                    if (montantAPayer) montantAPayer.value = 'Chargement...';

                    const formData = new FormData();
                    formData.append('action', 'get_montant_tarif');
                    formData.append('niveau', niveauId);

                    fetch('/GSCV+/public/assets/traitements/ajax_handler.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                showNotification(data.error, 'error');
                                if (montantAPayer) montantAPayer.value = '';
                                if (totalPaye) {
                                    totalPaye.value = '';
                                    totalPaye.dataset.initial = '0';
                                }
                                if (resteAPayer) resteAPayer.value = '';
                            } else if (data.success && data.montant !== undefined) {
                                const montantTotal = parseFloat(data.montant) || 0;
                                const totalPayePrecedent = parseFloat(totalPaye ? totalPaye.dataset.initial : 0) || 0;

                                if (montantAPayer) montantAPayer.value = montantTotal.toFixed(0);
                                if (totalPaye) totalPaye.value = totalPayePrecedent.toFixed(0);
                                if (resteAPayer) resteAPayer.value = Math.max(0, montantTotal - totalPayePrecedent).toFixed(0);
                            } else {
                                showNotification('Réponse inattendue du serveur', 'error');
                                if (montantAPayer) montantAPayer.value = '';
                                if (totalPaye) {
                                    totalPaye.value = '';
                                    totalPaye.dataset.initial = '0';
                                }
                                if (resteAPayer) resteAPayer.value = '';
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            showNotification("Erreur lors de la récupération du montant", 'error');
                            if (montantAPayer) montantAPayer.value = '';
                            if (totalPaye) {
                                totalPaye.value = '';
                                totalPaye.dataset.initial = '0';
                            }
                            if (resteAPayer) resteAPayer.value = '';
                        });
                });
            }

            // Gestion de l'historique des paiements
            const historyModal = document.getElementById('history-modal');
            const closeHistoryModalBtn = document.getElementById('close-history-modal-btn');
            const historyButtons = document.querySelectorAll('.history-button');

            historyButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    const reglement = button.dataset.reglement;
                    const studentCardNumber = button.dataset.numcarte;
                    const studentName = button.dataset.nom;
                    const studentFirstname = button.dataset.prenom;

                    const historyStudentId = document.getElementById('history-student-id');
                    const historyStudentName = document.getElementById('history-student-name');
                    const historyStudentFirstname = document.getElementById('history-student-firstname');

                    if (historyStudentId) historyStudentId.textContent = studentCardNumber;
                    if (historyStudentName) historyStudentName.textContent = studentName;
                    if (historyStudentFirstname) historyStudentFirstname.textContent = studentFirstname;

                    try {
                        const formData = new FormData();
                        formData.append('action', 'get_payment_history');
                        formData.append('numero_reglement', reglement);

                        const response = await fetch('/GSCV+/public/assets/traitements/ajax_handler.php', {
                            method: 'POST',
                            body: formData
                        });

                        const text = await response.text();
                        let data;
                        try {
                            data = JSON.parse(text);
                        } catch (parseError) {
                            throw new Error("Réponse invalide du serveur");
                        }

                        const timeline = document.getElementById('history-body');
                        if (timeline) {
                            timeline.innerHTML = '';

                            if (data.error || !Array.isArray(data) || data.length === 0) {
                                timeline.innerHTML = `<tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">${data.error || "Aucun paiement enregistré"}</td></tr>`;
                            } else {
                                displayPaymentHistory(data);
                            }
                        }

                        if (historyModal) {
                            historyModal.classList.remove('hidden');
                            historyModal.classList.add('flex');
                        }
                    } catch (error) {
                        console.error('Erreur :', error);
                        showNotification("Erreur lors du chargement de l'historique des paiements : " + error.message, 'error');
                    }
                });
            });

            if (closeHistoryModalBtn) {
                closeHistoryModalBtn.addEventListener('click', () => {
                    if (historyModal) {
                        historyModal.classList.add('hidden');
                        historyModal.classList.remove('flex');
                    }
                });
            }

            window.addEventListener('click', (event) => {
                if (event.target === historyModal) {
                    if (historyModal) {
                        historyModal.classList.add('hidden');
                        historyModal.classList.remove('flex');
                    }
                }
            });

            function displayPaymentHistory(history) {
                const historyBody = document.getElementById('history-body');
                historyBody.innerHTML = '';

                history.forEach(item => {
                    const date = new Date(item.date_paiement).toLocaleDateString('fr-FR');
                    const montantPaye = parseFloat(item.montant_paye);
                    const totalPaye = parseFloat(item.total_paye);
                    const resteAPayer = parseFloat(item.reste_a_payer);
                    const numeroRecu = item.numero_recu;
                    const numeroReglement = item.numero_reglement || '';

                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50';
                    row.innerHTML = `
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${date}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">${montantPaye.toLocaleString('fr-FR')} FCFA</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${totalPaye.toLocaleString('fr-FR')} FCFA</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-red-600">${resteAPayer.toLocaleString('fr-FR')} FCFA</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href='/GSCV+/public/assets/traitements/imprimer_reglement.php?numero_reglement=${encodeURIComponent(numeroReglement)}&numero_recu=${encodeURIComponent(numeroRecu)}&mode_de_paiement=${encodeURIComponent(item.mode_de_paiement || '')}&numero_cheque=${encodeURIComponent(item.numero_cheque || '')}&motif_paiement=${encodeURIComponent(item.motif_paiement || '')}' target='_blank' class='bg-blue-100 hover:bg-blue-200 text-blue-700 p-2 rounded-lg transition-all duration-200' title="Voir reçu">
                                <i class="fas fa-receipt"></i>
                            </a>
                        </td>
                    `;
                    historyBody.appendChild(row);
                });
            }

            // Gestion de l'activation du champ numéro de chèque
            const radioCash = document.getElementById('cash');
            const radioCheque = document.getElementById('cheque');
            const numCheque = document.getElementById('numero_cheque');
            if (radioCash && radioCheque && numCheque) {
                function updateChequeField() {
                    numCheque.disabled = !radioCheque.checked;
                    if (!radioCheque.checked) numCheque.value = '';
                }
                radioCash.addEventListener('change', updateChequeField);
                radioCheque.addEventListener('change', updateChequeField);
                updateChequeField();
            }

            // Modale de confirmation générique
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

            // Suppression individuelle
            document.querySelectorAll('.delete-reglement-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const numeroReglement = this.dataset.reglement;
                    openConfirmationModal('Voulez-vous vraiment supprimer ce règlement ?', function() {
                        const formData = new FormData();
                        formData.append('action', 'supprimer_reglement');
                        formData.append('numero_reglement', numeroReglement);

                        fetch('/GSCV+/public/assets/traitements/ajax_handler.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.success) {
                                    showNotification('Règlement supprimé avec succès', 'success');
                                    location.reload();
                                } else {
                                    showNotification(data.error || 'Erreur lors de la suppression.', 'error');
                                }
                            });
                    });
                });
            });

            // Suppression multiple
            const bulkDeleteBtn = document.getElementById('delete-all-reglement');
            if (bulkDeleteBtn) {
                bulkDeleteBtn.addEventListener('click', function() {
                    const checkedBoxes = document.querySelectorAll('.reglement-checkbox:checked');
                    const ids = Array.from(checkedBoxes).map(cb => cb.value);
                    if (ids.length === 0) {
                        showNotification('Veuillez sélectionner au moins un règlement à supprimer.', 'error');
                        return;
                    }
                    openConfirmationModal(
                        `Voulez-vous vraiment supprimer les ${ids.length} règlements sélectionnés ?`,
                        function() {
                            const formData = new FormData();
                            formData.append('action', 'supprimer_reglements');
                            formData.append('reglement_ids', JSON.stringify(ids));

                            fetch('/GSCV+/public/assets/traitements/ajax_handler.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        showNotification(data.message || 'Règlements supprimés avec succès.', 'success');
                                        location.reload();
                                    } else {
                                        showNotification(data.message || data.error, 'error');
                                    }
                                });
                        }
                    );
                });
            }

            // Gestion de la case "tout cocher"
            const selectAllCheckbox = document.getElementById('select-all-reglements');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    document.querySelectorAll('.reglement-checkbox').forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }
        });
    </script>

</body>

</html>