<?php

require_once __DIR__ . '/../../config/config.php';

class IndexEtudiantController {
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

        // Vérification que l'utilisateur est bien un étudiant
        $userGroups = $_SESSION['user_groups'] ?? [];
        if (!in_array(1, $userGroups)) {
            $_SESSION['error_message'] = "Accès non autorisé à l'interface étudiant";
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Récupération des données de l'étudiant
        $sql = "SELECT 
                u.id_utilisateur,
                u.login_utilisateur,
                etd.photo_etd 
                FROM utilisateur u
                LEFT JOIN etudiants etd ON etd.email_etd = u.login_utilisateur
                WHERE u.id_utilisateur = ?";

        $recupUser = $this->pdo->prepare($sql);
        $recupUser->execute([$_SESSION['user_id']]);
        $userData = $recupUser->fetch(PDO::FETCH_ASSOC);

        $_SESSION['photo_etd'] = $userData['photo_etd'];

        // Inclusion de la vue
        include __DIR__ . '/../Views/index_etudiant.php';
    }
} 
