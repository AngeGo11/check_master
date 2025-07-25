<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

try {
    $num_carte = $_GET['num_carte'] ?? '';

    if (empty($num_carte)) {
        echo json_encode(['success' => false, 'message' => "Numéro de carte manquant"]);
        exit;
    }

    //Récupération de l'id de l'année en cours
    $query = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        echo json_encode(['success' => false, 'message' => "Aucune année académique en cours trouvée"]);
        exit;
    }
    
    $id_ac = $result['id_ac'];

    // Récupérer les informations de l'étudiant, y compris le niveau et le total payé
    $stmt = $pdo->prepare("
        SELECT 
            e.num_etd,
            e.nom_etd,
            e.prenom_etd,
            e.id_promotion,
            pr.*,
            fi.montant as montant_inscription,
            e.id_niv_etd,
            n.lib_niv_etd,
            ac.id_ac,
            COALESCE(r.total_paye, 0) as total_paye,
            r.reste_a_payer,
            r.id_reglement,
            r.statut,
            r.date_reglement
        FROM etudiants e
        LEFT JOIN frais_inscription fi ON e.id_niv_etd = fi.id_niv_etd AND fi.id_ac = ?
        LEFT JOIN promotion pr ON pr.id_promotion = e.id_promotion
        LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd
        LEFT JOIN annee_academique ac ON ac.id_ac = fi.id_ac
        LEFT JOIN reglement r ON e.num_etd = r.num_etd 
            AND r.id_ac = ? 
            AND r.id_niv_etd = e.id_niv_etd
            AND r.id_reglement = (
                SELECT MAX(id_reglement)
                FROM reglement
                WHERE num_etd = e.num_etd
                AND id_ac = ?
                AND id_niv_etd = e.id_niv_etd
            )
        WHERE e.num_carte_etd = ?
    ");

    $stmt->execute([$id_ac, $id_ac, $id_ac, $num_carte]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$etudiant) {
        echo json_encode(['success' => false, 'message' => "Étudiant non trouvé avec le numéro de carte : " . $num_carte]);
        exit;
    }

    if (!$etudiant['montant_inscription']) {
        echo json_encode(['success' => false, 'message' => "Aucun montant de frais d'inscription trouvé pour ce niveau"]);
        exit;
    }

    // Si aucun règlement, on prend le montant d'inscription comme reste à payer
    $reste_a_payer = isset($etudiant['reste_a_payer']) && $etudiant['reste_a_payer'] !== null ? $etudiant['reste_a_payer'] : $etudiant['montant_inscription'];

    echo json_encode([
        'success' => true,
        'num_etd' => $etudiant['num_etd'],
        'nom_etd' => $etudiant['nom_etd'],
        'prenom_etd' => $etudiant['prenom_etd'],
        'lib_promotion' => $etudiant['lib_promotion'],
        'id_niv_etd' => $etudiant['id_niv_etd'],
        'lib_niv_etd' => $etudiant['lib_niv_etd'],
        'montant' => $etudiant['montant_inscription'],
        'total_paye' => $etudiant['total_paye'],
        'reste_a_payer' => $reste_a_payer,
        'debug_info' => [
            'dernier_reglement_total_paye' => $etudiant['total_paye'],
            'dernier_reglement_reste' => $reste_a_payer,
            'date_dernier_reglement' => $etudiant['date_reglement'] ?? null
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('Erreur PDO dans get_etudiant_info.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => "Erreur de base de données : " . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Erreur dans get_etudiant_info.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
