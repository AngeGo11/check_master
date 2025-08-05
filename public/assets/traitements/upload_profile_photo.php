<?php
// Démarrer la session
session_start();

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/check_database_structure.php';

// Établir la connexion à la base de données
$pdo = DataBase::getConnection();

// Vérification de sécurité
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    // Vérifier si un fichier a été uploadé
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        $uploadError = $_FILES['profile_photo']['error'] ?? 'Fichier non défini';
        error_log('Erreur upload: ' . $uploadError);
        throw new Exception('Aucun fichier uploadé ou erreur lors de l\'upload (Code: ' . $uploadError . ')');
    }

    $file = $_FILES['profile_photo'];
    error_log('Fichier reçu: ' . $file['name'] . ' - Taille: ' . $file['size'] . ' - Type: ' . $file['type']);
    
    // Validation du fichier
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('Fichier trop volumineux. Taille maximum : 5MB.');
    }
    
    // Créer le dossier d'upload s'il n'existe pas
    $uploadDir = __DIR__ . '/../../../storage/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Impossible de créer le dossier d\'upload');
        }
    }
    
    error_log('Dossier upload: ' . $uploadDir . ' - Existe: ' . (is_dir($uploadDir) ? 'Oui' : 'Non'));
    
    // Générer un nom de fichier unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    error_log('Chemin fichier: ' . $filepath);
    
    // Déplacer le fichier uploadé
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erreur lors du déplacement du fichier');
    }
    
    error_log('Fichier déplacé avec succès');
    
    // Redimensionner l'image si nécessaire
    $imageInfo = getimagesize($filepath);
    if ($imageInfo[0] > 500 || $imageInfo[1] > 500) {
        resizeImage($filepath, 500, 500);
        error_log('Image redimensionnée');
    }
    
    // Mettre à jour la base de données
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    
    error_log('User ID: ' . $userId . ' - User Type: ' . $userType);
    
    // Utiliser les fonctions robustes pour déterminer la table et colonne
    $table = getUserTable($userType);
    $column = getPhotoField($userType);
    $idField = getIdField($userType);
    
    error_log('Table: ' . $table . ' - Colonne: ' . $column . ' - ID Field: ' . $idField);
    
    // Vérifier si le type d'utilisateur supporte les photos
    if (!$column) {
        echo json_encode([
            'success' => false,
            'error' => 'Type d\'utilisateur non supporté pour l\'upload de photo'
        ]);
        exit;
    }
    
    // Vérifier si la colonne existe dans la table
    if (!columnExists($table, $column)) {
        echo json_encode([
            'success' => false,
            'error' => 'Structure de base de données non compatible: colonne photo non trouvée'
        ]);
        exit;
    }
    
    // Supprimer l'ancienne photo si elle existe
    $stmt = $pdo->prepare("SELECT $column FROM $table WHERE $idField = ?");
    $stmt->execute([$userId]);
    $oldPhoto = $stmt->fetchColumn();
    
    if ($oldPhoto && file_exists(__DIR__ . '/../../../' . $oldPhoto)) {
        unlink(__DIR__ . '/../../../' . $oldPhoto);
        error_log('Ancienne photo supprimée: ' . $oldPhoto);
    }
    
    // Mettre à jour la base de données
    $relativePath = 'storage/uploads/profiles/' . $filename;
    $stmt = $pdo->prepare("UPDATE $table SET $column = ? WHERE $idField = ?");
    $stmt->execute([$relativePath, $userId]);
    
    error_log('Base de données mise à jour avec: ' . $relativePath);
    
    echo json_encode([
        'success' => true,
        'message' => 'Photo de profil mise à jour avec succès',
        'photo_url' => $relativePath
    ]);
    
} catch (Exception $e) {
    error_log('Erreur upload photo profil: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Redimensionne une image
 */
function resizeImage($filepath, $maxWidth, $maxHeight) {
    $imageInfo = getimagesize($filepath);
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    // Calculer les nouvelles dimensions
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = $width * $ratio;
    $newHeight = $height * $ratio;
    
    // Créer l'image source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filepath);
            break;
        default:
            return false;
    }
    
    // Créer l'image de destination
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Préserver la transparence pour PNG et GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Redimensionner
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Sauvegarder
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $filepath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $filepath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $filepath);
            break;
    }
    
    // Libérer la mémoire
    imagedestroy($source);
    imagedestroy($destination);
    
    return true;
}
?> 