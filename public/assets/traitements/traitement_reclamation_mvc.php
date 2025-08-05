<?php
session_start();
require_once '../../../app/config/config.php';
require_once '../../../app/Controllers/ReclamationController.php';

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Utilisateur non connecté.";
    header('Location: ../../index_etudiant.php?page=reclamations');
    exit;
}

// Initialisation du contrôleur
$controller = new ReclamationController($pdo);

// Vérification si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reclamation'])) {
    try {
        // Récupération des données du formulaire
        $motif_reclamation = $_POST['motif_reclamation'] ?? '';
        $noms_matieres = $_POST['noms_matieres'] ?? [];

        // Récupération des informations de l'étudiant via le contrôleur
        $data = $controller->index($_SESSION['user_id']);
        
        if (isset($data['error'])) {
            throw new Exception($data['error']);
        }
        
        $studentData = $data['studentData'];
        $student_id = $studentData['num_etd'];
        
        // Traitement de la pièce jointe si elle existe
        $piece_jointe = null;
        if (isset($_FILES['pieceJointe']) && $_FILES['pieceJointe']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/reclamations/';
            
            // Création du dossier s'il n'existe pas
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Génération d'un nom de fichier unique
            $file_extension = pathinfo($_FILES['pieceJointe']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('reclamation_') . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            // Déplacement du fichier
            if (move_uploaded_file($_FILES['pieceJointe']['tmp_name'], $upload_path)) {
                $piece_jointe = 'assets/uploads/reclamations/' . $file_name;
            }
        }
        
        // Préparation des données pour la création
        $reclamationData = [
            'motif_reclamation' => $motif_reclamation,
            'noms_matieres' => $noms_matieres,
            'piece_jointe' => $piece_jointe
        ];

        // Création de la réclamation via le contrôleur
        $result = $controller->createReclamation($student_id, $reclamationData);
        
        if ($result) {
            $_SESSION['success_message'] = "Votre réclamation a été enregistrée avec succès.";
            $_SESSION['show_confirmation'] = true;
        } else {
            throw new Exception("Erreur lors de la création de la réclamation");
        }
        
    } catch (Exception $e) {
        error_log("Erreur dans le traitement : " . $e->getMessage());
        $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "Le formulaire n'a pas été soumis correctement.";
}

// Redirection vers la page des réclamations
header('Location: ../../index_etudiant.php?page=reclamations');
exit();
?> 