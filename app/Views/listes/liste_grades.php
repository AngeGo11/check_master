<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'grades') {
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
    $where = 'WHERE nom_grd LIKE ?';
    $params[] = "%$search%";
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) FROM grade $where";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_grades = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_grades / $per_page));

// Récupérer les grades filtrés et paginés
$sql = "SELECT * FROM grade $where ORDER BY id_grd LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$grades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout ou modification
    if (isset($_POST['nom_grd'])) {
        $nom_grd = $_POST['nom_grd'];
        if (!empty($_POST['id_grd'])) {
            // Modification
            $id = intval($_POST['id_grd']);
            $sql = "UPDATE grade SET nom_grd = ? WHERE id_grd = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nom_grd, $id]);
            $_SESSION['success'] = "Grade modifié avec succès.";
        } else {
            // Ajout
            $sql = "INSERT INTO grade (nom_grd) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nom_grd]);
            $_SESSION['success'] = "Grade ajouté avec succès.";
        }
    }
    // Suppression
    if (isset($_POST['delete_grade_id'])) {
        $id = intval($_POST['delete_grade_id']);
        $sql = "DELETE FROM grade WHERE id_grd = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $_SESSION['success'] = "Grade supprimé avec succès.";
    }
    // Suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM grade WHERE id_grd IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success'] = count($ids) . " grade(s) supprimé(s) avec succès.";
        } else {
            $_SESSION['error'] = "Aucun grade sélectionné.";
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
    <title>Liste des Grades - Tableau de Bord Commission</title>
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
                <h1>Liste des Grades</h1>
                <p>Gestion des grades du système</p>
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
            <a href="?page=parametres_generaux" class="button back-to-params"><i class="fas fa-arrow-left"></i> Retour aux paramètres généraux</a>

            <form method="GET" class="search-box" style="display:inline-flex;align-items:center;gap:5px;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Rechercher un grade..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="button" style="margin-left:5px;">Rechercher</button>
            </form>
            <button class="button" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Ajouter un grade
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
                <div class="data-table-title">Liste des grades (<?php echo $total_grades; ?> éléments)</div>
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
                    <?php if (count($grades) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">Aucun grade trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($grades as $grade): ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($grade['id_grd']); ?>"></td>
                                <td><?php echo htmlspecialchars($grade['id_grd']); ?></td>
                                <td><?php echo htmlspecialchars($grade['nom_grd']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button edit-button" title="Modifier" onclick="editGrade(<?= $grade['id_grd']; ?>, '<?= htmlspecialchars(addslashes($grade['nom_grd'])); ?>')">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="action-button delete-button" title="Supprimer" onclick="showDeleteModal(<?= $grade['id_grd']; ?>, '<?= htmlspecialchars(addslashes($grade['nom_grd'])); ?>')">
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

    <!-- Modal pour ajouter/modifier un grade -->
    <div id="gradeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Ajouter un grade</h2>
                <span class="close">&times;</span>
            </div>
            <form id="gradeForm" method="POST">
                <input type="hidden" id="gradeId" name="id_grd">
                <div class="form-group">
                    <label for="nom_grd">Libellé :</label>
                    <input type="text" id="nom_grd" name="nom_grd" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="button">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirmer la suppression</h2>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="deleteForm" method="POST">
                    <input type="hidden" id="delete_grade_id" name="delete_grade_id">
                    <p id="deleteMessage">Êtes-vous sûr de vouloir supprimer ce grade ?</p>
                    <div class="modal-footer">
                        <button type="button" class="button secondary" onclick="closeDeleteModal()">Annuler</button>
                        <button type="submit" class="button delete">Supprimer</button>
                    </div>
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

    <script>
        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter un grade';
            document.getElementById('gradeForm').reset();
            document.getElementById('gradeId').value = '';
            document.getElementById('gradeModal').style.display = 'block';
        }

        function editGrade(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier un grade';
            document.getElementById('gradeId').value = id;
            document.getElementById('nom_grd').value = libelle;
            document.getElementById('gradeModal').style.display = 'block';
        }

        function showDeleteModal(id, libelle) {
            document.getElementById('delete_grade_id').value = id;
            document.getElementById('deleteMessage').textContent = "Êtes-vous sûr de vouloir supprimer le grade : '" + libelle + "' ?";
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('gradeModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Fermer la modale si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('gradeModal') || event.target == document.getElementById('deleteModal')) {
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
            openDeleteMultipleModal();
        });

        // Suppression multiple
        function openDeleteMultipleModal() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const msg = document.getElementById('deleteMultipleMessage');
            const footer = document.getElementById('deleteMultipleFooter');
            if (checked.length === 0) {
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins un grade à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> grade(s) sélectionné(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
                footer.innerHTML = '<button type="button" class="button" onclick="confirmDeleteMultiple()">Oui, supprimer</button>' +
                    '<button type="button" class="button secondary" onclick="closeDeleteMultipleModal()">Non</button>';
            }
            document.getElementById('confirmation-modal').style.display = 'flex';
        }

        function closeDeleteMultipleModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
        }

        function confirmDeleteMultiple() {
            document.getElementById('bulkDeleteForm').submit();
        }
    </script>
</body>

</html>