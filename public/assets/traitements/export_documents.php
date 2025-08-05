<?php
require_once __DIR__ . '/../../../config/config.php';

// Vérification de sécurité
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Non autorisé';
    exit;
}

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Méthode non autorisée';
    exit;
}

try {
    $documents = json_decode($_POST['documents'], true);
    if (!$documents || !is_array($documents)) {
        throw new Exception('Données de documents invalides');
    }
    
    // Créer un fichier ZIP temporaire
    $zipName = 'export_documents_' . date('Y-m-d_H-i-s') . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipName;
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
        throw new Exception('Impossible de créer le fichier ZIP');
    }
    
    $addedFiles = 0;
    $metadata = [];
    
    foreach ($documents as $doc) {
        $documentId = $doc['id'];
        $documentType = $doc['type'];
        
        // Déterminer la table et colonnes selon le type
        if ($documentType === 'Rapport') {
            $table = 'rapport_etudiant';
            $idColumn = 'id_rapport';
            $fileColumn = 'fichier_rapport';
            $titleColumn = 'titre_rapport';
            $dateColumn = 'date_soumission';
        } elseif ($documentType === 'Compte rendu') {
            $table = 'compte_rendu';
            $idColumn = 'id_cr';
            $fileColumn = 'fichier_cr';
            $titleColumn = 'titre_cr';
            $dateColumn = 'date_creation';
        } else {
            continue; // Ignorer les types non reconnus
        }
        
        // Récupérer les informations complètes du document
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE $idColumn = ?");
        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$document) {
            continue;
        }
        
        $filePath = $document[$fileColumn];
        $title = $document[$titleColumn];
        
        // Ajouter les métadonnées
        $metadata[] = [
            'id' => $documentId,
            'type' => $documentType,
            'titre' => $title,
            'date' => $document[$dateColumn] ?? '',
            'fichier' => $filePath
        ];
        
        // Ajouter le fichier s'il existe
        if ($filePath) {
            $fullPath = __DIR__ . '/../../../storage/uploads/' . $filePath;
            
            if (file_exists($fullPath)) {
                $safeTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title);
                $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
                $zipFileName = $safeTitle . '.' . $extension;
                
                if ($zip->addFile($fullPath, $zipFileName)) {
                    $addedFiles++;
                }
            }
        }
    }
    
    // Ajouter un fichier de métadonnées JSON
    $metadataJson = json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $zip->addFromString('metadata.json', $metadataJson);
    
    // Ajouter un fichier de métadonnées CSV
    $csvContent = "ID,Type,Titre,Date,Fichier\n";
    foreach ($metadata as $meta) {
        $csvContent .= sprintf(
            '"%s","%s","%s","%s","%s"' . "\n",
            $meta['id'],
            $meta['type'],
            str_replace('"', '""', $meta['titre']),
            $meta['date'],
            $meta['fichier']
        );
    }
    $zip->addFromString('metadata.csv', $csvContent);
    
    $zip->close();
    
    // En-têtes pour le téléchargement
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Envoyer le fichier ZIP
    readfile($zipPath);
    
    // Nettoyer le fichier temporaire
    unlink($zipPath);
    
} catch (Exception $e) {
    error_log('Erreur export documents: ' . $e->getMessage());
    http_response_code(500);
    echo 'Erreur: ' . $e->getMessage();
}
?> 