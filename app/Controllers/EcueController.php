<?php





class EcueController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Ecue($db);
    }

    public function index() {
        return $this->model->getAllEcues();
    }

    public function show($id) {
        return $this->model->getEcueById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterEcue(
            $data['code_ecue'], $data['lib_ecue'], $data['id_ue'], $data['volume_horaire'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierEcue($id, $data);
    }

    public function delete($id) {
        return $this->model->supprimerEcue($id);
    }
} 
