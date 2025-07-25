<?php

namespace App\Models;

use PDO;
use PDOException;

class Rapport
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Vérifier si un étudiant a déjà un rapport soumis
     */
    public function checkExistingReport($studentId)
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM rapport_etudiant WHERE num_etd = ?");
            $stmt->execute([$studentId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ($result && $result['total'] > 0);
        } catch (PDOException $e) {
            error_log("Erreur vérification rapport existant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer le statut du rapport d'un étudiant
     */
    public function getReportStatus($userId)
    {
        try {
            $stmt = $this->db->prepare("SELECT re.statut_rapport
                                        FROM rapport_etudiant re
                                        JOIN etudiants e ON re.num_etd = e.num_etd
                                        JOIN utilisateur u ON u.login_utilisateur = e.email_etd
                                        WHERE u.id_utilisateur = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['statut_rapport'] : "Non soumis";
        } catch (PDOException $e) {
            error_log("Erreur récupération statut rapport: " . $e->getMessage());
            return "Non soumis";
        }
    }

    /**
     * Récupérer les commentaires d'un rapport
     */
    public function getReportComments($studentId)
    {
        try {
            $comments = [];

            // Commentaires d'approbation (personnel administratif)
            $approbation_query = $this->db->prepare("
                SELECT 'approbation' as type, 
                       a.com_appr as texte_commentaire, 
                       a.date_approbation as date_commentaire,
                       CONCAT(p.prenoms_personnel_adm, ' ', p.nom_personnel_adm) as nom_encadrant
                FROM approuver a
                JOIN personnel_administratif p ON a.id_personnel_adm = p.id_personnel_adm
                JOIN rapport_etudiant r ON a.id_rapport_etd = r.id_rapport_etd
                WHERE r.num_etd = ? AND a.com_appr IS NOT NULL
            ");
            $approbation_query->execute([$studentId]);
            $approval_comments = $approbation_query->fetchAll(PDO::FETCH_ASSOC);
            $comments = array_merge($comments, $approval_comments);

            // Commentaires de validation (enseignants)
            $validation_query = $this->db->prepare("
                SELECT 'validation' as type, 
                       v.com_validation as texte_commentaire, 
                       v.date_validation as date_commentaire,
                       CONCAT(e.prenoms_ens, ' ', e.nom_ens) as nom_encadrant
                FROM valider v
                JOIN enseignants e ON v.id_ens = e.id_ens
                JOIN rapport_etudiant r ON v.id_rapport_etd = r.id_rapport_etd
                WHERE r.num_etd = ? AND v.com_validation IS NOT NULL
            ");
            $validation_query->execute([$studentId]);
            $validation_comments = $validation_query->fetchAll(PDO::FETCH_ASSOC);
            $comments = array_merge($comments, $validation_comments);

            // Trier les commentaires par date (du plus récent au plus ancien)
            usort($comments, function ($a, $b) {
                $date_a = $a['date_commentaire'] ? strtotime($a['date_commentaire']) : 0;
                $date_b = $b['date_commentaire'] ? strtotime($b['date_commentaire']) : 0;
                return $date_b - $date_a;
            });

            return $comments;
        } catch (PDOException $e) {
            error_log("Erreur récupération commentaires: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Créer un nouveau rapport
     */
    public function createReport($studentId, $themeMemoire, $filePath)
    {
        try {
            $this->db->beginTransaction();

            $sql = "INSERT INTO rapport_etudiant (num_etd, theme_memoire, fichier_rapport, date_rapport, statut_rapport) 
                    VALUES (?, ?, ?, CURDATE(), 'En attente d\'approbation')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId, $themeMemoire, $filePath]);

            $rapportId = $this->db->lastInsertId();

            // Mettre à jour ou insérer dans la table deposer
            $depotSql = "INSERT INTO deposer (num_etd, id_rapport_etd, date_depot)
                         VALUES (:num, :idRapport, CURDATE())
                         ON DUPLICATE KEY UPDATE date_depot = CURDATE()";
            $depotStmt = $this->db->prepare($depotSql);
            $depotStmt->execute([
                ':num' => $studentId,
                ':idRapport' => $rapportId
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Rapport enregistré',
                'file' => basename($filePath),
                'file_path' => $filePath
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur création rapport: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du rapport',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer les données de l'étudiant pour le rapport
     */
    public function getStudentDataForReport($userId)
    {
        try {
            $sql = "SELECT * FROM etudiants e 
                    JOIN utilisateur u ON u.login_utilisateur = e.email_etd
                    WHERE id_utilisateur = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération données étudiant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier l'éligibilité de l'étudiant
     */
    public function checkEligibility($userId)
    {
        try {
            $sql = "SELECT e.statut_eligibilite 
                    FROM etudiants e 
                    JOIN utilisateur u ON u.login_utilisateur = e.email_etd
                    WHERE u.id_utilisateur = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['statut_eligibilite'] : 'Non défini';
        } catch (PDOException $e) {
            error_log("Erreur vérification éligibilité: " . $e->getMessage());
            return 'Non défini';
        }
    }

    public function ajouterRapport($titre, $description, $fichier_path, $etudiant_id, $date_soumission) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO rapport (titre, description, fichier_path, etudiant_id, date_soumission) VALUES (:titre, :description, :fichier_path, :etudiant_id, :date_soumission)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':fichier_path', $fichier_path);
            $stmt->bindParam(':etudiant_id', $etudiant_id);
            $stmt->bindParam(':date_soumission', $date_soumission);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout rapport: " . $e->getMessage());
            return false;
        }
    }

    public function getAllRapports() {
        $query = "SELECT * FROM rapport ORDER BY date_soumission DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getRapportById($id) {
        $query = "SELECT * FROM rapport_etudiant WHERE num_etd = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getRapportsByEtudiant($etudiant_id) {
        $query = "SELECT * FROM rapport_etudiant WHERE num_etd = :etudiant_id ORDER BY date_soumission DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':etudiant_id', $etudiant_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function modifierRapport($id, $titre, $description, $fichier_path) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE rapport SET titre = :titre, description = :description, fichier_path = :fichier_path WHERE id_rapport = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':fichier_path', $fichier_path);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification rapport: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerRapport($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM rapport_etudiant WHERE id_rapport_etd = :id";
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

    public function approuverRapport($id) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE rapport_etudiant SET statut_statut = 'approuvé' WHERE id_rapport = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur approbation rapport: " . $e->getMessage());
            return false;
        }
    }

    public function rejeterRapport($id, $commentaire) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE rapport_etudiant SET statut_statut = 'rejeté', commentaire = :commentaire WHERE id_rapport = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':commentaire', $commentaire);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur rejet rapport: " . $e->getMessage());
            return false;
        }
    }
} 