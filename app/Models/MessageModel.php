<?php

require_once __DIR__ . '/../../config/config.php';

class MessageModel {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getConnection();
    }

    /**
     * Récupère les messages reçus avec filtres et pagination
     */
    public function getMessagesRecus($user_id, $search = '', $filter_statut = '', $filter_priorite = '', $filter_date = '', $page = 1, $limit = 6) {
        $offset = ($page - 1) * $limit;
        
        $where = ["m.destinataire_id = ?", "m.statut != 'supprimé'", "m.destinataire_type='individuel'"];
        $params = [$user_id];

        if (!empty($search)) {
            $where[] = "(m.objet LIKE ? OR m.contenu LIKE ? OR u_exped.login_utilisateur LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
        }

        if (!empty($filter_statut)) {
            $where[] = "m.statut = ?";
            $params[] = $filter_statut;
        }

        if (!empty($filter_priorite)) {
            $where[] = "m.priorite = ?";
            $params[] = $filter_priorite;
        }

        if (!empty($filter_date)) {
            switch ($filter_date) {
                case 'today':
                    $where[] = "DATE(m.date_creation) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "YEARWEEK(m.date_creation, 1) = YEARWEEK(CURDATE(), 1)";
                    break;
                case 'month':
                    $where[] = "MONTH(m.date_creation) = MONTH(CURDATE()) AND YEAR(m.date_creation) = YEAR(CURDATE())";
                    break;
            }
        }

        $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) FROM messages m
            JOIN utilisateur u_exped ON m.expediteur_id = u_exped.id_utilisateur
            $where_sql";
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_messages = $count_stmt->fetchColumn();

        // Requête principale
        $sql = "SELECT m.*, 
            CASE 
                WHEN e_exped.nom_ens IS NOT NULL THEN CONCAT(e_exped.nom_ens, ' ', e_exped.prenoms_ens)
                WHEN et_exped.nom_etd IS NOT NULL THEN CONCAT(et_exped.nom_etd, ' ', et_exped.prenom_etd)
                WHEN pa_exped.nom_personnel_adm IS NOT NULL THEN CONCAT(pa_exped.nom_personnel_adm, ' ', pa_exped.prenoms_personnel_adm)
                ELSE u_exped.login_utilisateur
            END as expediteur_nom
            FROM messages m
            JOIN utilisateur u_exped ON m.expediteur_id = u_exped.id_utilisateur
            LEFT JOIN enseignants e_exped ON u_exped.login_utilisateur = e_exped.email_ens
            LEFT JOIN etudiants et_exped ON u_exped.login_utilisateur = et_exped.email_etd
            LEFT JOIN personnel_administratif pa_exped ON u_exped.login_utilisateur = pa_exped.email_personnel_adm
            $where_sql
            ORDER BY m.date_creation DESC
            LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'messages' => $messages,
            'total' => $total_messages,
            'pages' => ceil($total_messages / $limit)
        ];
    }

    /**
     * Récupère les messages envoyés
     */
    public function getMessagesEnvoyes($user_id) {
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
                WHERE m.expediteur_id = ? AND m.statut != 'supprimé' AND m.destinataire_type='individuel'
                ORDER BY m.date_creation DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les contacts avec pagination
     */
    public function getContacts($user_id, $page = 1, $limit = 6, $search = '') {
        $offset = ($page - 1) * $limit;
        
        $where = ["u.id_utilisateur != ?"];
        $params = [$user_id];

        if (!empty($search)) {
            $where[] = "(e.nom_ens LIKE ? OR e.prenoms_ens LIKE ? OR et.nom_etd LIKE ? OR et.prenom_etd LIKE ? OR pa.nom_personnel_adm LIKE ? OR pa.prenoms_personnel_adm LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
        }

        $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) FROM utilisateur u
            LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
            LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
            LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
            $where_sql";
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_contacts = $count_stmt->fetchColumn();

        // Requête principale
        $sql = "SELECT u.id_utilisateur, u.login_utilisateur as email,
                CASE 
                    WHEN e.nom_ens IS NOT NULL THEN CONCAT(e.nom_ens, ' ', e.prenoms_ens)
                    WHEN et.nom_etd IS NOT NULL THEN CONCAT(et.nom_etd, ' ', et.prenom_etd)
                    WHEN pa.nom_personnel_adm IS NOT NULL THEN CONCAT(pa.nom_personnel_adm, ' ', pa.prenoms_personnel_adm)
                    ELSE u.login_utilisateur
                END as nom_complet,
                CASE 
                    WHEN e.photo_ens IS NOT NULL THEN e.photo_ens
                    WHEN et.photo_etd IS NOT NULL THEN et.photo_etd
                    WHEN pa.photo_personnel_adm IS NOT NULL THEN pa.photo_personnel_adm
                    ELSE 'default_profile.jpg'
                END as photo
                FROM utilisateur u
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                $where_sql
                ORDER BY nom_complet
                LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'contacts' => $contacts,
            'total' => $total_contacts,
            'pages' => ceil($total_contacts / $limit)
        ];
    }

    /**
     * Récupère les détails d'un contact
     */
    public function getContactDetails($email) {
        $sql = "SELECT u.id_utilisateur, u.login_utilisateur as email,
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
                WHERE u.login_utilisateur = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Envoie un message
     */
    public function envoyerMessage($expediteur_id, $destinataire_id, $objet, $contenu, $priorite = 'normale') {
        $sql = "INSERT INTO messages (expediteur_id, destinataire_id, objet, contenu, priorite, date_creation, statut, destinataire_type) 
                VALUES (?, ?, ?, ?, ?, NOW(), 'non lu', 'individuel')";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$expediteur_id, $destinataire_id, $objet, $contenu, $priorite]);
    }

    /**
     * Marque un message comme lu
     */
    public function marquerCommeLu($message_id, $user_id) {
        $sql = "UPDATE messages SET statut = 'lu' WHERE id_message = ? AND destinataire_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$message_id, $user_id]);
    }

    /**
     * Archive un message
     */
    public function archiverMessage($message_id, $user_id) {
        $sql = "UPDATE messages SET statut = 'archivé' WHERE id_message = ? AND destinataire_id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$message_id, $user_id]);
    }

    /**
     * Supprime des messages
     */
    public function supprimerMessages($message_ids, $user_id) {
        try {
            $this->pdo->beginTransaction();
            
            $placeholders = str_repeat('?,', count($message_ids) - 1) . '?';
            $sql = "UPDATE messages SET statut = 'supprimé' WHERE id_message IN ($placeholders) AND destinataire_id = ?";
            
            $params = array_merge($message_ids, [$user_id]);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Compte les messages non lus
     */
    public function compterMessagesNonLus($user_id) {
        $sql = "SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND statut = 'non lu'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    }
} 