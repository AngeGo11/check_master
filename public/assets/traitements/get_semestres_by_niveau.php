<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/config.php';

try {
    // Créer la connexion PDO
    $pdo = DataBase::getConnection();
    if (!isset($_GET['id_niveau'])) {
        throw new Exception('ID du niveau manquant');
    }

    $idNiveau = (int) $_GET['id_niveau'];
    if ($idNiveau <= 0) {
        throw new Exception('ID du niveau invalide');
    }

    $stmt = $pdo->prepare("
        SELECT id_semestre, lib_semestre 
        FROM semestre 
        WHERE id_niv_etd = ?
        ORDER BY lib_semestre ASC
    ");
    
    $stmt->execute([$idNiveau]);
    $semestres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($semestres)) {
        echo json_encode([
            'success' => false,
            'message' => 'Aucun semestre trouvé pour ce niveau'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'semestres' => $semestres
        ]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>