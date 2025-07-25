<?php
namespace App\Models;

use PDO;
use PDOException;

class TypeUtilisateur {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterTypeUtilisateur($lib_tu, $description_tu, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO type_utilisateur (lib_tu, description_tu) VALUES (:lib_tu, :description_tu)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_tu', $lib_tu);
            $stmt->bindParam(':description_tu', $description_tu);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout type utilisateur: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllTypesUtilisateurs() {
        $query = "SELECT * FROM type_utilisateur";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getTypeUtilisateurById($id) {
        $query = "SELECT * FROM type_utilisateur WHERE id_tu = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierTypeUtilisateur($id, $lib_tu, $description_tu) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE type_utilisateur SET lib_tu = :lib_tu, description_tu = :description_tu WHERE id_tu = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':lib_tu', $lib_tu);
            $stmt->bindParam(':description_tu', $description_tu);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification type utilisateur: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerTypeUtilisateur($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM type_utilisateur WHERE id_tu = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression type utilisateur: " . $e->getMessage());
            return false;
        }
    }
} 