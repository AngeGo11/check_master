<?php

require_once __DIR__ . '/../Models/FraisInscription.php';

use App\Models\FraisInscription;

class FraisInscriptionController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new FraisInscription($db);
    }

    public function index() {
        return $this->model->getAllFraisInscription();
    }

    public function show($id) {
        return $this->model->getFraisInscriptionById($id);
    }

    public function store($data) {
        return $this->model->ajouterFraisInscription(
            $data['montant'], 
            $data['id_ac'], 
            $data['id_niv_etd']
        );
    }

    public function update($id, $data) {
        return $this->model->modifierFraisInscription(
            $id, 
            $data['montant'], 
            $data['id_ac'], 
            $data['id_niv_etd']
        );
    }

    public function delete($id) {
        return $this->model->supprimerFraisInscription($id);
    }
} 
