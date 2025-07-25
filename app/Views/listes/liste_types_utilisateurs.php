<?php
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
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' && isset($_POST['lib_tu'])) {
            $lib_tu = trim($_POST['lib_tu']);
            if (!empty($lib_tu)) {
                try {
                    $sql = "INSERT INTO type_utilisateur (lib_tu) VALUES (?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_tu]);
                    $_SESSION['success'] = "Type d'utilisateur ajouté avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de l'ajout du type d'utilisateur.";
                }
            } else {
                $_SESSION['error'] = "Le libellé ne peut pas être vide.";
            }
        } elseif ($action === 'edit' && isset($_POST['id_tu']) && isset($_POST['lib_tu'])) {
            $id_tu = (int)$_POST['id_tu'];
            $lib_tu = trim($_POST['lib_tu']);
            if (!empty($lib_tu)) {
                try {
                    $sql = "UPDATE type_utilisateur SET lib_tu = ? WHERE id_tu = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_tu, $id_tu]);
                    $_SESSION['success'] = "Type d'utilisateur modifié avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la modification du type d'utilisateur.";
                }
            } else {
                $_SESSION['error'] = "Le libellé ne peut pas être vide.";
            }
        } elseif ($action === 'delete' && isset($_POST['id_tu'])) {
            $id_tu = (int)$_POST['id_tu'];
            try {
                $sql = "DELETE FROM type_utilisateur WHERE id_tu = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_tu]);
                $_SESSION['success'] = "Type d'utilisateur supprimé avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression du type d'utilisateur.";
            }
        } elseif (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
            $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM type_utilisateur WHERE id_tu IN ($placeholders)");
                $stmt->execute($ids);
                $_SESSION['success'] = count($ids) . " type(s) d'utilisateur supprimé(s) avec succès.";
            } else {
                $_SESSION['error'] = "Aucun type d'utilisateur sélectionné.";
            }
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }

        // Redirection avec conservation des paramètres
        $redirect_url = $_SERVER['PHP_SELF'];
        if (!empty($search)) $redirect_url .= "?search=" . urlencode($search);
        if ($page > 1) $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . "page=$page";

        header('Location: ' . $redirect_url);
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
    <title>Liste des Types d'Utilisateurs - Tableau de Bord Commission</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/GSCV+/app/Views/listes/assets/css/listes.css?v=<?php echo time(); ?>">
    <style>
        /* Styles pour les modales */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in-out;
        }

        .modal-content {
            position: relative;
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            width: 25%;
            max-width: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s ease-in-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.5em;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #4a90e2;
            outline: none;
        }

        .modal-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: right;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Styles pour les boutons dans la modale */
        .modal-footer .button {
            margin-left: 10px;
        }

        .modal-footer .button.secondary {
            background-color: #f5f5f5;
            color: #333;
        }

        .modal-footer .button.secondary:hover {
            background-color: #e5e5e5;
        }

        /* Messages d'alerte */
        .alert {
            padding: 12px 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="header-title">
            <div class="img-container">
                <img src="/GSCV+/public/assets/images/logo_mi_sbg.png" alt="">
            </div>
            <div class="text-container">
                <h1>Liste des Types d'Utilisateurs</h1>
                <p>Gestion des types d'utilisateurs du système</p>
            </div>
        </div>

        <div class="header-actions">

            <div class="user-avatar"><?php echo substr($fullname, 0, 1); ?></div>
            <div>
                <div class="user-name"><?php echo $fullname; ?></div>
                <div class="user-role"><?php echo $lib_user_type; ?></div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="actions-bar">
        <a href="?page=parametres_generaux" class="button back-to-params">
                <i class="fas fa-arrow-left"></i> Retour aux paramètres généraux
            </a>
            <form method="GET" class="search-box" style="display: flex; align-items: center;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Rechercher un type d'utilisateur..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" style="background: none; border: none; color: #666; cursor: pointer; padding: 0 10px;">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <form id="deleteMultipleForm" method="POST" style="display:inline; margin-right:10px;">
                <input type="hidden" name="delete_multiple" value="1">
                <button type="button" class="button delete-multiple-btn" onclick="openDeleteMultipleModal()">
                    <i class="fas fa-trash"></i> Supprimer la sélection
                </button>
            </form>
            <button class="button" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Ajouter un type d'utilisateur
            </button>
        </div>

        <!-- Messages de notification -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="data-table-container">
            <div class="data-table-header">
                <div class="data-table-title">Liste des types d'utilisateurs (<?php echo $total_types; ?> résultat<?php echo $total_types > 1 ? 's' : ''; ?>)</div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                        <th style="width: 50px;">ID</th>
                        <th>Libellé</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($types)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #666;">
                                <?php echo empty($search) ? 'Aucun type d\'utilisateur trouvé.' : 'Aucun résultat pour "' . htmlspecialchars($search) . '".'; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($types as $type): ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" name="delete_selected_ids[]" value="<?php echo $type['id_tu']; ?>"></td>
                                <td><?php echo htmlspecialchars($type['id_tu']); ?></td>
                                <td><?php echo htmlspecialchars($type['lib_tu']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" title="Modifier" onclick="editType(<?php echo $type['id_tu']; ?>, '<?php echo htmlspecialchars($type['lib_tu'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="action-button delete-button" title="Supprimer" onclick="deleteType(<?php echo $type['id_tu']; ?>, '<?php echo htmlspecialchars($type['lib_tu'], ENT_QUOTES); ?>')">
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
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-item">«</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1): ?>
                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-item">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="page-item">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-item <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="page-item">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-item"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="page-item">»</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal pour ajouter/modifier un type d'utilisateur -->
    <div id="typeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Ajouter un type d'utilisateur</h2>
            <form id="typeForm" method="POST">
                <input type="hidden" id="action" name="action" value="add">
                <input type="hidden" id="id_tu" name="id_tu">
                <div class="form-group">
                    <label for="lib_tu">Libellé :</label>
                    <input type="text" id="lib_tu" name="lib_tu" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="button">Enregistrer</button>
                    <button type="button" class="button secondary" onclick="closeModal()">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation de suppression (simple) harmonisée -->
    <div id="confirmation-modal-single" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h2>Confirmer la suppression</h2>
            <p id="deleteSingleMessage">Êtes-vous sûr de vouloir supprimer ce type d'utilisateur ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span></p>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeDeleteModal()">Annuler</button>
                <button type="button" class="button" onclick="confirmDeleteSingle()">Supprimer</button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression multiple harmonisée -->
    <div id="confirmation-modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteMultipleModal()">&times;</span>
            <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h2>Confirmation de suppression</h2>
            <p id="deleteMultipleMessage"></p>
            <div class="modal-footer" id="deleteMultipleFooter"></div>
        </div>
    </div>

    <script>
        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter un type d\'utilisateur';
            document.getElementById('action').value = 'add';
            document.getElementById('typeForm').reset();
            document.getElementById('id_tu').value = '';
            document.getElementById('typeModal').style.display = 'block';
        }

        function editType(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier un type d\'utilisateur';
            document.getElementById('action').value = 'edit';
            document.getElementById('id_tu').value = id;
            document.getElementById('lib_tu').value = libelle;
            document.getElementById('typeModal').style.display = 'block';
        }

        function deleteType(id, libelle) {
            document.getElementById('deleteSingleMessage').innerHTML = `Êtes-vous sûr de vouloir supprimer le type d'utilisateur <b>"${libelle}"</b> ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
            document.getElementById('confirmation-modal-single').style.display = 'flex';
            // Stocker l'id à supprimer
            window.idToDelete = id;
        }

        function closeModal() {
            document.getElementById('typeModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').style.display = 'none';
        }

        function confirmDeleteSingle() {
            // Remplir le champ caché et soumettre le formulaire de suppression
            document.getElementById('deleteIdTu').value = window.idToDelete;
            document.querySelector('form[action][method="POST"]').submit();
        }

        // Fermer les modales si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('typeModal')) {
                closeModal();
            }
            if (event.target == document.getElementById('confirmation-modal-single')) {
                closeDeleteModal();
            }
        }

        // Empêcher la fermeture des modales lors du clic sur leur contenu
        document.querySelectorAll('.modal-content').forEach(function(content) {
            content.onclick = function(event) {
                event.stopPropagation();
            }
        });

        // Fermer les modales avec la touche Échap
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
                closeDeleteModal();
            }
        });

        // Sélection groupée
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        selectAll && selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
        checkboxes.forEach(cb => cb.addEventListener('change', function() {
            if (!this.checked) selectAll.checked = false;
            else if ([...checkboxes].every(c => c.checked)) selectAll.checked = true;
        }));

        // Suppression multiple
        function openDeleteMultipleModal() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const msg = document.getElementById('deleteMultipleMessage');
            const footer = document.getElementById('deleteMultipleFooter');
            if (checked.length === 0) {
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins un type d'utilisateur à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> type(s) d'utilisateur sélectionné(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
                footer.innerHTML = '<button type="button" class="button" onclick="confirmDeleteMultiple()">Oui, supprimer</button>' +
                    '<button type="button" class="button secondary" onclick="closeDeleteMultipleModal()">Non</button>';
            }
            document.getElementById('confirmation-modal').style.display = 'flex';
        }

        function closeDeleteMultipleModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
        }

        function confirmDeleteMultiple() {
            document.getElementById('multipleDeleteForm').submit();
        }
    </script>
</body>

</html>