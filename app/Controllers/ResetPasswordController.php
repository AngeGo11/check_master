<?php





class ResetPasswordController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new ResetPassword($db);
    }

    public function index() {
        return $this->model->getAllResetPasswords();
    }

    
    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterResetPassword(
            $data['email'], $data['token'], $data['expiration'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierResetPassword(
            $id,
            $data['email'],
            $data['token'],
            $data['expiration']
        );
    }

    public function delete($id) {
        return $this->model->supprimerResetPassword($id);
    }
} 
