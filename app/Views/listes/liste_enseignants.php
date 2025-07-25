<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'enseignants') {
    return;
}

require_once __DIR__ . '/../../config/config.php';


$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// Chargé de compte rendu
if (isset($_POST['id_ens_cr']) && !empty($_POST['id_ens_cr'])) {
    $id_ens_cr = intval($_POST['id_ens_cr']);

    // 1. Désactiver tous les responsables
    $pdo->exec("UPDATE responsable_compte_rendu SET actif = 0");

    // 2. Si ce prof est déjà dans la table, on le met actif = 1. Sinon, on l'insère.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM responsable_compte_rendu WHERE id_ens = ?");
    $stmt->execute([$id_ens_cr]);

    if ($stmt->fetchColumn() > 0) {
        // Déjà existant => update
        $stmt = $pdo->prepare("UPDATE responsable_compte_rendu SET actif = 1 WHERE id_ens = ?");
        $stmt->execute([$id_ens_cr]);
    } else {
        // Nouveau => insert
        $stmt = $pdo->prepare("INSERT INTO responsable_compte_rendu (id_ens, actif) VALUES (?, 1)");
        $stmt->execute([$id_ens_cr]);
    }
}

// --- Recherche, filtres et pagination ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if ($search !== '') {
    $where_conditions[] = "(ens.nom_ens LIKE ? OR ens.prenoms_ens LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

if ($status_filter !== '') {
    $where_conditions[] = "rcr.actif = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) FROM responsable_compte_rendu rcr
              JOIN enseignants ens ON ens.id_ens = rcr.id_ens $where_clause";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_enseignants = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_enseignants / $per_page));

// Récupérer les enseignants filtrés et paginés
$sql = "SELECT rcr.*, ens.nom_ens, ens.prenoms_ens 
        FROM responsable_compte_rendu rcr
        JOIN enseignants ens ON ens.id_ens = rcr.id_ens 
        $where_clause 
        ORDER BY ens.nom_ens 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$enseignants_datas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des enseignants membre de commission</title>
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
    </style>
</head>


<body>

    <div class="header">
        <div class="header-title">
            <div class="img-container">
                <img src="/GSCV+/public/assets/images/logo_mi_sbg.png" alt="">
            </div>
            <div class="text-container">
                <h1>Liste des Enseignants membre de commission</h1>
                <p>Gestion des attributions de la redaction des comptes rendus de commission</p>
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
        <a href="?page=parametres_generaux" class="button">
            <i class="fas fa-arrow-left"></i> Retour aux paramètres
        </a>
        <form method="GET" class="search-box" style="display:inline-flex;align-items:center;gap:5px;">
            <input type="hidden" name="page" value="parametres_generaux">
            <input type="hidden" name="liste" value="enseignants">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Rechercher un enseignant..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="button" style="margin-left:5px;">Rechercher</button>
        </form>
        <button class="button" onclick="showAddModal()">
            <i class="fas fa-plus"></i>
            Charger de compte rendu
        </button>
    </div>

    <!-- Filtres -->
    <div class="filters">
        <form method="GET" style="display:inline;">
            <input type="hidden" name="page" value="parametres_generaux">
            <input type="hidden" name="liste" value="enseignants">
            <!-- Garder la recherche si présente -->
            <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
            <select class="filter-select" name="status" onchange="this.form.submit()">
                <option value="">Tous les statuts</option>
                <option value="active" <?php echo ($status_filter === 'active') ? 'selected' : ''; ?>>Actif</option>
                <option value="inactive" <?php echo ($status_filter === 'inactive') ? 'selected' : ''; ?>>Inactif</option>
            </select>
        </form>
    </div>

    <!-- Table de données -->
    <div class="data-table-container">
        <div class="data-table-header">
            <div class="data-table-title">Liste des enseignants (<?php echo $total_enseignants; ?> éléments)</div>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th></th>
                    <th>Nom de l'enseignant</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($enseignants_datas) === 0): ?>
                    <tr>
                        <td colspan="3" style="text-align:center;">Aucun enseignant trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($enseignants_datas as $enseignants_data): ?>
                        <tr>
                            <td><input type="checkbox"></td>
                            <td><?php echo $enseignants_data['nom_ens'] . ' ' . $enseignants_data['prenoms_ens']; ?></td>
                            <td>
                                <?php echo ($enseignants_data['actif'] == 1) ? 'Actif' : 'Inactif'; ?>
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
            <a class="page-item" href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=1">«</a>
            <a class="page-item" href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $page - 1; ?>">‹</a>
        <?php endif; ?>
        <?php
        // Affichage de 5 pages max autour de la page courante
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <a class="page-item<?php if ($i == $page) echo ' active'; ?>" href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a class="page-item" href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $page + 1; ?>">›</a>
            <a class="page-item" href="?page=parametres_generaux&liste=enseignants&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $total_pages; ?>">»</a>
        <?php endif; ?>
    </div>

    <!-- Modal pour charger un enseignant d'élaborer le compte rendu -->
    <div id="enseignantsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Assigner compte rendu</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <form id="enseignantsForm" method="POST">
                <input type="hidden" name="page" value="parametres_generaux">
                <input type="hidden" name="liste" value="enseignants">
                <input type="hidden" id="id_ens" name="id_ens">
                <div class="form-group">
                    <label for="id_ens_cr">Nom de l'enseignant</label>
                    <select name="id_ens_cr" id="id_ens_cr">
                        <option value="">-- Sélectionnez un enseignant --</option>
                        <?php
                        $enseignants = $pdo->prepare("
                        SELECT e.id_ens, e.nom_ens, e.prenoms_ens
                        FROM enseignants e
                        JOIN utilisateur u ON u.login_utilisateur = e.email_ens
                        JOIN posseder p ON p.id_util = u.id_utilisateur
                        WHERE p.id_gu = 8 OR p.id_gu = 9
                        ");
                        $enseignants->execute();
                        $enseignants_list = $enseignants->fetchAll();
                        foreach ($enseignants_list as $ens) {
                            echo "<option value=\"{$ens['id_ens']}\">{$ens['nom_ens']} {$ens['prenoms_ens']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="button">Assigner</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Charger de compte rendu'
            document.getElementById('enseignantsModal').style.display = 'block';
        }

        function editEntreprise(id, libelle) {
            document.getElementById('modalTitle').textContent = '<i class="fas fa-eye"></i>';
            document.getElementById('id_ens_cr').value = id;
            document.getElementById('enseignantsModal').style.display = 'block';
        }

        function deleteEntreprise(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette entreprise ?')) {
                // Ajouter ici la logique de suppression
            }
        }

        function closeModal() {
            document.getElementById('enseignantsModal').style.display = 'none';
        }

        // Fermer la modale si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('enseignantsModal')) {
                closeModal();
            }
        }

        // Empêcher la fermeture de la modale lors du clic sur son contenu
        document.querySelector('.modal-content').onclick = function(event) {
            event.stopPropagation();
        }
    </script>

</body>

</html>