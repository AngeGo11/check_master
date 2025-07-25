<?php
namespace App\Models;

use PDO;
use PDOException;

class CompteRendu {
    private $db;

    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterCompteRendu($titre, $contenu, $date_creation, $auteur_id, $fichier_path) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO compte_rendu (titre, contenu, date_creation, auteur_id, fichier_path) 
                      VALUES (:titre, :contenu, :date_creation, :auteur_id, :fichier_path)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':contenu', $contenu);
            $stmt->bindParam(':date_creation', $date_creation);
            $stmt->bindParam(':auteur_id', $auteur_id);
            $stmt->bindParam(':fichier_path', $fichier_path);
            $stmt->execute();
            $id_cr = $this->db->lastInsertId();
            $this->db->commit();
            return $id_cr;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout compte rendu: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllCompteRendus() {
        $query = "SELECT * FROM compte_rendu ORDER BY date_creation DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getCompteRenduById($id) {
        $query = "SELECT * FROM compte_rendu WHERE id_compte_rendu = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierCompteRendu($id, $titre, $contenu, $date_creation, $auteur_id, $fichier_path) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE compte_rendu SET titre = :titre, contenu = :contenu, date_creation = :date_creation, auteur_id = :auteur_id, fichier_path = :fichier_path WHERE id_compte_rendu = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':contenu', $contenu);
            $stmt->bindParam(':date_creation', $date_creation);
            $stmt->bindParam(':auteur_id', $auteur_id);
            $stmt->bindParam(':fichier_path', $fichier_path);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification compte rendu: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerCompteRendu($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM compte_rendu WHERE id_compte_rendu = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression compte rendu: " . $e->getMessage());
            return false;
        }
    }
} 