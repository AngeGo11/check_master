<?php
// Démarrer la session
session_start();

require_once __DIR__ . '/../../../config/config.php';

// Établir la connexion à la base de données
$pdo = DataBase::getConnection();

// Vérification de sécurité
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

try {
    // Récupérer toutes les réunions programmées ou en cours
    $stmt = $pdo->prepare("
        SELECT id, titre, date_reunion, heure_debut, duree, status 
        FROM reunions 
        WHERE status IN ('programmée', 'en cours')
        ORDER BY date_reunion ASC, heure_debut ASC
    ");
    $stmt->execute();
    $reunions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updatedCount = 0;
    $currentDateTime = new DateTime();
    
    foreach ($reunions as $reunion) {
        $reunionDate = new DateTime($reunion['date_reunion']);
        $heureDebut = new DateTime($reunion['heure_debut']);
        $duree = floatval($reunion['duree']); // Durée en heures
        
        // Combiner la date et l'heure de début
        $debutReunion = new DateTime($reunion['date_reunion'] . ' ' . $reunion['heure_debut']);
        
        // Calculer la fin de la réunion
        $finReunion = clone $debutReunion;
        $finReunion->add(new DateInterval('PT' . intval($duree * 60) . 'M')); // Convertir les heures en minutes
        
        $nouveauStatut = null;
        
        // Vérifier le statut selon l'heure actuelle
        if ($currentDateTime < $debutReunion) {
            // Avant le début de la réunion
            $nouveauStatut = 'programmée';
        } elseif ($currentDateTime >= $debutReunion && $currentDateTime <= $finReunion) {
            // Pendant la réunion
            $nouveauStatut = 'en cours';
        } else {
            // Après la fin de la réunion
            $nouveauStatut = 'terminée';
        }
        
        // Mettre à jour le statut seulement s'il a changé
        if ($nouveauStatut !== $reunion['status']) {
            $updateStmt = $pdo->prepare("
                UPDATE reunions 
                SET status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$nouveauStatut, $reunion['id']]);
            $updatedCount++;
            
            // Log du changement de statut
            error_log("Réunion {$reunion['titre']} (ID: {$reunion['id']}) : statut changé de '{$reunion['status']}' vers '$nouveauStatut'");
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Mise à jour terminée. $updatedCount réunion(s) mise(s) à jour.",
        'updated_count' => $updatedCount,
        'current_time' => $currentDateTime->format('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log('Erreur mise à jour automatique statuts réunions: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 