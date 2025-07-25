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
    $details = getReportDetails($_GET['id']);
    if ($details) {
        // Récupération des fichiers joints
        $query = "SELECT * FROM rapport_etudiant WHERE id_rapport_etd = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_GET['id']]);
        $details['fichiers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupération des commentaires
        $query = "SELECT m.*, e.nom_ens, e.prenoms_ens 
                 FROM messages m 
                 JOIN enseignants e ON m.expediteur_id = e.id_ens 
                 WHERE m.rapport_id = ? AND m.type_message = 'chat'
                 ORDER BY m.date_creation DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_GET['id']]);
        $details['commentaires'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($details);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Rapport non trouvé']);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des détails : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des détails']);
} 