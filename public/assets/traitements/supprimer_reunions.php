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

if (!isset($_POST['reunion_ids'])) {
    echo json_encode(['success' => false, 'error' => 'Aucune réunion sélectionnée.']);
    exit;
}

$reunion_ids_json = $_POST['reunion_ids'];
$reunion_ids = json_decode($reunion_ids_json, true);

if (empty($reunion_ids)) {
    echo json_encode(['success' => false, 'error' => 'Aucune réunion valide sélectionnée.']);
    exit;
}

$reunion_ids = array_map('intval', $reunion_ids);
$placeholders = implode(',', array_fill(0, count($reunion_ids), '?'));

$pdo->beginTransaction();

try {
    // 1. Supprimer les participants
    $sql_delete_participants = "DELETE FROM participants WHERE reunion_id IN ($placeholders)";
    $stmt_delete_participants = $pdo->prepare($sql_delete_participants);
    $stmt_delete_participants->execute($reunion_ids);

    // 2. Récupérer et supprimer les fichiers joints (si une table existe)
    // NOTE: Le schéma de la DB n'est pas clair sur le nom de la table des fichiers.
    // Je suppose qu'elle s'appelle 'fichiers_reunion'. A adapter si besoin.
    $sql_files = "SELECT chemin_fichier FROM fichiers_reunion WHERE reunion_id IN ($placeholders)";
    $stmt_files = $pdo->prepare($sql_files);
    $stmt_files->execute($reunion_ids);
    $files_to_delete = $stmt_files->fetchAll(PDO::FETCH_COLUMN);

    $sql_delete_files = "DELETE FROM fichiers_reunion WHERE reunion_id IN ($placeholders)";
    $pdo->prepare($sql_delete_files)->execute($reunion_ids);

    foreach ($files_to_delete as $file_path) {
        $absolute_path = realpath(__DIR__ . '/../../../' . $file_path);
        if ($absolute_path && file_exists($absolute_path)) {
            unlink($absolute_path);
        }
    }

    // 3. Supprimer les comptes rendus associés (si nécessaire)
    // Cela suppose que la suppression d'une réunion entraîne la suppression de son CR.
    $sql_delete_cr = "DELETE FROM compte_rendu WHERE reunion_id IN ($placeholders)";
    $pdo->prepare($sql_delete_cr)->execute($reunion_ids);


    // 4. Supprimer les réunions
    $sql_delete_reunions = "DELETE FROM reunions WHERE id IN ($placeholders)";
    $stmt_delete_reunions = $pdo->prepare($sql_delete_reunions);
    $stmt_delete_reunions->execute($reunion_ids);
    $deleted_count = $stmt_delete_reunions->rowCount();

    $pdo->commit();
    enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'reunions', 'Suppression réunions', $deleted_count);

    if ($deleted_count > 0) {
        echo json_encode(['success' => true, 'message' => "$deleted_count réunion(s) ont été supprimée(s)."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucune réunion n\'a été supprimée.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression de réunions : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données.']);
}
?> 