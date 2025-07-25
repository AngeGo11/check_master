<?php

require_once __DIR__ . '/../../config/config.php';

class ConsultationModel {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getConnection();
    }

    /**
     * Récupère les statistiques des comptes rendus
     */
    public function getCompteRenduStats() {
        $sql = "SELECT 
            COUNT(DISTINCT cr.id_cr) as total_cr,
            COUNT(DISTINCT CASE WHEN re.statut_rapport = 'Validé' THEN cr.id_cr END) as cr_valides,
            COUNT(DISTINCT CASE WHEN re.statut_rapport IN ('En attente de validation', 'Approuvé') THEN cr.id_cr END) as cr_en_cours
        FROM compte_rendu cr
        LEFT JOIN rendre r ON r.id_cr = cr.id_cr
        LEFT JOIN valider v ON v.id_ens = r.id_ens
        LEFT JOIN rapport_etudiant re ON re.id_rapport_etd = v.id_rapport_etd";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les rapports validés ou rejetés
     */
    public function getRapportsValides() {
        $sql = "SELECT DISTINCT re.*, e.nom_etd, e.prenom_etd, v.date_validation, 
                d.date_depot, a.date_approbation, a.com_appr
                FROM rapport_etudiant re
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN valider v ON v.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN approuver a ON a.id_rapport_etd = re.id_rapport_etd
                WHERE re.statut_rapport IN ('Validé', 'Rejeté')
                ORDER BY re.date_rapport DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les comptes rendus avec filtres et pagination
     */
    public function getComptesRendus($search = '', $date_filter = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $where_cr = [];
        $params_cr = [];

        if (!empty($search)) {
            $where_cr[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR cr.nom_cr LIKE ? OR r.nom_rapport LIKE ?)";
            $search_param = "%$search%";
            $params_cr = array_merge($params_cr, [$search_param, $search_param, $search_param, $search_param]);
        }

        if (!empty($date_filter)) {
            switch ($date_filter) {
                case 'today':
                    $where_cr[] = "DATE(cr.date_cr) = CURDATE()";
                    break;
                case 'week':
                    $where_cr[] = "YEARWEEK(cr.date_cr) = YEARWEEK(CURDATE())";
                    break;
                case 'month':
                    $where_cr[] = "MONTH(cr.date_cr) = MONTH(CURDATE()) AND YEAR(cr.date_cr) = YEAR(CURDATE())";
                    break;
                case 'semester':
                    $where_cr[] = "MONTH(cr.date_cr) BETWEEN 1 AND 6 AND YEAR(cr.date_cr) = YEAR(CURDATE())";
                    break;
            }
        }

        $where_clause_cr = count($where_cr) ? ('WHERE ' . implode(' AND ', $where_cr)) : '';

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) as total FROM compte_rendu cr
            JOIN rendre rn ON rn.id_cr = cr.id_cr
            JOIN enseignants ens ON ens.id_ens = rn.id_ens
            JOIN valider v ON v.id_ens = ens.id_ens
            JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
            JOIN etudiants e ON e.num_etd = r.num_etd
            $where_clause_cr";

        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params_cr);
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Requête principale
        $sql = "SELECT cr.id_cr, cr.nom_cr, cr.date_cr,
            r.id_rapport_etd, r.nom_rapport, r.date_rapport, r.statut_rapport,
            e.num_etd, e.nom_etd, e.prenom_etd,
            ens.id_ens, ens.nom_ens, ens.prenoms_ens,
            v.date_validation, v.com_validation
        FROM compte_rendu cr
        JOIN rendre rn ON rn.id_cr = cr.id_cr
        JOIN enseignants ens ON ens.id_ens = rn.id_ens
        JOIN valider v ON v.id_ens = ens.id_ens
        JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
        JOIN etudiants e ON e.num_etd = r.num_etd
        $where_clause_cr
        ORDER BY cr.date_cr DESC
        LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params_cr);
        $comptes_rendus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'comptes_rendus' => $comptes_rendus,
            'total' => $total_records,
            'pages' => ceil($total_records / $limit)
        ];
    }

    /**
     * Récupère les détails d'un rapport
     */
    public function getRapportDetails($rapport_id) {
        $sql = "SELECT e.*, r.*, v.*, d.date_depot,
                a.date_approbation, a.com_appr
            FROM rapport_etudiant r
            LEFT JOIN etudiants e ON e.num_etd = r.num_etd
            LEFT JOIN deposer d ON d.id_rapport_etd = r.id_rapport_etd
            LEFT JOIN valider v ON v.id_rapport_etd = r.id_rapport_etd
            LEFT JOIN approuver a ON a.id_rapport_etd = r.id_rapport_etd
            WHERE r.id_rapport_etd = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rapport_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les évaluateurs d'un rapport
     */
    public function getEvaluateurs($rapport_id) {
        $sql = "SELECT ens.nom_ens, ens.prenoms_ens, v.com_validation, v.decision
            FROM valider v
            JOIN enseignants ens ON ens.id_ens = v.id_ens
            WHERE v.id_rapport_etd = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rapport_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si l'utilisateur est responsable de compte rendu
     */
    public function isResponsableCompteRendu($userId) {
        $sql = "SELECT rcr.*, ens.*
            FROM responsable_compte_rendu rcr
            JOIN enseignants ens ON rcr.id_ens = ens.id_ens
            JOIN utilisateur u ON u.login_utilisateur = ens.email_ens
            WHERE u.id_utilisateur = ? AND rcr.actif = 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetch();
    }

    /**
     * Supprime des rapports
     */
    public function supprimerRapports($rapport_ids) {
        try {
            $this->pdo->beginTransaction();
            
            $placeholders = str_repeat('?,', count($rapport_ids) - 1) . '?';
            
            // Supprimer les validations
            $sql = "DELETE FROM valider WHERE id_rapport_etd IN ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($rapport_ids);
            
            // Supprimer les approbations
            $sql = "DELETE FROM approuver WHERE id_rapport_etd IN ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($rapport_ids);
            
            // Supprimer les dépôts
            $sql = "DELETE FROM deposer WHERE id_rapport_etd IN ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($rapport_ids);
            
            // Supprimer les rapports
            $sql = "DELETE FROM rapport_etudiant WHERE id_rapport_etd IN ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($rapport_ids);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Supprime des comptes rendus
     */
    public function supprimerComptesRendus($cr_ids) {
        try {
            $this->pdo->beginTransaction();
            
            $placeholders = str_repeat('?,', count($cr_ids) - 1) . '?';
            
            // Supprimer les rendus
            $sql = "DELETE FROM rendre WHERE id_cr IN ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($cr_ids);
            
            // Supprimer les comptes rendus
            $sql = "DELETE FROM compte_rendu WHERE id_cr IN ($placeholders)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($cr_ids);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
} 