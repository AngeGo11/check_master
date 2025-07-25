<?php
namespace App\Models;

use PDO;
use PDOException;

class PersonnelAdministratif {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterPersonnelAdministratif($nom, $prenoms, $email, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO personnel_administratif (nom_personnel_adm, prenoms_personnel_adm, email_personnel_adm) VALUES (:nom, :prenoms, :email)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenoms', $prenoms);
            $stmt->bindParam(':email', $email);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout personnel administratif: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllPersonnelAdministratif() {
        $query = "SELECT * FROM personnel_administratif";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPersonnelAdministratifById($id) {
        $query = "SELECT * FROM personnel_administratif WHERE id_personnel_adm = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierPersonnelAdministratif($id, $nom, $prenoms, $email, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE personnel_administratif SET nom_personnel_adm = :nom, prenoms_personnel_adm = :prenoms, email_personnel_adm = :email WHERE id_personnel_adm = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenoms', $prenoms);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $id);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification personnel administratif: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerPersonnelAdministratif($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM personnel_administratif WHERE id_personnel_adm = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression personnel administratif: " . $e->getMessage());
            return false;
        }
    }

    // Méthode delete pour compatibilité avec le contrôleur
    public function delete($id) {
        return $this->supprimerPersonnelAdministratif($id);
    }

    // Nouvelles méthodes ajoutées
    public function getStatistics() {
        $stats = [];
        
        // Total personnel administratif
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM personnel_administratif");
        $stats['total'] = $stmt->fetchColumn();
        
        // Secrétaires
        $stmt = $this->db->query("
            SELECT COUNT(*) as total
            FROM personnel_administratif p
            JOIN utilisateur u ON p.email_personnel_adm = u.login_utilisateur
            JOIN posseder po ON u.id_utilisateur = po.id_util
            JOIN groupe_utilisateur g ON po.id_gu = g.id_gu
            WHERE g.lib_gu = 'Secrétaire'
        ");
        $stats['secretaires'] = $stmt->fetchColumn();
        
        // Chargés de communication
        $stmt = $this->db->query("
            SELECT COUNT(*) as total
            FROM personnel_administratif p
            JOIN utilisateur u ON p.email_personnel_adm = u.login_utilisateur
            JOIN posseder po ON u.id_utilisateur = po.id_util
            JOIN groupe_utilisateur g ON po.id_gu = g.id_gu
            WHERE g.lib_gu = 'Chargé de communication'
        ");
        $stats['communication'] = $stmt->fetchColumn();
        
        // Responsables scolarité
        $stmt = $this->db->query("
            SELECT COUNT(*) as total
            FROM personnel_administratif p
            JOIN utilisateur u ON p.email_personnel_adm = u.login_utilisateur
            JOIN posseder po ON u.id_utilisateur = po.id_util
            JOIN groupe_utilisateur g ON po.id_gu = g.id_gu
            WHERE g.lib_gu = 'Responsable scolarité'
        ");
        $stats['scolarite'] = $stmt->fetchColumn();
        
        return $stats;
    }

    public function getPersonnelWithPagination($page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, g.id_gu, g.lib_gu as poste 
                FROM personnel_administratif p 
                LEFT JOIN utilisateur u ON p.email_personnel_adm = u.login_utilisateur
                LEFT JOIN posseder po ON u.id_utilisateur = po.id_util
                LEFT JOIN groupe_utilisateur g ON po.id_gu = g.id_gu 
                ORDER BY p.id_personnel_adm
                LIMIT $limit OFFSET $offset";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchPersonnel($search, $filters = []) {
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(p.nom_personnel_adm LIKE ? OR p.prenoms_personnel_adm LIKE ? OR p.email_personnel_adm LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
        }
        
        if (!empty($filters['poste'])) {
            $where[] = "g.lib_gu = ?";
            $params[] = $filters['poste'];
        }
        
        if (!empty($filters['genre'])) {
            $where[] = "p.sexe_personnel_adm = ?";
            $params[] = $filters['genre'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT p.*, g.id_gu, g.lib_gu as poste 
                FROM personnel_administratif p 
                LEFT JOIN utilisateur u ON p.email_personnel_adm = u.login_utilisateur
                LEFT JOIN posseder po ON u.id_utilisateur = po.id_util
                LEFT JOIN groupe_utilisateur g ON po.id_gu = g.id_gu 
                $whereClause
                ORDER BY p.nom_personnel_adm, p.prenoms_personnel_adm";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGroupes() {
        return $this->db->query("SELECT id_gu, lib_gu FROM groupe_utilisateur WHERE id_gu IN (2,3,4)")->fetchAll(PDO::FETCH_ASSOC);
    }
} 