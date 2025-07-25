<?php
namespace App\Models;

use PDO;
use PDOException;

class Specialite {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterSpecialite($lib_specialite, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO specialite (lib_specialite) VALUES (:lib_specialite)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_specialite', $lib_specialite);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout spécialité: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllSpecialites() {
        $query = "SELECT * FROM specialite";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getSpecialiteById($id) {
        $query = "SELECT * FROM specialite WHERE id_specialite = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierSpecialite($id, $lib_specialite, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE specialite SET lib_specialite = :lib_specialite WHERE id_specialite = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_specialite', $lib_specialite);
            $stmt->bindParam(':id', $id);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification spécialité: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerSpecialite($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM specialite WHERE id_specialite = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression spécialité: " . $e->getMessage());
            return false;
        }
    }
} 