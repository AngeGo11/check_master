<?php
namespace App\Models;

use PDO;
use PDOException;

class ECUE {
    private $db;

    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterECUE($code, $libelle, $id_ue, $volume_horaire) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO ecue (code, libelle, id_ue, volume_horaire) VALUES (:code, :libelle, :id_ue, :volume_horaire)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':libelle', $libelle);
            $stmt->bindParam(':id_ue', $id_ue);
            $stmt->bindParam(':volume_horaire', $volume_horaire);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout ECUE: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllECUEs() {
        $query = "SELECT ecue.*, ue.libelle as lib_ue FROM ecue LEFT JOIN ue ON ecue.id_ue = ue.id_ue ORDER BY ecue.libelle";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getECUEById($id) {
        $query = "SELECT ecue.*, ue.libelle as lib_ue FROM ecue LEFT JOIN ue ON ecue.id_ue = ue.id_ue WHERE ecue.id_ecue = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierECUE($id, $code, $libelle, $id_ue, $volume_horaire) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE ecue SET code = :code, libelle = :libelle, id_ue = :id_ue, volume_horaire = :volume_horaire WHERE id_ecue = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':libelle', $libelle);
            $stmt->bindParam(':id_ue', $id_ue);
            $stmt->bindParam(':volume_horaire', $volume_horaire);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification ECUE: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerECUE($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM ecue WHERE id_ecue = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression ECUE: " . $e->getMessage());
            return false;
        }
    }
} 