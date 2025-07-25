<?php
namespace App\Models;

use PDO;
use PDOException;

class Entreprise {
    private $db;

    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterEntreprise($lib_entr, $adresse, $ville, $pays, $telephone, $email) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO entreprise (lib_entr, adresse, ville, pays, telephone, email) VALUES (:lib_entr, :adresse, :ville, :pays, :telephone, :email)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_entr', $lib_entr);
            $stmt->bindParam(':adresse', $adresse);
            $stmt->bindParam(':ville', $ville);
            $stmt->bindParam(':pays', $pays);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout entreprise: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllEntreprises() {
        $query = "SELECT * FROM entreprise";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getEntrepriseById($id) {
        $query = "SELECT * FROM entreprise WHERE id_entr = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierEntreprise($id, $lib_entr, $adresse, $ville, $pays, $telephone, $email) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE entreprise SET lib_entr = :lib_entr, adresse = :adresse, ville = :ville, pays = :pays, telephone = :telephone, email = :email WHERE id_entr = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_entr', $lib_entr);
            $stmt->bindParam(':adresse', $adresse);
            $stmt->bindParam(':ville', $ville);
            $stmt->bindParam(':pays', $pays);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification entreprise: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerEntreprise($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM entreprise WHERE id_entr = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression entreprise: " . $e->getMessage());
            return false;
        }
    }
} 