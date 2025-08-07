<?php
require_once '../../../app/config/config.php';

// Activation du logging
ini_set('log_errors', 1);
ini_set('error_log', '../../logs/php-error.log');
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_GET['numero_reglement'])) {
    die('Numéro de règlement manquant.');
}
$numero_reglement = $_GET['numero_reglement'];

// ✅ ÉTAPE 1: Récupération des infos de base du règlement (SANS JOIN avec paiement_reglement)
$stmt = $pdo->prepare("SELECT 
        e.nom_etd, e.prenom_etd, e.num_carte_etd, e.num_etd,
        n.lib_niv_etd, 
        r.mode_de_paiement, r.numero_cheque, r.motif_paiement,
        r.id_reglement, r.numero_reglement, r.montant_a_payer, r.total_paye, r.date_reglement
    FROM reglement r
    JOIN etudiants e ON r.num_etd = e.num_etd
    LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd
    WHERE r.numero_reglement = ?
    LIMIT 1");
$stmt->execute([$numero_reglement]);
$reglement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reglement) {
    die('Règlement introuvable.');
}

$num_etd = $reglement['num_etd'];
$id_reglement = $reglement['id_reglement'];
$montant_total = floatval($reglement['montant_a_payer']);
$total_paye_initial = floatval($reglement['total_paye']);

