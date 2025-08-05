<?php
/**
 * Script de mise à jour automatique des statuts des réunions
 * À exécuter via cron toutes les minutes : * * * * * php /path/to/cron_update_meeting_status.php
 */

require_once __DIR__ . '/../../../config/config.php';

// Établir la connexion à la base de données
$pdo = DataBase::getConnection();

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
            $logMessage = date('Y-m-d H:i:s') . " - Réunion {$reunion['titre']} (ID: {$reunion['id']}) : statut changé de '{$reunion['status']}' vers '$nouveauStatut'";
            error_log($logMessage);
            
            // Écrire dans un fichier de log spécifique
            file_put_contents(__DIR__ . '/../../../storage/logs/meeting_status_updates.log', $logMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
    
    // Log du résumé
    $summaryMessage = date('Y-m-d H:i:s') . " - Mise à jour terminée. $updatedCount réunion(s) mise(s) à jour.";
    error_log($summaryMessage);
    file_put_contents(__DIR__ . '/../../../storage/logs/meeting_status_updates.log', $summaryMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    
    // Retourner un code de sortie approprié
    if ($updatedCount > 0) {
        echo "SUCCESS: $updatedCount réunion(s) mise(s) à jour.\n";
        exit(0);
    } else {
        echo "INFO: Aucune mise à jour nécessaire.\n";
        exit(0);
    }
    
} catch (Exception $e) {
    $errorMessage = date('Y-m-d H:i:s') . " - ERREUR mise à jour automatique statuts réunions: " . $e->getMessage();
    error_log($errorMessage);
    file_put_contents(__DIR__ . '/../../../storage/logs/meeting_status_updates.log', $errorMessage . PHP_EOL, FILE_APPEND | LOCK_EX);
    
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?> 