<?php

use App\Models\AnneeAcademique;
require_once __DIR__ . '/../Models/AnneeAcademique.php';


class AnneeAcademiqueController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new AnneeAcademique($db);
    }

    public function index() {
        return $this->model->getAllAnneesAcademiques();
    }

    public function show($id) {
        return $this->model->getAnneeAcademiqueById($id);
    }

    public function store($data) {
        return $this->model->ajouterAnneeAcademique(
            $data['date_debut'],
            $data['date_fin'],
            $data['statut_annee']
        );
    }

    public function update($id, $data) {
        return $this->model->modifierAnneeAcademique(
            $id,
            $data['date_debut'],
            $data['date_fin'],
            $data['statut_annee']
        );
    }

    public function delete($id) {
        return $this->model->supprimerAnneeAcademique($id);
    }

    public function getCurrentYear() {  
        return $this->model->getCurrentAcademicYear();
    }
} 
