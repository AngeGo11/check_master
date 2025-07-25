<?php
namespace App\Models;

use PDO;
use PDOException;

class ResetPassword {
    private $db;
    public function __construct($db) { $this->db = $db; }

    // CREATE
    public function ajouterResetPassword($email, $token, $expiration, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO reset_password (email, token, expiration) VALUES (:email, :token, :expiration)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expiration', $expiration);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return $token;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout reset password: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllResetPasswords() {
        $query = "SELECT * FROM reset_password";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getResetPasswordByToken($token) {
        $query = "SELECT * FROM reset_password WHERE token = :token";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierResetPassword($token, $email, $expiration, $autres_champs = []) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE reset_password SET email = :email, expiration = :expiration WHERE token = :token";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':expiration', $expiration);
            $stmt->bindParam(':token', $token);
            // Ajouter ici les autres bindParam si besoin
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification reset password: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerResetPassword($token) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM reset_password WHERE token = :token";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression reset password: " . $e->getMessage());
            return false;
        }
    }
} 