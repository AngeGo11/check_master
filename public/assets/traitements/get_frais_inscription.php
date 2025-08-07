<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../../config/config.php';

try {
    // Vérifier l'authentification
    if (!isset($_SESSION['id_personnel_adm'])) {
        // Essayer de récupérer l'ID du personnel depuis la session utilisateur
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT id_personnel_adm FROM personnel_administratif WHERE id_user = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $_SESSION['id_personnel_adm'] = $result['id_personnel_adm'];
            }
        }
        
        if (!isset($_SESSION['id_personnel_adm'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Non autorisé']);
            exit;
        }
    }

    // Récupérer les paramètres
    $niveau_id = isset($_GET['niveau_id']) ? intval($_GET['niveau_id']) : null;
    $annee_id = isset($_GET['annee_id']) ? intval($_GET['annee_id']) : null;

    error_log("get_frais_inscription - Niveau ID: " . $niveau_id);
    error_log("get_frais_inscription - Année ID: " . $annee_id);

    if (!$niveau_id || !$annee_id) {
        error_log("get_frais_inscription - Paramètres manquants");
        echo json_encode(['success' => false, 'message' => 'Niveau et année requis']);
        exit;
    }

    $pdo = DataBase::getConnection();

    // Récupérer les frais d'inscription pour le niveau et l'année
    $sql = "SELECT fi.montant, ne.lib_niv_etd, aa.id_ac as annee_ac
            FROM frais_inscription fi
            JOIN niveau_etude ne ON fi.id_niv_etd = ne.id_niv_etd
            JOIN annee_academique aa ON fi.id_ac = aa.id_ac
            WHERE fi.id_niv_etd = ? AND fi.id_ac = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$niveau_id, $annee_id]);
    $frais = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("get_frais_inscription - Résultat requête: " . print_r($frais, true));

    if (!$frais) {
        error_log("get_frais_inscription - Aucun frais trouvé");
        echo json_encode([
            'success' => false, 
            'message' => 'Aucun tarif défini pour ce niveau et cette année académique'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'montant' => intval($frais['montant']),
        'niveau' => $frais['lib_niv_etd'],
        'annee' => $frais['annee_ac']
    ]);

} catch (Exception $e) {
    error_log("Erreur AJAX get_frais_inscription: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
