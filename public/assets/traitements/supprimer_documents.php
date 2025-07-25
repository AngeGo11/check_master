<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session expirée. Veuillez vous reconnecter.']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée.']);
    exit;
}

if (!isset($_POST['doc_ids'])) {
    echo json_encode(['success' => false, 'error' => 'Aucun document sélectionné.']);
    exit;
}

$doc_ids_json = $_POST['doc_ids'];
$doc_ids_array = json_decode($doc_ids_json, true);

if (empty($doc_ids_array)) {
    echo json_encode(['success' => false, 'error' => 'Aucun document valide sélectionné.']);
    exit;
}

$user_id = $_SESSION['user_id'];

$pdo->beginTransaction();

try {
    $deleted_count = 0;
    $errors = [];

    foreach ($doc_ids_array as $composite_id) {
        if (strpos($composite_id, ':') !== false) {
            list($type, $id) = explode(':', $composite_id, 2);
            $id = intval($id);

            // Vérifier les permissions de l'utilisateur
            if ($type === 'Rapport') {
                // Vérifier si l'utilisateur a le droit de supprimer ce rapport
                $check_sql = "SELECT COUNT(*) FROM rapport_etudiant re 
                             JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd 
                             WHERE re.id_rapport_etd = ? AND d.num_etd IN (
                                 SELECT num_etd FROM etudiants WHERE id_utilisateur = ?
                             )";
                $delete_sql = "DELETE FROM rapport_etudiant WHERE id_rapport_etd = ?";
            } elseif ($type === 'Compte rendu') {
                // Vérifier si l'utilisateur a le droit de supprimer ce compte rendu
                $check_sql = "SELECT COUNT(*) FROM compte_rendu cr 
                             JOIN rendre rn ON rn.id_cr = cr.id_cr 
                             JOIN enseignants ens ON ens.id_ens = rn.id_ens 
                             WHERE cr.id_cr = ? AND ens.id_utilisateur = ?";
                $delete_sql = "DELETE FROM compte_rendu WHERE id_cr = ?";
            } else {
                $errors[] = "Type de document invalide pour l'ID $id";
                continue;
            }

            // Vérifier les permissions
            $check_stmt = $pdo->prepare($check_sql);
            $check_stmt->execute([$id, $user_id]);
            
            if ($check_stmt->fetchColumn() == 0) {
                $errors[] = "Vous n'avez pas les permissions pour supprimer le document $id ($type).";
                continue;
            }

            // Supprimer le document
            $delete_stmt = $pdo->prepare($delete_sql);
            $delete_stmt->execute([$id]);

            if ($delete_stmt->rowCount() > 0) {
                $deleted_count++;
            } else {
                $errors[] = "Erreur lors de la suppression du document $id ($type).";
            }
        }
    }

    $pdo->commit();

    $message = "$deleted_count document(s) supprimé(s) avec succès.";
    if (!empty($errors)) {
        $message .= " Erreurs : " . implode(', ', $errors);
    }

    if ($deleted_count > 0) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Aucun document n\'a été supprimé. ' . implode(', ', $errors)]);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Erreur suppression documents: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur de base de données lors de la suppression.']);
}

?> 