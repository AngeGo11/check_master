<?php
// upload_bilan.php

session_start();
header('Content-Type: application/json');

// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier le chemin du fichier de configuration
$configPath = 'C:/wamp64/www/GSCV/config/db_connect.php';
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
    $report_id = (int)$_POST['report_id'];
    $theme_memoire = $_POST['theme_memoire'] ?? null;
    $file_path = $_POST['file_path'] ?? null;
    $name_bilan = $_SESSION['name_bilan'] ?? 'Compte rendu du ' . date('Y-m-d');

    // Récupérer les informations de l'étudiant
    $student_query = $pdo->prepare("SELECT nom_etd, prenom_etd FROM etudiants WHERE num_etd = ?");
    $student_query->execute([$student_id]);
    $student_info = $student_query->fetch(PDO::FETCH_ASSOC);

    // Générer le nom du fichier au format nom_étudiant + prénom + date.pdf
    $new_filename = $student_info['nom_etd'] . '_' . $student_info['prenom_etd'] . '_' . date('Y-m-d') . '.pdf';

    /* 1. Vérification du fichier */
    $file = $_FILES['bilan-file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors de l\'upload: ' . ($file ? $file['error'] : 'Aucun fichier reçu'));
    }

    // Vérifier que c'est bien un fichier PDF
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Liste des types MIME acceptés pour les PDF
    $allowed_mime_types = [
        'application/pdf',
        'application/x-pdf',
        'application/acrobat',
        'application/vnd.pdf',
        'text/pdf',
        'text/x-pdf'
    ];

    if (!in_array($mime_type, $allowed_mime_types)) {
        throw new Exception('Le fichier doit être au format PDF (type reçu: ' . $mime_type . ')');
    }

    /* 2. Déplacement du fichier */
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        throw new Exception('L\'extension du fichier doit être .pdf (extension reçue: ' . $ext . ')');
    }

    // Définir le chemin de destination
    $uploadDir = 'C:/wamp64/www/GSCV/pages/assets/uploads/compte_rendu/';
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
    $relativePath = 'assets/uploads/compte_rendu/' . $new_filename;

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

    // Vérifier si un compte rendu existe déjà pour ce rapport
    $checkSql = "SELECT id_cr FROM compte_rendu cr WHERE id_rapport_etd = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$report_id]);
    $existingBilan = $checkStmt->fetch();

    if ($existingBilan) {
        // Mettre à jour le compte rendu existant
        $sql = "UPDATE compte_rendu 
                SET nom_cr = :nom, 
                    date_cr = CURDATE(), 
                    fichier_cr = :fichier 
                WHERE id_rapport_etd = :id_rapport_etd";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_rapport_etd' => $report_id,
            ':nom' => $name_bilan,
            ':fichier' => $relativePath
        ]);
        $bilanId = $existingBilan['id_cr'];
    } else {
        // Insérer un nouveau compte rendu
        $sql = "INSERT INTO compte_rendu (id_rapport_etd, nom_cr, date_cr, fichier_cr)
                VALUES (:id_rapport_etd, :nom, CURDATE(), :fichier)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id_rapport_etd' => $report_id,
            ':nom' => $name_bilan,
            ':fichier' => $relativePath
        ]);
        $bilanId = $pdo->lastInsertId();
    }

    // Mettre à jour ou insérer dans la table rendre
    $rendreSql = "INSERT INTO rendre (id_cr, id_ens, date_env)
                 VALUES (:idCr, :idEns, CURDATE())
                 ON DUPLICATE KEY UPDATE date_env = CURDATE()";
    $rendreStmt = $pdo->prepare($rendreSql);
    $rendreStmt->execute([
        ':idCr' => $bilanId,
        ':idEns' => $_SESSION['user_id']
    ]);

    $pdo->commit();

    // Enregistrer le message de succès en session
    $_SESSION['success_message'] = 'Compte rendu crée avec succès.';

    echo json_encode([
        'success' => true, 
        'message' => 'Compte rendu enregistré', 
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





