<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'groupes_utilisateurs') {
    return;
}

require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lib_gu'])) {
        $lib_groupe = $_POST['lib_gu'];

        if (isset($_POST['id_gu']) && !empty($_POST['id_gu'])) {
            // Modification d'un groupe existant
            $id_groupe = $_POST['id_gu'];
            $sql = "UPDATE groupe_utilisateur SET lib_gu = ? WHERE id_gu = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_groupe, $id_groupe]);

            // Mise à jour des traitements
            if (isset($_POST['traitements'])) {
                // Supprimer les anciennes associations
                $sql = "DELETE FROM rattacher WHERE id_gu = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_groupe]);

                // Ajouter les nouvelles associations
                $sql = "INSERT INTO rattacher (id_gu, id_traitement) VALUES (?, ?)";
                $stmt = $pdo->prepare($sql);
                foreach ($_POST['traitements'] as $id_traitement) {
                    $stmt->execute([$id_groupe, $id_traitement]);
                }
            }

            $_SESSION['success'] = "Groupe modifié avec succès.";
        } else {
            // Ajout d'un nouveau groupe
            $sql = "INSERT INTO groupe_utilisateur (lib_gu) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_groupe]);
            $_SESSION['success'] = "Groupe ajouté avec succès.";
        }

        // Mise à jour des permissions
        updateUserPermissions();

        header('Location: ?page=parametres_generaux&liste=groupes_utilisateurs');
        exit;
    }

    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM groupe_utilisateur WHERE id_gu IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success'] = count($ids) . " groupe(s) supprimé(s) avec succès.";
        } else {
            $_SESSION['error'] = "Aucun groupe sélectionné.";
        }
        header('Location: ?page=parametres_generaux&liste=groupes_utilisateurs');
        exit;
    }
}

// Fonction pour mettre à jour les permissions de l'utilisateur
function updateUserPermissions()
{
    global $pdo;

    // Récupérer l'ID de l'utilisateur connecté
    $id_utilisateur = $_SESSION['user_id'];

    // Récupérer les traitements associés à l'utilisateur via ses groupes et types d'utilisateur
    $sql = "SELECT DISTINCT t.lib_traitement 
            FROM traitement t 
            JOIN rattacher r ON t.id_traitement = r.id_traitement 
            JOIN groupe_utilisateur g ON r.id_gu = g.id_gu 
            JOIN type_a_groupe tag ON g.id_gu = tag.id_gu
            JOIN type_utilisateur tu ON tag.id_tu = tu.id_tu
            JOIN utilisateur_type_utilisateur utu ON tu.id_tu = utu.id_tu
            JOIN utilisateur u ON utu.id_utilisateur = u.id_utilisateur 
            WHERE u.id_utilisateur = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_utilisateur]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Mettre à jour les permissions dans la session
    $_SESSION['user_permissions'] = array_map('strtolower', $permissions);
}

// Récupération des informations pour l'affichage
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id_groupe = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- Recherche et pagination ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if ($search !== '') {
    $where_conditions[] = "g.lib_gu LIKE ?";
    $params[] = "%$search%";
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) 
              FROM groupe_utilisateur g
              LEFT JOIN rattacher r ON g.id_gu = r.id_gu
              LEFT JOIN traitement t ON r.id_traitement = t.id_traitement
              LEFT JOIN type_a_groupe tag ON g.id_gu = tag.id_gu
              LEFT JOIN type_utilisateur tu ON tag.id_tu = tu.id_tu
              $where_clause
              GROUP BY g.id_gu";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_groupes = $stmt_count->rowCount();
$total_pages = max(1, ceil($total_groupes / $per_page));

// Récupération des groupes d'utilisateurs avec leurs traitements et type d'utilisateur
$sql = "SELECT g.*, 
               GROUP_CONCAT(DISTINCT t.nom_traitement) as traitements,
               GROUP_CONCAT(DISTINCT t.id_traitement) as id_traitements,
               COUNT(DISTINCT t.id_traitement) as nombre_traitements,
               tu.lib_tu
        FROM groupe_utilisateur g
        LEFT JOIN rattacher r ON g.id_gu = r.id_gu
        LEFT JOIN traitement t ON r.id_traitement = t.id_traitement
        LEFT JOIN type_a_groupe tag ON g.id_gu = tag.id_gu
        LEFT JOIN type_utilisateur tu ON tag.id_tu = tu.id_tu
        $where_clause
        GROUP BY g.id_gu
        ORDER BY g.id_gu
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération de tous les groupes pour la pagination (sans limite)
$sql_all = "SELECT g.*, 
                   GROUP_CONCAT(DISTINCT t.nom_traitement) as traitements,
                   GROUP_CONCAT(DISTINCT t.id_traitement) as id_traitements,
                   COUNT(DISTINCT t.id_traitement) as nombre_traitements,
                   tu.lib_tu
            FROM groupe_utilisateur g
            LEFT JOIN rattacher r ON g.id_gu = r.id_gu
            LEFT JOIN traitement t ON r.id_traitement = t.id_traitement
            LEFT JOIN type_a_groupe tag ON g.id_gu = tag.id_gu
            LEFT JOIN type_utilisateur tu ON tag.id_tu = tu.id_tu
            $where_clause
            GROUP BY g.id_gu
            ORDER BY g.id_gu";
