<?php
namespace App\Models;

use PDO;
use PDOException;

class Documents {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterDocument($nom_document, $type_document, $date_ajout, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO documents (nom_document, type_document, date_ajout) VALUES (:nom_document, :type_document, :date_ajout)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nom_document', $nom_document);
            $stmt->bindParam(':type_document', $type_document);
            $stmt->bindParam(':date_ajout', $date_ajout);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout document: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllDocuments() {
        $query = "SELECT * FROM documents";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getDocumentById($id) {
        $query = "SELECT * FROM documents WHERE id_document = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierDocument($id, $nom_document, $type_document, $date_ajout, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE documents SET nom_document = :nom_document, type_document = :type_document, date_ajout = :date_ajout WHERE id_document = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nom_document', $nom_document);
            $stmt->bindParam(':type_document', $type_document);
            $stmt->bindParam(':date_ajout', $date_ajout);
            $stmt->bindParam(':id', $id);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification document: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerDocument($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM documents WHERE id_document = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression document: " . $e->getMessage());
            return false;
        }
    }
} 