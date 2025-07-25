<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../Models/PersonnelAdministratif.php';
use App\Models\PersonnelAdministratif;


class PersonnelAdministratifController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new PersonnelAdministratif($db);
    }

    public function index() {
        return $this->model->getAllPersonnelAdministratif();
    }

    public function show($id) {
        return $this->model->getPersonnelAdministratifById($id);
    }

    public function store($data) {
        return $this->model->ajouterPersonnelAdministratif(
            $data['nom'], 
            $data['prenoms'], 
            $data['email'], 
            $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierPersonnelAdministratif(
            $id, 
            $data['nom'] ?? '', 
            $data['prenoms'] ?? '', 
            $data['email'] ?? '', 
            $data['autres'] ?? []
        );
    }

    public function delete($id) {
        return $this->model->supprimerPersonnelAdministratif($id);
    }

    public function getStatistics() {
        return $this->model->getStatistics();
    }

    public function getPersonnelWithPagination($page = 1, $limit = 10) {
        return $this->model->getPersonnelWithPagination($page, $limit);
    }

    public function searchPersonnel($search, $filters = []) {
        return $this->model->searchPersonnel($search, $filters);
    }

    public function getGroupes() {
        return $this->model->getGroupes();
    }
} 
