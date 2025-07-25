<?php

require_once __DIR__ . '/../Models/ArchiveModel.php';

class ArchiveController {
    private $model;

    public function __construct() {
        $this->model = new ArchiveModel();
    }

    /**
     * Affiche la page des archives
     */
    public function index() {
        // Vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Vérification des permissions (personnel administratif)
        $allowedGroups = [2, 3, 4]; // Chargé de communication, Responsable scolarité, Secrétaire
        $userGroups = $_SESSION['user_groups'] ?? [];
        
        $hasAccess = false;
        foreach ($userGroups as $group) {
            if (in_array($group, $allowedGroups)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $_SESSION['error_message'] = "Accès non autorisé à l'interface des archives";
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        $user_id = $_SESSION['user_id'];

        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date = $_GET['date'] ?? '';
        $type = $_GET['type'] ?? '';

        // Récupération des données
        $stats = $this->model->getStatistics($user_id);
        $archives = $this->model->getArchives($user_id, $search, $date, $type);

        // Inclusion de la vue
        include __DIR__ . '/../Views/archives.php';
    }

    /**
     * Supprime des archives (AJAX)
     */
    public function supprimerArchives() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['ids']) || !is_array($data['ids'])) {
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            return;
        }

        try {
            $this->model->supprimerArchives($data['ids'], $_SESSION['user_id']);
            echo json_encode(['success' => true, 'message' => 'Archives supprimées avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les données d'archives pour affichage dans la vue
     */
    public function viewArchives() {
        $user_id = $_SESSION['user_id'] ?? 0;
        
        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date = $_GET['date'] ?? '';
        $type = $_GET['type'] ?? '';
        $page = max(1, intval($_GET['page_num'] ?? 1));
        
        // Récupération des données
        $archives = $this->model->getArchives($user_id, $search, $date, $type);
        $statistics = $this->model->getStatistics($user_id);
        $current_year = date('Y');
        
        return [
            'archives' => $archives,
            'statistics' => $statistics,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => 1,
                'total_items' => count($archives)
            ],
            'filters' => [
                'search' => $search,
                'date' => $date,
                'type' => $type
            ],
            'current_year' => $current_year
        ];
    }
} 