<?php
session_start();
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer l'ID du message
$message_id = $_POST['message_id'] ?? null;

if (!$message_id) {
    echo json_encode(['success' => false, 'error' => 'ID du message manquant']);
    exit;
}

try {
    // Connexion à la base de données
    require_once '../../../config/config.php';
    
    // Vérifier que le message archivé appartient à l'utilisateur connecté
    $sql = "SELECT id_message FROM messages WHERE id_message = ? AND destinataire_id = ? AND statut = 'archivé'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$message_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Message archivé non trouvé ou accès non autorisé']);
        exit;
    }
    
    // Restaurer le message (le remettre en statut 'lu')
    $sql = "UPDATE messages SET statut = 'lu' WHERE id_message = ? AND destinataire_id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$message_id, $_SESSION['user_id']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Message restauré avec succès']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la restauration']);
    }
    
} catch (PDOException $e) {
    error_log("Erreur restauration message: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données']);
} catch (Exception $e) {
    error_log("Erreur restauration message: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur inattendue']);
}
?> 