<?php
require_once __DIR__ . '/../../config/config.php';


$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];

$perPage = 8; // Nombre d'éléments par page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

if ($search !== '') {
    $where = "WHERE lib_ue LIKE :search OR id_ue LIKE :search";
    $params[':search'] = "%$search%";
}

// Récupération des UE
$sql = "SELECT * FROM ue $where ORDER BY id_ue LIMIT $offset, $perPage";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->execute();
$ues = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countSql = "SELECT COUNT(*) FROM ue $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalUes = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalUes / $perPage));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement des actions
    $lib_ue = $_POST['lib_ue'];
    $credit_ue = $_POST['credit_ue'];
    $volume_horaire = $_POST['volume_horaire'];
    $niveau = $_POST['niveau'];
    $semestre = $_POST['semestre'];
    $id_ens = !empty($_POST['id_ens']) ? intval($_POST['id_ens']) : null;

    // Génération du code UE unique
    try {
        $code_ue = genererCodeUEUnique($pdo, $niveau, $semestre);

        $sql = "INSERT INTO ue (id_ue, lib_ue, credit_ue, volume_horaire, id_niv_etd, id_semestre, id_ens) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$code_ue, $lib_ue, $credit_ue, $volume_horaire, $niveau, $semestre, $id_ens]);
        $_SESSION['success_message'] = "UE ajoutée avec succès.";
      

        // Redirection pour éviter la soumission multiple
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $error_message = "Erreur lors de la génération du code UE : " . $e->getMessage();
    }

    // Ajout du traitement PHP pour la suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM ue WHERE id_ue IN ($placeholders)");
            $stmt->execute($ids);
            $_SESSION['success'] = count($ids) . " UE supprimée(s) avec succès.";
        } else {
            $_SESSION['error'] = "Aucune UE sélectionnée.";
        }
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des UE</title>
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
            padding: 30px;
            width: 60%;
            max-width: 800px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease-in-out;
        }

        .form-group select option {
            color: #000000;
            background-color: #fff;
            padding: 12px;
            font-size: 1em;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.8em;
            font-weight: 600;
        }

        .close {
            background: none;
            border: none;
            font-size: 28px;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease;
            padding: 5px;
        }

        .close:hover {
            color: #e74c3c;
        }

        #ueForm {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 500;
            font-size: 0.95em;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
            outline: none;
            background-color: #fff;
        }

        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
            color: #000000;
        }

        .form-group select option {
            color: #000000;
            background-color: #ffffff;
            padding: 12px;
            font-size: 1em;
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
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            text-align: right;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .modal-footer button {
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-footer .button {
            background-color: #1a5276;
            color: white;
            border: none;
        }

        .modal-footer .button:hover {
            background-color: #1a5276db;
        }

        .modal-footer .button.secondary {
            background-color: #f8f9fa;
            color: #2c3e50;
            border: 1px solid #ddd;
        }

        .modal-footer .button.secondary:hover {
            background-color: #e9ecef;
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
                transform: translateY(-30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Message d'erreur */
        .error-message {
            color: #e74c3c;
            background-color: #fde8e8;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9em;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        /* Styles pour les détails */
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
            font-size: 1.1em;
        }

        /* Style pour le bouton de suppression */
        .button.delete {
            width: 100%;
            background-color: #e74c3c;
        }

        .button.delete:hover {
            background-color: #c0392b;
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

        .ue-info {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }

        .ue-info strong {
            color: #dc2626;
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

        @keyframes popIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Style amélioré pour la modale de détails */
        #viewModal.modal {
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

        #viewModal[style*="display: block"] {
            display: flex !important;
        }

        #viewModal .modal-content {
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

        #viewModal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 2px solid #f5f6fa;
            position: relative;
        }

        #viewModal .modal-header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #1a5276, #3498db);
            border-radius: 2px;
        }

        #viewModal .modal-header h2 {
            margin: 0;
            color: #1a202c;
            font-size: 1.9em;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        #viewModal .close {
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

        #viewModal .close:hover {
            background: #fee2e2;
            color: #dc2626;
            border-color: rgba(220, 38, 38, 0.2);
            transform: rotate(90deg);
        }

        #viewModal .modal-body {
            margin: 0 0 12px 0;
            color: #2d3748;
            font-size: 1.1em;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 22px 28px;
            line-height: 1.6;
        }

        #viewModal .detail-group {
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

        #viewModal .detail-group::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(26, 82, 118, 0.3), transparent);
        }

        #viewModal .detail-group:hover {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 82, 118, 0.1);
        }

        #viewModal .detail-group label {
            color: #475569;
            font-weight: 600;
            font-size: 0.95em;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.85em;
        }

        #viewModal .detail-group span {
            color: #1e293b;
            font-weight: 500;
            font-size: 1.05em;
            word-break: break-word;
            padding: 4px 0;
        }

        #viewModal .modal-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f5f6fa;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        #viewModal .button {
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

        #viewModal .button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        #viewModal .button:hover::before {
            left: 100%;
        }

        #viewModal .button.secondary {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            color: #1a5276;
            border: 2px solid #cbd5e1;
        }

        #viewModal .button.secondary:hover {
            background: linear-gradient(135deg, #e2e8f0, #cbd5e1);
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        #viewModal .button:hover {
            background: linear-gradient(135deg, #154360, #1f618d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 82, 118, 0.3);
        }

        #viewModal .button:active {
            transform: translateY(0);
        }

        /* Animations et transitions fluides */
        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: scale(0.9) translateY(-30px);
            }

            100% {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        /* Media queries améliorées */
        @media (max-width: 768px) {
            #viewModal .modal-content {
                padding: 20px;
                width: 95vw;
                margin: 2.5vh auto;
                border-radius: 12px;
            }

            #viewModal .modal-header h2 {
                font-size: 1.5em;
            }

            #viewModal .modal-body {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            #viewModal .detail-group {
                padding: 14px;
            }

            #viewModal .modal-footer {
                flex-direction: column-reverse;
                gap: 8px;
            }

            #viewModal .button {
                width: 100%;
                justify-content: center;
                padding: 16px 20px;
            }
        }

        @media (max-width: 480px) {
            #viewModal .modal-content {
                padding: 16px;
                width: 98vw;
                margin: 1vh auto;
            }

            #viewModal .modal-header {
                margin-bottom: 20px;
                padding-bottom: 12px;
            }

            #viewModal .modal-header h2 {
                font-size: 1.3em;
            }

            #viewModal .close {
                width: 36px;
                height: 36px;
                font-size: 18px;
            }
        }


        .form-group-inline label {
            white-space: nowrap;
        }

        .form-group-inline select {
            flex: 1;
        }
    </style>
