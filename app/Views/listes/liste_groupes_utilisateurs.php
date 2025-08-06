<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'groupes_utilisateurs') {
    return;
}

require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lib_gu'])) {
        $lib_groupe = trim($_POST['lib_gu']);

        if (!empty($lib_groupe)) {
            if (isset($_POST['id_gu']) && !empty($_POST['id_gu'])) {
                // Modification d'un groupe existant
                $id_groupe = intval($_POST['id_gu']);
                try {
                    $sql = "UPDATE groupe_utilisateur SET lib_gu = ? WHERE id_gu = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_groupe, $id_groupe]);

                    // Mise à jour des traitements
                    if (isset($_POST['traitements'])) {
                        // Supprimer les anciennes associations
                        $sql = "DELETE FROM rattacher WHERE id_gu = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$id_groupe]);

                        // Ajouter les nouvelles associations
                        $sql = "INSERT INTO rattacher (id_gu, id_traitement) VALUES (?, ?)";
                        $stmt = $pdo->prepare($sql);
                        foreach ($_POST['traitements'] as $id_traitement) {
                            $stmt->execute([$id_groupe, intval($id_traitement)]);
                        }
                    }

                    $_SESSION['success'] = "Groupe modifié avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la modification du groupe.";
                }
            } else {
                // Ajout d'un nouveau groupe
                try {
                    $sql = "INSERT INTO groupe_utilisateur (lib_gu) VALUES (?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_groupe]);
                    $_SESSION['success'] = "Groupe ajouté avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de l'ajout du groupe.";
                }
            }

            // Mise à jour des permissions
            updateUserPermissions();
        } else {
            $_SESSION['error'] = "Le libellé ne peut pas être vide.";
        }

        header('Location: ?page=parametres_generaux&liste=groupes_utilisateurs');
        exit;
    }

    // Suppression d'un groupe
    if (isset($_POST['delete_groupe_id'])) {
        $id_groupe = intval($_POST['delete_groupe_id']);

        if ($id_groupe > 0) {
            try {
                // Supprimer d'abord les associations dans la table rattacher
                $sql = "DELETE FROM rattacher WHERE id_gu = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_groupe]);

                // Puis supprimer le groupe
                $sql = "DELETE FROM groupe_utilisateur WHERE id_gu = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_groupe]);

                $_SESSION['success'] = "Groupe supprimé avec succès.";
                updateUserPermissions();
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression du groupe.";
            }
        }

        header('Location: ?page=parametres_generaux&liste=groupes_utilisateurs');
        exit;
    }

    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM groupe_utilisateur WHERE id_gu IN ($placeholders)");
                $stmt->execute($ids);
                $_SESSION['success'] = count($ids) . " groupe(s) supprimé(s) avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression multiple.";
            }
        } else {
            $_SESSION['error'] = "Aucun groupe sélectionné.";
        }
        header('Location: ?page=parametres_generaux&liste=groupes_utilisateurs');
        exit;
    }
}

// Fonction pour mettre à jour les permissions de l'utilisateur
function updateUserPermissions()
{
    global $pdo;

    // Récupérer l'ID de l'utilisateur connecté
    $id_utilisateur = $_SESSION['user_id'];

    // Récupérer les traitements associés à l'utilisateur via ses groupes et types d'utilisateur
    $sql = "SELECT DISTINCT t.lib_traitement 
            FROM traitement t 
            JOIN rattacher r ON t.id_traitement = r.id_traitement 
            JOIN groupe_utilisateur g ON r.id_gu = g.id_gu 
            JOIN type_a_groupe tag ON g.id_gu = tag.id_gu
            JOIN type_utilisateur tu ON tag.id_tu = tu.id_tu
            JOIN utilisateur_type_utilisateur utu ON tu.id_tu = utu.id_tu
            JOIN utilisateur u ON utu.id_utilisateur = u.id_utilisateur 
            WHERE u.id_utilisateur = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_utilisateur]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Mettre à jour les permissions dans la session
    $_SESSION['user_permissions'] = array_map('strtolower', $permissions);
}

