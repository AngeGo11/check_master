<?php 
header('Content-Type: application/json'); 
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';   

try {         
    $numero_reglement = $_POST['numero_reglement'] ?? '';      
    
    if (empty($numero_reglement)) {         
        throw new Exception("Numéro de règlement manquant");     
    }      

    // ✅ ÉTAPE 1: Récupérer les infos du règlement principal
    $stmt = $pdo->prepare("
            SELECT 
                id_reglement, 
                montant_a_payer, 
                total_paye,
                date_reglement,
                numero_reglement,
                mode_de_paiement,
                numero_cheque,
                motif_paiement
            FROM reglement 
            WHERE numero_reglement = ?
            ");   
    $stmt->execute([$numero_reglement]);     
    $reglement = $stmt->fetch(PDO::FETCH_ASSOC);      

    if (!$reglement) {         
        throw new Exception("Règlement introuvable.");     
    }      

    $id_reglement = $reglement['id_reglement'];
    $montant_total = floatval($reglement['montant_a_payer']);
    $total_paye_initial = floatval($reglement['total_paye']);
    
    $paiements = [];

    // ✅ ÉTAPE 3: Ajouter les PAIEMENTS SUPPLÉMENTAIRES (depuis paiement_reglement)
    $stmt = $pdo->prepare("
        SELECT 
            date_paiement,
            mode_de_paiement,
            numero_cheque,
            motif_paiement,
            montant_paye,
            numero_recu
        FROM paiement_reglement 
        WHERE id_reglement = ?
        ORDER BY date_paiement ASC
    ");     
    $stmt->execute([$id_reglement]);     
    $paiements_supplementaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ✅ ÉTAPE 2: Ajouter le PREMIER PAIEMENT (depuis reglement.total_paye) UNIQUEMENT si aucun paiement n'existe dans paiement_reglement
    if ($total_paye_initial > 0 && count($paiements_supplementaires) === 0) {
        $paiements[] = [
            'date_paiement' => $reglement['date_reglement'],
            'montant_paye' => $total_paye_initial,
            'total_paye' => $total_paye_initial,
            'reste_a_payer' => max(0, $montant_total - $total_paye_initial),
            'numero_recu' => 'REG-' . $reglement['numero_reglement'],
            'numero_reglement' => $reglement['numero_reglement'],
            'mode_de_paiement' => $reglement['mode_de_paiement'],
            'numero_cheque' => $reglement['numero_cheque'],
            'motif_paiement' => $reglement['motif_paiement'],
            'source' => 'reglement_initial'
        ];
    }

    // Ajouter les paiements supplémentaires
    $total_cumule = 0;
    foreach ($paiements_supplementaires as $paiement) {
        $montant_paye = floatval($paiement['montant_paye']);
        $total_cumule += $montant_paye;
        $paiements[] = [
            'date_paiement' => $paiement['date_paiement'],
            'montant_paye' => $montant_paye,
            'total_paye' => $total_cumule,
            'mode_de_paiement' => $paiement['mode_de_paiement'],
            'numero_cheque' => $paiement['numero_cheque'],
            'motif_paiement' => $paiement['motif_paiement'],
            'reste_a_payer' => max(0, $montant_total - $total_cumule),
            'numero_recu' => $paiement['numero_recu'],
            'numero_reglement' => $reglement['numero_reglement'],
            'source' => 'paiement_supplementaire'
        ];
    }

    // ✅ ÉTAPE 5: Trier par date (plus récent en premier)
    usort($paiements, function($a, $b) {
        return strtotime($b['date_paiement']) - strtotime($a['date_paiement']);
    });

    // ✅ ÉTAPE 6: Vérifier qu'on a des paiements
    if (empty($paiements)) {
        throw new Exception("Aucun paiement enregistré pour ce règlement.");
    }
    
    echo json_encode($paiements);  

} catch (Exception $e) {     
    http_response_code(400);     
    echo json_encode(['error' => $e->getMessage()]); 
} catch (PDOException $e) {     
    http_response_code(500);     
    echo json_encode(['error' => "Erreur serveur : " . $e->getMessage()]); 
}