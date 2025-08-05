<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'annees_academiques') {
    return;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'] ?? 'Utilisateur';
$lib_user_type = $_SESSION['lib_user_type'] ?? 'Utilisateur';

// Traitement du formulaire d'ajout/modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    // Validation des dates
                    $date_debut = $_POST['date_debut'];
                    $date_fin = $_POST['date_fin'];

                    if (strtotime($date_debut) >= strtotime($date_fin)) {
                        $_SESSION['error'] = "La date de début doit être antérieure à la date de fin";
                        break;
                    }

                    // Vérifier les chevauchements avec d'autres années
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM annee_academique WHERE 
                        (date_debut <= ? AND date_fin >= ?) OR 
                        (date_debut <= ? AND date_fin >= ?) OR 
                        (date_debut >= ? AND date_fin <= ?)");
                    $stmt->execute([$date_debut, $date_debut, $date_fin, $date_fin, $date_debut, $date_fin]);

                    if ($stmt->fetchColumn() > 0) {
                        $_SESSION['error'] = "Cette période chevauche avec une année académique existante";
                        break;
                    }

                    // Génération de l'ID automatique
                    $debut = new DateTime($date_debut);
                    $fin = new DateTime($date_fin);
                    $id_ac = $fin->format('y') . $debut->format('y');

                    $stmt = $pdo->prepare("INSERT INTO annee_academique (id_ac, date_debut, date_fin, statut_annee) VALUES (?, ?, ?, 'En attente')");
                    $stmt->execute([$id_ac, $date_debut, $date_fin]);
                    $_SESSION['success'] = "L'année académique a été ajoutée avec succès";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Une erreur est survenue lors de l'ajout : " . $e->getMessage();
                }
                break;

            case 'edit':
                try {
                    $id_ac = $_POST['id_ac'];
                    $date_debut = $_POST['date_debut'];
                    $date_fin = $_POST['date_fin'];

                    if (strtotime($date_debut) >= strtotime($date_fin)) {
                        $_SESSION['error'] = "La date de début doit être antérieure à la date de fin";
                        break;
                    }

                    // Vérifier les chevauchements (exclure l'année en cours de modification)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM annee_academique WHERE id_ac != ? AND (
                        (date_debut <= ? AND date_fin >= ?) OR 
                        (date_debut <= ? AND date_fin >= ?) OR 
                        (date_debut >= ? AND date_fin <= ?))");
                    $stmt->execute([$id_ac, $date_debut, $date_debut, $date_fin, $date_fin, $date_debut, $date_fin]);

                    if ($stmt->fetchColumn() > 0) {
                        $_SESSION['error'] = "Cette période chevauche avec une autre année académique";
                        break;
                    }

                    $stmt = $pdo->prepare("UPDATE annee_academique SET date_debut = ?, date_fin = ? WHERE id_ac = ?");
                    $stmt->execute([$date_debut, $date_fin, $id_ac]);
                    $_SESSION['success'] = "L'année académique a été modifiée avec succès";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Une erreur est survenue lors de la modification : " . $e->getMessage();
                }
                break;

            case 'delete_selected':
                if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
                    $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
                    if (!empty($ids)) {
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $stmt = $pdo->prepare("DELETE FROM annee_academique WHERE id_ac IN ($placeholders) AND statut_annee != 'En cours'");
                        $stmt->execute($ids);
                        $_SESSION['success'] = count($ids) . " année(s) académique(s) supprimée(s) avec succès.";
                    } else {
                        $_SESSION['error'] = "Aucune année sélectionnée.";
                    }
                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                    exit;
                }
                break;
        }
    }
    // Redirection pour éviter la soumission multiple
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Récupération des années académiques
try {
    // --- Recherche, filtres et pagination ---
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;

    // Construction de la requête avec filtres
    $where_conditions = [];
    $params = [];

    if ($search !== '') {
        $where_conditions[] = "(id_ac LIKE ? OR date_debut LIKE ? OR date_fin LIKE ? OR statut_annee LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }

    if ($status_filter !== '') {
        $where_conditions[] = "statut_annee = ?";
        $params[] = $status_filter;
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Compter le total pour la pagination
    $sql_count = "SELECT COUNT(*) FROM annee_academique $where_clause";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_annees = $stmt_count->fetchColumn();
    $total_pages = max(1, ceil($total_annees / $per_page));

    // Récupérer les années filtrées et paginées
    $sql = "SELECT * FROM annee_academique $where_clause ORDER BY date_debut DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $annees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $annees = [];
    $_SESSION['error'] = "Erreur lors de la récupération des données";
}

// Récupération des détails d'une année spécifique si demandé
$annee_details = null;
if (isset($_GET['view']) && !empty($_GET['view'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM annee_academique WHERE id_ac = ?");
        $stmt->execute([$_GET['view']]);
        $annee_details = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la récupération des détails";
    }
}

// Traitement des actions GET
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'set_current':
            if (isset($_GET['id'])) {
                try {
                    $pdo->beginTransaction();
                    // Mettre à jour le statut de toutes les années
                    $pdo->exec("UPDATE annee_academique SET statut_annee = 'Terminée'");
                    // Définir l'année sélectionnée comme année courante
                    $stmt = $pdo->prepare("UPDATE annee_academique SET statut_annee = 'En cours' WHERE id_ac = ?");
                    $stmt->execute([$_GET['id']]);
                    $pdo->commit();
                    $_SESSION['success'] = "L'année académique a été définie comme année courante";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Une erreur est survenue lors de la modification du statut";
                }
            }
            break;

        case 'set_terminated':
            if (isset($_GET['id'])) {
                try {
                    $stmt = $pdo->prepare("UPDATE annee_academique SET statut_annee = 'Terminée' WHERE id_ac = ?");
                    $stmt->execute([$_GET['id']]);
                    $_SESSION['success'] = "L'année académique a été marquée comme terminée";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Une erreur est survenue lors de la modification du statut";
                }
            }
            break;

        case 'delete':
            if (isset($_GET['id'])) {
                try {
                    // Vérifier si l'année est en cours avant suppression
                    $stmt = $pdo->prepare("SELECT statut_annee FROM annee_academique WHERE id_ac = ?");
                    $stmt->execute([$_GET['id']]);
                    $statut = $stmt->fetchColumn();

                    if ($statut === 'En cours') {
                        $_SESSION['error'] = "Impossible de supprimer l'année académique en cours";
                        break;
                    }

                    $stmt = $pdo->prepare("DELETE FROM annee_academique WHERE id_ac = ?");
                    $stmt->execute([$_GET['id']]);
                    $_SESSION['success'] = "L'année académique a été supprimée avec succès";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Une erreur est survenue lors de la suppression : " . $e->getMessage();
                }
            }
            break;
    }
    // Rediriger pour éviter la soumission multiple
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Récupération des détails pour l'édition
$annee_edit = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM annee_academique WHERE id_ac = ?");
        $stmt->execute([$_GET['edit']]);
        $annee_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la récupération des données d'édition";
    }
}

