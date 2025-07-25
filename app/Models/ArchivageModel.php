<?php

require_once __DIR__ . '/../../config/config.php';

class ArchivageModel {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getConnection();
    }

    /**
     * Récupère les statistiques d'archivage
     */
    public function getStatistics($user_id) {
        try {
            $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN id_rapport_etd IS NOT NULL THEN 1 ELSE 0 END) as rapports_valides,
                SUM(CASE WHEN id_cr IS NOT NULL THEN 1 ELSE 0 END) as comptes_rendus,
                SUM(CASE WHEN id_rapport_etd IS NOT NULL AND id_utilisateur = ? THEN 1 ELSE 0 END) as rapports_en_attente
                FROM archives 
                WHERE id_utilisateur = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$user_id, $user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur statistiques archivage: " . $e->getMessage());
            return [
                'rapports_valides' => 0,
                'rapports_en_attente' => 0,
                'comptes_rendus' => 0
            ];
        }
    }

    /**
     * Récupère les documents non archivés avec filtres
     */
    public function getDocumentsNonArchives($user_id, $search = '', $date_soumission = '', $date_decision = '', $type_doc = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;

        // Filtres pour les rapports
        $where_rapport = [];
        $params_rapport = [];

        // Filtres pour les comptes rendus
        $where_cr = [];
        $params_cr = [];

        // Application des filtres
        if (!empty($search)) {
            $where_rapport[] = "(re.nom_rapport LIKE ? OR e.nom_etd LIKE ? OR e.prenom_etd LIKE ?)";
            $where_cr[] = "(cr.nom_cr LIKE ? OR e.nom_etd LIKE ? OR e.prenom_etd LIKE ?)";
            $search_param = "%$search%";
            $params_rapport = array_merge($params_rapport, [$search_param, $search_param, $search_param]);
            $params_cr = array_merge($params_cr, [$search_param, $search_param, $search_param]);
        }

        // Filtres de date et type
        $this->applyDateFilters($where_rapport, $where_cr, $date_soumission, $date_decision, $type_doc);

        $where_rapport_sql = count($where_rapport) ? (' AND ' . implode(' AND ', $where_rapport)) : '';
        $where_cr_sql = count($where_cr) ? (' AND ' . implode(' AND ', $where_cr)) : '';

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) FROM (
            SELECT re.id_rapport_etd AS id_document
            FROM rapport_etudiant re
            LEFT JOIN etudiants e ON re.num_etd = e.num_etd
            LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
            LEFT JOIN valider v ON v.id_rapport_etd = re.id_rapport_etd
            LEFT JOIN archives a ON a.id_rapport_etd = re.id_rapport_etd AND a.id_utilisateur = ?
            WHERE a.id_archives IS NULL $where_rapport_sql
            UNION
            SELECT cr.id_cr AS id_document
            FROM compte_rendu cr
            JOIN rendre rn ON cr.id_cr = rn.id_cr
            JOIN enseignants ens ON ens.id_ens = rn.id_ens
            JOIN valider v ON v.id_ens = ens.id_ens
            JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
            JOIN etudiants e ON e.num_etd = r.num_etd
            LEFT JOIN archives a ON a.id_cr = cr.id_cr AND a.id_utilisateur = ?
            WHERE a.id_archives IS NULL $where_cr_sql
        ) AS docs";

        // Préparer les paramètres pour la requête de comptage
        $count_params = [$user_id, $user_id];
        $count_params = array_merge($count_params, $params_rapport, $params_cr);

        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($count_params);
        $total_docs = $count_stmt->fetchColumn();

        // Requête principale avec protection contre les valeurs NULL
        $sql = "SELECT * FROM (
            SELECT 
                re.id_rapport_etd AS id_document,
                COALESCE(re.nom_rapport, '') AS titre,
                COALESCE(CONCAT(COALESCE(e.nom_etd, ''), ' ', COALESCE(e.prenom_etd, '')), 'Étudiant inconnu') AS etudiant,
                'Rapport' AS type_document,
                d.date_depot AS date_soumission,
                v.date_validation AS date_decision
            FROM rapport_etudiant re
            LEFT JOIN etudiants e ON re.num_etd = e.num_etd
            LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
            LEFT JOIN valider v ON v.id_rapport_etd = re.id_rapport_etd
            LEFT JOIN archives a ON a.id_rapport_etd = re.id_rapport_etd AND a.id_utilisateur = ?
            WHERE a.id_archives IS NULL $where_rapport_sql
            UNION
            SELECT 
                cr.id_cr AS id_document,
                COALESCE(cr.nom_cr, '') AS titre,
                COALESCE(CONCAT(COALESCE(e.nom_etd, ''), ' ', COALESCE(e.prenom_etd, '')), 'Étudiant inconnu') AS etudiant,
                'Compte rendu' AS type_document,
                cr.date_cr AS date_soumission,
                NULL AS date_decision
            FROM compte_rendu cr
            JOIN rendre rn ON cr.id_cr = rn.id_cr
            JOIN enseignants ens ON ens.id_ens = rn.id_ens
            JOIN valider v ON v.id_ens = ens.id_ens
            JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
            JOIN etudiants e ON e.num_etd = r.num_etd
            LEFT JOIN archives a ON a.id_cr = cr.id_cr AND a.id_utilisateur = ?
            WHERE a.id_archives IS NULL $where_cr_sql
        ) AS docs
        ORDER BY id_document DESC
        LIMIT $limit OFFSET $offset";

        // Préparer les paramètres pour la requête principale
        $main_params = [$user_id, $user_id];
        $main_params = array_merge($main_params, $params_rapport, $params_cr);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($main_params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'documents' => $documents,
            'total' => $total_docs,
            'pages' => ceil($total_docs / $limit)
        ];
    }

    /**
     * Archive un document
     */
    public function archiverDocument($id, $type, $user_id) {
        try {
            $this->pdo->beginTransaction();

            // Récupérer l'année académique en cours
            $stmt = $this->pdo->query("SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1");
            $annee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$annee) {
                throw new Exception("Aucune année académique en cours trouvée.");
            }

            // Récupérer le chemin du fichier
            if ($type === 'Rapport') {
                $stmt = $this->pdo->prepare("SELECT fichier_rapport FROM rapport_etudiant WHERE id_rapport_etd = ?");
                $stmt->execute([$id]);
                $fichier = $stmt->fetchColumn();
            } else {
                $stmt = $this->pdo->prepare("SELECT fichier_cr FROM compte_rendu WHERE id_cr = ?");
                $stmt->execute([$id]);
                $fichier = $stmt->fetchColumn();
            }

            if (!$fichier) {
                throw new Exception("Fichier non trouvé pour ce document.");
            }

            // Vérifier si déjà archivé
            if ($type === 'Rapport') {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM archives WHERE id_rapport_etd = ? AND id_utilisateur = ?");
            } else {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM archives WHERE id_cr = ? AND id_utilisateur = ?");
            }
            $stmt->execute([$id, $user_id]);

            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Ce document est déjà archivé par vous.");
            }

            // Insérer l'archive
            $date = date('Y-m-d H:i:s');
            if ($type === 'Rapport') {
                $stmt = $this->pdo->prepare("INSERT INTO archives (id_rapport_etd, date_archivage, id_utilisateur, id_ac, fichier_archive) VALUES (?, ?, ?, ?, ?)");
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO archives (id_cr, date_archivage, id_utilisateur, id_ac, fichier_archive) VALUES (?, ?, ?, ?, ?)");
            }
            
            $stmt->execute([$id, $date, $user_id, $annee['id_ac'], $fichier]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Archive plusieurs documents
     */
    public function archiverDocuments($doc_ids, $user_id) {
        try {
            $this->pdo->beginTransaction();
            
            foreach ($doc_ids as $doc_id) {
                list($type, $id) = explode(':', $doc_id);
                $this->archiverDocument($id, $type, $user_id);
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Détermine le type de document basé sur l'ID et les tables
     */
    public function getTypeDocument($id_document, $context = null) {
        try {
            // Vérifier d'abord si c'est un rapport
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rapport_etudiant WHERE id_rapport_etd = ?");
            $stmt->execute([$id_document]);
            
            if ($stmt->fetchColumn() > 0) {
                return 'Rapport';
            }
            
            // Vérifier si c'est un compte rendu
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM compte_rendu WHERE id_cr = ?");
            $stmt->execute([$id_document]);
            
            if ($stmt->fetchColumn() > 0) {
                return 'Compte rendu';
            }
            
            // Si aucun n'est trouvé, retourner un type par défaut
            return 'Document';
            
        } catch (Exception $e) {
            error_log("Erreur lors de la détermination du type de document: " . $e->getMessage());
            return 'Document';
        }
    }

    /**
     * Applique les filtres de date
     */
    private function applyDateFilters(&$where_rapport, &$where_cr, $date_soumission, $date_decision, $type_doc) {
        if (!empty($date_soumission)) {
            switch ($date_soumission) {
                case 'today':
                    $where_rapport[] = "d.date_depot = CURDATE()";
                    $where_cr[] = "cr.date_cr = CURDATE()";
                    break;
                case 'week':
                    $where_rapport[] = "YEARWEEK(d.date_depot, 1) = YEARWEEK(CURDATE(), 1)";
                    $where_cr[] = "YEARWEEK(cr.date_cr, 1) = YEARWEEK(CURDATE(), 1)";
                    break;
                case 'month':
                    $where_rapport[] = "MONTH(d.date_depot) = MONTH(CURDATE()) AND YEAR(d.date_depot) = YEAR(CURDATE())";
                    $where_cr[] = "MONTH(cr.date_cr) = MONTH(CURDATE()) AND YEAR(cr.date_cr) = YEAR(CURDATE())";
                    break;
                case 'semester':
                    $where_rapport[] = "((MONTH(d.date_depot) BETWEEN 1 AND 6 AND MONTH(CURDATE()) BETWEEN 1 AND 6) OR (MONTH(d.date_depot) BETWEEN 7 AND 12 AND MONTH(CURDATE()) BETWEEN 7 AND 12))";
                    $where_cr[] = "((MONTH(cr.date_cr) BETWEEN 1 AND 6 AND MONTH(CURDATE()) BETWEEN 1 AND 6) OR (MONTH(cr.date_cr) BETWEEN 7 AND 12 AND MONTH(CURDATE()) BETWEEN 7 AND 12))";
                    break;
            }
        }

        if (!empty($date_decision)) {
            switch ($date_decision) {
                case 'last_week':
                    $where_rapport[] = "YEARWEEK(v.date_validation, 1) = YEARWEEK(CURDATE(), 1) - 1";
                    break;
                case 'last_month':
                    $where_rapport[] = "MONTH(v.date_validation) = MONTH(CURDATE()) - 1 AND YEAR(v.date_validation) = YEAR(CURDATE())";
                    break;
                case 'last_semester':
                    $where_rapport[] = "((MONTH(v.date_validation) BETWEEN 1 AND 6 AND MONTH(CURDATE()) BETWEEN 7 AND 12 AND YEAR(v.date_validation) = YEAR(CURDATE())) OR (MONTH(v.date_validation) BETWEEN 7 AND 12 AND MONTH(CURDATE()) BETWEEN 1 AND 6 AND YEAR(v.date_validation) = YEAR(CURDATE()) - 1))";
                    break;
            }
        }

        if ($type_doc === 'rapport') {
            $where_cr[] = "1=0"; // Exclure les comptes rendus
        } elseif ($type_doc === 'compte_rendu') {
            $where_rapport[] = "1=0"; // Exclure les rapports
        }
    }
} 