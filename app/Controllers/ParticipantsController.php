<?php





class ParticipantsController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Participants($db);
    }

    public function index() {
        return $this->model->getAllParticipants();
    }

    public function show($id) {
        return $this->model->getParticipantById($id);
    }

    public function store($data) {
        // À adapter selon les champs
        return $this->model->ajouterParticipant(
            $data['nom'], $data['autres'] ?? []
        );
    }

    public function update($id, $data) {
        return $this->model->modifierParticipant($id, $data);
    }

    public function delete($id) {
        return $this->model->supprimerParticipant($id);
    }
} 
