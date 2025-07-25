<?php


namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Audit
{


    private $db;
    public function __construct($db)
    {        
        $this->db = $db;
    }



    /**
     * Enregistre une action dans la piste d'audit avec action spécifique
     * 
     * @param PDO $pdo Instance de connexion à la base de données
     * @param int $id_utilisateur ID de l'utilisateur qui effectue l'action
     * @param string $nom_traitement Nom du traitement (lib_traitement dans la table traitement)
     * @param string $lib_action Libellé de l'action (lib_action dans la table action)
     * @param int $acceder Indicateur d'accès (1 = accès autorisé, 0 = accès refusé)
     * @return bool True si l'enregistrement réussit, False sinon
     */


   


   public function enregistrer_piste_audit($pdo, $id_utilisateur, $nom_traitement, $lib_action, $acceder = 1)
    {
        try {
            // Validation des paramètres d'entrée
            if (empty($id_utilisateur) || !is_numeric($id_utilisateur)) {
                error_log("Piste d'audit: ID utilisateur invalide - " . $id_utilisateur);
                return false;
            }

            if (empty($nom_traitement) || !is_string($nom_traitement)) {
                error_log("Piste d'audit: Nom de traitement invalide - " . $nom_traitement);
                return false;
            }

            if (empty($lib_action) || !is_string($lib_action)) {
                error_log("Piste d'audit: Libellé action invalide - " . $lib_action);
                return false;
            }

            if (!in_array($acceder, [0, 1])) {
                error_log("Piste d'audit: Valeur d'accès invalide - " . $acceder);
                return false;
            }

            // Vérifier si l'utilisateur existe
            $sql_user = "SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = ? LIMIT 1";
            $stmt_user = $pdo->prepare($sql_user);
            $stmt_user->execute([$id_utilisateur]);

            if (!$stmt_user->fetch()) {
                error_log("Piste d'audit: Utilisateur inexistant - ID: " . $id_utilisateur);
                return false;
            }

            // Récupérer l'id_traitement à partir du nom du traitement
            $sql_traitement = "SELECT id_traitement FROM traitement WHERE lib_traitement = ? LIMIT 1";
            $stmt_traitement = $pdo->prepare($sql_traitement);
            $stmt_traitement->execute([$nom_traitement]);
            $traitement = $stmt_traitement->fetch(PDO::FETCH_ASSOC);

            if (!$traitement) {
                error_log("Piste d'audit: Traitement inexistant - " . $nom_traitement);
                return false;
            }

            // Récupérer l'id_action à partir du libellé de l'action
            $sql_action = "SELECT id_action FROM action WHERE lib_action = ? LIMIT 1";
            $stmt_action = $pdo->prepare($sql_action);
            $stmt_action->execute([$lib_action]);
            $action = $stmt_action->fetch(PDO::FETCH_ASSOC);

            if (!$action) {
                error_log("Piste d'audit: Action inexistante - " . $lib_action);
                return false;
            }

            $id_traitement = $traitement['id_traitement'];
            $id_action = $action['id_action'];

            // Préparer les données temporelles
            $date_piste = date('Y-m-d');
            $heure_piste = date('H:i:s');

            // Toujours insérer un nouvel enregistrement
            $sql_insert = "INSERT INTO pister (id_utilisateur, id_traitement, id_action, date_piste, heure_piste, acceder)
                       VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $result = $stmt_insert->execute([$id_utilisateur, $id_traitement, $id_action, $date_piste, $heure_piste, $acceder]);

            if ($result) {
                return true;
            } else {
                error_log("Piste d'audit: Échec de l'enregistrement en base de données");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO dans piste d'audit: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Erreur générale dans piste d'audit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Version alternative qui accepte directement les IDs
     * Plus efficace si vous avez déjà les IDs
     * 
     * @param PDO $pdo Instance de connexion à la base de données
     * @param int $id_utilisateur ID de l'utilisateur
     * @param int $id_traitement ID du traitement
     * @param int $id_action ID de l'action
     * @param int $acceder Indicateur d'accès
     * @return bool
     */
    function enregistrer_piste_audit_par_id($pdo, $id_utilisateur, $id_traitement, $id_action, $acceder = 1)
    {
        try {
            // Validation des paramètres
            if (!is_numeric($id_utilisateur) || !is_numeric($id_traitement) || !is_numeric($id_action)) {
                error_log("Piste d'audit: Paramètres invalides");
                return false;
            }

            $date_piste = date('Y-m-d');
            $heure_piste = date('H:i:s');

            // Vérifier si un enregistrement existe déjà
            $sql_check = "SELECT COUNT(*) as count FROM pister 
                      WHERE id_utilisateur = ? AND id_traitement = ?";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$id_utilisateur, $id_traitement]);
            $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

            if ($existing['count'] > 0) {
                // Mettre à jour
                $sql_update = "UPDATE pister 
                          SET id_action = ?, date_piste = ?, heure_piste = ?, acceder = ? 
                          WHERE id_utilisateur = ? AND id_traitement = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $result = $stmt_update->execute([$id_action, $date_piste, $heure_piste, $acceder, $id_utilisateur, $id_traitement]);
            } else {
                // Insérer
                $sql_insert = "INSERT INTO pister (id_utilisateur, id_traitement, id_action, date_piste, heure_piste, acceder)
                          VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert = $pdo->prepare($sql_insert);
                $result = $stmt_insert->execute([$id_utilisateur, $id_traitement, $id_action, $date_piste, $heure_piste, $acceder]);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Erreur piste d'audit par ID: " . $e->getMessage());
            return false;
        }
    }



    /**
     * Fonctions spécialisées pour des actions courantes
     */

    // Accès à un module/page
    function enregistrer_acces_module($pdo, $id_utilisateur, $nom_traitement)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, $nom_traitement, 'Accès dashboard', 1);
    }

    // Connexion utilisateur
    function enregistrer_connexion($pdo, $id_utilisateur, $succes = true)
    {
        $action = $succes ? 'Connexion réussie' : 'Tentative connexion échouée';
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'dashboard', $action, $succes ? 1 : 0);
    }

    // Déconnexion utilisateur
    function enregistrer_deconnexion($pdo, $id_utilisateur)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'dashboard', 'Déconnexion', 1);
    }

    // Ajout d'un étudiant
    function enregistrer_ajout_etudiant($pdo, $id_utilisateur)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'etudiants', 'Ajout étudiant', 1);
    }

    // Modification d'un étudiant
    function enregistrer_modification_etudiant($pdo, $id_utilisateur)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'etudiants', 'Modification étudiant', 1);
    }

    // Ajout d'une UE
    function enregistrer_ajout_ue($pdo, $id_utilisateur)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'parametres_generaux', 'Ajout UE', 1);
    }

    // Modification d'une UE
    function enregistrer_modification_ue($pdo, $id_utilisateur)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'parametres_generaux', 'Modification UE', 1);
    }

    // Validation d'un rapport
    function enregistrer_validation_rapport($pdo, $id_utilisateur)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'validations', 'Validation rapport par enseignant', 1);
    }

    // Dépôt d'un rapport
    function enregistrer_depot_rapport($pdo, $id_utilisateur)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'rapports', 'Dépôt rapport étudiant', 1);
    }

    // Envoi d'un message
    function enregistrer_envoi_message($pdo, $id_utilisateur)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'messages', 'Envoi message', 1);
    }

    // Saisie d'évaluation
    function enregistrer_saisie_evaluation($pdo, $id_utilisateur)
    {
        return $this->enregistrer_piste_audit($pdo, $id_utilisateur, 'evaluations_etudiants', 'Saisie évaluation UE', 1);
    }

    /**
     * Récupérer l'historique de piste d'audit avec jointures
     * 
     * @param PDO $pdo Instance de connexion
     * @param array $filtres Filtres optionnels
     * @return array Historique des actions
     */
    function obtenir_historique_piste_audit($pdo, $filtres = [])
    {
        try {
            $where_conditions = [];
            $params = [];

            // Construction des filtres
            if (!empty($filtres['utilisateur'])) {
                $where_conditions[] = "p.id_utilisateur = ?";
                $params[] = $filtres['utilisateur'];
            }

            if (!empty($filtres['traitement'])) {
                $where_conditions[] = "t.lib_traitement = ?";
                $params[] = $filtres['traitement'];
            }

            if (!empty($filtres['action'])) {
                $where_conditions[] = "a.lib_action = ?";
                $params[] = $filtres['action'];
            }

            if (!empty($filtres['date_debut'])) {
                $where_conditions[] = "p.date_piste >= ?";
                $params[] = $filtres['date_debut'];
            }

            if (!empty($filtres['date_fin'])) {
                $where_conditions[] = "p.date_piste <= ?";
                $params[] = $filtres['date_fin'];
            }

            $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);

            $sql = "SELECT 
                    p.id_utilisateur,
                    p.date_piste,
                    p.heure_piste,
                    p.acceder,
                    t.lib_traitement,
                    t.nom_traitement,
                    a.lib_action,
                    COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, u.login_utilisateur, 'Utilisateur inconnu') as nom_utilisateur,
                    COALESCE(e.prenoms_ens, pa.prenoms_personnel_adm, et.prenom_etd, '') as prenoms_utilisateur,
                    CASE 
                        WHEN e.email_ens IS NOT NULL THEN 'Enseignant'
                        WHEN pa.email_personnel_adm IS NOT NULL THEN 'Personnel administratif'
                        WHEN et.email_etd IS NOT NULL THEN 'Étudiant'
                        ELSE 'Inconnu'
                    END as type_utilisateur
                FROM pister p
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                LEFT JOIN action a ON p.id_action = a.id_action
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                $where_clause
                ORDER BY p.date_piste DESC, p.heure_piste DESC
                LIMIT " . ($filtres['limit'] ?? 100);

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur récupération historique piste audit: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fonction pour nettoyer les anciens enregistrements de piste d'audit
     */
    function nettoyer_piste_audit($pdo, $jours_conservation = 90)
    {
        try {
            $date_limite = date('Y-m-d', strtotime("-$jours_conservation days"));

            $sql = "DELETE FROM pister WHERE date_piste < ?";
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$date_limite]);

            $nb_supprimes = $stmt->rowCount();
            error_log("Nettoyage piste d'audit: $nb_supprimes enregistrements supprimés (antérieurs à $date_limite)");

            return $result;
        } catch (Exception $e) {
            error_log("Erreur lors du nettoyage de la piste d'audit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir les statistiques de la piste d'audit
     */
    function obtenir_statistiques_audit($pdo, $periode_jours = 30)
    {
        try {
            $date_debut = date('Y-m-d', strtotime("-$periode_jours days"));

            $sql = "SELECT 
                    COUNT(*) as total_actions,
                    COUNT(DISTINCT p.id_utilisateur) as utilisateurs_actifs,
                    COUNT(DISTINCT p.id_traitement) as modules_utilises,
                    SUM(CASE WHEN p.acceder = 1 THEN 1 ELSE 0 END) as acces_reussis,
                    SUM(CASE WHEN p.acceder = 0 THEN 1 ELSE 0 END) as acces_refuses,
                    a.lib_action as action_plus_frequente,
                    COUNT(a.lib_action) as nb_action_frequente
                FROM pister p
                LEFT JOIN action a ON p.id_action = a.id_action
                WHERE p.date_piste >= ?
                GROUP BY a.lib_action
                ORDER BY nb_action_frequente DESC
                LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$date_debut]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur statistiques audit: " . $e->getMessage());
            return null;
        }
    }

    // Nouvelles méthodes pour le contrôleur PisteAudit
    public function getAllAuditRecords() {
        $sql = "SELECT p.*, t.lib_traitement, t.nom_traitement, a.lib_action,
                       COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, u.login_utilisateur, 'Inconnu') as nom_utilisateur,
                       COALESCE(e.prenoms_ens, pa.prenoms_personnel_adm, et.prenom_etd, '') as prenoms_utilisateur,
                       CASE 
                           WHEN e.id_ens IS NOT NULL THEN 'Enseignant'
                           WHEN pa.id_personnel_adm IS NOT NULL THEN 'Personnel administratif'
                           WHEN et.num_etd IS NOT NULL THEN 'Étudiant'
                           ELSE 'Inconnu'
                       END as type_utilisateur
                FROM pister p
                LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                LEFT JOIN action a ON p.id_action = a.id_action
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                ORDER BY p.date_piste DESC, p.heure_piste DESC";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuditRecordsWithFilters($filters = [])
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['date_debut'])) {
            $where[] = "p.date_piste >= ?";
            $params[] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where[] = "p.date_piste <= ?";
            $params[] = $filters['date_fin'];
        }
        
        if (!empty($filters['type_action'])) {
            $where[] = "a.lib_action = ?";
            $params[] = $filters['type_action'];
        }
        
        if (!empty($filters['type_utilisateur'])) {
            switch ($filters['type_utilisateur']) {
                case 'Enseignant':
                    $where[] = "e.id_ens IS NOT NULL";
                    break;
                case 'Personnel administratif':
                    $where[] = "pa.id_personnel_adm IS NOT NULL";
                    break;
                case 'Étudiant':
                    $where[] = "et.num_etd IS NOT NULL";
                    break;
            }
        }
        
        if (!empty($filters['module'])) {
            $where[] = "t.lib_traitement = ?";
            $params[] = $filters['module'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT p.*, t.lib_traitement, t.nom_traitement, a.lib_action,
                       COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, u.login_utilisateur, 'Inconnu') as nom_utilisateur,
                       COALESCE(e.prenoms_ens, pa.prenoms_personnel_adm, et.prenom_etd, '') as prenoms_utilisateur,
                       CASE 
                           WHEN e.id_ens IS NOT NULL THEN 'Enseignant'
                           WHEN pa.id_personnel_adm IS NOT NULL THEN 'Personnel administratif'
                           WHEN et.num_etd IS NOT NULL THEN 'Étudiant'
                           ELSE 'Inconnu'
                       END as type_utilisateur
                FROM pister p
                LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                LEFT JOIN action a ON p.id_action = a.id_action
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                $whereClause
                ORDER BY p.date_piste DESC, p.heure_piste DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuditStatistics()
    {
        $sql = "SELECT 
                    COUNT(*) as total_actions,
                    COUNT(DISTINCT p.id_utilisateur) as utilisateurs_actifs,
                    SUM(CASE WHEN p.acceder = 1 THEN 1 ELSE 0 END) as actions_reussies,
                    SUM(CASE WHEN p.acceder = 0 THEN 1 ELSE 0 END) as actions_echouees,
                    SUM(CASE WHEN p.date_piste = CURDATE() THEN 1 ELSE 0 END) as actions_aujourdhui,
                    SUM(CASE WHEN p.date_piste = CURDATE() - INTERVAL 1 DAY THEN 1 ELSE 0 END) as actions_hier,
                    SUM(CASE WHEN p.date_piste = CURDATE() AND a.lib_action LIKE '%onnexion%' THEN 1 ELSE 0 END) as connexions_aujourdhui,
                    SUM(CASE WHEN p.date_piste = CURDATE() - INTERVAL 1 DAY AND a.lib_action LIKE '%onnexion%' THEN 1 ELSE 0 END) as connexions_hier,
                    SUM(CASE WHEN p.date_piste = CURDATE() AND p.acceder = 0 THEN 1 ELSE 0 END) as echecs_aujourdhui,
                    SUM(CASE WHEN p.date_piste = CURDATE() - INTERVAL 1 DAY AND p.acceder = 0 THEN 1 ELSE 0 END) as echecs_hier
                FROM pister p
                LEFT JOIN action a ON p.id_action = a.id_action
                WHERE p.date_piste >= CURDATE() - INTERVAL 7 DAY";
        
        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
    }

    public function getAuditRecordsWithPagination($page = 1, $limit = 10, $filters = [])
    {
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];
        
        if (!empty($filters['date_debut'])) {
            $where[] = "p.date_piste >= ?";
            $params[] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where[] = "p.date_piste <= ?";
            $params[] = $filters['date_fin'];
        }
        
        if (!empty($filters['type_action'])) {
            $where[] = "a.lib_action = ?";
            $params[] = $filters['type_action'];
        }
        
        if (!empty($filters['type_utilisateur'])) {
            switch ($filters['type_utilisateur']) {
                case 'Enseignant':
                    $where[] = "e.id_ens IS NOT NULL";
                    break;
                case 'Personnel administratif':
                    $where[] = "pa.id_personnel_adm IS NOT NULL";
                    break;
                case 'Étudiant':
                    $where[] = "et.num_etd IS NOT NULL";
                    break;
            }
        }
        
        if (!empty($filters['module'])) {
            $where[] = "t.lib_traitement = ?";
            $params[] = $filters['module'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT p.*, t.lib_traitement, t.nom_traitement, a.lib_action,
                       COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, u.login_utilisateur, 'Inconnu') as nom_utilisateur,
                       COALESCE(e.prenoms_ens, pa.prenoms_personnel_adm, et.prenom_etd, '') as prenoms_utilisateur,
                       CASE 
                           WHEN e.id_ens IS NOT NULL THEN 'Enseignant'
                           WHEN pa.id_personnel_adm IS NOT NULL THEN 'Personnel administratif'
                           WHEN et.num_etd IS NOT NULL THEN 'Étudiant'
                           ELSE 'Inconnu'
                       END as type_utilisateur
                FROM pister p
                LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                LEFT JOIN action a ON p.id_action = a.id_action
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                $whereClause
                ORDER BY p.date_piste DESC, p.heure_piste DESC
                LIMIT $limit OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchAuditRecords($search, $filters = [])
    {
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(
                COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, '') LIKE ? OR 
                COALESCE(e.prenoms_ens, pa.prenoms_personnel_adm, et.prenom_etd, '') LIKE ? OR
                COALESCE(e.email_ens, pa.email_personnel_adm, et.email_etd, '') LIKE ? OR
                a.lib_action LIKE ? OR 
                t.nom_traitement LIKE ?
            )";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }
        
        // Ajouter les autres filtres
        if (!empty($filters['date_debut'])) {
            $where[] = "p.date_piste >= ?";
            $params[] = $filters['date_debut'];
        }
        
        if (!empty($filters['date_fin'])) {
            $where[] = "p.date_piste <= ?";
            $params[] = $filters['date_fin'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT p.*, t.lib_traitement, t.nom_traitement, a.lib_action,
                       COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, u.login_utilisateur, 'Inconnu') as nom_utilisateur,
                       COALESCE(e.prenoms_ens, pa.prenoms_personnel_adm, et.prenom_etd, '') as prenoms_utilisateur,
                       CASE 
                           WHEN e.id_ens IS NOT NULL THEN 'Enseignant'
                           WHEN pa.id_personnel_adm IS NOT NULL THEN 'Personnel administratif'
                           WHEN et.num_etd IS NOT NULL THEN 'Étudiant'
                           ELSE 'Inconnu'
                       END as type_utilisateur
                FROM pister p
                LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                LEFT JOIN action a ON p.id_action = a.id_action
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                $whereClause
                ORDER BY p.date_piste DESC, p.heure_piste DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuditRecordById($id)
    {
        $sql = "SELECT p.*, t.lib_traitement, t.nom_traitement, a.lib_action,
                       COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, u.login_utilisateur, 'Inconnu') as nom_utilisateur,
                       COALESCE(e.prenoms_ens, pa.prenoms_personnel_adm, et.prenom_etd, '') as prenoms_utilisateur,
                       CASE 
                           WHEN e.id_ens IS NOT NULL THEN 'Enseignant'
                           WHEN pa.id_personnel_adm IS NOT NULL THEN 'Personnel administratif'
                           WHEN et.num_etd IS NOT NULL THEN 'Étudiant'
                           ELSE 'Inconnu'
                       END as type_utilisateur
                FROM pister p
                LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                LEFT JOIN action a ON p.id_action = a.id_action
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                WHERE p.id_piste = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function exportAuditData($format = 'csv', $filters = [])
    {
        $records = $this->getAuditRecordsWithFilters($filters);
        
        switch ($format) {
            case 'csv':
                return $this->exportToCSV($records);
            case 'json':
                return $this->exportToJSON($records);
            case 'pdf':
                return $this->exportToPDF($records);
            default:
                return false;
        }
    }

    public function getAuditLogsByUser($userId)
    {
        $sql = "SELECT p.*, t.lib_traitement, t.nom_traitement, a.lib_action
                FROM pister p
                LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                LEFT JOIN action a ON p.id_action = a.id_action
                WHERE p.id_utilisateur = ?
                ORDER BY p.date_piste DESC, p.heure_piste DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuditLogsByModule($module)
    {
        $sql = "SELECT p.*, t.lib_traitement, t.nom_traitement, a.lib_action,
                       COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, u.login_utilisateur, 'Inconnu') as nom_utilisateur
                FROM pister p
                LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                LEFT JOIN action a ON p.id_action = a.id_action
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                WHERE t.lib_traitement = ?
                ORDER BY p.date_piste DESC, p.heure_piste DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$module]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAuditLogsByDateRange($startDate, $endDate)
    {
        $sql = "SELECT p.*, t.lib_traitement, t.nom_traitement, a.lib_action,
                       COALESCE(e.nom_ens, pa.nom_personnel_adm, et.nom_etd, u.login_utilisateur, 'Inconnu') as nom_utilisateur
                FROM pister p
                LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                LEFT JOIN action a ON p.id_action = a.id_action
                LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                WHERE p.date_piste BETWEEN ? AND ?
                ORDER BY p.date_piste DESC, p.heure_piste DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableActions()
    {
        return $this->db->query("SELECT DISTINCT lib_action FROM action WHERE lib_action IS NOT NULL ORDER BY lib_action")->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getAvailableModules()
    {
        return $this->db->query("SELECT DISTINCT lib_traitement, nom_traitement FROM traitement WHERE lib_traitement IS NOT NULL ORDER BY nom_traitement")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAvailableUserTypes()
    {
        return ['Enseignant', 'Personnel administratif', 'Étudiant'];
    }

    // Méthodes privées pour l'export
    private function exportToCSV($records)
    {
        $filename = 'audit_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = __DIR__ . '/../../../storage/exports/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $fp = fopen($filepath, 'w');
        
        // En-têtes
        fputcsv($fp, ['Date', 'Heure', 'Utilisateur', 'Type', 'Action', 'Module', 'Statut']);
        
        // Données
        foreach ($records as $record) {
            fputcsv($fp, [
                $record['date_piste'],
                $record['heure_piste'],
                $record['nom_utilisateur'] . ' ' . $record['prenoms_utilisateur'],
                $record['type_utilisateur'],
                $record['lib_action'],
                $record['nom_traitement'],
                $record['acceder'] ? 'Succès' : 'Échec'
            ]);
        }
        
        fclose($fp);
        return $filepath;
    }

    private function exportToJSON($records)
    {
        $filename = 'audit_' . date('Y-m-d_H-i-s') . '.json';
        $filepath = __DIR__ . '/../../../storage/exports/' . $filename;
        
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        $data = [
            'export_info' => [
                'date_export' => date('Y-m-d H:i:s'),
                'total_records' => count($records)
            ],
            'records' => $records
        ];
        
        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        return $filepath;
    }

    private function exportToPDF($records)
    {
        // Cette méthode nécessiterait une bibliothèque PDF comme TCPDF ou FPDF
        // Pour l'instant, on retourne false
        return false;
    }

    public function clearOldLogs($days = 90) {
        try {
            $sql = "DELETE FROM pister WHERE date_piste < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$days]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Erreur lors du nettoyage des logs: " . $e->getMessage());
            return false;
        }
    }

    public function getAuditSummary($period = 'week') {
        try {
            $dateCondition = $this->getDateCondition($period);
            
            $sql = "SELECT 
                        DATE(date_piste) as date,
                        COUNT(*) as total_actions,
                        COUNT(DISTINCT id_utilisateur) as unique_users,
                        SUM(CASE WHEN acceder = 1 THEN 1 ELSE 0 END) as successful_actions,
                        SUM(CASE WHEN acceder = 0 THEN 1 ELSE 0 END) as failed_actions
                    FROM pister 
                    WHERE $dateCondition
                    GROUP BY DATE(date_piste)
                    ORDER BY date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération du résumé d'audit: " . $e->getMessage());
            return [];
        }
    }

    public function getUserActivity($user_id, $period = 'week') {
        try {
            $dateCondition = $this->getDateCondition($period);
            
            $sql = "SELECT 
                        p.*,
                        t.lib_traitement,
                        t.nom_traitement,
                        a.lib_action
                    FROM pister p
                    LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                    LEFT JOIN action a ON p.id_action = a.id_action
                    WHERE p.id_utilisateur = ? AND $dateCondition
                    ORDER BY p.date_piste DESC, p.heure_piste DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération de l'activité utilisateur: " . $e->getMessage());
            return [];
        }
    }

    public function getModuleActivity($module, $period = 'week') {
        try {
            $dateCondition = $this->getDateCondition($period);
            
            $sql = "SELECT 
                        DATE(p.date_piste) as date,
                        COUNT(*) as total_actions,
                        COUNT(DISTINCT p.id_utilisateur) as unique_users,
                        SUM(CASE WHEN p.acceder = 1 THEN 1 ELSE 0 END) as successful_actions,
                        SUM(CASE WHEN p.acceder = 0 THEN 1 ELSE 0 END) as failed_actions
                    FROM pister p
                    LEFT JOIN traitement t ON p.id_traitement = t.id_traitement
                    WHERE t.lib_traitement = ? AND $dateCondition
                    GROUP BY DATE(p.date_piste)
                    ORDER BY date DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$module]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur lors de la récupération de l'activité module: " . $e->getMessage());
            return [];
        }
    }

    private function getDateCondition($period) {
        switch ($period) {
            case 'day':
                return "date_piste = CURDATE()";
            case 'week':
                return "date_piste >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            case 'month':
                return "date_piste >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            case 'year':
                return "date_piste >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            default:
                return "date_piste >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        }
    }
}
