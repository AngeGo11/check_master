<?php

require_once __DIR__ . '/../Models/PaiementReglement.php';

use App\Models\PaiementReglement;

class PaiementReglementController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new PaiementReglement($db);
    }

    public function index() {
        return $this->model->getAllPaiementsReglement();
    }

    public function show($id) {
        return $this->model->getPaiementReglementById($id);
    }

    public function store($data) {
        return $this->model->ajouterPaiementReglement(
            $data['montant'], 
            $data['date_paiement'], 
            $data['etudiant_id']
        );
    }

    public function update($id, $data) {
        return $this->model->modifierPaiementReglement(
            $id, 
            $data['montant'], 
            $data['date_paiement'], 
            $data['etudiant_id']
        );
    }

    public function delete($id) {
        return $this->model->supprimerPaiementReglement($id);
    }
} 
