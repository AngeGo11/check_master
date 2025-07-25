<?php





class SpecialiteController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Specialite($db);
    }

    public function index() {
        return $this->model->getAllSpecialites();
    }

    public function show($id) {
        return $this->model->getSpecialiteById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterSpecialite(
            $data['lib_specialite'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierSpecialite($id, $data);
    }

    public function delete($id) {
        return $this->model->supprimerSpecialite($id);
    }
} 
