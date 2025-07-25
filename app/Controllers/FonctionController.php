<?php





class FonctionController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Fonction($db);
    }

    public function index() {
        return $this->model->getAllFonctions();
    }

    public function show($id) {
        return $this->model->getFonctionById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterFonction(
            $data['lib_fonction'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierFonction($id, $data);
    }

    public function delete($id) {
        return $this->model->supprimerFonction($id);
    }
} 
