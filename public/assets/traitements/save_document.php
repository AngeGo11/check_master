<?php
/**
 * Sauvegarde des documents OnlyOffice
 */

header('Content-Type: application/json');

// Récupérer les données POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Données invalides']);
    exit;
}

try {
    // Récupérer les paramètres
    $documentUrl = $data['documentUrl'] ?? '';
    $studentId = $data['studentId'] ?? '';
    $themeReport = $data['themeReport'] ?? '';
    
    if (empty($documentUrl) || empty($studentId)) {
        throw new Exception('Paramètres manquants');
    }
    
    // Télécharger le document depuis OnlyOffice
    $documentContent = file_get_contents($documentUrl);
    
    if ($documentContent === false) {
        throw new Exception('Impossible de télécharger le document');
    }
    
    // Créer le nom de fichier
    $fileName = 'rapport_' . $studentId . '_' . date('Y-m-d_H-i-s') . '.docx';
    $filePath = '../../storage/uploads/rapports/' . $fileName;
    
    // Créer le dossier s'il n'existe pas
    $uploadDir = dirname($filePath);
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Sauvegarder le fichier
    if (file_put_contents($filePath, $documentContent) === false) {
        throw new Exception('Erreur lors de la sauvegarde du fichier');
    }
    
    // Sauvegarder en base de données
    require_once '../../../config/config.php';
    require_once '../../../app/Controllers/RapportController.php';
    
    $rapportController = new RapportController($pdo);
    $result = $rapportController->createReport($studentId, $themeReport, $filePath);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Document sauvegardé avec succès',
            'filePath' => $filePath
        ]);
    } else {
        throw new Exception('Erreur lors de la sauvegarde en base de données');
    }
    
} catch (Exception $e) {
    error_log('Erreur sauvegarde document: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 