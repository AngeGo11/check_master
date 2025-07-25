<?php



require_once __DIR__ . '/../Models/Utilisateur.php';

use App\Models\Utilisateur;

class UtilisateurController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Utilisateur($db);
    }

    public function index() {
        return $this->model->getAllUtilisateurs();
    }

    public function show($id) {
        return $this->model->getUtilisateurById($id);
    }

    public function store($data) {
        return $this->model->ajouterUtilisateur(
            $data['login_utilisateur'],
            $data['mdp_utilisateur'],
            $data['statut_utilisateur'],
            $data['id_niveau_acces']
        );
    }

    public function update($id, $data) {
        return $this->model->updateUtilisateur(
            $data['login_utilisateur'],
            $data['mdp_utilisateur'],
            $data['statut_utilisateur'],
            $data['id_niveau_acces'],
            $id
        );
    }

    public function delete($id) {
        return $this->model->supprimerUtilisateur($id);
    }

    public function getInfosUser($login){
        return $this->model->getUserInfos($login);
    }
} 
