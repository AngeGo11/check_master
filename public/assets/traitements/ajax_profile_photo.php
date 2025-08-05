<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/Controllers/ParameterController.php';

// Créer une instance du contrôleur
$controller = new ParameterController();

// Déterminer l'action à effectuer
$action = '';

// Pour la mise à jour de photo (avec fichier uploadé)
if (isset($_FILES['change']) && $_FILES['change']['error'] === UPLOAD_ERR_OK && isset($_POST['validate_photo'])) {
    $action = 'update_profile_photo';
}
// Pour la suppression de photo
elseif (isset($_POST['delete'])) {
    $action = 'delete_profile_photo';
}
// Pour les autres actions via POST
else {
    $action = $_POST['action'] ?? '';
}

// Traiter l'action
switch ($action) {
    case 'update_profile_photo':
        $result = $controller->updateProfilePhoto();
        break;
    case 'delete_profile_photo':
        $result = $controller->deleteProfilePhoto();
        break;
    default:
        $result = ['success' => false, 'error' => 'Action non reconnue'];
}

// Retourner la réponse en JSON
header('Content-Type: application/json');
echo json_encode($result);
?> 