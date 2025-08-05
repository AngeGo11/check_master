<?php

use App\Models\Rapport;
require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../Models/Rapport.php';

class RapportController {
    private $model;

    public function __construct($pdo) {
        $this->model = new Rapport($pdo);
    }

    /**
     * Afficher la page des rapports
     */
    public function index($userId) {
        $studentData = $this->model->getStudentDataForReport($userId);
        
        if (!$studentData) {
            return ['error' => 'Aucune donnée trouvée pour cet utilisateur'];
        }

        $studentId = $studentData['num_etd'];
        
        return [
            'studentData' => $studentData,
            'hasExistingReport' => $this->model->checkExistingReport($studentId),
            'reportStatus' => $this->model->getReportStatus($userId),
            'eligibilityStatus' => $this->model->checkEligibility($userId),
            'comments' => $this->model->getReportComments($studentId)
        ];
    }

    /**
     * Créer un nouveau rapport
     */
    public function createReport($studentId, $themeMemoire, $filePath) {
        return $this->model->createReport($studentId, $themeMemoire, $filePath);
    }

    /**
     * Vérifier si un rapport existe déjà
     */
    public function checkExistingReport($studentId) {
        return $this->model->checkExistingReport($studentId);
    }

    /**
     * Récupérer le statut du rapport
     */
    public function getReportStatus($userId) {
        return $this->model->getReportStatus($userId);
    }

    /**
     * Récupérer les commentaires
     */
    public function getComments($studentId) {
        return $this->model->getReportComments($studentId);
    }

    /**
     * Vérifier l'éligibilité
     */
    public function checkEligibility($userId) {
        return $this->model->checkEligibility($userId);
    }
} 