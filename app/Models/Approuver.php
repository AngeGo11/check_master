<?php
namespace App\Models;

use PDO;
use PDOException;

class Approuver {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function ajouterApprouver($id_personnel_adm, $id_rapport_etd, $date_approbation) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO approuver (id_personnel_adm, id_rapport_etd, date_approbation) VALUES (:id_personnel_adm, :id_rapport_etd, :date_approbation)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_personnel_adm', $id_personnel_adm);
            $stmt->bindParam(':id_rapport_etd', $id_rapport_etd);
            $stmt->bindParam(':date_approbation', $date_approbation);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout approbation: " . $e->getMessage());
            return false;
        }
    }

    public function getAllApprouver() {
        $query = "SELECT * FROM approuver";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getApprouver($id_personnel_adm, $id_rapport_etd) {
        $query = "SELECT * FROM approuver WHERE id_personnel_adm = :id_personnel_adm AND id_rapport_etd = :id_rapport_etd";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_personnel_adm', $id_personnel_adm);
        $stmt->bindParam(':id_rapport_etd', $id_rapport_etd);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function modifierApprouver($id_personnel_adm, $id_rapport_etd, $date_approbation) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE approuver SET date_approbation = :date_approbation WHERE id_personnel_adm = :id_personnel_adm AND id_rapport_etd = :id_rapport_etd";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':date_approbation', $date_approbation);
            $stmt->bindParam(':id_personnel_adm', $id_personnel_adm);
            $stmt->bindParam(':id_rapport_etd', $id_rapport_etd);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification approbation: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerApprouver($id_personnel_adm, $id_rapport_etd) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM approuver WHERE id_personnel_adm = :id_personnel_adm AND id_rapport_etd = :id_rapport_etd";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_personnel_adm', $id_personnel_adm);
            $stmt->bindParam(':id_rapport_etd', $id_rapport_etd);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression approbation: " . $e->getMessage());
            return false;
        }
    }
} 