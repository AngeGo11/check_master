<?php
require_once __DIR__ . '/../../../app/config/config.php';



try {
    $documentId = $_GET['id'] ?? '';
    $documentType = $_GET['type'] ?? '';
    
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
    //$fullPath = 'C:/wamp64/www/GSCV+/storage/uploads/compte_rendu/compte_rendu_2025-07-30_01-51-25.pdf';

    
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
    
    // Vérifier si c'est un fichier PDF ou image
    $viewableTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (in_array($mimeType, $viewableTypes)) {
        // Afficher le fichier dans le navigateur
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: public, max-age=3600');
        
        readfile($fullPath);
    } else {
        // Pour les autres types, afficher une page HTML avec informations
        ?>
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Visualisation - <?php echo htmlspecialchars($title); ?></title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        </head>
        <body class="bg-gray-50">
            <div class="min-h-screen flex items-center justify-center">
                <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-alt text-2xl text-primary"></i>
                        </div>
                        <h1 class="text-xl font-semibold text-gray-900 mb-2">
                            <?php echo htmlspecialchars($title); ?>
                        </h1>
                        <p class="text-gray-600 mb-4">
                            Type: <?php echo htmlspecialchars($documentType); ?><br>
                            Format: <?php echo strtoupper(pathinfo($filePath, PATHINFO_EXTENSION)); ?>
                        </p>
                        <div class="space-y-3">
                            <a href="#" 
                               onclick="downloadDocument(<?php echo $documentId; ?>, '<?php echo $documentType; ?>')"
                               class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center justify-center">
                                <i class="fas fa-download mr-2"></i>
                                Télécharger
                            </a>
                            <button onclick="window.close()" 
                                    class="w-full bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors duration-200">
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
                function downloadDocument(id, type) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = window.location.pathname;
                    form.style.display = 'none';
                    
                    const inputId = document.createElement('input');
                    inputId.type = 'hidden';
                    inputId.name = 'document_id';
                    inputId.value = id;
                    form.appendChild(inputId);
                    
                    const inputType = document.createElement('input');
                    inputType.type = 'hidden';
                    inputType.name = 'document_type';
                    inputType.value = type;
                    form.appendChild(inputType);
                    
                    document.body.appendChild(form);
                    form.submit();
                    document.body.removeChild(form);
                }
            </script>
        </body>
        </html>
        <?php
    }
    
} catch (Exception $e) {
    error_log('Erreur visualisation document: ' . $e->getMessage());
    http_response_code(500);
    echo 'Erreur: ' . $e->getMessage();
}
?> 