// ✅ ÉTAPE 2: Calcul correct du total payé (reglement.total_paye + somme paiement_reglement)
$stmtTotal = $pdo->prepare("SELECT COALESCE(SUM(p.montant_paye), 0) 
    FROM paiement_reglement p
    WHERE p.id_reglement = ?");
$stmtTotal->execute([$id_reglement]);
$total_paiements_supplementaires = floatval($stmtTotal->fetchColumn());

// Total payé = premier paiement + paiements supplémentaires
$total_paye = $total_paye_initial + $total_paiements_supplementaires;
$reste_a_payer = $montant_total - $total_paye;
$statut = ($total_paye >= $montant_total) ? 'Payé' : (($total_paye > 0) ? 'Partiel' : 'Non payé');

// ✅ ÉTAPE 3: Gestion des numéros de reçu
$numero_recu = isset($_GET['numero_recu']) ? $_GET['numero_recu'] : null;

// Si pas de numéro de reçu spécifique, prendre le dernier
if (!$numero_recu) {
    // Chercher d'abord dans paiement_reglement
    $stmtRecu = $pdo->prepare("SELECT numero_recu FROM paiement_reglement 
        WHERE id_reglement = ?
        ORDER BY date_paiement DESC LIMIT 1");
    $stmtRecu->execute([$id_reglement]);
    $dernierPaiement = $stmtRecu->fetch(PDO::FETCH_ASSOC);

    if ($dernierPaiement) {
        $numero_recu = $dernierPaiement['numero_recu'];
    } else {
        // Si aucun paiement supplémentaire, utiliser le reçu du règlement initial
        $numero_recu = 'REG-' . $numero_reglement;
    }
}

// ✅ ÉTAPE 4: Récupération du paiement spécifique
$paiement_unique = null;
if ($numero_recu) {
    if (strpos($numero_recu, 'REG-') === 0) {
        // ✅ CORRECTION: Paiement initial (table reglement SEULEMENT)
        $paiement_unique = [
            'date_paiement' => $reglement['date_reglement'],
            'montant_paye' => $total_paye_initial,
            'mode_de_paiement' => $reglement['mode_de_paiement'], // Valeur par défaut
            'numero_cheque' => $reglement['numero_cheque'],
            'motif_paiement' => $reglement['motif_paiement'],
            'numero_recu' => $numero_recu,
            'numero_reglement' => $reglement['numero_reglement'],
            'montant_a_payer' => $reglement['montant_a_payer'],
            'nom_etd' => $reglement['nom_etd'],
            'prenom_etd' => $reglement['prenom_etd'],
            'num_carte_etd' => $reglement['num_carte_etd'],
            'lib_niv_etd' => $reglement['lib_niv_etd']
        ];
    } else {
        // Paiement supplémentaire (table paiement_reglement)
        $stmtPaiement = $pdo->prepare("SELECT p.*, r.numero_reglement, r.montant_a_payer, e.nom_etd, e.prenom_etd, e.num_carte_etd, n.lib_niv_etd
            FROM paiement_reglement p
            JOIN reglement r ON p.id_reglement = r.id_reglement
            JOIN etudiants e ON r.num_etd = e.num_etd
            LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd
            WHERE p.numero_recu = ? LIMIT 1");
        $stmtPaiement->execute([$numero_recu]);
        $paiement_unique = $stmtPaiement->fetch(PDO::FETCH_ASSOC);
    }
}

// ✅ ÉTAPE 5: Surcharge avec les paramètres GET si fournis
if ($paiement_unique) {
    if (isset($_GET['mode_de_paiement'])) {
        $paiement_unique['mode_de_paiement'] = $_GET['mode_de_paiement'];
    }
    if (isset($_GET['numero_cheque'])) {
        $paiement_unique['numero_cheque'] = $_GET['numero_cheque'];
    }
    if (isset($_GET['motif_paiement'])) {
        $paiement_unique['motif_paiement'] = $_GET['motif_paiement'];
    }
}

// Données pour le QR Code
$nom_complet = ($paiement_unique ? ($paiement_unique['nom_etd'] ?? '') . ' ' . ($paiement_unique['prenom_etd'] ?? '') : ($reglement['nom_etd'] ?? '') . ' ' . ($reglement['prenom_etd'] ?? ''));
$montant_affiche = $paiement_unique ? floatval($paiement_unique['montant_paye']) : $total_paye;
$date_paiement = $paiement_unique ? $paiement_unique['date_paiement'] : $reglement['date_reglement'];

$qr_data = json_encode([
    'numero_recu' => $numero_recu,
    'numero_reglement' => $numero_reglement,
    'etudiant' => trim($nom_complet),
    'montant' => $montant_affiche,
    'date' => date('Y-m-d', strtotime($date_paiement)),
    'statut' => $statut
]);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Reçu de paiement</title>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@700&display=swap" rel="stylesheet">
    <script src="../qrcodejs/qrcode.min.js"></script>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #fff;
            color: #212529;
        }

        .recu-form-style {
            width: 900px;
            margin: 60px auto;
            padding: 50px 60px 35px 60px;
            border: 2px solid #000;
            border-radius: 0;
            background: #fff;
            box-shadow: none;
        }

        .recu-form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0;
        }

        .recu-form-header img {
            height: 120px;
        }

        .recu-form-header .header-center {
            flex: 1;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            line-height: 1.3;
        }

        .recu-form-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 30px;
            margin-bottom: 30px;
            position: relative;
        }

        .recu-form-num {
            font-size: 26px;
            color: #c62828;
            font-weight: bold;
            min-width: 200px;
            text-align: left;
        }

        .recu-form-title {
            font-size: 38px;
            font-weight: bold;
            text-align: center;
            flex: 1;
            letter-spacing: 2px;
        }

        .recu-form-right {
            min-width: 200px;
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .recu-form-bpf {
            font-size: 24px;
            color: #000;
            font-weight: bold;
        }

        .qr-code-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #fafafa;
        }

        .qr-code-canvas {
            border: 1px solid #ccc;
            border-radius: 4px;
            background: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Styles pour le tableau QR code généré par la bibliothèque */
        #qrcode table {
            border-collapse: collapse;
            margin: 0 auto;
        }

        #qrcode table td {
            width: 2px;
            height: 2px;
            padding: 0;
            margin: 0;
        }

        #qrcode table td.qr-cell-black {
            background-color: #000000 !important;
        }

        #qrcode table td.qr-cell-white {
            background-color: #FFFFFF !important;
        }

        .qr-code-label {
            font-size: 10px;
            color: #666;
            margin-top: 6px;
            text-align: center;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        @media print {
            .qr-code-container {
                border: 1px solid #000;
                background: white;
                box-shadow: none;
            }

            .qr-code-canvas {
                border: 1px solid #000;
                box-shadow: none;
            }

            .qr-code-label {
                color: #000;
            }
        }

        .recu-form-fields {
            margin-top: 25px;
            margin-bottom: 25px;
        }

        .recu-form-line {
            display: flex;
            align-items: center;
            margin-bottom: 22px;
            font-size: 22px;
            position: relative;
        }

        .recu-form-label {
            min-width: 220px;
            font-weight: bold;
            font-size: 22px;
        }

        .recu-form-dots {
            flex: 1;
            border-bottom: 1px dotted #000;
            margin: 0 12px;
            height: 1.6em;
            position: relative;
        }

        .recu-form-value {
            font-family: 'Caveat', cursive;
            font-size: 28px;
            color: #1a237e;
            min-width: 160px;
            margin-left: 0;
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-60%);
            padding-left: 10px;
            background: transparent;
            pointer-events: none;
        }

        .recu-form-checkbox {
            display: inline-block;
            width: 24px;
            height: 24px;
            border: 2.5px solid #000;
            margin-right: 10px;
            vertical-align: middle;
            position: relative;
        }

        .recu-form-checkbox.checked::after {
            content: '\2713';
            position: absolute;
            left: 3px;
            top: -2px;
            font-size: 26px;
            color: #000;
        }

        .recu-form-footer {
            margin-top: 40px;
            font-size: 18px;
            text-align: left;
            color: #000;
        }

        .recu-form-signature {
            margin-top: 50px;
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .recu-form-signature-block {
            min-width: 220px;
            text-decoration: underline;
            text-align: center;
            font-weight: 700;
            font-size: 20px;
        }

        .recu-form-signature-line {
            border-bottom: 1px solid #000;
            width: 250px;
            margin-top: 40px;
        }

        .recu-form-mention {
            margin-top: 30px;
            font-size: 16px;
            color: #c62828;
            font-weight: 800;
            text-align: right;
            font-style: italic;
        }
    </style>
</head>

<body onload="setTimeout(function() { window.print(); }, 5000);">
    <div class="recu-form-style">
        <div class="recu-form-header">
            <img src="../../assets/images/logo ufhb.png" alt="Logo UFHB" onerror="this.style.display='none'">
            <div class="header-center">
                FILIERES PROFESSIONNALISEES, UFR MI<br>
                UNIVERSITE DE COCODY<br>
                22 B.P. 582 Abidjan 22<br>
                Tél. (Fax) : 22 41 05 74 / 22 48 01 80<br>
                Cel : 07 89 94 26 / 07 69 15 04
            </div>
            <img src="../../assets/images/logo_mi.png" alt="Logo MI" onerror="this.style.display='none'">
        </div>
        <div class="recu-form-title-row">
            <div class="recu-form-num">N° : <?php echo htmlspecialchars($numero_recu ?? ''); ?></div>
            <div class="recu-form-title">REÇU</div>
            <div class="recu-form-right">
                <div class="recu-form-bpf">B.P.F. : <?php echo isset($_GET['bpf']) ? htmlspecialchars($_GET['bpf'] ?? '') : '________'; ?></div>

            </div>
        </div>
        <div class="recu-form-fields">
            <div class="recu-form-line" style="position:relative;">
                <span class="recu-form-label">Reçu de M.</span>
                <span class="recu-form-dots">
                    <span class="recu-form-value" style="left:0;top:50%;transform:translateY(-60%);">
                        <?php echo htmlspecialchars(trim($nom_complet)); ?>
                    </span>
                </span>
            </div>
            <div class="recu-form-line" style="position:relative;">
                <span class="recu-form-label">La somme de</span>
                <span class="recu-form-dots">
                    <span class="recu-form-value" style="left:0;top:50%;transform:translateY(-60%);">
                        <?php echo number_format($montant_affiche, 0, ',', ' ') . ' FCFA'; ?>
                    </span>
                </span>
            </div>
            <div class="recu-form-line" style="position:relative;">
                <span class="recu-form-label">En règlement de</span>
                <span class="recu-form-dots">
                    <span class="recu-form-value" style="left:0;top:50%;transform:translateY(-60%);">
                        <?php
                        $motif = $paiement_unique ? ($paiement_unique['motif_paiement'] ?? 'Scolarité') : 'Scolarité';
                        echo htmlspecialchars($motif);
                        ?>
                    </span>
                </span>
            </div>
            <div class="recu-form-line" style="position:relative;">
                <span class="recu-form-label">Année d'Études</span>
                <span class="recu-form-dots">
                    <span class="recu-form-value" style="left:0;top:50%;transform:translateY(-60%);">
                        <?php
                        $niveau = $paiement_unique ? ($paiement_unique['lib_niv_etd'] ?? '') : ($reglement['lib_niv_etd'] ?? '');
                        echo htmlspecialchars($niveau);
                        ?>
                    </span>
                </span>
            </div>
            <div class="recu-form-line">
                <?php
                $mode_paiement = $paiement_unique ? ($paiement_unique['mode_de_paiement'] ?? 'espece') : 'espece';
                $est_espece = ($mode_paiement === 'espece');
                $est_cheque = ($mode_paiement === 'cheque');
                ?>
                <span class="recu-form-checkbox<?php echo $est_espece ? ' checked' : ''; ?>"></span>
                <span class="recu-form-label">Espèces</span>
                <span class="recu-form-checkbox<?php echo $est_cheque ? ' checked' : ''; ?>"></span>
                <span style="margin-left: 20px;">Chèque n°</span>
                <span class="recu-form-dots">
                    <span class="recu-form-value" style="left:0;top:50%;transform:translateY(-60%);">
                        <?php
                        if ($est_cheque) {
                            $num_cheque = $paiement_unique ? ($paiement_unique['numero_cheque'] ?? '') : '';
                            if (!empty($num_cheque) && strtolower($num_cheque) !== 'Néant') {
                                echo htmlspecialchars($num_cheque);
                            }
                        }
                        ?>
                    </span>
                </span>
            </div>
            <div class="recu-form-line" style="position:relative;">
                <span class="recu-form-label">Date</span>
                <span class="recu-form-dots">
                    <span class="recu-form-value" style="left:0;top:50%;transform:translateY(-60%);">
                        <?php echo date('d/m/Y', strtotime($date_paiement)); ?>
                    </span>
                </span>
            </div>
            <div class="recu-form-line" style="position:relative;">
                <span class="recu-form-label">Reste à payer</span>
                <span class="recu-form-dots">
                    <span class="recu-form-value" style="left:0;top:50%;transform:translateY(-60%);">
                        <?php
                        if ($paiement_unique && strpos($numero_recu, 'REG-') !== 0) {
                            // Pour un paiement supplémentaire, calculer le reste après ce paiement
                            $stmtTotalAvant = $pdo->prepare("
                            SELECT COALESCE(SUM(montant_paye), 0) 
                            FROM paiement_reglement 
                            WHERE id_reglement = ? AND date_paiement <= ? AND numero_recu <= ?
                        ");
                            $stmtTotalAvant->execute([$id_reglement, $paiement_unique['date_paiement'], $paiement_unique['numero_recu']]);
                            $totalPayeJusquaPaiement = floatval($stmtTotalAvant->fetchColumn()) + $total_paye_initial;
                            $reste = max(0, $montant_total - $totalPayeJusquaPaiement);
                            echo number_format($reste, 0, ',', ' ') . ' FCFA';
                        } else {
                            // Pour le règlement initial ou vue globale
                            echo number_format($reste_a_payer, 0, ',', ' ') . ' FCFA';
                        }
                        ?>
                    </span>
                </span>
            </div>
        </div>
        <div class="recu-form-signature" style="display: flex; justify-content: space-between; align-items: center;">
            <div class="recu-form-signature-block">
                Signature et cachet
                <div class="recu-form-signature-line"></div>
            </div>
            <div>
                <div id="qrcode" class="qr-code-canvas"></div>
            </div>
        </div>
        <div class="recu-form-footer">
            <span style="font-family: 'Caveat', cursive; font-size: 18px; color: #1a237e;">imprimé via Check Master</span>
        </div>
        <div class="recu-form-mention">
            N.B. : Aucun remboursement n'est possible après versement.
        </div>
    </div>

    <script>
        // Attendre que le DOM soit chargé
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM chargé, début de génération QR Code');

            try {
                // Vérifier si la bibliothèque QRCode est disponible
                if (typeof QRCode === 'undefined') {
                    console.error('Bibliothèque QRCode non chargée');
                    document.getElementById('qrcode').innerHTML = 'QR Code indisponible';
                    return;
                }

                // Vérifier si l'élément existe
                var qrcodeElement = document.getElementById("qrcode");
                if (!qrcodeElement) {
                    console.error('Élément qrcode introuvable');
                    return;
                }

                console.log('Élément qrcode trouvé:', qrcodeElement);

                // Générer le QR Code avec les données du reçu
                // URL simplifiée pour faciliter le scan
                <?php
                $server_ip = $_SERVER['SERVER_ADDR'] ?? '192.168.1.64';
                $server_port = $_SERVER['SERVER_PORT'] ?? '8083';
                $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                $base_url = $protocol . '://' . $server_ip . ':' . $server_port;
                ?>
                var qrText = "http://<?php echo '192.168.1.64:8083'; ?>/public/verifier_recu.php?r=<?php echo urlencode($numero_recu); ?>&n=<?php echo urlencode($numero_reglement); ?>";

                console.log('Texte QR Code:', qrText);

                var qrcode = new QRCode(qrcodeElement, {
                    text: qrText,
                    width: 120,
                    height: 120,
                    colorDark: '#000000',
                    colorLight: '#FFFFFF',
                    correctLevel: QRCode.CorrectLevel.L
                });

                console.log('QR Code généré avec succès');

                // Vérifier que le QR code a bien été généré
                setTimeout(function() {
                    if (qrcodeElement.innerHTML.trim() !== '') {
                        console.log('QR Code visible dans le DOM');
                        qrcodeElement.style.border = '2px solid green'; // Indicateur visuel
                    } else {
                        console.error('QR Code vide dans le DOM');
                        qrcodeElement.innerHTML = 'Erreur: QR Code vide';
                    }
                }, 1000);

            } catch (error) {
                console.error('Erreur lors de la génération du QR Code:', error);
                document.getElementById('qrcode').innerHTML = 'Erreur QR Code: ' + error.message;
            }
        });
    </script>
</body>

</html>