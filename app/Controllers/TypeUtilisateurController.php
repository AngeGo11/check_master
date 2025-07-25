<?php





class TypeUtilisateurController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new TypeUtilisateur($db);
    }

    public function index() {
        return $this->model->getAllTypesUtilisateurs();
    }

    public function show($id) {
        return $this->model->getTypeUtilisateurById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterTypeUtilisateur(
            $data['lib_tu'], $data['description_tu'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierTypeUtilisateur(
            $id,
            $data['lib_tu'],
            $data['description_tu']
        );
    }

    public function delete($id) {
        return $this->model->supprimerTypeUtilisateur($id);
    }
} 
