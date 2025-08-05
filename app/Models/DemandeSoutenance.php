<?php

namespace App\Models;

use PDO;
use PDOException;

class DemandeSoutenance
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }

    // CREATE
    public function ajouterDemandeSoutenance($etudiant_id, $sujet, $date_demande)
    {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO demande_soutenance (num_etd, sujet, date_demande) VALUES (:etudiant_id, :sujet, :date_demande)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':etudiant_id', $etudiant_id);
            $stmt->bindParam(':sujet', $sujet);
            $stmt->bindParam(':date_demande', $date_demande);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout demande soutenance: " . $e->getMessage());
            return false;
        }
    }

    

    // READ
    public function getAllDemandesSoutenance()
    {
        $query = "SELECT * FROM demande_soutenance";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getDemandeSoutenanceById($id)
    {
        $query = "SELECT * FROM demande_soutenance WHERE id_demande = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierDemandeSoutenance($id, $date_traitement, $date_demande)
    {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE demande_soutenance SET date_traitement = :date_traitement, date_demande = :date_demande WHERE id_demande = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':date_traitement', $date_traitement);
            $stmt->bindParam(':date_demande', $date_demande);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification demande soutenance: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerDemandeSoutenance($id)
    {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM demande_soutenance WHERE id_demande = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression demande soutenance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recherche, filtre et pagination des demandes de soutenance
     */
    public function searchDemandesSoutenance($search = '', $statut = '', $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR e.num_carte_etd LIKE ? OR e.email_etd LIKE ? OR d.sujet LIKE ? )";
            $search_param = "%$search%";
            $params = array_merge($params, array_fill(0, 5, $search_param));
        }
        if ($statut !== '') {
            $where[] = "d.statut_demande = ?";
            $params[] = $statut;
        }
        $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Sous-requête pour obtenir la demande la plus récente par étudiant
        $sql = "SELECT d.*, e.nom_etd, e.prenom_etd, e.num_carte_etd, e.email_etd, e.statut_eligibilite
                FROM demande_soutenance d
                JOIN etudiants e ON d.num_etd = e.num_etd
                INNER JOIN (
                    SELECT num_etd, MAX(date_demande) as max_date
                    FROM demande_soutenance
                    GROUP BY num_etd
                ) latest ON d.num_etd = latest.num_etd AND d.date_demande = latest.max_date
                $where_sql
                ORDER BY d.date_demande DESC
                LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le total des demandes de soutenance avec filtres
     */
    public function countDemandesSoutenance($search = '', $statut = '')
    {
        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR e.num_carte_etd LIKE ? OR e.email_etd LIKE ? OR d.sujet LIKE ? )";
            $search_param = "%$search%";
            $params = array_merge($params, array_fill(0, 5, $search_param));
        }
        if ($statut !== '') {
            $where[] = "d.statut_demande = ?";
            $params[] = $statut;
        }
        $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
        
        // Compter les demandes uniques par étudiant (la plus récente)
        $sql = "SELECT COUNT(*) as total
                FROM demande_soutenance d
                JOIN etudiants e ON d.num_etd = e.num_etd
                INNER JOIN (
                    SELECT num_etd, MAX(date_demande) as max_date
                    FROM demande_soutenance
                    GROUP BY num_etd
                ) latest ON d.num_etd = latest.num_etd AND d.date_demande = latest.max_date
                $where_sql";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }


    public function getCountDemandeWaiting()
    {
        // Requête pour les rapports en attente
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM demande_soutenance WHERE statut_demande = 'En attente'");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    public function getCountDemandeTreated()
    {
        // Requête pour les rapports évalués
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM demande_soutenance WHERE statut_demande = 'Traitée'");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
}
