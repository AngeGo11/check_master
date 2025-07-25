<?php
namespace App\Models;

use PDO;
use PDOException;

class Fonction {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterFonction($lib_fonction, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO fonction (lib_fonction) VALUES (:lib_fonction)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_fonction', $lib_fonction);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout fonction: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllFonctions() {
        $query = "SELECT * FROM fonction";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getFonctionById($id) {
        $query = "SELECT * FROM fonction WHERE id_fonction = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierFonction($id, $lib_fonction, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE fonction SET lib_fonction = :lib_fonction WHERE id_fonction = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_fonction', $lib_fonction);
            $stmt->bindParam(':id', $id);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification fonction: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerFonction($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM fonction WHERE id_fonction = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression fonction: " . $e->getMessage());
            return false;
        }
    }
} 