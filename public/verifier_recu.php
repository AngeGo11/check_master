<?php
require_once __DIR__ . '/../app/config/config.php';

// Activation du logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-error.log');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Récupération des paramètres
$numero_recu = isset($_GET['numero_recu']) ? $_GET['numero_recu'] : '';
$numero_reglement = isset($_GET['numero_reglement']) ? $_GET['numero_reglement'] : '';
$etudiant = isset($_GET['etudiant']) ? $_GET['etudiant'] : '';
$montant = isset($_GET['montant']) ? $_GET['montant'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Validation des paramètres
if (empty($numero_recu) || empty($numero_reglement)) {
    http_response_code(400);
    die('Paramètres manquants');
}

try {
    $pdo = DataBase::getConnection();
    
    // Récupération des informations du règlement
    $stmt = $pdo->prepare("SELECT 
        e.nom_etd, e.prenom_etd, e.num_carte_etd,
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
        http_response_code(404);
        die('Règlement introuvable');
    }
    
    // Calcul du total payé
    $stmtTotal = $pdo->prepare("SELECT COALESCE(SUM(p.montant_paye), 0) 
        FROM paiement_reglement p
        WHERE p.id_reglement = ?");
    $stmtTotal->execute([$reglement['id_reglement']]);
    $total_paiements_supplementaires = floatval($stmtTotal->fetchColumn());
    
    $total_paye = floatval($reglement['total_paye']) + $total_paiements_supplementaires;
    $reste_a_payer = floatval($reglement['montant_a_payer']) - $total_paye;
    $statut = ($total_paye >= floatval($reglement['montant_a_payer'])) ? 'Payé' : (($total_paye > 0) ? 'Partiel' : 'Non payé');
    
} catch (Exception $e) {
    http_response_code(500);
    die('Erreur serveur');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de reçu - <?php echo htmlspecialchars($numero_recu); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            font-size: 16px;
        }
        
        .status {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            margin: 20px 0;
            font-size: 14px;
        }
        
        .status.paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status.partial {
            background: #fff3cd;
            color: #856404;
        }
        
        .status.unpaid {
            background: #f8d7da;
            color: #721c24;
        }
        
        .info-grid {
            display: grid;
            gap: 15px;
            margin: 30px 0;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .info-label {
            font-weight: bold;
            color: #333;
        }
        
        .info-value {
            color: #666;
            text-align: right;
        }
        
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        .verification-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">CM</div>
            <h1 class="title">Vérification de reçu</h1>
            <p class="subtitle">Check Master - Système de gestion</p>
        </div>
        
        <div class="status <?php echo strtolower($statut); ?>">
            <?php echo htmlspecialchars($statut); ?>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Numéro de reçu</span>
                <span class="info-value"><?php echo htmlspecialchars($numero_recu); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Numéro de règlement</span>
                <span class="info-value"><?php echo htmlspecialchars($numero_reglement); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Étudiant</span>
                <span class="info-value"><?php echo htmlspecialchars($reglement['nom_etd'] . ' ' . $reglement['prenom_etd']); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Niveau</span>
                <span class="info-value"><?php echo htmlspecialchars($reglement['lib_niv_etd'] ?? 'Non défini'); ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Montant total</span>
                <span class="info-value amount"><?php echo number_format($reglement['montant_a_payer'], 0, ',', ' ') . ' FCFA'; ?></span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Total payé</span>
                <span class="info-value amount"><?php echo number_format($total_paye, 0, ',', ' ') . ' FCFA'; ?></span>
            </div>
            
            <?php if ($reste_a_payer > 0): ?>
            <div class="info-item">
                <span class="info-label">Reste à payer</span>
                <span class="info-value amount" style="color: #dc3545;"><?php echo number_format($reste_a_payer, 0, ',', ' ') . ' FCFA'; ?></span>
            </div>
            <?php endif; ?>
            
            <div class="info-item">
                <span class="info-label">Date de règlement</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($reglement['date_reglement'])); ?></span>
            </div>
        </div>
        
        <div class="verification-badge">
            ✓ Reçu vérifié
        </div>
        
        <div class="footer">
            <p>Ce reçu a été généré automatiquement par le système Check Master</p>
            <p>Pour toute question, contactez l'administration</p>
        </div>
    </div>
</body>
</html>
