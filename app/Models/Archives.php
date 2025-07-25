<?php
namespace App\Models;

use PDO;
use PDOException;

class Archives {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterArchive($nom_archive, $type_archive, $date_archive, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO archives (nom_archive, type_archive, date_archive) VALUES (:nom_archive, :type_archive, :date_archive)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nom_archive', $nom_archive);
            $stmt->bindParam(':type_archive', $type_archive);
            $stmt->bindParam(':date_archive', $date_archive);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout archive: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllArchives() {
        $query = "SELECT * FROM archives";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getArchiveById($id) {
        $query = "SELECT * FROM archives WHERE id_archives = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierArchive($id, $nom_archive, $type_archive, $date_archive, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE archives SET nom_archive = :nom_archive, type_archive = :type_archive, date_archive = :date_archive WHERE id_archives = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nom_archive', $nom_archive);
            $stmt->bindParam(':type_archive', $type_archive);
            $stmt->bindParam(':date_archive', $date_archive);
            $stmt->bindParam(':id', $id);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification archive: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerArchive($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM archives WHERE id_archives = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression archive: " . $e->getMessage());
            return false;
        }
    }
} 