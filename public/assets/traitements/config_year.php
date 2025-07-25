<?php


// Endpoint JSON pour récupérer l'année universitaire en cours
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    try {
        // Inclure la connexion à la base de données
        require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/config/db_connect.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/app/Models/AnneeAcademique.php';
        
        // Créer une instance du modèle
        $anneeModel = new App\Models\AnneeAcademique($pdo);
        
        // Récupérer l'année académique en cours
        $currentYear = $anneeModel->getCurrentAcademicYear();
        
        echo json_encode([
            'success' => true,
            'current_year' => $currentYear
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur lors de la récupération de l\'année académique: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Définir l'année académique en cours si elle n'existe pas
if (!isset($_SESSION['current_year']) || empty($_SESSION['current_year'])) {
    // Inclure le modèle AnneeAcademique
    require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/app/Models/AnneeAcademique.php';
    
    $anneeAcademique = new App\Models\AnneeAcademique($pdo);
    $_SESSION['current_year'] = $anneeAcademique->getCurrentAcademicYear();
}



?>