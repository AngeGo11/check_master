<?php
namespace App\Models;

use PDO;
use PDOException;

class CompteRendu {
    private $db;

    public function __construct($db) { 
        $this->db = $db; 
    }

    // Méthode pour accéder à la base de données
    public function getDb() {
        return $this->db;
    }

    // CREATE - Adapté à la nouvelle structure avec table de liaison
    public function ajouterCompteRendu($titre, $date_cr, $auteur_id, $fichier_path, $rapport_ids) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier qu'il y a au moins un rapport
            if (empty($rapport_ids)) {
                throw new PDOException("Au moins un rapport doit être associé au compte rendu");
            }
            
            $compte_rendu_ids = [];
            
            // Créer un compte rendu séparé pour chaque rapport
            foreach ($rapport_ids as $rapport_id) {
                // Insérer un nouveau compte rendu pour ce rapport
                $query = "INSERT INTO compte_rendu (id_rapport_etd, nom_cr, date_cr, fichier_cr) 
                          VALUES (:id_rapport_etd, :titre, :date_cr, :fichier_path)";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':id_rapport_etd', $rapport_id);
                $stmt->bindParam(':titre', $titre);
                $stmt->bindParam(':date_cr', $date_cr);
                $stmt->bindParam(':fichier_path', $fichier_path);
                $stmt->execute();
                
                $compte_rendu_id = $this->db->lastInsertId();
                $compte_rendu_ids[] = $compte_rendu_id;
                
                // ✅ NOUVEAU : Insérer dans la table de liaison rapport_compte_rendu
                $liaison_query = "INSERT INTO rapport_compte_rendu (id_rapport_etd, id_cr) VALUES (:rapport_id, :id_cr)";
                $liaison_stmt = $this->db->prepare($liaison_query);
                $liaison_stmt->bindParam(':rapport_id', $rapport_id);
                $liaison_stmt->bindParam(':id_cr', $compte_rendu_id);
                $liaison_stmt->execute();
                
                // ✅ NOUVEAU : Insérer dans la table rendre (association avec l'enseignant)
                if ($auteur_id) {
                    $rendre_query = "INSERT INTO rendre (id_cr, id_ens, date_env) VALUES (:id_cr, :id_ens, :date_env)";
                    $rendre_stmt = $this->db->prepare($rendre_query);
                    $rendre_stmt->bindParam(':id_cr', $compte_rendu_id);
                    $rendre_stmt->bindParam(':id_ens', $auteur_id);
                    $rendre_stmt->bindParam(':date_env', $date_cr);
                    $rendre_stmt->execute();
                }
            }
            
            $this->db->commit();
            
