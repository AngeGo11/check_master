<?php

require_once 'db_connect.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';


// ACTIVER les erreurs temporairement pour voir le problème
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Nettoyer le buffer de sortie - REMOVED FOR DEBUGGING
// if (ob_get_level()) {
//     ob_clean();
// }

// Définir le type de contenu
header('Content-Type: application/json; charset=utf-8');

// Fonction pour logger les étapes
function logStep($step, $data = null) {
    error_log("STEP: $step" . ($data ? " - " . json_encode($data) : ""));
}

try {
    logStep("1. Démarrage du script");
    logStep("DEBUG: DOCUMENT_ROOT est " . $_SERVER['DOCUMENT_ROOT']);
    
    session_start();
    logStep("2. Session démarrée");
    
    // Définir les chemins absolus des fichiers requis
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] . '/GSCV';
    
    $requiredFiles = [
        'db_connect' => $documentRoot . '/config/db_connect.php',
        'phpmailer_exception' => $documentRoot . '/PHPMailer/src/Exception.php',
        'phpmailer' => $documentRoot . '/PHPMailer/src/PHPMailer.php',
        'phpmailer_smtp' => $documentRoot . '/PHPMailer/src/SMTP.php'
    ];
    
    // Vérifier l'existence des fichiers avant de les inclure et les inclure
    // NOTE: require_once already handles this at the top of the file
    // This loop is redundant for now and can be removed later if needed
    // but for debugging it's useful to log if files are missing.
    foreach ($requiredFiles as $name => $file) {
        if (!file_exists($file)) {
            logStep("DEBUG: Fichier manquant dans requiredFiles array: $name ($file)");
            // Optionally throw an exception here if you want to strictly check
        }
    }

    // Vérifier la connexion DB
    if (!isset($pdo)) {
        throw new Exception('Variable $pdo non définie dans db_connect.php');
    }
    
    if (!($pdo instanceof PDO)) {
        throw new Exception('$pdo n\'est pas une instance PDO valide');
    }
    logStep("4. Connexion DB vérifiée");
    
    // Vérifier la méthode de requête
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée: ' . $_SERVER['REQUEST_METHOD']);
    }
    logStep("5. Méthode POST confirmée");
    
    // Récupérer et valider les données
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $crId = filter_input(INPUT_POST, 'cr_id', FILTER_VALIDATE_INT);
    
    logStep("6. Données récupérées", [
        'email' => $email,
        'subject' => $subject ? 'présent' : 'absent',
        'message' => $message ? 'présent' : 'absent',
        'cr_id' => $crId
    ]);
    
    if (!$email || !$subject || !$crId) {
        throw new Exception('Données manquantes ou invalides - Email: ' . ($email ? 'OK' : 'KO') . 
                          ', Subject: ' . ($subject ? 'OK' : 'KO') . 
                          ', CR_ID: ' . ($crId ? 'OK' : 'KO'));
    }
    
    // Récupérer les informations du compte rendu
    $stmt = $pdo->prepare("
        SELECT cr.*, r.nom_rapport, e.nom_etd, e.prenom_etd, cr.fichier_cr
        FROM compte_rendu cr
        JOIN rapport_etudiant r ON r.id_rapport_etd = cr.id_rapport_etd
        JOIN etudiants e ON e.num_etd = r.num_etd
        WHERE cr.id_cr = ?
    ");
    $stmt->execute([$crId]);
    $compteRendu = $stmt->fetch(PDO::FETCH_ASSOC);
    
    logStep("7. Requête DB exécutée", ['found' => $compteRendu ? 'oui' : 'non']);
    
    if (!$compteRendu) {
        throw new Exception('Compte rendu non trouvé pour ID: ' . $crId);
    }
    
    // Vérifier le fichier
    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/GSCV/' . $compteRendu['fichier_cr'];
    logStep("8. Chemin fichier", ['path' => $filePath]);
    
    if (!file_exists($filePath)) {
        throw new Exception('Fichier du compte rendu introuvable: ' . $filePath);
    }
    
    // Test PHPMailer
    $mail = new PHPMailer(true);
    logStep("9. PHPMailer créé");
    
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'axelangegomez2004@gmail.com';
    $mail->Password = 'yxxhpqgfxiulawhd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';
    
    logStep("10. Configuration SMTP définie");
    
    // Configuration de l'email
    $mail->setFrom('axelangegomez2004@gmail.com', 'UFHB - Système de Gestion');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    
    // Corps du message
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Compte Rendu - " . htmlspecialchars($compteRendu['nom_rapport']) . "</h2>
        <p>Cher(e) destinataire,</p>
        <p>Veuillez trouver ci-joint le compte rendu concernant le rapport de " . 
        htmlspecialchars($compteRendu['nom_etd']) . " " . 
        htmlspecialchars($compteRendu['prenom_etd']) . ".</p>
    ";
    
    if ($message) {
        $body .= "<p>Message additionnel :<br>" . nl2br(htmlspecialchars($message)) . "</p>";
    }
    
    $body .= "
        <p>Cordialement,<br>Université Félix Houphouët-Boigny</p>
    </body>
    </html>";
    
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);
    
    // Ajouter la pièce jointe
    $mail->addAttachment($filePath, basename($compteRendu['fichier_cr']));
    logStep("11. Email configuré et pièce jointe ajoutée");
    
    // Envoyer l'email
    $mail->send();
    logStep("12. Email envoyé");
    
    // Enregistrer l'historique
    $stmt = $pdo->prepare("
        INSERT INTO historique_envoi (id_cr, email_destinataire, date_envoi, statut)
        VALUES (?, ?, NOW(), 'succès')
    ");
    $stmt->execute([$crId, $email]);
    logStep("13. Historique enregistré");
    
    // Réponse de succès
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Email envoyé avec succès'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    logStep("ERREUR FATALE", [
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    
    // Enregistrer l'échec si possible
    if (isset($crId) && isset($email) && isset($pdo)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO historique_envoi (id_cr, email_destinataire, date_envoi, statut, message_erreur)
                VALUES (?, ?, NOW(), 'échec', ?)
            ");
            $stmt->execute([$crId, $email, $e->getMessage()]);
        } catch (Exception $dbError) {
            // Ignorer les erreurs de base de données lors de l'enregistrement de l'historique
        }
    }

    // Réponse JSON d'erreur
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'envoi : ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// S'assurer qu'aucune sortie supplémentaire n'est générée
exit;
?>