// Récupération des informations pour l'affichage
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id_groupe = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- Recherche et pagination ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if ($search !== '') {
    $where_conditions[] = "g.lib_gu LIKE ?";
    $params[] = "%$search%";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) 
              FROM groupe_utilisateur g
              LEFT JOIN rattacher r ON g.id_gu = r.id_gu
              LEFT JOIN traitement t ON r.id_traitement = t.id_traitement
              LEFT JOIN type_a_groupe tag ON g.id_gu = tag.id_gu
              LEFT JOIN type_utilisateur tu ON tag.id_tu = tu.id_tu
              $where_clause
              GROUP BY g.id_gu";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_groupes = $stmt_count->rowCount();
$total_pages = max(1, ceil($total_groupes / $per_page));

// Récupération des groupes d'utilisateurs avec leurs traitements et type d'utilisateur
        $sql = "SELECT g.*,
               GROUP_CONCAT(DISTINCT t.nom_traitement) as traitements,
               GROUP_CONCAT(DISTINCT t.id_traitement) as id_traitements,
               COUNT(DISTINCT t.id_traitement) as nombre_traitements,
               GROUP_CONCAT(DISTINCT tu.lib_tu) as lib_tu
        FROM groupe_utilisateur g
        LEFT JOIN rattacher r ON g.id_gu = r.id_gu
        LEFT JOIN traitement t ON r.id_traitement = t.id_traitement
        LEFT JOIN type_a_groupe tag ON g.id_gu = tag.id_gu
        LEFT JOIN type_utilisateur tu ON tag.id_tu = tu.id_tu
        $where_clause
        GROUP BY g.id_gu
        ORDER BY g.id_gu
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de tous les groupes pour la pagination (sans limite)
$sql_all = "SELECT g.*, 
                   GROUP_CONCAT(DISTINCT t.nom_traitement) as traitements,
                   GROUP_CONCAT(DISTINCT t.id_traitement) as id_traitements,
                   COUNT(DISTINCT t.id_traitement) as nombre_traitements,
                   GROUP_CONCAT(DISTINCT tu.lib_tu) as lib_tu
            FROM groupe_utilisateur g
            LEFT JOIN rattacher r ON g.id_gu = r.id_gu
            LEFT JOIN traitement t ON r.id_traitement = t.id_traitement
            LEFT JOIN type_a_groupe tag ON g.id_gu = tag.id_gu
            LEFT JOIN type_utilisateur tu ON tag.id_tu = tu.id_tu
            $where_clause
            GROUP BY g.id_gu
            ORDER BY g.id_gu";
$stmt_all = $pdo->prepare($sql_all);
$stmt_all->execute($params);
$all_groupes = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
$total_groupes = count($all_groupes);
$total_pages = max(1, ceil($total_groupes / $per_page));

// Récupération des groupes pour la page courante
$groupes_page = array_slice($all_groupes, $offset, $per_page);

