<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Accès refusé. Veuillez vous reconnecter.";
    exit;
}

// Vérifier les paramètres
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    http_response_code(400);
    echo "Paramètres manquants.";
    exit;
}

$id = intval($_GET['id']);
$type = $_GET['type'];
$user_id = $_SESSION['user_id'];

try {
    // Récupérer les informations du fichier
    if ($type === 'Rapport') {
        $sql = "SELECT a.fichier_archive, re.nom_rapport 
                FROM archives a 
                JOIN rapport_etudiant re ON a.id_rapport_etd = re.id_rapport_etd 
                WHERE a.id_rapport_etd = ? AND a.id_utilisateur = ?";
    } elseif ($type === 'Compte rendu') {
        $sql = "SELECT a.fichier_archive, cr.nom_cr 
                FROM archives a 
                JOIN compte_rendu cr ON a.id_cr = cr.id_cr 
                WHERE a.id_cr = ? AND a.id_utilisateur = ?";
    } else {
        http_response_code(400);
        echo "Type de document invalide.";
        exit;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id, $user_id]);
    $file_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file_info || !$file_info['fichier_archive']) {
        http_response_code(404);
        echo "Fichier non trouvé ou accès refusé.";
        exit;
    }

    $file_path = $file_info['fichier_archive'];
    $file_name = $file_info['nom_rapport'] ?? $file_info['nom_cr'];

    // Construire le chemin complet du fichier
    // Les chemins dans la BDD sont "assets/uploads/rapports/fichier.pdf"
    // Mais les fichiers sont dans "pages/assets/uploads/rapports/fichier.pdf"
    if (!preg_match('/^[\/\\\\]/', $file_path)) {
        // Remplacer "assets/uploads/" par "pages/assets/uploads/"
        $file_path = str_replace('assets/uploads/', 'pages/assets/uploads/', $file_path);
        // Ajouter le chemin de base
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/GSCV/' . $file_path;
    }

    // Vérifier que le fichier existe
    error_log("Tentative d'accès au fichier: $file_path");
    if (!file_exists($file_path)) {
        error_log("Fichier non trouvé: $file_path");
        http_response_code(404);
        echo "Le fichier physique n'existe pas. Chemin: " . basename($file_path);
        exit;
    }

    // Définir les en-têtes pour le téléchargement
    $file_size = filesize($file_path);
    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
    
    // Déterminer le type MIME
    $mime_types = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain'
    ];
    
    $mime_type = $mime_types[$file_extension] ?? 'application/octet-stream';

    // En-têtes de téléchargement
    header('Content-Type: ' . $mime_type);
    // Utiliser le nom du fichier original avec l'extension correcte
    $download_filename = basename($file_name);
    if (!pathinfo($download_filename, PATHINFO_EXTENSION)) {
        $download_filename .= '.' . $file_extension;
    }
    header('Content-Disposition: attachment; filename="' . $download_filename . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Lire et envoyer le fichier
    readfile($file_path);
    exit;

} catch (PDOException $e) {
    error_log("Erreur téléchargement archive: " . $e->getMessage());
    http_response_code(500);
    echo "Erreur lors du téléchargement.";
    exit;
}
?> 