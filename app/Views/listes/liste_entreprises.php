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
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Entreprises</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .details-rows {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .details-rows .detail-group {
            flex: 1;
            margin-bottom: 0;
            min-height: 100px;
            display: flex;
            justify-content: center;
        }

        .details-rows .detail-icon {
            align-self: center;
            margin-right: 0;
            margin-bottom: 10px;
        }

        .details-rows .detail-content {
            text-align: center;
        }

        .details-rows .detail-content label {
            margin-bottom: 8px;
        }

        .details-rows .detail-content span {
            font-weight: 500;
        }

        /* Style pour les cartes vides ou avec peu de contenu */
        .details-rows .detail-group:empty {
            visibility: hidden;
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

        /* Styles spécifiques pour la modale de détails */
        #viewEntrepriseModal .modal-content {
            width: 60%;
            max-width: 800px;
            margin: 3% auto;

        }

        .entreprise-details {
            padding: 30px;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .detail-group {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid #4a90e2;
        }

        .detail-group:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .detail-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4a90e2, #357abd);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .detail-icon i {
            color: white;
            font-size: 16px;
            background-color: #1a5276;
        }

        .detail-content {
            flex: 1;
        }

        .detail-content label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-content span {
            display: block;
            color: #333;
            font-size: 16px;
            line-height: 1.4;
            word-break: break-word;
        }



        /* Animation d'entrée pour les détails */
        .detail-group {
            animation: slideInDetail 0.5s ease-out;
            animation-fill-mode: both;
        }

        .detail-group:nth-child(1) {
            animation-delay: 0.1s;
        }

        .detail-group:nth-child(2) {
            animation-delay: 0.2s;
        }

        .detail-group:nth-child(3) {
            animation-delay: 0.3s;
        }

        .detail-group:nth-child(4) {
            animation-delay: 0.4s;
        }

        .detail-group:nth-child(5) {
            animation-delay: 0.5s;
        }

        .detail-group:nth-child(6) {
            animation-delay: 0.6s;
        }

        @keyframes slideInDetail {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Responsive pour les détails en lignes */
        @media (max-width: 768px) {
            #viewEntrepriseModal .modal-content {
                width: 95%;
                margin: 10px;
            }

            .details-rows {
                flex-direction: column;
                gap: 15px;
            }

            .details-rows .detail-group {
                min-height: auto;
            }

            .details-rows .detail-icon {
                margin-bottom: 8px;
            }

            .detail-group {
                flex-direction: column;
                text-align: center;
            }

            .detail-icon {
                margin-right: 0;
                margin-bottom: 10px;
            }
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
                <h1>Liste des Entreprises</h1>
                <p>Gestion des entreprises partenaires</p>
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
        <!-- Barre d'actions -->
        <div class="actions-bar">
            <a href="?page=parametres_generaux" class="button">
                <i class="fas fa-arrow-left"></i> Retour aux paramètres
            </a>
            <form method="GET" class="search-box" style="display:inline-flex;align-items:center;gap:5px;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Rechercher une entreprise..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="button" style="margin-left:5px;">Rechercher</button>
            </form>
            <button class="button" onclick="showAddModal()">
                <i class="fas fa-plus"></i>
                Ajouter une entreprise
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

        <!-- Table de données -->
        <div class="data-table-container">
            <div class="data-table-header">
                <div class="data-table-title">Liste des entreprises</div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                        <th style="width: 50px;">ID</th>
                        <th>Nom de l'entreprise</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($entreprises) === 0): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">Aucune entreprise trouvée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entreprises as $entreprise): ?>
                            <tr>
                                <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($entreprise['id_entr']); ?>"></td>
                                <td><?php echo $entreprise['id_entr']; ?></td>
                                <td><?php echo $entreprise['lib_entr']; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-button view-button" title="Voir" onclick="viewEntreprise(<?= $entreprise['id_entr']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-button edit-button" title="Modifier" onclick="editEntreprise(<?= $entreprise['id_entr']; ?>, '<?= htmlspecialchars(addslashes($entreprise['lib_entr'])); ?>')">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="action-button delete-button" title="Supprimer" onclick="showDeleteModal(<?= $entreprise['id_entr']; ?>, '<?= htmlspecialchars(addslashes($entreprise['lib_entr'])); ?>')">
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

    <!-- Modal pour ajouter/modifier une entreprise -->
    <div id="entrepriseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Ajouter une entreprise</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="entrepriseForm" method="POST">
                    <input type="hidden" id="id_entr" name="id_entr">
                    <div class="form-group">
                        <label for="lib_entr">Nom de l'entreprise</label>
                        <input type="text" id="lib_entr" name="lib_entr" required>
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
            <p id="deleteSingleMessage">Êtes-vous sûr de vouloir supprimer cette entreprise ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span></p>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeDeleteModal()">Annuler</button>
                <form id="deleteForm" method="POST" style="display:inline;">
                    <input type="hidden" id="delete_entreprise_id" name="delete_entreprise_id">
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

    <!-- Modal pour voir les détails d'une entreprise -->
    <div id="viewEntrepriseModal" class="modal" <?php echo (isset($_GET['view']) && $selected_entreprise) ? 'style="display: block;"' : ''; ?>>
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-building"></i> Détails de l'entreprise</h2>
                <span class="close" onclick="closeViewModal()">&times;</span>
            </div>
            <?php if ($selected_entreprise): ?>
                <div class="entreprise-details">

                    <div class="details-rows">
                        <div class="detail-group info-nom">
                            <div class="detail-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="detail-content">
                                <label>ID de l'entreprise</label>
                                <span><?php echo htmlspecialchars($selected_entreprise['id_entr']); ?></span>
                            </div>
                        </div>

                        <div class="detail-group info-nom">
                            <div class="detail-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="detail-content">
                                <label>Nom de l'entreprise</label>
                                <span><?php echo htmlspecialchars($selected_entreprise['lib_entr']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="details-rows">
                        <div class="detail-group info-location">
                            <div class="detail-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="detail-content">
                                <label>Pays</label>
                                <span><?php echo !empty($selected_entreprise['pays']) ? htmlspecialchars($selected_entreprise['pays']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>

                        <div class="detail-group info-location">
                            <div class="detail-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="detail-content">
                                <label>Ville</label>
                                <span><?php echo !empty($selected_entreprise['ville']) ? htmlspecialchars($selected_entreprise['ville']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="details-rows">
                        <div class="detail-group info-adresse">
                            <div class="detail-icon">
                                <i class="fas fa-map"></i>
                            </div>
                            <div class="detail-content">
                                <label>Adresse géographique</label>
                                <span><?php echo !empty($selected_entreprise['adresse']) ? htmlspecialchars($selected_entreprise['adresse']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>

                        <div class="detail-group info-contact">
                            <div class="detail-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="detail-content">
                                <label>Adresse mail</label>
                                <span><?php echo !empty($selected_entreprise['email']) ? htmlspecialchars($selected_entreprise['email']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="details-rows">
                        <div class="detail-group info-contact">
                            <div class="detail-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="detail-content">
                                <label>Téléphone</label>
                                <span><?php echo !empty($selected_entreprise['telephone']) ? htmlspecialchars($selected_entreprise['telephone']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>

                        <div class="detail-group info-nom">
                            <div class="detail-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="detail-content">
                                <label>Date d'ajout</label>
                                <span><?php echo !empty($selected_entreprise['date_creation']) ? htmlspecialchars($selected_entreprise['date_creation']) : 'Non renseigné'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Entreprise non trouvée
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeViewModal()">
                        <i class="fas fa-times"></i> Fermer
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter une entreprise';
            document.getElementById('entrepriseForm').reset();
            document.getElementById('id_entr').value = '';
            document.getElementById('entrepriseModal').style.display = 'block';
        }

        function editEntreprise(id, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier une entreprise';
            document.getElementById('id_entr').value = id;
            document.getElementById('lib_entr').value = libelle;
            document.getElementById('entrepriseModal').style.display = 'block';
        }

        function viewEntreprise(id) {
            window.location.href = '?view=' + id;
        }

        function showDeleteModal(id, libelle) {
            document.getElementById('delete_entreprise_id').value = id;
            document.getElementById('deleteSingleMessage').innerHTML = "Êtes-vous sûr de vouloir supprimer l'entreprise : '<b>" + libelle + "</b>' ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>";
            document.getElementById('confirmation-modal-single').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('entrepriseModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').style.display = 'none';
        }

        function closeViewModal() {
            document.getElementById('viewEntrepriseModal').style.display = 'none';
            window.location.href = window.location.pathname;
        }

        // Gestionnaire d'événements pour le bouton de fermeture
        if (document.querySelector('.close')) {
            document.querySelectorAll('.close').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    closeModal();
                    closeDeleteModal();
                    closeViewModal();
                });
            });
        }

        // Fermer la modale si on clique en dehors
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('entrepriseModal')) {
                closeModal();
            }
            if (event.target == document.getElementById('confirmation-modal-single')) {
                closeDeleteModal();
            }
            if (event.target == document.getElementById('viewEntrepriseModal')) {
                closeViewModal();
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

        function openDeleteMultipleModal() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const msg = document.getElementById('deleteMultipleMessage');
            const footer = document.getElementById('deleteMultipleFooter');
            if (checked.length === 0) {
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins une entreprise à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> entreprise(s) sélectionnée(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
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