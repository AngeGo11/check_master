<?php

namespace App\Models;

require_once __DIR__ . '/../../config/config.php';

use PDO;
use PDOException;

class Etudiant
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Récupère tous les étudiants avec filtres et pagination
     */
    public function getAllEtudiants($search = '', $promotion = '', $niveau = '', $page = 1, $limit = 50)
    {
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR e.email_etd LIKE ? OR e.num_carte_etd LIKE ? OR p.lib_promotion LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, array_fill(0, 5, $search_param));
        }

        if ($promotion !== '') {
            $where[] = "e.id_promotion = ?";
            $params[] = $promotion;
        }

        if ($niveau !== '') {
            $where[] = "e.id_niv_etd = ?";
            $params[] = $niveau;
        }

        $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT e.*, ne.lib_niv_etd, p.lib_promotion 
                FROM etudiants e 
                JOIN niveau_etude ne ON ne.id_niv_etd = e.id_niv_etd 
                LEFT JOIN promotion p ON e.id_promotion = p.id_promotion 
                $where_sql 
                ORDER BY e.nom_etd, e.prenom_etd 
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compte le total des étudiants avec filtres
     */
    public function getTotalEtudiants($search = '', $promotion = '', $niveau = '')
    {
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR e.email_etd LIKE ? OR e.num_carte_etd LIKE ? OR p.lib_promotion LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, array_fill(0, 5, $search_param));
        }

        if ($promotion !== '') {
            $where[] = "e.id_promotion = ?";
            $params[] = $promotion;
        }

        if ($niveau !== '') {
            $where[] = "e.id_niv_etd = ?";
            $params[] = $niveau;
        }

        $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT COUNT(*) 
                FROM etudiants e 
                LEFT JOIN promotion p ON e.id_promotion = p.id_promotion 
                $where_sql";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Récupère les statistiques des étudiants
     */
    public function getStatistiques()
    {
        $stats = [];

        // Total des étudiants inscrits
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM etudiants");
        $stmt->execute();
        $stats['total_etudiants'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Étudiants en attente de validation
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM etudiants etd 
                                   JOIN rapport_etudiant re ON re.num_etd = etd.num_etd
                                   WHERE statut_rapport = 'En attente de validation'");
        $stmt->execute();
        $stats['en_attente'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Étudiants validés
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM etudiants etd 
                                   JOIN rapport_etudiant re ON re.num_etd = etd.num_etd
                                   WHERE statut_rapport = 'Validé'");
        $stmt->execute();
        $stats['valides'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Étudiants refusés
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM etudiants etd 
                                   JOIN rapport_etudiant re ON re.num_etd = etd.num_etd
                                   WHERE statut_rapport = 'Rejeté'");
        $stmt->execute();
        $stats['refuses'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return $stats;
    }

    // CREATE
    public function ajouterEtudiant($nom, $prenom, $email, $num_carte, $id_niv_etd, $id_promotion, $date_naissance = null, $sexe = 'Homme')
    {
        try {
            $this->db->beginTransaction();

            // Ajout de l'étudiant
            $query = "INSERT INTO etudiants (num_carte_etd, nom_etd, prenom_etd, email_etd, date_naissance_etd, id_promotion, sexe_etd, id_niv_etd, photo_etd) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'default_profile.jpg')";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_carte, $nom, $prenom, $email, $date_naissance, $id_promotion, $sexe, $id_niv_etd]);

            // Création de l'utilisateur associé
            $password = password_hash(substr($num_carte, -4) . substr($nom, 0, 3), PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("INSERT INTO utilisateur (login_utilisateur, mdp_utilisateur) VALUES (?, ?)");
            $stmt->execute([$email, $password]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // READ
    public function getEtudiantById($id)
    {
        $query = "SELECT e.*, ne.lib_niv_etd, p.lib_promotion 
                  FROM etudiants e 
                  JOIN niveau_etude ne ON ne.id_niv_etd = e.id_niv_etd 
                  LEFT JOIN promotion p ON e.id_promotion = p.id_promotion 
                  WHERE e.num_etd = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // UPDATE
    public function modifierEtudiant($id, $num_carte, $nom, $prenom, $email, $id_niv_etd, $id_promotion, $date_naissance = null, $sexe = 'Homme')
    {
        try {
            $this->db->beginTransaction();

            // Récupérer l'ancien email
            $stmt = $this->db->prepare("SELECT email_etd FROM etudiants WHERE num_etd = ?");
            $stmt->execute([$id]);
            $old_email = $stmt->fetchColumn();

            // Mettre à jour l'étudiant
            $query = "UPDATE etudiants SET num_carte_etd = ?, nom_etd = ?, prenom_etd = ?, 
                      email_etd = ?, date_naissance_etd = ?, id_promotion = ?, sexe_etd = ?, id_niv_etd = ? 
                      WHERE num_etd = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$num_carte, $nom, $prenom, $email, $date_naissance, $id_promotion, $sexe, $id_niv_etd, $id]);

            // Mettre à jour l'email dans la table utilisateur si nécessaire
            if ($old_email !== $email) {
                $stmt = $this->db->prepare("UPDATE utilisateur SET login_utilisateur = ? WHERE login_utilisateur = ?");
                $stmt->execute([$email, $old_email]);
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // DELETE
    public function supprimerEtudiant($id)
    {
        try {
            $this->db->beginTransaction();

            // Récupérer l'email de l'étudiant
            $stmt = $this->db->prepare("SELECT email_etd FROM etudiants WHERE num_etd = ?");
            $stmt->execute([$id]);
            $email = $stmt->fetchColumn();

            // Vérifier si l'étudiant a des rapports
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM rapport_etudiant WHERE num_etd = ?");
            $stmt->execute([$id]);
            $has_reports = $stmt->fetchColumn() > 0;

            // Supprimer les rapports si nécessaire
            if ($has_reports) {
                // Supprimer d'abord les entrées dans la table deposer
                $stmt = $this->db->prepare("DELETE d FROM deposer d 
                                          JOIN rapport_etudiant r ON d.id_rapport_etd = r.id_rapport_etd 
                                          WHERE r.num_etd = ?");
                $stmt->execute([$id]);

                // Ensuite supprimer les rapports
                $stmt = $this->db->prepare("DELETE FROM rapport_etudiant WHERE num_etd = ?");
                $stmt->execute([$id]);
            }

            // Supprimer l'étudiant
            $stmt = $this->db->prepare("DELETE FROM etudiants WHERE num_etd = ?");
            $stmt->execute([$id]);

            // Supprimer le compte utilisateur associé
            $stmt = $this->db->prepare("DELETE FROM utilisateur WHERE login_utilisateur = ?");
            $stmt->execute([$email]);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Vérifie si un étudiant existe déjà
     */
    public function etudiantExists($email, $num_carte)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM etudiants WHERE email_etd = ? OR num_carte_etd = ?");
        $stmt->execute([$email, $num_carte]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Récupère les rapports d'un étudiant
     */
    public function getRapportsEtudiant($etudiant_id)
    {
        $query = "SELECT r.*, d.date_depot 
                  FROM rapport_etudiant r 
                  LEFT JOIN deposer d ON d.id_rapport_etd = r.id_rapport_etd 
                  WHERE r.num_etd = ?
                  ORDER BY r.date_rapport DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$etudiant_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    /**
     * Récupère les rapports des rapports étudiants en attente d'approbation
     */
    public function getRapportsEnAttente($search_rapport = '', $statut = '', $date_rapport = '', $page = 1, $limit = 10)
    {
        // Construction des filtres dynamiques
        $where_rapport = [];
        $params_rapport = [];

        // Recherche sur nom, prénom, email ou titre du rapport
        if ($search_rapport !== '') {
            $where_rapport[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR e.email_etd LIKE ? OR r.nom_rapport LIKE ?)";
            $search_param_rapport = "%$search_rapport%";
            $params_rapport = array_merge($params_rapport, array_fill(0, 4, $search_param_rapport));
        }

        // Filtre sur la date de soumission
        if ($date_rapport === 'today') {
            $where_rapport[] = "DATE(r.date_rapport) = CURDATE()";
        } elseif ($date_rapport === 'week') {
            $where_rapport[] = "YEARWEEK(r.date_rapport, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($date_rapport === 'month') {
            $where_rapport[] = "MONTH(r.date_rapport) = MONTH(CURDATE()) AND YEAR(r.date_rapport) = YEAR(CURDATE())";
        }

        // Statuts à afficher
        $where_rapport[] = "(r.statut_rapport = ? OR r.statut_rapport = ?)";
        $params_rapport[] = "En attente d'approbation";
        $params_rapport[] = "Approuvé";

        $where_sql_rapport = count($where_rapport) ? ('WHERE ' . implode(' AND ', $where_rapport)) : '';

        // Pagination : compter le total
        $count_sql_rapport = "SELECT COUNT(*) 
            FROM etudiants e
            JOIN rapport_etudiant r ON r.num_etd = e.num_etd 
            JOIN deposer d ON d.id_rapport_etd = r.id_rapport_etd
            JOIN utilisateur u ON u.login_utilisateur = e.email_etd
            $where_sql_rapport";
        $count_stmt_rapport = $this->db->prepare($count_sql_rapport);
        $count_stmt_rapport->execute($params_rapport);
        $total_records_rapport = $count_stmt_rapport->fetchColumn();
        $total_pages_rapport = max(1, ceil($total_records_rapport / $limit));

        // Requête principale avec LIMIT/OFFSET
        $offset = ($page - 1) * $limit;

        $sql_rapport = "SELECT e.nom_etd, e.prenom_etd, e.email_etd, e.num_etd, 
            r.id_rapport_etd, r.nom_rapport, r.date_rapport, r.theme_memoire,
            d.date_depot, r.statut_rapport
            FROM etudiants e
            JOIN rapport_etudiant r ON r.num_etd = e.num_etd 
            JOIN deposer d ON d.id_rapport_etd = r.id_rapport_etd
            JOIN utilisateur u ON u.login_utilisateur = e.email_etd
            $where_sql_rapport
            ORDER BY r.date_rapport DESC
            LIMIT $limit OFFSET $offset";
        $stmt_rapport = $this->db->prepare($sql_rapport);
        $stmt_rapport->execute($params_rapport);
        $lignes_rapport = $stmt_rapport->fetchAll();

        // Retourner les résultats avec les informations de pagination
        return [
            'rapports' => $lignes_rapport,
            'total_records' => $total_records_rapport,
            'total_pages' => $total_pages_rapport,
            'current_page' => $page,
            'limit' => $limit
        ];
    }



    /**
     * Récupère les informations de stage d'un étudiant
     */
    public function getStageInfo($etudiant_id)
    {
        $query = "SELECT f.*, e.lib_entr, e.adresse, e.ville, e.pays 
                  FROM faire_stage f 
                  JOIN entreprise e ON f.id_entr = e.id_entr 
                  WHERE f.num_etd = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$etudiant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les informations de scolarité d'un étudiant
     */
    public function getScolariteInfo($etudiant_id)
    {
        $query = "SELECT r.* FROM reglement r WHERE r.num_etd = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$etudiant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
