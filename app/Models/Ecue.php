<?php

namespace App\Models;

use PDO;
use PDOException;

class Ecue
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les ECUEs
     */
    public function getAllEcues()
    {
        try {
            $sql = "SELECT * FROM ecue ORDER BY lib_ecue";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération ECUEs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un ECUE par ID
     */
    public function getEcueById($id_ecue)
    {
        try {
            $sql = "SELECT * FROM ecue WHERE id_ecue = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_ecue]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération ECUE: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les ECUEs par promotion
     */
    public function getEcuesByPromotion($id_promotion)
    {
        try {
            $sql = "SELECT * FROM ecue WHERE id_promotion = ? ORDER BY lib_ecue";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_promotion]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération ECUEs par promotion: " . $e->getMessage());
            return [];
        }
    }
} 