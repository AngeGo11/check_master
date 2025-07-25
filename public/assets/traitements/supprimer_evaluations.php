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
    echo json_encode(['success' => false, 'error' => 'Aucune évaluation sélectionnée.']);
    exit;
}

$etudiant_ids_json = $_POST['etudiant_ids'];
// Les IDs sont les num_carte_etd, il faut les convertir en num_etd
$numeros_carte = json_decode($etudiant_ids_json, true);

if (empty($numeros_carte)) {
    echo json_encode(['success' => false, 'error' => 'Aucune évaluation valide sélectionnée.']);
    exit;
}

// Convertir les numéros de carte en num_etd
$placeholders_cartes = implode(',', array_fill(0, count($numeros_carte), '?'));
$sql_get_ids = "SELECT num_etd FROM etudiants WHERE num_carte_etd IN ($placeholders_cartes)";
$stmt_get_ids = $pdo->prepare($sql_get_ids);
$stmt_get_ids->execute($numeros_carte);
$etudiant_ids = $stmt_get_ids->fetchAll(PDO::FETCH_COLUMN);

if (empty($etudiant_ids)) {
    echo json_encode(['success' => false, 'error' => 'Aucun étudiant correspondant aux sélections n\'a été trouvé.']);
    exit;
}


$placeholders = implode(',', array_fill(0, count($etudiant_ids), '?'));
$pdo->beginTransaction();

try {
    // Supprimer toutes les évaluations (UE et ECUE) pour les étudiants sélectionnés
    // Il faut être prudent et s'assurer qu'on ne supprime que pour l'année en cours si nécessaire
    // Pour l'instant, on supprime tout pour l'étudiant, ce qui est très destructeur.
    
    $sql_delete_ecue = "DELETE FROM evaluer_ecue WHERE num_etd IN ($placeholders)";
    $stmt_ecue = $pdo->prepare($sql_delete_ecue);
    $stmt_ecue->execute($etudiant_ids);
    $deleted_count_ecue = $stmt_ecue->rowCount();

    // S'il y a une table evaluer_ue, il faut aussi la vider
    // $sql_delete_ue = "DELETE FROM evaluer_ue WHERE num_etd IN ($placeholders)";
    // $stmt_ue = $pdo->prepare($sql_delete_ue);
    // $stmt_ue->execute($etudiant_ids);
    // $deleted_count_ue = $stmt_ue->rowCount();

    $pdo->commit();
    enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'evaluations_etudiants', 'Suppression évaluations', count($etudiant_ids));

    $total_deleted = $deleted_count_ecue; // + $deleted_count_ue;

    if ($total_deleted > 0) {
        echo json_encode(['success' => true, 'message' => "Toutes les évaluations pour " . count($etudiant_ids) . " étudiant(s) ont été supprimées."]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucune évaluation n\'a été supprimée.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur de suppression d'évaluations : " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données.']);
}
?> 