<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/Controllers/SoutenanceController.php';

// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../../storage/logs/php-error.log');

header('Content-Type: application/json');
session_start();

$required_fields = [
    'nom_entreprise',
    'adresse_entreprise',
    'ville_entreprise',
    'pays_entreprise',
    'telephone_entreprise',
    'email_entreprise',
    'intitule_stage',
    'description_stage',
    'type_stage',
    'date_debut_stage',
    'date_fin_stage',
    'nom_tuteur',
    'poste_tuteur',
    'telephone_tuteur',
    'email_tuteur'
];

$missing_fields = array_filter($required_fields, fn($field) => empty($_POST[$field]));

if ($missing_fields) {
    echo json_encode(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.']);
    exit();
}

if ($_POST['date_fin_stage'] < $_POST['date_debut_stage']) {
    echo json_encode(['success' => false, 'message' => 'La date de fin ne peut pas être antérieure à la date de début.']);
    exit();
}

try {
    // Initialiser la connexion à la base de données
    $pdo = DataBase::getConnection();
    
    $soutenanceController = new SoutenanceController($pdo);

    // Récupérer les données via le contrôleur
    $data = $soutenanceController->index($_SESSION['user_id']);
    $student_data = $data['studentData'];


    $student_id = $student_data['num_etd'];

    if ($soutenanceController->declareInternship($student_id, $_POST)) {
        echo json_encode(['success' => true, 'message' => 'Déclaration enregistrée.']);
    } else {
        throw new Exception('Erreur lors de l\'enregistrement');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
