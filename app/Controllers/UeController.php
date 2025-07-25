<?php




require_once __DIR__ . '/../Models/UE.php';

use App\Models\UE;
class UeController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Ue($db);
    }

    public function index() {
        return $this->model->getAllUes();
    }

    public function show($id) {
        return $this->model->getUeById($id);
    }

    public function store($data) {
        return $this->model->ajouterUE(
            $data['code'],
            $data['libelle'],
            $data['id_semestre'],
            $data['credits']
        );
    }

    public function update($id, $data) {
        return $this->model->modifierUE(
            $id,
            $data['code'],
            $data['libelle'],
            $data['id_semestre'],
            $data['credits']
        );
    }

    public function delete($id) {
        return $this->model->supprimerUe($id);
    }
} 
