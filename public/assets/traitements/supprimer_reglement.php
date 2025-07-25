<?php
session_start();
header('Content-Type: application/json');
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

try {
    if (isset($_POST['numero_reglement'])) {
        $numero = $_POST['numero_reglement'];
        // Supprimer les paiements associés
        $stmt = $pdo->prepare("DELETE FROM paiement_reglement WHERE id_reglement = (SELECT id_reglement FROM reglement WHERE numero_reglement = ? LIMIT 1)");
        $stmt->execute([$numero]);
        // Supprimer le règlement
        $stmt = $pdo->prepare("DELETE FROM reglement WHERE numero_reglement = ?");
        $stmt->execute([$numero]);
        $_SESSION['success_message'] = "Paiement supprimé avec succès";

        echo json_encode(['success' => true]);
        exit;
    }
    if (isset($_POST['numeros_reglement'])) {
        $numeros = json_decode($_POST['numeros_reglement'], true);
        if (!is_array($numeros)) throw new Exception('Format de données invalide.');
        foreach ($numeros as $numero) {
            $stmt = $pdo->prepare("DELETE FROM paiement_reglement WHERE id_reglement = (SELECT id_reglement FROM reglement WHERE numero_reglement = ? LIMIT 1)");
            $stmt->execute([$numero]);
            $stmt = $pdo->prepare("DELETE FROM reglement WHERE numero_reglement = ?");
            $stmt->execute([$numero]);

            
        }
        $_SESSION['success_message'] = "Paiement supprimé avec succès";
        echo json_encode(['success' => true]);
        
       
        
    }
    echo json_encode(['success' => false, 'error' => 'Aucun règlement à supprimer.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 