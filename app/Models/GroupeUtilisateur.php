<?php
namespace App\Models;

use PDO;
use PDOException;

class GroupeUtilisateur {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterGroupeUtilisateur($lib_gu, $description_gu, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO groupe_utilisateur (lib_gu, description_gu) VALUES (:lib_gu, :description_gu)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_gu', $lib_gu);
            $stmt->bindParam(':description_gu', $description_gu);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout groupe utilisateur: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllGroupesUtilisateurs() {
        $query = "SELECT * FROM groupe_utilisateur";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getGroupeUtilisateurById($id) {
        $query = "SELECT * FROM groupe_utilisateur WHERE id_gu = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierGroupeUtilisateur($id, $lib_gu, $description_gu, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE groupe_utilisateur SET lib_gu = :lib_gu, description_gu = :description_gu WHERE id_gu = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_gu', $lib_gu);
            $stmt->bindParam(':description_gu', $description_gu);
            $stmt->bindParam(':id', $id);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification groupe utilisateur: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerGroupeUtilisateur($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM groupe_utilisateur WHERE id_gu = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression groupe utilisateur: " . $e->getMessage());
            return false;
        }
    }
} 