<?php
namespace App\Models;

use PDO;
use PDOException;

class Grade {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterGrade($lib_grade, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO grade (lib_grade) VALUES (:lib_grade)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_grade', $lib_grade);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout grade: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllGrades() {
        $query = "SELECT * FROM grade";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getGradeById($id) {
        $query = "SELECT * FROM grade WHERE id_grade = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierGrade($id, $lib_grade, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE grade SET lib_grade = :lib_grade WHERE id_grade = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_grade', $lib_grade);
            $stmt->bindParam(':id', $id);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification grade: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerGrade($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM grade WHERE id_grade = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression grade: " . $e->getMessage());
            return false;
        }
    }
} 