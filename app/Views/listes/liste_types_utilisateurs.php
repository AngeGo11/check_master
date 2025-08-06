<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'types_utilisateurs') {
    return;
}

require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// Paramètres de recherche et pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$types_par_page = 10;

// Construction de la requête avec recherche
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "lib_tu LIKE ?";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Comptage total pour pagination
$count_sql = "SELECT COUNT(*) FROM type_utilisateur $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_types = $count_stmt->fetchColumn();

$total_pages = ceil($total_types / $types_par_page);
$offset = ($page - 1) * $types_par_page;

// Récupération des types avec pagination
$sql = "SELECT * FROM type_utilisateur $where_clause ORDER BY id_tu LIMIT $types_par_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lib_tu'])) {
        $lib_tu = trim($_POST['lib_tu']);
        
        if (!empty($lib_tu)) {
            if (isset($_POST['id_tu']) && !empty($_POST['id_tu'])) {
                // Modification
                $id_tu = intval($_POST['id_tu']);
                try {
                    $sql = "UPDATE type_utilisateur SET lib_tu = ? WHERE id_tu = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_tu, $id_tu]);
                    $_SESSION['success'] = "Type d'utilisateur modifié avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la modification du type d'utilisateur.";
                }
            } else {
                // Ajout
                try {
                    $sql = "INSERT INTO type_utilisateur (lib_tu) VALUES (?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_tu]);
                    $_SESSION['success'] = "Type d'utilisateur ajouté avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de l'ajout du type d'utilisateur.";
                }
            }
        } else {
            $_SESSION['error'] = "Le libellé ne peut pas être vide.";
        }

        // Redirection avec conservation des paramètres
        header('Location: ?page=parametres_generaux&liste=types_utilisateurs');
        exit;
    }

    // Suppression d'un type
    if (isset($_POST['delete_type_id'])) {
        $id_tu = intval($_POST['delete_type_id']);

        if ($id_tu > 0) {
            try {
                $sql = "DELETE FROM type_utilisateur WHERE id_tu = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_tu]);
                $_SESSION['success'] = "Type d'utilisateur supprimé avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression du type d'utilisateur.";
            }
        }

        // Redirection avec conservation des paramètres
        header('Location: ?page=parametres_generaux&liste=types_utilisateurs');
        exit;
    }

    // Suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM type_utilisateur WHERE id_tu IN ($placeholders)");
                $stmt->execute($ids);
                $_SESSION['success'] = count($ids) . " type(s) d'utilisateur supprimé(s) avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression multiple.";
            }
        } else {
            $_SESSION['error'] = "Aucun type d'utilisateur sélectionné.";
        }
        header('Location: ?page=parametres_generaux&liste=types_utilisateurs');
        exit;
    }
}

