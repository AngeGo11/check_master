<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

header('Content-Type: application/json');

try {
    $numero = $_GET['numero'] ?? '';
    $semestre = $_GET['semestre'] ?? '';
    $annee = $_GET['annee'] ?? '';

    if (empty($numero) || empty($semestre)) {
        throw new Exception('Numéro étudiant et semestre requis');
    }

    // Récupérer l'ID de l'étudiant
    $stmt_etudiant = $pdo->prepare("SELECT num_etd FROM etudiants WHERE num_carte_etd = ?");
    $stmt_etudiant->execute([$numero]);
    $num_etd = $stmt_etudiant->fetchColumn();

    if (!$num_etd) {
        throw new Exception('Étudiant non trouvé');
    }

    // Récupérer les notes et crédits pour le semestre
    $sql = "
        SELECT 
            ue.id_ue,
            ue.lib_ue,
            ue.credit_ue,
            ev.note,
            ev.credit,
            CASE 
                WHEN ue.credit_ue >= 4 THEN 'majeure'
                ELSE 'mineure'
            END as type_ue
        FROM evaluer_ecue ev
        INNER JOIN ecue ec ON ev.id_ecue = ec.id_ecue
        INNER JOIN ue ON ec.id_ue = ue.id_ue
        WHERE ev.num_etd = ?
        AND ev.id_semestre = ?
        ORDER BY ue.credit_ue DESC, ue.lib_ue
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$num_etd, $semestre]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Séparer les UE majeures et mineures
    $ue_majeures = [];
    $ue_mineures = [];

    foreach ($notes as $note) {
        if ($note['type_ue'] === 'majeure') {
            $ue_majeures[] = $note;
        } else {
            $ue_mineures[] = $note;
        }
    }

    // Calculer les moyennes
    $moyenne_majeures = 0;
    $total_credit_majeures = 0;
    $moyenne_mineures = 0;
    $total_credit_mineures = 0;

    // Moyenne UE majeures
    if (!empty($ue_majeures)) {
        $somme_notes_majeures = 0;
        $somme_credits_majeures = 0;
        
        foreach ($ue_majeures as $ue) {
            $somme_notes_majeures += $ue['note'] * $ue['credit'];
            $somme_credits_majeures += $ue['credit'];
        }
        
        if ($somme_credits_majeures > 0) {
            $moyenne_majeures = $somme_notes_majeures / $somme_credits_majeures;
        }
        $total_credit_majeures = $somme_credits_majeures;
    }

    // Moyenne UE mineures
    if (!empty($ue_mineures)) {
        $somme_notes_mineures = 0;
        $somme_credits_mineures = 0;
        
        foreach ($ue_mineures as $ue) {
            $somme_notes_mineures += $ue['note'] * $ue['credit'];
            $somme_credits_mineures += $ue['credit'];
        }
        
        if ($somme_credits_mineures > 0) {
            $moyenne_mineures = $somme_notes_mineures / $somme_credits_mineures;
        }
        $total_credit_mineures = $somme_credits_mineures;
    }

    // Calculer la moyenne semestrielle selon la formule
    $moyenne_semestre = 0;
    $total_credits = $total_credit_majeures + $total_credit_mineures;
    
    if ($total_credits > 0) {
        $moyenne_semestre = ($moyenne_majeures * $total_credit_majeures + $moyenne_mineures * $total_credit_mineures) / $total_credits;
    }

    // Si on demande la moyenne annuelle, calculer les deux semestres
    $moyenne_annuelle = null;
    if (!empty($annee)) {
        // Récupérer les moyennes des deux semestres
        $sql_annuelle = "
            SELECT 
                s.id_semestre,
                s.lib_semestre,
                ROUND((
                    (COALESCE(SUM(CASE WHEN ue.credit_ue >= 4 THEN ev.note * ev.credit ELSE 0 END) / NULLIF(SUM(CASE WHEN ue.credit_ue >= 4 THEN ev.credit ELSE 0 END), 0), 0) * SUM(CASE WHEN ue.credit_ue >= 4 THEN ev.credit ELSE 0 END)) +
                    (COALESCE(SUM(CASE WHEN ue.credit_ue < 4 THEN ev.note * ev.credit ELSE 0 END) / NULLIF(SUM(CASE WHEN ue.credit_ue < 4 THEN ev.credit ELSE 0 END), 0), 0) * SUM(CASE WHEN ue.credit_ue < 4 THEN ev.credit ELSE 0 END))
                ) / NULLIF(SUM(ev.credit), 0), 2) AS moyenne_semestre
            FROM evaluer_ecue ev
            INNER JOIN ecue ec ON ev.id_ecue = ec.id_ecue
            INNER JOIN ue ON ec.id_ue = ue.id_ue
            INNER JOIN semestre s ON ev.id_semestre = s.id_semestre
            WHERE ev.num_etd = ?
            AND ev.id_ac = ?
            GROUP BY s.id_semestre, s.lib_semestre
            ORDER BY s.id_semestre
        ";
        
        $stmt_annuelle = $pdo->prepare($sql_annuelle);
        $stmt_annuelle->execute([$num_etd, $annee]);
        $moyennes_semestres = $stmt_annuelle->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($moyennes_semestres) >= 2) {
            $moyenne_annuelle = ($moyennes_semestres[0]['moyenne_semestre'] * 30 + $moyennes_semestres[1]['moyenne_semestre'] * 30) / 60;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'moyenne_semestre' => round($moyenne_semestre, 2),
            'moyenne_annuelle' => $moyenne_annuelle ? round($moyenne_annuelle, 2) : null,
            'details' => [
                'ue_majeures' => [
                    'moyenne' => round($moyenne_majeures, 2),
                    'total_credits' => $total_credit_majeures,
                    'ues' => $ue_majeures
                ],
                'ue_mineures' => [
                    'moyenne' => round($moyenne_mineures, 2),
                    'total_credits' => $total_credit_mineures,
                    'ues' => $ue_mineures
                ]
            ]
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 