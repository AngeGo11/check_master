<?php
session_start();

// Vérification de connexion
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit();
}

// Vérification des permissions (commission)
$allowedGroups = [5, 6, 7, 8, 9]; // Enseignant, Responsable niveau, Responsable filière, Administrateur, Commission
$userGroups = $_SESSION['user_groups'] ?? [];

$hasAccess = false;
foreach ($userGroups as $group) {
    if (in_array($group, $allowedGroups)) {
        $hasAccess = true;
        break;
    }
}

if (!$hasAccess) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit();
}

// Inclure le modèle et PHPMailer
require_once __DIR__ . '/../app/Models/ConsultationModel.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Définir le type de contenu JSON
header('Content-Type: application/json; charset=utf-8');

// Récupérer l'action
$action = $_GET['action'] ?? '';

if ($action === 'getEvaluationData') {
    $rapport_id = $_GET['rapport_id'] ?? null;
    
    if (!$rapport_id) {
        echo json_encode(['success' => false, 'error' => 'ID du rapport manquant']);
        exit();
    }

    try {
        $model = new ConsultationModel();
        
        // Log pour débogage
        error_log("ajax_consultations.php::getEvaluationData - Rapport ID: " . $rapport_id);
        
        $commission_members = $model->getCommissionMembers($rapport_id);
        $evaluation_stats = $model->getEvaluationStats($rapport_id);
        $evaluation_details = $model->getEvaluationDetails($rapport_id);
        
        // Log pour débogage
        error_log("ajax_consultations.php::getEvaluationData - Commission members count: " . count($commission_members));
        error_log("ajax_consultations.php::getEvaluationData - Evaluation stats: " . json_encode($evaluation_stats));
        error_log("ajax_consultations.php::getEvaluationData - Evaluation details count: " . count($evaluation_details));
        
        $response = [
            'success' => true,
            'commission_members' => $commission_members,
            'evaluation_stats' => $evaluation_stats,
            'evaluation_details' => $evaluation_details
        ];
        
        echo json_encode($response);
    } catch (Exception $e) {
        error_log("ajax_consultations.php::getEvaluationData - Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des données: ' . $e->getMessage()]);
    }
} elseif ($action === 'sendCompteRenduEmail') {
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Données invalides']);
        exit();
    }
    
    $cr_id = $input['cr_id'] ?? null;
    $titre = $input['titre'] ?? '';
    $email = $input['email'] ?? '';
    $subject = $input['subject'] ?? '';
    $message = $input['message'] ?? '';
    
    if (!$cr_id || !$email) {
        echo json_encode(['success' => false, 'error' => 'ID du compte rendu et email requis']);
        exit();
    }
    
    try {
        $model = new ConsultationModel();
        
        // Récupérer les détails du compte rendu
        $compteRendu = $model->getCompteRenduById($cr_id);
        
        if (!$compteRendu) {
            echo json_encode(['success' => false, 'error' => 'Compte rendu non trouvé']);
            exit();
        }
        
        // Vérifier que le fichier existe
        $filePath = __DIR__ . '/../../' . $compteRendu['fichier_cr'];
        if (!file_exists($filePath)) {
            echo json_encode(['success' => false, 'error' => 'Fichier du compte rendu introuvable']);
            exit();
        }
        
        // Configuration de PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'axelangegomez2004@gmail.com';
        $mail->Password = 'yxxhpqgfxiulawhd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';
        
        // Configuration de l'email
        $mail->setFrom('axelangegomez2004@gmail.com', 'UFHB - Système de Gestion GSCV+');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $subject ?: "Compte rendu - " . $titre;
        
        // Corps du message
        $body = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%); color: white; padding: 20px; border-radius: 10px 10px 0 0;'>
                    <h1 style='margin: 0; font-size: 24px;'>Compte Rendu - GSCV+</h1>
                    <p style='margin: 10px 0 0 0; opacity: 0.9;'>Université Félix Houphouët-Boigny</p>
                </div>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px;'>
                    <h2 style='color: #1a5276; margin-top: 0;'>Bonjour,</h2>
                    
                    <p>Veuillez trouver ci-joint le compte rendu concernant le rapport de <strong>" . 
                    htmlspecialchars($compteRendu['nom_etd'] ?? '') . " " . 
                    htmlspecialchars($compteRendu['prenom_etd'] ?? '') . "</strong>.</p>
                    
                    <div style='background: white; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #1a5276;'>
                        <h3 style='margin: 0 0 10px 0; color: #1a5276;'>Détails du compte rendu :</h3>
                        <p><strong>Titre :</strong> " . htmlspecialchars($titre) . "</p>
                        <p><strong>Étudiant :</strong> " . htmlspecialchars($compteRendu['nom_etd'] ?? '') . " " . htmlspecialchars($compteRendu['prenom_etd'] ?? '') . "</p>
                        <p><strong>Thème :</strong> " . htmlspecialchars($compteRendu['theme_memoire'] ?? '') . "</p>
                        <p><strong>Date de création :</strong> " . date('d/m/Y H:i', strtotime($compteRendu['date_cr'] ?? 'now')) . "</p>
                    </div>";
        
        if ($message) {
            $body .= "
                    <div style='background: #e8f4fd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #3498db;'>
                        <h4 style='margin: 0 0 10px 0; color: #2980b9;'>Message additionnel :</h4>
                        <p style='margin: 0;'>" . nl2br(htmlspecialchars($message)) . "</p>
                    </div>";
        }
        
        $body .= "
                    <p>Cordialement,<br>
                    <strong>Université Félix Houphouët-Boigny</strong><br>
                    <em>Système de Gestion des Consultations et Validations (GSCV+)</em></p>
                    
                    <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
                    <p style='font-size: 12px; color: #666; text-align: center;'>
                        Cet email a été envoyé automatiquement par le système GSCV+.<br>
                        Veuillez ne pas répondre à cet email.
                    </p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));
        
        // Ajouter la pièce jointe
        $mail->addAttachment($filePath, basename($compteRendu['fichier_cr']));
        
        // Envoyer l'email
        if ($mail->send()) {
            // Enregistrer l'envoi dans la base de données (optionnel)
            $model->logEmailSent($cr_id, $email, $subject, $_SESSION['user_id']);
            
            echo json_encode(['success' => true, 'message' => 'Compte rendu envoyé avec succès']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi de l\'email']);
        }
        
    } catch (Exception $e) {
        error_log("ajax_consultations.php::sendCompteRenduEmail - Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'envoi: ' . $e->getMessage()]);
    }
} elseif ($action === 'getEmailHistory') {
    try {
        $model = new ConsultationModel();
        
        // Récupérer l'historique des emails
        $history = $model->getEmailHistory();
        
        echo json_encode(['success' => true, 'history' => $history]);
        
    } catch (Exception $e) {
        error_log("ajax_consultations.php::getEmailHistory - Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération de l\'historique: ' . $e->getMessage()]);
    }
} elseif ($action === 'getCompteRenduDetails') {
    $titre = $_GET['titre'] ?? null;
    
    if (!$titre) {
        echo json_encode(['success' => false, 'error' => 'Titre du compte rendu manquant']);
        exit();
    }

    try {
        $model = new ConsultationModel();
        $details = $model->getCompteRenduDetailsByTitre($titre);
        echo json_encode(['success' => true, 'details' => $details]);
    } catch (Exception $e) {
        error_log("ajax_consultations.php::getCompteRenduDetails - Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des détails: ' . $e->getMessage()]);
    }
} elseif ($action === 'deleteCompteRenduGroup') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['titre'])) {
        echo json_encode(['success' => false, 'error' => 'Titre du compte rendu manquant']);
        exit();
    }

    try {
        $model = new ConsultationModel();
        $model->deleteCompteRenduGroup($data['titre']);
        echo json_encode(['success' => true, 'message' => 'Groupe de comptes rendus supprimé avec succès']);
    } catch (Exception $e) {
        error_log("ajax_consultations.php::deleteCompteRenduGroup - Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
    }
} elseif ($action === 'deleteCompteRendu') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'error' => 'ID du compte rendu manquant']);
        exit();
    }

    try {
        $model = new ConsultationModel();
        $model->deleteCompteRendu($data['id']);
        echo json_encode(['success' => true, 'message' => 'Compte rendu supprimé avec succès']);
    } catch (Exception $e) {
        error_log("ajax_consultations.php::deleteCompteRendu - Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
    }
} elseif ($action === 'getCompteRenduPreview') {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID du compte rendu manquant']);
        exit();
    }

    try {
        $model = new ConsultationModel();
        $compteRendu = $model->getCompteRenduById($id);
        
        if (!$compteRendu) {
            echo json_encode(['success' => false, 'error' => 'Compte rendu non trouvé']);
            exit();
        }

        $filePath = __DIR__ . '/../' . $compteRendu['fichier_cr'];
        
        if (!file_exists($filePath)) {
            echo json_encode(['success' => false, 'error' => 'Fichier non trouvé']);
            exit();
        }

        // Déterminer le type de fichier
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'pdf') {
            // Pour les PDF, retourner juste le type (on utilise une URL directe)
            echo json_encode(['success' => true, 'content' => '', 'fileType' => 'pdf']);
        } elseif ($extension === 'html') {
            // Pour les fichiers HTML, lire le contenu
            $content = file_get_contents($filePath);
            echo json_encode(['success' => true, 'content' => $content, 'fileType' => 'html']);
        } elseif (in_array($extension, ['docx', 'doc'])) {
            // Pour les fichiers Word, retourner le type
            echo json_encode(['success' => true, 'content' => '', 'fileType' => $extension]);
        } else {
            // Pour les autres types
            echo json_encode(['success' => true, 'content' => '', 'fileType' => $extension]);
        }
        
    } catch (Exception $e) {
        error_log("ajax_consultations.php::getCompteRenduPreview - Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération de l\'aperçu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
}
?> 