<?php
namespace App\Models;

use PDO;
use PDOException;

class RapportEtudiant {
    private $db;

    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterRapport($titre, $description, $etudiant_id, $date_soumission, $fichier_path) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO rapport (titre, description, etudiant_id, date_soumission, fichier_path) 
                      VALUES (:titre, :description, :etudiant_id, :date_soumission, :fichier_path)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':etudiant_id', $etudiant_id);
            $stmt->bindParam(':date_soumission', $date_soumission);
            $stmt->bindParam(':fichier_path', $fichier_path);
            $stmt->execute();
            $id_rapport = $this->db->lastInsertId();

            // Ex : affecter à un enseignant/jury (table de liaison)
            // $query = "INSERT INTO affecter (id_ens, id_rapport, id_jury) VALUES (?, ?, ?)";
            // $stmt = $this->db->prepare($query);
            // $stmt->execute([$id_ens, $id_rapport, $id_jury]);

            $this->db->commit();
            return $id_rapport;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout rapport: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllRapports() {
        $query = "SELECT r.*, e.nom_etd, e.prenom_etd
                  FROM rapport r
                  LEFT JOIN etudiants e ON r.etudiant_id = e.num_etd
                  ORDER BY r.date_soumission DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getRapportById($id) {
        $query = "SELECT r.*, e.nom_etd, e.prenom_etd
                  FROM rapport r
                  LEFT JOIN etudiants e ON r.etudiant_id = e.num_etd
                  WHERE r.id_rapport = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierRapport($id, $titre, $description, $etudiant_id, $date_soumission, $fichier_path) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE rapport SET titre = :titre, description = :description, etudiant_id = :etudiant_id, date_soumission = :date_soumission, fichier_path = :fichier_path WHERE id_rapport = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':etudiant_id', $etudiant_id);
            $stmt->bindParam(':date_soumission', $date_soumission);
            $stmt->bindParam(':fichier_path', $fichier_path);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification rapport: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerRapport($id) {
        try {
            $this->db->beginTransaction();
            // Supprimer d'abord dans les tables de liaison (affecter, approuver, etc.)
            $query = "DELETE FROM affecter WHERE id_rapport = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Puis supprimer le rapport
            $query = "DELETE FROM rapport WHERE id_rapport = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression rapport: " . $e->getMessage());
            return false;
        }
    }
}