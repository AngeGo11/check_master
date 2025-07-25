<?php

require_once __DIR__ . '/../../config/config.php';

class ArchiveModel {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getConnection();
    }

    /**
     * Récupère les statistiques des archives
     */
    public function getStatistics($user_id) {
        $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN id_rapport_etd IS NOT NULL THEN 1 ELSE 0 END) as rapports,
            SUM(CASE WHEN id_cr IS NOT NULL THEN 1 ELSE 0 END) as comptes_rendus
        FROM archives 
        WHERE id_utilisateur = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les documents archivés avec filtres
     */
    public function getArchives($user_id, $search = '', $date = '', $type = '') {
        // Filtres pour les rapports
        $where_rapport = ["a.id_utilisateur = ?", "a.id_rapport_etd IS NOT NULL"];
        $params_rapport = [$user_id];
        
        // Filtres pour les comptes rendus
        $where_cr = ["a.id_utilisateur = ?", "a.id_cr IS NOT NULL"];
        $params_cr = [$user_id];

        // Filtre de recherche
        if (!empty($search)) {
            $where_rapport[] = "(re.nom_rapport LIKE ? OR e.nom_etd LIKE ? OR e.prenom_etd LIKE ?)";
            $where_cr[] = "(cr.nom_cr LIKE ? OR et.nom_etd LIKE ? OR et.prenom_etd LIKE ?)";
            $search_param = "%$search%";
            $params_rapport = array_merge($params_rapport, [$search_param, $search_param, $search_param]);
            $params_cr = array_merge($params_cr, [$search_param, $search_param, $search_param]);
        }

        // Filtre par date
        if (!empty($date)) {
            switch ($date) {
                case 'last_week':
                    $where_rapport[] = "a.date_archivage >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                    $where_cr[] = "a.date_archivage >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
                    break;
                case 'last_month':
                    $where_rapport[] = "a.date_archivage >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                    $where_cr[] = "a.date_archivage >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
                    break;
                case 'last_semester':
                    $where_rapport[] = "a.date_archivage >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
                    $where_cr[] = "a.date_archivage >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
                    break;
            }
        }

        // Filtre par type
        if (!empty($type)) {
            if ($type === 'rapport') {
                $where_cr[] = '1=0'; // Exclure les comptes rendus
            } elseif ($type === 'compte_rendu') {
                $where_rapport[] = '1=0'; // Exclure les rapports
            }
        }

        $where_rapport_sql = $where_rapport ? ('WHERE ' . implode(' AND ', $where_rapport)) : '';
        $where_cr_sql = $where_cr ? ('WHERE ' . implode(' AND ', $where_cr)) : '';

        // Requête principale
        $sql = "SELECT 
            a.id_rapport_etd AS id_document,
            re.nom_rapport AS titre,
            CONCAT(e.nom_etd, ' ', e.prenom_etd) AS etudiant,
            'Rapport' AS type_document,
            a.date_archivage,
            a.fichier_archive
        FROM archives a
        LEFT JOIN rapport_etudiant re ON a.id_rapport_etd = re.id_rapport_etd
        LEFT JOIN etudiants e ON re.num_etd = e.num_etd
        $where_rapport_sql

        UNION

        SELECT 
            a.id_cr AS id_document,
            cr.nom_cr AS titre,
            CONCAT(et.nom_etd, ' ', et.prenom_etd) AS etudiant,
            'Compte rendu' AS type_document,
            a.date_archivage,
            a.fichier_archive
        FROM archives a
        LEFT JOIN compte_rendu cr ON a.id_cr = cr.id_cr
        LEFT JOIN rendre rn ON cr.id_cr = rn.id_cr
        LEFT JOIN rapport_etudiant re ON re.id_rapport_etd = (
            SELECT v.id_rapport_etd 
            FROM valider v 
            WHERE v.id_ens = rn.id_ens 
            LIMIT 1
        )
        LEFT JOIN etudiants et ON re.num_etd = et.num_etd
        $where_cr_sql

        ORDER BY date_archivage DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($params_rapport, $params_cr));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les détails d'un document archivé
     */
    public function getDocumentDetails($id, $type) {
        if ($type === 'Rapport') {
            $sql = "SELECT 
                re.id_rapport_etd,
                re.nom_rapport,
                re.theme_memoire,
                CONCAT(e.nom_etd, ' ', e.prenom_etd) AS etudiant,
                d.date_depot AS date_creation,
                v.date_validation AS date_decision,
                GROUP_CONCAT(
                    CONCAT(ens.nom_ens, ' ', ens.prenoms_ens, ' - ', v.decision, 
                    CASE WHEN v.com_validation IS NOT NULL THEN CONCAT(' (', v.com_validation, ')') ELSE '' END)
                    SEPARATOR '||'
                ) AS validations
            FROM rapport_etudiant re
            LEFT JOIN etudiants e ON re.num_etd = e.num_etd
            LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
            LEFT JOIN valider v ON v.id_rapport_etd = re.id_rapport_etd
            LEFT JOIN enseignants ens ON v.id_ens = ens.id_ens
            WHERE re.id_rapport_etd = ?
            GROUP BY re.id_rapport_etd";
        } else {
            $sql = "SELECT 
                cr.id_cr,
                cr.nom_cr,
                CONCAT(e.nom_etd, ' ', e.prenom_etd) AS etudiant,
                CONCAT(ens.nom_ens, ' ', ens.prenoms_ens) AS redacteur,
                cr.date_cr AS date_creation,
                re.nom_rapport AS rapport_associe
            FROM compte_rendu cr
            JOIN rendre rn ON cr.id_cr = rn.id_cr
            JOIN enseignants ens ON ens.id_ens = rn.id_ens
            JOIN valider v ON v.id_ens = ens.id_ens
            JOIN rapport_etudiant re ON re.id_rapport_etd = v.id_rapport_etd
            JOIN etudiants e ON e.num_etd = re.num_etd
            WHERE cr.id_cr = ?";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Supprime des archives
     */
    public function supprimerArchives($archive_ids, $user_id) {
        try {
            $this->pdo->beginTransaction();
            
            $placeholders = str_repeat('?,', count($archive_ids) - 1) . '?';
            $sql = "DELETE FROM archives WHERE id_archives IN ($placeholders) AND id_utilisateur = ?";
            
            $params = array_merge($archive_ids, [$user_id]);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
} 