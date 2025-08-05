<?php
/**
 * Script pour récupérer les modèles de rapport
 * 
 * Ce script permet de récupérer les modèles de rapport (HTML ou DOCX)
 * et de les retourner au format JSON pour l'éditeur de rapport
 */

// Configuration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Vérification de la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupération du paramètre template
$template = $_GET['template'] ?? '';

if (empty($template)) {
    echo json_encode(['success' => false, 'error' => 'Nom du modèle non spécifié']);
    exit;
}

// Définition du chemin vers les modèles
$templatesDir = __DIR__ . '/../../../storage/templates/';
$templatePath = $templatesDir . $template;

// Vérification de l'existence du fichier
if (!file_exists($templatePath)) {
    echo json_encode(['success' => false, 'error' => 'Modèle non trouvé']);
    exit;
}

try {
    // Détermination du type de fichier
    $extension = pathinfo($template, PATHINFO_EXTENSION);
    
    if ($extension === 'html') {
        // Lecture du fichier HTML
        $content = file_get_contents($templatePath);
        
        if ($content === false) {
            throw new Exception('Impossible de lire le fichier HTML');
        }
        
        echo json_encode([
            'success' => true,
            'type' => 'html',
            'content' => $content
        ]);
        
    } elseif ($extension === 'docx') {
        // Lecture du fichier DOCX (binaire)
        $content = file_get_contents($templatePath);
        
        if ($content === false) {
            throw new Exception('Impossible de lire le fichier DOCX');
        }
        
        // Encodage en base64 pour transmission JSON
        $base64Content = base64_encode($content);
        
        echo json_encode([
            'success' => true,
            'type' => 'docx',
            'content' => $base64Content
        ]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Type de fichier non supporté']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 