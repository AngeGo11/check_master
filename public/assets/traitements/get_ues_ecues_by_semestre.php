<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['semestre_id'])) {
        throw new Exception('ID du semestre requis');
    }

    $semestre_id = $_GET['semestre_id'];
    $niveau_id = $_GET['niveau_id'] ?? null;

    // Récupérer toutes les UE du semestre
    $sql_ues = "
        SELECT DISTINCT u.id_ue, u.lib_ue, u.credit_ue
        FROM ue u
        JOIN evaluer_ue eue ON u.id_ue = eue.id_ue
        WHERE eue.id_semestre = :semestre_id
        ORDER BY u.credit_ue DESC, u.lib_ue
    ";
    
    $stmt_ues = $pdo->prepare($sql_ues);
    $stmt_ues->execute(['semestre_id' => $semestre_id]);
    $ues = $stmt_ues->fetchAll(PDO::FETCH_ASSOC);

    $majeures = [];
    $mineures = [];

    foreach ($ues as $ue) {
        // Récupérer les ECUE de cette UE
        $sql_ecues = "
            SELECT e.id_ecue, e.lib_ecue, e.credit_ecue
            FROM ecue e
            WHERE e.id_ue = :id_ue
            ORDER BY e.lib_ecue
        ";
        
        $stmt_ecues = $pdo->prepare($sql_ecues);
        $stmt_ecues->execute(['id_ue' => $ue['id_ue']]);
        $ecues = $stmt_ecues->fetchAll(PDO::FETCH_ASSOC);

        // Si pas d'ECUE, l'UE est évaluée directement
        if (empty($ecues)) {
            $ecues = [[
                'id_ecue' => $ue['id_ue'],
                'lib_ecue' => $ue['lib_ue'],
                'credit_ecue' => $ue['credit_ue'],
                'is_ue_direct' => true
            ]];
        } else {
            // Marquer les ECUE comme non-UE directe
            foreach ($ecues as &$ecue) {
                $ecue['is_ue_direct'] = false;
            }
        }

        $ue['ecues'] = $ecues;

        // Classer selon le nombre de crédits
        if ($ue['credit_ue'] >= 4) {
            $majeures[] = $ue;
        } else {
            $mineures[] = $ue;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'majeures' => $majeures,
            'mineures' => $mineures
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 