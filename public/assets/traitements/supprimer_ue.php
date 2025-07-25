<?php
session_start();
require_once '../../../config/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/includes/audit_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_ue'])) {
    $id_ue = $_POST['id_ue'];

    if (empty($id_ue)) {
        $_SESSION['error_message'] = "Aucune UE sélectionnée pour la suppression.";
        header('Location: /GSCV+/public/listes/liste_ue.php');
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Vérifier les évaluations directement liées à l'UE
        $stmt_eval_ue = $pdo->prepare("SELECT COUNT(*) FROM evaluer_ue WHERE id_ue = ?");
        $stmt_eval_ue->execute([$id_ue]);
        if ($stmt_eval_ue->fetchColumn() > 0) {
            $_SESSION['error_message'] = "Impossible de supprimer cette UE car des évaluations y sont directement liées.";
            header('Location: /GSCV+/public/listes/liste_ue.php');
            exit();
        }

        // 2. Récupérer toutes les ECUEs liées à l'UE
        $stmt_ecues = $pdo->prepare("SELECT id_ecue FROM ecue WHERE id_ue = ?");
        $stmt_ecues->execute([$id_ue]);
        $ecues = $stmt_ecues->fetchAll(PDO::FETCH_COLUMN);

        if ($ecues) {
            // 3. Vérifier les évaluations liées aux ECUEs
            $placeholders = implode(',', array_fill(0, count($ecues), '?'));
            $stmt_eval_ecue = $pdo->prepare("SELECT COUNT(*) FROM evaluer_ecue WHERE id_ecue IN ($placeholders)");
            $stmt_eval_ecue->execute($ecues);
            if ($stmt_eval_ecue->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Impossible de supprimer cette UE car au moins une de ses ECUEs a des évaluations associées.";
                header('Location: /GSCV+/public/listes/liste_ue.php');
                exit();
            }

            // 4. Si aucune évaluation n'est liée, supprimer les ECUEs
            $stmt_delete_ecue = $pdo->prepare("DELETE FROM ecue WHERE id_ue = ?");
            $stmt_delete_ecue->execute([$id_ue]);
        }

        // 5. Supprimer l'UE elle-même
        $stmt_delete_ue = $pdo->prepare("DELETE FROM ue WHERE id_ue = ?");
        $stmt_delete_ue->execute([$id_ue]);

        $pdo->commit();
        
        // Enregistrer la piste d'audit
      //  enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'parametres_generaux', 'Suppression UE', 1);
        
        $_SESSION['success_message'] = "L'UE et toutes ses ECUEs associées ont été supprimées avec succès.";

    } catch (PDOException $e) {
        $pdo->rollBack();
        // En mode développement, il est utile d'afficher l'erreur réelle
        // $_SESSION['error_message'] = "Erreur lors de la suppression de l'UE : " . $e->getMessage();
        $_SESSION['error_message'] = "Une erreur technique est survenue lors de la suppression de l'UE.";
    }
} else {
    $_SESSION['error_message'] = "Requête invalide pour la suppression de l'UE.";
}

// Redirection vers la page de la liste des UE
header('Location: /GSCV+/public/listes/liste_ue.php');
exit();
?> 