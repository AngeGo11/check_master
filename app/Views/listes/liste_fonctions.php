<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'fonctions') {
    return;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'] ?? 'Utilisateur';
$lib_user_type = $_SESSION['lib_user_type'] ?? 'Utilisateur';

// --- Recherche et pagination ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE nom_fonction LIKE ?';
    $params = ["%$search%"];
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) FROM fonction $where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_fonctions = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_fonctions / $per_page));

// Récupérer les fonctions filtrées et paginées
$sql = "SELECT * FROM fonction $where ORDER BY id_fonction DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$fonctions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$sql_stats = "SELECT COUNT(*) as total_fonctions FROM fonction";
$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Fonctions - GSCV+</title>
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
                                <i class="fas fa-user-tie text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Fonctions</h1>
                                <p class="text-gray-600">Gestion des fonctions et postes</p>
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
            <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8 animate-slide-up">
                <!-- Total fonctions -->
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des fonctions</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($stats['total_fonctions']); ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-user-tie text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des fonctions -->
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
                                <input type="hidden" name="liste" value="fonctions">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" 
                                           name="search" 
                                           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           placeholder="Rechercher une fonction..." 
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
                            Ajouter une fonction
                        </button>
                    </div>
                </div>

                <!-- Table des fonctions -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Libellé de la fonction
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($fonctions) === 0): ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-user-tie text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-gray-500 text-lg">Aucune fonction trouvée</p>
                                            <p class="text-gray-400 text-sm">Essayez de modifier vos critères de recherche</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($fonctions as $fonction): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                                                #<?php echo $fonction['id_fonction']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($fonction['nom_fonction']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="editFonction(<?= $fonction['id_fonction']; ?>)" 
                                                        class="text-primary hover:text-primary-light transition-colors duration-200" 
                                                        title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="showDeleteModal(<?= $fonction['id_fonction']; ?>, '<?= htmlspecialchars(addslashes($fonction['nom_fonction'])); ?>')" 
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
                                Affichage de <span class="font-medium"><?php echo ($offset + 1); ?></span> à <span class="font-medium"><?php echo min($offset + $per_page, $total_fonctions); ?></span> sur <span class="font-medium"><?php echo $total_fonctions; ?></span> résultats
                            </div>
                            <div class="flex space-x-1">
                                <?php if ($page > 1): ?>
                                    <a href="?liste=fonctions&search=<?php echo urlencode($search); ?>&page=1" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?liste=fonctions&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <a href="?liste=fonctions&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" 
                                       class="px-3 py-2 text-sm font-medium <?php if ($i == $page): ?>text-white bg-primary border-primary<?php else: ?>text-gray-500 bg-white border-gray-300 hover:bg-gray-50<?php endif; ?> border rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?liste=fonctions&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?liste=fonctions&search=<?php echo urlencode($search); ?>&page=<?php echo $total_pages; ?>" 
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

    <!-- Modal pour ajouter/modifier une fonction -->
    <div id="fonctionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Ajouter une fonction</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="fonctionForm" method="POST">
                    <input type="hidden" id="id_fonction" name="id_fonction">
                    <div class="space-y-4">
                        <div>
                            <label for="nom_fonction" class="block text-sm font-medium text-gray-700 mb-1">Libellé de la fonction</label>
                            <input type="text" 
                                   id="nom_fonction" 
                                   name="nom_fonction" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
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
                            Enregistrer
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
                <p id="deleteSingleMessage" class="text-sm text-gray-500 mb-6">Êtes-vous sûr de vouloir supprimer cette fonction ?</p>
                <div class="flex justify-center space-x-3">
                    <button onclick="closeDeleteModal()" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200">
                        Annuler
                    </button>
                    <form id="deleteForm" method="POST" class="inline">
                        <input type="hidden" id="delete_fonction_id" name="delete_fonction_id">
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
            document.getElementById('modalTitle').textContent = 'Ajouter une fonction';
            document.getElementById('fonctionForm').reset();
            document.getElementById('id_fonction').value = '';
            document.getElementById('fonctionModal').classList.remove('hidden');
        }

        function editFonction(id) {
            // Implémenter la modification de fonction
            console.log('Modifier fonction:', id);
        }

        function showDeleteModal(id, libelle) {
            document.getElementById('delete_fonction_id').value = id;
            document.getElementById('deleteSingleMessage').innerHTML = "Êtes-vous sûr de vouloir supprimer la fonction : '<b>" + libelle + "</b>' ?<br><span class='text-red-600 text-xs'>Cette action est irréversible.</span>";
            document.getElementById('confirmation-modal-single').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('fonctionModal').classList.add('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').classList.add('hidden');
        }

        // Fermer la modale si on clique en dehors
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('fonctionModal')) {
                closeModal();
            }
            if (event.target == document.getElementById('confirmation-modal-single')) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>