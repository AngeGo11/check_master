<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';


if (!isset($_GET['num_carte'])) {
    echo json_encode(['error' => 'Numéro carte requis']);
    exit;
}

try {
  

    $num_carte = $_GET['num_carte'];

    // Trouver l'étudiant
    $stmt = $pdo->prepare("SELECT num_etd, id_niv_etd FROM etudiants WHERE num_carte_etd = ?");
    $stmt->execute([$num_carte]);
    $etudiant = $stmt->fetch();

    if (!$etudiant) {
        echo json_encode(['error' => "Étudiant introuvable"]);
        exit;
    }

    $num_etd = $etudiant['num_etd'];
    $id_niv_etd = $etudiant['id_niv_etd'];

    // Récupérer le dernier règlement
    $stmt = $pdo->prepare("SELECT * FROM reglement WHERE num_etd = ? ORDER BY id_reglement DESC LIMIT 1");
    $stmt->execute([$num_etd]);
    $reglement = $stmt->fetch();

    if (!$reglement) {
        // Aucun règlement encore enregistré
        echo json_encode([
            'numero_reglement' => null,
            'montant_total' => 0,
            'total_paye' => 0,
            'reste_a_payer' => 0,
            'id_niv_etd' => $id_niv_etd,
            'readonly' => false
        ]);
        exit;
    }

    $numero_reglement = $reglement['numero_reglement'];
    $montant_total = $reglement['montant_a_payer'];

    // Total payé pour ce règlement
    $stmt = $pdo->prepare("SELECT SUM(montant_paye) FROM paiement_reglement WHERE id_reglement = ?");
    $stmt->execute([$reglement['id_reglement']]);
    $total_paye = $stmt->fetchColumn() ?: 0;

    $reste_a_payer = $montant_total - $total_paye;

    echo json_encode([
        'numero_reglement' => $numero_reglement,
        'montant_total' => $montant_total,
        'total_paye' => $total_paye,
        'reste_a_payer' => $reste_a_payer,
        'id_niv_etd' => $id_niv_etd,
        'readonly' => true
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => "Erreur BD : " . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}