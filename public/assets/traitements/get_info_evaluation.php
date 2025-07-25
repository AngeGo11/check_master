<?php
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
session_start();

$id_personnel_adm = $_SESSION['id_personnel_adm'] ?? null;
$numero = $_GET['numero'] ?? '';
$semestre = $_GET['semestre'] ?? null;

if (!$id_personnel_adm || !$numero) {
    echo json_encode(['success' => false, 'message' => 'Identifiant personnel ou numéro étudiant manquant.']);
    exit;
}

try {
    // Étape 1 : Récupérer l'étudiant + promotion + semestre + niveau
    $stmt = $pdo->prepare("
        SELECT e.num_etd, e.nom_etd AS nom, e.prenom_etd AS prenom, a.id_ac,
               CONCAT(YEAR(a.date_debut), '-', YEAR(a.date_fin)) AS promotion,
               s.id_semestre, s.lib_semestre,
               ne.id_niv_etd, ne.lib_niv_etd AS niveau
        FROM etudiants e
        JOIN (
            SELECT num_etd, id_ac, id_semestre, id_personnel_adm FROM evaluer_ecue WHERE id_personnel_adm = :id_personnel
            UNION
            SELECT num_etd, id_ac, id_semestre, id_personnel_adm FROM evaluer_ue WHERE id_personnel_adm = :id_personnel
        ) ev ON ev.num_etd = e.num_etd
        JOIN annee_academique a ON a.id_ac = ev.id_ac
        JOIN semestre s ON s.id_semestre = ev.id_semestre
        JOIN niveau_etude ne ON ne.id_niv_etd = s.id_niv_etd
        WHERE e.num_carte_etd = :numero
        LIMIT 1
    ");
    
    $stmt->execute(['numero' => $numero, 'id_personnel' => $id_personnel_adm]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$etudiant) {
        echo json_encode(['success' => false, 'message' => "Étudiant non trouvé ou non évalué par vous."]);
        exit;
    }

    // Étape 2 : Récupérer toutes les ECUE et UE notées pour cet étudiant (et ce semestre si précisé)
    $sql_ecues = "
        SELECT 
            ue.id_ue, ue.lib_ue,
            ens.nom_ens, ens.prenoms_ens, ec.id_ecue, ec.lib_ecue, ec.credit_ecue, ec.id_ens, 
            ee.note, ee.date_eval,
            'ecue' as type
        FROM evaluer_ecue ee
        JOIN ecue ec ON ec.id_ecue = ee.id_ecue
        JOIN enseignants ens ON ens.id_ens = ec.id_ens
        JOIN ue ON ue.id_ue = ec.id_ue
        WHERE ee.num_etd = :num_etd AND ee.id_personnel_adm = :id_personnel
        ";
    if ($semestre) {
        $sql_ecues .= " AND ee.id_semestre = :semestre ";
    }
    $sql_ecues .= "
        UNION ALL
        SELECT 
            ue.id_ue, ue.lib_ue,
            ens_ue.nom_ens, ens_ue.prenoms_ens, ue.id_ue as id_ecue, ue.lib_ue as lib_ecue, ue.credit_ue as credit_ecue, ue.id_ens,
            eu.note, eu.date_eval,
            'ue' as type
        FROM evaluer_ue eu
        JOIN ue ON ue.id_ue = eu.id_ue
        LEFT JOIN enseignants ens_ue ON ens_ue.id_ens = ue.id_ens
        WHERE eu.num_etd = :num_etd AND eu.id_personnel_adm = :id_personnel
    ";
    if ($semestre) {
        $sql_ecues .= " AND eu.id_semestre = :semestre ";
    }
    $params = [
        'num_etd' => $etudiant['num_etd'],
        'id_personnel' => $id_personnel_adm
    ];
    if ($semestre) {
        $params['semestre'] = $semestre;
    }
    $stmt2 = $pdo->prepare($sql_ecues);
    $stmt2->execute($params);
    $ecues = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Étape 3 : Calcul de la moyenne selon la formule : (MOYENNE UE MINEURES*19 + MOYENNE UE MAJEURS*11)/30
    $totalNoteMajeures = 0;
    $totalNoteMineures = 0;
    $totalCreditMajeures = 0;
    $totalCreditMineures = 0;
    
    foreach ($ecues as &$ecue) {
        $ecue['date'] = date('Y-m-d', strtotime($ecue['date_eval']));
        // Si enseignant non renseigné
        if (empty($ecue['nom_ens']) && empty($ecue['prenoms_ens'])) {
            $ecue['nom_ens'] = 'Non renseigné';
            $ecue['prenoms_ens'] = '';
        }
        
        // Déterminer si c'est une UE majeure (crédit >= 4) ou mineure (crédit < 4)
        if ($ecue['credit_ecue'] >= 4) {
            // UE Majeure
            $totalNoteMajeures += $ecue['note'] * $ecue['credit_ecue'];
            $totalCreditMajeures += $ecue['credit_ecue'];
        } else {
            // UE Mineure
            $totalNoteMineures += $ecue['note'] * $ecue['credit_ecue'];
            $totalCreditMineures += $ecue['credit_ecue'];
        }
    }
    
    // Calculer les moyennes pondérées pour chaque type d'UE
    $moyenneMajeures = $totalCreditMajeures > 0 ? $totalNoteMajeures / $totalCreditMajeures : 0;
    $moyenneMineures = $totalCreditMineures > 0 ? $totalNoteMineures / $totalCreditMineures : 0;
    $totalCredits = $totalCreditMineures + $totalCreditMajeures;
    // Nouvelle formule : (Moyenne Mineures * Total Crédits Mineures + Moyenne Majeures * Total Crédits Majeures) / (Total Crédits Mineures + Total Crédits Majeures)
    if ($totalCredits > 0) {
        $moyenne = round((($moyenneMineures * $totalCreditMineures) + ($moyenneMajeures * $totalCreditMajeures)) / $totalCredits, 2);
    } else {
        $moyenne = 0;
    }

    // Envoi JSON
    $response = [
        'success' => true,
        'nom' => $etudiant['nom'],
        'prenom' => $etudiant['prenom'],
        'promotion' => $etudiant['promotion'],
        'niveau' => $etudiant['niveau'],
        'id_niv_etd' => $etudiant['id_niv_etd'],
        'semestre' => $etudiant['lib_semestre'],
        'id_semestre' => $etudiant['id_semestre'],
        'moyenne' => $moyenne,
        'ecues' => $ecues
    ];

    // Log pour debug
    error_log('Réponse JSON: ' . json_encode($response));
    
    echo json_encode($response);
} catch (PDOException $e) {
    error_log('Erreur PDO: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}