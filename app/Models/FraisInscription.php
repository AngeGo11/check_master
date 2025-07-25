<?php
namespace App\Models;

use PDO;
use PDOException;

class FraisInscription {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterFraisInscription($montant, $id_ac, $id_niv_etd) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO frais_inscription (montant, id_ac, id_niv_etd) VALUES (:montant, :id_ac, :id_niv_etd)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':montant', $montant);
            $stmt->bindParam(':id_ac', $id_ac);
            $stmt->bindParam(':id_niv_etd', $id_niv_etd);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout frais inscription: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllFraisInscription() {
        $query = "SELECT * FROM frais_inscription";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getFraisInscriptionById($id) {
        $query = "SELECT * FROM frais_inscription WHERE id_frais_inscription = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierFraisInscription($id, $montant, $id_ac, $id_niv_etd) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE frais_inscription SET montant = :montant, id_ac = :id_ac, id_niv_etd = :id_niv_etd WHERE id_frais_inscription = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':montant', $montant);
            $stmt->bindParam(':id_ac', $id_ac);
            $stmt->bindParam(':id_niv_etd', $id_niv_etd);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification frais inscription: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerFraisInscription($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM frais_inscription WHERE id_frais_inscription = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression frais inscription: " . $e->getMessage());
            return false;
        }
    }
} 