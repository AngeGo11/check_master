<?php
session_start();
require_once '../../../config/db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Accès non autorisé');
}

// Vérifier si l'ID est fourni
if (!isset($_POST['id']) || empty($_POST['id'])) {
    http_response_code(400);
    die('ID manquant');
}

$id = intval($_POST['id']);

try {
    // Récupérer les informations du compte rendu
    $stmt = $pdo->prepare("
        SELECT 
            cr.*,
            e.nom_etd,
            e.prenom_etd,
            r.nom_rapport,
            r.theme_memoire,
            d.date_depot
        FROM compte_rendu cr
        JOIN rapport_etudiant r ON r.id_rapport_etd = cr.id_rapport_etd
        JOIN etudiants e ON e.num_etd = r.num_etd
        JOIN deposer d ON d.id_rapport_etd = r.id_rapport_etd
        WHERE cr.id_cr = ?
    ");

    $stmt->execute([$id]);
    $compte_rendu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compte_rendu) {
        http_response_code(404);
        die('Compte rendu non trouvé');
    }

    // Construire le chemin complet du fichier
    // Utiliser le chemin absolu pour plus de fiabilité
    $basePath = dirname(dirname(dirname(__FILE__))); // Remonte jusqu'au répertoire pages/
    $filePath = $basePath . '/' . $compte_rendu['fichier_cr'];
    
    // Pour le débogage, afficher les chemins
    error_log("Chemin relatif dans BDD: " . $compte_rendu['fichier_cr']);
    error_log("Chemin complet construit: " . $filePath);
    error_log("Chemin absolu: " . realpath($filePath));

    // Vérifier si le fichier existe
    if (empty($compte_rendu['fichier_cr']) || !file_exists($filePath)) {
        http_response_code(404);
        die('Fichier du compte rendu non trouvé. Chemin: ' . $filePath);
    }

    // Vérifier le type MIME du fichier
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    // Vérifier que c'est bien un PDF
    if ($mime_type !== 'application/pdf') {
        http_response_code(400);
        die('Le fichier n\'est pas un PDF valide. Type MIME: ' . $mime_type);
    }

    // Lire le contenu du fichier PDF
    $pdf_content = file_get_contents($filePath);
    
    if ($pdf_content === false) {
        http_response_code(500);
        die('Erreur lors de la lecture du fichier');
    }

    // Envoyer les en-têtes appropriés
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($compte_rendu['fichier_cr']) . '"');
    header('Content-Length: ' . strlen($pdf_content));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('Expires: 0');

    // Afficher le PDF
    echo $pdf_content;
    
} catch (PDOException $e) {
    error_log('Erreur lors de la récupération du compte rendu: ' . $e->getMessage());
    http_response_code(500);
    die('Une erreur est survenue lors de la récupération du compte rendu');
} catch (Exception $e) {
    error_log('Erreur générale lors de la prévisualisation: ' . $e->getMessage());
    http_response_code(500);
    die('Une erreur inattendue est survenue');
}
