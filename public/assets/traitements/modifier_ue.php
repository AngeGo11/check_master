<?php
session_start();
require_once '../../../config/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/includes/audit_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ue = $_POST['id_ue'];
    $lib_ue = $_POST['lib_ue'];
    $credit_ue = $_POST['credit_ue'];
    $volume_horaire = $_POST['volume_horaire'];
    $niveau = $_POST['niveau'];
    $semestre = $_POST['semestre'];
    $id_ens = !empty($_POST['id_ens']) ? intval($_POST['id_ens']) : null;

    try {
        // Vérifier si l'UE existe
        $check = $pdo->prepare("SELECT id_ue FROM ue WHERE id_ue = ?");
        $check->execute([$id_ue]);
        
        if (!$check->fetch()) {
            $_SESSION['error'] = "L'UE n'existe pas.";
            header('Location: ../../listes/liste_ue.php');
            exit();
        }

        // Mettre à jour l'UE
        $sql = "UPDATE ue SET 
                lib_ue = ?, 
                credit_ue = ?, 
                volume_horaire = ?, 
                id_niv_etd = ?, 
                id_semestre = ?,
                id_ens = ?
                WHERE id_ue = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $lib_ue,
            $credit_ue,
            $volume_horaire,
            $niveau,
            $semestre,
            $id_ens,
            $id_ue
        ]);

        // Enregistrer la piste d'audit
        enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'parametres_generaux', 'Modification UE', 1);

        $_SESSION['success_message'] = "L'UE a été modifiée avec succès.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la modification de l'UE : " . $e->getMessage();
    }

    header('Location: ../../listes/liste_ue.php');
    exit();
} else {
    header('Location: ../../listes/liste_ue.php');
    exit();
} 