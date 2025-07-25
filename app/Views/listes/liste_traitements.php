<?php
require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// Paramètres de recherche et pagination
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$traitements_par_page = 10;

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['lib_traitement'])) {
        $lib_traitement = $_POST['lib_traitement'];
        $nom_traitement = $_POST['nom_traitement'];
        $classe_icone = $_POST['classe_icone'];

        if (isset($_POST['id_traitement']) && !empty($_POST['id_traitement'])) {
            // Modification d'un traitement existant
            $id_traitement = $_POST['id_traitement'];
            $sql = "UPDATE traitement SET lib_traitement = ?, nom_traitement = ?, classe_icone = ? WHERE id_traitement = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_traitement, $nom_traitement, $classe_icone, $id_traitement]);
            $_SESSION['success'] = "Traitement modifié avec succès.";
        } else {
            // Ajout d'un nouveau traitement
            $sql = "INSERT INTO traitement (lib_traitement, nom_traitement, classe_icone) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$lib_traitement, $nom_traitement, $classe_icone]);
            $_SESSION['success'] = "Traitement ajouté avec succès.";
        }

        // Mise à jour des permissions
        updateUserPermissions();

        // Redirection avec conservation des paramètres
        $redirect_url = $_SERVER['PHP_SELF'];
        if (!empty($search)) $redirect_url .= "?search=" . urlencode($search);
        if ($page > 1) $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . "page=$page";

        header('Location: ' . $redirect_url);
        exit;
    }

    // Attribution d'un traitement à un groupe
    if (isset($_POST['attribuer_traitement'])) {
        $id_traitement = $_POST['id_traitement'];
        $id_groupe = $_POST['id_groupe'];

        try {
            $sql = "INSERT INTO rattacher (id_gu, id_traitement) VALUES (?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_groupe, $id_traitement]);
            $_SESSION['success'] = "Traitement attribué avec succès au groupe.";

            // Mise à jour des permissions
            updateUserPermissions();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de l'attribution du traitement.";
        }

        // Redirection avec conservation des paramètres
        $redirect_url = $_SERVER['PHP_SELF'];
        if (!empty($search)) $redirect_url .= "?search=" . urlencode($search);
        if ($page > 1) $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . "page=$page";

        header('Location: ' . $redirect_url);
        exit;
    }

    // Retrait d'un traitement d'un groupe
    if (isset($_POST['retirer_traitement'])) {
        $id_traitement = $_POST['id_traitement'];
        $id_groupe = $_POST['id_groupe'];

        try {
            $sql = "DELETE FROM rattacher WHERE id_gu = ? AND id_traitement = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_groupe, $id_traitement]);
            $_SESSION['success'] = "Traitement retiré avec succès du groupe.";

            // Mise à jour des permissions
            updateUserPermissions();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors du retrait du traitement.";
        }

        // Redirection avec conservation des paramètres
        $redirect_url = $_SERVER['PHP_SELF'];
        if (!empty($search)) $redirect_url .= "?search=" . urlencode($search);
        if ($page > 1) $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . "page=$page";

        header('Location: ' . $redirect_url);
        exit;
    }

    // Suppression d'un traitement
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id_traitement = (int)$_GET['id'];

        try {
            // Supprimer d'abord les associations dans la table rattacher
            $sql = "DELETE FROM rattacher WHERE id_traitement = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_traitement]);

            // Puis supprimer le traitement
            $sql = "DELETE FROM traitement WHERE id_traitement = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_traitement]);

            $_SESSION['success'] = "Traitement supprimé avec succès.";

            // Mise à jour des permissions
            updateUserPermissions();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression du traitement.";
        }

        // Redirection avec conservation des paramètres
        $redirect_url = $_SERVER['PHP_SELF'];
        if (!empty($search)) $redirect_url .= "?search=" . urlencode($search);
        if ($page > 1) $redirect_url .= (strpos($redirect_url, '?') !== false ? '&' : '?') . "page=$page";

        header('Location: ' . $redirect_url);
        exit;
    }

    // Suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM traitement WHERE id_traitement IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success'] = count($ids) . " traitement(s) supprimé(s) avec succès.";
        } else {
            $_SESSION['error'] = "Aucun traitement sélectionné.";
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
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
$id_traitement = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Construction de la requête avec recherche
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(t.lib_traitement LIKE ? OR t.nom_traitement LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Comptage total pour pagination
$count_sql = "SELECT COUNT(*) FROM traitement t $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_traitements = $count_stmt->fetchColumn();

$total_pages = ceil($total_traitements / $traitements_par_page);
$offset = ($page - 1) * $traitements_par_page;

// Récupération des traitements avec leurs groupes associés et pagination
$sql = "SELECT t.*, 
           GROUP_CONCAT(DISTINCT g.lib_gu) as groupes_associes, 
           GROUP_CONCAT(DISTINCT g.id_gu) as id_groupes,
           COUNT(DISTINCT r.id_gu) as nombre_groupes
    FROM traitement t
    LEFT JOIN rattacher r ON t.id_traitement = r.id_traitement
    LEFT JOIN groupe_utilisateur g ON r.id_gu = g.id_gu
    $where_clause
    GROUP BY t.id_traitement
    ORDER BY t.id_traitement
    LIMIT $traitements_par_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$traitements_page = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des groupes d'utilisateurs
$groupes = $pdo->query("SELECT * FROM groupe_utilisateur ORDER BY lib_gu")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des informations du traitement sélectionné si nécessaire
$traitement_selectionne = null;
if ($id_traitement > 0) {
    foreach ($traitements_page as $traitement) {
        if ($traitement['id_traitement'] == $id_traitement) {
            $traitement_selectionne = $traitement;
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
    <title>Liste des Traitements - Tableau de Bord Commission</title>
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
            width: 50%;
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

        /* Ajoutez les styles pour le bouton de retrait */
        .action-button.remove-button {
            background-color: rgba(212, 106, 117, 0.84);
        }

        .action-button.remove-button:hover {
            background-color: #c82333;
            color: #fff;
        }

        /* Styles pour les détails du traitement */
        .traitement-details {
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

        .groupes-list {
            margin-top: 10px;
        }

        .groupes-list .groupe-item {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .groupes-list .groupe-item i {
            color: #28a745;
        }

        /* Style pour l'aperçu de l'icône */
        #viewIconeTraitement i {
            font-size: 1.2em;
            margin-right: 5px;
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
                <h1>Liste des Traitements</h1>
                <p>Gestion des traitements du système</p>
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
                <input type="text" name="search" placeholder="Rechercher un traitement..." value="<?php echo htmlspecialchars($search); ?>">
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
            <a href="?action=add<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>" class="button">
                <i class="fas fa-plus"></i> Ajouter un traitement
            </a>
            <a href="?action=assign<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>" class="button">
                <i class="fas fa-user-plus"></i> Attribuer un traitement
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

        <form id="multipleDeleteForm" method="POST">
            <div class="data-table-container">
                <div class="data-table-header">
                    <div class="data-table-title">Liste des traitements (<?php echo $total_traitements; ?> résultat<?php echo $total_traitements > 1 ? 's' : ''; ?>)</div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                            <th style="width: 50px;">ID</th>
                            <th>Libellé</th>
                            <th>Statut</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($traitements_page)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #666;">
                                    <?php echo empty($search) ? 'Aucun traitement trouvé.' : 'Aucun résultat pour "' . htmlspecialchars($search) . '".'; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($traitements_page as $traitement): ?>
                                <tr>
                                    <td><input type="checkbox" class="row-checkbox" name="delete_selected_ids[]" value="<?php echo $traitement['id_traitement']; ?>"></td>
                                    <td><?php echo htmlspecialchars($traitement['id_traitement']); ?></td>
                                    <td><?php echo htmlspecialchars($traitement['nom_traitement']); ?></td>
                                    <td><span class="status-badge status-active">Actif</span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?php echo $traitement['id_traitement']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>" class="action-button view-button" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $traitement['id_traitement']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>" class="action-button edit-button" title="Modifier">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $traitement['id_traitement']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>" class="action-button delete-button" title="Supprimer" onclick="deleteTraitement(<?php echo $traitement['id_traitement']; ?>, '<?php echo htmlspecialchars($traitement['nom_traitement'], ENT_QUOTES); ?>'); return false;">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="?action=remove&id=<?php echo $traitement['id_traitement']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>" class="action-button remove-button" title="Retirer">
                                                <i class="fas fa-user-minus"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $action ? '&action=' . $action : ''; ?><?php echo $id_traitement ? '&id=' . $id_traitement : ''; ?>" class="page-item">«</a>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1): ?>
                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $action ? '&action=' . $action : ''; ?><?php echo $id_traitement ? '&id=' . $id_traitement : ''; ?>" class="page-item">1</a>
                    <?php if ($start_page > 2): ?>
                        <span class="page-item">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $action ? '&action=' . $action : ''; ?><?php echo $id_traitement ? '&id=' . $id_traitement : ''; ?>" class="page-item <?php echo $page == $i ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="page-item">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $action ? '&action=' . $action : ''; ?><?php echo $id_traitement ? '&id=' . $id_traitement : ''; ?>" class="page-item"><?php echo $total_pages; ?></a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $action ? '&action=' . $action : ''; ?><?php echo $id_traitement ? '&id=' . $id_traitement : ''; ?>" class="page-item">»</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal pour afficher les détails d'un traitement -->
    <?php if ($action === 'view' && $traitement_selectionne): ?>
        <div class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>'">&times;</span>
                <h2>Détails du traitement : <?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?></h2>
                <div class="traitement-details">
                    <div class="detail-group">
                        <label>Libellé :</label>
                        <span><?php echo htmlspecialchars($traitement_selectionne['lib_traitement']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Nom :</label>
                        <span><?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?></span>
                    </div>
                    <div class="detail-group">
                        <label>Icône :</label>
                        <span id="viewIconeTraitement">
                            <i class="<?php echo htmlspecialchars($traitement_selectionne['classe_icone']); ?>"></i>
                            <?php echo htmlspecialchars($traitement_selectionne['classe_icone']); ?>
                        </span>
                    </div>
                    <div class="detail-group">
                        <label>Groupes associés :</label>
                        <div class="groupes-list">
                            <?php if ($traitement_selectionne['groupes_associes']): ?>
                                <?php foreach (explode(',', $traitement_selectionne['groupes_associes']) as $groupe): ?>
                                    <div class="groupe-item">
                                        <i class="fas fa-users"></i> <?php echo htmlspecialchars($groupe); ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Aucun groupe associé</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="detail-group">
                        <label>Nombre de groupes :</label>
                        <span><?php echo $traitement_selectionne['nombre_groupes']; ?></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>" class="button">Fermer</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour modifier un traitement -->
    <?php if ($action === 'edit' && $traitement_selectionne): ?>
        <div class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>'">&times;</span>
                <h2>Modifier le traitement : <?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?></h2>
                <form method="POST">
                    <input type="hidden" name="id_traitement" value="<?php echo $traitement_selectionne['id_traitement']; ?>">
                    <div class="form-group">
                        <label for="lib_traitement">Libellé :</label>
                        <input type="text" id="lib_traitement" name="lib_traitement" value="<?php echo htmlspecialchars($traitement_selectionne['lib_traitement']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="nom_traitement">Nom :</label>
                        <input type="text" id="nom_traitement" name="nom_traitement" value="<?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="classe_icone">Classe de l'icône :</label>
                        <input type="text" id="classe_icone" name="classe_icone" value="<?php echo htmlspecialchars($traitement_selectionne['classe_icone']); ?>" required placeholder="ex: fas fa-home">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="button">Enregistrer</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>" class="button secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour ajouter un traitement -->
    <?php if ($action === 'add'): ?>
        <div class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>'">&times;</span>
                <h2>Ajouter un traitement</h2>
                <form method="POST">
                    <div class="form-group">
                        <label for="lib_traitement">Libellé :</label>
                        <input type="text" id="lib_traitement" name="lib_traitement" required>
                    </div>
                    <div class="form-group">
                        <label for="nom_traitement">Nom :</label>
                        <input type="text" id="nom_traitement" name="nom_traitement" required>
                    </div>
                    <div class="form-group">
                        <label for="classe_icone">Classe de l'icône :</label>
                        <input type="text" id="classe_icone" name="classe_icone" required placeholder="ex: fas fa-home">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="button">Enregistrer</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>" class="button secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour attribuer un traitement -->
    <?php if ($action === 'assign'): ?>
        <div class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>'">&times;</span>
                <h2>Attribuer un traitement</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Traitement :</label>
                        <select name="id_traitement" required>
                            <option value="">Sélectionnez un traitement</option>
                            <?php foreach ($traitements_page as $traitement): ?>
                                <option value="<?php echo $traitement['id_traitement']; ?>">
                                    <?php echo htmlspecialchars($traitement['nom_traitement']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Groupe d'utilisateurs :</label>
                        <select name="id_groupe" required>
                            <option value="">Sélectionnez un groupe</option>
                            <?php foreach ($groupes as $groupe): ?>
                                <option value="<?php echo $groupe['id_gu']; ?>">
                                    <?php echo htmlspecialchars($groupe['lib_gu']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="attribuer_traitement" class="button">Attribuer au groupe</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>" class="button secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal pour retirer un traitement -->
    <?php if ($action === 'remove' && $traitement_selectionne): ?>
        <div class="modal" style="display: block;">
            <div class="modal-content">
                <span class="close" onclick="window.location.href='<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>'">&times;</span>
                <h2>Retirer le traitement : <?php echo htmlspecialchars($traitement_selectionne['nom_traitement']); ?></h2>
                <form method="POST">
                    <input type="hidden" name="id_traitement" value="<?php echo $traitement_selectionne['id_traitement']; ?>">
                    <div class="form-group">
                        <label>Groupe d'utilisateurs :</label>
                        <select name="id_groupe" required>
                            <option value="">Sélectionnez un groupe</option>
                            <?php
                            $id_groupes = explode(',', $traitement_selectionne['id_groupes'] ?? '');
                            foreach ($groupes as $groupe):
                                if (in_array($groupe['id_gu'], $id_groupes)):
                            ?>
                                    <option value="<?php echo $groupe['id_gu']; ?>">
                                        <?php echo htmlspecialchars($groupe['lib_gu']); ?>
                                    </option>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="retirer_traitement" class="button">Retirer du groupe</button>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?><?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? (strpos($_SERVER['PHP_SELF'], '?') !== false ? '&' : '?') . 'page=' . $page : ''; ?>" class="button secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal de confirmation de suppression (simple) harmonisée -->
    <div id="confirmation-modal-single" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <div class="modal-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <h2>Confirmer la suppression</h2>
            <p id="deleteSingleMessage">Êtes-vous sûr de vouloir supprimer ce traitement ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span></p>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeDeleteModal()">Annuler</button>
                <a href="#" id="confirmDeleteBtn" class="button">Supprimer</a>
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

        // Fonction pour supprimer un traitement
        function deleteTraitement(id_traitement, nom_traitement) {
            document.getElementById('deleteSingleMessage').innerHTML = `Êtes-vous sûr de vouloir supprimer le traitement <b>"${nom_traitement}"</b> ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
            document.getElementById('confirmDeleteBtn').href = '?action=delete&id=' + id_traitement + '<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo $page > 1 ? '&page=' . $page : ''; ?>';
            document.getElementById('confirmation-modal-single').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').style.display = 'none';
        }

        // Fermer les modales si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('deleteModal')) {
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
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins un traitement à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> traitement(s) sélectionné(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
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