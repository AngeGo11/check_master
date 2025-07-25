<?php

require_once __DIR__ . '/../../config/config.php';

class ReunionModel {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getConnection();
    }

    /**
     * Récupère toutes les réunions avec filtres
     */
    public function getReunions($search = '', $date_filter = '', $statut_filter = '', $page = 1, $limit = 10) {
        // Conversion des paramètres numériques en entiers
        $page = (int)$page;
        $limit = (int)$limit;
        
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(r.titre LIKE ? OR r.description LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param]);
        }

        if (!empty($date_filter)) {
            switch ($date_filter) {
                case 'today':
                    $where[] = "DATE(r.date_reunion) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "YEARWEEK(r.date_reunion) = YEARWEEK(CURDATE())";
                    break;
                case 'month':
                    $where[] = "MONTH(r.date_reunion) = MONTH(CURDATE()) AND YEAR(r.date_reunion) = YEAR(CURDATE())";
                    break;
                case 'upcoming':
                    $where[] = "r.date_reunion >= CURDATE()";
                    break;
            }
        }

        if (!empty($statut_filter)) {
            $where[] = "r.status = ?";
            $params[] = $statut_filter;
        }

        $where_clause = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) FROM reunions r $where_clause";
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_reunions = $count_stmt->fetchColumn();

        // Requête principale
        $sql = "SELECT r.*, 
                       COUNT(p.id_utilisateur) as nb_participants,
                       SUM(CASE WHEN p.status = 'acceptée' THEN 1 ELSE 0 END) as nb_acceptes,
                       SUM(CASE WHEN p.status = 'refusée' THEN 1 ELSE 0 END) as nb_refuses
                FROM reunions r
                LEFT JOIN participants p ON r.id = p.reunion_id
                $where_clause
                GROUP BY r.id
                ORDER BY r.date_reunion DESC, r.heure_debut ASC
                LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $reunions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'reunions' => $reunions,
            'total' => $total_reunions,
            'pages' => ceil($total_reunions / $limit)
        ];
    }

    /**
     * Récupère une réunion par ID
     */
    public function getReunionById($id) {
        $sql = "SELECT r.*, 
                       COUNT(p.id_utilisateur) as nb_participants,
                       SUM(CASE WHEN p.status = 'acceptée' THEN 1 ELSE 0 END) as nb_acceptes,
                       SUM(CASE WHEN p.status = 'refusée' THEN 1 ELSE 0 END) as nb_refuses
                FROM reunions r
                LEFT JOIN participants p ON r.id = p.reunion_id
                WHERE r.id = ?
                GROUP BY r.id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crée une nouvelle réunion
     */
    public function createReunion($titre, $description, $date_reunion, $heure_debut, $duree, $lieu, $type = 'normale', $statut = 'programmée') {
        try {
            $this->pdo->beginTransaction();
            
            $sql = "INSERT INTO reunions (titre, description, date_reunion, heure_debut, duree, lieu, type, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$titre, $description, $date_reunion, $heure_debut, $duree, $lieu, $type, $statut]);
            
            $reunion_id = $this->pdo->lastInsertId();
            
            $this->pdo->commit();
            return $reunion_id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Met à jour une réunion
     */
    public function updateReunion($id, $titre, $description, $date_reunion, $heure_debut, $duree, $lieu, $type, $statut) {
        $sql = "UPDATE reunions SET titre = ?, description = ?, date_reunion = ?, heure_debut = ?, duree = ?, lieu = ?, type = ?, status = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$titre, $description, $date_reunion, $heure_debut, $duree, $lieu, $type, $statut, $id]);
    }

    /**
     * Supprime une réunion
     */
    public function deleteReunion($id) {
        try {
            $this->pdo->beginTransaction();
            
            // Supprimer d'abord les participants
            $sql_participants = "DELETE FROM participants WHERE reunion_id = ?";
            $stmt_participants = $this->pdo->prepare($sql_participants);
            $stmt_participants->execute([$id]);
            
            // Puis supprimer la réunion
            $sql_reunion = "DELETE FROM reunions WHERE id = ?";
            $stmt_reunion = $this->pdo->prepare($sql_reunion);
            $stmt_reunion->execute([$id]);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Change le statut d'une réunion
     */
    public function changeStatutReunion($id, $statut) {
        $sql = "UPDATE reunions SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$statut, $id]);
    }

    /**
     * Récupère les réunions à venir
     */
    public function getReunionsAVenir($limit = 5) {
        $sql = "SELECT r.*, 
                       COUNT(p.id_utilisateur) as nb_participants
                FROM reunions r
                LEFT JOIN participants p ON r.id = p.reunion_id
                WHERE r.date_reunion >= CURDATE() AND r.status = 'programmée'
                GROUP BY r.id
                ORDER BY r.date_reunion ASC, r.heure_debut ASC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les participants d'une réunion
     */
    public function getParticipantsReunion($reunion_id) {
        $sql = "SELECT p.*, 
                       COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, u.login_utilisateur) as nom,
                       COALESCE(e.prenoms_ens, pa.prenoms_personnel_adm, et.prenom_etd, '') as prenoms,
                       COALESCE(e.email_ens, pa.email_personnel_adm, et.email_etd, u.login_utilisateur) as email,
                       CASE 
                           WHEN e.id_ens IS NOT NULL THEN 'Enseignant'
                           WHEN pa.id_personnel_adm IS NOT NULL THEN 'Personnel administratif'
                           WHEN et.num_etd IS NOT NULL THEN 'Étudiant'
                           ELSE 'Utilisateur'
                       END as type_utilisateur
                FROM participants p
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                WHERE p.reunion_id = ?
                ORDER BY p.date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$reunion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un participant à une réunion
     */
    public function ajouterParticipant($reunion_id, $utilisateur_id, $status = 'en attente') {
        $sql = "INSERT INTO participants (reunion_id, id_utilisateur, status, date) 
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE status = VALUES(status), date = NOW()";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$reunion_id, $utilisateur_id, $status]);
    }

    /**
     * Supprime un participant d'une réunion
     */
    public function supprimerParticipant($reunion_id, $utilisateur_id) {
        $sql = "DELETE FROM participants WHERE reunion_id = ? AND id_utilisateur = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$reunion_id, $utilisateur_id]);
    }

    /**
     * Met à jour le statut d'un participant
     */
    public function updateStatutParticipant($reunion_id, $utilisateur_id, $status) {
        $sql = "UPDATE participants SET status = ?, date = NOW() WHERE reunion_id = ? AND id_utilisateur = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $reunion_id, $utilisateur_id]);
    }

    /**
     * Récupère les utilisateurs disponibles pour les réunions
     */
    public function getUtilisateursDisponibles() {
        $sql = "SELECT u.id_utilisateur, u.login_utilisateur,
                       COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd) as nom,
                       COALESCE(e.prenoms_ens, pa.prenoms_personnel_adm, et.prenom_etd, '') as prenoms,
                       COALESCE(e.email_ens, pa.email_personnel_adm, et.email_etd, u.login_utilisateur) as email,
                       CASE 
                           WHEN e.id_ens IS NOT NULL THEN 'Enseignant'
                           WHEN pa.id_personnel_adm IS NOT NULL THEN 'Personnel administratif'
                           WHEN et.num_etd IS NOT NULL THEN 'Étudiant'
                           ELSE 'Utilisateur'
                       END as type_utilisateur
                FROM utilisateur u
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                ORDER BY nom, prenoms";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les statistiques des réunions
     */
    public function getStatistiquesReunions() {
        $sql = "SELECT 
                    COUNT(*) as total_reunions,
                    SUM(CASE WHEN status = 'programmée' THEN 1 ELSE 0 END) as reunions_programmees,
                    SUM(CASE WHEN status = 'en cours' THEN 1 ELSE 0 END) as reunions_en_cours,
                    SUM(CASE WHEN status = 'terminée' THEN 1 ELSE 0 END) as reunions_terminees,
                    SUM(CASE WHEN status = 'annulée' THEN 1 ELSE 0 END) as reunions_annulees,
                    SUM(CASE WHEN date_reunion >= CURDATE() THEN 1 ELSE 0 END) as reunions_a_venir,
                    SUM(CASE WHEN type = 'urgente' THEN 1 ELSE 0 END) as reunions_urgentes
                FROM reunions";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Recherche des réunions
     */
    public function rechercherReunions($terme_recherche, $filtres = []) {
        $where = [];
        $params = [];

        if (!empty($terme_recherche)) {
            $where[] = "(r.titre LIKE ? OR r.description LIKE ? OR r.lieu LIKE ?)";
            $search_param = "%$terme_recherche%";
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
        }

        if (!empty($filtres['type'])) {
            $where[] = "r.type = ?";
            $params[] = $filtres['type'];
        }

        if (!empty($filtres['status'])) {
            $where[] = "r.status = ?";
            $params[] = $filtres['status'];
        }

        if (!empty($filtres['date_debut'])) {
            $where[] = "r.date_reunion >= ?";
            $params[] = $filtres['date_debut'];
        }

        if (!empty($filtres['date_fin'])) {
            $where[] = "r.date_reunion <= ?";
            $params[] = $filtres['date_fin'];
        }

        $where_clause = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT r.*, 
                       COUNT(p.id_utilisateur) as nb_participants
                FROM reunions r
                LEFT JOIN participants p ON r.id = p.reunion_id
                $where_clause
                GROUP BY r.id
                ORDER BY r.date_reunion DESC, r.heure_debut ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les types de réunions disponibles
     */
    public function getTypesReunions() {
        return ['normale', 'urgente'];
    }

    /**
     * Récupère les statuts de réunions disponibles
     */
    public function getStatutsReunions() {
        return ['programmée', 'en cours', 'terminée', 'annulée'];
    }

    /**
     * Récupère les statuts de participants disponibles
     */
    public function getStatutsParticipants() {
        return ['en attente', 'acceptée', 'refusée'];
    }
} 