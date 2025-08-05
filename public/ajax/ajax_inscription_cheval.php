<?php
require_once '../../app/config/config.php';
require_once '../../app/Controllers/EtudiantsController.php';

header('Content-Type: application/json');

try {
    $controller = new EtudiantsController();
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'get-matieres-rattrapage':
                $annee_id = $_POST['annee_id'] ?? null;
                $promotion_id = $_POST['promotion_id'] ?? null;
                $etudiants_ids = json_decode($_POST['etudiants_ids'] ?? '[]', true);
                
                $result = $controller->getMatieresRattrapage($annee_id, $promotion_id, $etudiants_ids);
                echo json_encode($result);
                break;
                
            case 'calculer-frais-cheval':
                $niveau_id = $_POST['niveau_id'] ?? null;
                $annee_id = $_POST['annee_id'] ?? null;
                $matieres_ids = json_decode($_POST['matieres_ids'] ?? '[]', true);
                
                $result = $controller->calculerFraisCheval($niveau_id, $annee_id, $matieres_ids);
                echo json_encode($result);
                break;
                
            case 'inscrire-etudiants-cheval':
                $data = [
                    'etudiants' => json_decode($_POST['etudiants'] ?? '[]', true),
                    'matieres' => json_decode($_POST['matieres'] ?? '[]', true),
                    'annee_id' => $_POST['annee_id'] ?? null,
                    'promotion_principale' => $_POST['promotion_principale'] ?? null,
                    'total_frais' => $_POST['total_frais'] ?? 0
                ];
                
                $result = $controller->inscrireEtudiantsCheval($data);
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    }
} catch (Exception $e) {
    error_log("Erreur AJAX inscription cheval: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?> 