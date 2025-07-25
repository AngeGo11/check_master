<?php
namespace App\Models;

use PDO;
use PDOException;

class Traitement {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function ajouterTraitement($lib_traitement) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO traitement (lib_traitement) VALUES (:lib_traitement)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_traitement', $lib_traitement);
            $stmt->execute();
            $this->db->commit();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout traitement: " . $e->getMessage());
            return false;
        }
    }

    public function getAllTraitements() {
        $query = "SELECT * FROM traitement";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getTraitementById($id) {
        $query = "SELECT * FROM traitement WHERE id_traitement = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function modifierTraitement($id, $lib_traitement) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE traitement SET lib_traitement = :lib_traitement WHERE id_traitement = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_traitement', $lib_traitement);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification traitement: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerTraitement($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM traitement WHERE id_traitement = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression traitement: " . $e->getMessage());
            return false;
        }
    }

    public function getAll($page = 1, $perPage = 20, $search = '') {
        $offset = ($page - 1) * $perPage;
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE lib_traitement LIKE :search";
            $params[':search'] = "%$search%";
        }
        $sql = "SELECT * FROM traitement $where ORDER BY id_traitement DESC LIMIT :perPage OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll($search = '') {
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE lib_traitement LIKE :search";
            $params[':search'] = "%$search%";
        }
        $sql = "SELECT COUNT(*) FROM traitements $where";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getById($id) {
        $sql = "SELECT * FROM traitement WHERE id_traitement = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTraitementByGU($idGroupe) {
        $sql = "SELECT DISTINCT t.id_traitement, t.lib_traitement, t.nom_traitement, t.classe_icone
                FROM traitement t
                JOIN rattacher r ON t.id_traitement = r.id_traitement
                JOIN groupe_utilisateur gu ON r.id_gu = gu.id_gu
                WHERE gu.id_gu = :id_groupe
                ORDER BY t.id_traitement";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id_groupe', $idGroupe, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} 