</head>

<body>
    <!-- En-tête -->
    <div class="header">
        <div class="header-title">
            <img src="/GSCV+/public/assets/images/logo_mi_sbg.png" alt="">
            <div>
                <h1>Liste des UE</h1>
                <p>Gestion des unités d'enseignement</p>
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
    <div class="content">
        <div class="actions-bar">
        <a href="?page=parametres_generaux" class="button back-to-params">
                <i class="fas fa-arrow-left"></i> Retour aux paramètres généraux
            </a>
            <form class="search-box" method="get" style="display:flex;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Rechercher une UE..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" style="display:none;">Rechercher</button>
            </form>
            <form id="deleteMultipleForm" method="POST" style="display:inline; margin-right:10px;">
                <input type="hidden" name="delete_multiple" value="1">
                <button type="button" class="button delete-multiple-btn" onclick="openDeleteMultipleModal()">
                    <i class="fas fa-trash"></i> Supprimer la sélection
                </button>
            </form>
            <button class="button" onclick="showAddModal()">
                <i class="fas fa-plus"></i> Ajouter une UE
            </button>
        </div>

        <!-- Messages de notification -->
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


        <!-- Table de données -->
        <div class="data-table-container">
            <div class="data-table-header">
                <div class="data-table-title">Liste des UE</div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                        <th>Code UE</th>
                        <th>Libellé UE</th>
                        <th style="width: 50px;">Crédit</th>
                        <th>Volume horaire</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ues as $ue): ?>
                        <tr>
                            <td><input type="checkbox" class="row-checkbox" name="delete_selected_ids[]" value="<?php echo $ue['id_ue']; ?>"></td>
                            <td><?php echo $ue['id_ue']; ?></td>
                            <td><?php echo $ue['lib_ue']; ?></td>
                            <td><?php echo $ue['credit_ue']; ?></td>
                            <td><?php echo $ue['volume_horaire']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-button view-button" title="Voir" onclick="showViewModal(<?php echo $ue['id_ue']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-button edit-button" title="Modifier" onclick="showEditModal(<?php echo $ue['id_ue']; ?>)">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button class="action-button delete-button" title="Supprimer" onclick="showDeleteModal(<?php echo $ue['id_ue']; ?>)">
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
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">«</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="page-item<?php if ($i == $page) echo ' active'; ?>" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">»</a>
            <?php endif; ?>
        </div>
    </div>


    <!-- Modal pour ajouter/modifier une UE -->
    <div id="ueModal" class="modal">
        <div class="modal-content" style="max-width: 1500px;">
            <div class="modal-header">
                <h2 id="modalTitle">Ajouter une UE</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <?php if (isset($error_message)): ?>
                <div class="error-message show"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form id="ueForm" method="POST">
                <input type="hidden" id="id_ue" name="id_ue">

                <div class="form-row">
                    <div class="form-group">
                        <label for="lib_ue">Libellé UE</label>
                        <input type="text" id="lib_ue" name="lib_ue" required placeholder="Entrez le libellé de l'UE">
                    </div>

                    <div class="form-group form-group-inline">
                        <label for="id_ens">Nom de l'enseignant <span style="color:red; font-weight:700;">(Seulement si l'ue ne dispose pas d'ecue) </span>:</label>
                        <select name="id_ens" id="id_ens">
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
                        <label for="niveau">Niveau</label>
                        <select id="niveau" name="niveau" required onchange="updateSemestres(this.value)">
                            <option value="">Sélectionnez un niveau</option>
                            <?php
                            $niveaux = $pdo->prepare('SELECT * FROM niveau_etude');
                            $niveaux->execute();
                            $niveaux_list = $niveaux->fetchAll();
                            foreach ($niveaux_list as $niv) {
                                echo "<option value=\"{$niv['id_niv_etd']}\">{$niv['lib_niv_etd']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="semestre">Semestre</label>
                        <select id="semestre" name="semestre" required>
                            <option value="">Sélectionnez un semestre</option>
                        </select>
                    </div>

                </div>


                <div class="form-row">

                    <div class="form-group">
                        <label for="credit_ue">Crédit UE</label>
                        <input type="number" id="credit_ue" name="credit_ue" required min="1" max="30" placeholder="Nombre de crédits">
                    </div>

                    <div class="form-group">
                        <label for="volume_horaire">Volume horaire</label>
                        <input type="number" id="volume_horaire" name="volume_horaire" required min="1" placeholder="Heures">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="button">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal pour voir les détails d'une UE -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Détails de l'UE</h2>
                <button class="close" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-group">
                    <label>Code UE</label>
                    <span id="view_code_ue"></span>
                </div>
                <div class="detail-group">
                    <label>Libellé UE</label>
                    <span id="view_lib_ue"></span>
                </div>
                <div class="detail-group">
                    <label>Chargé de cours</label>
                    <span id="view_ens_ue"></span>
                </div>
                <div class="detail-group">
                    <label>Crédit UE</label>
                    <span id="view_credit_ue"></span>
                </div>
                <div class="detail-group">
                    <label>Volume horaire</label>
                    <span id="view_volume_horaire"></span>
                </div>
                <div class="detail-group">
                    <label>Niveau</label>
                    <span id="view_niveau"></span>
                </div>
                <div class="detail-group">
                    <label>Semestre</label>
                    <span id="view_semestre"></span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeViewModal()">Fermer</button>
            </div>
        </div>
    </div>

    <!-- Modal pour modifier une UE -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Modifier l'UE</h2>
                <button class="close" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" method="POST" action="../assets/traitements/modifier_ue.php">
                <input type="hidden" id="edit_id_ue" name="id_ue">

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_lib_ue">Libellé UE</label>
                        <input type="text" id="edit_lib_ue" name="lib_ue" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_id_ens">Nom de l'enseignant: </label>
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
                        <label for="edit_niveau">Niveau</label>
                        <select id="edit_niveau" name="niveau" required onchange="updateEditSemestres(this.value)">
                            <option value="">Sélectionnez un niveau</option>
                            <?php foreach ($niveaux_list as $niv): ?>
                                <option value="<?php echo $niv['id_niv_etd']; ?>"><?php echo $niv['lib_niv_etd']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_semestre">Semestre</label>
                        <select id="edit_semestre" name="semestre" required>
                            <option value="">Sélectionnez un semestre</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_credit_ue">Crédit UE</label>
                        <input type="number" id="edit_credit_ue" name="credit_ue" required min="1" max="30">
                    </div>
                    <div class="form-group">
                        <label for="edit_volume_horaire">Volume horaire</label>
                        <input type="number" id="edit_volume_horaire" name="volume_horaire" required min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="button">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation de suppression (simple) harmonisée -->
    <div id="confirmation-modal-single" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirmation de suppression</h2>
                <button class="close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="delete-confirmation">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Êtes-vous sûr de vouloir supprimer cette UE ?</h3>
                <p>Cette action est irréversible. Toutes les données associées à cette UE seront définitivement perdues.</p>
                <div class="ue-info">
                    <strong>UE à supprimer :</strong><br>
                    <span id="delete_ue_info"></span>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" id="delete_id_ue" name="id_ue">
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeDeleteModal()">
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
        // Fonction pour mettre à jour les semestres
        function updateSemestres(niveauId) {
            const semestreSelect = document.getElementById('semestre');
            semestreSelect.innerHTML = '<option value="">Sélectionnez un semestre</option>';

            if (niveauId) {
                fetch('../assets/traitements/get_semestres.php?niveau_id=' + niveauId)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erreur réseau: ' + response.status);
                        }
                        return response.json();
                    })
                    .then(semestres => {
                        if (semestres.error) {
                            console.error('Erreur:', semestres.error);
                            return;
                        }
                        semestres.forEach(semestre => {
                            const option = document.createElement('option');
                            option.value = semestre.id_semestre;
                            option.textContent = semestre.lib_semestre;
                            semestreSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        semestreSelect.innerHTML = '<option value="">Erreur lors du chargement des semestres</option>';
                    });
            }
        }

        // Fonctions pour la gestion des modals
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter une UE';
            document.getElementById('ueForm').reset();
            document.getElementById('id_ue').value = '';
            document.getElementById('ueModal').style.display = 'block';
        }

        // Ouvrir la modale si le paramètre modal=open est présent dans l'URL
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('modal') === 'open') {
                document.getElementById('ueModal').style.display = 'block';
            }
        }

        function editUE(id, code, id_ens, libelle) {
            document.getElementById('modalTitle').textContent = 'Modifier une UE';
            document.getElementById('id_ue').value = id;
            document.getElementById('edit_id_ens').value = id_ens;
            document.getElementById('code_ue').value = code;
            document.getElementById('lib_ue').value = libelle;
            document.getElementById('ueModal').style.display = 'block';
        }

        function deleteUE(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette UE ?')) {
                // Ajouter ici la logique de suppression
            }
        }

        function closeModal() {
            document.getElementById('ueModal').style.display = 'none';
        }

        // Fermer la modale si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('ueModal')) {
                closeModal();
            }
        }

        // Empêcher la fermeture de la modale lors du clic sur son contenu
        document.querySelector('.modal-content').onclick = function(event) {
            event.stopPropagation();
        }

        // Fonctions pour la gestion des modals
        function showViewModal(id) {
            fetch(`../assets/traitements/get_ue_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('view_code_ue').textContent = data.id_ue;
                    document.getElementById('view_lib_ue').textContent = data.lib_ue;
                    let enseignant = "Non renseigné";
                    if (data.nom_ens && data.nom_ens.trim() !== "" && data.prenoms_ens && data.prenoms_ens.trim() !== "") {
                        enseignant = data.nom_ens + " " + data.prenoms_ens;
                    }
                    document.getElementById('view_ens_ue').textContent = enseignant;
                    document.getElementById('view_credit_ue').textContent = data.credit_ue;
                    document.getElementById('view_volume_horaire').textContent = data.volume_horaire;
                    document.getElementById('view_niveau').textContent = data.lib_niv_etd;
                    document.getElementById('view_semestre').textContent = data.lib_semestre;
                    document.getElementById('viewModal').style.display = 'block';
                })
                .catch(error => console.error('Erreur:', error));
        }

        function showEditModal(id) {
            fetch(`../assets/traitements/get_ue_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id_ue').value = data.id_ue;
                    document.getElementById('edit_lib_ue').value = data.lib_ue;
                    document.getElementById('edit_id_ens').value = data.id_ens;
                    document.getElementById('edit_credit_ue').value = data.credit_ue;
                    document.getElementById('edit_volume_horaire').value = data.volume_horaire;
                    document.getElementById('edit_niveau').value = data.id_niv_etd;
                    updateEditSemestres(data.id_niv_etd, data.id_semestre);
                    document.getElementById('editModal').style.display = 'block';
                })
                .catch(error => console.error('Erreur:', error));
        }

        function showDeleteModal(id) {
            fetch(`../assets/traitements/get_ue_details.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('delete_id_ue').value = id;
                    document.getElementById('delete_ue_info').textContent = data.id_ue + " - " + data.lib_ue;
                    document.getElementById('confirmation-modal-single').style.display = 'flex';
                })
                .catch(error => console.error('Erreur:', error));
        }

        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').style.display = 'none';
        }

        // Fonction pour mettre à jour les semestres dans le modal d'édition
        function updateEditSemestres(niveauId, selectedSemestreId = null) {
            const semestreSelect = document.getElementById('edit_semestre');
            semestreSelect.innerHTML = '<option value="">Sélectionnez un semestre</option>';

            if (niveauId) {
                fetch('../assets/traitements/get_semestres.php?niveau_id=' + niveauId)
                    .then(response => response.json())
                    .then(semestres => {
                        semestres.forEach(semestre => {
                            const option = document.createElement('option');
                            option.value = semestre.id_semestre;
                            option.textContent = semestre.lib_semestre;
                            if (selectedSemestreId && semestre.id_semestre == selectedSemestreId) {
                                option.selected = true;
                            }
                            semestreSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Erreur:', error));
            }
        }

        // Fermer les modals si on clique en dehors
        window.onclick = function(event) {
            if (event.target == document.getElementById('viewModal')) {
                closeViewModal();
            }
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            }
            if (event.target == document.getElementById('confirmation-modal-single')) {
                closeDeleteModal();
            }
        }

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
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins une UE à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> UE sélectionnée(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
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