<?php
require_once __DIR__ . '/../../config/config.php';


$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// Paramètres de recherche et pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$statuts_par_page = 10;

// Construction de la requête avec recherche
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "lib_jury LIKE ?";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Comptage total pour pagination
$count_sql = "SELECT COUNT(*) FROM statut_jury $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_statuts = $count_stmt->fetchColumn();

$total_pages = ceil($total_statuts / $statuts_par_page);
$offset = ($page - 1) * $statuts_par_page;

// Récupération des statuts avec pagination
$sql = "SELECT * FROM statut_jury $where_clause ORDER BY id_jury LIMIT $statuts_par_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' && isset($_POST['lib_jury'])) {
            $lib_jury = trim($_POST['lib_jury']);
            if (!empty($lib_jury)) {
                try {
                    $sql = "INSERT INTO statut_jury (lib_jury) VALUES (?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_jury]);
                    $_SESSION['success'] = "Statut de jury ajouté avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de l'ajout du statut de jury.";
                }
            } else {
                $_SESSION['error'] = "Le libellé ne peut pas être vide.";
            }
        } elseif ($action === 'edit' && isset($_POST['id_jury']) && isset($_POST['lib_jury'])) {
            $id_jury = (int)$_POST['id_jury'];
            $lib_jury = trim($_POST['lib_jury']);
            if (!empty($lib_jury)) {
                try {
                    $sql = "UPDATE statut_jury SET lib_jury = ? WHERE id_jury = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$lib_jury, $id_jury]);
                    $_SESSION['success'] = "Statut de jury modifié avec succès.";
                } catch (PDOException $e) {
                    $_SESSION['error'] = "Erreur lors de la modification du statut de jury.";
                }
            } else {
                $_SESSION['error'] = "Le libellé ne peut pas être vide.";
            }
        } elseif ($action === 'delete' && isset($_POST['id_jury'])) {
            $id_jury = (int)$_POST['id_jury'];
            try {
                $sql = "DELETE FROM statut_jury WHERE id_jury = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_jury]);
                $_SESSION['success'] = "Statut de jury supprimé avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression du statut de jury.";
            }
        } elseif (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
            $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM statut_jury WHERE id_jury IN ($placeholders)");
                $stmt->execute($ids);
                $_SESSION['success'] = count($ids) . " statut(s) supprimé(s) avec succès.";
            } else {
                $_SESSION['error'] = "Aucun statut sélectionné.";
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

// Récupération d'un statut pour modification
$statut_to_edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id_jury = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM statut_jury WHERE id_jury = ?");
    $stmt->execute([$id_jury]);
    $statut_to_edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Statuts de Jury - Tableau de Bord Commission</title>
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
                <h1>Liste des Statuts de Jury</h1>
                <p>Gestion des statuts de jury du système</p>
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
                <input type="text" name="search" placeholder="Rechercher un statut de jury..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" style="background: none; border: none; color: #666; cursor: pointer; padding: 0 10px;">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            <button class="button" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Ajouter un statut de jury
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

        <!-- Bouton de suppression multiple -->
        <form id="bulkDeleteForm" method="POST" style="margin-bottom:10px;">
            <input type="hidden" name="bulk_delete" value="1">
            <button type="button" class="button danger" id="bulkDeleteBtn"><i class="fas fa-trash"></i> Supprimer la sélection</button>
            <input type="hidden" name="delete_selected_ids[]" id="delete_selected_ids">
        </form>

        <div class="data-table-container">
            <div class="data-table-header">
                <div class="data-table-title">Liste des statuts jury (<?php echo $total_statuts; ?> résultat<?php echo $total_statuts > 1 ? 's' : ''; ?>)</div>
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
                    <?php if (empty($statuts)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #666;">
                                <?php echo empty($search) ? 'Aucun statut de jury trouvé.' : 'Aucun résultat pour "' . htmlspecialchars($search) . '".'; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($statuts as $statut): ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($statut['id_jury']); ?>"></td>
                                <td><?php echo htmlspecialchars($statut['id_jury']); ?></td>
                                <td><?php echo htmlspecialchars($statut['lib_jury']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" title="Modifier" onclick="editStatut(<?php echo $statut['id_jury']; ?>, '<?php echo htmlspecialchars($statut['lib_jury'], ENT_QUOTES); ?>')">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="action-button delete-button" title="Supprimer" onclick="deleteStatut(<?php echo $statut['id_jury']; ?>, '<?php echo htmlspecialchars($statut['lib_jury'], ENT_QUOTES); ?>')">
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

    <!-- Modal pour ajouter/modifier un statut de jury -->
    <div id="statutModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Ajouter un statut de jury</h2>
            <form id="statutForm" method="POST">
                <input type="hidden" id="action" name="action" value="add">
                <input type="hidden" id="id_jury" name="id_jury">
                <div class="form-group">
                    <label for="lib_jury">Libellé :</label>
                    <input type="text" id="lib_jury" name="lib_jury" required>
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
            <p id="deleteSingleMessage">Êtes-vous sûr de vouloir supprimer ce statut de jury ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span></p>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeDeleteModal()">Annuler</button>
                <button type="button" class="button" onclick="confirmDeleteSingle()">Supprimer</button>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation harmonisée -->
    <div class="modal" id="confirmation-modal" style="display:none;">
        <div class="modal-content confirmation-content enhanced-modal">
            <div class="top-text">
                <h2 class="modal-title">Confirmation</h2>
                <button class="close-modal-btn" id="close-confirmation-modal-btn">×</button>
            </div>
            <div class="modal-action">
                <div class="confirmation-message">
                    <i class="fas fa-question-circle enhanced-modal-icon"></i>
                    <p id="confirmation-text">Voulez-vous vraiment supprimer les statuts sélectionnés ?</p>
                </div>
                <div class="confirmation-buttons" id="confirmation-buttons">
                    <button class="button" id="confirm-delete">Oui</button>
                    <button class="button secondary" id="cancel-delete">Non</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter un statut de jury';
            document.getElementById('action').value = 'add';
            document.getElementById('statutForm').reset();
            document.getElementById('id_jury').value = '';
            document.getElementById('statutModal').style.display = 'block';
        }

        function editStatut(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier un statut de jury';
            document.getElementById('action').value = 'edit';
            document.getElementById('id_jury').value = id;
            document.getElementById('lib_jury').value = libelle;
            document.getElementById('statutModal').style.display = 'block';
        }

        function deleteStatut(id, libelle) {
            document.getElementById('deleteSingleMessage').innerHTML = `Êtes-vous sûr de vouloir supprimer le statut de jury <b>"${libelle}"</b> ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
            window.idToDelete = id;
            document.getElementById('confirmation-modal-single').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('statutModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').style.display = 'none';
        }

        function confirmDeleteSingle() {
            document.getElementById('deleteIdJury').value = window.idToDelete;
            document.querySelector('form[action][method="POST"]').submit();
        }

        // Fermer les modales si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('statutModal')) {
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

        // Sélection/désélection tout
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
        });

        // Bouton suppression multiple
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        bulkDeleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const checked = Array.from(document.querySelectorAll('.row-checkbox:checked'));
            if (checked.length === 0) {
                document.getElementById('confirmation-text').textContent = "Veuillez sélectionner au moins un statut.";
                document.getElementById('confirmation-buttons').innerHTML = '<button class="button" id="ok-btn">OK</button>';
                document.getElementById('confirmation-modal').style.display = 'flex';
                document.getElementById('ok-btn').onclick = function() {
                    document.getElementById('confirmation-modal').style.display = 'none';
                };
                return;
            }
            document.getElementById('confirmation-text').textContent = "Voulez-vous vraiment supprimer les statuts sélectionnés ?";
            document.getElementById('confirmation-buttons').innerHTML = '<button class="button" id="confirm-delete">Oui</button><button class="button secondary" id="cancel-delete">Non</button>';
            document.getElementById('confirmation-modal').style.display = 'flex';
            document.getElementById('confirm-delete').onclick = function() {
                document.getElementById('delete_selected_ids').value = checked.map(cb => cb.value).join(',');
                document.getElementById('bulkDeleteForm').submit();
            };
            document.getElementById('cancel-delete').onclick = function() {
                document.getElementById('confirmation-modal').style.display = 'none';
            };
        });
    </script>
</body>

</html>