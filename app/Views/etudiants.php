<?php
// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../Controllers/EtudiantsController.php';
require_once __DIR__ . '/../Controllers/EnseignantController.php';
require_once __DIR__ . '/../Controllers/AnneeAcademiqueController.php';

//Récupérer l'id_ac
$anneeController = new AnneeAcademiqueController($pdo);
$id_ac = $anneeController->getIdCurrentYear();


// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');

// Initialisation du contrôleur
$controller = new EtudiantsController();

// Récupération des paramètres de pagination et filtres pour les rapports
$search_rapport = $_GET['search_rapport'] ?? '';
$date_rapport = $_GET['date_rapport'] ?? '';
$page_rapport = max(1, intval($_GET['page_rapport'] ?? 1));
$limit_rapport = 10;

// Récupération des rapports en attente
$rapports_data = $controller->rapportEnAttente($search_rapport, "En attente d'approbation", $date_rapport, $page_rapport, $limit_rapport);

// Debug: Afficher les informations de debug
error_log("Vue etudiants.php - rapports_data: " . print_r($rapports_data, true));

// Gestion des actions
$action = $_GET['action'] ?? '';
$message = '';
$error = '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($action) {
        case 'add_process':
            $result = $controller->ajouterEtudiant($_POST);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            header("Location: index_personnel_administratif.php?page=etudiants");
            exit;

        case 'modify_process':
            $id = $_GET['id'] ?? '';
            if ($id) {
                $result = $controller->modifierEtudiant($id, $_POST);
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
            }
            header("Location: index_personnel_administratif.php?page=etudiants");
            exit;

        case 'share':
            $etudiant_id = $_GET['id'] ?? '';
            $rapport_id = $_GET['rapport'] ?? '';
            if ($etudiant_id && $rapport_id) {
                $result = $controller->gererRapport($action, $etudiant_id, $rapport_id, $_POST);
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                header("Location: index_personnel_administratif.php?page=etudiants");
                exit;
            }
            break;

        case 'inscrire-etudiants-cheval':
            $result = $controller->inscrireEtudiantsCheval($_POST);
            if ($result['success']) {
                $_SESSION['success_message'] = $result['message'];
            } else {
                $_SESSION['error_message'] = $result['message'];
            }
            header("Location: index_personnel_administratif.php?page=etudiants");
            exit;
    }
}

// Traitement des actions GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'delete':
            $id = $_GET['id'] ?? '';
            if ($id && isset($_GET['confirm'])) {
                $result = $controller->supprimerEtudiant($id);
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                header("Location: index_personnel_administratif.php?page=etudiants");
                exit;
            }
            break;

        case 'approve':
        case 'reject':
            $etudiant_id = $_GET['id'] ?? '';
            $rapport_id = $_GET['rapport'] ?? '';
            if ($etudiant_id && $rapport_id) {
                $result = $controller->gererRapport($action, $etudiant_id, $rapport_id, $_POST);
                if ($result['success']) {
                    $_SESSION['success_message'] = $result['message'];
                } else {
                    $_SESSION['error_message'] = $result['message'];
                }
                header("Location: index_personnel_administratif.php?page=etudiants");
                exit;
            }
            break;
    }
}

// Récupération des données principales
$data = $controller->index();

// Extraction des variables pour la vue
$etudiants = $data['etudiants'];
$total_records = $data['total_records'];
$total_pages = $data['total_pages'];
$current_page = $data['current_page'];
$statistics = $data['statistics'];
$filters = $data['filters'];
$lists = $data['lists'];

// Récupération des rapports si l'utilisateur a les droits
$rapports_data = null;
if (isset($_SESSION['id_user_group']) && $_SESSION['id_user_group'] == 2) {
    $search_rapport = $_GET['search_rapport'] ?? '';
    $date_rapport = $_GET['date_rapport'] ?? '';
    $page_rapport = max(1, intval($_GET['page_rapport'] ?? 1));

    $rapports_data = $controller->getRapportsEtudiants($search_rapport, $date_rapport, $page_rapport, 10);
}

