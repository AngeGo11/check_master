<?php





class GroupeUtilisateurController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new GroupeUtilisateur($db);
    }

    public function index() {
        return $this->model->getAllGroupesUtilisateurs();
    }

    public function show($id) {
        return $this->model->getGroupeUtilisateurById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterGroupeUtilisateur(
            $data['lib_gu'], $data['description_gu'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierGroupeUtilisateur(
            $id,
            $data['lib_gu'],
            $data['description_gu']
        );
    }

    public function delete($id) {
        return $this->model->supprimerGroupeUtilisateur($id);
    }
} 
