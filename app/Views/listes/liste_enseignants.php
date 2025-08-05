<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'enseignants') {
    return;
}

require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// Chargé de compte rendu
if (isset($_POST['id_ens_cr']) && !empty($_POST['id_ens_cr'])) {
    $id_ens_cr = intval($_POST['id_ens_cr']);

    // 1. Désactiver tous les responsables
    $pdo->exec("UPDATE responsable_compte_rendu SET actif = 0");

    // 2. Si ce prof est déjà dans la table, on le met actif = 1. Sinon, on l'insère.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM responsable_compte_rendu WHERE id_ens = ?");
    $stmt->execute([$id_ens_cr]);

    if ($stmt->fetchColumn() > 0) {
        // Déjà existant => update
        $stmt = $pdo->prepare("UPDATE responsable_compte_rendu SET actif = 1 WHERE id_ens = ?");
        $stmt->execute([$id_ens_cr]);
    } else {
        // Nouveau => insert
        $stmt = $pdo->prepare("INSERT INTO responsable_compte_rendu (id_ens, actif) VALUES (?, 1)");
        $stmt->execute([$id_ens_cr]);
    }
}

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
    $where_conditions[] = "(ens.nom_ens LIKE ? OR ens.prenoms_ens LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

if ($status_filter !== '') {
    $where_conditions[] = "rcr.actif = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) FROM responsable_compte_rendu rcr
              JOIN enseignants ens ON ens.id_ens = rcr.id_ens $where_clause";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_enseignants = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_enseignants / $per_page));

// Récupérer les enseignants filtrés et paginés
$sql = "SELECT rcr.*, ens.nom_ens, ens.prenoms_ens 
        FROM responsable_compte_rendu rcr
        JOIN enseignants ens ON ens.id_ens = rcr.id_ens 
        $where_clause 
        ORDER BY ens.nom_ens 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enseignants_datas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$sql_stats = "SELECT 
    COUNT(*) as total_enseignants,
    COUNT(CASE WHEN rcr.actif = 1 THEN 1 END) as enseignants_actifs,
    COUNT(CASE WHEN rcr.actif = 0 THEN 1 END) as enseignants_inactifs
    FROM responsable_compte_rendu rcr
    JOIN enseignants ens ON ens.id_ens = rcr.id_ens";
$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Enseignants - GSCV+</title>
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
                                <i class="fas fa-chalkboard-teacher text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Enseignants</h1>
                                <p class="text-gray-600">Gestion des attributions de la rédaction des comptes rendus de commission</p>
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 animate-slide-up">
                <!-- Total enseignants -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des enseignants</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_enseignants']); ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-users text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enseignants actifs -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Enseignants actifs</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['enseignants_actifs']); ?></p>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-user-check text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enseignants inactifs -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-warning overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Enseignants inactifs</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['enseignants_inactifs']); ?></p>
                            </div>
                            <div class="bg-warning/10 rounded-full p-4">
                                <i class="fas fa-user-times text-2xl text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des enseignants -->
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
                                <input type="hidden" name="page" value="parametres_generaux">
                                <input type="hidden" name="liste" value="enseignants">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" 
                                           name="search" 
                                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           placeholder="Rechercher un enseignant..." 
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
                            Charger de compte rendu
                        </button>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <form method="GET" class="flex gap-4 items-center">
                        <input type="hidden" name="page" value="parametres_generaux">
                        <input type="hidden" name="liste" value="enseignants">
                        <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                        <select class="px-4 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" 
                                name="status" onchange="this.form.submit()">
                            <option value="">Tous les statuts</option>
                            <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Actif</option>
                            <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactif</option>
                        </select>
                    </form>
                </div>

                <!-- Table des enseignants -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    #
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nom de l'enseignant
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
                            <?php if (count($enseignants_datas) === 0): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-user-times text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-gray-500 text-lg">Aucun enseignant trouvé</p>
                                            <p class="text-gray-400 text-sm">Essayez de modifier vos critères de recherche</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($enseignants_datas as $index => $enseignants_data): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                <?php echo $index + 1; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($enseignants_data['nom_ens'] . ' ' . $enseignants_data['prenoms_ens']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= ($enseignants_data['actif'] == 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ($enseignants_data['actif'] == 1) ? 'Actif' : 'Inactif'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                
                                                <button onclick="toggleStatus(<?= $enseignants_data['id_ens']; ?>, <?= $enseignants_data['actif']; ?>)" 
                                                        class="<?= ($enseignants_data['actif'] == 1) ? 'text-danger hover:text-red-600' : 'text-accent hover:text-green-600'; ?> transition-colors duration-200" 
                                                        title="<?= ($enseignants_data['actif'] == 1) ? 'Désactiver' : 'Activer'; ?>">
                                                    <i class="fas <?= ($enseignants_data['actif'] == 1) ? 'fa-user-times' : 'fa-user-check'; ?>"></i>
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
                                Affichage de <span class="font-medium"><?php echo ($offset + 1); ?></span> à <span class="font-medium"><?php echo min($offset + $per_page, $total_enseignants); ?></span> sur <span class="font-medium"><?php echo $total_enseignants; ?></span> résultats
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($page > 1): ?>
                                    <a href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=1" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <a href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $i; ?>" 
                                       class="px-3 py-2 text-sm font-medium <?php if ($i == $page): ?>text-white bg-primary border-primary<?php else: ?>text-gray-500 bg-white border-gray-300 hover:bg-gray-50<?php endif; ?> border rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $total_pages; ?>" 
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

    <!-- Modal pour charger un enseignant d'élaborer le compte rendu -->
    <div id="enseignantsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Assigner compte rendu</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="enseignantsForm" method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="enseignants">
                    <input type="hidden" id="id_ens" name="id_ens">
                    <div class="space-y-4">
                        <div>
                            <label for="id_ens_cr" class="block text-sm font-medium text-gray-700 mb-1">Nom de l'enseignant</label>
                            <select name="id_ens_cr" 
                                    id="id_ens_cr"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">-- Sélectionnez un enseignant --</option>
                                <?php
                                $enseignants = $pdo->prepare("
                                SELECT e.id_ens, e.nom_ens, e.prenoms_ens
                                FROM enseignants e
                                JOIN utilisateur u ON u.login_utilisateur = e.email_ens
                                JOIN posseder p ON p.id_util = u.id_utilisateur
                                WHERE p.id_gu = 8 OR p.id_gu = 9
                                ");
                                $enseignants->execute();
                                $enseignants_list = $enseignants->fetchAll();
                                foreach ($enseignants_list as $ens) {
                                    echo "<option value=\"{$ens['id_ens']}\">{$ens['nom_ens']} {$ens['prenoms_ens']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" 
                                onclick="closeModal()" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200">
                            Annuler
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-light transition-colors duration-200">
                            Assigner
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('enseignantsModal').classList.remove('hidden');
        }

        function editEnseignant(id) {
            // Implémenter la modification d'enseignant
            console.log('Modifier enseignant:', id);
        }

        function toggleStatus(id, currentStatus) {
            // Implémenter le changement de statut
            console.log('Changer statut:', id, currentStatus);
        }

        function closeModal() {
            document.getElementById('enseignantsModal').classList.add('hidden');
        }

        // Fermer la modale si on clique en dehors
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('enseignantsModal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>