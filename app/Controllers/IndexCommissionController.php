<?php

require_once __DIR__ . '/../../config/config.php';

class IndexCommissionController {
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

        // Vérification que l'utilisateur a accès à la commission
        $allowedGroups = [5, 6, 7, 8, 9]; // Enseignant, Responsable niveau, Responsable filière, Administrateur, Commission
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
            $_SESSION['error_message'] = "Accès non autorisé à l'interface commission";
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

    }
} 
