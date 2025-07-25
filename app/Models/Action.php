<?php
namespace App\Models;

use PDO;
use PDOException;

class Action {
    private $db;
    public function __construct($db) { $this->db = $db; }

    public function ajouterAction($lib_action) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO action (lib_action) VALUES (:lib_action)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_action', $lib_action);
            $stmt->execute();
            $this->db->commit();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout action: " . $e->getMessage());
            return false;
        }
    }

    public function getAllActions() {
        $query = "SELECT * FROM action";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getActionById($id) {
        $query = "SELECT * FROM action WHERE id_action = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function modifierAction($id, $lib_action) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE action SET lib_action = :lib_action WHERE id_action = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':lib_action', $lib_action);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification action: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerAction($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM action WHERE id_action = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression action: " . $e->getMessage());
            return false;
        }
    }

    public function getAll($page = 1, $perPage = 20, $search = '') {
        $offset = ($page - 1) * $perPage;
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = "WHERE lib_action LIKE :search";
            $params[':search'] = "%$search%";
        }
        $sql = "SELECT * FROM action $where ORDER BY id_action DESC LIMIT :perPage OFFSET :offset";
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
            $where = "WHERE lib_action LIKE :search";
            $params[':search'] = "%$search%";
        }
        $sql = "SELECT COUNT(*) FROM action $where";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getById($id) {
        $sql = "SELECT * FROM action WHERE id_action = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 