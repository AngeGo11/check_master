<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/config.php';

try {
    // Créer la connexion PDO
    $pdo = DataBase::getConnection();
    
    // Récupérer l'année académique en cours
    $sql = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1";
    $stmt = $pdo->query($sql);
    $annee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($annee) {
        echo json_encode([
            'success' => true,
            'id_ac' => $annee['id_ac'],
            'date_debut' => $annee['date_debut'],
            'date_fin' => $annee['date_fin']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Aucune année académique en cours trouvée'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la récupération de l\'année académique: ' . $e->getMessage()
    ]);
}
?> 