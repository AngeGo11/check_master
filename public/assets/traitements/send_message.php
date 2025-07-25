<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 0); // Désactiver l'affichage des erreurs pour éviter le HTML dans la réponse

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Définir le type de contenu avant toute sortie
header('Content-Type: application/json');

try {
    // Inclure les fichiers nécessaires
    require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
    require_once __DIR__ . '/messages_functions.php';
    require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/includes/audit_utils.php';

    // Logger la méthode de requête
    error_log("Méthode de requête reçue : " . $_SERVER['REQUEST_METHOD']);
    error_log("Données POST reçues : " . print_r($_POST, true));
    error_log("Session utilisateur : " . print_r($_SESSION, true));

    // Vérifier si la requête est en POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Vous devez être connecté pour envoyer un message');
    }

    // Récupérer et valider les données du formulaire
    $destinataire_id = filter_input(INPUT_POST, 'destinataire_id', FILTER_VALIDATE_INT);
    $objet = trim(filter_input(INPUT_POST, 'objet', FILTER_SANITIZE_STRING));
    $contenu = trim(filter_input(INPUT_POST, 'contenu', FILTER_SANITIZE_STRING));

    // Validation des données
    if (!$destinataire_id || $destinataire_id <= 0) {
        throw new Exception('Destinataire invalide');
    }

    if (empty($objet)) {
        throw new Exception('Le sujet du message est requis');
    }

    if (empty($contenu)) {
        throw new Exception('Le contenu du message est requis');
    }

    // Vérifier que l'utilisateur ne s'envoie pas un message à lui-même
    if ($_SESSION['user_id'] == $destinataire_id) {
        throw new Exception('Vous ne pouvez pas vous envoyer un message à vous-même');
    }

    error_log("Tentative d'envoi du message...");
    error_log("Expéditeur ID: " . $_SESSION['user_id']);
    error_log("Destinataire ID: " . $destinataire_id);

    // Envoyer le message
    $result = sendMessage(
        $_SESSION['user_id'],  // ID de l'expéditeur
        $destinataire_id,      // ID du destinataire
        $objet,                // Sujet du message
        $contenu,              // Contenu du message
        'envoyé'               // Statut initial
    );

    // Enregistrer la piste d'audit
    if ($result['success']) {
        enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'messages', 'Envoi message', 1);
    }

    error_log("Résultat de l'envoi : " . print_r($result, true));
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Exception lors de l'envoi : " . $e->getMessage());
    error_log("Stack trace : " . $e->getTraceAsString());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>