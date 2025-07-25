<?php


require_once __DIR__ . '/../Models/Promotion.php';

use App\Models\Promotion;


class PromotionController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Promotion($db);
    }

    public function index() {
        return $this->model->getAllPromotions();
    }

    public function show($id) {
        return $this->model->getPromotionById($id);
    }

    public function store($data) {
        return $this->model->ajouterPromotion(
            $data['lib_promotion'],
            $data['annee_promotion']
        );
    }

    public function update($id, $data) {
        return $this->model->modifierPromotion(
            $id,
            $data['lib_promotion'],
            $data['annee_promotion']
        );
    }

    public function delete($id) {
        return $this->model->supprimerPromotion($id);
    }
} 
