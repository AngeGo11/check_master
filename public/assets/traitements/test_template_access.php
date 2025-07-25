<?php
header('Content-Type: application/json');

$templatePath = '../../storage/templates/modele_rapport_de_stage.docx';

if (file_exists($templatePath)) {
    $fileSize = filesize($templatePath);
    $fileInfo = [
        'exists' => true,
        'size' => $fileSize,
        'readable' => is_readable($templatePath),
        'path' => realpath($templatePath)
    ];
    
    // Vérifier que c'est bien un fichier DOCX
    $handle = fopen($templatePath, 'rb');
    if ($handle) {
        $header = fread($handle, 4);
        fclose($handle);
        
        // Les fichiers DOCX commencent par PK (0x50 0x4B)
        if ($header === 'PK' || (ord($header[0]) === 0x50 && ord($header[1]) === 0x4B)) {
            $fileInfo['valid_docx'] = true;
        } else {
            $fileInfo['valid_docx'] = false;
        }
    }
    
    echo json_encode($fileInfo);
} else {
    echo json_encode([
        'exists' => false,
        'error' => 'Fichier non trouvé',
        'path' => $templatePath
    ]);
}
?> 