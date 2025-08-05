<?php

use App\Models\Profil;
require_once __DIR__ . '/../Models/Profil.php';

class ProfilController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Profil($db);
    }

    /**
     * Afficher la page de profil
     */
    public function index($userId) {
        $studentData = $this->model->getStudentProfile($userId);
        
        if (!$studentData) {
            return ['error' => 'Aucune donnée trouvée pour cet utilisateur'];
        }

        $studentId = $studentData['num_etd'];
        
        return [
            'studentData' => $studentData,
            'grades' => $this->model->getStudentGrades($studentId),
            'internships' => $this->model->getStudentInternships($studentId),
            'reports' => $this->model->getStudentReports($studentId)
        ];
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile($numEtd, $data) {
        return $this->model->updateStudentProfile($numEtd, $data);
    }

    /**
     * Mettre à jour la photo de profil
     */
    public function updatePhoto($studentId, $filename) {
        return $this->model->updateProfilePhoto($studentId, $filename);
    }

    /**
     * Supprimer la photo de profil
     */
    public function deletePhoto($studentId) {
        return $this->model->deleteProfilePhoto($studentId);
    }

    /**
     * Organiser les notes par semestre
     */
    public function organizeGradesBySemester($studentId) {
        $notes = $this->model->getStudentGrades($studentId);
        
        // Organiser les notes par semestre, puis par UE
        $notesParSemestre = [];
        foreach ($notes as $note) {
            $semestre = $note['lib_semestre'] ?? 'Semestre inconnu';
            $id_ue = $note['id_ue'];

            if (!isset($notesParSemestre[$semestre])) {
                $notesParSemestre[$semestre] = [];
            }

            if (!isset($notesParSemestre[$semestre][$id_ue])) {
                $notesParSemestre[$semestre][$id_ue] = [
                    'lib_ue' => $note['lib_ue'],
                    'credit_ue' => $note['credit_ue'], // Le crédit total de l'UE
                    'notes' => []
                ];
            }

            // On ajoute la note (qu'elle vienne d'une UE ou d'un ECUE)
            $notesParSemestre[$semestre][$id_ue]['notes'][] = [
                'note' => $note['note'],
                'credit' => $note['credit'] // Le crédit de l'évaluation (UE ou ECUE)
            ];
        }

        return $notesParSemestre;
    }
} 