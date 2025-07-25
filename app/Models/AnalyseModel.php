<?php

require_once __DIR__ . '/../../config/config.php';

class AnalyseModel {
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les statistiques des rapports
     */
    public function getRapportStats() {
        $stats = [];
        
        // Total des rapports
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM rapport_etudiant");
        $stats['total_rapports'] = $stmt->fetch()['total'];

        // Rapports évalués
        $stmt = $this->pdo->query("SELECT COUNT(*) as evalues FROM rapport_etudiant WHERE statut_rapport = 'Validé'");
        $stats['rapports_evalues'] = $stmt->fetch()['evalues'];

        // Rapports en attente
        $stmt = $this->pdo->query("SELECT COUNT(*) as attente FROM rapport_etudiant WHERE statut_rapport = 'En attente de validation'");
        $stats['rapports_attente'] = $stmt->fetch()['attente'];

        return $stats;
    }

    /**
     * Récupère les rapports en attente de validation avec filtres
     */
    public function getRapportsEnAttente($search = '', $date_filter = '', $page = 1, $limit = 10) {
        $where_conditions = ["re.statut_rapport = 'En attente de validation'"];
        $params = [];

        // Filtre de recherche
        if (!empty($search)) {
            $where_conditions[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR re.nom_rapport LIKE ? OR re.theme_memoire LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }

        // Filtre par date
        if (!empty($date_filter)) {
            switch ($date_filter) {
                case 'today':
                    $where_conditions[] = "DATE(a.date_approbation) = CURDATE()";
                    break;
                case 'week':
                    $where_conditions[] = "YEARWEEK(a.date_approbation) = YEARWEEK(CURDATE())";
                    break;
                case 'month':
                    $where_conditions[] = "MONTH(a.date_approbation) = MONTH(CURDATE()) AND YEAR(a.date_approbation) = YEAR(CURDATE())";
                    break;
                case 'year':
                    $where_conditions[] = "YEAR(a.date_approbation) = YEAR(CURDATE())";
                    break;
            }
        }

        $where_clause = implode(" AND ", $where_conditions);
        $offset = ($page - 1) * $limit;

        // Requête pour le total
        $count_sql = "SELECT COUNT(*) as total FROM approuver a
                     LEFT JOIN rapport_etudiant re ON re.id_rapport_etd = a.id_rapport_etd
                     LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                     WHERE $where_clause";
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Requête principale
        $sql = "SELECT * FROM approuver a
                LEFT JOIN rapport_etudiant re ON re.id_rapport_etd = a.id_rapport_etd
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                WHERE $where_clause
                ORDER BY a.date_approbation DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rapports' => $rapports,
            'total' => $total_records,
            'pages' => ceil($total_records / $limit)
        ];
    }

    /**
     * Supprime plusieurs rapports
     */
    public function supprimerRapports($rapport_ids) {
        try {
            $this->pdo->beginTransaction();
            
            // Supprimer les validations
            $stmt = $this->pdo->prepare("DELETE FROM valider WHERE id_rapport_etd IN (" . str_repeat('?,', count($rapport_ids) - 1) . "?)");
            $stmt->execute($rapport_ids);
            
            // Supprimer les approbations
            $stmt = $this->pdo->prepare("DELETE FROM approuver WHERE id_rapport_etd IN (" . str_repeat('?,', count($rapport_ids) - 1) . "?)");
            $stmt->execute($rapport_ids);
            
            // Supprimer les dépôts
            $stmt = $this->pdo->prepare("DELETE FROM deposer WHERE id_rapport_etd IN (" . str_repeat('?,', count($rapport_ids) - 1) . "?)");
            $stmt->execute($rapport_ids);
            
            // Supprimer les rapports
            $stmt = $this->pdo->prepare("DELETE FROM rapport_etudiant WHERE id_rapport_etd IN (" . str_repeat('?,', count($rapport_ids) - 1) . "?)");
            $stmt->execute($rapport_ids);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
} 