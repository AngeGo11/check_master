<?php

namespace App\Models;

require_once __DIR__ . '/../../config/config.php';

use PDO;
use PDOException;
use Exception;

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
    public function getAllEtudiants($search = '', $promotion = '', $niveau = '', $statut_etudiant = '', $page = 1, $limit = 50)
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

        if ($statut_etudiant !== '') {
            $where[] = "e.id_statut = ?";
            $params[] = $statut_etudiant;
        }

        $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        $sql = "SELECT e.*, ne.lib_niv_etd, p.lib_promotion, se.lib_statut 
                FROM etudiants e 
                JOIN niveau_etude ne ON ne.id_niv_etd = e.id_niv_etd 
                LEFT JOIN promotion p ON e.id_promotion = p.id_promotion 
                LEFT JOIN statut_etudiant se ON e.id_statut = se.id_statut 
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
    public function getTotalEtudiants($search = '', $promotion = '', $niveau = '', $statut_etudiant = '')
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

        if ($statut_etudiant !== '') {
            $where[] = "e.id_statut = ?";
            $params[] = $statut_etudiant;
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

    /**
     * Vérifier si un étudiant est à cheval
     */
    public function isEtudiantCheval($num_etd, $id_ac)
    {
        try {
            $sql = "SELECT COUNT(*) FROM inscription_etudiant_cheval 
                    WHERE num_etd = ? AND id_ac = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_etd, $id_ac]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur vérification étudiant à cheval: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les informations d'inscription d'un étudiant à cheval
     */
    public function getInscriptionCheval($num_etd, $id_ac)
    {
        try {
            $sql = "SELECT iec.*, e.nom_etd, e.prenom_etd, e.email_etd, 
                           ne.lib_niv_etd, p.lib_promotion, 
                           CONCAT(YEAR(aa.date_debut), '-', YEAR(aa.date_fin)) as annee_ac
                    FROM inscription_etudiant_cheval iec
                    JOIN etudiants e ON iec.num_etd = e.num_etd
                    JOIN niveau_etude ne ON e.id_niv_etd = ne.id_niv_etd
                    JOIN promotion p ON iec.promotion_principale = p.id_promotion
                    JOIN annee_academique aa ON iec.id_ac = aa.id_ac
                    WHERE iec.num_etd = ? AND iec.id_ac = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_etd, $id_ac]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération inscription étudiant à cheval: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les matières à rattraper d'un étudiant
     */
    public function getMatieresRattrapage($num_etd, $id_ac)
    {
        try {
            $sql = "SELECT mr.*, ec.lib_ecue, ec.credit_ecue,
                           p_origine.lib_promotion as promotion_origine,
                           p_actuelle.lib_promotion as promotion_actuelle
                    FROM matieres_rattrapage mr
                    JOIN ecue ec ON mr.id_ecue = ec.id_ecue
                    JOIN promotion p_origine ON mr.promotion_origine = p_origine.id_promotion
                    JOIN promotion p_actuelle ON mr.promotion_actuelle = p_actuelle.id_promotion
                    WHERE mr.num_etd = ? AND mr.id_ac = ?
                    ORDER BY mr.date_creation DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_etd, $id_ac]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération matières rattrapage: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ajouter une matière à rattraper
     */
    public function ajouterMatiereRattrapage($num_etd, $id_ecue, $id_ac, $promotion_origine, $promotion_actuelle)
    {
        try {
            $sql = "INSERT INTO matieres_rattrapage (num_etd, id_ecue, id_ac, promotion_origine, promotion_actuelle, statut)
                    VALUES (?, ?, ?, ?, ?, 'En cours')";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$num_etd, $id_ecue, $id_ac, $promotion_origine, $promotion_actuelle]);
        } catch (PDOException $e) {
            error_log("Erreur ajout matière rattrapage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour le statut d'une matière à rattraper
     */
    public function updateStatutMatiereRattrapage($id_rattrapage, $statut)
    {
        try {
            $sql = "UPDATE matieres_rattrapage SET statut = ?, date_validation = NOW() 
                    WHERE id_rattrapage = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$statut, $id_rattrapage]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour statut matière rattrapage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une matière à rattraper
     */
    public function supprimerMatiereRattrapage($id_rattrapage)
    {
        try {
            $sql = "DELETE FROM matieres_rattrapage WHERE id_rattrapage = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id_rattrapage]);
        } catch (PDOException $e) {
            error_log("Erreur suppression matière rattrapage: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Inscrire un étudiant à cheval
     */
    public function inscrireEtudiantCheval($num_etd, $id_ac, $promotion_principale, $nombre_matieres_rattrapage, $montant_inscription, $commentaire = '')
    {
        try {
            $this->db->beginTransaction();
            
            // Insérer dans la table inscription_etudiant_cheval
            $sql = "INSERT INTO inscription_etudiant_cheval 
                    (num_etd, id_ac, promotion_principale, nombre_matieres_rattrapage, montant_inscription, commentaire)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$num_etd, $id_ac, $promotion_principale, $nombre_matieres_rattrapage, $montant_inscription, $commentaire]);
            
            if ($result) {
                // Mettre à jour le statut de l'étudiant à 2 (À cheval)
                $sql_update = "UPDATE etudiants SET id_statut = 2 WHERE num_etd = ?";
                $stmt_update = $this->db->prepare($sql_update);
                $result_update = $stmt_update->execute([$num_etd]);
                
                if ($result_update) {
                    error_log("Étudiant $num_etd inscrit à cheval - statut mis à jour à 2");
                    $this->db->commit();
                    return true;
                } else {
                    error_log("Erreur lors de la mise à jour du statut pour l'étudiant $num_etd");
                    $this->db->rollBack();
                    return false;
                }
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur inscription étudiant à cheval: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour l'inscription d'un étudiant à cheval
     */
    public function updateInscriptionCheval($num_etd, $id_ac, $nombre_matieres_rattrapage, $montant_inscription, $statut_paiement, $commentaire = '')
    {
        try {
            $sql = "UPDATE inscription_etudiant_cheval 
                    SET nombre_matieres_rattrapage = ?, montant_inscription = ?, 
                        statut_paiement = ?, commentaire = ?
                    WHERE num_etd = ? AND id_ac = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$nombre_matieres_rattrapage, $montant_inscription, $statut_paiement, $commentaire, $num_etd, $id_ac]);
        } catch (PDOException $e) {
            error_log("Erreur mise à jour inscription étudiant à cheval: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer les frais pour un étudiant à cheval
     */
    public function calculerFraisCheval($id_niv_etd, $id_ac, $nombre_matieres_rattrapage)
    {
        try {
            $sql = "SELECT montant_base, montant_supplementaire 
                    FROM frais_etudiant_cheval 
                    WHERE id_niv_etd = ? AND id_ac = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_niv_etd, $id_ac]);
            $frais = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($frais) {
                $montant_total = $frais['montant_base'] + ($frais['montant_supplementaire'] * $nombre_matieres_rattrapage);
                return [
                    'montant_base' => $frais['montant_base'],
                    'montant_supplementaire' => $frais['montant_supplementaire'],
                    'montant_total' => $montant_total,
                    'nombre_matieres' => $nombre_matieres_rattrapage
                ];
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur calcul frais étudiant à cheval: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer les frais d'inscription à cheval avec les prix des matières
     */
    public function calculerFraisChevalAvecMatieres($id_niv_etd, $id_ac, $matieres_ids = [])
    {
        try {
            // Récupérer les frais de base pour le niveau et l'année
            $sql = "SELECT montant FROM frais_inscription 
                    WHERE id_niv_etd = ? AND id_ac = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_niv_etd, $id_ac]);
            $frais_base = $stmt->fetch(PDO::FETCH_ASSOC);

            $montant_base = $frais_base ? $frais_base['montant'] : 0;

            // Calculer le total des prix des matières sélectionnées
            $total_prix_matieres = 0;
            if (!empty($matieres_ids)) {
                $placeholders = str_repeat('?,', count($matieres_ids) - 1) . '?';
                $sql = "SELECT SUM(COALESCE(prix_matiere_cheval, 25000.00)) as total_prix
                        FROM ecue 
                        WHERE id_ecue IN ($placeholders)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute($matieres_ids);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $total_prix_matieres = $result ? $result['total_prix'] : 0;
            }

            $total_frais = $montant_base + $total_prix_matieres;

            return [
                'frais_base' => $montant_base,
                'total_prix_matieres' => $total_prix_matieres,
                'total_frais' => $total_frais
            ];
        } catch (PDOException $e) {
            error_log("Erreur calcul frais cheval avec matières: " . $e->getMessage());
            return [
                'frais_base' => 0,
                'total_prix_matieres' => 0,
                'total_frais' => 0
            ];
        }
    }

    /**
     * Récupérer les prix des matières sélectionnées
     */
    public function getPrixMatieres($matieres_ids)
    {
        try {
            if (empty($matieres_ids)) {
                return [];
            }

            $placeholders = str_repeat('?,', count($matieres_ids) - 1) . '?';
            $sql = "SELECT id_ecue, lib_ecue, COALESCE(prix_matiere_cheval, 25000.00) as prix_matiere_cheval
                    FROM ecue 
                    WHERE id_ecue IN ($placeholders)
                    ORDER BY lib_ecue";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($matieres_ids);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération prix matières: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer tous les étudiants à cheval pour une année académique
     */
    public function getAllEtudiantsCheval($id_ac, $filters = [])
    {
        try {
            $sql = "SELECT iec.*, e.nom_etd, e.prenom_etd, e.email_etd, e.id_niv_etd, e.id_promotion,
                           ne.lib_niv_etd, p.lib_promotion,
                           CONCAT(YEAR(aa.date_debut), '-', YEAR(aa.date_fin)) as annee_ac,
                           (SELECT COUNT(*) FROM matieres_rattrapage mr 
                            WHERE mr.num_etd = iec.num_etd AND mr.id_ac = iec.id_ac) as total_matieres,
                           (SELECT COUNT(*) FROM matieres_rattrapage mr 
                            WHERE mr.num_etd = iec.num_etd AND mr.id_ac = iec.id_ac AND mr.statut = 'Validée') as matieres_validees
                    FROM inscription_etudiant_cheval iec
                    JOIN etudiants e ON iec.num_etd = e.num_etd
                    JOIN niveau_etude ne ON e.id_niv_etd = ne.id_niv_etd
                    JOIN promotion p ON iec.promotion_principale = p.id_promotion
                    JOIN annee_academique aa ON iec.id_ac = aa.id_ac
                    WHERE iec.id_ac = ?";

            $params = [$id_ac];

            // Ajout des filtres
            if (!empty($filters['search'])) {
                $sql .= " AND (e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR e.email_etd LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }

            if (!empty($filters['niveau'])) {
                $sql .= " AND e.id_niv_etd = ?";
                $params[] = $filters['niveau'];
            }

            if (!empty($filters['statut_paiement'])) {
                $sql .= " AND iec.statut_paiement = ?";
                $params[] = $filters['statut_paiement'];
            }

            $sql .= " ORDER BY e.nom_etd, e.prenom_etd";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération étudiants à cheval: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les statistiques des étudiants à cheval
     */
    public function getStatistiquesCheval($id_ac)
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_etudiants_cheval,
                        SUM(CASE WHEN statut_paiement = 'Complet' THEN 1 ELSE 0 END) as paiements_complets,
                        SUM(CASE WHEN statut_paiement = 'Partiel' THEN 1 ELSE 0 END) as paiements_partiels,
                        SUM(CASE WHEN statut_paiement = 'En attente' THEN 1 ELSE 0 END) as paiements_en_attente,
                        SUM(montant_inscription) as total_montant,
                        AVG(nombre_matieres_rattrapage) as moyenne_matieres
                    FROM inscription_etudiant_cheval 
                    WHERE id_ac = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_ac]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération statistiques étudiants à cheval: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer l'historique des inscriptions à cheval d'un étudiant
     */
    public function getHistoriqueInscriptionsCheval($num_etd)
    {
        try {
            $sql = "SELECT iec.*, CONCAT(YEAR(aa.date_debut), '-', YEAR(aa.date_fin)) as annee_ac, p.lib_promotion
                    FROM inscription_etudiant_cheval iec
                    JOIN annee_academique aa ON iec.id_ac = aa.id_ac
                    JOIN promotion p ON iec.promotion_principale = p.id_promotion
                    WHERE iec.num_etd = ?
                    ORDER BY iec.date_inscription DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_etd]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération historique inscriptions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier si un étudiant peut passer au niveau supérieur
     */
    public function peutPasserNiveauSuperieur($num_etd, $id_ac)
    {
        try {
            $sql = "SELECT COUNT(*) as total_matieres,
                           SUM(CASE WHEN statut = 'Validée' THEN 1 ELSE 0 END) as matieres_validees
                    FROM matieres_rattrapage 
                    WHERE num_etd = ? AND id_ac = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_etd, $id_ac]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return $result['total_matieres'] > 0 && $result['total_matieres'] == $result['matieres_validees'];
        } catch (PDOException $e) {
            error_log("Erreur vérification passage niveau supérieur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les matières disponibles pour le rattrapage par niveau
     */
    public function getMatieresDisponibles($niveaux_ids, $annee_id = null)
    {
        try {
            if (empty($niveaux_ids)) {
                return [];
            }

            $placeholders = str_repeat('?,', count($niveaux_ids) - 1) . '?';
            $sql = "SELECT DISTINCT ec.id_ecue, ec.lib_ecue, ec.credit_ecue, 
                           ne.id_niv_etd, ne.lib_niv_etd,
                           COALESCE(ec.prix_matiere_cheval, 25000.00) as prix_matiere_cheval
                    FROM ecue ec
                    JOIN ue u ON ec.id_ue = u.id_ue
                    JOIN niveau_etude ne ON u.id_niv_etd = ne.id_niv_etd
                    WHERE u.id_niv_etd IN ($placeholders)";

            $params = $niveaux_ids;

            if ($annee_id) {
                $sql .= " AND u.id_annee_academique = ?";
                $params[] = $annee_id;
            }

            $sql .= " ORDER BY ne.lib_niv_etd, ec.lib_ecue";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération matières disponibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Inscrire plusieurs étudiants à cheval en lot
     */
    public function inscrireEtudiantsChevalLot($etudiants_data)
    {
        try {
            $this->db->beginTransaction();

            $success_count = 0;
            $error_messages = [];

            foreach ($etudiants_data as $data) {
                try {
                    $num_etd = $data['num_etd'];
                    $id_ac = $data['id_ac'];
                    $promotion_principale = $data['promotion_principale'];
                    $nombre_matieres_rattrapage = $data['nombre_matieres_rattrapage'];
                    $montant_inscription = $data['montant_inscription'];
                    $commentaire = $data['commentaire'] ?? '';
                    $matieres_ids = $data['matieres_ids'] ?? [];

                    // Vérifier si l'étudiant n'est pas déjà inscrit à cheval
                    if ($this->isEtudiantCheval($num_etd, $id_ac)) {
                        $error_messages[] = "L'étudiant $num_etd est déjà inscrit à cheval pour cette année";
                        continue;
                    }

                    // Inscrire l'étudiant à cheval
                    $result = $this->inscrireEtudiantCheval(
                        $num_etd,
                        $id_ac,
                        $promotion_principale,
                        $nombre_matieres_rattrapage,
                        $montant_inscription,
                        $commentaire
                    );

                    if ($result) {
                        // Ajouter les matières de rattrapage
                        foreach ($matieres_ids as $matiere_id) {
                            $this->ajouterMatiereRattrapage(
                                $num_etd,
                                $matiere_id,
                                $id_ac,
                                $promotion_principale,
                                $promotion_principale
                            );
                        }
                        $success_count++;
                    } else {
                        $error_messages[] = "Erreur lors de l'inscription de l'étudiant $num_etd";
                    }
                } catch (Exception $e) {
                    $error_messages[] = "Erreur pour l'étudiant $num_etd: " . $e->getMessage();
                }
            }

            if ($success_count > 0) {
                $this->db->commit();
                return [
                    'success' => true,
                    'success_count' => $success_count,
                    'error_messages' => $error_messages
                ];
            } else {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'error_messages' => $error_messages
                ];
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur inscription lot étudiants à cheval: " . $e->getMessage());
            return [
                'success' => false,
                'error_messages' => ['Erreur générale: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Récupérer les informations des étudiants pour l'inscription à cheval
     * Seulement les étudiants avec statut académique = 'Autorisé' dans moyenne_generale
     */
    public function getEtudiantsPourInscriptionCheval($filters = [])
    {
        try {
            // Requête pour récupérer les étudiants avec leur statut académique depuis moyenne_generale
            $sql = "SELECT e.num_etd, e.nom_etd, e.prenom_etd, e.email_etd, e.id_niv_etd, e.id_promotion,
                           ne.lib_niv_etd, p.lib_promotion, se.lib_statut,
                           mg.statut_academique,
                           CASE 
                               WHEN mg.statut_academique = 'Autorisé' THEN 'Autorisé'
                               WHEN mg.statut_academique = 'Validé' THEN 'Non autorisé'
                               WHEN mg.statut_academique = 'Ajourné' THEN 'Non autorisé'
                               ELSE 'Non autorisé'
                           END as statut_inscription_cheval
                    FROM etudiants e
                    JOIN niveau_etude ne ON e.id_niv_etd = ne.id_niv_etd
                    JOIN promotion p ON e.id_promotion = p.id_promotion
                    JOIN statut_etudiant se ON e.id_statut = se.id_statut
                    LEFT JOIN moyenne_generale mg ON e.num_etd = mg.num_etd 
                        AND mg.id_ac = (SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1)
                        AND mg.id_semestre = (
                            SELECT s.id_semestre 
                            FROM semestre s 
                            WHERE s.id_niv_etd = e.id_niv_etd 
                            ORDER BY s.id_semestre DESC 
                            LIMIT 1
                        )
                    WHERE e.id_statut = 2"; // Normal ou À cheval

            $params = [];

            if (!empty($filters['niveau'])) {
                $sql .= " AND e.id_niv_etd = ?";
                $params[] = $filters['niveau'];
            }

            if (!empty($filters['promotion'])) {
                $sql .= " AND e.id_promotion = ?";
                $params[] = $filters['promotion'];
            }

            if (!empty($filters['search'])) {
                $sql .= " AND (e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR e.email_etd LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
            }

            $sql .= " ORDER BY e.nom_etd, e.prenom_etd";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Filtrer seulement les étudiants autorisés pour l'inscription à cheval
            return array_filter($etudiants, function ($etudiant) {
                return $etudiant['statut_inscription_cheval'] === 'Autorisé';
            });
        } catch (PDOException $e) {
            error_log("Erreur récupération étudiants pour inscription cheval: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer le statut académique d'un étudiant depuis moyenne_generale
     */
    public function getStatutAcademiqueEtudiant($num_etd, $id_ac = null)
    {
        try {
            if (!$id_ac) {
                $id_ac = $this->getAnneeAcademiqueEnCours();
            }

            $sql = "SELECT mg.statut_academique, mg.moyenne_generale, mg.total_credits_obtenus, mg.total_credits_inscrits
                    FROM moyenne_generale mg
                    WHERE mg.num_etd = ? AND mg.id_ac = ?
                    ORDER BY mg.date_calcul DESC
                    LIMIT 1";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_etd, $id_ac]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération statut académique: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifier si un étudiant peut être inscrit à cheval
     */
    public function peutEtreInscritCheval($num_etd, $id_ac = null)
    {
        try {
            $statut_academique = $this->getStatutAcademiqueEtudiant($num_etd, $id_ac);

            if (!$statut_academique) {
                return false; // Pas de moyenne générale trouvée
            }

            // Seuls les étudiants avec statut 'Autorisé' peuvent être inscrits à cheval
            return $statut_academique['statut_academique'] === 'Autorisé';
        } catch (PDOException $e) {
            error_log("Erreur vérification inscription cheval: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer l'année académique en cours
     */
    private function getAnneeAcademiqueEnCours()
    {
        try {
            $sql = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur récupération année académique en cours: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mettre à jour le statut académique d'un étudiant
     */
    public function updateStatutAcademique($num_etd, $id_ac, $id_semestre)
    {
        try {
            // Mettre à jour directement le statut de l'étudiant à 2 (À cheval)
            $sql = "UPDATE etudiants 
                    SET id_statut = 2
                    WHERE num_etd = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$num_etd]);
            
            if ($result) {
                error_log("Statut académique mis à jour pour l'étudiant $num_etd - id_statut = 2");
                return true;
            } else {
                error_log("Erreur lors de la mise à jour du statut pour l'étudiant $num_etd");
                return false;
            }
        } catch (PDOException $e) {
            error_log("Erreur mise à jour statut académique: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer la moyenne générale selon le système défini
     * m1: moyenne du 1er semestre (UE mineures)
     * m2: moyenne du 2ème semestre (UE majeures)
     * mga: moyenne générale annuelle = (m1*30 + m2*30)/60
     */
    public function calculerMoyenneGenerale($num_etd, $id_ac)
    {
        try {
            // Récupérer le niveau de l'étudiant
            $stmt = $this->db->prepare("
                SELECT e.id_niv_etd, ne.lib_niv_etd 
                FROM etudiants e 
                JOIN niveau_etude ne ON e.id_niv_etd = ne.id_niv_etd 
                WHERE e.num_etd = ?
            ");
            $stmt->execute([$num_etd]);
            $niveau_etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$niveau_etudiant) {
                throw new Exception("Niveau de l'étudiant non trouvé");
            }

            $is_master2 = (stripos($niveau_etudiant['lib_niv_etd'], 'Master 2') !== false);

            // Récupérer toutes les évaluations de l'étudiant pour l'année
            $sql = "SELECT 
                        ev.id_semestre,
                        s.lib_semestre,
                        ev.note,
                        ev.credit,
                        ue.credit_ue,
                        ec.credit_ecue,
                        CASE 
                            WHEN ev.credit >= 4 THEN 'majeur'
                            ELSE 'mineur'
                        END as type_ue
                    FROM (
                        -- Évaluations ECUE
                        SELECT 
                            CAST(num_etd AS UNSIGNED) as num_etd,
                            id_ac,
                            id_semestre,
                            note,
                            credit,
                            id_ecue,
                            NULL as id_ue
                        FROM evaluer_ecue
                        WHERE CAST(num_etd AS UNSIGNED) = ? AND id_ac = ?
                        
                        UNION ALL
                        
                        -- Évaluations UE directes
                        SELECT 
                            CAST(num_etd AS UNSIGNED) as num_etd,
                            id_ac,
                            id_semestre,
                            note,
                            credit,
                            NULL as id_ecue,
                            id_ue
                        FROM evaluer_ue
                        WHERE CAST(num_etd AS UNSIGNED) = ? AND id_ac = ?
                    ) ev
                    JOIN semestre s ON ev.id_semestre = s.id_semestre
                    LEFT JOIN ecue ec ON ev.id_ecue = ec.id_ecue
                    LEFT JOIN ue ON (ev.id_ue = ue.id_ue OR ec.id_ue = ue.id_ue)
                    WHERE ev.note IS NOT NULL AND ev.note >= 0
                    ORDER BY ev.id_semestre, ev.credit DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_etd, $id_ac, $num_etd, $id_ac]);
            $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($evaluations)) {
                return [
                    'm1' => 0,
                    'm2' => 0,
                    'mga' => 0,
                    'total_credits' => 0,
                    'statut_academique' => 'Non évalué',
                    'details' => []
                ];
            }

            // Organiser les données par semestre
            $semestres = [];
            foreach ($evaluations as $eval) {
                $semestre_id = $eval['id_semestre'];
                if (!isset($semestres[$semestre_id])) {
                    $semestres[$semestre_id] = [
                        'lib_semestre' => $eval['lib_semestre'],
                        'majeures' => [],
                        'mineures' => []
                    ];
                }

                $credit = $eval['credit_ecue'] ?? $eval['credit_ue'] ?? $eval['credit'] ?? 0;
                $note = $eval['note'];

                if ($credit >= 4) {
                    $semestres[$semestre_id]['majeures'][] = [
                        'note' => $note,
                        'credit' => $credit
                    ];
                } else {
                    $semestres[$semestre_id]['mineures'][] = [
                        'note' => $note,
                        'credit' => $credit
                    ];
                }
            }

            // Calculer les moyennes par semestre
            $m1 = 0; // Moyenne du 1er semestre (UE mineures)
            $m2 = 0; // Moyenne du 2ème semestre (UE majeures)
            $cm1 = 0; // Total crédits UE mineures du 1er semestre
            $cm2 = 0; // Total crédits UE majeures du 2ème semestre
            $total_credits = 0;
            $details = [];

            foreach ($semestres as $semestre_id => $semestre) {
                $moyenne_semestre = 0;
                $total_credit_semestre = 0;
                $notes_semestre = [];

                // Calculer la moyenne des UE mineures (crédits < 4)
                if (!empty($semestre['mineures'])) {
                    $somme_notes_mineures = 0;
                    $total_credit_mineures = 0;
                    foreach ($semestre['mineures'] as $ue) {
                        $somme_notes_mineures += $ue['note'] * $ue['credit'];
                        $total_credit_mineures += $ue['credit'];
                        $notes_semestre[] = "UE mineure: {$ue['note']} (crédit: {$ue['credit']})";
                    }
                    if ($total_credit_mineures > 0) {
                        $moyenne_mineures = $somme_notes_mineures / $total_credit_mineures;
                        $moyenne_semestre += $moyenne_mineures * $total_credit_mineures;
                        $total_credit_semestre += $total_credit_mineures;
                    }
                }

                // Calculer la moyenne des UE majeures (crédits >= 4)
                if (!empty($semestre['majeures'])) {
                    $somme_notes_majeures = 0;
                    $total_credit_majeures = 0;
                    foreach ($semestre['majeures'] as $ue) {
                        $somme_notes_majeures += $ue['note'] * $ue['credit'];
                        $total_credit_majeures += $ue['credit'];
                        $notes_semestre[] = "UE majeure: {$ue['note']} (crédit: {$ue['credit']})";
                    }
                    if ($total_credit_majeures > 0) {
                        $moyenne_majeures = $somme_notes_majeures / $total_credit_majeures;
                        $moyenne_semestre += $moyenne_majeures * $total_credit_majeures;
                        $total_credit_semestre += $total_credit_majeures;
                    }
                }

                // Moyenne du semestre
                if ($total_credit_semestre > 0) {
                    $moyenne_semestre = $moyenne_semestre / $total_credit_semestre;
                }

                // Assigner selon le semestre
                if ($semestre_id % 2 == 1) { // Semestre impair (1, 3, 5, etc.)
                    $m1 = $moyenne_semestre;
                    $cm1 = $total_credit_semestre;
                } else { // Semestre pair (2, 4, 6, etc.)
                    $m2 = $moyenne_semestre;
                    $cm2 = $total_credit_semestre;
                }

                $total_credits += $total_credit_semestre;
                $details[] = [
                    'semestre' => $semestre['lib_semestre'],
                    'moyenne' => round($moyenne_semestre, 2),
                    'total_credits' => $total_credit_semestre,
                    'notes' => $notes_semestre
                ];
            }

            // Calculer la moyenne générale annuelle selon le niveau
            $mga = 0;
            if (($cm1 + $cm2) > 0) {
                if ($is_master2) {
                    // Formule spécifique pour Master 2: (Moyenne M1 Sem2 × 2 + moyenne M2 Sem1 × 3)/5
                    // Récupérer la moyenne M1 Sem2 de l'année précédente
                    $moyenne_m1_sem2 = $this->getMoyenneM1Sem2($num_etd, $id_ac);

                    if ($moyenne_m1_sem2 > 0) {
                        $mga = (($moyenne_m1_sem2 * 2) + ($m1 * 3)) / 5;
                    } else {
                        // Si pas de moyenne M1 disponible, utiliser la formule standard
                        $mga = (($m1 * 30) + ($m2 * 30)) / 60;
                    }
                } else {
                    // Formule standard pour les autres niveaux
                    $mga = (($m1 * 30) + ($m2 * 30)) / 60;
                }
            }

            // Déterminer le statut académique
            $statut_academique = $this->determinerStatutAcademique($evaluations, $mga, $total_credits);

            return [
                'm1' => round($m1, 2),
                'm2' => round($m2, 2),
                'mga' => round($mga, 2),
                'cm1' => $cm1,
                'cm2' => $cm2,
                'total_credits' => $total_credits,
                'statut_academique' => $statut_academique,
                'details' => $details
            ];
        } catch (PDOException $e) {
            error_log("Erreur calcul moyenne générale: " . $e->getMessage());
            return [
                'm1' => 0,
                'm2' => 0,
                'mga' => 0,
                'total_credits' => 0,
                'statut_academique' => 'Erreur',
                'details' => []
            ];
        }
    }

    /**
     * Récupérer la moyenne M1 Sem2 de l'année précédente pour un étudiant Master 2
     */
    private function getMoyenneM1Sem2($num_etd, $id_ac)
    {
        try {
            // Récupérer l'année académique précédente
            $stmt = $this->db->prepare("
                SELECT id_ac 
                FROM annee_academique 
                WHERE id_ac < ? 
                ORDER BY id_ac DESC 
                LIMIT 1
            ");
            $stmt->execute([$id_ac]);
            $id_ac_precedente = $stmt->fetchColumn();

            if (!$id_ac_precedente) {
                return 0; // Pas d'année précédente
            }

            // Récupérer la moyenne du semestre 2 de l'année précédente
            $stmt = $this->db->prepare("
                SELECT moyenne_generale 
                FROM moyenne_generale 
                WHERE num_etd = ? AND id_ac = ? AND id_semestre = 8
            ");
            $stmt->execute([$num_etd, $id_ac_precedente]);
            $moyenne = $stmt->fetchColumn();

            return $moyenne ? floatval($moyenne) : 0;
        } catch (PDOException $e) {
            error_log("Erreur récupération moyenne M1 Sem2: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Déterminer le statut académique selon les règles définies
     */
    private function determinerStatutAcademique($evaluations, $mga, $total_credits)
    {
        // Vérifier s'il y a des notes < 5
        $notes_inferieures_5 = false;
        $moyenne_ue = 0;
        $moyenne_ecue = 0;
        $total_credits_ue = 0;
        $total_credits_ecue = 0;

        foreach ($evaluations as $eval) {
            $note = $eval['note'];
            $credit = $eval['credit'] ?? 0;

            // Vérifier les notes < 5
            if ($note < 5) {
                $notes_inferieures_5 = true;
            }

            // Calculer les moyennes UE et ECUE
            if ($credit >= 4) { // UE majeure
                $moyenne_ue += $note * $credit;
                $total_credits_ue += $credit;
            } else { // ECUE ou UE mineure
                $moyenne_ecue += $note * $credit;
                $total_credits_ecue += $credit;
            }
        }

        // Calculer les moyennes finales
        if ($total_credits_ue > 0) {
            $moyenne_ue = $moyenne_ue / $total_credits_ue;
        }
        if ($total_credits_ecue > 0) {
            $moyenne_ecue = $moyenne_ecue / $total_credits_ecue;
        }

        // Appliquer les règles de statut
        if ($total_credits >= 48 && !$notes_inferieures_5) {
            return 'Autorisé';
        } elseif ($moyenne_ue >= 10 && $moyenne_ecue >= 10 && !$notes_inferieures_5) {
            return 'Validé';
        } else {
            return 'Ajourné';
        }
    }

    /**
     * Mettre à jour ou créer une entrée dans moyenne_generale
     */
    public function updateMoyenneGenerale($num_etd, $id_ac, $id_semestre, $moyenne_generale, $total_credits_obtenus, $total_credits_inscrits, $statut_academique)
    {
        try {
            // Vérifier si une entrée existe déjà
            $sql_check = "SELECT id_moyenne FROM moyenne_generale 
                         WHERE num_etd = ? AND id_ac = ? AND id_semestre = ?";
            $stmt_check = $this->db->prepare($sql_check);
            $stmt_check->execute([$num_etd, $id_ac, $id_semestre]);
            $exists = $stmt_check->fetchColumn();

            if ($exists) {
                // Mettre à jour l'entrée existante
                $sql = "UPDATE moyenne_generale 
                        SET moyenne_generale = ?, total_credits_obtenus = ?, 
                            total_credits_inscrits = ?, statut_academique = ?, 
                            date_mise_a_jour = NOW()
                        WHERE num_etd = ? AND id_ac = ? AND id_semestre = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$moyenne_generale, $total_credits_obtenus, $total_credits_inscrits, $statut_academique, $num_etd, $id_ac, $id_semestre]);
            } else {
                // Créer une nouvelle entrée
                $sql = "INSERT INTO moyenne_generale 
                        (num_etd, id_ac, id_semestre, moyenne_generale, total_credits_obtenus, 
                         total_credits_inscrits, statut_academique, date_calcul)
                        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$num_etd, $id_ac, $id_semestre, $moyenne_generale, $total_credits_obtenus, $total_credits_inscrits, $statut_academique]);
            }
        } catch (PDOException $e) {
            error_log("Erreur mise à jour moyenne générale: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculer et mettre à jour automatiquement toutes les moyennes générales
     */
    public function calculerEtMettreAJourMoyennesGenerales($id_ac = null)
    {
        try {
            if (!$id_ac) {
                $id_ac = $this->getAnneeAcademiqueEnCours();
            }

            if (!$id_ac) {
                throw new Exception("Aucune année académique en cours trouvée");
            }

            // Récupérer tous les étudiants avec des évaluations pour cette année
            $sql = "SELECT DISTINCT CAST(num_etd AS UNSIGNED) as num_etd
                    FROM (
                        SELECT CAST(num_etd AS UNSIGNED) as num_etd FROM evaluer_ecue WHERE id_ac = ?
                        UNION
                        SELECT CAST(num_etd AS UNSIGNED) as num_etd FROM evaluer_ue WHERE id_ac = ?
                    ) as all_evaluations";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_ac, $id_ac]);
            $etudiants = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $success_count = 0;
            $error_count = 0;

            foreach ($etudiants as $num_etd) {
                try {
                    // Calculer la moyenne générale pour cet étudiant
                    $resultat = $this->calculerMoyenneGenerale($num_etd, $id_ac);

                    if ($resultat['mga'] > 0) {
                        // Récupérer le semestre le plus récent
                        $sql_semestre = "SELECT id_semestre FROM semestre 
                                       WHERE id_niv_etd = (SELECT id_niv_etd FROM etudiants WHERE num_etd = ?)
                                       ORDER BY id_semestre DESC LIMIT 1";
                        $stmt_semestre = $this->db->prepare($sql_semestre);
                        $stmt_semestre->execute([$num_etd]);
                        $id_semestre = $stmt_semestre->fetchColumn() ?: 1;

                        // Mettre à jour la moyenne générale
                        $success = $this->updateMoyenneGenerale(
                            $num_etd,
                            $id_ac,
                            $id_semestre,
                            $resultat['mga'],
                            $resultat['total_credits'],
                            $resultat['total_credits'], // Pour simplifier, on utilise le même nombre
                            $resultat['statut_academique']
                        );

                        if ($success) {
                            $success_count++;
                        } else {
                            $error_count++;
                        }
                    }
                } catch (Exception $e) {
                    error_log("Erreur calcul moyenne pour étudiant $num_etd: " . $e->getMessage());
                    $error_count++;
                }
            }

            return [
                'success' => true,
                'success_count' => $success_count,
                'error_count' => $error_count,
                'total_etudiants' => count($etudiants)
            ];
        } catch (Exception $e) {
            error_log("Erreur calcul moyennes générales: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }


    public function getEtudiantCheval($id_ac)
    {
        $sql = "SELECT e.num_etd, e.nom, e.prenom, e.email, e.telephone, e.date_naissance, e.sexe, e.id_niv_etd, e.id_promotion, mg.moyenne_generale, mg.statut_academique
                FROM etudiants e
                JOIN moyenne_generale mg ON e.num_etd = mg.num_etd
                WHERE mg.id_ac = ? AND e.id_statut = 2 
                ORDER BY e.nom_etd ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ac]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStatutEtudiant()
    {
        $sql = "SELECT id_statut, lib_statut FROM statut_etudiant ORDER BY id_statut";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSemestreEtudiant($num_etd, $id_ac = null)
    {
        try {
            // Récupérer le semestre le plus récent pour l'étudiant
            $sql = "SELECT s.id_semestre 
                    FROM semestre s
                    JOIN etudiants e ON s.id_niv_etd = e.id_niv_etd
                    WHERE e.num_etd = ?
                    ORDER BY s.id_semestre DESC 
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$num_etd]);
            $semestre = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($semestre) {
                return $semestre['id_semestre'];
            } else {
                // Retourner le semestre par défaut (1) si aucun n'est trouvé
                return 1;
            }
        } catch (PDOException $e) {
            error_log("Erreur récupération semestre étudiant: " . $e->getMessage());
            return 1; // Semestre par défaut
        }
    }
}