// Récupération des messages de notification
$success_message = $_SESSION['success'] ?? null;
$error_message = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Statistiques
$sql_stats = "SELECT 
    COUNT(*) as total_annees,
    COUNT(CASE WHEN statut_annee = 'En cours' THEN 1 END) as annees_en_cours,
    COUNT(CASE WHEN statut_annee = 'Terminée' THEN 1 END) as annees_terminees,
    COUNT(CASE WHEN statut_annee = 'En attente' THEN 1 END) as annees_en_attente
    FROM annee_academique";
$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Années Académiques - GSCV+</title>
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
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(26, 82, 118, 0.1), 0 10px 10px -5px rgba(26, 82, 118, 0.04);
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">
        <!-- Contenu principal -->
        <main class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- En-tête de page -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                <div class="border-l-4 border-primary bg-white rounded-r-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-primary/10 rounded-lg p-3 mr-4">
                                <i class="fas fa-calendar-alt text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Années Académiques</h1>
                                <p class="text-gray-600">Gestion des années académiques</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Connecté en tant que</div>
                                <div class="font-semibold text-gray-900"><?php echo $fullname; ?></div>
                                <div class="text-sm text-primary"><?php echo $lib_user_type; ?></div>
                            </div>
                            <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold text-lg">
                                <?php echo substr($fullname, 0, 1); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 animate-slide-up">
                <!-- Total années -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des années</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_annees']); ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-calendar text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Années en cours -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">En cours</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['annees_en_cours']); ?></p>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-play-circle text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Années terminées -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-gray-500 overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Terminées</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['annees_terminees']); ?></p>
                            </div>
                            <div class="bg-gray-500/10 rounded-full p-4">
                                <i class="fas fa-check-circle text-2xl text-gray-500"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Années en attente -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-warning overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">En attente</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['annees_en_attente']); ?></p>
                            </div>
                            <div class="bg-warning/10 rounded-full p-4">
                                <i class="fas fa-clock text-2xl text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des années académiques -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                <!-- Barre d'actions -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                        <!-- Bouton de retour -->
                        <a href="?page=parametres_generaux" 
                           class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Retour aux paramètres
                        </a>

                        <!-- Recherche -->
                        <div class="flex-1 w-full lg:w-auto">
                            <form method="GET" class="flex gap-3">
                                <input type="hidden" name="liste" value="annees_academiques">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" 
                                           name="search" 
                                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           placeholder="Rechercher une année académique..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <button type="submit" 
                                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                    <i class="fas fa-search mr-2"></i>
                                    Rechercher
                                </button>
                            </form>
                        </div>

                        <!-- Bouton d'ajout -->
                        <button onclick="showAddModal()" 
                                class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-green-600 transition-colors duration-200 flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter une année académique
                        </button>
                    </div>
                </div>

                <!-- Messages d'alerte -->
                <?php if ($success_message): ?>
                    <div class="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-green-800"><?php echo htmlspecialchars($success_message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <span class="text-red-800"><?php echo htmlspecialchars($error_message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filtres -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <form method="GET" class="flex gap-4 items-center">
                        <input type="hidden" name="liste" value="annees_academiques">
                        <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                        <select class="px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" 
                                name="status" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="En cours" <?php echo ($status_filter === 'En cours') ? 'selected' : ''; ?>>En cours</option>
                            <option value="Terminée" <?php echo ($status_filter === 'Terminée') ? 'selected' : ''; ?>>Terminée</option>
                        </select>
                    </form>
                </div>

                <!-- Table des années académiques -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date de début
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date de fin
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
                            <?php if (empty($annees)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-gray-500 text-lg">Aucune année académique trouvée</p>
                                            <p class="text-gray-400 text-sm">Essayez de modifier vos critères de recherche</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($annees as $annee):
                                    try {
                                        $debut = new DateTime($annee['date_debut']);
                                        $fin = new DateTime($annee['date_fin']);
                                        $id_annee = htmlspecialchars($annee['id_ac']);
                                    } catch (Exception $e) {
                                        continue;
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" class="row-checkbox rounded border-gray-300 text-primary focus:ring-primary" value="<?php echo htmlspecialchars($annee['id_ac']); ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                <?php echo $id_annee; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $debut->format('d/m/Y'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo $fin->format('d/m/Y'); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php
                                                echo $annee['statut_annee'] === 'En cours' ? 'bg-green-100 text-green-800' : 
                                                    ($annee['statut_annee'] === 'Terminée' ? 'bg-gray-100 text-gray-800' : 'bg-yellow-100 text-yellow-800');
                                            ?>">
                                                <?php echo htmlspecialchars($annee['statut_annee']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="viewAnnee('<?php echo urlencode($id_annee); ?>')" 
                                                        class="text-primary hover:text-primary-light transition-colors duration-200" 
                                                        title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editAnnee('<?php echo urlencode($id_annee); ?>')" 
                                                        class="text-accent hover:text-green-600 transition-colors duration-200" 
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($annee['statut_annee'] !== 'En cours'): ?>
                                                    <button onclick="setCurrentAnnee('<?php echo htmlspecialchars($id_annee); ?>')" 
                                                            class="text-warning hover:text-orange-600 transition-colors duration-200" 
                                                            title="Définir comme année courante">
                                                        <i class="fas fa-star"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($annee['statut_annee'] === 'En cours'): ?>
                                                    <button onclick="setTerminatedAnnee('<?php echo htmlspecialchars($id_annee); ?>')" 
                                                            class="text-gray-500 hover:text-gray-700 transition-colors duration-200" 
                                                            title="Marquer comme terminée">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="showDeleteModal('<?php echo htmlspecialchars($id_annee); ?>', '<?php echo htmlspecialchars($id_annee); ?>')" 
                                                        class="text-danger hover:text-red-600 transition-colors duration-200" 
                                                        title="Supprimer">
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Affichage de <span class="font-medium"><?php echo ($offset + 1); ?></span> à <span class="font-medium"><?php echo min($offset + $per_page, $total_annees); ?></span> sur <span class="font-medium"><?php echo $total_annees; ?></span> résultats
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($page > 1): ?>
                                    <a href="?liste=annees_academiques&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=1" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?liste=annees_academiques&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <a href="?liste=annees_academiques&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $i; ?>" 
                                       class="px-3 py-2 text-sm font-medium <?php if ($i == $page): ?>text-white bg-primary border-primary<?php else: ?>text-gray-500 bg-white border-gray-300 hover:bg-gray-50<?php endif; ?> border rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?liste=annees_academiques&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?liste=annees_academiques&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $total_pages; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal d'ajout -->
    <div id="addModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Ajouter une année académique</h3>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" id="addForm">
                    <input type="hidden" name="action" value="add">
                    <div class="space-y-4">
                        <div>
                            <label for="add_date_debut" class="block text-sm font-medium text-gray-700 mb-1">Date de début *</label>
                            <input type="date" 
                                   id="add_date_debut" 
                                   name="date_debut" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        <div>
                            <label for="add_date_fin" class="block text-sm font-medium text-gray-700 mb-1">Date de fin *</label>
                            <input type="date" 
                                   id="add_date_fin" 
                                   name="date_fin" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" 
                                onclick="closeAddModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200">
                            Annuler
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-light transition-colors duration-200">
                            Ajouter
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="confirmation-modal-single" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                <p id="deleteSingleMessage" class="text-sm text-gray-500 mb-6">Êtes-vous sûr de vouloir supprimer cette année académique ?</p>
                <div class="flex justify-center space-x-3">
                    <button onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200">
                        Annuler
                    </button>
                    <form id="deleteForm" method="GET" class="inline">
                        <input type="hidden" id="delete_action_id" name="id">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" 
                                class="px-4 py-2 bg-danger text-white rounded-md hover:bg-red-600 transition-colors duration-200">
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addForm').reset();
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
        }

        function viewAnnee(id) {
            window.location.href = '?liste=annees_academiques&view=' + id;
        }

        function editAnnee(id) {
            window.location.href = '?liste=annees_academiques&edit=' + id;
        }

        function setCurrentAnnee(id) {
            if (confirm('Voulez-vous définir cette année comme année académique courante ?')) {
                window.location.href = '?liste=annees_academiques&action=set_current&id=' + id;
            }
        }

        function setTerminatedAnnee(id) {
            if (confirm('Voulez-vous marquer cette année académique comme terminée ?')) {
                window.location.href = '?liste=annees_academiques&action=set_terminated&id=' + id;
            }
        }

        function showDeleteModal(id, libelle) {
            document.getElementById('delete_action_id').value = id;
            document.getElementById('deleteSingleMessage').innerHTML = "Êtes-vous sûr de vouloir supprimer l'année académique : '<b>" + libelle + "</b>' ?<br><span class='text-red-600 text-xs'>Cette action est irréversible.</span>";
            document.getElementById('confirmation-modal-single').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').classList.add('hidden');
        }

        // Sélection/désélection toutes les cases
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });

        // Validation des formulaires
        document.getElementById('addForm').addEventListener('submit', function(e) {
            const dateDebut = document.getElementById('add_date_debut').value;
            const dateFin = document.getElementById('add_date_fin').value;

            if (dateDebut >= dateFin) {
                e.preventDefault();
                alert('La date de début doit être antérieure à la date de fin');
                return false;
            }
        });

        // Fermer la modale si on clique en dehors
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('addModal')) {
                closeAddModal();
            }
            if (event.target == document.getElementById('confirmation-modal-single')) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>