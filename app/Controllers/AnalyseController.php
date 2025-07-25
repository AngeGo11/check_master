<?php

require_once __DIR__ . '/../Models/AnalyseModel.php';
require_once __DIR__ . '/../../config/config.php';

class AnalyseController {
    private $model;

    public function __construct() {
        $this->model = new AnalyseModel(DataBase::getConnection());
    }

    /**
     * Affiche la page d'analyses
     */
    public function index() {
        // Vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Vérification des permissions (commission)
        $allowedGroups = [5, 6, 7, 8, 9]; // Enseignant, Responsable niveau, Responsable filière, Administrateur, Commission
        $userGroups = $_SESSION['user_groups'] ?? [];
        
        $hasAccess = false;
        foreach ($userGroups as $group) {
            if (in_array($group, $allowedGroups)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $_SESSION['error_message'] = "Accès non autorisé à l'interface d'analyses";
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date_filter = $_GET['date_filter'] ?? '';
        $page = max(1, intval($_GET['page_num'] ?? 1));

        // Récupération des données
        $stats = $this->model->getRapportStats();
        $rapports_data = $this->model->getRapportsEnAttente($search, $date_filter, $page, 10);

        
    }

    /**
     * Supprime des rapports (AJAX)
     */
    public function supprimerRapports() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['rapport_ids']) || !is_array($data['rapport_ids'])) {
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            return;
        }

        try {
            $this->model->supprimerRapports($data['rapport_ids']);
            echo json_encode(['success' => true, 'message' => 'Rapports supprimés avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les données d'analyse pour affichage dans la vue
     */
    public function viewAnalyses() {
        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date_filter = $_GET['date_filter'] ?? '';
        $page = max(1, intval($_GET['page_num'] ?? 1));
        
        // Récupération des données
        $rapports_data = $this->model->getRapportsEnAttente($search, $date_filter, $page, 10);
        $stats = $this->model->getRapportStats();
        
        return [
            'rapports' => $rapports_data['rapports'],
            'total_rapports' => $stats['total_rapports'],
            'rapports_evalues' => $stats['rapports_evalues'],
            'rapports_attente' => $stats['rapports_attente'],
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $rapports_data['pages'],
                'total_items' => $rapports_data['total']
            ],
            'filters' => [
                'search' => $search,
                'date_filter' => $date_filter
            ]
        ];
    }
} 