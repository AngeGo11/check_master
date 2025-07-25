<?php

namespace App\Models;

use PDO;
use DateTime;
use PDOException;

class AnneeAcademique
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }

    // CREATE
    public function ajouterAnneeAcademique($date_debut, $date_fin, $statut_annee)
    {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO annee_academique (date_debut, date_fin, statut_annee) VALUES (:date_debut, :date_fin, :statut_annee)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':date_debut', $date_debut);
            $stmt->bindParam(':date_fin', $date_fin);
            $stmt->bindParam(':statut_annee', $statut_annee);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout année académique: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllAnneesAcademiques()
    {
        $query = "SELECT * FROM annee_academique";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getAnneeAcademiqueById($id)
    {
        $query = "SELECT * FROM annee_academique WHERE id_ac = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierAnneeAcademique($id, $date_debut, $date_fin, $statut_annee)
    {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE annee_academique SET date_debut = :date_debut, date_fin = :date_fin, statut_annee = :statut_annee WHERE id_ac = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':date_debut', $date_debut);
            $stmt->bindParam(':date_fin', $date_fin);
            $stmt->bindParam(':statut_annee', $statut_annee);
            $stmt->bindParam(':id', $id);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification année académique: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerAnneeAcademique($id)
    {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM annee_academique WHERE id_ac = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression année académique: " . $e->getMessage());
            return false;
        }
    }


    // Fonction pour récupérer l'année académique en cours
    public function getCurrentAcademicYear()
    {
        try {
            $sql = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1";
            $stmt = $this->db->query($sql);
            $annee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($annee) {
                $dateDebut = new DateTime($annee['date_debut']);
                $dateFin = new DateTime($annee['date_fin']);
                return $dateDebut->format('Y') . '-' . $dateFin->format('Y');
            }
            return "À définir";
        } catch (PDOException $e) {
            return "Erreur";
        }
    }

    // Fonction pour rafraîchir l'année académique
    public function refreshCurrentYear()
    {
        $newYear = $this->getCurrentAcademicYear();
        $_SESSION['current_year'] = $newYear;
        return $newYear;
    }
}
