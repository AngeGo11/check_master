<?php

require_once __DIR__ . '/../Models/ConsultationModel.php';

class ConsultationController {
    private $model;

    public function __construct() {
        $this->model = new ConsultationModel();
    }

    /**
     * Affiche la page de consultations
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
            $_SESSION['error_message'] = "Accès non autorisé à l'interface de consultations";
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date_filter = $_GET['date_filter'] ?? '';
        $status_filter = $_GET['status_filter'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        
        $search_cr = $_GET['search_cr'] ?? '';
        $date_filter_cr = $_GET['date_filter_cr'] ?? '';
        $page_cr = max(1, intval($_GET['page_cr'] ?? 1));

        // Récupération des données
        $stats = $this->model->getCompteRenduStats();
        $rapports = $this->model->getRapportsValides();
        $comptes_rendus_data = $this->model->getComptesRendus($search_cr, $date_filter_cr, $page_cr, 10);

        // Inclusion de la vue
        include __DIR__ . '/../Views/consultations.php';
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
     * Supprime des comptes rendus (AJAX)
     */
    public function supprimerComptesRendus() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['cr_ids']) || !is_array($data['cr_ids'])) {
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            return;
        }

        try {
            $this->model->supprimerComptesRendus($data['cr_ids']);
            echo json_encode(['success' => true, 'message' => 'Comptes rendus supprimés avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les données de consultation pour affichage dans la vue
     */
    public function viewConsultations() {
        // Récupération des paramètres
        $search_rapports = $_GET['search'] ?? '';
        $date_filter_rapports = $_GET['date_filter'] ?? '';
        $status_filter_rapports = $_GET['status_filter'] ?? '';
        $page_rapports = max(1, intval($_GET['page_num'] ?? 1));
        
        $search_cr = $_GET['search_cr'] ?? '';
        $date_filter_cr = $_GET['date_filter_cr'] ?? '';
        $status_filter_cr = $_GET['status_filter_cr'] ?? '';
        $page_cr = max(1, intval($_GET['page_num_cr'] ?? 1));
        
        // Récupération des données
        $rapports = $this->model->getRapportsValides();
        $comptes_rendus_data = $this->model->getComptesRendus($search_cr, $date_filter_cr, $page_cr, 10);
        $statistics = $this->model->getCompteRenduStats();
        
        return [
            'rapports' => $rapports,
            'comptes_rendus' => $comptes_rendus_data['comptes_rendus'],
            'statistics' => $statistics,
            'pagination_rapports' => [
                'current_page' => $page_rapports,
                'total_pages' => 1,
                'total_items' => count($rapports)
            ],
            'pagination_cr' => [
                'current_page' => $page_cr,
                'total_pages' => $comptes_rendus_data['pages'],
                'total_items' => $comptes_rendus_data['total']
            ],
            'filters_rapports' => [
                'search' => $search_rapports,
                'date_filter' => $date_filter_rapports,
                'status_filter' => $status_filter_rapports
            ],
            'filters_cr' => [
                'search' => $search_cr,
                'date_filter' => $date_filter_cr,
                'status_filter' => $status_filter_cr
            ]
        ];
    }

    public function getResponsableCompteRendu($userId){
        return $this->model->isResponsableCompteRendu($userId);
    }
} 