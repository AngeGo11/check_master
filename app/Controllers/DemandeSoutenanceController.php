<?php

require_once __DIR__ . '/../Models/DemandeSoutenance.php';

use App\Models\DemandeSoutenance;

class DemandeSoutenanceController
{
    private $model;

    public function __construct(PDO $db)
    {
        $this->model = new DemandeSoutenance($db);
    }

    public function index()
    {
        return $this->model->getAllDemandesSoutenance();
    }

    public function show($id)
    {
        return $this->model->getDemandeSoutenanceById($id);
    }

    public function store($data)
    {
        // À adapter selon les champs
        return $this->model->ajouterDemandeSoutenance(
            $data['id_etd'],
            $data['sujet'],
            $data['date_demande'],
            $data['autres'] ?? []
        );
    }

    public function update($id, $data)
    {
        return $this->model->modifierDemandeSoutenance($id, $data['id_etd'], $data['sujet'], $data['date_demande'], $data['autres'] ?? []);
    }

    public function delete($id)
    {
        return $this->model->supprimerDemandeSoutenance($id);
    }

    /**
     * Recherche, filtre et pagination des demandes de soutenance
     */
    public function search($search = '', $statut = '', $page = 1, $limit = 10)
    {
        return $this->model->searchDemandesSoutenance($search, $statut, $page, $limit);
    }

    /**
     * Compte le total des demandes de soutenance avec filtres
     */
    public function count($search = '', $statut = '')
    {
        return $this->model->countDemandesSoutenance($search, $statut);
    }

    public function countDemandeWaiting()
    {
        return $this->model->getCountDemandeWaiting();
    }

    public function countDemandeTreated()
    {
        return $this->model->getCountDemandeTreated();
    }
}