// Récupération des détails d'un étudiant si demandé
$etudiant_details = null;
if ($action === 'view-details' && isset($_GET['id'])) {
    $etudiant_details = $controller->getEtudiantDetails($_GET['id']);
}

// Récupération des données pour modification
$etudiant_modify = null;
if ($action === 'modify' && isset($_GET['id'])) {
    $etudiant_modify = $controller->getEtudiantDetails($_GET['id']);
}
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - GSCV+</title>
    <link rel="stylesheet" href="../../public/assets/css/etudiants.css?v=<?php echo time(); ?>">
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

        /* Nouveaux styles pour les filtres et actions */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.8);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .filters-section {
            padding: 24px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .filters-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filters-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #1a5276 0%, #163d5a 100%);
            border-radius: 2px;
        }

        .filters-grid {
            gap: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filters-grid div,
        .filters-grid select {
            width: 100%;
            max-width: 700px;
        }

        .search-input-container {
            position: relative;
            grid-column: 1 / -1;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
            z-index: 10;
        }

        .form-input {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #ffffff;
            color: #1e293b;
        }

        .form-input:focus {
            outline: none;
            border-color: #1a5276;
            box-shadow: 0 0 0 3px rgba(26, 82, 118, 0.1);
            transform: translateY(-1px);
        }

        .form-input::placeholder {
            color: #94a3b8;
        }

        .form-select {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            background: #ffffff;
            color: #1e293b;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px;
        }

        .form-select:focus {
            outline: none;
            border-color: #1a5276;
            box-shadow: 0 0 0 3px rgba(26, 82, 118, 0.1);
            transform: translateY(-1px);
        }

        .actions-section {
            padding: 24px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        }

        .actions-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .actions-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            border-radius: 2px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
        }

        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn-primary {
            background: linear-gradient(135deg, #1a5276 0%, #163d5a 100%);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #163d5a 0%, #1a5276 100%);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #ea580c 0%, #f97316 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: #ffffff;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #dc2626 100%);
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }

            .actions-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 0 16px;
            }

            .filters-section,
            .actions-section {
                padding: 16px;
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
                <!-- Total étudiants -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total étudiants inscrits</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['total_etudiants']; ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-users text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- En attente -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-warning overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">En attente de validation</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['en_attente']; ?></p>
                            </div>
                            <div class="bg-warning/10 rounded-full p-4">
                                <i class="fas fa-hourglass-half text-2xl text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Validés -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Étudiants validés</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['valides']; ?></p>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-check-circle text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Refusés -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-danger overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Étudiants refusés</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $statistics['refuses']; ?></p>
                            </div>
                            <div class="bg-danger/10 rounded-full p-4">
                                <i class="fas fa-times-circle text-2xl text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des étudiants -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                <div class="border-l-4 border-primary bg-white rounded-r-lg shadow-sm p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-list text-primary mr-3"></i>
                        Liste des étudiants
                    </h2>
                    <p class="text-gray-600">
                        Gestion et suivi des étudiants inscrits
                    </p>
                </div>

                <!-- Actions et filtres pour étudiants -->
                <div class="container mx-auto mb-8">
                    <div class="card">
                        <!-- Section Filtres -->
                        <div class="filters-section">
                            <h3 class="filters-title">Filtres</h3>
                            <form method="get" class="filters-grid">
                                <input type="hidden" name="page" value="etudiants">
                                
                                <div class="search-input-container">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" 
                                           name="search" 
                                           placeholder="Rechercher un étudiant (nom, email, carte, promotion)..." 
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($filters['search']); ?>">
                                </div>

                                <select name="promotion" 
                                        class="form-select"
                                        onchange="this.form.submit()">
                                    <option value="">Toutes les promotions</option>
                                    <?php foreach ($lists['promotions'] as $promo): ?>
                                        <?php $selected = ($filters['promotion'] == $promo['id_promotion']) ? 'selected' : ''; ?>
                                        <option value="<?php echo $promo['id_promotion']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($promo['lib_promotion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="niveau" 
                                        class="form-select"
                                        onchange="this.form.submit()">
                                    <option value="">Tous les niveaux</option>
                                    <?php foreach ($lists['niveaux'] as $niv): ?>
                                        <?php $selected = ($filters['niveau'] == $niv['id_niv_etd']) ? 'selected' : ''; ?>
                                        <option value="<?php echo $niv['id_niv_etd']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($niv['lib_niv_etd']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <select name="statut_etudiant" 
                                        class="form-select"
                                        onchange="this.form.submit()">
                                    <option value="">Tous les statuts</option>
                                    <?php if (isset($lists['statut_etudiant']) && is_array($lists['statut_etudiant'])): ?>
                                        <?php foreach ($lists['statut_etudiant'] as $statut): ?>
                                            <?php $selected = ($filters['statut_etudiant'] == $statut['id_statut']) ? 'selected' : ''; ?>
                                            <option value="<?php echo $statut['id_statut']; ?>" <?php echo $selected; ?>>
                                                <?php echo htmlspecialchars($statut['lib_statut']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>

                               

                               
                            </form>
                        </div>

                        <!-- Section Actions -->
                        <div class="actions-section">
                            <h3 class="actions-title">Actions</h3>
                            <div class="actions-grid">
                                <button type="button" 
                                        class="btn btn-danger" 
                                        id="bulk-delete-btn">
                                    <i class="fas fa-trash"></i>
                                    Supprimer la sélection
                                </button>
                                <button type="button" 
                                        class="btn btn-secondary" 
                                        id="bulk-export-btn">
                                    <i class="fas fa-file-export"></i>
                                    Exporter
                                </button>
                                <a href="?page=etudiants&action=inscrire-etudiant-cheval" 
                                   class="btn btn-secondary" 
                                   id="inscrire-etudiant-cheval">
                                    <i class="fa-solid fa-bookmark"></i>
                                    Inscrire à cheval
                                </a>
                                <a href="?page=etudiants&action=add" 
                                   class="btn btn-primary" 
                                   id="add_student">
                                    <i class="fas fa-plus"></i>
                                    Ajouter un étudiant
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table des étudiants -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" id="select-all-etudiants"
                                        class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Étudiant
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    N° Carte
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Niveau
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Promotion
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
                            <?php if ($etudiants): ?>
                                <?php foreach ($etudiants as $etudiant): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox"
                                                class="etudiant-checkbox rounded border-gray-300 text-primary focus:ring-primary"
                                                value="<?php echo $etudiant['num_etd']; ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-primary">
                                                            <?php echo substr($etudiant['nom_etd'], 0, 1); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo $etudiant["nom_etd"] . " " . $etudiant["prenom_etd"]; ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo $etudiant["email_etd"]; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo $etudiant["num_carte_etd"]; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $etudiant["lib_niv_etd"]; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo $etudiant["lib_promotion"] ? htmlspecialchars($etudiant["lib_promotion"]) : 'N/A'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php if (isset($etudiant["lib_statut"]) && $etudiant["lib_statut"]): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?php 
                                                    switch($etudiant["id_statut"]) {
                                                        case 1:
                                                            echo 'bg-green-100 text-green-800';
                                                            break;
                                                        case 2:
                                                            echo 'bg-yellow-100 text-yellow-800';
                                                            break;
                                                        case 3:
                                                            echo 'bg-red-100 text-red-800';
                                                            break;
                                                        default:
                                                            echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo htmlspecialchars($etudiant["lib_statut"]); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    N/A
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="?page=etudiants&id=<?php echo $etudiant['num_etd']; ?>&action=view-details"
                                                    class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors duration-200"
                                                    title="Voir détails">
                                                    <i class="fas fa-info-circle"></i>
                                                </a>
                                                <a href="?page=etudiants&id=<?php echo $etudiant['num_etd']; ?>&action=modify"
                                                    class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors duration-200"
                                                    title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?page=etudiants&id=<?php echo $etudiant['num_etd']; ?>&action=delete"
                                                    class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-colors duration-200"
                                                    title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-user-graduate text-4xl mb-4"></i>
                                            <h3 class="text-lg font-medium mb-2">Aucun étudiant trouvé</h3>
                                            <p>Aucun étudiant ne correspond aux critères de recherche.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Page <?php echo $current_page; ?> sur <?php echo $total_pages; ?>
                            </div>
                            <div class="flex space-x-2">
                                <?php
                                function buildPageUrl($num)
                                {
                                    $params = $_GET;
                                    $params['page'] = 'etudiants';
                                    $params['page_num'] = $num;
                                    return 'index_personnel_administratif.php?' . http_build_query($params);
                                }
                                ?>
                                <?php if ($current_page > 1): ?>
                                    <a href="<?php echo buildPageUrl($current_page - 1); ?>"
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>

                                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                    <a href="<?php echo buildPageUrl($i); ?>"
                                        class="px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $i == $current_page ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <a href="<?php echo buildPageUrl($current_page + 1); ?>"
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section Rapports (si autorisé) -->
            <?php if (isset($_SESSION['id_user_group']) && $_SESSION['id_user_group'] == 2 && $rapports_data): ?>
                <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                    <div class="border-l-4 border-secondary bg-white rounded-r-lg shadow-sm p-6 mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">
                            <i class="fas fa-file-alt text-secondary mr-3"></i>
                            Rapports Étudiants
                        </h2>
                        <p class="text-gray-600">
                            Gestion des rapports soumis par les étudiants
                        </p>
                    </div>

                    <!-- Filtres rapports -->
                    <div class="p-6 border-b border-gray-200">
                        <form method="get" class="flex flex-col sm:flex-row gap-4">
                            <input type="hidden" name="page" value="etudiants">

                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text"
                                    name="search_rapport"
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                    placeholder="Rechercher un étudiant ou un rapport..."
                                    value="<?php echo htmlspecialchars($_GET['search_rapport'] ?? ''); ?>">
                            </div>

                            <select name="date_rapport"
                                class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                onchange="this.form.submit()">
                                <option value="">Date de soumission</option>
                                <option value="today" <?php echo ($_GET['date_rapport'] ?? '') === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                <option value="week" <?php echo ($_GET['date_rapport'] ?? '') === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                                <option value="month" <?php echo ($_GET['date_rapport'] ?? '') === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                            </select>

                            <button type="submit"
                                class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                <i class="fas fa-search mr-2"></i>
                                Filtrer
                            </button>

                            <?php if (($_GET['search_rapport'] ?? '') || ($_GET['date_rapport'] ?? '')): ?>
                                <a href="index_personnel_administratif.php?page=etudiants"
                                    class="px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200 flex items-center">
                                    <i class="fas fa-times mr-2"></i>
                                    Réinitialiser
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Table rapports -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        <input type="checkbox" id="select-all-rapports"
                                            class="rounded border-gray-300 text-primary focus:ring-primary">
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
                                <?php if ($rapports_data['rapports']): ?>
                                    <?php foreach ($rapports_data['rapports'] as $rapport): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox"
                                                    class="rapport-checkbox rounded border-gray-300 text-primary focus:ring-primary"
                                                    value="<?php echo $rapport['id_rapport_etd']; ?>">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                            <span class="text-sm font-medium text-primary">
                                                                <?php echo substr($rapport['nom_etd'] ?? '', 0, 1); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo ($rapport['nom_etd'] ?? '') . " " . ($rapport['prenom_etd'] ?? ''); ?>
                                                        </div>
                                                        <div class="text-sm text-gray-500">
                                                            <?php echo $rapport['email_etd'] ?? ''; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo $rapport['theme_memoire'] ?? ''; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $rapport['date_rapport'] ?? ''; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <a href="?page=etudiants&id=<?php echo $rapport['num_etd']; ?>&rapport=<?php echo $rapport['id_rapport_etd']; ?>&action=view"
                                                        class="text-blue-600 hover:text-blue-900 p-2 rounded-lg hover:bg-blue-50 transition-colors duration-200"
                                                        title="Voir rapport">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?page=etudiants&id=<?php echo $rapport['num_etd']; ?>&rapport=<?php echo $rapport['id_rapport_etd']; ?>&action=delete"
                                                        class="text-red-600 hover:text-red-900 p-2 rounded-lg hover:bg-red-50 transition-colors duration-200"
                                                        title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="?page=etudiants&id=<?php echo $rapport['num_etd']; ?>&rapport=<?php echo $rapport['id_rapport_etd']; ?>&action=share"
                                                        class="text-green-600 hover:text-green-900 p-2 rounded-lg hover:bg-green-50 transition-colors duration-200"
                                                        title="Partager">
                                                        <i class="fas fa-share"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="text-gray-500">
                                                <i class="fas fa-check-circle text-4xl mb-4 text-green-400"></i>
                                                <h3 class="text-lg font-medium mb-2">Aucun rapport en attente</h3>
                                                <p>Tous les rapports ont été traités ou aucun rapport n'est en attente.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination rapports -->
                    <?php if ($rapports_data['total_pages'] > 1): ?>
                        <div class="px-6 py-4 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Page <?php echo $rapports_data['current_page']; ?> sur <?php echo $rapports_data['total_pages']; ?>
                                </div>
                                <div class="flex space-x-2">
                                    <?php
                                    function buildPageUrlRapport($num)
                                    {
                                        $params = $_GET;
                                        $params['page'] = 'etudiants';
                                        $params['page_rapport'] = $num;
                                        return 'index_personnel_administratif.php?' . http_build_query($params);
                                    }
                                    ?>
                                    <?php if ($rapports_data['current_page'] > 1): ?>
                                        <a href="<?php echo buildPageUrlRapport($rapports_data['current_page'] - 1); ?>"
                                            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php for ($i = max(1, $rapports_data['current_page'] - 2); $i <= min($rapports_data['total_pages'], $rapports_data['current_page'] + 2); $i++): ?>
                                        <a href="<?php echo buildPageUrlRapport($i); ?>"
                                            class="px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $i == $rapports_data['current_page'] ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>

                                    <?php if ($rapports_data['current_page'] < $rapports_data['total_pages']): ?>
                                        <a href="<?php echo buildPageUrlRapport($rapports_data['current_page'] + 1); ?>"
                                            class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php

    // Fenêtre modale pour ajouter un étudiant
    if ($action === 'add') {
        include __DIR__ . '/modals/add_student_modal.php';
    }

    // Fenêtre modale pour modifier les informations d'un étudiant
    if ($action === 'modify' && $etudiant_modify) {
        include __DIR__ . '/modals/modify_student_modal.php';
    }

    // Fenêtre modale pour inscrire un étudiant à cheval
    if ($action === 'inscrire-etudiant-cheval') {
        // Récupérer les données pour l'inscription à cheval
        $inscription_data = $controller->getInscriptionChevalData();
      //  $inscription_data = $inscription_data['etudiants']; // Extraire seulement les étudiants
        include __DIR__ . '/modals/inscription_etudiant_cheval_modal.php';
    }

    // Fenêtre modale pour vérifier l'éligibilité d'un étudiant
    if ($action === 'view-details' && $etudiant_details) {
        include __DIR__ . '/modals/view_student_details_modal.php';
    }

    // Fenêtre modale pour voir les détails du rapport d'un étudiant
    if ($action === 'view' && isset($_GET['id']) && isset($_GET['rapport'])) {
        include __DIR__ . '/modals/view_rapport_modal.php';
    }

    // Fenêtre modale pour voir les détails du rapport d'un étudiant
    if ($action === 'share') {
        include __DIR__ . '/modals/share_rapport_modal.php';
    }

    // Traitement de la suppression d'un étudiant
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        // Vérifier si l'étudiant a des rapports
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rapport_etudiant WHERE num_etd = ?");
        $stmt->execute([$_GET['id']]);
        $has_reports = $stmt->fetchColumn() > 0;

        // Demander confirmation avant de supprimer
        if (!isset($_GET['confirm'])) {
            // Afficher une demande de confirmation
    ?>
            <!-- Modal de confirmation de suppression moderne -->
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" id="confirm-delete-modal">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 animate-bounce-in">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Confirmation de suppression</h3>
                        </div>
                        <div class="mb-6">
                            <p class="text-gray-600 mb-2">Êtes-vous sûr de vouloir supprimer cet étudiant<?php if ($has_reports) echo " et tous ses rapports associés" ?>?</p>
                            <p class="text-red-600 font-medium">Cette action est irréversible.</p>
                        </div>
                        <div class="flex gap-3 justify-end">
                            <a href="?page=etudiants"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                                Annuler
                            </a>
                            <a href="?page=etudiants&id=<?php echo $_GET['id']; ?>&action=delete&confirm=1"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                Oui, supprimer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
    <?php
        } else {
            try {
                // Récupérer l'email de l'étudiant pour supprimer son compte utilisateur
                $stmt = $pdo->prepare("SELECT email_etd FROM etudiants WHERE num_etd = ?");
                $stmt->execute([$_GET['id']]);
                $email = $stmt->fetchColumn();

                // Début de la transaction
                $pdo->beginTransaction();

                // Supprimer les rapports de l'étudiant si nécessaire
                if ($has_reports) {
                    // Supprimer d'abord les entrées dans la table deposer
                    $stmt = $pdo->prepare("DELETE d FROM deposer d 
                                      JOIN rapport_etudiant r ON d.id_rapport_etd = r.id_rapport_etd 
                                      WHERE r.num_etd = ?");
                    $stmt->execute([$_GET['id']]);

                    // Ensuite supprimer les rapports
                    $stmt = $pdo->prepare("DELETE FROM rapport_etudiant WHERE num_etd = ?");
                    $stmt->execute([$_GET['id']]);
                }

                // Supprimer l'étudiant
                $stmt = $pdo->prepare("DELETE FROM etudiants WHERE num_etd = ?");
                $stmt->execute([$_GET['id']]);


                // Validation de la transaction
                $pdo->commit();
                $_SESSION['success_message'] = "L'étudiant a été supprimé avec succès.";
                header("Location: index_personnel_administratif.php?page=etudiants");
                exit;
            } catch (PDOException $e) {
                // Annulation de la transaction en cas d'erreur
                $pdo->rollBack();
                $_SESSION['error_message'] = "Erreur lors de la suppression: " . $e->getMessage();
                header("Location: index_personnel_administratif.php?page=etudiants");
                exit;
            }
        }
    }

    ?>

    <!-- Modal pour l'aperçu du rapport -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="preview-rapport-modal">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 h-5/6">
            <div class="flex justify-between items-center p-6 border-b">
                <h2 class="text-xl font-semibold">Aperçu du Rapport</h2>
                <button class="text-gray-400 hover:text-gray-600" id="close-modal-preview-btn">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6 h-full">
                <iframe src="" frameborder="0" class="w-full h-full rounded-lg"></iframe>
            </div>
        </div>
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

    <script src="./assets/js/gsEtudiants.js"></script>

    <script>
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

        // Vérifier les messages de session
        <?php if (isset($_SESSION['success_message'])): ?>
            showNotification('<?php echo $_SESSION['success_message']; ?>', 'success');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            showNotification('<?php echo $_SESSION['error_message']; ?>', 'error');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>
</body>

</html>