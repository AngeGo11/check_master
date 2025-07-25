<?php
require_once '../../../config/db_connect.php';

// Récupération du numéro de règlement depuis l'URL
$id_reclamation = isset($_GET['id_reclamation']) ? $_GET['id_reclamation'] : '';

// Récupération des informations de l'étudiant et de la réclamation
$sql = "SELECT e.*, n.lib_niv_etd, rec.motif_reclamation, rec.matieres
        FROM etudiants e 
        JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd 
        JOIN reclamations rec ON rec.num_etd = e.num_etd
        JOIN annee_academique a ON a.id_ac = rec.id_ac
        WHERE rec.id_reclamation = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_reclamation]);
$reclamation = $stmt->fetch(PDO::FETCH_ASSOC);

// Conversion des matières de JSON en tableau
$matieres = json_decode($reclamation['matieres'], true) ?: [];

// Récupération de l'année académique en cours
$sql_annee = "SELECT CONCAT(YEAR(date_debut), '-', YEAR(date_fin)) as annee 
              FROM annee_academique 
              WHERE statut_annee = 'En cours'";
$stmt_annee = $pdo->prepare($sql_annee);
$stmt_annee->execute();
$annee_academique = $stmt_annee->fetchColumn();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche de Réclamation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header-top {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 10px;
            width: 100%;
            margin: auto;
        }
        .logo-placeholder {
            width: 80px;
            height: 60px;
            border: 1px solid #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #666;
        }

        .logo-placeholder .logo-univ{
            width: 160px;
            height: 140px;
        }

        .logo-placeholder .logo-ufr{
            width: 130px;
            height: 110px;
        }


        .institution-info {
            text-align: center;
            font-size: 12px;
            margin: 10px 0;
        }
        .title-box {
            border: 2px solid #000;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }
        .form-field {
            margin: 15px 0;
            display: flex;
            align-items: center;
        }
        .form-field label {
            min-width: 200px;
            font-weight: normal;
        }
        .form-field .value {
            flex: 1;
            border-bottom: 1px dotted #000;
            height: 20px;
            margin-left: 10px;
            padding: 0 5px;
        }
        .section-title {
            font-weight: bold;
            margin: 25px 0 15px 0;
        }
        .text-area {
            border: 1px solid #000;
            min-height: 100px;
            padding: 10px;
            margin: 10px 0;
        }
        .signature-section {
            margin-top: 40px;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }
        .signature-table td {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
            height: 80px;
            vertical-align: top;
        }
        .date-line {
            text-align: right;
            margin: 20px 0;
        }
        .print-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 20px 0;
        }
        .print-button:hover {
            background-color: #45a049;
        }
        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-top">
            <div class="logo-placeholder"><img class="logo-univ" src="../../../assets/images/ufhb-logo-sbg.png" alt=""></div>
            <div style="flex: 1;">
                <div class="institution-info">
                    <strong>RÉPUBLIQUE DE CÔTE D'IVOIRE</strong><br>
                    MINISTÈRE DE L'ENSEIGNEMENT SUPÉRIEUR ET DE LA RECHERCHE SCIENTIFIQUE<br>
                    UNIVERSITÉ FÉLIX HOUPHOUËT BOIGNY D'ABIDJAN-COCODY<br>
                    <em>UFR Mathématiques et Informatique</em>
                </div>
            </div>
            <div class="logo-placeholder"><img class="logo-ufr" src="../../../assets/images/logo_mi_sbg" alt=""></div>
        </div>
        
        <div class="title-box">
            <h2 style="margin: 0;">FICHE DE RÉCLAMATION</h2>
            <h3 style="margin: 10px 0 0 0;">Année Académique : <?php echo $annee_academique; ?></h3>
        </div>
    </div>

    <div class="form-field">
        <label> <strong> Numéro de la carte d'étudiant : </strong> </label>
        <div class="value"><?php echo htmlspecialchars($reclamation['num_carte_etd']); ?></div>
    </div>

    <div class="form-field">
        <label> <strong> Nom et prénoms : </strong></label>
        <div class="value"><?php echo htmlspecialchars($reclamation['nom_etd'] . ' ' . $reclamation['prenom_etd']); ?></div>
    </div>

    <div class="form-field">
        <label> <strong> Niveau d'études : </strong></label>
        <div class="value"><?php echo htmlspecialchars($reclamation['lib_niv_etd']); ?></div>
    </div>

    <div class="form-field">
        <label> <strong> Motif de la réclamation (précis et succinct) : </strong></label>
        <div class="value"><?php echo htmlspecialchars($reclamation['motif_reclamation']); ?></div>
    </div>

    <div class="section-title">Matière à préciser :</div>
    <table style="width: 100%; border-collapse: collapse; border: 1px solid #000; margin: 10px 0;">
        <?php foreach($matieres as $matiere): ?>
        <tr>
            <td style="border-bottom: 1px solid #000; height: 25px; padding: 5px;"><?php echo htmlspecialchars($matiere); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php for($i = count($matieres); $i < 5; $i++): ?>
        <tr>
            <td style="border-bottom: 1px solid #000; height: 25px; padding: 5px;"></td>
        </tr>
        <?php endfor; ?>
    </table>

    <div class="date-line">
        Fait à Abidjan, le <?php echo date('d/m/Y'); ?>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <strong>Signature de l'étudiant</strong>
    </div>

    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td style="width: 33.33%;"><strong>Signature du Superviseur</strong></td>
                <td style="width: 33.33%;"><strong>Signature du Responsable de niveau</strong></td>
                <td style="width: 33.33%;"><strong>Signature du Responsable de filière</strong></td>
            </tr>
            <tr>
                <td style="height: 100px;"></td>
                <td style="height: 100px;"></td>
                <td style="height: 100px;"></td>
            </tr>
        </table>
    </div>

    <button class="print-button" onclick="window.print()">Imprimer ce document</button>

    <script>
        function printDocument() {
            window.print();
        }
    </script>
</body>
</html>
