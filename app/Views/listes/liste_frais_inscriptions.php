<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'frais_inscriptions') {
    return;
}

require_once __DIR__ . '/../../config/config.php';


$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// --- Recherche, filtres et pagination ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$niveau_filter = isset($_GET['niveau']) ? $_GET['niveau'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if ($search !== '') {
    $where_conditions[] = "(ne.lib_niv_etd LIKE ? OR CONCAT(aa.date_debut, ' - ', aa.date_fin) LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

if ($niveau_filter !== '') {
    $where_conditions[] = "f.id_niv_etd = ?";
    $params[] = $niveau_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) 
              FROM frais_inscription f 
              JOIN niveau_etude ne ON ne.id_niv_etd = f.id_niv_etd 
              JOIN annee_academique aa ON aa.id_ac = f.id_ac 
              $where_clause";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_tarifs = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_tarifs / $per_page));

// Récupérer les tarifs filtrés et paginés
$sql = "SELECT f.*, ne.lib_niv_etd, aa.date_debut, aa.date_fin 
        FROM frais_inscription f 
        JOIN niveau_etude ne ON ne.id_niv_etd = f.id_niv_etd 
        JOIN annee_academique aa ON aa.id_ac = f.id_ac 
        $where_clause 
        ORDER BY f.id_frais 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Récupération de l'année en cours
$sql = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$annee = $stmt->fetch(PDO::FETCH_ASSOC);
$dateDebut = new DateTime($annee['date_debut']);
$dateFin = new DateTime($annee['date_fin']);
$date = $dateDebut->format('Y') . '-' . $dateFin->format('Y');

// Traitement des frais d'inscriptions
if (isset($_POST['tarifs']) && isset($_POST['id_niv_etd'])) {
    $id_niveau_etd = intval($_POST['id_niv_etd']);
    $tarifs = $_POST['tarifs'];

    //Récupération de l'année academique (id)
    $query = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_ac = $result['id_ac'];

    //Récupération de l'id du niveau étudiant
    $query = "SELECT COUNT(*) FROM niveau_etude WHERE id_niv_etd = ? ";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_niveau_etd]);
    if ($stmt->fetchColumn() > 0) {
        // Vérifier si les frais existent déjà pour ce niveau et cette année
        $check_query = "SELECT COUNT(*) FROM frais_inscription WHERE id_niv_etd = ? AND id_ac = ?";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([$id_niveau_etd, $id_ac]);

        if ($check_stmt->fetchColumn() > 0) {
            // Mettre à jour les frais existants
            $sql = "UPDATE frais_inscription SET montant = ? WHERE id_niv_etd = ? AND id_ac = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tarifs, $id_niveau_etd, $id_ac]);
            $_SESSION['success'] = "Les frais d'inscription ont été mis à jour avec succès";
        } else {
            //Insertion dans la table frais inscription
            $sql = "INSERT INTO frais_inscription (id_niv_etd, id_ac, montant) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_niveau_etd, $id_ac, $tarifs]);
            $_SESSION['success'] = "Frais d'inscriptions ajoutés avec succès";
        }
    } else {
        $_SESSION['error'] = "Le niveau d'étude sélectionné n'existe pas";
    }
    // Redirection pour éviter le repost
    header('Location: ?page=parametres_generaux&liste=frais_inscriptions');
    exit;
}

