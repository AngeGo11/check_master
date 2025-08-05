<?php

use App\Models\Reclamation;
require_once __DIR__ . '/../Models/Reclamation.php';

class ReclamationController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Reclamation($db);
    }

    /**
     * Afficher la page des réclamations pour les étudiants
     */
    public function index($userId) {
        $studentData = $this->model->getStudentData($userId);
        
        if (!$studentData) {
            return ['error' => 'Aucune donnée trouvée pour cet utilisateur'];
        }

        $studentId = $studentData['num_etd'];
        
        return [
            'studentData' => $studentData,
            'niveauEtudiant' => $this->model->getStudentLevel($studentId),
            'reclamations' => $this->model->getStudentReclamations($studentId),
            'reclamationsEnCours' => $this->model->getReclamationsEnCours($studentId)
        ];
    }

    /**
     * Créer une nouvelle réclamation
     */
    public function createReclamation($studentId, $data) {
        return $this->model->createReclamation($studentId, $data);
    }

    /**
     * Récupérer les réclamations en cours
     */
    public function getReclamationsEnCours($studentId) {
        return $this->model->getReclamationsEnCours($studentId);
    }

    /**
     * Filtrer les réclamations par statut
     */
    public function filterReclamationsByStatus($studentId, $status) {
        return $this->model->filterReclamationsByStatus($studentId, $status);
    }

    /**
     * Récupérer le niveau d'étude
     */
    public function getStudentLevel($studentId) {
        return $this->model->getStudentLevel($studentId);
    }

    /**
     * Afficher toutes les réclamations (pour la vue admin)
     */
    public function viewReclamations($userGroup = '') {
        return $this->model->getAllReclamationsWithPagination($userGroup);
    }

    /**
     * Récupérer les statistiques des réclamations
     */
    public function getStatistics($userGroup = '') {
        return $this->model->getStatistics($userGroup);
    }

    /**
     * Récupérer les réclamations avec filtres
     */
    public function getReclamationsWithFilters($search = '', $date_filter = '', $status_filter = '', $userGroup = '') {
        return $this->model->getReclamationsWithFilters($search, $date_filter, $status_filter, $userGroup);
    }

    /**
     * Récupérer les détails d'une réclamation
     */
    public function getReclamationDetails($id) {
        return $this->model->getReclamationDetails($id);
    }

    /**
     * Traiter une réclamation
     */
    public function traiterReclamation($reclamationId, $commentaire, $userGroup) {
        return $this->model->traiterReclamation($reclamationId, $commentaire, $userGroup);
    }

    /**
     * Transférer une réclamation
     */
    public function transfererReclamation($reclamationId, $commentaireTransfert, $userGroup) {
        return $this->model->transfererReclamation($reclamationId, $commentaireTransfert, $userGroup);
    }

    /**
     * Supprimer une ou plusieurs réclamations
     */
    public function supprimerReclamations($reclamationIds) {
        return $this->model->supprimerReclamations($reclamationIds);
    }

    /**
     * Gérer les actions AJAX
     */
    public function handleAjaxRequest() {
        $action = $_POST['action'] ?? $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_details':
                return $this->handleGetDetails();
            case 'traiter_reclamation':
                return $this->traiterReclamation($_POST['reclamation_id'], $_POST['retour_traitement'], $_POST['user_group']);
            case 'transferer_reclamation':
                return $this->transfererReclamation($_POST['reclamation_id'], $_POST['commentaire_transfert'], $_POST['user_group']);
            case 'supprimer':
                return $this->handleSupprimer();
            default:
                return ['error' => 'Action non reconnue'];
        }
    }

    /**
     * Gérer la récupération des détails d'une réclamation
     */
    private function handleGetDetails() {
        try {
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID de réclamation manquant');
            }

            $details = $this->model->getReclamationDetails($id);
            
            if (!$details) {
                throw new Exception('Réclamation non trouvée');
            }

            return ['success' => true, 'data' => $details];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    

    /**
     * Gérer la suppression d'une réclamation
     */
    private function handleSupprimer() {
        try {
            $reclamation_id = $_POST['reclamation_id'] ?? null;

            if (!$reclamation_id) {
                throw new Exception('ID de réclamation manquant');
            }

            $result = $this->model->supprimerReclamations([$reclamation_id]);

            if ($result) {
                return ['success' => true, 'message' => 'La réclamation a été supprimée avec succès.'];
            } else {
                return ['success' => false, 'error' => 'Aucune réclamation n\'a été supprimée.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Une erreur est survenue lors de la suppression.'];
        }
    }
} 
