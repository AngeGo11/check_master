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
    public function getStudentData($userId)
    {
        try {
            $sql = "SELECT * FROM etudiants e 
                    JOIN utilisateur u ON u.login_utilisateur = e.email_etd
                    WHERE id_utilisateur = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération données étudiant réclamation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les notes de l'étudiant
     */
    public function getStudentGrades($studentId)
    {
        try {
            $query = "SELECT 
                s.lib_semestre,
                u.id_ue,
                u.lib_ue,
                u.credit_ue,
                'ue' as type,
                ev.note,
                ev.credit
            FROM evaluer_ue ev
            JOIN ue u ON ev.id_ue = u.id_ue
            JOIN semestre s ON ev.id_semestre = s.id_semestre
            WHERE ev.num_etd = ?

            UNION ALL

            SELECT 
                s.lib_semestre,
                u.id_ue,
                u.lib_ue,
                u.credit_ue,
                'ecue' as type,
                ev.note,
                ev.credit
            FROM evaluer_ecue ev
            JOIN ecue e ON ev.id_ecue = e.id_ecue
            JOIN ue u ON e.id_ue = u.id_ue
            JOIN semestre s ON ev.id_semestre = s.id_semestre
            WHERE ev.num_etd = ?
            ORDER BY lib_semestre, lib_ue";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$studentId, $studentId]);
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
            $query = "SELECT fs.*, ent.lib_entr
                FROM faire_stage fs
                JOIN entreprise ent ON fs.id_entr = ent.id_entr
                WHERE fs.num_etd = :student_id 
                ORDER BY fs.date_debut DESC";
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
            $query = "SELECT * FROM rapport_etudiant WHERE num_etd = :student_id ORDER BY date_rapport DESC";
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
