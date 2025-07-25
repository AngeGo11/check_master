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

if (!isset($_POST['message_ids'])) {
    echo json_encode(['success' => false, 'error' => 'Aucun message sélectionné.']);
    exit;
}

$message_ids_json = $_POST['message_ids'];
$message_ids = json_decode($message_ids_json, true);

if (empty($message_ids)) {
    echo json_encode(['success' => false, 'error' => 'Aucun message valide sélectionné.']);
    exit;
}

$message_ids = array_map('intval', $message_ids);
$placeholders = implode(',', array_fill(0, count($message_ids), '?'));
$user_id = $_SESSION['user_id'];

$pdo->beginTransaction();

try {
    // Mettre à jour le statut des messages à 'supprimé'
    // On s'assure que l'utilisateur ne peut supprimer que ses propres messages
    $sql = "UPDATE messages SET statut = 'supprimé' WHERE id_message IN ($placeholders) AND destinataire_id = ?";
    $stmt = $pdo->prepare($sql);
    
    $params = array_merge($message_ids, [$user_id]);
    $stmt->execute($params);
    $updated_count = $stmt->rowCount();

    $pdo->commit();
    enregistrer_piste_audit($pdo, $user_id, 'messages', 'Suppression messages', $updated_count);

    if ($updated_count > 0) {
        echo json_encode(['success' => true, 'message' => "$updated_count message(s) ont été supprimé(s)."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucun message n\'a été supprimé. Il se peut qu\'ils n\'appartiennent pas à votre boîte de réception.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression de messages : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données.']);
}
?> 