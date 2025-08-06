<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'traitements') {
    return;
}

require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// Paramètres de recherche et pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$traitements_par_page = 10;

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lib_traitement'])) {
        $lib_traitement = trim($_POST['lib_traitement']);
        $nom_traitement = trim($_POST['nom_traitement']);
        $classe_icone = trim($_POST['classe_icone']);

        if (!empty($lib_traitement) && !empty($nom_traitement) && !empty($classe_icone)) {
            if (isset($_POST['id_traitement']) && !empty($_POST['id_traitement'])) {
                // Modification d'un traitement existant
                $id_traitement = intval($_POST['id_traitement']);
                try {
                    $sql = "UPDATE traitement SET lib_traitement = ?, nom_traitement = ?, classe_icone = ? WHERE id_traitement = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_traitement, $nom_traitement, $classe_icone, $id_traitement]);
                    $_SESSION['success'] = "Traitement modifié avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la modification du traitement.";
                }
            } else {
                // Ajout d'un nouveau traitement
                try {
                    $sql = "INSERT INTO traitement (lib_traitement, nom_traitement, classe_icone) VALUES (?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_traitement, $nom_traitement, $classe_icone]);
                    $_SESSION['success'] = "Traitement ajouté avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de l'ajout du traitement.";
                }
            }

            // Mise à jour des permissions
            updateUserPermissions();
        } else {
            $_SESSION['error'] = "Tous les champs sont obligatoires.";
        }

        // Redirection avec conservation des paramètres
        header('Location: ?page=parametres_generaux&liste=traitements');
        exit;
    }

    // Attribution d'un traitement à un groupe
    if (isset($_POST['attribuer_traitement'])) {
        $id_traitement = intval($_POST['id_traitement']);
        $id_groupe = intval($_POST['id_groupe']);

        if ($id_traitement > 0 && $id_groupe > 0) {
            try {
                $sql = "INSERT INTO rattacher (id_gu, id_traitement) VALUES (?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_groupe, $id_traitement]);
                $_SESSION['success'] = "Traitement attribué avec succès au groupe.";

                // Mise à jour des permissions
                updateUserPermissions();
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de l'attribution du traitement.";
            }
        } else {
            $_SESSION['error'] = "Veuillez sélectionner un traitement et un groupe.";
        }

        // Redirection avec conservation des paramètres
        header('Location: ?page=parametres_generaux&liste=traitements');
        exit;
    }

    // Retrait d'un traitement d'un groupe
    if (isset($_POST['retirer_traitement'])) {
        $id_traitement = intval($_POST['id_traitement']);
        $id_groupe = intval($_POST['id_groupe']);

        if ($id_traitement > 0 && $id_groupe > 0) {
            try {
                $sql = "DELETE FROM rattacher WHERE id_gu = ? AND id_traitement = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_groupe, $id_traitement]);
                $_SESSION['success'] = "Traitement retiré avec succès du groupe.";

                // Mise à jour des permissions
                updateUserPermissions();
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors du retrait du traitement.";
            }
        } else {
            $_SESSION['error'] = "Veuillez sélectionner un traitement et un groupe.";
        }

        // Redirection avec conservation des paramètres
        header('Location: ?page=parametres_generaux&liste=traitements');
        exit;
    }

    // Suppression d'un traitement
    if (isset($_POST['delete_traitement_id'])) {
        $id_traitement = intval($_POST['delete_traitement_id']);

        if ($id_traitement > 0) {
            try {
                // Supprimer d'abord les associations dans la table rattacher
                $sql = "DELETE FROM rattacher WHERE id_traitement = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_traitement]);

                // Puis supprimer le traitement
                $sql = "DELETE FROM traitement WHERE id_traitement = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_traitement]);

                $_SESSION['success'] = "Traitement supprimé avec succès.";

                // Mise à jour des permissions
                updateUserPermissions();
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression du traitement.";
            }
        }

        // Redirection avec conservation des paramètres
        header('Location: ?page=parametres_generaux&liste=traitements');
        exit;
    }

    // Suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM traitement WHERE id_traitement IN ($placeholders)");
                $stmt->execute($ids);
                $_SESSION['success'] = count($ids) . " traitement(s) supprimé(s) avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression multiple.";
            }
        } else {
            $_SESSION['error'] = "Aucun traitement sélectionné.";
        }
        header('Location: ?page=parametres_generaux&liste=traitements');
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
$id_traitement = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Construction de la requête avec recherche
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(t.lib_traitement LIKE ? OR t.nom_traitement LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Comptage total pour pagination
$count_sql = "SELECT COUNT(*) FROM traitement t $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_traitements = $count_stmt->fetchColumn();

$total_pages = ceil($total_traitements / $traitements_par_page);
$offset = ($page - 1) * $traitements_par_page;

// Récupération des traitements avec leurs groupes associés et pagination
$sql = "SELECT t.*, 
           GROUP_CONCAT(DISTINCT g.lib_gu) as groupes_associes, 
           GROUP_CONCAT(DISTINCT g.id_gu) as id_groupes,
           COUNT(DISTINCT r.id_gu) as nombre_groupes
    FROM traitement t
    LEFT JOIN rattacher r ON t.id_traitement = r.id_traitement
    LEFT JOIN groupe_utilisateur g ON r.id_gu = g.id_gu
    $where_clause
    GROUP BY t.id_traitement
    ORDER BY t.id_traitement
    LIMIT $traitements_par_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$traitements_page = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des groupes d'utilisateurs
$groupes = $pdo->query("SELECT * FROM groupe_utilisateur ORDER BY lib_gu")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des informations du traitement sélectionné si nécessaire
$traitement_selectionne = null;
if ($id_traitement > 0) {
    foreach ($traitements_page as $traitement) {
        if ($traitement['id_traitement'] == $id_traitement) {
            $traitement_selectionne = $traitement;
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
    <title>Liste des Traitements - GSCV+</title>
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
                                <i class="fas fa-cogs text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Traitements</h1>
                                <p class="text-gray-600">Gestion des traitements du système</p>
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
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des traitements</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($total_traitements); ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-cogs text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des traitements -->
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
                                <input type="hidden" name="liste" value="traitements">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text"
                                        name="search"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Rechercher un traitement..."
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
                            <button onclick="showAddModal()"
                                class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-green-600 transition-colors duration-200 flex items-center">
                                <i class="fas fa-plus mr-2"></i>
                                Ajouter un traitement
                            </button>
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
                    <input type="hidden" name="liste" value="traitements">
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left">
                                        <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Libellé</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icône</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Groupes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (count($traitements_page) === 0): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-4 block"></i>
                                            <?php echo empty($search) ? 'Aucun traitement trouvé.' : 'Aucun résultat pour "' . htmlspecialchars($search) . '".'; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($traitements_page as $traitement): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" class="row-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                       name="delete_selected_ids[]" value="<?php echo htmlspecialchars($traitement['id_traitement']); ?>">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($traitement['id_traitement']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($traitement['lib_traitement']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($traitement['nom_traitement']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <i class="<?php echo htmlspecialchars($traitement['classe_icone']); ?> text-lg"></i>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo $traitement['nombre_groupes']; ?> groupe(s)
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <button onclick="showEditModal(<?php echo $traitement['id_traitement']; ?>, '<?php echo htmlspecialchars($traitement['lib_traitement'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($traitement['nom_traitement'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($traitement['classe_icone'], ENT_QUOTES); ?>')" 
                                                            class="btn-icon text-warning hover:text-warning/80 transition-colors" title="Modifier">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                    <button onclick="confirmDeleteSingle(<?php echo $traitement['id_traitement']; ?>, '<?php echo htmlspecialchars($traitement['lib_traitement'], ENT_QUOTES); ?>')" 
                                                            class="btn-icon text-danger hover:text-danger/80 transition-colors" title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button onclick="showViewModal(<?php echo $traitement['id_traitement']; ?>, '<?php echo htmlspecialchars($traitement['lib_traitement'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($traitement['nom_traitement'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($traitement['classe_icone'], ENT_QUOTES); ?>')" 
                                                            class="btn-icon text-info hover:text-info/80 transition-colors" title="Voir">
                                                        <i class="fas fa-eye"></i>
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
                                <a href="?page=1&liste=traitements<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    «
                                </a>
                                <a href="?page=<?php echo $page - 1; ?>&liste=traitements<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    ‹
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?>&liste=traitements<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-primary border-primary' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'; ?> border rounded-lg transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&liste=traitements<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    ›
                                </a>
                                <a href="?page=<?php echo $total_pages; ?>&liste=traitements<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
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
    <!-- Modal pour afficher les détails d'un traitement -->
    <?php if ($action === 'view' && $traitement_selectionne): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Détails du traitement : <?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?>
                    </h2>
                    <button onclick="window.location.href='?page=parametres_generaux&liste=traitements'" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Libellé :</label>
                            <span class="mt-1 block text-sm text-gray-900"><?php echo htmlspecialchars($traitement_selectionne['lib_traitement']); ?></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom :</label>
                            <span class="mt-1 block text-sm text-gray-900"><?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?></span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Icône :</label>
                            <span class="mt-1 block text-sm text-gray-900">
                                <i class="<?php echo htmlspecialchars($traitement_selectionne['classe_icone']); ?> mr-2"></i>
                                <?php echo htmlspecialchars($traitement_selectionne['classe_icone']); ?>
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Groupes associés :</label>
                            <div class="mt-2 space-y-2">
                                <?php if ($traitement_selectionne['groupes_associes']): ?>
                                    <?php foreach (explode(',', $traitement_selectionne['groupes_associes']) as $groupe): ?>
                                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                            <i class="fas fa-users text-success mr-3"></i>
                                            <span class="text-sm text-gray-900"><?php echo htmlspecialchars($groupe); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500">Aucun groupe associé</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nombre de groupes :</label>
                            <span class="mt-1 block text-sm text-gray-900"><?php echo $traitement_selectionne['nombre_groupes']; ?></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                    <a href="?page=parametres_generaux&liste=traitements" 
                       class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        Fermer
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour modifier un traitement -->
    <?php if ($action === 'edit' && $traitement_selectionne): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Modifier le traitement : <?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?>
                    </h2>
                    <button onclick="window.location.href='?page=parametres_generaux&liste=traitements'" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="traitements">
                    <input type="hidden" name="id_traitement" value="<?php echo $traitement_selectionne['id_traitement']; ?>">
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="lib_traitement" class="block text-sm font-medium text-gray-700">Libellé :</label>
                            <input type="text" id="lib_traitement" name="lib_traitement" 
                                   value="<?php echo htmlspecialchars($traitement_selectionne['lib_traitement']); ?>" 
                                   required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="nom_traitement" class="block text-sm font-medium text-gray-700">Nom :</label>
                            <input type="text" id="nom_traitement" name="nom_traitement" 
                                   value="<?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?>" 
                                   required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="classe_icone" class="block text-sm font-medium text-gray-700">Classe de l'icône :</label>
                            <input type="text" id="classe_icone" name="classe_icone" 
                                   value="<?php echo htmlspecialchars($traitement_selectionne['classe_icone']); ?>" 
                                   required placeholder="ex: fas fa-home"
                                   class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                        <a href="?page=parametres_generaux&liste=traitements" 
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

    <!-- Modal pour ajouter un traitement -->
    <?php if ($action === 'add'): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Ajouter un traitement</h2>
                    <button onclick="window.location.href='?page=parametres_generaux&liste=traitements'" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="traitements">
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label for="lib_traitement" class="block text-sm font-medium text-gray-700">Libellé :</label>
                            <input type="text" id="lib_traitement" name="lib_traitement" required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="nom_traitement" class="block text-sm font-medium text-gray-700">Nom :</label>
                            <input type="text" id="nom_traitement" name="nom_traitement" required
                                   class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="classe_icone" class="block text-sm font-medium text-gray-700">Classe de l'icône :</label>
                            <input type="text" id="classe_icone" name="classe_icone" required placeholder="ex: fas fa-home"
                                   class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                        <a href="?page=parametres_generaux&liste=traitements" 
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

    <!-- Modal pour attribuer un traitement -->
    <?php if ($action === 'assign'): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Attribuer un traitement</h2>
                    <button onclick="window.location.href='?page=parametres_generaux&liste=traitements'" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="traitements">
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Traitement :</label>
                            <select name="id_traitement" required
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">Sélectionnez un traitement</option>
                                <?php foreach ($traitements_page as $traitement): ?>
                                    <option value="<?php echo $traitement['id_traitement']; ?>">
                                        <?php echo htmlspecialchars($traitement['nom_traitement']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Groupe d'utilisateurs :</label>
                            <select name="id_groupe" required
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">Sélectionnez un groupe</option>
                                <?php foreach ($groupes as $groupe): ?>
                                    <option value="<?php echo $groupe['id_gu']; ?>">
                                        <?php echo htmlspecialchars($groupe['lib_gu']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                        <a href="?page=parametres_generaux&liste=traitements" 
                           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            Annuler
                        </a>
                        <button type="submit" name="attribuer_traitement" 
                                class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                            Attribuer au groupe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour retirer un traitement -->
    <?php if ($action === 'remove' && $traitement_selectionne): ?>
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">
                        Retirer le traitement : <?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?>
                    </h2>
                    <button onclick="window.location.href='?page=parametres_generaux&liste=traitements'" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="traitements">
                    <input type="hidden" name="id_traitement" value="<?php echo $traitement_selectionne['id_traitement']; ?>">
                    
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Groupe d'utilisateurs :</label>
                            <select name="id_groupe" required
                                    class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">Sélectionnez un groupe</option>
                                <?php
                                $id_groupes = explode(',', $traitement_selectionne['id_groupes'] ?? '');
                                foreach ($groupes as $groupe):
                                    if (in_array($groupe['id_gu'], $id_groupes)):
                                ?>
                                    <option value="<?php echo $groupe['id_gu']; ?>">
                                        <?php echo htmlspecialchars($groupe['lib_gu']); ?>
                                    </option>
                                <?php
                                    endif;
                                endforeach;
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                        <a href="?page=parametres_generaux&liste=traitements" 
                           class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            Annuler
                        </a>
                        <button type="submit" name="retirer_traitement" 
                                class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger/90 transition-colors">
                            Retirer du groupe
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal d'ajout/modification -->
    <div id="traitementModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 modal-transition">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 id="modalTitle" class="text-xl font-semibold text-gray-900">Ajouter un traitement</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="traitementForm" method="POST">
                <input type="hidden" name="page" value="parametres_generaux">
                <input type="hidden" name="liste" value="traitements">
                <input type="hidden" id="id_traitement" name="id_traitement" value="">
                
                <div class="p-6 space-y-4">
                    <div>
                        <label for="lib_traitement" class="block text-sm font-medium text-gray-700">Libellé :</label>
                        <input type="text" id="lib_traitement" name="lib_traitement" required
                               class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label for="nom_traitement" class="block text-sm font-medium text-gray-700">Nom :</label>
                        <input type="text" id="nom_traitement" name="nom_traitement" required
                               class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label for="classe_icone" class="block text-sm font-medium text-gray-700">Classe d'icône :</label>
                        <input type="text" id="classe_icone" name="classe_icone" required
                               class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent"
                               placeholder="fas fa-cog">
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                        Enregistrer
                    </button>
                </div>
            </form>
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
                    Êtes-vous sûr de vouloir supprimer ce traitement ?<br>
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

        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter un traitement';
            document.getElementById('traitementForm').reset();
            document.getElementById('id_traitement').value = '';
            document.getElementById('traitementModal').classList.remove('hidden');
        }

        function showEditModal(id, libelle, nom, icone) {
            document.getElementById('modalTitle').textContent = 'Modifier un traitement';
            document.getElementById('id_traitement').value = id;
            document.getElementById('lib_traitement').value = libelle;
            document.getElementById('nom_traitement').value = nom;
            document.getElementById('classe_icone').value = icone;
            document.getElementById('traitementModal').classList.remove('hidden');
        }

        function showViewModal(id, libelle, nom, icone) {
            document.getElementById('modalTitle').textContent = 'Voir le traitement';
            document.getElementById('id_traitement').value = id;
            document.getElementById('lib_traitement').value = libelle;
            document.getElementById('nom_traitement').value = nom;
            document.getElementById('classe_icone').value = icone;
            document.getElementById('traitementModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('traitementModal').classList.add('hidden');
        }

        // Fonction pour supprimer un traitement
        function confirmDeleteSingle(id_traitement, nom_traitement) {
            document.getElementById('deleteSingleMessage').innerHTML = 
                `Êtes-vous sûr de vouloir supprimer le traitement <b>"${nom_traitement}"</b> ?<br><span class="text-red-600 text-xs">Cette action est irréversible.</span>`;
            document.getElementById('confirmDeleteBtn').href = 
                '?page=parametres_generaux&liste=traitements&action=delete&id=' + id_traitement;
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
                closeModal();
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
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins un traitement à supprimer.";
                footer.innerHTML = '<button type="button" onclick="closeDeleteMultipleModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> traitement(s) sélectionné(s) ?<br><span class="text-red-600 text-xs">Cette action est irréversible.</span>`;
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