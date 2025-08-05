<?php
require_once '../../../config/db_connect.php';
require_once '../../../includes/audit_utils.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté.']);
    exit;
}

if (!isset($_POST['reclamation_ids'])) {
    echo json_encode(['success' => false, 'error' => 'Aucune réclamation sélectionnée.']);
    exit;
}

$reclamation_ids_json = $_POST['reclamation_ids'];
$reclamation_ids = json_decode($reclamation_ids_json, true);

if (empty($reclamation_ids)) {
    echo json_encode(['success' => false, 'error' => 'Aucune réclamation valide sélectionnée.']);
    exit;
}

$reclamation_ids = array_map('intval', $reclamation_ids);
$placeholders = implode(',', array_fill(0, count($reclamation_ids), '?'));

$pdo->beginTransaction();

try {
    // 1. Récupérer les chemins des fichiers à supprimer
    $sql_files = "SELECT chemin_fichier FROM fichiers_reclamation WHERE id_reclamation IN ($placeholders)";
    $stmt_files = $pdo->prepare($sql_files);
    $stmt_files->execute($reclamation_ids);
    $files_to_delete = $stmt_files->fetchAll(PDO::FETCH_COLUMN);

    // 2. Supprimer les entrées dans la table des fichiers
    $sql_delete_files = "DELETE FROM fichiers_reclamation WHERE id_reclamation IN ($placeholders)";
    $stmt_delete_files = $pdo->prepare($sql_delete_files);
    $stmt_delete_files->execute($reclamation_ids);

    // 3. Supprimer les réclamations
    $sql_delete_reclamations = "DELETE FROM reclamations WHERE id_reclamation IN ($placeholders)";
    $stmt_delete_reclamations = $pdo->prepare($sql_delete_reclamations);
    $stmt_delete_reclamations->execute($reclamation_ids);
    $deleted_count = $stmt_delete_reclamations->rowCount();

    // 4. Supprimer les fichiers physiques du serveur
    foreach ($files_to_delete as $file_path) {
        // Le chemin est relatif depuis la racine du projet, il faut ajuster si besoin.
        // Exemple: ../../../uploads/reclamations/fichier.pdf
        // __DIR__ est le dossier 'traitements', donc on remonte de 3 niveaux.
        $absolute_path = realpath(__DIR__ . '/../../../' . $file_path);
        if ($absolute_path && file_exists($absolute_path)) {
            unlink($absolute_path);
        }
    }

    $pdo->commit();

    if ($deleted_count > 0) {
        echo json_encode(['success' => true, 'message' => "$deleted_count réclamation(s) ont été supprimée(s)."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucune réclamation n\'a été supprimée.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression de réclamations : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données.']);
}
?> 