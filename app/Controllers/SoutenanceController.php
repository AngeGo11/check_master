<?php

use App\Models\Soutenance;
require_once __DIR__ . '/../Models/Soutenance.php';
require_once __DIR__ . '/../config/config.php';


class SoutenanceController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Soutenance($db);
    }

    /**
     * Afficher la page des soutenances
     */
    public function index($userId) {
        $studentData = $this->model->getStudentData($userId);
        
        if (!$studentData) {
            return ['error' => 'Aucune donnée trouvée pour cet utilisateur'];
        }

        $studentId = $studentData['num_etd'];
        
        return [
            'studentData' => $studentData,
            'stageDeclare' => $this->model->checkDeclaredInternship($studentId),
            'demandeSoutenance' => $this->model->checkSoutenanceRequest($studentId),
            'rapport' => $this->model->checkReportExists($studentId),
            'compteRendu' => $this->model->getCompteRendu($studentId)
        ];
    }

    /**
     * Déclarer un stage
     */
    public function declareInternship($studentId, $data) {
        return $this->model->declareInternship($studentId, $data);
    }

    /**
     * Créer une demande de soutenance
     */
    public function createSoutenanceRequest($studentId) {
        return $this->model->createSoutenanceRequest($studentId);
    }

    /**
     * Vérifier si un stage est déclaré
     */
    public function checkDeclaredInternship($studentId) {
        return $this->model->checkDeclaredInternship($studentId);
    }

    /**
     * Vérifier si une demande de soutenance existe
     */
    public function checkSoutenanceRequest($studentId) {
        return $this->model->checkSoutenanceRequest($studentId);
    }

    /**
     * Récupérer le compte rendu
     */
    public function getCompteRendu($studentId) {
        return $this->model->getCompteRendu($studentId);
    }
} 