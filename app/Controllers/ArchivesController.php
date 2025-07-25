<?php

require_once __DIR__ . '/../Models/Archives.php';
require_once __DIR__ . '/../../config/config.php';

class ArchivesController {
    private $model;

    public function __construct() {
        $this->model = new \App\Models\Archives(DataBase::getConnection());
    }

    public function index() {
        // require __DIR__ . '/../Views/archives.php';
    }

    public function show($id) {
        return $this->model->getArchiveById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterArchive(
            $data['nom_archive'], $data['type_archive'], $data['date_archive'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierArchive($id, $data);
    }

    public function delete($id) {
        return $this->model->supprimerArchive($id);
    }
} 
