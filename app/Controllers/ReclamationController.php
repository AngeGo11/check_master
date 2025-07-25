<?php

use App\Models\Reclamation;
require_once __DIR__ . '/../Models/Reclamation.php';

class ReclamationController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Reclamation($db);
    }

    /**
     * Afficher la page des réclamations
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
    public function viewReclamations() {
        return $this->model->getAllReclamationsWithPagination();
    }
} 
