<?php


require_once __DIR__ . '/../Models/Entreprise.php';

use App\Models\Entreprise;


class EntrepriseController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Entreprise($db);
    }

    public function index() {
        return $this->model->getAllEntreprises();
    }

    public function show($id) {
        return $this->model->getEntrepriseById($id);
    }

    public function store($data) {
        return $this->model->ajouterEntreprise(
            $data['lib_entr'],
            $data['adresse'],
            $data['ville'],
            $data['pays'],
            $data['telephone'],
            $data['email']
        );
    }

    public function update($id, $data) {
        return $this->model->modifierEntreprise(
            $id,
            $data['lib_entr'],
            $data['adresse'],
            $data['ville'],
            $data['pays'],
            $data['telephone'],
            $data['email']
        );
    }

    public function delete($id) {
        return $this->model->supprimerEntreprise($id);
    }
} 
