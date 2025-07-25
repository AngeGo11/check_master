<?php





class GradeController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Grade($db);
    }

    public function index() {
        return $this->model->getAllGrades();
    }

    public function show($id) {
        return $this->model->getGradeById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterGrade(
            $data['lib_grade'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierGrade($id, $data);
    }

    public function delete($id) {
        return $this->model->supprimerGrade($id);
    }
} 
