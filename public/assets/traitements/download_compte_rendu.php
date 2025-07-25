<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "ID manquant";
    exit;
}

try {
    $crId = $_GET['id'];
    
    // Récupérer les informations du compte rendu
    $query = "SELECT cr.*, r.nom_rapport, et.nom_etd, et.prenom_etd
              FROM compte_rendu cr
              JOIN rapport_etudiant r ON cr.id_rapport_etd = r.id_rapport_etd
              JOIN etudiants et ON r.num_etd = et.num_etd
              WHERE cr.id_cr = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$crId]);
    $compteRendu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$compteRendu) {
        http_response_code(404);
        echo "Compte rendu non trouvé";
        exit;
    }
    
    $filePath = "C:/wamp64/www/GSCV/pages/" . "". $compteRendu['fichier_cr'];;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo "Fichier non trouvé";
        exit;
    }
    
    $fileName = basename($filePath);
    $fileSize = filesize($filePath);
    $fileType = mime_content_type($filePath);
    
    // Générer un nom de fichier personnalisé
    $studentName = $compteRendu['nom_etd'] . '_' . $compteRendu['prenom_etd'];
    $reportName = $compteRendu['nom_rapport'];
    $customFileName = 'Compte_rendu_' . $studentName . '_' . $reportName . '.pdf';
    
    // Nettoyer le nom de fichier
    $customFileName = preg_replace('/[^a-zA-Z0-9_\-\s\.]/', '', $customFileName);
    $customFileName = str_replace(' ', '_', $customFileName);
    
    // Mode téléchargement - forcer le téléchargement
    header('Content-Type: ' . $fileType);
    header('Content-Length: ' . $fileSize);
    header('Content-Disposition: attachment; filename="' . $customFileName . '"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Lire et envoyer le fichier
    readfile($filePath);
    exit;
    
} catch (PDOException $e) {
    error_log("Erreur lors du téléchargement du compte rendu : " . $e->getMessage());
    http_response_code(500);
    echo "Erreur lors du téléchargement";
}
