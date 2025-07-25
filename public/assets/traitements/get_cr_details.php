<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

try {
    $crId = $_GET['id'];
    
    // Récupérer les détails du compte rendu avec les informations du rapport et de l'étudiant
    $query = "SELECT cr.*, 
                     r.nom_rapport, r.theme_memoire, r.statut_rapport,
                     et.nom_etd, et.prenom_etd, et.email_etd,
                     GROUP_CONCAT(DISTINCT CONCAT(e.nom_ens, ' ', e.prenoms_ens) SEPARATOR ', ') as enseignants
              FROM compte_rendu cr
              JOIN rapport_etudiant r ON cr.id_rapport_etd = r.id_rapport_etd
              JOIN etudiants et ON r.num_etd = et.num_etd
              LEFT JOIN valider v ON v.id_rapport_etd = r.id_rapport_etd
              LEFT JOIN enseignants e ON v.id_ens = e.id_ens
              WHERE cr.id_cr = ?
              GROUP BY cr.id_cr";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$crId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode($result);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Compte rendu non trouvé']);
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des détails du compte rendu : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des détails']);
}