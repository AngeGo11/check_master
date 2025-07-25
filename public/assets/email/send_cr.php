<?php
ob_clean();
require_once '../../config/db_connect.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

// Vérifier si les données requises sont présentes
if (!isset($_POST['email']) || !isset($_POST['subject']) || !isset($_POST['cr_id'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

try {
    // Récupérer les informations du compte rendu
    $stmt = $pdo->prepare("
        SELECT * FROM compte_rendu 
        WHERE id_cr = ?
    ");
    $stmt->execute([$_POST['cr_id']]);
    $compteRendu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$compteRendu) {
        throw new Exception('Compte rendu non trouvé');
    }

    // Mettre à jour le nom du compte rendu dans la base de données
    $updateStmt = $pdo->prepare("
        UPDATE compte_rendu 
        SET nom_cr = ? 
        WHERE id_cr = ?
    ");
    $updateStmt->execute([$_POST['subject'], $_POST['cr_id']]);

    // Configuration de l'email
    $to = $_POST['email'];
    $subject = $_POST['subject'];
    $message = isset($_POST['message']) ? $_POST['message'] : '';
    
    // Générer le lien de téléchargement avec le chemin corrigé
    $downloadLink = "http://" . $_SERVER['HTTP_HOST'] . "/GSCV/pages/assets/download/download_cr.php?id=" . $compteRendu['id_cr'];
    
    // En-têtes de l'email
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $_SESSION['email'] . "\r\n";

    // Préparer le contenu HTML de l'email
    $htmlMessage = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { padding: 20px; }
            .footer { margin-top: 20px; font-size: 12px; color: #666; }
            .download-link {
                display: inline-block;
                margin: 20px 0;
                padding: 10px 20px;
                background-color: #4CAF50;
                color: white;
                text-decoration: none;
                border-radius: 5px;
            }
            .download-link:hover {
                background-color: #45a049;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Partage de compte rendu</h2>
            <p>" . nl2br(htmlspecialchars($message)) . "</p>
            
            <div class='download-section'>
                <h3>Télécharger le compte rendu</h3>
                <p>Vous pouvez télécharger le compte rendu en cliquant sur le lien ci-dessous :</p>
                <a href='" . $downloadLink . "' class='download-link'>
                    <i class='fas fa-download'></i> Télécharger le compte rendu
                </a>
            </div>

            <div class='footer'>
                <p>Ce message a été envoyé depuis le système CHECK Master.</p>
                <p>Si le lien ne fonctionne pas, copiez et collez cette URL dans votre navigateur :</p>
                <p>" . $downloadLink . "</p>
            </div>
        </div>
    </body>
    </html>";

    // Envoyer l'email
    if (mail($to, $subject, $htmlMessage, $headers)) {
        echo json_encode(['success' => true, 'message' => 'Email envoyé avec succès']);
    } else {
        throw new Exception('Erreur lors de l\'envoi de l\'email');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 