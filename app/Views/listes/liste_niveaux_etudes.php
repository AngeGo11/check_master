<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'niveaux_etudes') {
    return;
}
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
    $where = 'WHERE lib_niv_etd LIKE ?';
    $params[] = "%$search%";
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) FROM niveau_etude $where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_niveaux = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_niveaux / $per_page));

// Récupérer les niveaux filtrés et paginés
$sql = "SELECT * FROM niveau_etude $where ORDER BY id_niv_etd LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout ou modification
    if (isset($_POST['lib_niv_etd'])) {
        $lib_niv_etd = trim($_POST['lib_niv_etd']);
        if (!empty($lib_niv_etd)) {
            if (!empty($_POST['id_niv_etd'])) {
                // Modification
                $id = intval($_POST['id_niv_etd']);
                try {
                    $sql = "UPDATE niveau_etude SET lib_niv_etd = ? WHERE id_niv_etd = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_niv_etd, $id]);
                    $_SESSION['success'] = "Niveau d'études modifié avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la modification du niveau d'études.";
                }
            } else {
                // Ajout
                try {
                    $sql = "INSERT INTO niveau_etude (lib_niv_etd) VALUES (?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_niv_etd]);
                    $_SESSION['success'] = "Niveau d'études ajouté avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de l'ajout du niveau d'études.";
                }
            }
        } else {
            $_SESSION['error'] = "Le libellé ne peut pas être vide.";
        }
    }
    // Suppression
    if (isset($_POST['delete_niveau_id'])) {
        $id = intval($_POST['delete_niveau_id']);
        try {
            $sql = "DELETE FROM niveau_etude WHERE id_niv_etd = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $_SESSION['success'] = "Niveau d'études supprimé avec succès.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression du niveau d'études.";
        }
    }
    // Suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM niveau_etude WHERE id_niv_etd IN ($placeholders)");
                $stmt->execute($ids);
                $_SESSION['success'] = count($ids) . " niveau(x) d'études supprimé(s) avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression multiple.";
            }
        } else {
            $_SESSION['error'] = "Aucun niveau d'études sélectionné.";
        }
        header('Location: ?page=parametres_generaux&liste=niveaux_etudes');
        exit;
    }
    // Redirection pour éviter le repost
    header('Location: ?page=parametres_generaux&liste=niveaux_etudes');
    exit;
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Niveaux d'Études - GSCV+</title>
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
                                <i class="fas fa-graduation-cap text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Niveaux d'Études</h1>
                                <p class="text-gray-600">Gestion des niveaux d'études du système</p>
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
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des niveaux d'études</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($total_niveaux); ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-graduation-cap text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des niveaux -->
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
                                <input type="hidden" name="liste" value="niveaux_etudes">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text"
                                        name="search"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Rechercher un niveau d'études..."
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
                            Ajouter un niveau
                        </button>
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

                <!-- Bouton de suppression multiple -->
                <div class="px-6 pt-4">
                    <form id="bulkDeleteForm" method="POST">
                        <input type="hidden" name="page" value="parametres_generaux">
                        <input type="hidden" name="liste" value="niveaux_etudes">
                        <input type="hidden" name="bulk_delete" value="1">
                        <input type="hidden" name="delete_selected_ids[]" id="delete_selected_ids">
                        <button type="button" 
                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center"
                            id="bulkDeleteBtn">
                            <i class="fas fa-trash mr-2"></i>
                            Supprimer la sélection
                        </button>
                    </form>
                </div>

                <!-- Tableau des données -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Libellé
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($niveaux)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-gray-500 text-lg">
                                                <?php echo empty($search) ? 'Aucun niveau d\'études trouvé.' : 'Aucun résultat pour "' . htmlspecialchars($search) . '".'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($niveaux as $niveau): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" 
                                                class="row-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                value="<?php echo htmlspecialchars($niveau['id_niv_etd']); ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($niveau['id_niv_etd']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($niveau['lib_niv_etd']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button class="btn-icon text-blue-600 hover:text-blue-900" 
                                                    title="Modifier"
                                                    onclick="editNiveau(<?php echo $niveau['id_niv_etd']; ?>, '<?php echo htmlspecialchars($niveau['lib_niv_etd'], ENT_QUOTES); ?>')">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="btn-icon text-red-600 hover:text-red-900" 
                                                    title="Supprimer"
                                                    onclick="deleteNiveau(<?php echo $niveau['id_niv_etd']; ?>, '<?php echo htmlspecialchars($niveau['lib_niv_etd'], ENT_QUOTES); ?>')">
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
                                Page <?php echo $page; ?> sur <?php echo $total_pages; ?>
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=parametres_generaux&liste=niveaux_etudes&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Précédent
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=parametres_generaux&liste=niveaux_etudes&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" 
                                        class="px-3 py-2 text-sm font-medium <?php echo $page == $i ? 'text-white bg-primary' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=parametres_generaux&liste=niveaux_etudes&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Suivant
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal d'ajout/modification -->
    <div id="niveauModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white modal-transition">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Ajouter un niveau d'études</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="niveauForm" method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="niveaux_etudes">
                    <input type="hidden" id="id_niv_etd" name="id_niv_etd">
                    
                    <div class="mb-4">
                        <label for="lib_niv_etd" class="block text-sm font-medium text-gray-700 mb-2">Libellé :</label>
                        <input type="text" 
                            id="lib_niv_etd" 
                            name="lib_niv_etd" 
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    
                    <div class="flex justify-end space-x-3">
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
    <div id="confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                <div class="mt-2 px-7 pt-6">
                    <p class="text-sm text-gray-500" id="confirmation-text">
                        Êtes-vous sûr de vouloir supprimer ce niveau d'études ?
                    </p>
                </div>
                <div class="flex justify-center space-x-3 mt-6">
                    <button id="cancel-delete"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200">
                        Annuler
                    </button>
                    <button id="confirm-delete"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">
                        Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let idToDelete = null;

        // Fonctions pour les modales
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter un niveau d\'études';
            document.getElementById('id_niv_etd').value = '';
            document.getElementById('lib_niv_etd').value = '';
            document.getElementById('niveauModal').classList.remove('hidden');
        }

        function editNiveau(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier le niveau d\'études';
            document.getElementById('id_niv_etd').value = id;
            document.getElementById('lib_niv_etd').value = libelle;
            document.getElementById('niveauModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('niveauModal').classList.add('hidden');
        }

        function deleteNiveau(id, libelle) {
            idToDelete = id;
            document.getElementById('confirmation-text').textContent = `Êtes-vous sûr de vouloir supprimer le niveau "${libelle}" ?`;
            document.getElementById('confirmation-modal').classList.remove('hidden');
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
            listeInput.value = 'niveaux_etudes';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'delete_niveau_id';
            idInput.value = idToDelete;
            
            form.appendChild(pageInput);
            form.appendChild(listeInput);
            form.appendChild(idInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        // Gestion de la sélection multiple
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });

        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkDeleteButton();
                updateSelectAll();
            });
        });

        function updateBulkDeleteButton() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            if (checkedBoxes.length > 0) {
                bulkDeleteBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                bulkDeleteBtn.classList.add('cursor-pointer');
            } else {
                bulkDeleteBtn.classList.add('opacity-50', 'cursor-not-allowed');
                bulkDeleteBtn.classList.remove('cursor-pointer');
            }
        }

        function updateSelectAll() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const selectAll = document.getElementById('selectAll');
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (checkedBoxes.length === checkboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }

        // Suppression multiple
        document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            if (checked.length === 0) {
                alert('Veuillez sélectionner au moins un niveau à supprimer.');
                return;
            }
            
            document.getElementById('confirmation-text').textContent = `Êtes-vous sûr de vouloir supprimer ${checked.length} niveau(x) sélectionné(s) ?`;
            document.getElementById('confirmation-modal').classList.remove('hidden');
            
            document.getElementById('confirm-delete').onclick = function() {
                const checked = Array.from(document.querySelectorAll('.row-checkbox:checked'));
                const ids = checked.map(cb => cb.value);
                document.getElementById('delete_selected_ids').value = ids.join(',');
                document.getElementById('bulkDeleteForm').submit();
            };
        });

        document.getElementById('cancel-delete').onclick = function() {
            document.getElementById('confirmation-modal').classList.add('hidden');
        };

        // Fermer les modales si on clique en dehors
        window.onclick = function(event) {
            const niveauModal = document.getElementById('niveauModal');
            const confirmationModal = document.getElementById('confirmation-modal');
            
            if (event.target === niveauModal) {
                closeModal();
            }
            if (event.target === confirmationModal) {
                document.getElementById('confirmation-modal').classList.add('hidden');
            }
        };

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            updateBulkDeleteButton();
        });
    </script>
</body>
</html>