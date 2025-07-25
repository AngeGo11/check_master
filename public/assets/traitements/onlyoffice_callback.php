<?php
/**
 * Callback OnlyOffice pour la gestion des événements
 */

header('Content-Type: application/json');

// Récupérer les données POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

// Log des événements pour debug
error_log('OnlyOffice Callback: ' . json_encode($data));

// Traiter selon le type d'événement
switch ($data['status']) {
    case 1: // Document en cours d'édition
        echo json_encode(['error' => 0]);
        break;
        
    case 2: // Document prêt pour l'édition
        echo json_encode(['error' => 0]);
        break;
        
    case 3: // Erreur lors de l'ouverture du document
        echo json_encode(['error' => 0]);
        break;
        
    case 4: // Document en cours de sauvegarde
        echo json_encode(['error' => 0]);
        break;
        
    case 6: // Document en cours de fermeture
        echo json_encode(['error' => 0]);
        break;
        
    case 7: // Erreur lors de la sauvegarde
        echo json_encode(['error' => 0]);
        break;
        
    default:
        echo json_encode(['error' => 0]);
        break;
}
?> 