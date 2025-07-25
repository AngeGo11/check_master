<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'annees_academiques') {
    return;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../config/config.php';



$fullname = $_SESSION['user_fullname'] ?? 'Utilisateur';
$lib_user_type = $_SESSION['lib_user_type'] ?? 'Utilisateur';

// Traitement du formulaire d'ajout/modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                try {
                    // Validation des dates
                    $date_debut = $_POST['date_debut'];
                    $date_fin = $_POST['date_fin'];

                    if (strtotime($date_debut) >= strtotime($date_fin)) {
                        $_SESSION['error'] = "La date de début doit être antérieure à la date de fin";
                        break;
                    }

                    // Vérifier les chevauchements avec d'autres années
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM annee_academique WHERE 
                        (date_debut <= ? AND date_fin >= ?) OR 
                        (date_debut <= ? AND date_fin >= ?) OR 
                        (date_debut >= ? AND date_fin <= ?)");
                    $stmt->execute([$date_debut, $date_debut, $date_fin, $date_fin, $date_debut, $date_fin]);

                    if ($stmt->fetchColumn() > 0) {
                        $_SESSION['error'] = "Cette période chevauche avec une année académique existante";
                        break;
                    }

                    // Génération de l'ID automatique
                    $debut = new DateTime($date_debut);
                    $fin = new DateTime($date_fin);
                    $id_ac = $fin->format('y') . $debut->format('y');

                    $stmt = $pdo->prepare("INSERT INTO annee_academique (id_ac, date_debut, date_fin, statut_annee) VALUES (?, ?, ?, 'En attente')");
                    $stmt->execute([$id_ac, $date_debut, $date_fin]);
                    $_SESSION['success'] = "L'année académique a été ajoutée avec succès";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Une erreur est survenue lors de l'ajout : " . $e->getMessage();
                }
                break;

            case 'edit':
                try {
                    $id_ac = $_POST['id_ac'];
                    $date_debut = $_POST['date_debut'];
                    $date_fin = $_POST['date_fin'];

                    if (strtotime($date_debut) >= strtotime($date_fin)) {
                        $_SESSION['error'] = "La date de début doit être antérieure à la date de fin";
                        break;
                    }

                    // Vérifier les chevauchements (exclure l'année en cours de modification)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM annee_academique WHERE id_ac != ? AND (
                        (date_debut <= ? AND date_fin >= ?) OR 
                        (date_debut <= ? AND date_fin >= ?) OR 
                        (date_debut >= ? AND date_fin <= ?))");
                    $stmt->execute([$id_ac, $date_debut, $date_debut, $date_fin, $date_fin, $date_debut, $date_fin]);

                    if ($stmt->fetchColumn() > 0) {
                        $_SESSION['error'] = "Cette période chevauche avec une autre année académique";
                        break;
                    }

                    $stmt = $pdo->prepare("UPDATE annee_academique SET date_debut = ?, date_fin = ? WHERE id_ac = ?");
                    $stmt->execute([$date_debut, $date_fin, $id_ac]);
                    $_SESSION['success'] = "L'année académique a été modifiée avec succès";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Une erreur est survenue lors de la modification : " . $e->getMessage();
                }
                break;

            case 'delete_selected':
                if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
                    $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
                    if (!empty($ids)) {
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $stmt = $pdo->prepare("DELETE FROM annee_academique WHERE id_ac IN ($placeholders) AND statut_annee != 'En cours'");
                        $stmt->execute($ids);
                        $_SESSION['success'] = count($ids) . " année(s) académique(s) supprimée(s) avec succès.";
                    } else {
                        $_SESSION['error'] = "Aucune année sélectionnée.";
                    }
                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                    exit;
                }
                break;
        }
    }
    // Redirection pour éviter la soumission multiple
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Récupération des années académiques
try {
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
        $where_conditions[] = "(id_ac LIKE ? OR date_debut LIKE ? OR date_fin LIKE ? OR statut_annee LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }

    if ($status_filter !== '') {
        $where_conditions[] = "statut_annee = ?";
        $params[] = $status_filter;
    }

    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }

    // Compter le total pour la pagination
    $sql_count = "SELECT COUNT(*) FROM annee_academique $where_clause";
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_annees = $stmt_count->fetchColumn();
    $total_pages = max(1, ceil($total_annees / $per_page));

    // Récupérer les années filtrées et paginées
    $sql = "SELECT * FROM annee_academique $where_clause ORDER BY date_debut DESC LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $annees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $annees = [];
    $_SESSION['error'] = "Erreur lors de la récupération des données";
}

