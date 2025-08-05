<?php

namespace App\Models;

use PDO;
use PDOException;

class NiveauEtude
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer tous les niveaux d'étude
     */
    public function getAllNiveaux()
    {
        try {
            $sql = "SELECT * FROM niveau_etude ORDER BY id_niv_etd";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération niveaux d'étude: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un niveau par ID
     */
    public function getNiveauById($id_niv_etd)
    {
        try {
            $sql = "SELECT * FROM niveau_etude WHERE id_niv_etd = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_niv_etd]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération niveau d'étude: " . $e->getMessage());
            return false;
        }
    }
} 