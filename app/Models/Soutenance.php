<?php

namespace App\Models;

use PDO;
use PDOException;

class Soutenance
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer les informations de l'étudiant pour les soutenances
     */
    public function getStudentData($userId)
    {
        try {
            $query = "SELECT e.*, p.lib_promotion, n.lib_niv_etd, u.login_utilisateur, u.statut_utilisateur
                      FROM etudiants e 
                      LEFT JOIN promotion p ON e.id_promotion = p.id_promotion 
                      LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd
                      LEFT JOIN utilisateur u ON e.email_etd = u.login_utilisateur
                      WHERE u.id_utilisateur = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération données étudiant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un stage est déclaré
     */
    public function checkDeclaredInternship($studentId)
    {
        try {
            $query = "SELECT * FROM faire_stage WHERE num_etd = :student_id ORDER BY date_debut DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération déclaration stage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si une demande de soutenance existe
     */
    public function checkSoutenanceRequest($studentId)
    {
        try {
            $query = "SELECT * FROM demande_soutenance WHERE num_etd = :student_id ORDER BY date_demande DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération demande soutenance: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un rapport existe
     */
    public function checkReportExists($studentId)
    {
        try {
            $query = "SELECT * FROM rapport_etudiant WHERE num_etd = :student_id ORDER BY date_rapport DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapport: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer le compte rendu
     */
    public function getCompteRendu($studentId)
    {
        try {
            // Récupérer le compte rendu via la relation avec les enseignants et validations
            $query = "SELECT cr.id_cr, cr.nom_cr, cr.fichier_cr, cr.date_cr,
                             r.id_rapport_etd, r.nom_rapport, r.date_rapport
                      FROM compte_rendu cr
                      JOIN rendre rn ON rn.id_cr = cr.id_cr
                      JOIN enseignants ens ON ens.id_ens = rn.id_ens
                      JOIN valider v ON v.id_ens = ens.id_ens
                      JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
                      JOIN etudiants e ON e.num_etd = r.num_etd
                      WHERE e.num_etd = :student_id
                      ORDER BY cr.date_cr DESC 
                      LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération compte rendu: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer le compte rendu pour un rapport spécifique
     */
    public function getCompteRenduByRapport($studentId, $rapportId)
    {
        try {
            // Vérifier que le rapport appartient à l'étudiant
            $query = "SELECT cr.id_cr, cr.nom_cr, cr.fichier_cr, cr.date_cr,
                             r.id_rapport_etd, r.nom_rapport, r.date_rapport
                      FROM compte_rendu cr
                      JOIN rendre rn ON rn.id_cr = cr.id_cr
                      JOIN enseignants ens ON ens.id_ens = rn.id_ens
                      JOIN valider v ON v.id_ens = ens.id_ens
                      JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
                      JOIN etudiants e ON e.num_etd = r.num_etd
                      WHERE e.num_etd = :student_id AND r.id_rapport_etd = :rapport_id
                      ORDER BY cr.date_cr DESC 
                      LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->bindParam(':rapport_id', $rapportId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération compte rendu par rapport: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Déclarer un stage
     */
    public function declareInternship($studentId, $data)
    {
        try {
            $this->db->beginTransaction();

            // Insérer l'entreprise
            $stmt = $this->db->prepare("INSERT INTO entreprise (lib_entr, adresse, ville, pays, telephone, email) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $data['nom_entreprise'],
                $data['adresse_entreprise'],
                $data['ville_entreprise'],
                $data['pays_entreprise'],
                $data['telephone_entreprise'],
                $data['email_entreprise']
            ]);
            $id_entreprise = $this->db->lastInsertId();

            // Insérer le stage
            $stmt = $this->db->prepare("INSERT INTO faire_stage (num_etd, id_entr, intitule_stage, description_stage, type_stage, date_debut, date_fin, nom_tuteur, poste_tuteur, telephone_tuteur, email_tuteur)
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $studentId,
                $id_entreprise,
                $data['intitule_stage'],
                $data['description_stage'],
                $data['type_stage'],
                $data['date_debut_stage'],
                $data['date_fin_stage'],
                $data['nom_tuteur'],
                $data['poste_tuteur'],
                $data['telephone_tuteur'],
                $data['email_tuteur']
            ]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur déclaration stage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Créer une demande de soutenance
     */
    public function createSoutenanceRequest($studentId)
    {
        try {
            $this->db->beginTransaction();

            // Insérer la demande de soutenance
            $stmt = $this->db->prepare("
                INSERT INTO demande_soutenance (num_etd, date_demande, statut_demande) 
                VALUES (?, CURDATE(), 'En attente')
            ");

            if (!$stmt->execute([$studentId])) {
                throw new PDOException("Erreur lors de l'insertion de la demande de soutenance");
            }

            // Mettre à jour le statut d'éligibilité
            $stmt = $this->db->prepare("
                UPDATE etudiants 
                SET statut_eligibilite = 'En attente de confirmation' 
                WHERE num_etd = ?
            ");

            if (!$stmt->execute([$studentId])) {
                throw new PDOException("Erreur lors de la mise à jour du statut d'éligibilité");
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur création demande soutenance: " . $e->getMessage());
            return false;
        }
    }

    public function ajouterSoutenance($date_soutenance, $heure_soutenance, $lieu, $etudiant_id, $rapport_id)
    {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO demande_soutenance (date_soutenance, heure_soutenance, lieu, etudiant_id, rapport_id) VALUES (:date_soutenance, :heure_soutenance, :lieu, :etudiant_id, :rapport_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':date_soutenance', $date_soutenance);
            $stmt->bindParam(':heure_soutenance', $heure_soutenance);
            $stmt->bindParam(':lieu', $lieu);
            $stmt->bindParam(':etudiant_id', $etudiant_id);
            $stmt->bindParam(':rapport_id', $rapport_id);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout soutenance: " . $e->getMessage());
            return false;
        }
    }

    public function getAllSoutenances()
    {
        $query = "SELECT * FROM demande_soutenance ORDER BY date_soutenance DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getSoutenanceById($id)
    {
        $query = "SELECT * FROM demande_soutenance WHERE id_demande = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getSoutenancesByEtudiant($etudiant_id)
    {
        $query = "SELECT * FROM demande_soutenance WHERE etudiant_id = :etudiant_id ORDER BY date_soutenance DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':etudiant_id', $etudiant_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function modifierSoutenance($id, $date_soutenance, $heure_soutenance, $lieu)
    {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE demande_soutenance SET date_soutenance = :date_soutenance, heure_soutenance = :heure_soutenance, lieu = :lieu WHERE id_demande = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':date_soutenance', $date_soutenance);
            $stmt->bindParam(':heure_soutenance', $heure_soutenance);
            $stmt->bindParam(':lieu', $lieu);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification soutenance: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerSoutenance($id)
    {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM demande_soutenance WHERE id_demande = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression soutenance: " . $e->getMessage());
            return false;
        }
    }
}
