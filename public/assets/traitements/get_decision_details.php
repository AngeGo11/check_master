<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
require_once 'traitements_suivis_decisions.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

try {
    $details = getDecisionDetails($_GET['id']);
    if ($details) {
        // Récupération des fichiers joints
        $query = "SELECT * FROM rapport_etudiant WHERE id_rapport_etd = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_GET['id']]);
        $details['fichier_rapport'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des commentaires
        $query = "SELECT v.*, e.nom_ens, e.prenoms_ens 
                 FROM enseignants ens
                 JOIN valider e ON v.id_ens = ens.id_ens 
                 WHERE v.id_rapport_etd = ? 
                 ORDER BY c.date_validation DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_GET['id']]);
        $details['commentaires'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des approbations
        $query = "SELECT a.*, e.nom_ens, e.prenoms_ens 
                 FROM approuver a 
                 JOIN personnel_administratif pa ON a.id_personnel_adm = pa.personnel_adm 
                 WHERE a.id_rapport_etd = ? 
                 ORDER BY a.date_approbation ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_GET['id']]);
        $details['com_appr'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($details);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Décision non trouvée']);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des détails : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des détails']);
} 