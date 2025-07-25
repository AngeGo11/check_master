<?php

require_once __DIR__ . '/../../config/config.php';

class ReclamationModel {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getConnection();
    }

    /**
     * Récupère les réclamations avec filtres
     */
    public function getReclamations($search = '', $date_filter = '', $statut_filter = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];

        if (!empty($search)) {
            $where[] = "(r.sujet_reclamation LIKE ? OR r.description LIKE ? OR e.nom_etd LIKE ? OR e.prenom_etd LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }

        if (!empty($date_filter)) {
            switch ($date_filter) {
                case 'today':
                    $where[] = "DATE(r.date_reclamation) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "YEARWEEK(r.date_reclamation) = YEARWEEK(CURDATE())";
                    break;
                case 'month':
                    $where[] = "MONTH(r.date_reclamation) = MONTH(CURDATE()) AND YEAR(r.date_reclamation) = YEAR(CURDATE())";
                    break;
            }
        }

        if (!empty($statut_filter)) {
            $where[] = "r.statut_reclamation = ?";
            $params[] = $statut_filter;
        }

        $where_clause = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) FROM reclamations r
            LEFT JOIN etudiants e ON r.num_etd = e.num_etd
            $where_clause";
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_reclamations = $count_stmt->fetchColumn();

        // Requête principale
        $sql = "SELECT r.*, e.nom_etd, e.prenom_etd, e.email_etd
            FROM reclamations r
            LEFT JOIN etudiants e ON r.num_etd = e.num_etd
            $where_clause
            ORDER BY r.date_reclamation DESC
            LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'reclamations' => $reclamations,
            'total' => $total_reclamations,
            'pages' => ceil($total_reclamations / $limit)
        ];
    }

    /**
     * Récupère une réclamation par ID
     */
    public function getReclamationById($id) {
        $sql = "SELECT r.*, e.nom_etd, e.prenom_etd, e.email_etd
            FROM reclamations r
            LEFT JOIN etudiants e ON r.num_etd = e.num_etd
            WHERE r.id_reclamation = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crée une nouvelle réclamation
     */
    public function createReclamation($num_etd, $sujet, $description, $priorite = 'normale') {
        $sql = "INSERT INTO reclamations (num_etd, sujet_reclamation, description, priorite, statut_reclamation, date_reclamation) 
                VALUES (?, ?, ?, ?, 'en_attente', NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$num_etd, $sujet, $description, $priorite]);
    }

    /**
     * Met à jour une réclamation
     */
    public function updateReclamation($id, $sujet, $description, $priorite, $statut) {
        $sql = "UPDATE reclamations SET sujet_reclamation = ?, description = ?, priorite = ?, statut_reclamation = ? 
                WHERE id_reclamation = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$sujet, $description, $priorite, $statut, $id]);
    }

    /**
     * Supprime une réclamation
     */
    public function deleteReclamation($id) {
        $sql = "DELETE FROM reclamations WHERE id_reclamation = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Change le statut d'une réclamation
     */
    public function changeStatutReclamation($id, $statut, $reponse = null) {
        $sql = "UPDATE reclamations SET statut_reclamation = ?, reponse = ?, date_traitement = NOW() WHERE id_reclamation = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$statut, $reponse, $id]);
    }

    /**
     * Récupère les réclamations d'un étudiant
     */
    public function getReclamationsEtudiant($num_etd) {
        $sql = "SELECT * FROM reclamations WHERE num_etd = ? ORDER BY date_reclamation DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$num_etd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les statistiques des réclamations
     */
    public function getReclamationStats() {
        $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN statut_reclamation = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
            SUM(CASE WHEN statut_reclamation = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
            SUM(CASE WHEN statut_reclamation = 'resolue' THEN 1 ELSE 0 END) as resolue,
            SUM(CASE WHEN statut_reclamation = 'rejetee' THEN 1 ELSE 0 END) as rejetee
        FROM reclamations";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
} 