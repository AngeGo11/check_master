<?php
require_once '../../../app/config/config.php';
require_once '../../../app/Models/Etudiant.php';

header('Content-Type: application/json');

try {
    // Vérifier les permissions (optionnel)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Créer une instance du modèle Etudiant
    $pdo = DataBase::getConnection();
    $etudiantModel = new App\Models\Etudiant($pdo);
    
    // Récupérer l'année académique en cours
    $stmt = $pdo->prepare("SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1");
    $stmt->execute();
    $id_ac = $stmt->fetchColumn();
    
    if (!$id_ac) {
        throw new Exception("Aucune année académique en cours trouvée");
    }
    
    // Lancer le recalcul des moyennes générales
    $resultat = $etudiantModel->calculerEtMettreAJourMoyennesGenerales($id_ac);
    
    if ($resultat['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Moyennes recalculées avec succès',
            'success_count' => $resultat['success_count'],
            'error_count' => $resultat['error_count'],
            'total_etudiants' => $resultat['total_etudiants']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $resultat['message'] ?? 'Erreur lors du recalcul'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur recalcul moyennes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du recalcul : ' . $e->getMessage()
    ]);
}
?> 