<?php

// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'entreprises') {
    return;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// --- Recherche et pagination ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE lib_entr LIKE ?';
    $params[] = "%$search%";
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) FROM entreprise $where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_entreprises = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_entreprises / $per_page));

// Récupérer les entreprises filtrées et paginées
$sql = "SELECT * FROM entreprise $where ORDER BY id_entr DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout ou modification
    if (isset($_POST['lib_entr'])) {
        $lib_entr = $_POST['lib_entr'];
        if (!empty($_POST['id_entr'])) {
            // Modification
            $id = intval($_POST['id_entr']);
            $sql = "UPDATE entreprise SET lib_entr = ? WHERE id_entr = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_entr, $id]);
            $_SESSION['success'] = "Entreprise modifiée avec succès.";
        } else {
            // Ajout
            $sql = "INSERT INTO entreprise (lib_entr) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_entr]);
            $_SESSION['success'] = "Entreprise ajoutée avec succès.";
        }
    }
    // Suppression
    if (isset($_POST['delete_entreprise_id'])) {
        $id = intval($_POST['delete_entreprise_id']);
        $sql = "DELETE FROM entreprise WHERE id_entr = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['success'] = "Entreprise supprimée avec succès.";
    }
    // Suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM entreprise WHERE id_entr IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success'] = count($ids) . " entreprise(s) supprimée(s) avec succès.";
        } else {
            $_SESSION['error'] = "Aucune entreprise sélectionnée.";
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    // Redirection pour éviter le repost
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Récupérer les détails d'une entreprise si demandé
$selected_entreprise = null;
if (isset($_GET['view']) && !empty($_GET['view'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM entreprise WHERE id_entr = ?");
        $stmt->execute([$_GET['view']]);
        $selected_entreprise = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la récupération des détails";
    }
}

?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Entreprises - GSCV+</title>
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
        .detail-group {
            animation: slideInDetail 0.5s ease-out;
            animation-fill-mode: both;
        }
        .detail-group:nth-child(1) { animation-delay: 0.1s; }
        .detail-group:nth-child(2) { animation-delay: 0.2s; }
        .detail-group:nth-child(3) { animation-delay: 0.3s; }
        .detail-group:nth-child(4) { animation-delay: 0.4s; }
        .detail-group:nth-child(5) { animation-delay: 0.5s; }
        .detail-group:nth-child(6) { animation-delay: 0.6s; }
        @keyframes slideInDetail {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
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
                                <i class="fas fa-building text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Entreprises</h1>
                                <p class="text-gray-600">Gestion des entreprises partenaires</p>
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

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 stat-card transition-all duration-300">
                    <div class="flex items-center">
                        <div class="bg-blue-100 rounded-lg p-3 mr-4">
                            <i class="fas fa-building text-2xl text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Entreprises</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_entreprises; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barre d'outils -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0 sm:space-x-4">
                    <!-- Recherche -->
                    <div class="flex-1 max-w-md">
                        <form method="GET" class="flex">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Rechercher une entreprise..."
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <button type="submit" class="ml-3 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200">
                                <i class="fas fa-search mr-2"></i>Rechercher
                            </button>
                        </form>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex space-x-3">
                        <button onclick="openAddModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center">
                            <i class="fas fa-plus mr-2"></i>Ajouter
                        </button>
                        <button onclick="deleteSelected()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 flex items-center">
                            <i class="fas fa-trash mr-2"></i>Supprimer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tableau -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom de l'entreprise</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($entreprises) === 0): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    <div class="flex flex-col items-center py-8">
                                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-lg font-medium">Aucune entreprise trouvée</p>
                                        <p class="text-sm">Essayez de modifier vos critères de recherche</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($entreprises as $entreprise): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="delete_selected_ids[]" value="<?php echo $entreprise['id_entr']; ?>" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $entreprise['id_entr']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-3">
                                                <i class="fas fa-building mr-1"></i>
                                            </span>
                                            <?php echo htmlspecialchars($entreprise['lib_entr']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewEntreprise(<?php echo $entreprise['id_entr']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 btn-icon" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="openEditModal(<?php echo $entreprise['id_entr']; ?>, '<?php echo htmlspecialchars($entreprise['lib_entr']); ?>')" 
                                                    class="text-indigo-600 hover:text-indigo-900 btn-icon" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteEntreprise(<?php echo $entreprise['id_entr']; ?>, '<?php echo htmlspecialchars($entreprise['lib_entr']); ?>')" 
                                                    class="text-red-600 hover:text-red-900 btn-icon" title="Supprimer">
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
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Précédent
                            </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Suivant
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Affichage de <span class="font-medium"><?php echo ($offset + 1); ?></span> à <span class="font-medium"><?php echo min($offset + $per_page, $total_entreprises); ?></span> sur <span class="font-medium"><?php echo $total_entreprises; ?></span> résultats
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i == $page ? 'z-10 bg-primary border-primary text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Ajout/Modification -->
    <div id="entrepriseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900" id="modalTitle">Ajouter une entreprise</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="id_entr" id="edit_id">
                <div class="mb-4">
                    <label for="lib_entr" class="block text-sm font-medium text-gray-700 mb-2">Nom de l'entreprise</label>
                    <input type="text" 
                           name="lib_entr" 
                           id="edit_lib_entr" 
                           required 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Détails Entreprise -->
    <div id="viewEntrepriseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" <?php echo (isset($_GET['view']) && $selected_entreprise) ? 'style="display: flex;"' : ''; ?>>
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-building mr-2"></i> Détails de l'entreprise
                </h2>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <?php if ($selected_entreprise): ?>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="detail-group bg-white rounded-lg p-4 border-l-4 border-blue-500 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-id-card text-blue-600"></i>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">ID de l'entreprise</label>
                                <span class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($selected_entreprise['id_entr']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-group bg-white rounded-lg p-4 border-l-4 border-green-500 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-building text-green-600"></i>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nom de l'entreprise</label>
                                <span class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($selected_entreprise['lib_entr']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-group bg-white rounded-lg p-4 border-l-4 border-purple-500 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-globe text-purple-600"></i>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Pays</label>
                                <span class="text-lg font-semibold text-gray-900"><?php echo !empty($selected_entreprise['pays']) ? htmlspecialchars($selected_entreprise['pays']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-group bg-white rounded-lg p-4 border-l-4 border-orange-500 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-map-marker-alt text-orange-600"></i>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Ville</label>
                                <span class="text-lg font-semibold text-gray-900"><?php echo !empty($selected_entreprise['ville']) ? htmlspecialchars($selected_entreprise['ville']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-group bg-white rounded-lg p-4 border-l-4 border-red-500 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-map text-red-600"></i>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Adresse géographique</label>
                                <span class="text-lg font-semibold text-gray-900"><?php echo !empty($selected_entreprise['adresse']) ? htmlspecialchars($selected_entreprise['adresse']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-group bg-white rounded-lg p-4 border-l-4 border-indigo-500 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-envelope text-indigo-600"></i>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Adresse mail</label>
                                <span class="text-lg font-semibold text-gray-900"><?php echo !empty($selected_entreprise['email']) ? htmlspecialchars($selected_entreprise['email']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-group bg-white rounded-lg p-4 border-l-4 border-teal-500 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-phone text-teal-600"></i>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                                <span class="text-lg font-semibold text-gray-900"><?php echo !empty($selected_entreprise['telephone']) ? htmlspecialchars($selected_entreprise['telephone']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="detail-group bg-white rounded-lg p-4 border-l-4 border-yellow-500 shadow-sm">
                        <div class="flex items-center mb-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-calendar-alt text-yellow-600"></i>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date d'ajout</label>
                                <span class="text-lg font-semibold text-gray-900"><?php echo !empty($selected_entreprise['date_creation']) ? htmlspecialchars($selected_entreprise['date_creation']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="p-6">
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-300 mb-4"></i>
                    <p class="text-lg font-medium text-gray-900">Entreprise non trouvée</p>
                    <p class="text-sm text-gray-500">L'entreprise demandée n'existe pas ou a été supprimée</p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="flex justify-end p-6 border-t border-gray-200">
                <button onclick="closeViewModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>Fermer
                </button>
            </div>
        </div>
    </div>

    <script>
        // Gestion des modales
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter une entreprise';
            document.getElementById('edit_id').value = '';
            document.getElementById('edit_lib_entr').value = '';
            document.getElementById('entrepriseModal').classList.remove('hidden');
            document.getElementById('entrepriseModal').classList.add('flex');
        }

        function openEditModal(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier une entreprise';
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_lib_entr').value = libelle;
            document.getElementById('entrepriseModal').classList.remove('hidden');
            document.getElementById('entrepriseModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('entrepriseModal').classList.add('hidden');
            document.getElementById('entrepriseModal').classList.remove('flex');
        }

        function viewEntreprise(id) {
            window.location.href = '?view=' + id;
        }

        function closeViewModal() {
            document.getElementById('viewEntrepriseModal').classList.add('hidden');
            document.getElementById('viewEntrepriseModal').classList.remove('flex');
            window.location.href = window.location.pathname;
        }

        // Gestion de la sélection multiple
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="delete_selected_ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Suppression d'une entreprise
        function deleteEntreprise(id, libelle) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer l'entreprise : '${libelle}' ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_entreprise_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Suppression multiple
        function deleteSelected() {
            const selected = document.querySelectorAll('input[name="delete_selected_ids[]"]:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins une entreprise à supprimer.');
                return;
            }
            
            if (confirm(`Êtes-vous sûr de vouloir supprimer ${selected.length} entreprise(s) ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_selected_ids" value="${Array.from(selected).map(cb => cb.value).join(',')}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Fermer les modales en cliquant à l'extérieur
        document.getElementById('entrepriseModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('viewEntrepriseModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeViewModal();
            }
        });
    </script>
</body>
</html>