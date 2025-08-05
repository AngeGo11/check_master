<?php
// Démarrer la session
session_start();

require_once __DIR__ . '/../../../config/config.php';

// Établir la connexion à la base de données
$pdo = DataBase::getConnection();

// Vérification de sécurité
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé - Session non valide']);
    exit;
}

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'bulk_archive':
            handleBulkArchive();
            break;
        case 'archive_single':
            handleSingleArchive();
            break;
        default:
            throw new Exception('Action non reconnue');
    }
} catch (Exception $e) {
    error_log('Erreur archivage documents: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleBulkArchive() {
    global $pdo;
    
    $documents = json_decode($_POST['documents'], true);
    if (!$documents || !is_array($documents)) {
        throw new Exception('Données de documents invalides');
    }
    
    $pdo->beginTransaction();
    $successCount = 0;
    $errors = [];
    
    try {
        foreach ($documents as $doc) {
            $documentId = $doc['id'];
            $documentType = $doc['type'];
            
            // Déterminer la table source selon le type
            if ($documentType === 'Rapport') {
                $sourceTable = 'rapport_etudiant';
                $idColumn = 'id_rapport_etd';
            } elseif ($documentType === 'Compte rendu') {
                $sourceTable = 'compte_rendu';
                $idColumn = 'id_cr';
            } else {
                $errors[] = "Type de document non reconnu: $documentType";
                continue;
            }
            
            // Vérifier si le document existe et n'est pas déjà archivé
            $stmt = $pdo->prepare("SELECT * FROM $sourceTable WHERE $idColumn = ?");
            $stmt->execute([$documentId]);
            $document = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$document) {
                $errors[] = "Document $documentId non trouvé";
                continue;
            }
            
            // Vérifier si le document n'est pas déjà archivé
            $stmt = $pdo->prepare("SELECT id_archives FROM archives WHERE $idColumn = ?");
            $stmt->execute([$documentId]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Document $documentId déjà archivé";
                continue;
            }
            
            // Insérer dans la table archives
            $archiveData = [
                'id_utilisateur' => $_SESSION['user_id'],
                'date_archivage' => date('Y-m-d H:i:s'),
                'fichier_archive' => $document['fichier_rapport'] ?? $document['fichier_cr'] ?? null
            ];
            
            // Ajouter l'ID du document selon le type
            if ($documentType === 'Rapport') {
                $archiveData['id_rapport_etd'] = $documentId;
                $archiveData['id_cr'] = null;
            } else {
                $archiveData['id_cr'] = $documentId;
                $archiveData['id_rapport_etd'] = null;
            }
            
            // Récupérer l'année académique actuelle
            $stmt = $pdo->prepare("SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1");
            $stmt->execute();
            $annee = $stmt->fetch(PDO::FETCH_ASSOC);
            $archiveData['id_ac'] = $annee ? $annee['id_ac'] : date('Y');
            
            $columns = implode(', ', array_keys($archiveData));
            $placeholders = ':' . implode(', :', array_keys($archiveData));
            
            $stmt = $pdo->prepare("INSERT INTO archives ($columns) VALUES ($placeholders)");
            $stmt->execute($archiveData);
            
           
            
            $successCount++;
        }
        
        $pdo->commit();
        
        $message = "$successCount document(s) archivé(s) avec succès";
        if (!empty($errors)) {
            $message .= ". Erreurs: " . implode(', ', $errors);
        }
        
        echo json_encode(['success' => true, 'message' => $message, 'archived_count' => $successCount, 'errors' => $errors]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function handleSingleArchive() {
    global $pdo;
    
    $documentId = $_POST['document_id'] ?? '';
    $documentType = $_POST['document_type'] ?? '';
    
    if (!$documentId || !$documentType) {
        throw new Exception('ID et type de document requis');
    }
    
    // Déterminer la table source selon le type
    if ($documentType === 'Rapport') {
        $sourceTable = 'rapport_etudiant';
        $idColumn = 'id_rapport_etd';
    } elseif ($documentType === 'Compte rendu') {
        $sourceTable = 'compte_rendu';
        $idColumn = 'id_cr';
    } else {
        throw new Exception('Type de document non reconnu');
    }
    
    // Vérifier si le document existe
    $stmt = $pdo->prepare("SELECT * FROM $sourceTable WHERE $idColumn = ?");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$document) {
        throw new Exception('Document non trouvé');
    }
    
    // Vérifier si le document n'est pas déjà archivé
    $stmt = $pdo->prepare("SELECT id_archives FROM archives WHERE $idColumn = ?");
    $stmt->execute([$documentId]);
    if ($stmt->rowCount() > 0) {
        throw new Exception('Document déjà archivé');
    }
    
    $pdo->beginTransaction();
    
    try {
        
        // Insérer dans la table archives
        $archiveData = [
            'id_utilisateur' => $_SESSION['user_id'],
            'date_archivage' => date('Y-m-d H:i:s'),
            'fichier_archive' => $document['fichier_rapport'] ?? $document['fichier_cr'] ?? null
        ];
        
        // Ajouter l'ID du document selon le type
        if ($documentType === 'Rapport') {
            $archiveData['id_rapport_etd'] = $documentId;
            $archiveData['id_cr'] = null;
        } else {
            $archiveData['id_cr'] = $documentId;
            $archiveData['id_rapport_etd'] = null;
        }
        
        // Récupérer l'année académique actuelle
        $stmt = $pdo->prepare("SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1");
        $stmt->execute();
        $annee = $stmt->fetch(PDO::FETCH_ASSOC);
        $archiveData['id_ac'] = $annee ? $annee['id_ac'] : date('Y');
        
        $columns = implode(', ', array_keys($archiveData));
        $placeholders = ':' . implode(', :', array_keys($archiveData));
        
        $stmt = $pdo->prepare("INSERT INTO archives ($columns) VALUES ($placeholders)");
        $stmt->execute($archiveData);
        
        // Marquer comme archivé dans la table source (si les colonnes existent)
        try {
            $stmt = $pdo->prepare("UPDATE $sourceTable SET statut_archivage = 'archivé', date_archivage = ? WHERE $idColumn = ?");
            $stmt->execute([date('Y-m-d H:i:s'), $documentId]);
        } catch (Exception $e) {
            // Si les colonnes n'existent pas, on continue sans erreur
            error_log("Colonnes d'archivage non disponibles dans $sourceTable: " . $e->getMessage());
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Document archivé avec succès']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?> 