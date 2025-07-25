<?php





class CompteRenduController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new CompteRendu($db);
    }

    public function index() {
        return $this->model->getAllCompteRendus();
    }

    public function show($id) {
        return $this->model->getCompteRenduById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterCompteRendu(
            $data['titre'], $data['contenu'], $data['date'], $data['auteur'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierCompteRendu($id, $data);
    }

    public function delete($id) {
        return $this->model->supprimerCompteRendu($id);
    }
} 
