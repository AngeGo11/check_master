<?php

require_once __DIR__ . '/../Models/ArchivageModel.php';

class ArchivageController {
    private $model;

    public function __construct() {
        $this->model = new ArchivageModel();
    }

    /**
     * Affiche la page d'archivage
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
        
        

        $user_id = $_SESSION['user_id'];

        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date_soumission = $_GET['date_soumission'] ?? '';
        $date_decision = $_GET['date_decision'] ?? '';
        $type_doc = $_GET['type_doc'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));

        // Traitement des actions
        if (isset($_GET['action']) && $_GET['action'] === 'archive' && isset($_GET['id'], $_GET['type'])) {
            $this->archiverDocument($_GET['id'], urldecode($_GET['type']), $user_id);
        }

        // Récupération des données
        $stats = $this->model->getStatistics($user_id);
        $documents_data = $this->model->getDocumentsNonArchives($user_id, $search, $date_soumission, $date_decision, $type_doc, $page, 10);

        // Inclusion de la vue
        include __DIR__ . '/../Views/archivage_documents.php';
    }

    /**
     * Archive un document individuel
     */
    private function archiverDocument($id, $type, $user_id) {
        try {
            $this->model->archiverDocument($id, $type, $user_id);
            $_SESSION['success_message'] = "Le document a été archivé avec succès.";
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
    }

    /**
     * Archive plusieurs documents (AJAX)
     */
    public function archiverDocuments() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['doc_ids']) || !is_array($data['doc_ids'])) {
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            return;
        }

        try {
            $this->model->archiverDocuments($data['doc_ids'], $_SESSION['user_id']);
            echo json_encode(['success' => true, 'message' => 'Documents archivés avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'archivage: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les données d'archivage pour affichage dans la vue
     */
    public function viewArchivage() {
        $user_id = $_SESSION['user_id'] ?? 0;
        
        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date_soumission = $_GET['date_soumission'] ?? '';
        $date_decision = $_GET['date_decision'] ?? '';
        $type_doc = $_GET['type_doc'] ?? '';
        $page = max(1, intval($_GET['page_num'] ?? 1));
        
        // Récupération des données
        $documents_data = $this->model->getDocumentsNonArchives($user_id, $search, $date_soumission, $date_decision, $type_doc, $page, 10);
        $statistics = $this->model->getStatistics($user_id);
        
        return [
            'documents' => $documents_data['documents'],
            'statistics' => $statistics,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $documents_data['pages'],
                'total_items' => $documents_data['total']
            ],
            'filters' => [
                'search' => $search,
                'date_soumission' => $date_soumission,
                'date_decision' => $date_decision,
                'type_doc' => $type_doc
            ]
        ];
    }
} 