// ... Ajout du traitement PHP pour la suppression multiple ...
if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
    $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM frais_inscription WHERE id_frais IN ($placeholders)");
        $stmt->execute($ids);
        $_SESSION['success'] = count($ids) . " frais supprimé(s) avec succès.";
    } else {
        $_SESSION['error'] = "Aucun frais sélectionné.";
    }
    header('Location: ?page=parametres_generaux&liste=frais_inscriptions');
    exit;
}
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Niveaux d'Études - Tableau de Bord Commission</title>
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
            width: 30%;
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

        .delete-button {
            color: var(--danger-color);
            background-color: rgba(231, 76, 60, 0.1);
        }

        .edit-button {
            color: var(--text-color);
            background-color: rgba(26, 82, 118, 0.1);
        }


        .edit-button:hover {
            background-color: var(--text-color);
            color: var(--text-light);
        }

        .action-button {
            text-decoration: none;
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
                <h1>Liste des frais d'inscription</h1>
                <p>Gestion des frais d'inscription par niveau</p>
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

    <!-- Barre d'actions -->
    <div class="actions-bar">
        <a href="?page=parametres_generaux" class="button back-to-params"><i class="fas fa-arrow-left"></i> Retour aux paramètres généraux</a>
        <form method="GET" class="search-box" style="display:inline-flex;align-items:center;gap:5px;">
            <input type="hidden" name="page" value="parametres_generaux">
            <input type="hidden" name="liste" value="frais_inscriptions">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Rechercher par niveau ou année..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="button" style="margin-left:5px;">Rechercher</button>
        </form>
        <button class="button" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Ajouter un tarif
        </button>
    </div>

    <!-- Filtres -->
    <div class="filters">
        <form method="GET" style="display:inline;">
            <input type="hidden" name="page" value="parametres_generaux">
            <input type="hidden" name="liste" value="frais_inscriptions">
            <!-- Garder la recherche si présente -->
            <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
            <select class="filter-select" name="niveau" onchange="this.form.submit()">
                <option value="">Tous les niveaux</option>
                <?php
                $niveaux = $pdo->prepare("SELECT * FROM niveau_etude ORDER BY lib_niv_etd");
                $niveaux->execute();
                $niveaux_list = $niveaux->fetchAll();
                foreach ($niveaux_list as $niv) {
                    $selected = ($niveau_filter == $niv['id_niv_etd']) ? 'selected' : '';
                    echo "<option value=\"{$niv['id_niv_etd']}\" $selected>{$niv['lib_niv_etd']}</option>";
                }
                ?>
            </select>
        </form>
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
        <input type="hidden" name="page" value="parametres_generaux">
        <input type="hidden" name="liste" value="frais_inscriptions">
        <input type="hidden" name="bulk_delete" value="1">
        <button type="button" class="button danger" id="bulkDeleteBtn"><i class="fas fa-trash"></i> Supprimer la sélection</button>
        <input type="hidden" name="delete_selected_ids[]" id="delete_selected_ids">
    </form>

    <div class="data-table-container">
        <div class="data-table-header">
            <div class="data-table-title">Liste des tarifs inscriptions (<?php echo $total_tarifs; ?> éléments)</div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <td><input type="checkbox" id="selectAll"></td>
                    <th>N°</th>
                    <th>Niveau d'étude</th>
                    <th>Année académique</th>
                    <th>Montant</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($tarifs) === 0): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">Aucun tarif trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tarifs as $index => $tarif): ?>
                        <tr>
                            <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($tarif['id_frais']); ?>"></td>
                            <td><?php echo $offset + $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($tarif['lib_niv_etd']); ?></td>
                            <td>
                                <?php
                                $dateDebut = new DateTime($tarif['date_debut']);
                                $dateFin = new DateTime($tarif['date_fin']);
                                echo $dateDebut->format('Y') . ' - ' . $dateFin->format('Y');
                                ?>
                            </td>
                            <td><?php echo number_format($tarif['montant'], 0, ',', ' '); ?> FCFA</td>
                            <td style="display: flex; gap: 10px;">
                                <a href="javascript:void(0)" onclick="showEditModal(<?php echo $tarif['id_frais']; ?>, <?php echo $tarif['id_niv_etd']; ?>, <?php echo $tarif['montant']; ?>)" class="action-button edit-button" title="Modifier"><i class="fas fa-edit"></i></a>
                                <a href="javascript:void(0)" onclick="showDeleteModal(<?php echo $tarif['id_frais']; ?>)" class="action-button delete-button" title="Supprimer"><i class="fas fa-trash"></i></a>
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
            <a class="page-item" href="?page=parametres_generaux&liste=frais_inscriptions&search=<?php echo urlencode($search); ?>&niveau=<?php echo urlencode($niveau_filter); ?>&page=1">«</a>
            <a class="page-item" href="?page=parametres_generaux&liste=frais_inscriptions&search=<?php echo urlencode($search); ?>&niveau=<?php echo urlencode($niveau_filter); ?>&page=<?php echo $page - 1; ?>">‹</a>
        <?php endif; ?>
        <?php
        // Affichage de 5 pages max autour de la page courante
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <a class="page-item<?php if ($i == $page) echo ' active'; ?>" href="?page=parametres_generaux&liste=frais_inscriptions&search=<?php echo urlencode($search); ?>&niveau=<?php echo urlencode($niveau_filter); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a class="page-item" href="?page=parametres_generaux&liste=frais_inscriptions&search=<?php echo urlencode($search); ?>&niveau=<?php echo urlencode($niveau_filter); ?>&page=<?php echo $page + 1; ?>">›</a>
            <a class="page-item" href="?page=parametres_generaux&liste=frais_inscriptions&search=<?php echo urlencode($search); ?>&niveau=<?php echo urlencode($niveau_filter); ?>&page=<?php echo $total_pages; ?>">»</a>
        <?php endif; ?>
    </div>

    <!-- Modal pour ajouter des frais d'inscription -->
    <div id="fraisModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Ajouter des frais d'inscription</h2>
            <form id="fraisForm" method="POST">
                <input type="hidden" name="page" value="parametres_generaux">
                <input type="hidden" name="liste" value="frais_inscriptions">
                <div class="form-group">
                    <label for="id_niv_etd">Niveau d'étude: </label>
                    <select name="id_niv_etd" id="id_niv_etd" required>
                        <option value="">-- Sélectionnez un niveau d'étude--</option>
                        <?php
                        $niveaux = $pdo->prepare("SELECT * FROM niveau_etude");
                        $niveaux->execute();
                        $niveaux_list = $niveaux->fetchAll();
                        foreach ($niveaux_list as $niv) {
                            echo "<option value=\"{$niv['id_niv_etd']}\">{$niv['lib_niv_etd']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tarifs">Montant (FCFA): </label>
                    <input type="number" name="tarifs" id="tarifs" required>
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
            <span class="close">&times;</span>
            <h2>Confirmation de suppression</h2>
            <p>Êtes-vous sûr de vouloir supprimer ces frais d'inscription ?</p>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeDeleteModal()">Annuler</button>
                <button type="button" class="button" id="confirmDelete">Supprimer</button>
            </div>
        </div>
    </div>

    <!-- Modal pour modifier des frais d'inscription -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Modifier les frais d'inscription</h2>
            <form id="editForm" method="POST" action="./traitements/modifier_frais_inscription.php">
                <input type="hidden" name="page" value="parametres_generaux">
                <input type="hidden" name="liste" value="frais_inscriptions">
                <input type="hidden" id="edit_id_frais" name="id_frais">
                <div class="form-group">
                    <label for="edit_id_niv_etd">Niveau d'étude: </label>
                    <select name="id_niv_etd" id="edit_id_niv_etd" required>
                        <option value="">-- Sélectionnez un niveau d'étude--</option>
                        <?php
                        $niveaux = $pdo->prepare("SELECT * FROM niveau_etude");
                        $niveaux->execute();
                        $niveaux_list = $niveaux->fetchAll();
                        foreach ($niveaux_list as $niv) {
                            echo "<option value=\"{$niv['id_niv_etd']}\">{$niv['lib_niv_etd']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_tarifs">Montant (FCFA): </label>
                    <input type="number" name="tarifs" id="edit_tarifs" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="button">Enregistrer</button>
                </div>
            </form>
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
            document.getElementById('modalTitle').textContent = 'Ajouter des frais d\'inscription';
            document.getElementById('fraisForm').reset();
            document.getElementById('fraisModal').style.display = 'block';
        }

        function showDeleteModal(id) {
            document.getElementById('deleteModal').style.display = 'block';
            document.getElementById('confirmDelete').onclick = function() {
                window.location.href = 'supprimer_frais_inscription.php?id=' + id;
            };
        }

        function closeModal() {
            document.getElementById('fraisModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        function showEditModal(id, niveauId, montant) {
            document.getElementById('edit_id_frais').value = id;
            document.getElementById('edit_id_niv_etd').value = niveauId;
            document.getElementById('edit_tarifs').value = montant;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Fermer les modales si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('fraisModal')) {
                closeModal();
            }
            if (event.target == document.getElementById('deleteModal')) {
                closeDeleteModal();
            }
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }

        // Empêcher la fermeture des modales lors du clic sur leur contenu
        document.querySelectorAll('.modal-content').forEach(function(content) {
            content.onclick = function(event) {
                event.stopPropagation();
            }
        });

        // Gestion des boutons de fermeture
        document.querySelectorAll('.close').forEach(function(closeBtn) {
            closeBtn.onclick = function() {
                closeModal();
                closeDeleteModal();
                closeEditModal();
            }
        });

        // Gestion de la sélection multiple
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
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins un frais à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> frais sélectionné(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
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