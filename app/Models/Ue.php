<?php
namespace App\Models;

use PDO;
use PDOException;

class Ue {
    private $db;

    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterUE($code, $libelle, $id_semestre, $credits) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO ue (code, libelle, id_semestre, credits) VALUES (:code, :libelle, :id_semestre, :credits)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':libelle', $libelle);
            $stmt->bindParam(':id_semestre', $id_semestre);
            $stmt->bindParam(':credits', $credits);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout UE: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllUEs() {
        $query = "SELECT * FROM ue";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getUEById($id) {
        $query = "SELECT * FROM ue WHERE id_ue = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierUE($id, $code, $libelle, $id_semestre, $credits) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE ue SET code = :code, libelle = :libelle, id_semestre = :id_semestre, credits = :credits WHERE id_ue = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':libelle', $libelle);
            $stmt->bindParam(':id_semestre', $id_semestre);
            $stmt->bindParam(':credits', $credits);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification UE: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerUE($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM ue WHERE id_ue = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression UE: " . $e->getMessage());
            return false;
        }
    }
} 