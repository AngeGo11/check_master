<?php
require_once __DIR__ . '/../../../app/config/config.php';



// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Méthode non autorisée';
    exit;
}

try {
    $documentId = $_POST['document_id'] ?? '';
    $documentType = $_POST['document_type'] ?? '';
    
    if (!$documentId || !$documentType) {
        throw new Exception('ID et type de document requis');
    }
    
    // Déterminer la table et colonne selon le type
    if ($documentType === 'Rapport') {
        $table = 'rapport_etudiant';
        $idColumn = 'id_rapport_etd';
        $fileColumn = 'fichier_rapport';
        $titleColumn = 'theme_memoire';
    } elseif ($documentType === 'Compte rendu') {
        $table = 'compte_rendu';
        $idColumn = 'id_cr';
        $fileColumn = 'fichier_cr';
        $titleColumn = 'nom_cr';
    } else {
        throw new Exception('Type de document non reconnu');
    }
    
    // Récupérer les informations du document
    $stmt = $pdo->prepare("SELECT $fileColumn, $titleColumn FROM $table WHERE $idColumn = ?");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        throw new Exception('Document non trouvé');
    }
    
    $filePath = $document[$fileColumn];
    $title = $document[$titleColumn];
    
    if (!$filePath) {
        throw new Exception('Fichier non trouvé');
    }
    
    // Construire le chemin complet du fichier
    $fullPath = __DIR__ . '/../../../' . $filePath;
   // $fullPath = 'C:/wamp64/www/GSCV+/storage/uploads/compte_rendu/compte_rendu_2025-07-30_01-51-25.pdf';
    
    if (!file_exists($fullPath)) {
        error_log("Fichier non trouvé: $fullPath");
        error_log("Chemin relatif: $filePath");
        error_log("Document ID: $documentId, Type: $documentType");
        throw new Exception('Fichier physique non trouvé: ' . $fullPath);
    }
    
    // Déterminer le type MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fullPath);
    finfo_close($finfo);
    
    // Nettoyer le nom de fichier pour le téléchargement
    $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
    $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
    $downloadName = $safeTitle . '.' . $extension;
    
    // En-têtes pour le téléchargement
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Envoyer le fichier
    readfile($fullPath);
    
} catch (Exception $e) {
    error_log('Erreur téléchargement document: ' . $e->getMessage());
    http_response_code(500);
    echo 'Erreur: ' . $e->getMessage();
}
?> 