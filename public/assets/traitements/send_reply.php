<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
require_once __DIR__ . '/messages_functions.php';

header('Content-Type: application/json');

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Utilisateur non connecté');
    }

    // Vérifier si la requête est en POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupérer et valider les données
    $message_id = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
    $destinataire_id = filter_input(INPUT_POST, 'destinataire_id', FILTER_VALIDATE_INT);
    $contenu = trim($_POST['contenu'] ?? '');

    if (!$message_id || !$destinataire_id || empty($contenu)) {
        throw new Exception('Données invalides');
    }

    // Vérifier que l'utilisateur a le droit de répondre à ce message
    $sql = "SELECT * FROM messages WHERE id_message = ? AND (expediteur_id = ? OR destinataire_id = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$message_id, $_SESSION['user_id'], $_SESSION['user_id']]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        throw new Exception('Message non trouvé ou accès non autorisé');
    }

    // Envoyer la réponse
    $result = sendMessage(
        $_SESSION['user_id'],
        $destinataire_id,
        'Re: ' . getMessageSubject($message_id),
        $contenu,
        'chat',
        'reponse'
    );

    if (!$result['success']) {
        throw new Exception($result['message']);
    }

    // Mettre à jour le statut du message original
    $sql = "UPDATE messages SET statut = 'répondu' WHERE id_message = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$message_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Réponse envoyée avec succès',
        'message_id' => $result['message_id']
    ]);

} catch (Exception $e) {
    error_log("Erreur dans send_reply.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Fonction pour récupérer le sujet du message original
function getMessageSubject($message_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT objet FROM messages WHERE id_message = ?");
    $stmt->execute([$message_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['objet'] : 'Sans objet';
} 