// Récupération de tous les traitements pour la modale de modification
$traitements = $pdo->query("SELECT * FROM traitement ORDER BY nom_traitement")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des informations du groupe sélectionné si nécessaire
$groupe_selectionne = null;
if ($id_groupe > 0) {
    foreach ($groupes as $groupe) {
        if ($groupe['id_gu'] == $id_groupe) {
            $groupe_selectionne = $groupe;
            break;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Groupes d'Utilisateurs - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2980b9',
                        accent: '#27ae60',
                        warning: '#f39c12',
                        danger: '#e74c3c',
                        success: '#27ae60'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-in-out',
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

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(26, 82, 118, 0.1), 0 10px 10px -5px rgba(26, 82, 118, 0.04);
        }

        .modal-transition {
            transition: all 0.3s ease-in-out;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        .btn-icon {
            transition: all 0.2s ease-in-out;
        }

        .btn-icon:hover {
            transform: scale(1.1);
        }

        .bg-gradient {
            background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
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
                                <i class="fas fa-users text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Groupes d'Utilisateurs</h1>
                                <p class="text-gray-600">Gestion des groupes d'utilisateurs du système</p>
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

            <!-- KPI Card -->
            <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8 animate-slide-up">
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des groupes d'utilisateurs</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($total_groupes); ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-users text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des groupes -->
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
                                <input type="hidden" name="liste" value="groupes_utilisateurs">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text"
                                        name="search"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Rechercher un groupe d'utilisateurs..."
                                        value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <button type="submit"
                                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                    <i class="fas fa-search mr-2"></i>
                                    Rechercher
                                </button>
                            </form>
                        </div>

                        <!-- Boutons d'action -->
                        <div class="flex gap-2">
                            <button onclick="openDeleteMultipleModal()"
                                class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer la sélection
                            </button>
                            <a href="?page=parametres_generaux&liste=groupes_utilisateurs&action=add"
                                class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-green-600 transition-colors duration-200 flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Ajouter un groupe
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Messages d'alerte -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-lg fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-green-800"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <span class="text-red-800"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Tableau des données -->
                <form id="multipleDeleteForm" method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="groupes_utilisateurs">
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Libellé</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($groupes_page) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-4 block"></i>
                                            <?php echo empty($search) ? 'Aucun groupe trouvé.' : 'Aucun résultat pour "' . htmlspecialchars($search) . '".'; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($groupes_page as $groupe): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" class="row-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                       name="delete_selected_ids[]" value="<?php echo htmlspecialchars($groupe['id_gu']); ?>">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($groupe['id_gu']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($groupe['lib_gu']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <a href="?page=parametres_generaux&liste=groupes_utilisateurs&action=view&id=<?php echo $groupe['id_gu']; ?>" 
                                                       class="btn-icon text-info hover:text-info/80 transition-colors" title="Voir">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?page=parametres_generaux&liste=groupes_utilisateurs&action=edit&id=<?php echo $groupe['id_gu']; ?>" 
                                                       class="btn-icon text-warning hover:text-warning/80 transition-colors" title="Modifier">
                                                        <i class="fas fa-pen"></i>
                                                    </a>
                                                    <button type="button" onclick="confirmDeleteSingle(<?php echo $groupe['id_gu']; ?>, '<?php echo htmlspecialchars($groupe['lib_gu'], ENT_QUOTES); ?>')" 
                                                            class="btn-icon text-danger hover:text-danger/80 transition-colors" title="Supprimer">
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
                </form>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="bg-white rounded-xl shadow-lg px-6 py-4 animate-slide-up">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Page <?php echo $page; ?> sur <?php echo $total_pages; ?>
                        </div>
                        <div class="flex items-center space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=1&liste=groupes_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    «
                                </a>
                                <a href="?page=<?php echo $page - 1; ?>&liste=groupes_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    ‹
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?>&liste=groupes_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-primary border-primary' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'; ?> border rounded-lg transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&liste=groupes_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    ›
                                </a>
                                <a href="?page=<?php echo $total_pages; ?>&liste=groupes_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    »
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modals -->
    <!-- Modal pour afficher les détails d'un groupe -->
    <?php if ($action === 'view' && $groupe_selectionne): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Détails du groupe : <?php echo htmlspecialchars($groupe_selectionne['lib_gu']); ?>
                    </h2>
                    <button onclick="window.location.href='?page=parametres_generaux&liste=groupes_utilisateurs'" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom du groupe :</label>
                            <span class="mt-1 block text-sm text-gray-900"><?php echo htmlspecialchars($groupe_selectionne['lib_gu']); ?></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type d'utilisateur :</label>
                            <span class="mt-1 block text-sm text-gray-900"><?php echo htmlspecialchars($groupe_selectionne['lib_tu'] ?? 'Non défini'); ?></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nombre de traitements :</label>
                            <span class="mt-1 block text-sm text-gray-900"><?php echo $groupe_selectionne['nombre_traitements']; ?></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Traitements associés :</label>
                            <div class="mt-2 space-y-2">
                                <?php if ($groupe_selectionne['traitements']): ?>
                                    <?php foreach (explode(',', $groupe_selectionne['traitements']) as $traitement): ?>
                                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                            <i class="fas fa-check-circle text-success mr-3"></i>
                                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($traitement); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500">Aucun traitement associé</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                    <a href="?page=parametres_generaux&liste=groupes_utilisateurs" 
                       class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        Fermer
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour modifier un groupe -->
    <?php if ($action === 'edit' && $groupe_selectionne): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Modifier le groupe : <?php echo htmlspecialchars($groupe_selectionne['lib_gu']); ?>
                    </h2>
                    <button onclick="window.location.href='?page=parametres_generaux&liste=groupes_utilisateurs'" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="groupes_utilisateurs">
                    <input type="hidden" name="id_gu" value="<?php echo $groupe_selectionne['id_gu']; ?>">
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="lib_gu" class="block text-sm font-medium text-gray-700">Libellé :</label>
                            <input type="text" id="lib_gu" name="lib_gu" 
                                   value="<?php echo htmlspecialchars($groupe_selectionne['lib_gu']); ?>" 
                                   required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Traitements :</label>
                            <div class="mt-2 max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-3">
                                <?php
                                $id_traitements = explode(',', $groupe_selectionne['id_traitements'] ?? '');
                                foreach ($traitements as $traitement):
                                ?>
                                    <div class="flex items-center mb-2">
                                        <input type="checkbox"
                                            id="traitement_<?php echo $traitement['id_traitement']; ?>"
                                            name="traitements[]"
                                            value="<?php echo $traitement['id_traitement']; ?>"
                                            <?php echo in_array($traitement['id_traitement'], $id_traitements) ? 'checked' : ''; ?>
                                            class="rounded border-gray-300 text-primary focus:ring-primary">
                                        <label for="traitement_<?php echo $traitement['id_traitement']; ?>" class="ml-2 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($traitement['nom_traitement']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                        <a href="?page=parametres_generaux&liste=groupes_utilisateurs" 
                           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            Annuler
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour ajouter un groupe -->
    <?php if ($action === 'add'): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Ajouter un groupe d'utilisateur</h2>
                    <button onclick="window.location.href='?page=parametres_generaux&liste=groupes_utilisateurs'" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="groupes_utilisateurs">
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="lib_gu" class="block text-sm font-medium text-gray-700">Libellé :</label>
                            <input type="text" id="lib_gu" name="lib_gu" required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Traitements :</label>
                            <div class="mt-2 max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-3">
                                <?php foreach ($traitements as $traitement): ?>
                                    <div class="flex items-center mb-2">
                                        <input type="checkbox"
                                            id="traitement_<?php echo $traitement['id_traitement']; ?>"
                                            name="traitements[]"
                                            value="<?php echo $traitement['id_traitement']; ?>"
                                            class="rounded border-gray-300 text-primary focus:ring-primary">
                                        <label for="traitement_<?php echo $traitement['id_traitement']; ?>" class="ml-2 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($traitement['nom_traitement']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                        <a href="?page=parametres_generaux&liste=groupes_utilisateurs" 
                           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            Annuler
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal de confirmation de suppression (simple) -->
    <div id="confirmation-modal-single" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
            <div class="flex items-center justify-center p-6 border-b border-gray-200">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                <p id="deleteSingleMessage" class="text-sm text-gray-600">
                    Êtes-vous sûr de vouloir supprimer ce groupe ?<br>
                    <span class="text-red-600 text-xs">Cette action est irréversible.</span>
                </p>
            </div>
            <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Annuler
                </button>
                <a href="#" id="confirmDeleteBtn" 
                   class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger/90 transition-colors">
                    Supprimer
                </a>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression (simple) -->
    <div id="confirmation-modal-single" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 modal-transition">
            <div class="flex items-center justify-center p-6 border-b border-gray-200">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                <p id="deleteSingleMessage" class="text-sm text-gray-600">
                    Êtes-vous sûr de vouloir supprimer ce groupe ?<br>
                    <span class="text-red-600 text-xs">Cette action est irréversible.</span>
                </p>
            </div>
            <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Annuler
                </button>
                <a href="#" id="confirmDeleteBtn" 
                   class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger/90 transition-colors">
                    Supprimer
                </a>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression multiple -->
    <div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 modal-transition">
            <div class="flex items-center justify-center p-6 border-b border-gray-200">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmation de suppression</h3>
                <p id="deleteMultipleMessage" class="text-sm text-gray-600"></p>
            </div>
            <div id="deleteMultipleFooter" class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
            </div>
        </div>
    </div>

    <script>
        // Fonction pour recharger la sidebar
        function reloadSidebar() {
            const sidebar = window.parent.document.querySelector('.sidebar');
            if (sidebar) {
                window.parent.location.reload();
            }
        }

        // Vérifier si une action a été effectuée
        <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
            reloadSidebar();
        <?php endif; ?>

        // Fonction pour supprimer un groupe
        function confirmDeleteSingle(id_groupe, nom_groupe) {
            document.getElementById('deleteSingleMessage').innerHTML = 
                `Êtes-vous sûr de vouloir supprimer le groupe <b>"${nom_groupe}"</b> ?<br><span class="text-red-600 text-xs">Cette action est irréversible.</span>`;
            document.getElementById('confirmDeleteBtn').href = 
                '?page=parametres_generaux&liste=groupes_utilisateurs&action=delete&id=' + id_groupe;
            document.getElementById('confirmation-modal-single').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').classList.add('hidden');
        }

        // Fermer les modales si on clique en dehors
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.fixed.inset-0');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }

        // Empêcher la fermeture des modales lors du clic sur leur contenu
        document.querySelectorAll('.bg-white.rounded-lg').forEach(function(content) {
            content.onclick = function(event) {
                event.stopPropagation();
            }
        });

        // Fermer les modales avec la touche Échap
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDeleteModal();
                document.getElementById('confirmation-modal').classList.add('hidden');
            }
        });

        // Sélection groupée
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                updateBulkDeleteButton();
            });
        }
        
        checkboxes.forEach(cb => cb.addEventListener('change', function() {
            if (!this.checked) {
                if (selectAll) selectAll.checked = false;
            } else if ([...checkboxes].every(c => c.checked)) {
                if (selectAll) selectAll.checked = true;
            }
            updateBulkDeleteButton();
        }));

        function updateBulkDeleteButton() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const bulkDeleteBtn = document.querySelector('button[onclick="openDeleteMultipleModal()"]');
            if (bulkDeleteBtn) {
                bulkDeleteBtn.disabled = checked.length === 0;
                bulkDeleteBtn.classList.toggle('opacity-50', checked.length === 0);
            }
        }

        // Suppression multiple
        function openDeleteMultipleModal() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const msg = document.getElementById('deleteMultipleMessage');
            const footer = document.getElementById('deleteMultipleFooter');
            
            if (checked.length === 0) {
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins un groupe à supprimer.";
                footer.innerHTML = '<button type="button" onclick="closeDeleteMultipleModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> groupe(s) sélectionné(s) ?<br><span class="text-red-600 text-xs">Cette action est irréversible.</span>`;
                footer.innerHTML = 
                    '<button type="button" onclick="confirmDeleteMultiple()" class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger/90 transition-colors">Oui, supprimer</button>' +
                    '<button type="button" onclick="closeDeleteMultipleModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">Non</button>';
            }
            document.getElementById('confirmation-modal').classList.remove('hidden');
        }

        function closeDeleteMultipleModal() {
            document.getElementById('confirmation-modal').classList.add('hidden');
        }

        function confirmDeleteMultiple() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const ids = Array.from(checked).map(cb => cb.value);
            document.getElementById('delete_selected_ids').value = ids.join(',');
            document.getElementById('multipleDeleteForm').submit();
        }

        // Initialiser l'état du bouton de suppression multiple
        updateBulkDeleteButton();
    </script>
</body>
</html>