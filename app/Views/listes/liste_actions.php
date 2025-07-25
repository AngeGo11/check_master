<?php
require_once __DIR__ . '/../../config/config.php';

// Obtenir la connexion PDO

$fullname = $_SESSION['user_fullname'] ?? 'Utilisateur';
$lib_user_type = $_SESSION['lib_user_type'] ?? 'Inconnu';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout ou modification
    if (isset($_POST['lib_action'])) {
        $lib_action = $_POST['lib_action'];
        if (!empty($_POST['actionId'])) {
            // Modification
            $id = intval($_POST['actionId']);
            $sql = "UPDATE action SET lib_action = ? WHERE id_action = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_action, $id]);
            $_SESSION['success'] = "Action modifiée avec succès.";
        } else {
            // Ajout
            $sql = "INSERT INTO action (lib_action) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_action]);
            $_SESSION['success'] = "Action ajoutée avec succès.";
        }
    }
    // Suppression individuelle
    if (isset($_POST['delete_action_id'])) {
        $id = intval($_POST['delete_action_id']);
        $sql = "DELETE FROM action WHERE id_action = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['success'] = "Action supprimée avec succès.";
    }
    // Suppression multiple
    if (isset($_POST['delete_action_ids']) && is_array($_POST['delete_action_ids'])) {
        $ids = array_map('intval', $_POST['delete_action_ids']);
        if (count($ids) > 0) {
            $in = implode(',', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM action WHERE id_action IN ($in)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($ids);
            $_SESSION['success'] = "Actions supprimées avec succès.";
        }
    }
    // Redirection pour éviter le repost
    header('Location: ?liste=actions');
    exit;
}

// --- Recherche et pagination ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE lib_action LIKE ?';
    $params[] = "%$search%";
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) FROM action $where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_actions = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_actions / $per_page));

// Récupérer les actions filtrées et paginées
$sql = "SELECT * FROM action $where ORDER BY id_action DESC LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$actions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Actions</title>
    <link rel="stylesheet" href="/GSCV+/app/Views/listes/assets/css/listes.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>

<body>

<!-- Styles pour cette page -->
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
</style>
</head>

