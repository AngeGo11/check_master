<?php
// upload_report.php
session_start();
header('Content-Type: application/json');

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier le chemin du fichier de configuration
$configPath = 'C:/wamp64/www/GSCV+/app/config/config.php';
if (!file_exists($configPath)) {
    echo json_encode(['success' => false, 'message' => 'Fichier de configuration non trouvé: ' . $configPath]);
    exit;
}

require_once $configPath;

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    $student_id = (int)$_POST['student_id'];
    $theme_memoire = $_POST['theme_memoire'] ?? null;
    $file_path = $_POST['file_path'] ?? null;
    $name_report = $_SESSION['name_report'] ?? null;

    // Récupérer les informations de l'étudiant
    $student_query = $pdo->prepare("SELECT nom_etd, prenom_etd FROM etudiants WHERE num_etd = ?");
    $student_query->execute([$student_id]);
    $student_info = $student_query->fetch(PDO::FETCH_ASSOC);

    // Générer le nom du fichier au format nom_étudiant + prénom + date.pdf
    $new_filename = $student_info['nom_etd'] . '_' . $student_info['prenom_etd'] . '_' . date('Y-m-d') . '.pdf';

    /* 1. Vérification du fichier */
    $file = $_FILES['report-file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors de l\'upload: ' . ($file ? $file['error'] : 'Aucun fichier reçu'));
    }

    // Vérifier que c'est bien un fichier PDF
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mime_type !== 'application/pdf') {
        throw new Exception('Le fichier doit être au format PDF (type reçu: ' . $mime_type . ')');
    }

    /* 2. Déplacement du fichier */
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($ext !== 'pdf') {
        throw new Exception('L\'extension du fichier doit être .pdf (extension reçue: ' . $ext . ')');
    }

    // Définir le chemin de destination
    $uploadDir = __DIR__ . '/rapports/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception('Impossible de créer le dossier de destination: ' . $uploadDir);
        }
    }

    // Vérifier les permissions du dossier
    if (!is_writable($uploadDir)) {
        throw new Exception('Le dossier de destination n\'est pas accessible en écriture: ' . $uploadDir);
    }

    $dest = $uploadDir . $new_filename;
    // Chemin à stocker en BDD et à utiliser côté web
    $relativePath = '/GSCV+/storage/uploads/rapports/' . $new_filename;

    // Lire le contenu du fichier
    $content = file_get_contents($file['tmp_name']);
    if ($content === false) {
        throw new Exception('Impossible de lire le fichier temporaire: ' . $file['tmp_name']);
    }

    // Écrire le contenu
    if (!file_put_contents($dest, $content)) {
        throw new Exception('Impossible d\'écrire le fichier dans: ' . $dest);
    }

    /* 3. Insertion BDD */
    $pdo->beginTransaction();

    // Vérifier si un rapport existe déjà pour cet étudiant
    $checkSql = "SELECT * FROM rapport_etudiant WHERE num_etd = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$student_id]);
    $existingReport = $checkStmt->fetch();

    if ($existingReport) {
        // Mettre à jour le rapport existant
        $sql = "UPDATE rapport_etudiant 
                SET nom_rapport = :nom, 
                    date_rapport = CURDATE(), 
                    theme_memoire = :theme, 
                    statut_rapport = 'En attente d''approbation', 
                    fichier_rapport = :fichier 
                WHERE num_etd = :num";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':num' => $student_id,
            ':nom' => $name_report,
            ':theme' => $theme_memoire,
            ':fichier' => $relativePath
        ]);
        $rapportId = $existingReport['id_rapport_etd'];
    } else {
        // Insérer un nouveau rapport
        $sql = "INSERT INTO rapport_etudiant (num_etd, nom_rapport, date_rapport, theme_memoire, statut_rapport, fichier_rapport)
                VALUES (:num, :nom, CURDATE(), :theme, 'En attente d''approbation', :fichier)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':num' => $student_id,
            ':nom' => $name_report,
            ':theme' => $theme_memoire,
            ':fichier' => $relativePath
        ]);
        $rapportId = $pdo->lastInsertId();
    }

    // Mettre à jour ou insérer dans la table deposer
    $depotSql = "INSERT INTO deposer (num_etd, id_rapport_etd, date_depot)
                 VALUES (:num, :idRapport, CURDATE())
                 ON DUPLICATE KEY UPDATE date_depot = CURDATE()";
    $depotStmt = $pdo->prepare($depotSql);
    $depotStmt->execute([
        ':num' => $student_id,
        ':idRapport' => $rapportId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Rapport enregistré', 
        'file' => basename($file_path),
        'file_path' => $file_path
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (isset($dest) && file_exists($dest)) {
        unlink($dest);
    }
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'debug_info' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}