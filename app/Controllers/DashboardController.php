<?php
class DashboardController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function index() {
        // Vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header('Location: pageConnexion.php');
            exit();
        }

        // Redirection selon le type d'utilisateur
        if(isset($_SESSION['id_user_group'])){
            redirectByUserType($_SESSION['id_user_group']);
        }

        // Si pas de groupe défini, redirection vers la page de connexion
        header('Location: pageConnexion.php');
        exit();
    }
} 
