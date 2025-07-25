<?php





class DocumentsController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Documents($db);
    }

    public function index() {
        return $this->model->getAllDocuments();
    }

    public function show($id) {
        return $this->model->getDocumentById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterDocument(
            $data['nom_document'], $data['type_document'], $data['date_ajout'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierDocument($id, $data);
    }

    public function delete($id) {
        return $this->model->supprimerDocument($id);
    }
} 
