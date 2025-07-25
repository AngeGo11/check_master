<?php

namespace App\Models;

use PDO;
use PDOException;

class Utilisateur
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function ajouterUtilisateur($login_utilisateur, $mdp_utilisateur, $statut_utilisateur, $id_niveau_acces)
    {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO utilisateur (login_utilisateur, mdp_utilisateur, statut_utilisateur, id_niveau_acces) VALUES (:login_utilisateur, :mdp_utilisateur, :statut_utilisateur, :id_niveau_acces)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':login_utilisateur', $login_utilisateur);
            $stmt->bindParam(':mdp_utilisateur', $mdp_utilisateur);
            $stmt->bindParam(':statut_utilisateur', $statut_utilisateur);
            $stmt->bindParam(':id_niveau_acces', $id_niveau_acces);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout utilisateur: " . $e->getMessage());
            return false;
        }
    }

    public function updateUtilisateur($login_utilisateur, $mdp_utilisateur, $statut_utilisateur, $id_niveau_acces, $id)
    {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE utilisateur SET login_utilisateur = :login_utilisateur, mdp_utilisateur = :mdp_utilisateur, statut_utilisateur = :statut_utilisateur, id_niveau_acces = :id_niveau_acces WHERE id_utilisateur = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':login_utilisateur', $login_utilisateur);
            $stmt->bindParam(':mdp_utilisateur', $mdp_utilisateur);
            $stmt->bindParam(':statut_utilisateur', $statut_utilisateur);
            $stmt->bindParam(':id_niveau_acces', $id_niveau_acces);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Désactive un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @return bool True si la désactivation a réussi
     */
    public function desactiverUtilisateur($id)
    {
        $sql = "UPDATE utilisateur SET statut_utilisateur = 'Inactif' WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Réactive un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @return bool True si la réactivation a réussi
     */
    public function reactiverUtilisateur($id)
    {
        $sql = "UPDATE utilisateur SET statut_utilisateur = 'Actif' WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updatePassword($id, $newPassword)
    {
        $query = "UPDATE utilisateur SET mdp_utilisateur = :mdp WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':mdp', $newPassword);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAllUtilisateursActifs()
    {
        $query = "SELECT u.id_utilisateur, u.nom_utilisateur, u.login_utilisateur, 
                    u.statut_utilisateur,
                    t.lib_type_utilisateur as role_utilisateur,
                    g.lib_GU as gu,
                    n.lib_niveau_acces_donnees as niveau_acces
              FROM utilisateur u
              LEFT JOIN type_utilisateur t ON u.id_type_utilisateur = t.id_type_utilisateur
              LEFT JOIN groupe_utilisateur g ON u.id_GU = g.id_GU
              LEFT JOIN niveau_acces_donnees n ON u.id_niv_acces_donnee = n.id_niveau_acces_donnees
              WHERE u.statut_utilisateur = 'Actif'
              ORDER BY u.nom_utilisateur";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    public function getAllUtilisateursInactifs()
    {
        $query = "SELECT u.id_utilisateur, u.nom_utilisateur, u.login_utilisateur, 
                    u.statut_utilisateur,
                    t.lib_type_utilisateur as role_utilisateur,
                    g.lib_GU as gu,
                    n.lib_niveau_acces_donnees as niveau_acces
              FROM utilisateur u
              LEFT JOIN type_utilisateur t ON u.id_type_utilisateur = t.id_type_utilisateur
              LEFT JOIN groupe_utilisateur g ON u.id_GU = g.id_GU
              LEFT JOIN niveau_acces_donnees n ON u.id_niv_acces_donnee = n.id_niveau_acces_donnees
              WHERE u.statut_utilisateur = 'Inactif'
              ORDER BY u.nom_utilisateur";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // READ
    public function getAllUtilisateurs()
    {
        $query = "SELECT * FROM utilisateur";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getUtilisateurById($id)
    {
        $query = "SELECT * FROM utilisateur WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getUserInfos($login)
    {
        $query = "SELECT u.*, e.*, 
            CASE 
                WHEN e.nom_ens IS NOT NULL THEN CONCAT(e.prenoms_ens, ' ', e.nom_ens)
                WHEN et.nom_etd IS NOT NULL THEN CONCAT(et.prenom_etd, ' ', et.nom_etd)
                WHEN pa.nom_personnel_adm IS NOT NULL THEN CONCAT(pa.prenoms_personnel_adm, ' ', pa.nom_personnel_adm)
                ELSE 'Utilisateur'
            END AS nom_complet
            FROM utilisateur u
            LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
            LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
            LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
            WHERE u.login_utilisateur = :login";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':login', $login);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // DELETE
    public function supprimerUtilisateur($id)
    {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM utilisateur WHERE id_utilisateur = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression utilisateur: " . $e->getMessage());
            return false;
        }
    }
}
