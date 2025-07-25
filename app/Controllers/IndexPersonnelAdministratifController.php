<?php

require_once '../config/config.php';

class IndexPersonnelAdministratifController {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getConnection();
    }

    public function index() {
        // Vérification de connexion et du type d'utilisateur
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Vérification que l'utilisateur est bien du personnel administratif
        $allowedGroups = [2, 3, 4]; // Chargé de communication, Responsable scolarité, Secrétaire
        $userGroups = $_SESSION['user_groups'] ?? [];
        
        // Vérifier si l'utilisateur a au moins un des groupes autorisés
        $hasAccess = false;
        foreach ($userGroups as $group) {
            if (in_array($group, $allowedGroups)) {
                $hasAccess = true;
                break;
            }
        }
        
        if (!$hasAccess) {
            $_SESSION['error_message'] = "Accès non autorisé à l'interface personnel administratif";
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Inclusion de la vue
        include __DIR__ . '/../Views/index_personnel_administratif.php';
    }
} 