$stmt_all = $pdo->prepare($sql_all);
$stmt_all->execute($params);
$all_groupes = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
$total_groupes = count($all_groupes);
$total_pages = max(1, ceil($total_groupes / $per_page));

// Récupération des groupes pour la page courante
$groupes_page = array_slice($all_groupes, $offset, $per_page);

// Récupération de tous les traitements pour la modale de modification
$traitements = $pdo->query("SELECT * FROM traitement ORDER BY nom_traitement")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des informations du groupe sélectionné si nécessaire
$groupe_selectionne = null;
if ($id_groupe > 0) {
    foreach ($groupes as $groupe) {
        if ($groupe['id_gu'] == $id_groupe) {
            $groupe_selectionne = $groupe;
            break;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Groupes d'Utilisateurs - Tableau de Bord Commission</title>
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
            width: 40%;
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
            border-color: #1a5276;
            outline: none;
        }

        .modal-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            text-align: right;
        }

        /* Styles pour les détails du groupe */
        .groupe-details {
            padding: 20px;
        }

        .detail-group {
            margin-bottom: 15px;
        }

        .detail-group label {
            font-weight: bold;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }

        .detail-group span {
            color: #333;
        }

        .traitements-list {
            margin-top: 10px;
        }

        .traitements-list .traitement-item {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .traitements-list .traitement-item i {
            color: #28a745;
        }

        /* Styles pour les checkboxes des traitements */
        .traitements-checkboxes {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
        }

        .checkbox-item {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-item input[type="checkbox"] {
            width: auto;
        }

        .checkbox-item label {
            margin: 0;
            font-weight: normal;
        }

        /* Styles pour la pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }

        .pagination .page-item {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination .page-item:hover {
            background-color: #f5f5f5;
        }

        .pagination .page-item.active {
            background-color: #1a5276;
            color: white;
            border-color: #1a5276;
        }

        .pagination .page-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
                <h1>Liste des Groupes d'Utilisateurs</h1>
                <p>Gestion des groupes d'utilisateurs du système</p>
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
            <input type="hidden" name="liste" value="groupes_utilisateurs">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Rechercher un groupe d'utilisateurs..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="button" style="margin-left:5px;">Rechercher</button>
        </form>
        <a href="?page=parametres_generaux&liste=groupes_utilisateurs&action=add" class="button">
            <i class="fas fa-plus"></i> Ajouter un groupe d'utilisateur
        </a>
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
            <div class="data-table-title">Liste des groupes d'utilisateurs (<?php echo $total_groupes; ?> éléments)</div>
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
                <?php if (count($groupes_page) === 0): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;">Aucun groupe trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($groupes_page as $groupe): ?>
                        <tr>
                            <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($groupe['id_gu']); ?>"></td>
                            <td><?php echo htmlspecialchars($groupe['id_gu']); ?></td>
                            <td><?php echo htmlspecialchars($groupe['lib_gu']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?page=parametres_generaux&liste=groupes_utilisateurs&action=view&id=<?php echo $groupe['id_gu']; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="action-button view-button" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?page=parametres_generaux&liste=groupes_utilisateurs&action=edit&id=<?php echo $groupe['id_gu']; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="action-button edit-button" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="?page=parametres_generaux&liste=groupes_utilisateurs&action=delete&id=<?php echo $groupe['id_gu']; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="action-button delete-button" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce groupe ?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
            <a class="page-item" href="?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=1">«</a>
            <a class="page-item" href="?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">‹</a>
        <?php endif; ?>
        <?php
        // Affichage de 5 pages max autour de la page courante
        $start = max(1, $page - 2);
        $end = min($total_pages, $page + 2);
        for ($i = $start; $i <= $end; $i++):
        ?>
            <a class="page-item<?php if ($i == $page) echo ' active'; ?>" href="?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a class="page-item" href="?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">›</a>
            <a class="page-item" href="?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $total_pages; ?>">»</a>
        <?php endif; ?>
    </div>

    <!-- Bouton de suppression multiple -->
    <form id="bulkDeleteForm" method="POST" style="margin-bottom:10px;">
        <input type="hidden" name="page" value="parametres_generaux">
        <input type="hidden" name="liste" value="groupes_utilisateurs">
        <input type="hidden" name="bulk_delete" value="1">
        <button type="button" class="button danger" id="bulkDeleteBtn"><i class="fas fa-trash"></i> Supprimer la sélection</button>
        <input type="hidden" name="delete_selected_ids[]" id="delete_selected_ids">
    </form>

    <?php if ($action === 'view' && $groupe_selectionne): ?>
        <!-- Modal pour afficher les détails d'un groupe -->
        <div class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="window.location.href='?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>'">&times;</span>
                <h2>Détails du groupe : <?php echo htmlspecialchars($groupe_selectionne['lib_gu']); ?></h2>
                <div class="groupe-details">
                    <div class="detail-group">
                        <label>Nom du groupe :</label>
                        <span><?php echo htmlspecialchars($groupe_selectionne['lib_gu']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Type d'utilisateur :</label>
                        <span><?php echo htmlspecialchars($groupe_selectionne['lib_tu'] ?? 'Non défini'); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Nombre de traitements :</label>
                        <span><?php echo $groupe_selectionne['nombre_traitements']; ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Traitements associés :</label>
                        <div class="traitements-list">
                            <?php if ($groupe_selectionne['traitements']): ?>
                                <?php foreach (explode(',', $groupe_selectionne['traitements']) as $traitement): ?>
                                    <div class="traitement-item">
                                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($traitement); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Aucun traitement associé</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="button">Fermer</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($action === 'edit' && $groupe_selectionne): ?>
        <!-- Modal pour modifier un groupe -->
        <div class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="window.location.href='?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>'">&times;</span>
                <h2>Modifier le groupe : <?php echo htmlspecialchars($groupe_selectionne['lib_gu']); ?></h2>
                <form method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="groupes_utilisateurs">
                    <input type="hidden" name="id_gu" value="<?php echo $groupe_selectionne['id_gu']; ?>">
                    <div class="form-group">
                        <label for="lib_gu">Libellé :</label>
                        <input type="text" id="lib_gu" name="lib_gu" value="<?php echo htmlspecialchars($groupe_selectionne['lib_gu']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Traitements :</label>
                        <div class="traitements-checkboxes">
                            <?php
                            $id_traitements = explode(',', $groupe_selectionne['id_traitements'] ?? '');
                            foreach ($traitements as $traitement):
                            ?>
                                <div class="checkbox-item">
                                    <input type="checkbox"
                                        id="traitement_<?php echo $traitement['id_traitement']; ?>"
                                        name="traitements[]"
                                        value="<?php echo $traitement['id_traitement']; ?>"
                                        <?php echo in_array($traitement['id_traitement'], $id_traitements) ? 'checked' : ''; ?>>
                                    <label for="traitement_<?php echo $traitement['id_traitement']; ?>">
                                        <?php echo htmlspecialchars($traitement['nom_traitement']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button">Enregistrer</button>
                        <a href="?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="button">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($action === 'add'): ?>
        <!-- Modal pour ajouter un groupe -->
        <div class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="window.location.href='?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>'">&times;</span>
                <h2>Ajouter un groupe d'utilisateur</h2>
                <form method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="groupes_utilisateurs">
                    <div class="form-group">
                        <label for="lib_gu">Libellé :</label>
                        <input type="text" id="lib_gu" name="lib_gu" required>
                    </div>
                    <div class="form-group">
                        <label>Traitements :</label>
                        <div class="traitements-checkboxes">
                            <?php foreach ($traitements as $traitement): ?>
                                <div class="checkbox-item">
                                    <input type="checkbox"
                                        id="traitement_<?php echo $traitement['id_traitement']; ?>"
                                        name="traitements[]"
                                        value="<?php echo $traitement['id_traitement']; ?>">
                                    <label for="traitement_<?php echo $traitement['id_traitement']; ?>">
                                        <?php echo htmlspecialchars($traitement['nom_traitement']); ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="button">Enregistrer</button>
                        <a href="?page=parametres_generaux&liste=groupes_utilisateurs&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" class="button">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

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
        // Fonction pour recharger la sidebar
        function reloadSidebar() {
            // Récupérer la sidebar depuis le parent
            const sidebar = window.parent.document.querySelector('.sidebar');
            if (sidebar) {
                // Recharger la page parent pour mettre à jour la sidebar
                window.parent.location.reload();
            }
        }

        // Vérifier si une action a été effectuée
        <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
            // Recharger la sidebar après une action réussie ou échouée
            reloadSidebar();
        <?php endif; ?>

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
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins un groupe à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> groupe(s) sélectionné(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
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