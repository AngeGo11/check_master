<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../Controllers/PisteAuditController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Initialisation du contrôleur
$auditController = new PisteAuditController($pdo);

// Récupération de l'action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_audit_records':
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $filters = [
                'date_debut' => $_GET['date_debut'] ?? date('Y-m-d', strtotime('-7 days')),
                'date_fin' => $_GET['date_fin'] ?? date('Y-m-d'),
                'type_action' => $_GET['type_action'] ?? '',
                'type_utilisateur' => $_GET['type_utilisateur'] ?? '',
                'module' => $_GET['module'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            
            $result = $auditController->getAuditRecordsWithPagination($page, $limit, $filters);
            echo json_encode(['success' => true, 'data' => $result]);
            exit;

        case 'get_statistics':
            $stats = $auditController->getAuditStatistics();
            echo json_encode(['success' => true, 'data' => $stats]);
            exit;

        case 'export_audit':
            $filters = [
                'date_debut' => $_GET['date_debut'] ?? date('Y-m-d', strtotime('-30 days')),
                'date_fin' => $_GET['date_fin'] ?? date('Y-m-d'),
                'type_action' => $_GET['type_action'] ?? '',
                'type_utilisateur' => $_GET['type_utilisateur'] ?? '',
                'module' => $_GET['module'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            
            $format = $_GET['format'] ?? 'csv';
            $result = $auditController->exportAuditData($filters, $format);
            
            if ($result) {
                echo json_encode(['success' => true, 'file' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'export']);
            }
            exit;

        case 'get_available_actions':
            $actions = $auditController->getAvailableActions();
            echo json_encode(['success' => true, 'data' => $actions]);
            exit;

        case 'get_available_modules':
            $modules = $auditController->getAvailableModules();
            echo json_encode(['success' => true, 'data' => $modules]);
            exit;

        case 'get_user_types':
            $types = $auditController->getAvailableUserTypes();
            echo json_encode(['success' => true, 'data' => $types]);
            exit;

        case 'get_audit_summary':
            $period = $_GET['period'] ?? 'week';
            $summary = $auditController->getAuditSummary($period);
            echo json_encode(['success' => true, 'data' => $summary]);
            exit;

        case 'get_user_activity':
            $user_id = $_GET['user_id'] ?? 0;
            $period = $_GET['period'] ?? 'week';
            $activity = $auditController->getUserActivity($user_id, $period);
            echo json_encode(['success' => true, 'data' => $activity]);
            exit;

        case 'get_module_activity':
            $module = $_GET['module'] ?? '';
            $period = $_GET['period'] ?? 'week';
            $activity = $auditController->getModuleActivity($module, $period);
            echo json_encode(['success' => true, 'data' => $activity]);
            exit;

        case 'clear_old_logs':
            // Action pour nettoyer les anciens logs (admin seulement)
            $days = $_POST['days'] ?? 90;
            $result = $auditController->clearOldLogs($days);
            
            if ($result) {
                $_SESSION['success_message'] = "Anciens logs supprimés avec succès (plus de $days jours)";
            } else {
                $_SESSION['error_message'] = 'Erreur lors du nettoyage des logs';
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            exit;
    }

    // Redirection vers la page de piste d'audit
    header('Location: ../../index_commission.php?page=piste_audit');
    exit;

} catch (Exception $e) {
    error_log("Erreur dans traitements_piste_audit.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue: ' . $e->getMessage()]);
    exit;
}
?> 