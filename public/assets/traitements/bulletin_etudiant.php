<?php
// On s'assure que les erreurs sont affichées pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/app/Models/AnneeAcademique.php';

// Récupération du numéro de l'étudiant
if (!isset($_GET['numero'])) {
    die("Numéro d'étudiant non fourni.");
}
$numero_carte_etd = $_GET['numero'];

// Utiliser le modèle pour récupérer l'année académique
$anneeModel = new App\Models\AnneeAcademique($pdo);
$annee_en_cours = $anneeModel->getCurrentAcademicYear();

// --- RÉCUPÉRATION DES DONNÉES ---

// 1. Informations de l'étudiant
$stmtEtudiant = $pdo->prepare("
    SELECT e.*, n.lib_niv_etd
    FROM etudiants e
    LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd
    WHERE e.num_carte_etd = ?
");
$stmtEtudiant->execute([$numero_carte_etd]);
$etudiant = $stmtEtudiant->fetch(PDO::FETCH_ASSOC);

$lib_niveau_etude = explode(" ", $etudiant['lib_niv_etd']);
$grade = $lib_niveau_etude[0];
$niveau = $lib_niveau_etude[1];

if (!$etudiant) {
    die("Aucun étudiant trouvé avec ce numéro.");
}
$num_etd = $etudiant['num_etd'];

// 2. Récupérer toutes les notes (UE et ECUE) et les organiser par semestre
$stmtNotes = $pdo->prepare("
    SELECT 
        s.id_semestre, s.lib_semestre, u.id_ue, u.lib_ue, u.credit_ue, 
        'ue' as type, ev.note, ev.credit as credit_obtenu, u.lib_ue as lib_ecue
    FROM evaluer_ue ev
    JOIN ue u ON ev.id_ue = u.id_ue
    JOIN semestre s ON ev.id_semestre = s.id_semestre
    WHERE ev.num_etd = :num_etd1
    AND NOT EXISTS (SELECT 1 FROM ecue WHERE id_ue = u.id_ue)

    UNION ALL

    SELECT 
        s.id_semestre, s.lib_semestre, u.id_ue, u.lib_ue, u.credit_ue, 
        'ecue' as type, ev.note, ev.credit as credit_obtenu, e.lib_ecue
    FROM evaluer_ecue ev
    JOIN ecue e ON ev.id_ecue = e.id_ecue
    JOIN ue u ON e.id_ue = u.id_ue
    JOIN semestre s ON ev.id_semestre = s.id_semestre
    WHERE ev.num_etd = :num_etd2
    
    ORDER BY id_semestre, id_ue
");
$stmtNotes->execute([':num_etd1' => $num_etd, ':num_etd2' => $num_etd]);
$all_notes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);

// 3. Organiser les données pour l'affichage
$semestres_data = [];
foreach ($all_notes as $note) {
    $id_semestre = $note['id_semestre'];
    $id_ue = $note['id_ue'];

    // Initialiser le semestre s'il n'existe pas
    if (!isset($semestres_data[$id_semestre])) {
        $semestres_data[$id_semestre] = [
            'lib_semestre' => $note['lib_semestre'],
            'ues_majeures' => [],
            'ues_mineures' => []
        ];
    }

    // Initialiser l'UE si elle n'existe pas dans le semestre
    if (!isset($semestres_data[$id_semestre]['ues_majeures'][$id_ue]) && !isset($semestres_data[$id_semestre]['ues_mineures'][$id_ue])) {
        $ue_data = [
            'lib_ue' => $note['lib_ue'],
            'coef' => $note['credit_ue'],
            'ecues' => [],
            'moyenne' => 0,
            'credits_obtenus' => 0
        ];
        if ($note['credit_ue'] >= 4) { // UE Majeure
            $semestres_data[$id_semestre]['ues_majeures'][$id_ue] = $ue_data;
        } else { // UE Mineure
            $semestres_data[$id_semestre]['ues_mineures'][$id_ue] = $ue_data;
        }
    }
    
    // Ajouter la note (ECUE ou UE simple)
    $target_ue = &$semestres_data[$id_semestre][($note['credit_ue'] >= 4 ? 'ues_majeures' : 'ues_mineures')][$id_ue];
    $target_ue['ecues'][] = [
        'lib' => $note['type'] === 'ue' ? $note['lib_ue'] : $note['lib_ecue'],
        'note' => $note['note']
    ];
}

// 4. Calculer les moyennes par UE
foreach ($semestres_data as $id_semestre => &$semestre) {
    foreach (['ues_majeures', 'ues_mineures'] as $type) {
        foreach ($semestre[$type] as $id_ue => &$ue) {
            $total_notes_ue = 0;
            $count_notes = count($ue['ecues']);
            if ($count_notes > 0) {
                foreach ($ue['ecues'] as $ecue) {
                    $total_notes_ue += $ecue['note'];
                }
                $ue['moyenne'] = $total_notes_ue / $count_notes;
                if ($ue['moyenne'] >= 10) {
                    $ue['credits_obtenus'] = $ue['coef'];
                }
            }
        }
    }
}
unset($semestre, $ue); // Rompre les références

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relevé de Notes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f5f5f5; }
        .document { background: white; max-width: 800px; margin: 0 auto; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); color: #000; }
        .header-section { display: flex; justify-content: space-between; align-items: flex-start; }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .header-republic-text { font-size: 10px; line-height: 1.3; font-weight: bold; text-align: center; }
        .logo-ufhb { width: 95px; height: 85px; }
        .logo-miage { width: 95px; height: 85px; }
        .header-divider { border: none; border-top: 2px solid #000; margin: 15px 0 20px 0; }
        .info-title-section { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
        .student-info-table { font-size: 11px; }
        .student-info-table table { border-collapse: collapse; }
        .student-info-table td { padding: 1.5px 5px 1.5px 0; vertical-align: top; }
        .student-info-table td:first-child { font-weight: bold; min-width: 120px; }
        .main-title { text-align: center; }
        .main-title h1, .main-title h2 { margin: 2px 0; font-weight: bold; }
        .main-title h1 { font-size: 16px; }
        .main-title h2 { font-size: 13px; }
        .semester h3 { background: #f0f0f0; padding: 8px; margin: 20px 0 10px 0; font-size: 14px; border-left: 4px solid #333; }
        .grades-table { width: 100%; border-collapse: collapse; font-size: 11px; margin-bottom: 20px; }
        .grades-table th, .grades-table td { border: 1px solid #000; padding: 4px 6px; text-align: left; }
        .grades-table th { background: #f8f8f8; font-weight: bold; text-align: center; }
        .grades-table .numeric { text-align: center; }
        .grades-table .section-header { background: #e8e8e8; font-weight: bold; }
        .grades-table .total-row { background: #f0f0f0; font-weight: bold; }
        .grades-table .semester-result-row td { border-top: 2px solid #000; font-weight: bold; }
        .result-section { margin-top: 30px; border: 1px solid #000; padding: 15px; font-size: 11px; }
        .result-section h4 { margin: 0 0 10px 0; text-decoration: underline; }
        .final-result { text-align: center; margin-top: 20px; font-weight: bold; }
        .calculation-details { margin-top: 15px; font-size: 10px; color: #666; }
        .no-print { position: fixed; top: 15px; right: 15px; z-index: 1000; }
        .no-print button { padding: 8px 15px; margin-left: 10px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; color: white; }
        .no-print .print-btn { background-color: #28a745; }
        .no-print .close-btn { background-color: #dc3545; }
        @media print { .no-print { display: none; } body { background: white; } .document { box-shadow: none; border: none; } .calculation-details { display: none; } }
    </style>
</head>
<body>

<div class="no-print">
    <button class="print-btn" onclick="window.print()">Imprimer</button>
    <button class="close-btn" onclick="window.close()">Fermer</button>
</div>

<div class="document">
    <div class="header-section">
        <div class="header-left">
            <img src="../../../assets/images/logo ufhb.png" alt="Logo UFHB" class="logo-ufhb">
        </div>
        <div class="header-center">
            <div class="header-republic-text">
                REPUBLIQUE DE COTE D'IVOIRE<br>
                UNION DISCIPLINE TRAVAIL<br><br>
                MINISTERE CHARGE DE L'ENSEIGNEMENT SUPERIEUR<br>
                DE LA RECHERCHE SCIENTIFIQUE
            </div>
        </div>
        <div class="header-right">
            <img src="../../../assets/images/logo_mi_sbg.png" alt="Logo MIAGE" class="logo-miage">
        </div>
    </div>
    <hr class="header-divider">

    <div class="info-title-section">
        <div class="student-info-table">
            <table>
                <tr><td>NOM</td><td>: <?= htmlspecialchars($etudiant['nom_etd'] ?? '') ?></td></tr>
                <tr><td>PRENOMS</td><td>: <?= htmlspecialchars($etudiant['prenom_etd'] ?? '') ?></td></tr>
                <tr><td>DATE DE NAISSANCE</td><td>: <?= isset($etudiant['date_naissance_etd']) ? date('d/m/Y', strtotime($etudiant['date_naissance_etd'])) : '' ?></td></tr>
                <tr><td>LIEU DE NAISSANCE</td><td>: <?= htmlspecialchars($etudiant['lieu_naissance_etd'] ?? 'Non défini') ?></td></tr>
                <tr><td>MENTION</td><td>: <?= htmlspecialchars($etudiant['mention'] ?? 'Informatique') ?></td></tr>
                <tr><td>PARCOURS</td><td>: <?= htmlspecialchars($etudiant['parcours'] ?? 'MIAGE') ?></td></tr>
                <tr><td>GRADE</td><td>: <?= htmlspecialchars($grade ?? 'Inconnu') ?></td></tr>
                <tr><td>NIVEAU</td><td>: <?= htmlspecialchars($niveau ?? 'Inconnu') ?></td></tr>
                <tr><td>N° CARTE ETUDIANT</td><td>: <?= htmlspecialchars($etudiant['num_carte_etd'] ?? '') ?></td></tr>
            </table>
        </div>
        <div class="main-title">
            <h2>FILIERES PROFESSIONNALISEES</h2>
            <h2>(GI-MIAGE)</h2>
            <h2><?= htmlspecialchars($annee_en_cours) ?></h2>
            <h1>RELEVE DE NOTES</h1>
        </div>
    </div>

    <?php
    $moyennes_semestrielles = [];
    $details_calculs = []; // Pour stocker les détails des calculs
    
    foreach($semestres_data as $id_semestre => $data) {
        // Calcul pour UE Majeures
        $total_coef_maj = 0;
        $total_moy_pond_maj = 0;
        $total_cred_obtenus_maj = 0;
        foreach($data['ues_majeures'] as $code => $ue) {
            $total_coef_maj += $ue['coef'];
            $total_moy_pond_maj += $ue['moyenne'] * $ue['coef'];
            $total_cred_obtenus_maj += $ue['credits_obtenus'];
        }
        $moyenne_maj = ($total_coef_maj > 0) ? $total_moy_pond_maj / $total_coef_maj : 0;

        // Calcul pour UE Mineures
        $total_coef_min = 0;
        $total_moy_pond_min = 0;
        $total_cred_obtenus_min = 0;
        foreach($data['ues_mineures'] as $code => $ue) {
            $total_coef_min += $ue['coef'];
            $total_moy_pond_min += $ue['moyenne'] * $ue['coef'];
            $total_cred_obtenus_min += $ue['credits_obtenus'];
        }
        $moyenne_min = ($total_coef_min > 0) ? $total_moy_pond_min / $total_coef_min : 0;

        // CALCUL CORRIGÉ DE LA MOYENNE SEMESTRIELLE
        // Formule : (MOYENNES UE MAJEURES*TOTAL CREDIT UE MAJEUR + MOYENNES UE MINEURES*TOTAL CREDIT UE MINEUR)/30
        $moyenne_semestre = ($moyenne_maj * $total_coef_maj + $moyenne_min * $total_coef_min) / 30;
        
        $moyennes_semestrielles[] = $moyenne_semestre;
        
        // Stocker les détails pour affichage (optionnel)
        $details_calculs[] = [
            'semestre' => $data['lib_semestre'],
            'moyenne_maj' => $moyenne_maj,
            'total_coef_maj' => $total_coef_maj,
            'moyenne_min' => $moyenne_min,
            'total_coef_min' => $total_coef_min,
            'moyenne_semestre' => $moyenne_semestre
        ];
        
        // Affichage du tableau du semestre
        ?>
        <div class="semester">
            <h3><?= strtoupper($data['lib_semestre']) ?></h3>
            <table class="grades-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th style="width: 40%;">Matière</th>
                        <th>Coef</th>
                        <th>Moyenne/20</th>
                        <th>Crédits</th>
                        <th>Session</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- UE Majeures -->
                    <tr class="section-header"><td colspan="6"><strong>UE MAJEURES</strong></td></tr>
                    <?php
                    foreach($data['ues_majeures'] as $code => $ue) {
                        echo "<tr><td>{$code}</td><td>{$ue['lib_ue']}</td><td class='numeric'>{$ue['coef']}</td><td class='numeric'>".number_format($ue['moyenne'], 2)."</td><td class='numeric'>{$ue['credits_obtenus']}</td><td class='numeric'>1</td></tr>";
                    }
                    ?>
                    <tr class="total-row"><td colspan="2"><strong>Moyenne UE Majeures et crédits</strong></td><td class="numeric"><strong><?= $total_coef_maj ?></strong></td><td class="numeric"><strong><?= number_format($moyenne_maj, 2) ?></strong></td><td class="numeric"><strong><?= $total_cred_obtenus_maj ?></strong></td><td></td></tr>
                    
                    <!-- UE Mineures -->
                    <tr class="section-header"><td colspan="6"><strong>UE MINEURES</strong></td></tr>
                    <?php
                    foreach($data['ues_mineures'] as $code => $ue) {
                        echo "<tr><td>{$code}</td><td>{$ue['lib_ue']}</td><td class='numeric'>{$ue['coef']}</td><td class='numeric'>".number_format($ue['moyenne'], 2)."</td><td class='numeric'>{$ue['credits_obtenus']}</td><td class='numeric'>1</td></tr>";
                    }
                    ?>
                    <tr class="total-row"><td colspan="2"><strong>Moyenne UE Mineures et crédits</strong></td><td class="numeric"><strong><?= $total_coef_min ?></strong></td><td class="numeric"><strong><?= number_format($moyenne_min, 2) ?></strong></td><td class="numeric"><strong><?= $total_cred_obtenus_min ?></strong></td><td></td></tr>
                    
                    <!-- Résultat semestre -->
                    <?php
                        $total_coef_semestre = $total_coef_maj + $total_coef_min;
                        $total_cred_obtenus_semestre = $total_cred_obtenus_maj + $total_cred_obtenus_min;
                    ?>
                    <tr class="semester-result-row">
                        <td colspan="2"><strong>MOYENNE SEMESTRIELLE ET CREDITS CAPITALISES</strong></td>
                        <td class="numeric"><?= $total_coef_semestre ?></td>
                        <td class="numeric"><?= number_format($moyenne_semestre, 2) ?></td>
                        <td class="numeric"><?= $total_cred_obtenus_semestre ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
            
            
        </div>
        <?php
    }
    
    // CALCUL CORRIGÉ DE LA MOYENNE ANNUELLE
    // Formule : (MOYENNES SEMESTRE 1*30 + MOYENNES SEMESTRE 2*30)/60
    if (count($moyennes_semestrielles) == 2) {
        $moyenne_generale_annuelle = ($moyennes_semestrielles[0] * 30 + $moyennes_semestrielles[1] * 30) / 60;
        $formule_annuelle = "({$moyennes_semestrielles[0]} × 30 + {$moyennes_semestrielles[1]} × 30) ÷ 60";
    } elseif (count($moyennes_semestrielles) == 1) {
        // Si un seul semestre, la moyenne annuelle = moyenne du semestre
        $moyenne_generale_annuelle = $moyennes_semestrielles[0];
        $formule_annuelle = "Un seul semestre : {$moyennes_semestrielles[0]}";
    } else {
        $moyenne_generale_annuelle = 0;
        $formule_annuelle = "Aucun semestre évalué";
    }
    
    ?>

    <div class="result-section">
        <h4>RESULTAT GENERAL</h4>
        <div class="result-text">
            Un Semestre n'est validé que si la moyenne des UE majeures et celle des UE mineures sont toutes >=10.<br>
            La note plancher de chaque UE étant de 05/20.<br>
            L'étudiant n'est déclaré admis que s'il a obtenu 30 Crédits par semestre.
        </div>
        
        
        
        <div class="final-result">
            <strong>Résultat (Délibération du jury) :</strong> 
            <?php 
            $statut_admission = "Non Admis";
            $total_credits_obtenus = 0;
            
            // Vérifier les crédits obtenus par semestre
            foreach($semestres_data as $data) {
                $credits_semestre = 0;
                foreach($data['ues_majeures'] as $ue) {
                    $credits_semestre += $ue['credits_obtenus'];
                }
                foreach($data['ues_mineures'] as $ue) {
                    $credits_semestre += $ue['credits_obtenus'];
                }
                $total_credits_obtenus += $credits_semestre;
            }
            
            // Conditions d'admission : moyenne >= 10 ET crédits suffisants
            if ($moyenne_generale_annuelle >= 10 && $total_credits_obtenus >= (count($semestres_data) * 30)) {
                $statut_admission = "Admis";
            }
            
            echo $statut_admission;
            ?><br>
            <strong>Moyenne générale : <?= number_format($moyenne_generale_annuelle, 2) ?></strong><br>
            <strong>Total crédits obtenus : <?= $total_credits_obtenus ?> / <?= count($semestres_data) * 30 ?></strong>
        </div>
    </div>
</div>

</body>
</html>