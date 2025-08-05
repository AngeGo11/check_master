<?php
/**
 * Export PDF avec domPDF
 * 
 * Ce fichier gère l'exportation PDF des rapports en utilisant domPDF
 */

// Inclure l'autoloader de Composer
require_once __DIR__ . '/../../vendor/autoload.php';

// Inclure la configuration
require_once __DIR__ . '/../../config/config.php';

// Importer domPDF
use Dompdf\Dompdf;
use Dompdf\Options;

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer le contenu HTML
$htmlContent = $_POST['content'] ?? '';
$filename = $_POST['filename'] ?? 'rapport.pdf';

if (empty($htmlContent)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Aucun contenu fourni']);
    exit;
}

try {
    // Configuration de domPDF
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isPhpEnabled', true);
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Arial');
    $options->set('defaultPaperSize', 'A4');
    $options->set('defaultPaperOrientation', 'portrait');
    $options->set('dpi', 96);
    $options->set('fontCache', __DIR__ . '/../../storage/cache/fonts');
    $options->set('tempDir', __DIR__ . '/../../storage/cache/temp');
    $options->set('chroot', __DIR__ . '/../../');

    // Créer l'instance domPDF
    $dompdf = new Dompdf($options);

    // Préparer le HTML avec les styles CSS
    $html = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rapport de stage</title>
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap");
            @import url("https://fonts.googleapis.com/css2?family=Candara:wght@300;400;500;600;700&display=swap");
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: "Candara", "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                line-height: 1.6;
                color: #1f2937;
                background-color: white;
                font-size: 12pt;
            }
            
            .rapport-template {
                padding: 40px;
                max-width: 100%;
                margin: 0;
            }
            
            .rapport-template * {
                color: #1f2937 !important;
                font-family: inherit !important;
            }
            
            .rapport-template img {
                display: block !important;
                max-width: 100% !important;
                height: auto !important;
            }
            
            .page-break {
                page-break-before: always !important;
                break-before: page !important;
                margin-top: 40px !important;
                padding-top: 40px !important;
                border-top: 2px solid #e5e7eb !important;
                min-height: 100px !important;
            }
            
            /* Styles pour les en-têtes */
            h1, h2, h3, h4, h5, h6 {
                margin-bottom: 1em;
                font-weight: bold;
                color: #1a5276 !important;
            }
            
            h1 { font-size: 24pt; }
            h2 { font-size: 20pt; }
            h3 { font-size: 16pt; }
            h4 { font-size: 14pt; }
            h5 { font-size: 12pt; }
            h6 { font-size: 11pt; }
            
            /* Styles pour les paragraphes */
            p {
                margin-bottom: 1em;
                text-align: justify;
            }
            
            /* Styles pour les listes */
            ul, ol {
                margin-bottom: 1em;
                padding-left: 2em;
            }
            
            li {
                margin-bottom: 0.5em;
            }
            
            /* Styles pour les tableaux */
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 1em;
            }
            
            th, td {
                border: 1px solid #d1d5db;
                padding: 8px;
                text-align: left;
            }
            
            th {
                background-color: #f3f4f6;
                font-weight: bold;
            }
            
            /* Styles pour les citations */
            blockquote {
                border-left: 4px solid #1a5276;
                padding-left: 1em;
                margin: 1em 0;
                font-style: italic;
                background-color: #f9fafb;
                padding: 1em;
            }
            
            /* Styles pour le code */
            code {
                background-color: #f3f4f6;
                padding: 2px 4px;
                border-radius: 3px;
                font-family: "Courier New", monospace;
                font-size: 0.9em;
            }
            
            pre {
                background-color: #f3f4f6;
                padding: 1em;
                border-radius: 5px;
                overflow-x: auto;
                margin: 1em 0;
                font-family: "Courier New", monospace;
                font-size: 0.9em;
            }
            
            /* Styles pour les liens */
            a {
                color: #1a5276;
                text-decoration: underline;
            }
            
            /* Styles pour les images */
            img {
                max-width: 100%;
                height: auto;
                display: block;
                margin: 1em auto;
            }
            
            /* Styles pour les sauts de page */
            @media print {
                .page-break {
                    page-break-before: always !important;
                    break-before: page !important;
                }
                
                body {
                    font-size: 11pt;
                }
                
                h1 { font-size: 18pt; }
                h2 { font-size: 16pt; }
                h3 { font-size: 14pt; }
                h4 { font-size: 12pt; }
                h5 { font-size: 11pt; }
                h6 { font-size: 10pt; }
            }
        </style>
    </head>
    <body>
        <div class="rapport-template">
            ' . $htmlContent . '
        </div>
    </body>
    </html>';

    // Charger le HTML dans domPDF
    $dompdf->loadHtml($html);

    // Rendre le PDF
    $dompdf->render();

    // Générer le nom de fichier sécurisé
    $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
    if (empty($safeFilename)) {
        $safeFilename = 'rapport_' . date('Y-m-d_H-i-s');
    }
    $safeFilename .= '.pdf';

    // En-têtes pour le téléchargement
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output du PDF
    echo $dompdf->output();

} catch (Exception $e) {
    // En cas d'erreur, retourner une réponse JSON
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur lors de la génération du PDF: ' . $e->getMessage()
    ]);
}
?> 