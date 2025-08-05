<?php

require_once __DIR__ . '/../Models/ConsultationModel.php';

class ConsultationController {
    private $model;

    public function __construct() {
        $this->model = new ConsultationModel();
    }

    /**
     * Corrige le chemin du fichier pour pointer vers le bon répertoire
     */
    private function getCorrectFilePath($dbPath) {
        // Le chemin dans la BDD commence déjà par storage/uploads/, on l'utilise directement
        return dirname(__DIR__, 2) . '/' . $dbPath;
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

        // Gestion des actions AJAX
        $action = $_GET['action'] ?? '';
        if ($action === 'getEvaluationData') {
            // Nettoyer tout buffer de sortie pour éviter les caractères parasites
            while (ob_get_level()) {
                ob_end_clean();
            }
            $this->getEvaluationData();
            exit(); // Arrêter l'exécution ici pour éviter de charger la vue
        } elseif ($action === 'getCompteRenduDetails') {
            while (ob_get_level()) {
                ob_end_clean();
            }
            $this->getCompteRenduDetails();
            exit();
        } elseif ($action === 'deleteCompteRenduGroup') {
            while (ob_get_level()) {
                ob_end_clean();
            }
            $this->deleteCompteRenduGroup();
            exit();
        } elseif ($action === 'deleteCompteRendu') {
            while (ob_get_level()) {
                ob_end_clean();
            }
            $this->deleteCompteRendu();
            exit();
        } elseif ($action === 'download_cr') {
            $this->downloadCompteRendu();
            exit();
        } elseif ($action === 'view_cr') {
            $this->viewCompteRendu();
            exit();
        } elseif ($action === 'download_rapport') {
            $this->downloadRapport();
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

    /**
     * Récupère les membres de la commission et leurs évaluations pour un rapport
     */
    public function getCommissionMembers($rapport_id) {
        return $this->model->getCommissionMembers($rapport_id);
    }

    /**
     * Récupère les statistiques d'évaluation pour un rapport
     */
    public function getEvaluationStats($rapport_id) {
        return $this->model->getEvaluationStats($rapport_id);
    }

    /**
     * Récupère les détails des évaluations pour un rapport
     */
    public function getEvaluationDetails($rapport_id) {
        return $this->model->getEvaluationDetails($rapport_id);
    }

    /**
     * Récupère les données d'évaluation pour un rapport (AJAX)
     */
    public function getEvaluationData() {
        // S'assurer qu'il n'y a pas de contenu envoyé avant
        if (headers_sent()) {
            error_log("ConsultationController::getEvaluationData - Headers already sent");
        }
        
        // Définir le type de contenu JSON
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $rapport_id = $_GET['rapport_id'] ?? null;
        
        if (!$rapport_id) {
            echo json_encode(['success' => false, 'error' => 'ID du rapport manquant']);
            return;
        }

        try {
            // Log pour débogage
            error_log("ConsultationController::getEvaluationData - Rapport ID: " . $rapport_id);
            
            $commission_members = $this->model->getCommissionMembers($rapport_id);
            $evaluation_stats = $this->model->getEvaluationStats($rapport_id);
            
            // Log pour débogage
            error_log("ConsultationController::getEvaluationData - Commission members count: " . count($commission_members));
            error_log("ConsultationController::getEvaluationData - Evaluation stats: " . json_encode($evaluation_stats));
            
            $response = [
                'success' => true,
                'commission_members' => $commission_members,
                'evaluation_stats' => $evaluation_stats
            ];
            
            echo json_encode($response);
        } catch (Exception $e) {
            error_log("ConsultationController::getEvaluationData - Exception: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des données: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les détails d'un compte rendu (AJAX)
     */
    public function getCompteRenduDetails() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $titre = $_GET['titre'] ?? null;
        
        if (!$titre) {
            echo json_encode(['success' => false, 'error' => 'Titre du compte rendu manquant']);
            return;
        }

        try {
            $details = $this->model->getCompteRenduDetailsByTitre($titre);
            echo json_encode(['success' => true, 'details' => $details]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération des détails: ' . $e->getMessage()]);
        }
    }

    /**
     * Supprime un groupe de comptes rendus (AJAX)
     */
    public function deleteCompteRenduGroup() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['titre'])) {
            echo json_encode(['success' => false, 'error' => 'Titre du compte rendu manquant']);
            return;
        }

        try {
            $this->model->deleteCompteRenduGroup($data['titre']);
            echo json_encode(['success' => true, 'message' => 'Groupe de comptes rendus supprimé avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }

    /**
     * Supprime un compte rendu individuel (AJAX)
     */
    public function deleteCompteRendu() {
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['id'])) {
            echo json_encode(['success' => false, 'error' => 'ID du compte rendu manquant']);
            return;
        }

        try {
            $this->model->deleteCompteRendu($data['id']);
            echo json_encode(['success' => true, 'message' => 'Compte rendu supprimé avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }

    public function getHistory() {
        return $this->model->getEmailHistory();
    }

    /**
     * Télécharge un compte rendu
     */
    public function downloadCompteRendu() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID du compte rendu manquant']);
            return;
        }

        try {
            $compteRendu = $this->model->getCompteRenduById($id);
            
            if (!$compteRendu) {
                echo json_encode(['success' => false, 'error' => 'Compte rendu non trouvé']);
                return;
            }

            $filePath = $this->getCorrectFilePath($compteRendu['fichier_cr']);
            
            if (!file_exists($filePath)) {
                echo json_encode(['success' => false, 'error' => 'Fichier non trouvé: ' . $filePath]);
                return;
            }

            // Déterminer le type MIME
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'doc' => 'application/msword',
                'html' => 'text/html'
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

            // Nettoyer tout buffer de sortie
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Vérifier qu'aucun en-tête n'a été envoyé
            if (headers_sent($file, $line)) {
                error_log("Headers already sent in $file:$line");
                exit();
            }
            
            // En-têtes pour le téléchargement
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');

            // Lire et envoyer le fichier
            readfile($filePath);
            exit();

        } catch (Exception $e) {
            error_log("ConsultationController::downloadCompteRendu - Exception: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement: ' . $e->getMessage()]);
        }
    }

    /**
     * Affiche un compte rendu dans le navigateur
     */
    public function viewCompteRendu() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID du compte rendu manquant']);
            return;
        }

        try {
            $compteRendu = $this->model->getCompteRenduById($id);
            
            if (!$compteRendu) {
                echo json_encode(['success' => false, 'error' => 'Compte rendu non trouvé']);
                return;
            }

            $filePath = $this->getCorrectFilePath($compteRendu['fichier_cr']);
            
            if (!file_exists($filePath)) {
                echo json_encode(['success' => false, 'error' => 'Fichier non trouvé: ' . $filePath]);
                return;
            }

            // Déterminer le type MIME
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'doc' => 'application/msword',
                'html' => 'text/html'
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

            // Nettoyer tout buffer de sortie
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Vérifier qu'aucun en-tête n'a été envoyé
            if (headers_sent($file, $line)) {
                error_log("Headers already sent in $file:$line");
                exit();
            }
            
            // En-têtes pour l'affichage
            header('Content-Type: ' . $mimeType);
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');

            // Lire et envoyer le fichier
            readfile($filePath);
            exit();

        } catch (Exception $e) {
            error_log("ConsultationController::viewCompteRendu - Exception: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'affichage: ' . $e->getMessage()]);
        }
    }

    /**
     * Télécharge un rapport étudiant
     */
    public function downloadRapport() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID du rapport manquant']);
            return;
        }

        try {
            // Récupérer les informations du rapport via le modèle
            $rapport = $this->model->getRapportById($id);
            
            if (!$rapport) {
                echo json_encode(['success' => false, 'error' => 'Rapport non trouvé']);
                return;
            }

            $filePath = $this->getCorrectFilePath($rapport['fichier_rapport']);
            
            if (!file_exists($filePath)) {
                echo json_encode(['success' => false, 'error' => 'Fichier non trouvé: ' . $filePath]);
                return;
            }

            // Déterminer le type MIME
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'doc' => 'application/msword',
                'html' => 'text/html'
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

            // Générer un nom de fichier personnalisé
            $studentName = $rapport['nom_etd'] . '_' . $rapport['prenom_etd'];
            $reportName = $rapport['nom_rapport'] ?: 'rapport';
            $customFileName = $studentName . '_' . $reportName . '.' . $extension;
            
            // Nettoyer le nom de fichier
            $customFileName = preg_replace('/[^a-zA-Z0-9_\-\s\.]/', '', $customFileName);
            $customFileName = str_replace(' ', '_', $customFileName);

            // En-têtes pour le téléchargement
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $customFileName . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');

            // Lire et envoyer le fichier
            readfile($filePath);
            exit();

        } catch (Exception $e) {
            error_log("ConsultationController::downloadRapport - Exception: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement: ' . $e->getMessage()]);
        }
    }
} 