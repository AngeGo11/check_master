<?php
namespace App\Models;

use PDO;
use PDOException;

class PaiementReglement {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterPaiementReglement($montant, $date_paiement, $etudiant_id) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO paiement_reglement (montant, date_paiement, etudiant_id) VALUES (:montant, :date_paiement, :etudiant_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':montant', $montant);
            $stmt->bindParam(':date_paiement', $date_paiement);
            $stmt->bindParam(':etudiant_id', $etudiant_id);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout paiement: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllPaiementsReglement() {
        $query = "SELECT * FROM paiement_reglement";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPaiementReglementById($id) {
        $query = "SELECT * FROM paiement_reglement WHERE id_paiement_reglement = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierPaiementReglement($id, $montant, $date_paiement, $etudiant_id) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE paiement_reglement SET montant = :montant, date_paiement = :date_paiement, etudiant_id = :etudiant_id WHERE id_paiement_reglement = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':montant', $montant);
            $stmt->bindParam(':date_paiement', $date_paiement);
            $stmt->bindParam(':etudiant_id', $etudiant_id);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification paiement: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerPaiementReglement($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM paiement_reglement WHERE id_paiement_reglement = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression paiement: " . $e->getMessage());
            return false;
        }
    }
} 