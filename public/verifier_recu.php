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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Vérification - <?php echo htmlspecialchars($numero_recu); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            background: linear-gradient(135deg, #1a5276 0%, #2471a3 100%);
            min-height: 100vh;
            padding: 10px;
            color: #333;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(26, 82, 118, 0.3);
            margin: 0 auto;
            max-width: 400px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.4s ease-out;
            border: 2px solid #1a5276;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            background: linear-gradient(135deg, #1a5276, #2471a3);
            color: white;
            padding: 25px 20px;
            text-align: center;
            position: relative;
            border-bottom: 3px solid #1a5276;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #1a5276, #85c1e9, #1a5276);
        }
        
        .logo {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            font-family: 'Times New Roman', serif;
        }
        
        .title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
            font-family: 'Times New Roman', serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .subtitle {
            font-size: 14px;
            opacity: 0.9;
            font-style: italic;
            font-family: 'Times New Roman', serif;
        }
        
        .content {
            padding: 25px 20px 20px;
            background: #f8f9fa;
        }
        
        .status-card {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .status::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status.paid {
            background: linear-gradient(45deg, #d4edda, #c3e6cb);
            color: #155724;
            border: 2px solid #28a745;
            font-family: 'Times New Roman', serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status.paid::before {
            background: #28a745;
        }
        
        .status.partial {
            background: linear-gradient(45deg, #fff3cd, #ffeaa7);
            color: #856404;
            border: 2px solid #ffc107;
            font-family: 'Times New Roman', serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status.partial::before {
            background: #ffc107;
        }
        
        .status.unpaid {
            background: linear-gradient(45deg, #f8d7da, #f5c6cb);
            color: #721c24;
            border: 2px solid #dc3545;
            font-family: 'Times New Roman', serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status.unpaid::before {
            background: #dc3545;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #1a5276;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 15px;
            padding-left: 5px;
            font-family: 'Times New Roman', serif;
            border-left: 4px solid #1a5276;
            padding-left: 12px;
        }
        
        .info-card {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid #e8e8e8;
            box-shadow: 0 2px 8px rgba(26, 82, 118, 0.1);
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 16px 20px;
            border-bottom: 1px solid #e8e8e8;
            gap: 15px;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #1a5276;
            flex-shrink: 0;
            font-size: 15px;
            font-family: 'Times New Roman', serif;
        }
        
        .info-value {
            color: #333;
            text-align: right;
            font-weight: 500;
            word-break: break-word;
            font-family: 'Times New Roman', serif;
        }
        
        .amount {
            font-size: 18px;
            font-weight: bold;
            color: #1a5276;
            font-family: 'Times New Roman', serif;
        }
        
        .amount.negative {
            color: #dc3545;
        }
        
        .student-name {
            font-weight: bold;
            color: #1a5276;
            font-family: 'Times New Roman', serif;
        }
        
        .verification-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(45deg, #1a5276, #2471a3);
            color: white;
            padding: 15px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            margin: 25px 0;
            box-shadow: 0 4px 15px rgba(26, 82, 118, 0.4);
            font-family: 'Times New Roman', serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .verification-badge::before {
            content: '✓';
            font-weight: bold;
            font-size: 18px;
            background: white;
            color: #1a5276;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .footer {
            padding: 25px 20px;
            background: #1a5276;
            color: white;
            text-align: center;
            font-size: 13px;
            line-height: 1.6;
            font-family: 'Times New Roman', serif;
            font-style: italic;
        }
        
        .footer p {
            margin-bottom: 8px;
            opacity: 0.9;
        }
        
        /* Améliorations pour le touch */
        .touchable {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .touchable:active {
            transform: scale(0.98);
        }
        
        /* Animation pour les éléments importants */
        .amount {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
        }
        
        /* Responsive pour très petits écrans */
        @media (max-width: 320px) {
            body {
                padding: 5px;
                font-size: 14px;
            }
            
            .container {
                border-radius: 12px;
            }
            
            .header {
                padding: 15px;
            }
            
            .content {
                padding: 20px 15px 15px;
            }
            
            .title {
                font-size: 18px;
            }
            
            .info-item {
                padding: 14px 16px;
                gap: 10px;
            }
            
            .amount {
                font-size: 16px;
            }
        }
        
        /* Mode sombre (détection automatique) */
        @media (prefers-color-scheme: dark) {
            .container {
                background: #1a1a1a;
                color: #f5f5f5;
            }
            
            .info-card {
                background: #2a2a2a;
            }
            
            .info-item {
                border-bottom-color: rgba(255,255,255,0.1);
            }
            
            .info-label {
                color: #aaa;
            }
            
            .info-value {
                color: #f5f5f5;
            }
            
            .footer {
                background: #2a2a2a;
                color: #aaa;
            }
            
            .section-title {
                color: #aaa;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">CM</div>
            <h1 class="title">Vérification de reçu</h1>
            <p class="subtitle">Check Master</p>
        </div>
        
        <div class="content">
            <div class="status-card">
                <div class="status <?php echo strtolower($statut) === 'payé' ? 'paid' : (strtolower($statut) === 'partiel' ? 'partial' : 'unpaid'); ?>">
                    <?php echo htmlspecialchars($statut); ?>
                </div>
            </div>
            
            <div class="info-section">
                <h2 class="section-title">Informations du reçu</h2>
                <div class="info-card">
                    <div class="info-item">
                        <span class="info-label">N° reçu</span>
                        <span class="info-value"><?php echo htmlspecialchars($numero_recu); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">N° règlement</span>
                        <span class="info-value"><?php echo htmlspecialchars($numero_reglement); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Date</span>
                        <span class="info-value"><?php echo date('d/m/Y', strtotime($reglement['date_reglement'])); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="info-section">
                <h2 class="section-title">Étudiant</h2>
                <div class="info-card">
                    <div class="info-item">
                        <span class="info-label">Nom complet</span>
                        <span class="info-value student-name"><?php echo htmlspecialchars($reglement['nom_etd'] . ' ' . $reglement['prenom_etd']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Niveau</span>
                        <span class="info-value"><?php echo htmlspecialchars($reglement['lib_niv_etd'] ?? 'Non défini'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="info-section">
                <h2 class="section-title">Montants</h2>
                <div class="info-card">
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
                        <span class="info-value amount negative"><?php echo number_format($reste_a_payer, 0, ',', ' ') . ' FCFA'; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="verification-badge">
                Reçu authentifié
            </div>
        </div>
        
        <div class="footer">
            <p>Ce reçu a été vérifié automatiquement</p>
            <p>Check Master - Système de gestion scolaire</p>
        </div>
    </div>
    
    <script>
        // Amélioration de l'expérience tactile
        document.addEventListener('DOMContentLoaded', function() {
            // Animation d'entrée
            const container = document.querySelector('.container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.4s ease-out';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
            
            // Feedback tactile pour les éléments interactifs
            const touchableElements = document.querySelectorAll('.touchable');
            touchableElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>