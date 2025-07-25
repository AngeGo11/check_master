<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session expirée. Veuillez vous reconnecter.']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

if (!isset($_POST['doc_ids'])) {
    error_log("archiver_documents.php: Aucun doc_ids reçu");
    echo json_encode(['success' => false, 'error' => 'Aucun document sélectionné.']);
    exit;
}

error_log("archiver_documents.php: doc_ids reçu: " . $_POST['doc_ids']);

$doc_ids_json = $_POST['doc_ids'];
$doc_ids_array = json_decode($doc_ids_json, true);

if (empty($doc_ids_array)) {
    echo json_encode(['success' => false, 'error' => 'Aucun document valide sélectionné.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$date = date('Y-m-d H:i:s');

// Vérifier que l'utilisateur existe
$check_user_sql = "SELECT COUNT(*) FROM utilisateur WHERE id_utilisateur = ?";
$check_user_stmt = $pdo->prepare($check_user_sql);
$check_user_stmt->execute([$user_id]);
if ($check_user_stmt->fetchColumn() == 0) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé dans la base de données.']);
    exit;
}

    // Récupérer l'année académique actuelle
    try {
        $sql = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1";
        $stmt = $pdo->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            echo json_encode(['success' => false, 'error' => 'Aucune année académique en cours trouvée.']);
            exit;
        }
        
        $id_ac = strval($result['id_ac']); // Convertir en string car id_ac est varchar(10)
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération de l\'année académique: ' . $e->getMessage()]);
        exit;
    }

$pdo->beginTransaction();

try {
    $archived_count = 0;
    $errors = [];

    error_log("Traitement de " . count($doc_ids_array) . " documents");
    foreach ($doc_ids_array as $composite_id) {
        error_log("Traitement du document: $composite_id");
        if (strpos($composite_id, ':') !== false) {
            list($type, $id) = explode(':', $composite_id, 2);
            $id = intval($id);

            // Vérifier si le document existe et s'il est déjà archivé par cet utilisateur
            if ($type === 'Rapport') {
                $check_sql = "SELECT COUNT(*) FROM archives WHERE id_rapport_etd = ? AND id_utilisateur = ?";
                $file_sql = "SELECT fichier_rapport FROM rapport_etudiant WHERE id_rapport_etd = ?";
                $exists_sql = "SELECT COUNT(*) FROM rapport_etudiant WHERE id_rapport_etd = ?";
            } elseif ($type === 'Compte rendu') {
                $check_sql = "SELECT COUNT(*) FROM archives WHERE id_cr = ? AND id_utilisateur = ?";
                $file_sql = "SELECT fichier_cr FROM compte_rendu WHERE id_cr = ?";
                $exists_sql = "SELECT COUNT(*) FROM compte_rendu WHERE id_cr = ?";
            } else {
                $errors[] = "Type de document invalide pour l'ID $id";
                continue;
            }

            // Vérifier que le document existe
            $exists_stmt = $pdo->prepare($exists_sql);
            $exists_stmt->execute([$id]);
            if ($exists_stmt->fetchColumn() == 0) {
                $errors[] = "Le document $id ($type) n'existe pas dans la base de données.";
                continue;
            }

            // Vérifier si déjà archivé
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$id, $user_id]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $errors[] = "Le document $id ($type) est déjà archivé.";
                error_log("Document $id ($type) déjà archivé par utilisateur $user_id");
                continue;
            }

            // Récupérer le chemin du fichier
            $file_stmt = $pdo->prepare($file_sql);
            $file_stmt->execute([$id]);
            $fichier = $file_stmt->fetchColumn();

            if (!$fichier) {
                $errors[] = "Fichier non trouvé pour le document $id ($type).";
                continue;
            }

            // Vérifier que le fichier existe physiquement (optionnel pour le moment)
            $file_path = $_SERVER['DOCUMENT_ROOT'] . '/GSCV/' . $fichier;
            if (!file_exists($file_path)) {
                error_log("Fichier physique non trouvé: $file_path");
                // On continue quand même car le chemin peut être relatif
            }

            // Insérer dans les archives
            if ($type === 'Rapport') {
                $insert_sql = "INSERT INTO archives (id_rapport_etd, date_archivage, id_utilisateur, id_ac, fichier_archive) VALUES (?, ?, ?, ?, ?)";
            } else {
                $insert_sql = "INSERT INTO archives (id_cr, date_archivage, id_utilisateur, id_ac, fichier_archive) VALUES (?, ?, ?, ?, ?)";
            }

            $insert_stmt = $pdo->prepare($insert_sql);
            
            // Debug: afficher les valeurs avant insertion
            error_log("Tentative d'archivage - ID: $id, Type: $type, Date: $date, User: $user_id, AC: $id_ac, Fichier: $fichier");
            
            $result = $insert_stmt->execute([$id, $date, $user_id, $id_ac, $fichier]);

            if ($result && $insert_stmt->rowCount() > 0) {
                $archived_count++;
                error_log("Document $id ($type) archivé avec succès par utilisateur $user_id");
            } else {
                $error_info = $insert_stmt->errorInfo();
                $errors[] = "Erreur lors de l'archivage du document $id ($type): " . $error_info[2];
                error_log("Erreur archivage document $id ($type): " . $error_info[2]);
                error_log("SQL: $insert_sql");
                error_log("Paramètres: " . json_encode([$id, $date, $user_id, $id_ac, $fichier]));
            }
        }
    }

    $pdo->commit();

    $message = "$archived_count document(s) archivé(s) avec succès.";
    if (!empty($errors)) {
        $message .= " Erreurs : " . implode(', ', $errors);
    }

    if ($archived_count > 0) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucun document n\'a été archivé. ' . implode(', ', $errors)]);
    }

} catch (PDOException $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    $pdo->rollBack();
    error_log("Erreur archivage documents: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données lors de l\'archivage: ' . $e->getMessage()]);
}

?> 