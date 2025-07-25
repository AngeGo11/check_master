<?php

namespace App\Models;

use PDO;
use PDOException;

class Profil
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer le profil complet d'un étudiant
     */
    public function getStudentProfile($userId)
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
            error_log("Erreur récupération profil étudiant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les données du profil étudiant
     */
    public function getStudentData($studentId)
    {
        try {
            $query = "SELECT e.*, p.lib_promotion, n.lib_niv_etd 
                      FROM etudiants e 
                      LEFT JOIN promotion p ON e.id_promotion = p.id_promotion 
                      LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd 
                      WHERE e.num_etd = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération données étudiant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les notes de l'étudiant
     */
    public function getStudentGrades($studentId)
    {
        try {
            $query = " SELECT * FROM (
            SELECT 
                ev.id_ue as id_ecue,
                ev.note,
                ev.credit,
                ue.credit_ue as credit_ecue,
                ue.lib_ue as lib_ecue,
                ue.id_ue,
                ue.lib_ue
            FROM evaluer_ue ev
            INNER JOIN ue ON ev.id_ue = ue.id_ue
            WHERE ev.num_etd = (
                SELECT num_etd FROM etudiants WHERE num_carte_etd = ?
            )
            AND ev.id_semestre = ?

            UNION ALL

            SELECT 
                ev.id_ecue,
                ev.note,
                ev.credit,
                ec.credit_ecue,
                ec.lib_ecue,
                ue.id_ue,
                ue.lib_ue
            FROM evaluer_ecue ev
            INNER JOIN ecue ec ON ev.id_ecue = ec.id_ecue
            INNER JOIN ue ON ec.id_ue = ue.id_ue
            WHERE ev.num_etd = (
                SELECT num_etd FROM etudiants WHERE num_carte_etd = ?
            )
            AND ev.id_semestre = :student_id
        ) AS combined_notes
        ORDER BY lib_ue, lib_ecue";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération notes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les stages de l'étudiant
     */
    public function getStudentInternships($studentId)
    {
        try {
            $query = "SELECT s.*, e.lib_entr 
                      FROM stage s 
                      LEFT JOIN entreprise e ON s.entreprise_id = e.id_entr 
                      WHERE s.etudiant_id = :student_id 
                      ORDER BY s.date_debut DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération stages: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les rapports de l'étudiant
     */
    public function getStudentReports($studentId)
    {
        try {
            $query = "SELECT * FROM rapport WHERE etudiant_id = :student_id ORDER BY date_soumission DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération rapports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mettre à jour le profil étudiant
     */
    public function updateStudentProfile($studentId, $data)
    {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE etudiants SET 
                        nom_etd = :nom_etd,
                        prenom_etd = :prenom_etd,
                        email_etd = :email_etd,
                        telephone_etd = :telephone_etd,
                        adresse_etd = :adresse_etd
                      WHERE num_etd = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nom_etd', $data['nom_etd']);
            $stmt->bindParam(':prenom_etd', $data['prenom_etd']);
            $stmt->bindParam(':email_etd', $data['email_etd']);
            $stmt->bindParam(':telephone_etd', $data['telephone_etd']);
            $stmt->bindParam(':adresse_etd', $data['adresse_etd']);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur mise à jour profil: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour la photo de profil
     */
    public function updateProfilePhoto($studentId, $filename)
    {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE etudiants SET photo_etd = :filename WHERE num_etd = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':filename', $filename);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur mise à jour photo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer la photo de profil
     */
    public function deleteProfilePhoto($studentId)
    {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE etudiants SET photo_etd = 'default_profile.jpg' WHERE num_etd = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression photo: " . $e->getMessage());
            return false;
        }
    }

    public function getStudentLevel($studentId)
    {
        try {
            $query = "SELECT n.lib_niv_etd 
                      FROM etudiants e 
                      LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd 
                      WHERE e.num_etd = :student_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':student_id', $studentId);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? $result['lib_niv_etd'] : null;
        } catch (PDOException $e) {
            error_log("Erreur récupération niveau: " . $e->getMessage());
            return null;
        }
    }
}
