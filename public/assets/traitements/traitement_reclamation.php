<?php

session_start();
require_once "config_year.php";
require_once '../../../config/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/includes/audit_utils.php';

// Débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reclamation'])) {
    try {
        // Récupération des données du formulaire
        $motif_reclamation = $_POST['motif_reclamation'];
        $noms_matieres = $_POST['noms_matieres'];

        //Récupération de l'année academique (id)
        $query = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_ac = $result['id_ac'];
        
        // Récupération de l'ID de l'étudiant depuis la session
        if (!isset($_SESSION['user_id'])) {
            throw new Exception("Session utilisateur non trouvée");
        }
        
        // Récupération des informations de l'étudiant
        $sqlData = "SELECT num_etd FROM etudiants e 
            JOIN utilisateur u ON u.login_utilisateur = e.email_etd
            WHERE id_utilisateur = ?";
        
        $stmt = $pdo->prepare($sqlData);
        $stmt->execute([$_SESSION['user_id']]);
        $student_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student_data) {
            throw new Exception("Données étudiant non trouvées");
        }
        
        $student_id = $student_data['num_etd'];
        
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
        
        // Conversion du tableau des matières en JSON
        $matieres_json = json_encode($noms_matieres);

        // Débogage des valeurs
        error_log("Valeurs à insérer :");
        error_log("id_ac: " . $id_ac);
        error_log("student_id: " . $student_id);
        error_log("motif_reclamation: " . $motif_reclamation);
        error_log("matieres_json: " . $matieres_json);
        error_log("piece_jointe: " . $piece_jointe);
        
        // Préparation de la requête d'insertion
        $sql = "INSERT INTO reclamations (id_ac, num_etd, motif_reclamation, matieres, piece_jointe, date_reclamation, statut_reclamation) 
                VALUES (?, ?, ?, ?, ?, CURDATE(), 'En attente')";
        
        $stmt = $pdo->prepare($sql);
        
        // Débogage de la requête
        error_log("Requête SQL : " . $sql);
        
        try {
            $stmt->execute([$id_ac, $student_id, $motif_reclamation, $matieres_json, $piece_jointe]);
            error_log("Insertion réussie");
            
            // Enregistrer la piste d'audit
            enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'reclamations', 'Dépôt réclamation étudiant', 1);
            
        } catch (PDOException $e) {
            error_log("Erreur SQL : " . $e->getMessage());
            throw $e;
        }
        
        // Redirection avec un message de succès et affichage de la modal
        $_SESSION['success_message'] = "Votre réclamation a été enregistrée avec succès.";
        $_SESSION['show_confirmation'] = true;
        header('Location: ../../index_etudiant.php?page=reclamations');
        exit();
        
    } catch (Exception $e) {
        // En cas d'erreur, redirection avec un message d'erreur
        error_log("Erreur dans le traitement : " . $e->getMessage());
        $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
        header('Location: ../../index_etudiant.php?page=reclamations');
        exit();
    }
} else {
    // Si le formulaire n'a pas été soumis correctement
    error_log("Formulaire non soumis correctement");
    $_SESSION['error_message'] = "Le formulaire n'a pas été soumis correctement.";
    header('Location: ../../index_etudiant.php?page=reclamations');
    exit();
} 