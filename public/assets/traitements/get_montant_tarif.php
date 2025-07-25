<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

try {
    if (!isset($_POST['niveau'])) {
        throw new Exception("Niveau non spécifié");
    }

    $id_niv_etd = intval($_POST['niveau']);
    error_log("Niveau demandé : " . $id_niv_etd);

    // Vérification que le niveau existe
    $query = "SELECT id_niv_etd, lib_niv_etd FROM niveau_etude WHERE id_niv_etd = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_niv_etd]);
    $niveau = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$niveau) {
        throw new Exception("Niveau d'études invalide");
    }
    error_log("Niveau trouvé : " . $niveau['lib_niv_etd']);

    //Récupération de l'id de l'année en cours
    $query = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception("Aucune année académique en cours trouvée");
    }

    $id_ac = $result['id_ac'];
    error_log("Année académique : " . $id_ac . " (" . $result['date_debut'] . " - " . $result['date_fin'] . ")");

    // Récupération du montant pour le niveau et l'année
    $query = "SELECT 
    ne.lib_niv_etd, ac.id_ac, fi.montant
    FROM etudiants e
    JOIN niveau_etude ne ON e.id_niv_etd = ne.id_niv_etd
    JOIN annee_academique ac ON ac.statut_annee = 'En cours'
    JOIN frais_inscription fi ON fi.id_niv_etd = e.id_niv_etd AND fi.id_ac = ac.id_ac; ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_niv_etd, $id_ac]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        error_log("Montant trouvé : " . $row['montant'] . " pour le niveau " . $row['id_niv_etd'] . " et l'année " . $row['id_ac']);
        echo json_encode([
            'success' => true,
            'montant' => floatval($row['montant']),
            'niveau' => $id_niv_etd,
            'annee' => $id_ac,
            'details' => [
                'niveau_lib' => $niveau['lib_niv_etd'],
                'annee_debut' => $result['date_debut'],
                'annee_fin' => $result['date_fin']
            ]
        ]);
    } else {
        error_log("Aucun tarif trouvé pour le niveau " . $id_niv_etd . " et l'année " . $id_ac);
        echo json_encode([
            'error' => "Aucun tarif trouvé pour ce niveau",
            'niveau' => $id_niv_etd,
            'annee' => $id_ac,
            'details' => [
                'niveau_lib' => $niveau['lib_niv_etd'],
                'annee_debut' => $result['date_debut'],
                'annee_fin' => $result['date_fin']
            ]
        ]);
    }
} catch (Exception $e) {
    error_log("Erreur dans get_montant_tarif.php : " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage(),
        'niveau' => isset($id_niv_etd) ? $id_niv_etd : null,
        'annee' => isset($id_ac) ? $id_ac : null
    ]);
}
