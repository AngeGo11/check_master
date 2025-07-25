<?php
require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_ue = isset($_GET['filter_ue']) ? trim($_GET['filter_ue']) : '';
$where = [];
$params = [];

// Recherche texte
if ($search !== '') {
    $where[] = "(e.lib_ecue LIKE :search OR e.id_ecue LIKE :search)";
    $params[':search'] = "%$search%";
}

// Filtre par UE
if ($filter_ue !== '') {
    $where[] = "e.id_ue = :filter_ue";
    $params[':filter_ue'] = $filter_ue;
}

$where_sql = '';
if (count($where) > 0) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// Pagination
$perPage = 7;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

// Compte total pour la pagination
$countSql = "SELECT COUNT(*) FROM ecue e LEFT JOIN ue u ON e.id_ue = u.id_ue $where_sql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalEcues = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalEcues / $perPage));

// Récupération paginée
$sql = "SELECT e.*, ens.nom_ens, ens.prenoms_ens, u.lib_ue FROM ecue e LEFT JOIN enseignants ens ON ens.id_ens = e.id_ens LEFT JOIN ue u ON e.id_ue = u.id_ue $where_sql ORDER BY e.id_ecue LIMIT $offset, $perPage";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$ecues = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des UE pour le select
$ues = $pdo->query("SELECT * FROM ue ORDER BY id_ue")->fetchAll(PDO::FETCH_ASSOC);

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Traitement pour l'ajout d'une ECUE
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        try {
            if (empty($_POST['lib_ue']) || empty($_POST['lib_ecue']) || empty($_POST['credit_ecue']) || empty($_POST['volume_horaire_ecue'])) {
                throw new Exception("Tous les champs sont obligatoires.");
            }

            $id_ue = $_POST['lib_ue'];
            $lib_ecue = $_POST['lib_ecue'];
            $credit_ecue = $_POST['credit_ecue'];
            $volume_horaire_ecue = $_POST['volume_horaire_ecue'];
            $id_ens = !empty($_POST['id_ens']) ? intval($_POST['id_ens']) : null;

            // Génération du code ECUE
            $code_ecue = genererCodeECUEUnique($pdo, $id_ue);

            $sql = "INSERT INTO ecue (id_ecue, lib_ecue, credit_ecue, volume_horaire, id_ue, id_ens) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$code_ecue, $lib_ecue, $credit_ecue, $volume_horaire_ecue, $id_ue, $id_ens]);

            if ($success) {
                $_SESSION['success_message'] = "L'ECUE a été enregistrée avec succès.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de l'enregistrement.";
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }

    // Traitement pour la modification d'une ECUE
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        try {
            if (
                empty($_POST['id_ecue']) || empty($_POST['lib_ue']) || empty($_POST['lib_ecue']) ||
                empty($_POST['credit_ecue']) || empty($_POST['volume_horaire_ecue'])
            ) {
                throw new Exception("Tous les champs sont obligatoires.");
            }

            $id_ecue = $_POST['id_ecue'];
            $id_ue = $_POST['lib_ue'];
            $lib_ecue = $_POST['lib_ecue'];
            $credit_ecue = $_POST['credit_ecue'];
            $volume_horaire_ecue = $_POST['volume_horaire_ecue'];
            $id_ens = !empty($_POST['id_ens']) ? intval($_POST['id_ens']) : null;


            $sql = "UPDATE ecue SET lib_ecue = ?, credit_ecue = ?, volume_horaire = ?, id_ue = ?, id_ens = ? WHERE id_ecue = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$lib_ecue, $credit_ecue, $volume_horaire_ecue, $id_ue, $id_ens, $id_ecue]);

            if ($success) {
                $_SESSION['success_message'] = "L'ECUE a été modifiée avec succès.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la modification.";
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }

    // Traitement pour la suppression d'une ECUE
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        try {
            if (empty($_POST['id_ecue'])) {
                throw new Exception("ID de l'ECUE manquant.");
            }

            $id_ecue = $_POST['id_ecue'];

            // Vérifier s'il y a des dépendances (par exemple des notes, inscriptions, etc.)
            $checkSql = "SELECT COUNT(*) FROM evaluer_ecue WHERE id_ecue = ?";
            $checkStmt = $pdo->prepare($checkSql);
            $checkStmt->execute([$id_ecue]);
            $hasNotes = $checkStmt->fetchColumn() > 0;

            if ($hasNotes) {
                throw new Exception("Impossible de supprimer cette ECUE car elle contient des notes.");
            }

            $sql = "DELETE FROM ecue WHERE id_ecue = ?";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([$id_ecue]);

            if ($success) {
                $_SESSION['success_message'] = "L'ECUE a été supprimée avec succès.";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la suppression.";
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
        }
    }

    // Traitement pour la suppression multiple
    if (isset($_POST['delete_selected_ids'])) {
        $ids = array_filter(explode(',', $_POST['delete_selected_ids']), 'strlen');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM ecue WHERE id_ecue IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success_message'] = count($ids) . " ECUE supprimée(s) avec succès.";
        } else {
            $_SESSION['error_message'] = "Aucune ECUE sélectionnée.";
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des ECUE</title>
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
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.6);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(2px);
        }

        .modal[style*="display: block"] {
            display: flex !important;
        }

        .modal-content {
            position: relative;
            background: linear-gradient(145deg, #ffffff, #fdfdfd);
            margin: 0;
            padding: 32px;
            width: min(90vw, 800px);
            max-height: 90vh;
            border-radius: 16px;
            box-shadow:
                0 10px 40px rgba(0, 0, 0, 0.15),
                0 4px 12px rgba(0, 0, 0, 0.1);
            animation: modalAppear 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow-y: auto;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        @keyframes modalAppear {
            0% {
                opacity: 0;
                transform: scale(0.8) translateY(-20px);
            }

            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 2px solid #f5f6fa;
            position: relative;
        }

        .modal-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #1a5276, #3498db);
            border-radius: 2px;
        }

        .modal-header h2 {
            margin: 0;
            color: #1a202c;
            font-size: 1.9em;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .close {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 300;
        }

        .close:hover {
            background: #fee2e2;
            color: #dc2626;
            border-color: rgba(220, 38, 38, 0.2);
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-weight: 600;
            font-size: 0.95em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #1a5276;
            outline: none;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(26, 82, 118, 0.1);
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .modal-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f5f6fa;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .button {
            background: linear-gradient(135deg, #1a5276, #2980b9);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 14px 24px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 0.95em;
            letter-spacing: 0.3px;
            position: relative;
            overflow: hidden;
        }

        .button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .button:hover::before {
            left: 100%;
        }

        .button.secondary {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            color: #1a5276;
            border: 2px solid #cbd5e1;
        }

        .button.secondary:hover {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        .button.danger {
            background: linear-gradient(135deg, #dc2626, #ef4444);
            color: white;
        }

        .button.danger:hover {
            background: linear-gradient(135deg, #b91c1c, #dc2626);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.3);
        }

        .button:hover {
            background: linear-gradient(135deg, #154360, #1f618d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 82, 118, 0.3);
        }

        .button:active {
            transform: translateY(0);
        }

        /* Styles pour la modale de visualisation */
        .modal-body {
            margin: 0 0 12px 0;
            color: #2d3748;
            font-size: 1.1em;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 22px 28px;
            line-height: 1.6;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
            padding: 18px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-radius: 12px;
            border-left: 4px solid #1a5276;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .detail-group::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(26, 82, 118, 0.3), transparent);
        }

        .detail-group:hover {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 82, 118, 0.1);
        }

        .detail-group label {
            color: #475569;
            font-weight: 600;
            font-size: 0.95em;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85em;
        }

        .detail-group span {
            color: #1e293b;
            font-weight: 500;
            font-size: 1.05em;
            word-break: break-word;
            padding: 4px 0;
        }

        /* Media queries */
        @media (max-width: 768px) {
            .modal-content {
                padding: 20px;
                width: 95vw;
                margin: 2.5vh auto;
                border-radius: 12px;
            }

            .modal-header h2 {
                font-size: 1.5em;
            }

            .modal-body {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .detail-group {
                padding: 14px;
            }

            .modal-footer {
                flex-direction: column-reverse;
                gap: 8px;
            }

            .button {
                width: 100%;
                justify-content: center;
                padding: 16px 20px;
            }
        }

        /* Style pour la confirmation de suppression */
        .delete-confirmation {
            text-align: center;
            padding: 20px;
        }

        .delete-confirmation .warning-icon {
            font-size: 64px;
            color: #ef4444;
            margin-bottom: 20px;
        }

        .delete-confirmation h3 {
            color: #1f2937;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .delete-confirmation p {
            color: #6b7280;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        .ecue-info {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }

        .ecue-info strong {
            color: #dc2626;
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
                <h1>Liste des ECUE</h1>
                <p>Gestion des éléments constitutifs des unités d'enseignement</p>
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
            <form class="search-box" method="get" style="display:flex;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Rechercher une ECUE..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" style="display:none;">Rechercher</button>
            </form>
            <!-- Bouton de suppression multiple -->
            <form id="bulkDeleteForm" method="POST" style="display:inline; margin-right:10px;">
                <input type="hidden" name="bulk_delete" value="1">
                <button type="button" class="button danger" onclick="openDeleteMultipleModal()" id="bulkDeleteBtn"><i class="fas fa-trash"></i> Supprimer la sélection</button>
                <input type="hidden" name="delete_selected_ids" id="delete_selected_ids">
            </form>
            <button class="button" onclick="showAddModal()">
                <i class="fas fa-plus"></i>
                Ajouter une ECUE
            </button>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" style="position: fixed; top: 20px; right: 20px; z-index: 1000; background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb;">
                <?php
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button onclick="this.parentElement.remove()" style="margin-left: 10px; background: none; border: none; color: #155724; font-weight: bold; cursor: pointer;">×</button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger" style="position: fixed; top: 20px; right: 20px; z-index: 1000; background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; border: 1px solid #f5c6cb;">
                <?php
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button onclick="this.parentElement.remove()" style="margin-left: 10px; background: none; border: none; color: #721c24; font-weight: bold; cursor: pointer;">×</button>
            </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="filters">
            <form method="get">
                <select class="filter-select" name="filter_ue" onchange="this.form.submit()">
                    <option value="">Toutes les UE</option>
                    <?php foreach ($ues as $ue): ?>
                        <option value="<?php echo $ue['id_ue']; ?>" <?php if ($filter_ue == $ue['id_ue']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($ue['lib_ue']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>



        <!-- Table de données -->
        <div class="data-table-container">
            <div class="data-table-header">
                <div class="data-table-title">Liste des ECUE</div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                        <th style="width: 50px;">ID</th>
                        <th>Libellé ECUE</th>
                        <th>UE</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ecues as $ecue): ?>
                        <tr>
                            <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($ecue['id_ecue']); ?>"></td>
                            <td><?php echo $ecue['id_ecue']; ?></td>
                            <td><?php echo $ecue['lib_ecue']; ?></td>
                            <td><?php echo $ecue['lib_ue']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-button view-button" title="Voir"
                                        onclick="viewEcue(
                                            '<?php echo $ecue['id_ecue']; ?>',
                                            '<?php echo addslashes($ecue['lib_ecue']); ?>',
                                            '<?php echo addslashes($ecue['nom_ens'] . ' ' . $ecue['prenoms_ens']); ?>',
                                            '<?php echo $ecue['credit_ecue']; ?>',
                                            '<?php echo $ecue['volume_horaire']; ?>',
                                            '<?php echo addslashes($ecue['lib_ue']); ?>'
                                        )">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-button edit-button" title="Modifier" onclick="editEcue('<?php echo $ecue['id_ecue']; ?>', '<?php echo addslashes($ecue['lib_ecue']); ?>', '<?php echo $ecue['id_ens']; ?>', '<?php echo $ecue['id_ue']; ?>', '<?php echo $ecue['credit_ecue']; ?>', '<?php echo $ecue['volume_horaire']; ?>')">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="action-button delete-button" title="Supprimer" onclick="confirmDeleteEcue('<?php echo $ecue['id_ecue']; ?>', '<?php echo addslashes($ecue['lib_ecue']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="page-item" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">«</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="page-item<?php if ($i == $page) echo ' active'; ?>" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a class="page-item" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">»</a>
            <?php endif; ?>
        </div>
    </div>


    <!-- Modal pour ajouter une ECUE -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ajouter une ECUE</h2>
                <button class="close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="add_lib_ecue">Libellé ECUE</label>
                        <input type="text" id="add_lib_ecue" name="lib_ecue" required>
                    </div>
                    <div class="form-group form-group-inline">
                        <label for="add_id_ens">Nom de l'enseignant :</label>
                        <select name="id_ens" id="add_id_ens">
                            <option value="">-- Sélectionnez un enseignant --</option>
                            <?php
                            $enseignants = $pdo->prepare("
                                SELECT e.id_ens, e.nom_ens, e.prenoms_ens
                                FROM enseignants e");
                            $enseignants->execute();
                            $enseignants_list = $enseignants->fetchAll();
                            foreach ($enseignants_list as $ens) {
                                echo "<option value=\"{$ens['id_ens']}\">{$ens['nom_ens']} {$ens['prenoms_ens']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="add_credit_ecue">Crédit ECUE</label>
                        <input type="number" id="add_credit_ecue" name="credit_ecue" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="add_volume_horaire_ecue">Volume horaire ECUE</label>
                        <input type="number" id="add_volume_horaire_ecue" name="volume_horaire_ecue" min="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="add_lib_ue">UE</label>
                    <select id="add_lib_ue" name="lib_ue" required>
                        <option value="">Sélectionner une UE</option>
                        <?php
                        $ues_list = $pdo->query("SELECT id_ue, lib_ue FROM ue ORDER BY lib_ue")->fetchAll();
                        foreach ($ues_list as $ue): ?>
                            <option value="<?php echo $ue['id_ue']; ?>"><?php echo htmlspecialchars($ue['lib_ue']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeModal('addModal')">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="button">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal pour voir les détails d'une ECUE -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Détails de l'ECUE</h2>
                <button class="close" onclick="closeModal('viewModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-group">
                    <label>Code ECUE</label>
                    <span id="view_id_ecue"></span>
                </div>
                <div class="detail-group">
                    <label>Chargé de cours</label>
                    <span id="view_ens_ecue"></span>
                </div>
                <div class="detail-group">
                    <label>Libellé ECUE</label>
                    <span id="view_lib_ecue"></span>
                </div>
                <div class="detail-group">
                    <label>Unité d'Enseignement</label>
                    <span id="view_lib_ue"></span>
                </div>
                <div class="detail-group">
                    <label>Crédit ECUE</label>
                    <span id="view_credit_ecue"></span>
                </div>
                <div class="detail-group">
                    <label>Volume Horaire</label>
                    <span id="view_volume_horaire"></span>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeModal('viewModal')">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal pour modifier une ECUE -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Modifier l'ECUE</h2>
                <button class="close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id_ecue" name="id_ecue">

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_lib_ecue">Libellé ECUE</label>
                        <input type="text" id="edit_lib_ecue" name="lib_ecue" required>
                    </div>
                    <div class="form-group form-group-inline">
                        <label for="edit_id_ens">Nom de l'enseignant :</label>
                        <select name="id_ens" id="edit_id_ens">
                            <option value="">-- Sélectionnez un enseignant --</option>
                            <?php
                            $enseignants = $pdo->prepare("
                                SELECT e.id_ens, e.nom_ens, e.prenoms_ens
                                FROM enseignants e");
                            $enseignants->execute();
                            $enseignants_list = $enseignants->fetchAll();
                            foreach ($enseignants_list as $ens) {
                                echo "<option value=\"{$ens['id_ens']}\">{$ens['nom_ens']} {$ens['prenoms_ens']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>


                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_credit_ecue">Crédit ECUE</label>
                        <input type="number" id="edit_credit_ecue" name="credit_ecue" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="edit_volume_horaire_ecue">Volume horaire ECUE</label>
                        <input type="number" id="edit_volume_horaire_ecue" name="volume_horaire_ecue" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="edit_lib_ue">UE</label>
                    <select id="edit_lib_ue" name="lib_ue" required>
                        <option value="">Sélectionner une UE</option>
                        <?php
                        foreach ($ues_list as $ue): ?>
                            <option value="<?php echo $ue['id_ue']; ?>"><?php echo htmlspecialchars($ue['lib_ue']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeModal('editModal')">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="button">
                        <i class="fas fa-save"></i> Modifier
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirmation de suppression</h2>
                <button class="close" onclick="closeModal('deleteModal')">&times;</button>
            </div>
            <div class="delete-confirmation">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Êtes-vous sûr de vouloir supprimer cette ECUE ?</h3>
                <p>Cette action est irréversible. Toutes les données associées à cette ECUE seront définitivement perdues.</p>

                <div class="ecue-info">
                    <strong>ECUE à supprimer :</strong><br>
                    <span id="delete_ecue_info"></span>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id_ecue" name="id_ecue">
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeModal('deleteModal')">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="button danger">
                        <i class="fas fa-trash"></i> Supprimer définitivement
                    </button>
                </div>
            </form>
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
        // Fonctions pour la gestion des modales
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function viewEcue(id, libelle, nom_ens, credit, volumeHoraire, libUe) {
            document.getElementById('view_id_ecue').textContent = id;
            document.getElementById('view_lib_ecue').textContent = libelle;
            let enseignant = 'Non renseigné';
            if (nom_ens && nom_ens.trim() !== '' && nom_ens.trim() !== ' ') {
                enseignant = nom_ens;
            }
            document.getElementById('view_ens_ecue').textContent = enseignant;

            document.getElementById('view_lib_ue').textContent = libUe;
            document.getElementById('view_credit_ecue').textContent = credit + ' crédits';
            document.getElementById('view_volume_horaire').textContent = volumeHoraire + ' heures';
            document.getElementById('viewModal').style.display = 'block';
        }

        // Suppression multiple
        function openDeleteMultipleModal() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const msg = document.getElementById('deleteMultipleMessage');
            const footer = document.getElementById('deleteMultipleFooter');
            if (checked.length === 0) {
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins une ECUE à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> ECUE sélectionnée(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
                footer.innerHTML = '<button type="button" class="button" onclick="confirmDeleteMultiple()">Oui, supprimer</button>' +
                    '<button type="button" class="button secondary" onclick="closeDeleteMultipleModal()">Non</button>';
            }
            document.getElementById('confirmation-modal').style.display = 'flex';
        }

        function closeDeleteMultipleModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
        }

        function confirmDeleteMultiple() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            document.getElementById('delete_selected_ids').value = Array.from(checked).map(cb => cb.value).join(',');
            document.getElementById('bulkDeleteForm').submit();
        }

        function editEcue(id, libelle, id_ens, idUe, credit, volumeHoraire) {
            document.getElementById('edit_id_ecue').value = id;
            document.getElementById('edit_lib_ecue').value = libelle;
            document.getElementById('edit_id_ens').value = id_ens;
            document.getElementById('edit_credit_ecue').value = credit;
            document.getElementById('edit_volume_horaire_ecue').value = volumeHoraire;
            document.getElementById('edit_lib_ue').value = idUe;
            document.getElementById('editModal').style.display = 'block';
        }

        function confirmDeleteEcue(id, libelle) {
            document.getElementById('delete_id_ecue').value = id;
            document.getElementById('delete_ecue_info').textContent = id + ' - ' + libelle;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';

            // Réinitialiser les formulaires
            if (modalId === 'addModal') {
                document.querySelector('#addModal form').reset();
            } else if (modalId === 'editModal') {
                document.querySelector('#editModal form').reset();
            }
        }

        // Fermer les modales en cliquant en dehors
        window.addEventListener('click', function(event) {
            const modals = ['addModal', 'viewModal', 'editModal', 'deleteModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    closeModal(modalId);
                }
            });
        });

        // Empêcher la fermeture lors du clic sur le contenu de la modale
        document.querySelectorAll('.modal-content').forEach(content => {
            content.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        });

        // Animation pour fermer automatiquement les alertes
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.style.opacity = '0';
                        alert.style.transform = 'translateX(100%)';
                        setTimeout(() => {
                            alert.remove();
                        }, 300);
                    }
                }, 5000);
            });
        });

        // Validation des formulaires
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(event) {
                const requiredFields = form.querySelectorAll('[required]');
                let hasError = false;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#ef4444';
                        hasError = true;
                    } else {
                        field.style.borderColor = '#e2e8f0';
                    }
                });

                if (hasError) {
                    event.preventDefault();
                    alert('Veuillez remplir tous les champs obligatoires.');
                }
            });
        });

        // Améliorer l'UX des champs de formulaire
        document.querySelectorAll('input, select, textarea').forEach(field => {
            field.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });

            field.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Sélection/désélection tout
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.row-checkbox');
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>

</html>