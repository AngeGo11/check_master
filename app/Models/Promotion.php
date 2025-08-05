<?php

namespace App\Models;

use PDO;
use PDOException;

class Promotion
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer toutes les promotions
     */
    public function getAllPromotions()
    {
        try {
            $sql = "SELECT * FROM promotion ORDER BY id_promotion DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération promotions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer une promotion par ID
     */
    public function getPromotionById($id_promotion)
    {
        try {
            $sql = "SELECT * FROM promotion WHERE id_promotion = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_promotion]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération promotion: " . $e->getMessage());
            return false;
        }
    }
} 