            // Retourner le premier ID créé (pour compatibilité)
            return $compte_rendu_ids[0];
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur ajout compte rendu: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllCompteRendus() {
        $query = "SELECT * FROM compte_rendu ORDER BY date_cr DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getCompteRenduById($id) {
        $query = "SELECT * FROM compte_rendu WHERE id_cr = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Méthode pour récupérer les comptes rendus avec les informations des étudiants
    public function getCompteRendusWithStudents() {
        $query = "SELECT cr.*, 
                         CONCAT(e.nom_etd, ' ', e.prenom_etd) as etudiants,
                         1 as nombre_rapports
                  FROM compte_rendu cr 
                  LEFT JOIN rapport_etudiant re ON cr.id_rapport_etd = re.id_rapport_etd
                  LEFT JOIN etudiants e ON re.num_etd = e.num_etd
                  ORDER BY cr.date_cr DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Méthode pour récupérer les informations du responsable du compte rendu
    public function getResponsableCompteRenduByCompteRenduId($idCr) {
        $query = "SELECT ens.nom_ens, ens.prenoms_ens, r.id_cr FROM enseignants ens 
        JOIN rendre r ON ens.id_ens = r.id_ens
        WHERE r.id_cr = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $idCr);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        $responsable = $result->nom_ens . ' ' . $result->prenoms_ens;
        return $responsable;
    }

    //Méthode pour vérifier qu'un compte rendu est disponible pour un étudiant
    public function isCompteRenduDisponiblePourEtudiant($idEtudiant, $idCr) {
        $query = "SELECT COUNT(*) as count FROM rapport_etudiant re 
        JOIN rapport_compte_rendu rcr ON re.id_rapport_etd = rcr.id_rapport_etd
        WHERE re.num_etd = :idEtudiant AND rcr.id_cr = :idCr";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':idEtudiant', $idEtudiant);
        $stmt->bindParam(':idCr', $idCr);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return $result->count > 0;
    }

    // UPDATE
    public function modifierCompteRendu($id, $titre, $contenu, $date_cr, $auteur_id, $fichier_path) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE compte_rendu SET nom_cr = :titre, date_cr = :date_cr, fichier_cr = :fichier_path WHERE id_cr = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':titre', $titre);
            $stmt->bindParam(':date_cr', $date_cr);
            $stmt->bindParam(':fichier_path', $fichier_path);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur modification compte rendu: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerCompteRendu($id) {
        try {
            $this->db->beginTransaction();
            
            // ✅ NOUVEAU : Supprimer les associations dans rapport_compte_rendu
            $delete_liaison_query = "DELETE FROM rapport_compte_rendu WHERE id_cr = :id_cr";
            $delete_liaison_stmt = $this->db->prepare($delete_liaison_query);
            $delete_liaison_stmt->bindParam(':id_cr', $id);
            $delete_liaison_stmt->execute();
            
            // ✅ NOUVEAU : Supprimer les associations dans rendre
            $delete_rendre_query = "DELETE FROM rendre WHERE id_cr = :id_cr";
            $delete_rendre_stmt = $this->db->prepare($delete_rendre_query);
            $delete_rendre_stmt->bindParam(':id_cr', $id);
            $delete_rendre_stmt->execute();
            
            // Ensuite, supprimer le compte rendu
            $query = "DELETE FROM compte_rendu WHERE id_cr = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur suppression compte rendu: " . $e->getMessage());
            return false;
        }
    }

    // Méthode pour récupérer le rapport associé à un compte rendu
    public function getRapportsAssocies($compte_rendu_id) {
        $query = "SELECT re.*, e.nom_etd, e.prenom_etd 
                  FROM rapport_etudiant re
                  LEFT JOIN etudiants e ON re.num_etd = e.num_etd
                  INNER JOIN rapport_compte_rendu rcr ON re.id_rapport_etd = rcr.id_rapport_etd
                  WHERE rcr.id_cr = :id_cr";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_cr', $compte_rendu_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Méthode pour récupérer l'ID du rapport associé à un compte rendu
    public function getRapportIdsAssocies($compte_rendu_id) {
        $query = "SELECT id_rapport_etd FROM rapport_compte_rendu WHERE id_cr = :id_cr";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_cr', $compte_rendu_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? [$result['id_rapport_etd']] : [];
    }

    // Méthode pour associer un rapport à un compte rendu
    public function associerRapportAuCompteRendu($compte_rendu_id, $rapport_id) {
        $query = "INSERT INTO rapport_compte_rendu (id_rapport_etd, id_cr) VALUES (:rapport_id, :compte_rendu_id)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':compte_rendu_id', $compte_rendu_id);
        $stmt->bindParam(':rapport_id', $rapport_id);
        return $stmt->execute();
    }

    // Méthode pour dissocier un rapport d'un compte rendu
    public function dissocierRapportDuCompteRendu($rapport_id) {
        try {
            $query = "DELETE FROM rapport_compte_rendu WHERE id_rapport_etd = :rapport_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':rapport_id', $rapport_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur dissociation rapport: " . $e->getMessage());
            return false;
        }
    }

    // Méthode pour dissocier tous les rapports d'un compte rendu
    public function dissocierTousRapports($compte_rendu_id) {
        try {
            $query = "DELETE FROM rapport_compte_rendu WHERE id_cr = :compte_rendu_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':compte_rendu_id', $compte_rendu_id);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur dissociation tous rapports: " . $e->getMessage());
            return false;
        }
    }

    // Méthode pour mettre à jour les associations de rapports
    public function mettreAJourAssociationsRapports($compte_rendu_id, $nouveaux_rapport_ids) {
        try {
            $this->db->beginTransaction();
            
            // Dissocier tous les rapports actuels
            $this->dissocierTousRapports($compte_rendu_id);
            
            // Associer les nouveaux rapports
            if (!empty($nouveaux_rapport_ids)) {
                foreach ($nouveaux_rapport_ids as $rapport_id) {
                    $success = $this->associerRapportAuCompteRendu($compte_rendu_id, $rapport_id);
                    if (!$success) {
                        throw new PDOException("Erreur lors de l'association du rapport ID: $rapport_id");
                    }
                }
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Erreur mise à jour associations: " . $e->getMessage());
            return false;
        }
    }
} 