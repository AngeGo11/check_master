<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'niveaux_acces') {
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
    $where = 'WHERE lib_niveau_acces LIKE ?';
    $params[] = "%$search%";
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) FROM niveau_acces_donnees $where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_niveaux = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_niveaux / $per_page));

// Récupérer les niveaux filtrés et paginés
$sql = "SELECT * FROM niveau_acces_donnees $where ORDER BY id_niveau_acces LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout ou modification
    if (isset($_POST['lib_niveau_acces'])) {
        $lib_niveau_acces = $_POST['lib_niveau_acces'];
        if (!empty($_POST['id_niveau_acces'])) {
            // Modification
            $id = intval($_POST['id_niveau_acces']);
            $sql = "UPDATE niveau_acces_donnees SET lib_niveau_acces = ? WHERE id_niveau_acces = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_niveau_acces, $id]);
            $_SESSION['success'] = "Niveau d'accès modifié avec succès.";
        } else {
            // Ajout
            $sql = "INSERT INTO niveau_acces_donnees (lib_niveau_acces) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_niveau_acces]);
            $_SESSION['success'] = "Niveau d'accès ajouté avec succès.";
        }
    }
    // Suppression
    if (isset($_POST['delete_niveau_id'])) {
        $id = intval($_POST['delete_niveau_id']);
        $sql = "DELETE FROM niveau_acces_donnees WHERE id_niveau_acces = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['success'] = "Niveau d'accès supprimé avec succès.";
    }
    // Suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM niveau_acces_donnees WHERE id_niveau_acces IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success'] = count($ids) . " niveau(x) d'accès supprimé(s) avec succès.";
        } else {
            $_SESSION['error'] = "Aucun niveau d'accès sélectionné.";
        }
        header('Location: ?page=parametres_generaux&liste=niveaux_acces');
        exit;
    }
    // Redirection pour éviter le repost
    header('Location: ?page=parametres_generaux&liste=niveaux_acces');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Niveaux d'Accès - GSCV+</title>
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
                                <i class="fas fa-shield-alt text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Niveaux d'Accès</h1>
                                <p class="text-gray-600">Gestion des niveaux d'accès du système</p>
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
                            <i class="fas fa-shield-alt text-2xl text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Niveaux</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_niveaux; ?></p>
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
                            <input type="hidden" name="page" value="parametres_generaux">
                            <input type="hidden" name="liste" value="niveaux_acces">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Rechercher un niveau d'accès..."
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Libellé</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($niveaux as $niveau): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" name="delete_selected_ids[]" value="<?php echo $niveau['id_niveau_acces']; ?>" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo $niveau['id_niveau_acces']; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($niveau['lib_niveau_acces']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="openEditModal(<?php echo $niveau['id_niveau_acces']; ?>, '<?php echo htmlspecialchars($niveau['lib_niveau_acces']); ?>')" 
                                                class="text-indigo-600 hover:text-indigo-900 btn-icon">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteNiveau(<?php echo $niveau['id_niveau_acces']; ?>)" 
                                                class="text-red-600 hover:text-red-900 btn-icon">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                            <a href="?page=parametres_generaux&liste=niveaux_acces&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Précédent
                            </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=parametres_generaux&liste=niveaux_acces&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Suivant
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Affichage de <span class="font-medium"><?php echo ($offset + 1); ?></span> à <span class="font-medium"><?php echo min($offset + $per_page, $total_niveaux); ?></span> sur <span class="font-medium"><?php echo $total_niveaux; ?></span> résultats
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=parametres_generaux&liste=niveaux_acces&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" 
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
    <div id="niveauModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900" id="modalTitle">Ajouter un niveau d'accès</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="id_niveau_acces" id="edit_id">
                <div class="mb-4">
                    <label for="lib_niveau_acces" class="block text-sm font-medium text-gray-700 mb-2">Libellé du niveau d'accès</label>
                    <input type="text" 
                           name="lib_niveau_acces" 
                           id="edit_lib_niveau_acces" 
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

    <script>
        // Gestion des modales
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter un niveau d\'accès';
            document.getElementById('edit_id').value = '';
            document.getElementById('edit_lib_niveau_acces').value = '';
            document.getElementById('niveauModal').classList.remove('hidden');
            document.getElementById('niveauModal').classList.add('flex');
        }

        function openEditModal(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier un niveau d\'accès';
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_lib_niveau_acces').value = libelle;
            document.getElementById('niveauModal').classList.remove('hidden');
            document.getElementById('niveauModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('niveauModal').classList.add('hidden');
            document.getElementById('niveauModal').classList.remove('flex');
        }

        // Gestion de la sélection multiple
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="delete_selected_ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Suppression d'un niveau
        function deleteNiveau(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce niveau d\'accès ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_niveau_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Suppression multiple
        function deleteSelected() {
            const selected = document.querySelectorAll('input[name="delete_selected_ids[]"]:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins un niveau d\'accès à supprimer.');
                return;
            }
            
            if (confirm(`Êtes-vous sûr de vouloir supprimer ${selected.length} niveau(x) d'accès ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_selected_ids" value="${Array.from(selected).map(cb => cb.value).join(',')}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Fermer la modale en cliquant à l'extérieur
        document.getElementById('niveauModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>