<?php

namespace App\Models;

use PDO;
use PDOException;

class Message
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupérer les messages reçus
     */
    public function getMessagesRecus($userId)
    {
        try {
            $sql = "SELECT m.*, 
                    CASE 
                        WHEN e_exp.nom_ens IS NOT NULL THEN CONCAT(e_exp.nom_ens, ' ', e_exp.prenoms_ens)
                        WHEN et_exp.nom_etd IS NOT NULL THEN CONCAT(et_exp.nom_etd, ' ', et_exp.prenom_etd)
                        WHEN pa_exp.nom_personnel_adm IS NOT NULL THEN CONCAT(pa_exp.nom_personnel_adm, ' ', pa_exp.prenoms_personnel_adm)
                        ELSE u_exp.login_utilisateur
                    END as expediteur_nom
                    FROM messages m
                    JOIN utilisateur u_exp ON m.expediteur_id = u_exp.id_utilisateur
                    LEFT JOIN enseignants e_exp ON u_exp.login_utilisateur = e_exp.email_ens
                    LEFT JOIN etudiants et_exp ON u_exp.login_utilisateur = et_exp.email_etd
                    LEFT JOIN personnel_administratif pa_exp ON u_exp.login_utilisateur = pa_exp.email_personnel_adm
                    WHERE m.destinataire_id = ? AND m.statut != 'archivé'
                    ORDER BY m.date_envoi DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération messages reçus: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les contacts
     */
    public function getContacts($userId)
    {
        try {
            $sql = "SELECT DISTINCT u.id_utilisateur, u.login_utilisateur as email,
                    CASE 
                        WHEN e.nom_ens IS NOT NULL THEN CONCAT(e.nom_ens, ' ', e.prenoms_ens)
                        WHEN et.nom_etd IS NOT NULL THEN CONCAT(et.nom_etd, ' ', et.prenom_etd)
                        WHEN pa.nom_personnel_adm IS NOT NULL THEN CONCAT(pa.nom_personnel_adm, ' ', pa.prenoms_personnel_adm)
                        ELSE u.login_utilisateur
                    END as nom_complet
                    FROM utilisateur u
                    LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                    LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                    LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                    WHERE u.id_utilisateur != ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération contacts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compter les messages non lus
     */
    public function compterMessagesNonLus($userId)
    {
        try {
            $sql = "SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND statut = 'non lu'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur comptage messages non lus: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupérer les messages archivés
     */
    public function getMessagesArchives($userId)
    {
        try {
            $sql = "SELECT m.*, 
                    CASE 
                        WHEN e_exp.nom_ens IS NOT NULL THEN CONCAT(e_exp.nom_ens, ' ', e_exp.prenoms_ens)
                        WHEN et_exp.nom_etd IS NOT NULL THEN CONCAT(et_exp.nom_etd, ' ', et_exp.prenom_etd)
                        WHEN pa_exp.nom_personnel_adm IS NOT NULL THEN CONCAT(pa_exp.nom_personnel_adm, ' ', pa_exp.prenoms_personnel_adm)
                        ELSE u_exp.login_utilisateur
                    END as expediteur_nom
                    FROM messages m
                    JOIN utilisateur u_exp ON m.expediteur_id = u_exp.id_utilisateur
                    LEFT JOIN enseignants e_exp ON u_exp.login_utilisateur = e_exp.email_ens
                    LEFT JOIN etudiants et_exp ON u_exp.login_utilisateur = et_exp.email_etd
                    LEFT JOIN personnel_administratif pa_exp ON u_exp.login_utilisateur = pa_exp.email_personnel_adm
                    WHERE m.destinataire_id = ? AND m.statut = 'archivé'
                    ORDER BY m.date_envoi DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération messages archivés: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Envoyer un message
     */
    public function sendMessage($data)
    {
        try {
            $sql = "INSERT INTO messages (expediteur_id, destinataire_id, objet, contenu, priorite, date_envoi, statut) 
                    VALUES (?, ?, ?, ?, ?, NOW(), 'non lu')";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['expediteur_id'],
                $data['destinataire_id'],
                $data['objet'],
                $data['contenu'],
                $data['priorite'] ?? 'normale'
            ]);
        } catch (PDOException $e) {
            error_log("Erreur envoi message: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer un message par ID
     */
    public function getMessageById($messageId)
    {
        try {
            $sql = "SELECT m.*, 
                    CASE 
                        WHEN e_exp.nom_ens IS NOT NULL THEN CONCAT(e_exp.nom_ens, ' ', e_exp.prenoms_ens)
                        WHEN et_exp.nom_etd IS NOT NULL THEN CONCAT(et_exp.nom_etd, ' ', et_exp.prenom_etd)
                        WHEN pa_exp.nom_personnel_adm IS NOT NULL THEN CONCAT(pa_exp.nom_personnel_adm, ' ', pa_exp.prenoms_personnel_adm)
                        ELSE u_exp.login_utilisateur
                    END as expediteur_nom
                    FROM messages m
                    JOIN utilisateur u_exp ON m.expediteur_id = u_exp.id_utilisateur
                    LEFT JOIN enseignants e_exp ON u_exp.login_utilisateur = e_exp.email_ens
                    LEFT JOIN etudiants et_exp ON u_exp.login_utilisateur = et_exp.email_etd
                    LEFT JOIN personnel_administratif pa_exp ON u_exp.login_utilisateur = pa_exp.email_personnel_adm
                    WHERE m.id_message = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$messageId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération message: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead($messageId)
    {
        try {
            $sql = "UPDATE messages SET statut = 'lu' WHERE id_message = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$messageId]);
        } catch (PDOException $e) {
            error_log("Erreur marquage message lu: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Archiver des messages
     */
    public function archiveMessages($messageIds)
    {
        try {
            $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
            $sql = "UPDATE messages SET statut = 'archivé' WHERE id_message IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($messageIds);
        } catch (PDOException $e) {
            error_log("Erreur archivage messages: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer des messages
     */
    public function deleteMessages($messageIds)
    {
        try {
            $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
            $sql = "DELETE FROM messages WHERE id_message IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($messageIds);
        } catch (PDOException $e) {
            error_log("Erreur suppression messages: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restaurer des messages
     */
    public function restoreMessages($messageIds)
    {
        try {
            $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
            $sql = "UPDATE messages SET statut = 'lu' WHERE id_message IN ($placeholders)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($messageIds);
        } catch (PDOException $e) {
            error_log("Erreur restauration messages: " . $e->getMessage());
            return false;
        }
    }

    public function ajouterMessage($expediteur_id, $destinataire_id, $destinataire_type, $objet, $contenu, $type_message, $categorie, $priorite, $statut) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO messages (expediteur_id, destinataire_id, destinataire_type, objet, contenu, type_message, categorie, priorite, statut) VALUES (:expediteur_id, :destinataire_id, :destinataire_type, :objet, :contenu, :type_message, :categorie, :priorite, :statut)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':expediteur_id', $expediteur_id);
            $stmt->bindParam(':destinataire_id', $destinataire_id);
            $stmt->bindParam(':destinataire_type', $destinataire_type);
            $stmt->bindParam(':objet', $objet);
            $stmt->bindParam(':contenu', $contenu);
            $stmt->bindParam(':type_message', $type_message);
            $stmt->bindParam(':categorie', $categorie);
            $stmt->bindParam(':priorite', $priorite);
            $stmt->bindParam(':statut', $statut);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout message: " . $e->getMessage());
            return false;
        }
    }

    public function getAllMessages() {
        $query = "SELECT * FROM messages ORDER BY date_creation DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

  

    public function getMessagesByUser($user_id) {
        $query = "SELECT * FROM messages WHERE destinataire_id = :user_id OR expediteur_id = :user_id ORDER BY date_creation DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function modifierMessage($id, $objet, $contenu, $statut) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE messages SET objet = :objet, contenu = :contenu, statut = :statut WHERE id_message = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':objet', $objet);
            $stmt->bindParam(':contenu', $contenu);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification message: " . $e->getMessage());
            return false;
        }
    }

    public function supprimerMessage($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM messages WHERE id_message = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression message: " . $e->getMessage());
            return false;
        }
    }

    public function marquerCommeLu($id) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE messages SET statut = 'lu', date_lecture = NOW() WHERE id_message = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur marquage message lu: " . $e->getMessage());
            return false;
        }
    }

    public function archiverMessage($id) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE messages SET statut = 'archivé' WHERE id_message = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur archivage message: " . $e->getMessage());
            return false;
        }
    }
}
