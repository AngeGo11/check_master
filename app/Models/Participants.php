<?php
namespace App\Models;

use PDO;
use PDOException;

class Participants {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterParticipant($reunion_id, $utilisateur_id, $statut) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO participants (reunion_id, id_utilisateur, statut) VALUES (:reunion_id, :utilisateur_id, :statut)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':reunion_id', $reunion_id);
            $stmt->bindParam(':utilisateur_id', $utilisateur_id);
            $stmt->bindParam(':statut', $statut);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout participant: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllParticipants() {
        $query = "SELECT * FROM participants";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getParticipantById($id) {
        $query = "SELECT * FROM participants WHERE id_participant = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierParticipant($id, $reunion_id, $utilisateur_id, $statut) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE participants SET reunion_id = :reunion_id, id_utilisateur = :utilisateur_id, statut = :statut WHERE id_participant = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':reunion_id', $reunion_id);
            $stmt->bindParam(':utilisateur_id', $utilisateur_id);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification participant: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerParticipant($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM participants WHERE id_participant = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression participant: " . $e->getMessage());
            return false;
        }
    }
} 