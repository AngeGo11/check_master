<?php
require_once '../../../config/db_connect.php';
require_once '../../../includes/audit_utils.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilisateur non connecté.']);
    exit;
}

if (!isset($_POST['etudiant_ids'])) {
    echo json_encode(['success' => false, 'error' => 'Aucun étudiant sélectionné.']);
    exit;
}

$etudiant_ids_json = $_POST['etudiant_ids'];
$etudiant_ids = json_decode($etudiant_ids_json, true);

if (empty($etudiant_ids)) {
    echo json_encode(['success' => false, 'error' => 'Aucun étudiant valide sélectionné.']);
    exit;
}

$etudiant_ids = array_map('intval', $etudiant_ids);
$placeholders = implode(',', array_fill(0, count($etudiant_ids), '?'));

$pdo->beginTransaction();

try {
    foreach ($etudiant_ids as $num_etd) {
        // 1. Récupérer tous les rapports de l'étudiant
        $stmt = $pdo->prepare("SELECT id_rapport_etd FROM rapport_etudiant WHERE num_etd = ?");
        $stmt->execute([$num_etd]);
        $rapports = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($rapports as $id_rapport) {
            // 2.1 Supprimer les compte-rendus liés à ce rapport
            $stmt = $pdo->prepare("SELECT id_cr FROM compte_rendu WHERE id_rapport_etd = ?");
            $stmt->execute([$id_rapport]);
            $compte_rendus = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($compte_rendus)) {
                // Supprimer dans historique_envoi
                $placeholders = implode(',', array_fill(0, count($compte_rendus), '?'));
                $stmt = $pdo->prepare("DELETE FROM historique_envoi WHERE id_cr IN ($placeholders)");
                $stmt->execute($compte_rendus);

                // Supprimer dans archives (liées à compte rendu)
                $stmt = $pdo->prepare("DELETE FROM archives WHERE id_cr IN ($placeholders)");
                $stmt->execute($compte_rendus);

                // Supprimer les compte-rendus
                $stmt = $pdo->prepare("DELETE FROM compte_rendu WHERE id_cr IN ($placeholders)");
                $stmt->execute($compte_rendus);
            }

            // 2.2 Supprimer dans archives (liées au rapport)
            $stmt = $pdo->prepare("DELETE FROM archives WHERE id_rapport_etd = ?");
            $stmt->execute([$id_rapport]);

            // 2.3 Supprimer dans partage_rapport, approuver, valider, deposer, chat_commission
            $stmt = $pdo->prepare("DELETE FROM partage_rapport WHERE id_rapport_etd = ?");
            $stmt->execute([$id_rapport]);
            $stmt = $pdo->prepare("DELETE FROM approuver WHERE id_rapport_etd = ?");
            $stmt->execute([$id_rapport]);
            $stmt = $pdo->prepare("DELETE FROM valider WHERE id_rapport_etd = ?");
            $stmt->execute([$id_rapport]);
            $stmt = $pdo->prepare("DELETE FROM deposer WHERE id_rapport_etd = ?");
            $stmt->execute([$id_rapport]);
            $stmt = $pdo->prepare("DELETE FROM chat_commission WHERE id_rapport_etd = ?");
            $stmt->execute([$id_rapport]);
        }

        // 3. Supprimer les rapports
        $stmt = $pdo->prepare("DELETE FROM rapport_etudiant WHERE num_etd = ?");
        $stmt->execute([$num_etd]);

        // 4. Supprimer dans les autres tables liées à l'étudiant
        $tables = [
            'faire_stage', 'inscrire', 'reclamations', 'evaluer_ecue', 'evaluer_ue', 'demande_soutenance', 'reglement'
        ];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("DELETE FROM $table WHERE num_etd = ?");
            $stmt->execute([$num_etd]);
        }

        // 5. Supprimer l'étudiant
        $stmt = $pdo->prepare("DELETE FROM etudiants WHERE num_etd = ?");
        $stmt->execute([$num_etd]);

        // 6. Supprimer le compte utilisateur associé (si besoin)
        // Récupérer l'email de l'étudiant
        $stmt = $pdo->prepare("SELECT email_etd FROM etudiants WHERE num_etd = ?");
        $stmt->execute([$num_etd]);
        $email = $stmt->fetchColumn();
        if ($email) {
            $stmt = $pdo->prepare("DELETE FROM utilisateur WHERE login_utilisateur = ?");
            $stmt->execute([$email]);
        }
    }

    $pdo->commit();
    enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'etudiants', 'Suppression multiple étudiants', count($etudiant_ids));

    echo json_encode(['success' => true, 'message' => count($etudiant_ids) . ' étudiant(s) et toutes leurs données associées ont été supprimé(e)s.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression multiple d'étudiants : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données lors de la suppression. Détails : ' . $e->getMessage()]);
}
?> 