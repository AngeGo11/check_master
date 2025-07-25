<?php
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
    $where = 'WHERE lib_spe LIKE ?';
    $params[] = "%$search%";
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) FROM specialite $where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_specialites = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_specialites / $per_page));

// Récupérer les spécialités filtrées et paginées
$sql = "SELECT * FROM specialite $where ORDER BY id_spe LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$specialites = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout ou modification
    if (isset($_POST['lib_spe'])) {
        $lib_spe = $_POST['lib_spe'];
        if (!empty($_POST['id_spe'])) {
            // Modification
            $id = intval($_POST['id_spe']);
            $sql = "UPDATE specialite SET lib_spe = ? WHERE id_spe = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_spe, $id]);
            $_SESSION['success'] = "Spécialité modifiée avec succès.";
        } else {
            // Ajout
            $sql = "INSERT INTO specialite (lib_spe) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_spe]);
            $_SESSION['success'] = "Spécialité ajoutée avec succès.";
        }
    }
    // Suppression
    if (isset($_POST['delete_spe_id'])) {
        $id = intval($_POST['delete_spe_id']);
        $sql = "DELETE FROM specialite WHERE id_spe = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['success'] = "Spécialité supprimée avec succès.";
    }
    // Suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM specialite WHERE id_spe IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success'] = count($ids) . " spécialité(s) supprimée(s) avec succès.";
        } else {
            $_SESSION['error'] = "Aucune spécialité sélectionnée.";
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    // Redirection pour éviter le repost
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Spécialités - Tableau de Bord Commission</title>
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
    <div class="header">
        <div class="header-title">
            <div class="img-container">
                <img src="/GSCV+/public/assets/images/logo_mi_sbg.png" alt="">
            </div>
            <div class="text-container">
                <h1>Liste des Spécialités</h1>
                <p>Gestion des spécialités du système</p>
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

    </div>

    <div class="content">
        <div class="actions-bar">
        <a href="?page=parametres_generaux" class="button back-to-params">
                <i class="fas fa-arrow-left"></i> Retour aux paramètres généraux
            </a>
            <form method="GET" class="search-box" style="display:inline-flex;align-items:center;gap:5px;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Rechercher une spécialité..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="button" style="margin-left:5px;">Rechercher</button>
            </form>
            <button class="button" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Ajouter une spécialité
            </button>
        </div>

        <!-- Messages de succès/erreur -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success'];
                unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error'];
                unset($_SESSION['error']); ?>
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
                <div class="data-table-title">Liste des spécialités (<?php echo $total_specialites; ?> éléments)</div>
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
                    <?php if (count($specialites) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">Aucune spécialité trouvée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($specialites as $specialite): ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($specialite['id_spe']); ?>"></td>
                                <td><?php echo htmlspecialchars($specialite['id_spe']); ?></td>
                                <td><?php echo htmlspecialchars($specialite['lib_spe']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" title="Modifier" onclick="editSpecialite(<?= $specialite['id_spe']; ?>, '<?= htmlspecialchars(addslashes($specialite['lib_spe'])); ?>')">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="action-button delete-button" title="Supprimer" onclick="showDeleteModal(<?= $specialite['id_spe']; ?>, '<?= htmlspecialchars(addslashes($specialite['lib_spe'])); ?>')">
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
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&page=1">«</a>
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">‹</a>
            <?php endif; ?>
            <?php
            // Affichage de 5 pages max autour de la page courante
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a class="page-item<?php if ($i == $page) echo ' active'; ?>" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">›</a>
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $total_pages; ?>">»</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal pour ajouter/modifier une spécialité -->
    <div id="specialiteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Ajouter une spécialité</h2>
                <span class="close">&times;</span>
            </div>
            <form id="specialiteForm" method="POST">
                <input type="hidden" id="specialiteId" name="id_spe">
                <div class="form-group">
                    <label for="lib_spe">Libellé :</label>
                    <input type="text" id="lib_spe" name="lib_spe" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="button">Enregistrer</button>
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
            <p id="deleteSingleMessage">Êtes-vous sûr de vouloir supprimer cette spécialité ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span></p>
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
                    <p id="confirmation-text">Voulez-vous vraiment supprimer les spécialités sélectionnées ?</p>
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
            document.getElementById('modalTitle').textContent = 'Ajouter une spécialité';
            document.getElementById('specialiteForm').reset();
            document.getElementById('specialiteId').value = '';
            document.getElementById('specialiteModal').style.display = 'block';
        }

        function editSpecialite(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier une spécialité';
            document.getElementById('specialiteId').value = id;
            document.getElementById('lib_spe').value = libelle;
            document.getElementById('specialiteModal').style.display = 'block';
        }

        function showDeleteModal(id, libelle) {
            document.getElementById('deleteSingleMessage').innerHTML = `Êtes-vous sûr de vouloir supprimer la spécialité <b>"${libelle}"</b> ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
            window.idToDelete = id;
            document.getElementById('confirmation-modal-single').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('specialiteModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').style.display = 'none';
        }

        function confirmDeleteSingle() {
            document.getElementById('delete_spe_id').value = window.idToDelete;
            document.getElementById('deleteForm').submit();
        }

        // Fermer la modale si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('specialiteModal') || event.target == document.getElementById('confirmation-modal-single')) {
                closeModal();
                closeDeleteModal();
            }
        }

        // Empêcher la fermeture de la modale lors du clic sur son contenu
        document.querySelectorAll('.modal-content').forEach(function(content) {
            content.onclick = function(event) {
                event.stopPropagation();
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
                document.getElementById('confirmation-text').textContent = "Veuillez sélectionner au moins une spécialité.";
                document.getElementById('confirmation-buttons').innerHTML = '<button class="button" id="ok-btn">OK</button>';
                document.getElementById('confirmation-modal').style.display = 'flex';
                document.getElementById('ok-btn').onclick = function() {
                    document.getElementById('confirmation-modal').style.display = 'none';
                };
                return;
            }
            document.getElementById('confirmation-text').textContent = "Voulez-vous vraiment supprimer les spécialités sélectionnées ?";
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