<body>

    <!-- En-tête -->
    <div class="header">
        <div class="header-title">
            <div class="img-container">
                <img src="/GSCV+/public/assets/images/logo_mi_sbg.png" alt="">
            </div>
            <div class="text-container">
                <h1>Liste des Actions</h1>
                <p>Gestion des actions du système</p>
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
            <!-- Bouton de retour -->

            <a href="?page=parametres_generaux" class="button back-to-params">
                <i class="fas fa-arrow-left"></i> Retour aux paramètres généraux
            </a>

            <form method="GET" class="search-box" style="display:inline-flex;align-items:center;gap:5px;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Rechercher une action..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="button" style="margin-left:5px;">Rechercher</button>
            </form>
            <button class="button" onclick="showAddModal()">
                <i class="fas fa-plus"></i>
                Ajouter une action
            </button>
        </div>

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

        <!-- Table de données -->
        <div class="data-table-container">
            <div class="data-table-header">
                <div class="data-table-title">Liste des actions</div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="select-all"></th>
                        <th>N° Action</th>
                        <th>Libellé de l'action</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($actions) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">Aucune action trouvée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($actions as $act): ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" value="<?= $act['id_action']; ?>"></td>
                                <td><?= $act['id_action']; ?></td>
                                <td><?= $act['lib_action']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" title="Modifier" onclick="editAction(<?= $act['id_action']; ?>, '<?= htmlspecialchars(addslashes($act['lib_action'])); ?>')">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="action-button delete-button" title="Supprimer" onclick="showDeleteModal(<?= $act['id_action']; ?>, '<?= htmlspecialchars(addslashes($act['lib_action'])); ?>')">
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
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="page-item" href="?liste=actions&search=<?php echo urlencode($search); ?>&page=1">«</a>
                <a class="page-item" href="?liste=actions&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">‹</a>
            <?php endif; ?>
            <?php
            // Affichage de 5 pages max autour de la page courante
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a class="page-item<?php if ($i == $page) echo ' active'; ?>" href="?liste=actions&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a class="page-item" href="?liste=actions&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">›</a>
                <a class="page-item" href="?liste=actions&search=<?php echo urlencode($search); ?>&page=<?php echo $total_pages; ?>">»</a>
            <?php endif; ?>
        </div>

        <!-- Bouton de suppression multiple -->
        <div style="margin: 18px 0;">
            <button class="button bulk-delete-btn" id="bulk-delete-btn" type="button">
                <i class="fas fa-trash"></i> Supprimer la sélection
            </button>
        </div>
    </div>


    <div id="actionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Ajouter une action</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="actionForm" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="lib_action">Libellé de l'action</label>
                            <input type="text" id="lib_action" name="lib_action" required>
                        </div>
                        <div class="form-group">
                            <label for="actionId">ID</label>
                            <input type="text" id="actionId" name="actionId" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button secondary" onclick="closeModal()">Annuler</button>
                        <button type="submit" class="button">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression (simple) harmonisée -->
    <div id="confirmation-modal-single" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h2>Confirmer la suppression</h2>
            <p id="deleteSingleMessage">Êtes-vous sûr de vouloir supprimer cette action ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span></p>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeDeleteModal()">Annuler</button>
                <form id="deleteForm" method="POST" style="display:inline;">
                    <input type="hidden" id="delete_action_id" name="delete_action_id">
                    <button type="submit" class="button delete">Supprimer</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression multiple harmonisée -->
    <div id="confirmation-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteMultipleModal()">&times;</span>
            <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h2>Confirmation de suppression</h2>
            <p id="deleteMultipleMessage"></p>
            <div class="modal-footer" id="deleteMultipleFooter"></div>
        </div>
    </div>

    <!-- Message de succès -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" style="margin: 20px auto; max-width: 600px; text-align:center;">
            <?php echo $_SESSION['success_message'];
            unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <script>
        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter une action';
            document.getElementById('actionForm').reset();
            document.getElementById('actionId').value = '';
            document.getElementById('actionModal').style.display = 'block';
        }

        function editAction(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier une action';
            document.getElementById('actionId').value = id;
            document.getElementById('lib_action').value = libelle;
            document.getElementById('actionModal').style.display = 'block';
        }

        function showDeleteModal(id, libelle) {
            document.getElementById('delete_action_id').value = id;
            document.getElementById('deleteSingleMessage').innerHTML = "Êtes-vous sûr de vouloir supprimer l'action : '<b>" + libelle + "</b>' ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>";
            document.getElementById('confirmation-modal-single').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('actionModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').style.display = 'none';
        }

        // Gestionnaire d'événements pour le bouton de fermeture
        if (document.querySelector('.close')) {
            document.querySelectorAll('.close').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    closeModal();
                    closeDeleteModal();
                });
            });
        }

        // Fermer la modale si on clique en dehors
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('actionModal')) {
                closeModal();
            }
            if (event.target == document.getElementById('confirmation-modal-single')) {
                closeDeleteModal();
            }
        });

        // Empêcher la fermeture de la modale lors du clic sur son contenu
        if (document.querySelector('.modal-content')) {
            document.querySelectorAll('.modal-content').forEach(function(modalContent) {
                modalContent.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
            });
        }

        // Sélection/désélection toutes les cases
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
        });

        // Ouvrir la modale de suppression multiple
        document.getElementById('bulk-delete-btn').addEventListener('click', function() {
            openDeleteMultipleModal();
        });

        function openDeleteMultipleModal() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const msg = document.getElementById('deleteMultipleMessage');
            const footer = document.getElementById('deleteMultipleFooter');
            if (checked.length === 0) {
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins une action à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> action(s) sélectionnée(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
                footer.innerHTML = '<button type="button" class="button" onclick="confirmDeleteMultiple()">Oui, supprimer</button>' +
                    '<button type="button" class="button secondary" onclick="closeDeleteMultipleModal()">Non</button>';
            }
            document.getElementById('confirmation-modal').style.display = 'flex';
        }

        function closeDeleteMultipleModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
        }

        function confirmDeleteMultiple() {
            // À adapter selon la structure du formulaire
            // document.getElementById('bulkDeleteForm').submit();
        }
    </script>