// Récupération d'un type pour modification
$type_to_edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id_tu = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM type_utilisateur WHERE id_tu = ?");
    $stmt->execute([$id_tu]);
    $type_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Types d'Utilisateurs - GSCV+</title>
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
                                <i class="fas fa-user-tag text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Types d'Utilisateurs</h1>
                                <p class="text-gray-600">Gestion des types d'utilisateurs du système</p>
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
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des types d'utilisateurs</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($total_types); ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-user-tag text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des types -->
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
                                <input type="hidden" name="liste" value="types_utilisateurs">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text"
                                        name="search"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Rechercher un type d'utilisateur..."
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
                                Ajouter un type
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
                    <input type="hidden" name="liste" value="types_utilisateurs">
                    
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
                                <?php if (count($types) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-4 block"></i>
                                            <?php echo empty($search) ? 'Aucun type trouvé.' : 'Aucun résultat pour "' . htmlspecialchars($search) . '".'; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($types as $type): ?>
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <input type="checkbox" class="row-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                       name="delete_selected_ids[]" value="<?php echo htmlspecialchars($type['id_tu']); ?>">
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($type['id_tu']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($type['lib_tu']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex items-center space-x-2">
                                                    <button onclick="showEditModal(<?php echo $type['id_tu']; ?>, '<?php echo htmlspecialchars($type['lib_tu'], ENT_QUOTES); ?>')" 
                                                            class="btn-icon text-warning hover:text-warning/80 transition-colors" title="Modifier">
                                                        <i class="fas fa-pen"></i>
                                                    </button>
                                                    <button onclick="confirmDeleteSingle(<?php echo $type['id_tu']; ?>, '<?php echo htmlspecialchars($type['lib_tu'], ENT_QUOTES); ?>')" 
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
                                <a href="?page=1&liste=types_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    «
                                </a>
                                <a href="?page=<?php echo $page - 1; ?>&liste=types_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    ‹
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <a href="?page=<?php echo $i; ?>&liste=types_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-white bg-primary border-primary' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'; ?> border rounded-lg transition-colors">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&liste=types_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    ›
                                </a>
                                <a href="?page=<?php echo $total_pages; ?>&liste=types_utilisateurs<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
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
    <!-- Modal pour ajouter/modifier un type d'utilisateur -->
    <div id="typeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 modal-transition">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 id="modalTitle" class="text-xl font-semibold text-gray-900">Ajouter un type d'utilisateur</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="typeForm" method="POST">
                <input type="hidden" name="page" value="parametres_generaux">
                <input type="hidden" name="liste" value="types_utilisateurs">
                <input type="hidden" id="id_tu" name="id_tu">
                
                <div class="p-6 space-y-4">
                    <div>
                        <label for="lib_tu" class="block text-sm font-medium text-gray-700">Libellé :</label>
                        <input type="text" id="lib_tu" name="lib_tu" required
                               class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        Annuler
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation de suppression (simple) -->
    <div id="confirmation-modal-single" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 modal-transition">
            <div class="flex items-center justify-center p-6 border-b border-gray-200">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
            </div>
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                <p id="deleteSingleMessage" class="text-sm text-gray-600">
                    Êtes-vous sûr de vouloir supprimer ce type d'utilisateur ?<br>
                    <span class="text-red-600 text-xs">Cette action est irréversible.</span>
                </p>
            </div>
            <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Annuler
                </button>
                <button type="button" onclick="confirmDeleteSingle()" 
                        class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger/90 transition-colors">
                    Supprimer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal d'ajout/modification -->
    <div id="typeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 modal-transition">
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 id="modalTitle" class="text-xl font-semibold text-gray-900">Ajouter un type d'utilisateur</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="typeForm" method="POST">
                <input type="hidden" name="page" value="parametres_generaux">
                <input type="hidden" name="liste" value="types_utilisateurs">
                <input type="hidden" id="id_tu" name="id_tu" value="">
                
                <div class="p-6 space-y-4">
                    <div>
                        <label for="lib_tu" class="block text-sm font-medium text-gray-700">Libellé :</label>
                        <input type="text" id="lib_tu" name="lib_tu" required
                               class="mt-1 block w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-primary focus:border-transparent">
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
                    Êtes-vous sûr de vouloir supprimer ce type d'utilisateur ?<br>
                    <span class="text-red-600 text-xs">Cette action est irréversible.</span>
                </p>
            </div>
            <div class="flex items-center justify-end space-x-3 p-6 border-t border-gray-200">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    Annuler
                </button>
                <button type="button" onclick="confirmDeleteSingle()" 
                        class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger/90 transition-colors">
                    Supprimer
                </button>
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
        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter un type d\'utilisateur';
            document.getElementById('typeForm').reset();
            document.getElementById('id_tu').value = '';
            document.getElementById('typeModal').classList.remove('hidden');
        }

        function showEditModal(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier un type d\'utilisateur';
            document.getElementById('id_tu').value = id;
            document.getElementById('lib_tu').value = libelle;
            document.getElementById('typeModal').classList.remove('hidden');
        }

        function confirmDeleteSingle(id, libelle) {
            document.getElementById('deleteSingleMessage').innerHTML = 
                `Êtes-vous sûr de vouloir supprimer le type d'utilisateur <b>"${libelle}"</b> ?<br><span class="text-red-600 text-xs">Cette action est irréversible.</span>`;
            window.idToDelete = id;
            document.getElementById('confirmation-modal-single').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('typeModal').classList.add('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').classList.add('hidden');
        }

        function confirmDeleteSingle() {
            // Créer un formulaire temporaire pour la suppression
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const pageInput = document.createElement('input');
            pageInput.type = 'hidden';
            pageInput.name = 'page';
            pageInput.value = 'parametres_generaux';
            
            const listeInput = document.createElement('input');
            listeInput.type = 'hidden';
            listeInput.name = 'liste';
            listeInput.value = 'types_utilisateurs';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'delete_type_id';
            idInput.value = window.idToDelete;
            
            form.appendChild(pageInput);
            form.appendChild(listeInput);
            form.appendChild(idInput);
            
            document.body.appendChild(form);
            form.submit();
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
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins un type d'utilisateur à supprimer.";
                footer.innerHTML = '<button type="button" onclick="closeDeleteMultipleModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> type(s) d'utilisateur sélectionné(s) ?<br><span class="text-red-600 text-xs">Cette action est irréversible.</span>`;
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