// Récupération des détails d'une année spécifique si demandé
$annee_details = null;
if (isset($_GET['view']) && !empty($_GET['view'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM annee_academique WHERE id_ac = ?");
        $stmt->execute([$_GET['view']]);
        $annee_details = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la récupération des détails";
    }
}

// Traitement des actions GET
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'set_current':
            if (isset($_GET['id'])) {
                try {
                    $pdo->beginTransaction();
                    // Mettre à jour le statut de toutes les années
                    $pdo->exec("UPDATE annee_academique SET statut_annee = 'Terminée'");
                    // Définir l'année sélectionnée comme année courante
                    $stmt = $pdo->prepare("UPDATE annee_academique SET statut_annee = 'En cours' WHERE id_ac = ?");
                    $stmt->execute([$_GET['id']]);
                    $pdo->commit();
                    $_SESSION['success'] = "L'année académique a été définie comme année courante";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['error'] = "Une erreur est survenue lors de la modification du statut";
                }
            }
            break;

        case 'set_terminated':
            if (isset($_GET['id'])) {
                try {
                    $stmt = $pdo->prepare("UPDATE annee_academique SET statut_annee = 'Terminée' WHERE id_ac = ?");
                    $stmt->execute([$_GET['id']]);
                    $_SESSION['success'] = "L'année académique a été marquée comme terminée";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Une erreur est survenue lors de la modification du statut";
                }
            }
            break;

        case 'delete':
            if (isset($_GET['id'])) {
                try {
                    // Vérifier si l'année est en cours avant suppression
                    $stmt = $pdo->prepare("SELECT statut_annee FROM annee_academique WHERE id_ac = ?");
                    $stmt->execute([$_GET['id']]);
                    $statut = $stmt->fetchColumn();

                    if ($statut === 'En cours') {
                        $_SESSION['error'] = "Impossible de supprimer l'année académique en cours";
                        break;
                    }

                    $stmt = $pdo->prepare("DELETE FROM annee_academique WHERE id_ac = ?");
                    $stmt->execute([$_GET['id']]);
                    $_SESSION['success'] = "L'année académique a été supprimée avec succès";
                } catch (Exception $e) {
                    $_SESSION['error'] = "Une erreur est survenue lors de la suppression : " . $e->getMessage();
                }
            }
            break;
    }
    // Rediriger pour éviter la soumission multiple
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Récupération des détails pour l'édition
$annee_edit = null;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM annee_academique WHERE id_ac = ?");
        $stmt->execute([$_GET['edit']]);
        $annee_edit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la récupération des données d'édition";
    }
}

