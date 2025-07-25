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

    // Vérifier si l'ID du message est fourni
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception('ID du message invalide');
    }

    $messageId = (int)$_GET['id'];
    $userId = $_SESSION['user_id'];

    // Récupérer les détails du message
    $sql = "SELECT m.*, 
            CASE 
                WHEN e_exp.nom_ens IS NOT NULL THEN CONCAT(e_exp.nom_ens, ' ', e_exp.prenoms_ens)
                WHEN et_exp.nom_etd IS NOT NULL THEN CONCAT(et_exp.nom_etd, ' ', et_exp.prenom_etd)
                WHEN pa_exp.nom_personnel_adm IS NOT NULL THEN CONCAT(pa_exp.nom_personnel_adm, ' ', pa_exp.prenoms_personnel_adm)
                ELSE u_exp.login_utilisateur
            END as expediteur_nom,
            u_exp.login_utilisateur as expediteur_email
            FROM messages m
            JOIN utilisateur u_exp ON m.expediteur_id = u_exp.id_utilisateur
            LEFT JOIN enseignants e_exp ON u_exp.login_utilisateur = e_exp.email_ens
            LEFT JOIN etudiants et_exp ON u_exp.login_utilisateur = et_exp.email_etd
            LEFT JOIN personnel_administratif pa_exp ON u_exp.login_utilisateur = pa_exp.email_personnel_adm
            WHERE m.id_message = ? AND (m.expediteur_id = ? OR m.destinataire_id = ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$messageId, $userId, $userId]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$message) {
        throw new Exception('Message non trouvé');
    }

    // Marquer le message comme lu si l'utilisateur est le destinataire et que le message n'est pas encore lu
    if ($message['destinataire_id'] == $userId && $message['statut'] == 'non lu') {
        $sql = "UPDATE messages SET statut = 'lu' WHERE id_message = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$messageId]);
        
        // Mettre à jour le statut dans le tableau retourné
        $message['statut'] = 'lu';
        
        // Log pour debug
        error_log("Message {$messageId} marqué comme lu pour l'utilisateur {$userId}");
    }

    echo json_encode($message);

} catch (Exception $e) {
    error_log("Erreur dans get_message.php: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}