<?php




class HomeController {
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

        // Redirection vers la page d'accueil publique
        header('Location: index.php');
        exit();
    }
} 
