<?php
session_start();
require_once '../../../app/config/config.php';
require_once '../../../app/Controllers/ReclamationController.php';

header('Content-Type: application/json');

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté.']);
    exit;
}

// Initialisation du contrôleur
$controller = new ReclamationController($pdo);

// Traitement des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'supprimer_multiple':
            handleSupprimerMultiple($controller);
            break;
        case 'traiter_reclamation':
            handleTraiterReclamation($controller);
            break;
        case 'get_details':
            handleGetDetails($controller);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue.']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_details':
            handleGetDetails($controller);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Action non reconnue.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
}

/**
 * Gérer la suppression multiple de réclamations
 */
function handleSupprimerMultiple($controller) {
    if (!isset($_POST['reclamation_ids'])) {
        echo json_encode(['success' => false, 'error' => 'Aucune réclamation sélectionnée.']);
        return;
    }

    $reclamation_ids_json = $_POST['reclamation_ids'];
    $reclamation_ids = json_decode($reclamation_ids_json, true);

    if (empty($reclamation_ids)) {
        echo json_encode(['success' => false, 'error' => 'Aucune réclamation valide sélectionnée.']);
        return;
    }

    $reclamation_ids = array_map('intval', $reclamation_ids);
    $result = $controller->supprimerReclamations($reclamation_ids);

    if ($result !== false) {
        echo json_encode(['success' => true, 'message' => "$result réclamation(s) ont été supprimée(s)."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression des réclamations.']);
    }
}

/**
 * Gérer le traitement d'une réclamation
 */
function handleTraiterReclamation($controller) {
    $reclamation_id = $_POST['reclamation_id'] ?? null;
    $user_group = $_POST['user_group'] ?? null;
    $commentaire = $_POST['retour_traitement'] ?? null;
    $commentaire_transfert = $_POST['commentaire_transfert'] ?? null;

    if (!$reclamation_id || !$user_group) {
        $_SESSION['error_message'] = 'Données manquantes pour le traitement.';
        header('Location: ../../app.php?page=reclamations_etudiants');
        exit;
    }

    // Déterminer l'action selon le groupe utilisateur
    if ($user_group == 'Administrateur plateforme' || $user_group == 'Responsable filière') {
        // Traitement final
        if (!$commentaire) {
            $_SESSION['error_message'] = 'Le commentaire de traitement est requis.';
            header('Location: ../../app.php?page=reclamations_etudiants');
            exit;
        }
        $result = $controller->traiterReclamation($reclamation_id, $commentaire, $user_group);
        $message = 'La réclamation a été traitée avec succès.';
    } else {
        // Transfert par le responsable scolarité
        if (!$commentaire_transfert) {
            $_SESSION['error_message'] = 'Le commentaire de transfert est requis.';
            header('Location: ../../app.php?page=reclamations_etudiants');
            exit;
        }
        $result = $controller->transfererReclamation($reclamation_id, $commentaire_transfert, $user_group);
        $message = 'La réclamation a été transférée avec succès.';
    }

    if ($result) {
        $_SESSION['success_message'] = $message;
        header('Location: ../../app.php?page=reclamations_etudiants');
        exit;
    } else {
        $_SESSION['error_message'] = 'Erreur lors du traitement de la réclamation.';
        header('Location: ../../app.php?page=reclamations_etudiants');
        exit;
    }
}

/**
 * Gérer la récupération des détails d'une réclamation
 */
function handleGetDetails($controller) {
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID de réclamation manquant']);
        return;
    }

    $details = $controller->getReclamationDetails($id);
    
    if (!$details) {
        echo json_encode(['success' => false, 'error' => 'Réclamation non trouvée']);
        return;
    }

    echo json_encode(['success' => true, 'data' => $details]);
}
?> 