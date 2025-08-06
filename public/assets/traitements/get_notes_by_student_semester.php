<?php
require_once __DIR__ . '/../../../config/config.php';

header('Content-Type: application/json');

try {
    // Créer la connexion PDO
    $pdo = DataBase::getConnection();
    // Récupérer les paramètres
    $numero = $_GET['numero'] ?? '';
    $semestre = $_GET['semestre'] ?? '';

    if (empty($numero) || empty($semestre)) {
        throw new Exception('Numéro étudiant et semestre requis');
    }

    // Requête pour récupérer les notes existantes (UE et ECUE)
    $sql = "
        SELECT * FROM (
            SELECT 
                ev.id_ue as id_ecue,
                ev.note,
                ev.credit,
                ue.credit_ue as credit_ecue,
                ue.lib_ue as lib_ecue,
                ue.id_ue,
                ue.lib_ue
            FROM evaluer_ue ev
            INNER JOIN ue ON ev.id_ue = ue.id_ue
            WHERE ev.num_etd = (
                SELECT num_etd FROM etudiants WHERE num_carte_etd = ?
            )
            AND ev.id_semestre = ?

            UNION ALL

            SELECT 
                ev.id_ecue,
                ev.note,
                ev.credit,
                ec.credit_ecue,
                ec.lib_ecue,
                ue.id_ue,
                ue.lib_ue
            FROM evaluer_ecue ev
            INNER JOIN ecue ec ON ev.id_ecue = ec.id_ecue
            INNER JOIN ue ON ec.id_ue = ue.id_ue
            WHERE ev.num_etd = (
                SELECT num_etd FROM etudiants WHERE num_carte_etd = ?
            )
            AND ev.id_semestre = ?
        ) AS combined_notes
        ORDER BY lib_ue, lib_ecue
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$numero, $semestre, $numero, $semestre]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $notes
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 