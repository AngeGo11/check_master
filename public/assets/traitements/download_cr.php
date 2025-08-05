<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit("Utilisateur non connecté.");
}

// Récupérer l'ID du compte rendu depuis la requête
$cr_id = $_GET['id'] ?? null;

if (!$cr_id) {
    http_response_code(400);
    exit("ID du compte rendu manquant.");
}

try {
    $pdo = DataBase::getConnection();
    
    // Récupérer les informations du compte rendu
    $stmt = $pdo->prepare("
        SELECT cr.fichier_cr, cr.nom_cr, cr.date_cr,
               e.nom_etd, e.prenom_etd
        FROM compte_rendu cr
        JOIN rendre rn ON rn.id_cr = cr.id_cr
        JOIN enseignants ens ON ens.id_ens = rn.id_ens
        JOIN valider v ON v.id_ens = ens.id_ens
        JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
        JOIN etudiants e ON e.num_etd = r.num_etd
        WHERE cr.id_cr = ? AND e.num_etd = (
            SELECT e2.num_etd 
            FROM etudiants e2 
            JOIN utilisateur u ON e2.email_etd = u.login_utilisateur 
            WHERE u.id_utilisateur = ?
        )
    ");
    
    $stmt->execute([$cr_id, $_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || empty($result['fichier_cr'])) {
        http_response_code(404);
        exit("Compte rendu non trouvé ou non autorisé.");
    }

    // Construire le chemin du fichier
    $file_path = __DIR__ . '/../../../storage/uploads/compte_rendu/' . $result['fichier_cr'];

    if (!file_exists($file_path)) {
        http_response_code(404);
        exit("Fichier introuvable sur le serveur.");
    }

    // Déterminer le type MIME
    $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_types = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'html' => 'text/html',
        'txt' => 'text/plain'
    ];
    
    $content_type = $mime_types[$file_extension] ?? 'application/octet-stream';

    // Générer un nom de fichier approprié
    $student_name = $result['nom_etd'] . '_' . $result['prenom_etd'];
    $date_cr = date('Y-m-d', strtotime($result['date_cr']));
    $filename = "Compte_rendu_{$student_name}_{$date_cr}.{$file_extension}";

    // Envoyer les headers
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Envoyer le fichier
    readfile($file_path);
    exit;

} catch (Exception $e) {
    error_log("Erreur lors du téléchargement du compte rendu: " . $e->getMessage());
    http_response_code(500);
    exit("Erreur lors du téléchargement du fichier.");
}
