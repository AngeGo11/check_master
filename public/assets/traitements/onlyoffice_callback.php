<?php
/**
 * Callback OnlyOffice pour la gestion des événements de l'éditeur
 */

require_once __DIR__ . '/../../../config/config.php';

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée');
}

// Récupérer les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    exit('Données JSON invalides');
}

// Log des événements pour le débogage
error_log('OnlyOffice Callback: ' . json_encode($data));

// Traiter les différents types d'événements
switch ($data['status']) {
    case 1: // Document en cours d'édition
        handleDocumentEditing($data);
        break;
        
    case 2: // Document prêt pour l'édition
        handleDocumentReady($data);
        break;
        
    case 3: // Erreur lors de l'édition
        handleDocumentError($data);
        break;
        
    case 4: // Document en cours de sauvegarde
        handleDocumentSaving($data);
        break;
        
    case 6: // Document en cours de fermeture
        handleDocumentClosing($data);
        break;
        
    case 7: // Document fermé
        handleDocumentClosed($data);
        break;
        
    default:
        error_log('Événement OnlyOffice inconnu: ' . $data['status']);
        break;
}

// Réponse de succès
http_response_code(200);
echo json_encode(['error' => 0]);

/**
 * Gérer l'événement de modification du document
 */
function handleDocumentEditing($data) {
    error_log('Document en cours d\'édition: ' . $data['key']);
    
    // Ici vous pouvez ajouter une logique pour suivre les modifications
    // Par exemple, sauvegarder un timestamp de dernière modification
}

/**
 * Gérer l'événement de document prêt
 */
function handleDocumentReady($data) {
    error_log('Document prêt pour l\'édition: ' . $data['key']);
    
    // Le document est chargé et prêt pour l'édition
    // Vous pouvez ici initialiser des variables ou des états
}

/**
 * Gérer l'événement d'erreur
 */
function handleDocumentError($data) {
    error_log('Erreur lors de l\'édition: ' . json_encode($data));
    
    // Gérer les erreurs (fichier non trouvé, permissions, etc.)
    if (isset($data['error'])) {
        switch ($data['error']) {
            case -1:
                error_log('Erreur: Document non trouvé');
                break;
            case -2:
                error_log('Erreur: Document en cours d\'édition par un autre utilisateur');
                break;
            case -3:
                error_log('Erreur: Erreur interne du serveur');
                break;
            case -4:
                error_log('Erreur: Erreur de conversion');
                break;
            case -5:
                error_log('Erreur: Erreur de téléchargement');
                break;
            default:
                error_log('Erreur inconnue: ' . $data['error']);
        }
    }
}

/**
 * Gérer l'événement de sauvegarde
 */
function handleDocumentSaving($data) {
    error_log('Document en cours de sauvegarde: ' . $data['key']);
    
    // Le document est en cours de sauvegarde
    // Vous pouvez ici ajouter une logique de validation ou de traitement
}

/**
 * Gérer l'événement de fermeture en cours
 */
function handleDocumentClosing($data) {
    error_log('Document en cours de fermeture: ' . $data['key']);
    
    // Le document va être fermé
    // Vous pouvez ici sauvegarder des métadonnées ou nettoyer des ressources
}

/**
 * Gérer l'événement de document fermé
 */
function handleDocumentClosed($data) {
    error_log('Document fermé: ' . $data['key']);
    
    // Le document a été fermé
    // Vous pouvez ici effectuer des actions de nettoyage finales
    
    // Si des modifications ont été apportées, vous pouvez les traiter ici
    if (isset($data['url']) && !empty($data['url'])) {
        // Le document a été modifié et sauvegardé
        handleDocumentModified($data);
    }
}

/**
 * Gérer les modifications du document
 */
function handleDocumentModified($data) {
    error_log('Document modifié et sauvegardé: ' . $data['url']);
    
    try {
        // Récupérer l'ID de l'étudiant depuis la session ou les paramètres
        $studentId = $_SESSION['user_id'] ?? null;
        
        if (!$studentId) {
            error_log('ID étudiant non trouvé dans la session');
            return;
        }
        
        // Mettre à jour le rapport dans la base de données
        global $pdo;
        $stmt = $pdo->prepare("
            UPDATE rapports 
            SET fichier_rapport = ?, 
                date_modification = NOW(),
                statut_rapport = 'En attente d\'approbation'
            WHERE num_etd = ?
        ");
        
        $stmt->execute([$data['url'], $studentId]);
        
        error_log('Rapport mis à jour avec succès pour l\'étudiant: ' . $studentId);
        
    } catch (Exception $e) {
        error_log('Erreur lors de la mise à jour du rapport: ' . $e->getMessage());
    }
}
?> 