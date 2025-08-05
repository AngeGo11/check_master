<?php
session_start();

// Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

// Vérification des permissions (commission)
$allowedGroups = [5, 6, 7, 8, 9]; // Enseignant, Responsable niveau, Responsable filière, Administrateur, Commission
$userGroups = $_SESSION['user_groups'] ?? [];

$hasAccess = false;
foreach ($userGroups as $group) {
    if (in_array($group, $allowedGroups)) {
        $hasAccess = true;
        break;
    }
}

if (!$hasAccess) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/Models/ConsultationModel.php';

// Initialiser la connexion PDO
$pdo = DataBase::getConnection();
$model = new ConsultationModel();

// Fonction pour corriger le chemin du fichier
function getCorrectFilePath($dbPath) {
    // Utiliser un chemin absolu depuis la racine du projet
    $rootPath = dirname(__DIR__, 3);
    $fullPath = $rootPath . '/' . $dbPath;
    return $fullPath;
}

// Récupérer l'action
$action = $_GET['action'] ?? '';

if ($action === 'download_cr') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID du compte rendu manquant']);
        exit();
    }

    try {
        $compteRendu = $model->getCompteRenduById($id);
        
        if (!$compteRendu) {
            echo json_encode(['success' => false, 'error' => 'Compte rendu non trouvé']);
            exit();
        }

        $filePath = getCorrectFilePath($compteRendu['fichier_cr']);
        
        if (!file_exists($filePath)) {
            echo json_encode(['success' => false, 'error' => 'Fichier non trouvé: ' . $filePath]);
            exit();
        }

        // Déterminer le type MIME
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'html' => 'text/html'
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        // Nettoyer tout buffer de sortie et s'assurer qu'aucun contenu n'est envoyé
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Vérifier qu'aucun en-tête n'a été envoyé
        if (headers_sent($file, $line)) {
            error_log("Headers already sent in $file:$line");
            exit();
        }
        
        // En-têtes pour le téléchargement
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Lire et envoyer le fichier
        readfile($filePath);
        exit();

    } catch (Exception $e) {
        error_log("gestion_comptes_rendus.php::download_cr - Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement: ' . $e->getMessage()]);
    }

} elseif ($action === 'view_cr') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID du compte rendu manquant']);
        exit();
    }

    try {
        $compteRendu = $model->getCompteRenduById($id);
        
        if (!$compteRendu) {
            echo json_encode(['success' => false, 'error' => 'Compte rendu non trouvé']);
            exit();
        }

        $filePath = getCorrectFilePath($compteRendu['fichier_cr']);
        
        if (!file_exists($filePath)) {
            echo json_encode(['success' => false, 'error' => 'Fichier non trouvé: ' . $filePath]);
            exit();
        }

        // Déterminer le type MIME
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'html' => 'text/html'
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        // Nettoyer tout buffer de sortie et s'assurer qu'aucun contenu n'est envoyé
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Vérifier qu'aucun en-tête n'a été envoyé
        if (headers_sent($file, $line)) {
            error_log("Headers already sent in $file:$line");
            exit();
        }
        
        // En-têtes pour l'affichage
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        // Lire et envoyer le fichier
        readfile($filePath);
        exit();

    } catch (Exception $e) {
        error_log("gestion_comptes_rendus.php::view_cr - Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'affichage: ' . $e->getMessage()]);
    }

} else {
    // Action non reconnue
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
    exit();
}
?> 