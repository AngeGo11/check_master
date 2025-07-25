<?php
namespace App\Models;

use PDO;
use PDOException;

class Promotion {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterPromotion($lib_promotion, $annee_promotion) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO promotion (lib_promotion, annee_promotion) VALUES (:lib_promotion, :annee_promotion)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_promotion', $lib_promotion);
            $stmt->bindParam(':annee_promotion', $annee_promotion);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout promotion: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllPromotions() {
        $query = "SELECT * FROM promotion";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPromotionById($id) {
        $query = "SELECT * FROM promotion WHERE id_promotion = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierPromotion($id, $lib_promotion, $annee_promotion) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE promotion SET lib_promotion = :lib_promotion, annee_promotion = :annee_promotion WHERE id_promotion = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_promotion', $lib_promotion);
            $stmt->bindParam(':annee_promotion', $annee_promotion);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification promotion: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerPromotion($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM promotion WHERE id_promotion = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression promotion: " . $e->getMessage());
            return false;
        }
    }
} 