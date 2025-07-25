<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupérer les données JSON
$data = json_decode(file_get_contents('php://input'), true);
$messageIds = $data['messageIds'] ?? [];

error_log("Données reçues pour l'archivage : " . print_r($data, true));
error_log("IDs des messages à archiver : " . print_r($messageIds, true));
error_log("ID de l'utilisateur connecté : " . $_SESSION['user_id']);

if (empty($messageIds)) {
    echo json_encode(['success' => false, 'message' => 'Aucun message sélectionné']);
    exit;
}



try {
    // Préparer la requête pour mettre à jour le statut des messages
    $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
    $sql = "UPDATE messages SET statut = 'archivé' WHERE id_message IN ($placeholders) AND (destinataire_id = ? OR expediteur_id = ?)";
    
    error_log("Requête SQL : " . $sql);
    error_log("Paramètres : " . print_r(array_merge($messageIds, [$_SESSION['user_id'], $_SESSION['user_id']]), true));
    
    $stmt = $pdo->prepare($sql);
    $params = array_merge($messageIds, [$_SESSION['user_id'], $_SESSION['user_id']]);
    $stmt->execute($params);

    error_log("Nombre de lignes affectées : " . $stmt->rowCount());

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Messages archivés avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Aucun message n\'a pu être archivé'
        ]);
    }
} catch (PDOException $e) {
    error_log("Erreur d'archivage des messages : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'archivage des messages'
    ]);
} 