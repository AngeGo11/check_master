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

    /**
     * Récupérer les contacts avec pagination
     */
    public function getContactsWithPagination($userId, $page = 1, $limit = 15, $filters = [])
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Construction de la clause WHERE
            $whereClause = "WHERE u.id_utilisateur != ?";
            $params = [$userId];
            
            // Ajout des filtres
            if (!empty($filters['search_contact'])) {
                $whereClause .= " AND (e.nom_ens LIKE ? OR e.prenoms_ens LIKE ? OR et.nom_etd LIKE ? OR et.prenom_etd LIKE ? OR pa.nom_personnel_adm LIKE ? OR pa.prenoms_personnel_adm LIKE ? OR u.login_utilisateur LIKE ?)";
                $searchTerm = '%' . $filters['search_contact'] . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            }
            
            // Requête pour compter le total
            $countSql = "SELECT COUNT(DISTINCT u.id_utilisateur) as total
                        FROM utilisateur u
                        LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                        LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                        LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                        $whereClause";
            
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            
            // Requête pour récupérer les données avec pagination
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
                    $whereClause
                    ORDER BY nom_complet LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalPages = ceil($totalItems / $limit);
            
            return [
                'data' => $data,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'items_per_page' => $limit
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération contacts avec pagination: " . $e->getMessage());
            return [
                'data' => [],
                'total_items' => 0,
                'total_pages' => 1,
                'current_page' => $page,
                'items_per_page' => $limit
            ];
        }
    }

    /**
     * Récupérer les messages reçus avec pagination
     */
    public function getMessagesRecusWithPagination($userId, $page = 1, $limit = 5, $filters = [])
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Construction de la requête de base
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
                    WHERE m.destinataire_id = ? AND m.statut != 'archivé'";
            
            $params = [$userId];
            
            // Ajout des filtres
            if (!empty($filters['search'])) {
                $sql .= " AND (m.objet LIKE ? OR m.contenu LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['filter_statut'])) {
                $sql .= " AND m.statut = ?";
                $params[] = $filters['filter_statut'];
            }
            
            if (!empty($filters['filter_priorite'])) {
                $sql .= " AND m.priorite = ?";
                $params[] = $filters['filter_priorite'];
            }
            
            if (!empty($filters['filter_date'])) {
                switch ($filters['filter_date']) {
                    case 'today':
                        $sql .= " AND DATE(m.date_envoi) = CURDATE()";
                        break;
                    case 'week':
                        $sql .= " AND m.date_envoi >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        break;
                    case 'month':
                        $sql .= " AND m.date_envoi >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                        break;
                }
            }
            
            // Requête pour compter le total
            $countSql = str_replace("SELECT m.*,", "SELECT COUNT(*) as total", $sql);
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            
            // Requête pour récupérer les données avec pagination
            $sql .= " ORDER BY m.date_envoi DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalPages = ceil($totalItems / $limit);
            
            return [
                'data' => $data,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'items_per_page' => $limit
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération messages avec pagination: " . $e->getMessage());
            return [
                'data' => [],
                'total_items' => 0,
                'total_pages' => 1,
                'current_page' => $page,
                'items_per_page' => $limit
            ];
        }
    }

    /**
     * Récupérer les messages envoyés
     */
    public function getMessagesEnvoyes($userId)
    {
        try {
            $sql = "SELECT m.*, 
                    CASE 
                        WHEN e_dest.nom_ens IS NOT NULL THEN CONCAT(e_dest.nom_ens, ' ', e_dest.prenoms_ens)
                        WHEN et_dest.nom_etd IS NOT NULL THEN CONCAT(et_dest.nom_etd, ' ', et_dest.prenom_etd)
                        WHEN pa_dest.nom_personnel_adm IS NOT NULL THEN CONCAT(pa_dest.nom_personnel_adm, ' ', pa_dest.prenoms_personnel_adm)
                        ELSE u_dest.login_utilisateur
                    END as destinataire_nom
                    FROM messages m
                    JOIN utilisateur u_dest ON m.destinataire_id = u_dest.id_utilisateur
                    LEFT JOIN enseignants e_dest ON u_dest.login_utilisateur = e_dest.email_ens
                    LEFT JOIN etudiants et_dest ON u_dest.login_utilisateur = et_dest.email_etd
                    LEFT JOIN personnel_administratif pa_dest ON u_dest.login_utilisateur = pa_dest.email_personnel_adm
                    WHERE m.expediteur_id = ?
                    ORDER BY m.date_envoi DESC
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération messages envoyés: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les rappels
     */
    public function getRappels($userId)
    {
        try {
            $sql = "SELECT * FROM rappels WHERE destinataire_id = ? AND date_creation >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY date_creation DESC LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération rappels: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les statistiques
     */
    public function getStatistics($userId)
    {
        try {
            // Total des messages
            $sqlTotal = "SELECT COUNT(*) FROM messages WHERE destinataire_id = ?";
            $stmtTotal = $this->db->prepare($sqlTotal);
            $stmtTotal->execute([$userId]);
            $totalMessages = $stmtTotal->fetchColumn();
            
            // Nouveaux messages (cette semaine)
            $sqlNouveaux = "SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND date_envoi >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmtNouveaux = $this->db->prepare($sqlNouveaux);
            $stmtNouveaux->execute([$userId]);
            $nouveauxMessages = $stmtNouveaux->fetchColumn();
            
            // Messages non lus
            $sqlNonLus = "SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND statut = 'non lu'";
            $stmtNonLus = $this->db->prepare($sqlNonLus);
            $stmtNonLus->execute([$userId]);
            $messagesNonLus = $stmtNonLus->fetchColumn();
            
            // Messages répondus
            $sqlRepondus = "SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND statut = 'repondu'";
            $stmtRepondus = $this->db->prepare($sqlRepondus);
            $stmtRepondus->execute([$userId]);
            $messagesRepondus = $stmtRepondus->fetchColumn();
            
            // Calcul des évolutions (simplifié)
            $evolutionTotal = $totalMessages > 0 ? round(($nouveauxMessages / $totalMessages) * 100) : 0;
            $evolutionNouveaux = $nouveauxMessages > 0 ? 15 : 0; // Simulation
            $evolutionNonLus = $messagesNonLus > 0 ? -5 : 0; // Simulation
            $evolutionRepondus = $totalMessages > 0 ? round(($messagesRepondus / $totalMessages) * 100) : 0;
            
            return [
                'total_messages' => $totalMessages,
                'evolution_total' => $evolutionTotal,
                'nouveaux_messages' => $nouveauxMessages,
                'evolution_nouveaux' => $evolutionNouveaux,
                'messages_non_lus' => $messagesNonLus,
                'evolution_non_lus' => $evolutionNonLus,
                'messages_repondus' => $messagesRepondus,
                'evolution_repondus' => $evolutionRepondus
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération statistiques: " . $e->getMessage());
            return [
                'total_messages' => 0,
                'evolution_total' => 0,
                'nouveaux_messages' => 0,
                'evolution_nouveaux' => 0,
                'messages_non_lus' => 0,
                'evolution_non_lus' => 0,
                'messages_repondus' => 0,
                'evolution_repondus' => 0
            ];
        }
    }

    /**
     * Récupérer les messages non lus avec pagination
     */
    public function getMessagesNonLusWithPagination($userId, $page = 1, $limit = 5, $filters = [])
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Construction de la requête de base
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
                    WHERE m.destinataire_id = ? AND m.statut = 'non lu'";
            
            $params = [$userId];
            
            // Ajout des filtres
            if (!empty($filters['search'])) {
                $sql .= " AND (m.objet LIKE ? OR m.contenu LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['filter_priorite'])) {
                $sql .= " AND m.priorite = ?";
                $params[] = $filters['filter_priorite'];
            }
            
            if (!empty($filters['filter_date'])) {
                switch ($filters['filter_date']) {
                    case 'today':
                        $sql .= " AND DATE(m.date_creation) = CURDATE()";
                        break;
                    case 'week':
                        $sql .= " AND m.date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        break;
                    case 'month':
                        $sql .= " AND m.date_creation >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                        break;
                }
            }
            
            // Requête pour compter le total
            $countSql = str_replace("SELECT m.*,", "SELECT COUNT(*) as total", $sql);
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            
            // Requête pour récupérer les données avec pagination
            $sql .= " ORDER BY m.date_creation DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalPages = ceil($totalItems / $limit);
            
            return [
                'data' => $data,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'items_per_page' => $limit
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération messages non lus avec pagination: " . $e->getMessage());
            return [
                'data' => [],
                'total_items' => 0,
                'total_pages' => 1,
                'current_page' => $page,
                'items_per_page' => $limit
            ];
        }
    }

    /**
     * Récupérer les messages archivés avec pagination
     */
    public function getMessagesArchivesWithPagination($userId, $page = 1, $limit = 5, $filters = [])
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Construction de la requête de base
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
                    WHERE m.destinataire_id = ? AND m.statut = 'archivé'";
            
            $params = [$userId];
            
            // Ajout des filtres
            if (!empty($filters['search'])) {
                $sql .= " AND (m.objet LIKE ? OR m.contenu LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['filter_priorite'])) {
                $sql .= " AND m.priorite = ?";
                $params[] = $filters['filter_priorite'];
            }
            
            if (!empty($filters['filter_date'])) {
                switch ($filters['filter_date']) {
                    case 'today':
                        $sql .= " AND DATE(m.date_creation) = CURDATE()";
                        break;
                    case 'week':
                        $sql .= " AND m.date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                        break;
                    case 'month':
                        $sql .= " AND m.date_creation >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                        break;
                }
            }
            
            // Requête pour compter le total
            $countSql = str_replace("SELECT m.*,", "SELECT COUNT(*) as total", $sql);
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $totalItems = $countStmt->fetchColumn();
            
            // Requête pour récupérer les données avec pagination
            $sql .= " ORDER BY m.date_creation DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalPages = ceil($totalItems / $limit);
            
            return [
                'data' => $data,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'items_per_page' => $limit
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération messages archivés avec pagination: " . $e->getMessage());
            return [
                'data' => [],
                'total_items' => 0,
                'total_pages' => 1,
                'current_page' => $page,
                'items_per_page' => $limit
            ];
        }
    }

    /**
     * Méthode de débogage pour vérifier les messages non lus
     */
    public function debugMessagesNonLus($userId)
    {
        try {
            // Vérifier tous les statuts possibles
            $sql = "SELECT statut, COUNT(*) as count FROM messages WHERE destinataire_id = ? GROUP BY statut";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $statuts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Vérifier les messages non lus spécifiquement
            $sql = "SELECT id_message, objet, statut, date_creation FROM messages WHERE destinataire_id = ? AND statut = 'non lu'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $messages_non_lus = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'statuts' => $statuts,
                'messages_non_lus' => $messages_non_lus,
                'total_non_lus' => count($messages_non_lus)
            ];
        } catch (PDOException $e) {
            error_log("Erreur debug messages non lus: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