// Récupération des messages de notification
$success_message = $_SESSION['success'] ?? null;
$error_message = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Années Académiques</title>
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
            max-width: 500px;
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
            text-decoration: none;
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
            box-sizing: border-box;
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

        .alert {
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
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
                <h1>Liste des Années Académiques</h1>
                <p>Gestion des années académiques</p>
            </div>
        </div>

        <div class="header-actions">

            <div class="user-avatar"><?php echo strtoupper(substr($fullname, 0, 1)); ?></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($fullname); ?></div>
                <div class="user-role"><?php echo htmlspecialchars($lib_user_type); ?></div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="actions-bar">
            <a href="../index_commission.php?page=parametres_generaux" class="button">
                <i class="fas fa-arrow-left"></i> Retour aux paramètres
            </a>
            <form method="GET" class="search-box" style="display:inline-flex;align-items:center;gap:5px;">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Rechercher une année académique..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="button" style="margin-left:5px;">Rechercher</button>
            </form>
            <button class="button" onclick="showAddModal()">
                <i class="fas fa-plus"></i>
                Ajouter une année académique
            </button>
        </div>

        <!-- Messages de notification -->
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="filters">
            <form method="GET" style="display:inline;">
                <!-- Garder la recherche si présente -->
                <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                <select class="filter-select" name="status" onchange="this.form.submit()">
                    <option value="">Tous les statuts</option>
                    <option value="En cours" <?php echo ($status_filter === 'En cours') ? 'selected' : ''; ?>>En cours</option>
                    <option value="Terminée" <?php echo ($status_filter === 'Terminée') ? 'selected' : ''; ?>>Terminée</option>
                </select>
            </form>
        </div>

        <!-- Bouton de suppression multiple -->
        <form id="bulkDeleteForm" method="POST" style="margin-bottom:10px;">
            <input type="hidden" name="bulk_delete" value="1">
            <button type="button" class="button danger" id="bulkDeleteBtn"><i class="fas fa-trash"></i> Supprimer la sélection</button>
            <input type="hidden" name="delete_selected_ids[]" id="delete_selected_ids">
        </form>

        <!-- Table de données -->
        <div class="data-table-container">
            <div class="data-table-header">
                <div class="data-table-title">Liste des années académiques (<?php echo $total_annees; ?> éléments)</div>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                        <th style="width: 80px;">ID</th>
                        <th>Date de début</th>
                        <th>Date de fin</th>
                        <th>Statut</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php if (empty($annees)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                Aucune année académique trouvée
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($annees as $annee):
                            try {
                                $debut = new DateTime($annee['date_debut']);
                                $fin = new DateTime($annee['date_fin']);
                                $id_annee = htmlspecialchars($annee['id_ac']);
                            } catch (Exception $e) {
                                continue;
                            }
                        ?>
                            <tr data-status="<?php echo htmlspecialchars($annee['statut_annee']); ?>" data-year="<?php echo $debut->format('Y'); ?>">
                                <td><input type="checkbox" class="row-checkbox" value="<?php echo htmlspecialchars($annee['id_ac']); ?>"></td>
                                <td><?php echo $id_annee; ?></td>
                                <td><?php echo $debut->format('d/m/Y'); ?></td>
                                <td><?php echo $fin->format('d/m/Y'); ?></td>
                                <td>
                                    <span class="status-badge <?php
                                                                echo $annee['statut_annee'] === 'En cours' ? 'status-active' : ($annee['statut_annee'] === 'Terminée' ? 'status-inactive' : 'status-pending');
                                                                ?>">
                                        <?php echo htmlspecialchars($annee['statut_annee']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?view=<?php echo urlencode($id_annee); ?>" class="action-button view-button" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?edit=<?php echo urlencode($id_annee); ?>" class="action-button edit-button" title="Modifier">
                                            <i class="fas fa-pen"></i>
                                        </a>
                                        <?php if ($annee['statut_annee'] !== 'En cours'): ?>
                                            <button type="button" class="action-button set-current-button" title="Définir comme année courante"
                                                onclick="showConfirmModal('set_current', '<?php echo htmlspecialchars($id_annee); ?>', 'Voulez-vous définir cette année comme année académique courante ?')">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($annee['statut_annee'] === 'En cours'): ?>
                                            <button type="button" class="action-button terminate-button" title="Marquer comme terminée"
                                                onclick="showConfirmModal('set_terminated', '<?php echo htmlspecialchars($id_annee); ?>', 'Voulez-vous marquer cette année académique comme terminée ?')">
                                                <i class="fas fa-check-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="button" class="action-button delete-button" title="Supprimer"
                                            onclick="showDeleteModal('<?php echo htmlspecialchars($id_annee); ?>', '<?php echo htmlspecialchars($id_annee); ?>')">
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
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=1">«</a>
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $page - 1; ?>">‹</a>
            <?php endif; ?>
            <?php
            // Affichage de 5 pages max autour de la page courante
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a class="page-item<?php if ($i == $page) echo ' active'; ?>" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $page + 1; ?>">›</a>
                <a class="page-item" href="?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&page=<?php echo $total_pages; ?>">»</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modale pour l'ajout -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Ajouter une année académique</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST" id="addForm">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label for="add_date_debut">Date de début *</label>
                    <input type="date" id="add_date_debut" name="date_debut" required>
                </div>
                <div class="form-group">
                    <label for="add_date_fin">Date de fin *</label>
                    <input type="date" id="add_date_fin" name="date_fin" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button secondary" onclick="closeAddModal()">Annuler</button>
                    <button type="submit" class="button">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale pour les détails -->
    <?php if ($annee_details): ?>
        <div id="viewModal" class="modal" style="display: block;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Détails de l'année académique</h2>
                    <a href="?" class="close">&times;</a>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>ID de l'année académique</label>
                        <p><?php echo htmlspecialchars($annee_details['id_ac']); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Date de début</label>
                        <p><?php echo (new DateTime($annee_details['date_debut']))->format('d/m/Y'); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Date de fin</label>
                        <p><?php echo (new DateTime($annee_details['date_fin']))->format('d/m/Y'); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Statut</label>
                        <p><?php echo htmlspecialchars($annee_details['statut_annee']); ?></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="?" class="button secondary">Fermer</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modale pour l'édition -->
    <?php if ($annee_edit): ?>
        <div id="editModal" class="modal" style="display: block;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Modifier l'année académique</h2>
                    <a href="?" class="close">&times;</a>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id_ac" value="<?php echo htmlspecialchars($annee_edit['id_ac']); ?>">
                    <div class="form-group">
                        <label for="edit_date_debut">Date de début *</label>
                        <input type="date" id="edit_date_debut" name="date_debut" value="<?php echo htmlspecialchars($annee_edit['date_debut']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_date_fin">Date de fin *</label>
                        <input type="date" id="edit_date_fin" name="date_fin" value="<?php echo htmlspecialchars($annee_edit['date_fin']); ?>" required>
                    </div>
                    <div class="modal-footer">
                        <a href="?" class="button secondary">Annuler</a>
                        <button type="submit" class="button">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modale de confirmation -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirmation</h2>
                <button class="close" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeConfirmModal()">Annuler</button>
                <form id="confirmForm" method="GET" style="display: inline;">
                    <input type="hidden" name="action" id="confirmAction">
                    <input type="hidden" name="id" id="confirmId">
                    <button type="submit" class="button">Confirmer</button>
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
            <p id="deleteSingleMessage">Êtes-vous sûr de vouloir supprimer cette année académique ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span></p>
            <div class="modal-footer">
                <button type="button" class="button secondary" onclick="closeDeleteModal()">Annuler</button>
                <form id="deleteForm" method="GET" style="display:inline;">
                    <input type="hidden" id="delete_action_id" name="id">
                    <input type="hidden" name="action" value="delete">
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

    <script>
        // Variables globales
        let currentAction = '';
        let currentId = '';

        // Fonctions pour la modale d'ajout
        function showAddModal() {
            document.getElementById('addModal').style.display = 'block';
            document.getElementById('addForm').reset();
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        // Fonctions pour la modale de confirmation
        function showConfirmModal(action, id, message) {
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('confirmAction').value = action;
            document.getElementById('confirmId').value = id;
            document.getElementById('confirmModal').style.display = 'block';
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
            currentAction = '';
            currentId = '';
        }

        // Validation des formulaires
        document.getElementById('addForm').addEventListener('submit', function(e) {
            const dateDebut = document.getElementById('add_date_debut').value;
            const dateFin = document.getElementById('add_date_fin').value;

            if (dateDebut >= dateFin) {
                e.preventDefault();
                alert('La date de début doit être antérieure à la date de fin');
                return false;
            }
        });

        // Fermer les modales si on clique en dehors
        window.onclick = function(event) {
            const modals = ['confirmModal', 'addModal'];
            modals.forEach(modalId => {
                const modal = document.getElementById(modalId);
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Empêcher la fermeture des modales lors du clic sur leur contenu
        document.querySelectorAll('.modal-content').forEach(content => {
            content.onclick = function(event) {
                event.stopPropagation();
            }
        });

        // Auto-hide des messages d'alerte après 5 secondes
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

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

        // Ajoute la fonction showDeleteModal harmonisée :
        function showDeleteModal(id, libelle) {
            document.getElementById('delete_action_id').value = id;
            document.getElementById('deleteSingleMessage').innerHTML = "Êtes-vous sûr de vouloir supprimer l'année académique : <b>" + libelle + "</b> ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>";
            document.getElementById('confirmation-modal-single').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('confirmation-modal-single').style.display = 'none';
        }

        // Fermer la modale avec le X
        document.getElementById('close-confirmation-modal-btn').onclick = function() {
            document.getElementById('confirmation-modal').style.display = 'none';
        };
        // Fermer la modale si clic en dehors
        window.addEventListener('click', function(event) {
            if (event.target === document.getElementById('confirmation-modal')) {
                document.getElementById('confirmation-modal').style.display = 'none';
            }
        });

        function openDeleteMultipleModal() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            const msg = document.getElementById('deleteMultipleMessage');
            const footer = document.getElementById('deleteMultipleFooter');
            if (checked.length === 0) {
                msg.innerHTML = "Aucune sélection. Veuillez sélectionner au moins une année académique à supprimer.";
                footer.innerHTML = '<button type="button" class="button" onclick="closeDeleteMultipleModal()">OK</button>';
            } else {
                msg.innerHTML = `Êtes-vous sûr de vouloir supprimer <b>${checked.length}</b> année(s) académique(s) sélectionnée(s) ?<br><span style='color:#c0392b;font-size:0.95em;'>Cette action est irréversible.</span>`;
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