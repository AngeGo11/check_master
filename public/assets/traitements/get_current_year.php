<?php
session_start();
require_once __DIR__ . '/../../../config/config.php';

// Fonction pour récupérer l'année académique en cours
function getCurrentAcademicYear($pdo) {
    try {
        $sql = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1";
        $stmt = $pdo->query($sql);
        $annee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($annee) {
            $dateDebut = new DateTime($annee['date_debut']);
            $dateFin = new DateTime($annee['date_fin']);
            return $dateDebut->format('Y') . '-' . $dateFin->format('Y');
        }
        return "À définir";
    } catch (PDOException $e) {
        return "Erreur";
    }
}

// Endpoint JSON pour récupérer l'année universitaire en cours
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    try {
        // Inclure la connexion à la base de données
        require_once __DIR__ . '/../../../config/config.php';
        $pdo = DataBase::getConnection();
        
        // Récupérer l'année académique en cours
        $sql = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1";
        $stmt = $pdo->query($sql);
        $annee = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($annee) {
            $dateDebut = new DateTime($annee['date_debut']);
            $dateFin = new DateTime($annee['date_fin']);
            $current_year = $dateDebut->format('Y') . '-' . $dateFin->format('Y');
            
            echo json_encode([
                'success' => true,
                'current_year' => $current_year
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Aucune année académique en cours trouvée',
                'current_year' => 'À définir'
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la récupération de l\'année universitaire: ' . $e->getMessage(),
            'current_year' => 'À définir'
        ]);
    }
    exit;
}

// Définir l'année académique en cours si elle n'existe pas
if (!isset($_SESSION['current_year']) || empty($_SESSION['current_year'])) {
    $_SESSION['current_year'] = getCurrentAcademicYear($pdo);
}

// Fonction pour rafraîchir l'année académique
function refreshCurrentYear($pdo) {
    $newYear = getCurrentAcademicYear($pdo);
    $_SESSION['current_year'] = $newYear;
    return $newYear;
}

?>