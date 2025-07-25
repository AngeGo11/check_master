<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../Models/Enseignant.php';

use App\Models\Enseignant;

class EnseignantController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Enseignant($db);
    }

    public function index() {
        return $this->model->all();
    }

    public function show($id) {
        return $this->model->find($id);
    }

    public function store($data) {
        return $this->model->create($data);
    }

    public function update($id, $data) {
        return $this->model->update($id, $data);
    }

    public function delete($id) {
        return $this->model->delete($id);
    }

    public function getStatistics() {
        return $this->model->getStatistics();
    }

    public function getEnseignantsWithPagination($page = 1, $limit = 10) {
        return $this->model->getEnseignantsWithPagination($page, $limit);
    }

    public function searchEnseignants($search, $filters = []) {
        return $this->model->searchEnseignants($search, $filters);
    }

    public function getGrades() {
        return $this->model->getGrades();
    }

    public function getFonctions() {
        return $this->model->getFonctions();
    }

    public function getSpecialites() {
        return $this->model->getSpecialites();
    }

    public function commissionMembers() {
        return $this->model->getCommissionMembers();
    }

    public function getEnseignantByLogin($login){
        return $this->model->getIdEnseignantsByUserLogin($login);
    }
} 
