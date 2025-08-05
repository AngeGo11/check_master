<?php

require_once __DIR__ . '/../../config/config.php';

class ConsultationModel
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = DataBase::getConnection();
    }

    /**
     * Récupère les statistiques des comptes rendus
     */
    public function getCompteRenduStats()
    {
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
     * Récupère les rapports qui ont au moins une évaluation
     */
    public function getRapportsValides()
    {
        $sql = "SELECT DISTINCT re.*, e.nom_etd, e.prenom_etd, 
                d.date_depot, a.date_approbation, a.com_appr,
                COUNT(v.id_ens) as nb_evaluations,
                COUNT(CASE WHEN v.decision = 'Validé' THEN 1 END) as nb_validations,
                COUNT(CASE WHEN v.decision = 'Rejeté' THEN 1 END) as nb_rejets,
                MAX(v.date_validation) as derniere_evaluation
                FROM rapport_etudiant re
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN valider v ON v.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN approuver a ON a.id_rapport_etd = re.id_rapport_etd
                WHERE v.id_ens IS NOT NULL
                GROUP BY re.id_rapport_etd, re.num_etd, re.nom_rapport, re.theme_memoire, re.date_rapport, re.statut_rapport, re.fichier_rapport, e.nom_etd, e.prenom_etd, d.date_depot, a.date_approbation, a.com_appr
                HAVING nb_evaluations > 0
                ORDER BY derniere_evaluation DESC, re.date_rapport DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les comptes rendus avec filtres et pagination
     */
    public function getComptesRendus($search = '', $date_filter = '', $page = 1, $limit = 10)
    {
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
    public function getRapportDetails($rapport_id)
    {
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
    public function getEvaluateurs($rapport_id)
    {
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
    public function isResponsableCompteRendu($userId)
    {
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
    public function supprimerRapports($rapport_ids)
    {
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
    public function supprimerComptesRendus($cr_ids)
    {
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

    /**
     * Récupère les membres de la commission et leurs évaluations pour un rapport donné
     */
    public function getCommissionMembers($rapport_id)
    {
        // Récupérer tous les enseignants qui peuvent être membres de commission
        $sql = "SELECT e.id_ens, e.nom_ens, e.prenoms_ens, e.email_ens
                FROM enseignants e
                JOIN utilisateur u ON u.login_utilisateur = e.email_ens
                JOIN posseder p ON p.id_util = u.id_utilisateur
                WHERE p.id_gu = 9 OR p.id_gu = 8";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $all_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les évaluations existantes pour ce rapport
        $sql_evaluations = "SELECT v.id_ens, v.com_validation, v.decision, v.date_validation,
                           ens.nom_ens, ens.prenoms_ens, ens.email_ens
                           FROM valider v
                           JOIN enseignants ens ON ens.id_ens = v.id_ens
                           WHERE v.id_rapport_etd = ?";

        $stmt_evaluations = $this->pdo->prepare($sql_evaluations);
        $stmt_evaluations->execute([$rapport_id]);
        $evaluations = $stmt_evaluations->fetchAll(PDO::FETCH_ASSOC);

        // Créer un tableau indexé par id_ens pour les évaluations
        $evaluations_by_ens = [];
        foreach ($evaluations as $eval) {
            $evaluations_by_ens[$eval['id_ens']] = $eval;
        }

        // Combiner les membres avec leurs évaluations
        $commission_members = [];
        foreach ($all_members as $member) {
            $member_data = [
                'id_ens' => $member['id_ens'],
                'nom_ens' => $member['nom_ens'],
                'prenoms_ens' => $member['prenoms_ens'],
                'email_ens' => $member['email_ens'] ?? '',
                'a_evalue' => false,
                'com_validation' => null,
                'decision' => null,
                'date_validation' => null
            ];

            // Vérifier si ce membre a évalué le rapport
            if (isset($evaluations_by_ens[$member['id_ens']])) {
                $eval = $evaluations_by_ens[$member['id_ens']];
                $member_data['a_evalue'] = true;
                $member_data['com_validation'] = $eval['com_validation'];
                $member_data['decision'] = $eval['decision'];
                $member_data['date_validation'] = $eval['date_validation'];
            }

            $commission_members[] = $member_data;
        }

        return $commission_members;
    }

    /**
     * Récupère les statistiques d'évaluation pour un rapport
     */
    public function getEvaluationStats($rapport_id)
    {
        $sql = "SELECT 
                COUNT(*) as total_evaluations,
                COUNT(CASE WHEN decision = 'Validé' THEN 1 END) as validations,
                COUNT(CASE WHEN decision = 'Rejeté' THEN 1 END) as rejets,
                COUNT(CASE WHEN decision IS NULL OR decision = '' THEN 1 END) as en_attente
                FROM valider 
                WHERE id_rapport_etd = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rapport_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les détails des évaluations pour un rapport
     */
    public function getEvaluationDetails($rapport_id)
    {
        $sql = "SELECT v.*, e.nom_ens, e.prenoms_ens, e.email_ens
                FROM valider v
                JOIN enseignants e ON e.id_ens = v.id_ens
                WHERE v.id_rapport_etd = ?
                ORDER BY v.date_validation DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rapport_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les détails d'un compte rendu par titre
     */
    public function getCompteRenduDetailsByTitre($titre)
    {
        $sql = "SELECT cr.id_cr, cr.nom_cr, cr.date_cr,
            r.id_rapport_etd, r.nom_rapport, r.date_rapport, r.statut_rapport,
            e.num_etd, e.nom_etd, e.prenom_etd,
            ens.id_ens, ens.nom_ens, ens.prenoms_ens
        FROM compte_rendu cr
        JOIN rendre rn ON rn.id_cr = cr.id_cr
        JOIN enseignants ens ON ens.id_ens = rn.id_ens
        JOIN valider v ON v.id_ens = ens.id_ens
        JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
        JOIN etudiants e ON e.num_etd = r.num_etd
        WHERE cr.nom_cr = ?
        ORDER BY cr.date_cr DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$titre]);
        $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Grouper les données comme dans la vue
        $details = [
            'titre' => $titre,
            'nombre_total' => count($rapports),
            'date_creation' => !empty($rapports) ? $rapports[0]['date_cr'] : 'now',
            'auteur' => !empty($rapports) ? $rapports[0]['nom_ens'] . ' ' . $rapports[0]['prenoms_ens'] : 'Utilisateur',
            'rapports' => $rapports
        ];

        return $details;
    }

    /**
     * Supprime un groupe de comptes rendus par titre
     */
    public function deleteCompteRenduGroup($titre)
    {
        try {
            $this->pdo->beginTransaction();

            // Récupérer tous les IDs des comptes rendus avec ce titre
            $sql = "SELECT id_cr FROM compte_rendu WHERE nom_cr = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$titre]);
            $cr_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($cr_ids)) {
                throw new Exception('Aucun compte rendu trouvé avec ce titre');
            }

            // Supprimer les relations rendre
            $placeholders = str_repeat('?,', count($cr_ids) - 1) . '?';
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

    /**
     * Supprime un compte rendu individuel
     */
    public function deleteCompteRendu($id)
    {
        try {
            $this->pdo->beginTransaction();

            // Supprimer les relations rendre
            $sql = "DELETE FROM rendre WHERE id_cr = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            // Supprimer le compte rendu
            $sql = "DELETE FROM compte_rendu WHERE id_cr = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Récupère un compte rendu par son ID
     */
    public function getCompteRenduById($id)
    {
        $sql = "SELECT cr.*, r.nom_rapport, r.theme_memoire, r.statut_rapport,
                e.nom_etd, e.prenom_etd, e.num_etd,
                ens.nom_ens, ens.prenoms_ens
                FROM compte_rendu cr
                JOIN rendre rn ON rn.id_cr = cr.id_cr
                JOIN enseignants ens ON ens.id_ens = rn.id_ens
                JOIN valider v ON v.id_ens = ens.id_ens
                JOIN rapport_etudiant r ON r.id_rapport_etd = v.id_rapport_etd
                JOIN etudiants e ON e.num_etd = r.num_etd
                WHERE cr.id_cr = ?
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère un rapport par son ID
     */
    public function getRapportById($id)
    {
        $sql = "SELECT r.*, e.nom_etd, e.prenom_etd 
                FROM rapport_etudiant r 
                JOIN etudiants e ON r.num_etd = e.num_etd 
                WHERE r.id_rapport_etd = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Enregistre l'envoi d'un email dans la base de données
     */
    public function logEmailSent($cr_id, $email, $message_erreur)
    {
        try {
            
            // Insérer le log
            $sql = "INSERT INTO historique_envoi (id_cr, email_destinataire, date_envoi, statut, message_erreur) VALUES (?, ?, NOW(), 'succès', ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cr_id, $email, $message_erreur]);
            
            return true;
        } catch (Exception $e) {
            // En cas d'erreur, on log mais on ne fait pas échouer l'envoi d'email
            error_log("Erreur lors de l'enregistrement du log email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère l'historique des emails envoyés
     */
    public function getEmailHistory()
    {
        try {
            // Vérifier si la table existe
            $checkTableSql = "SHOW TABLES LIKE 'historique_envoi'";
            $stmt = $this->pdo->prepare($checkTableSql);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                // La table n'existe pas encore
                return [];
            }

            $sql = "SELECT *
                    FROM historique_envoi 
                    ORDER BY date_envoi DESC
                    LIMIT 100";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération de l'historique des emails: " . $e->getMessage());
            return [];
        }
    }
}
