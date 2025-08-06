<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');

try {
    // Créer la connexion PDO
    $pdo = DataBase::getConnection();
    $numero = $_GET['numero'] ?? '';
    
    if (empty($numero)) {
        throw new Exception('Numéro d\'étudiant requis');
    }

    // Récupérer l'ID de l'étudiant
    $stmt = $pdo->prepare("SELECT num_etd FROM etudiants WHERE num_carte_etd = ?");
    $stmt->execute([$numero]);
    $num_etd = $stmt->fetchColumn();

    if (!$num_etd) {
        throw new Exception('Étudiant non trouvé');
    }

    // Récupérer toutes les évaluations (UE et ECUE) de l'étudiant
    $sql = "
        SELECT * FROM (
            SELECT DISTINCT 
                'UE' as type_evaluation,
                ue.lib_ue,
                NULL as lib_ecue,
                ev.note,
                ev.credit,
                ev.date_eval,
                s.lib_semestre,
                COALESCE(CONCAT(ens.nom_ens, ' ', ens.prenoms_ens), '') as nom_enseignant,
                ev.id_ue,
                NULL as id_ecue,
                s.id_semestre as semestre_order
            FROM evaluer_ue ev
            JOIN ue ON ev.id_ue = ue.id_ue
            JOIN semestre s ON ev.id_semestre = s.id_semestre
            LEFT JOIN enseignants ens ON ens.id_ens = ue.id_ens
            JOIN personnel_administratif pa ON ev.id_personnel_adm = pa.id_personnel_adm
            WHERE ev.num_etd = ?

            UNION ALL

            SELECT DISTINCT 
                'ECUE' as type_evaluation,
                ue.lib_ue,
                ec.lib_ecue,
                ev.note,
                ev.credit,
                ev.date_eval,
                s.lib_semestre,
                COALESCE(CONCAT(ens.nom_ens, ' ', ens.prenoms_ens), '') as nom_enseignant,
                ue.id_ue,
                ec.id_ecue,
                s.id_semestre as semestre_order
            FROM evaluer_ecue ev
            JOIN ecue ec ON ev.id_ecue = ec.id_ecue
            JOIN ue ON ec.id_ue = ue.id_ue
            JOIN semestre s ON ev.id_semestre = s.id_semestre
            LEFT JOIN enseignants ens ON ens.id_ens = ec.id_ens
            JOIN personnel_administratif pa ON ev.id_personnel_adm = pa.id_personnel_adm
            WHERE ev.num_etd = ?
        ) AS combined_evaluations
        ORDER BY semestre_order, lib_ue, lib_ecue
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$num_etd, $num_etd]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $evaluations
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 