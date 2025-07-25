<?php

namespace App\Models;

use PDO;
use PDOException;

class Reglement
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Récupérer tous les règlements avec pagination et filtres
    public function getAllReglements($search = '', $filter_niveau = '', $filter_statut = '', $filter_date = '', $page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(e.num_carte_etd LIKE ? OR e.nom_etd LIKE ? OR e.prenom_etd LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param]);
        }
        if ($filter_niveau !== '') {
            $where[] = "n.lib_niv_etd = ?";
            $params[] = $filter_niveau;
        }
        if ($filter_statut !== '') {
            if ($filter_statut === 'paye') {
                $where[] = "r.reste_a_payer = 0";
            } elseif ($filter_statut === 'partiel') {
                $where[] = "r.reste_a_payer > 0 AND r.reste_a_payer < r.montant_a_payer";
            } elseif ($filter_statut === 'nonpaye') {
                $where[] = "r.reste_a_payer = r.montant_a_payer";
            }
        }
        if ($filter_date !== '') {
            if ($filter_date === 'this-month') {
                $where[] = "MONTH(r.date_reglement) = MONTH(CURDATE()) AND YEAR(r.date_reglement) = YEAR(CURDATE())";
            } elseif ($filter_date === 'last-month') {
                $where[] = "MONTH(r.date_reglement) = MONTH(CURDATE())-1 AND YEAR(r.date_reglement) = YEAR(CURDATE())";
            }
        }

        $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Requête de comptage pour la pagination
        $count_sql = "SELECT COUNT(*) FROM (
            SELECT r.id_reglement
            FROM reglement r
            JOIN (
                SELECT MAX(id_reglement) AS latest_id
                FROM reglement
                GROUP BY num_etd
            ) latest ON r.id_reglement = latest.latest_id
            JOIN etudiants e ON r.num_etd = e.num_etd
            LEFT JOIN niveau_etude n ON r.id_niv_etd = n.id_niv_etd
            $where_sql
        ) AS sub";

        $count_stmt = $this->db->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetchColumn();

        // Requête principale avec filtres et pagination
        $sql = "
            SELECT 
                e.num_etd,
                e.num_carte_etd,
                e.nom_etd,
                e.prenom_etd,
                n.lib_niv_etd,
                r.id_reglement,
                r.numero_reglement,
                r.montant_a_payer,
                r.total_paye,
                r.reste_a_payer,
                r.date_reglement,
                r.statut
            FROM reglement r
            JOIN (
                SELECT MAX(id_reglement) AS latest_id
                FROM reglement
                GROUP BY num_etd
            ) latest ON r.id_reglement = latest.latest_id
            JOIN etudiants e ON r.num_etd = e.num_etd
            LEFT JOIN niveau_etude n ON r.id_niv_etd = n.id_niv_etd
            $where_sql
            ORDER BY r.date_reglement DESC
            LIMIT $limit OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return [
            'reglements' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total_records' => $total_records,
            'total_pages' => ceil($total_records / $limit)
        ];
    }

    // Récupérer les statistiques des règlements
    public function getReglementStats()
    {
        try {
            // Total étudiants
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM etudiants");
            $stmt->execute();
            $totalEtudiants = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Étudiants ayant payé partiellement
            $stmt = $this->db->prepare("SELECT COUNT(*) as count 
                                      FROM reglement 
                                      WHERE reste_a_payer > 0 AND reste_a_payer < montant_a_payer");
            $stmt->execute();
            $partiellementPaye = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Étudiants ayant tout payé
            $stmt = $this->db->prepare("SELECT COUNT(*) as count 
                                      FROM reglement 
                                      WHERE reste_a_payer = 0");
            $stmt->execute();
            $toutPaye = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Étudiants n'ayant rien payé
            $stmt = $this->db->prepare("SELECT COUNT(*) as count 
                                      FROM reglement 
                                      WHERE reste_a_payer = montant_a_payer");
            $stmt->execute();
            $rienPaye = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            return [
                'total_etudiants' => $totalEtudiants,
                'partiellement_paye' => $partiellementPaye,
                'tout_paye' => $toutPaye,
                'rien_paye' => $rienPaye
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération statistiques règlements: " . $e->getMessage());
            return [
                'total_etudiants' => 0,
                'partiellement_paye' => 0,
                'tout_paye' => 0,
                'rien_paye' => 0
            ];
        }
    }

    // Récupérer les informations d'un étudiant par numéro de carte
    public function getEtudiantInfo($num_carte)
    {
        try {
            // Récupérer l'année académique en cours
            $stmt = $this->db->prepare("SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1");
            $stmt->execute();
            $annee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$annee) {
                return ['error' => 'Aucune année académique en cours trouvée'];
            }
            
            $id_ac = $annee['id_ac'];

            // Requête améliorée pour récupérer les informations complètes
            $stmt = $this->db->prepare("
                SELECT 
                    e.num_etd,
                    e.nom_etd,
                    e.prenom_etd,
                    e.id_promotion,
                    pr.*,
                    fi.montant as montant_inscription,
                    e.id_niv_etd,
                    n.lib_niv_etd,
                    ac.id_ac,
                    COALESCE(r.total_paye, 0) as total_paye,
                    COALESCE(r.reste_a_payer, fi.montant) as reste_a_payer,
                    r.id_reglement,
                    r.statut,
                    r.date_reglement,
                    r.numero_reglement
                FROM etudiants e
                LEFT JOIN frais_inscription fi ON e.id_niv_etd = fi.id_niv_etd AND fi.id_ac = ?
                LEFT JOIN promotion pr ON pr.id_promotion = e.id_promotion
                LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd
                LEFT JOIN annee_academique ac ON ac.id_ac = fi.id_ac
                LEFT JOIN reglement r ON e.num_etd = r.num_etd 
                    AND r.id_ac = ? 
                    AND r.id_niv_etd = e.id_niv_etd
                    AND r.id_reglement = (
                        SELECT MAX(id_reglement)
                        FROM reglement
                        WHERE num_etd = e.num_etd
                        AND id_ac = ?
                        AND id_niv_etd = e.id_niv_etd
                    )
                WHERE e.num_carte_etd = ?
            ");

            $stmt->execute([$id_ac, $id_ac, $id_ac, $num_carte]);
            $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$etudiant) {
                return ['error' => "Étudiant non trouvé avec le numéro de carte : " . $num_carte];
            }

            if (!$etudiant['montant_inscription']) {
                return ['error' => "Aucun montant de frais d'inscription trouvé pour ce niveau"];
            }

            // Calculer le reste à payer correctement
            $montant_total = $etudiant['montant_inscription'];
            $total_paye = $etudiant['total_paye'] ?? 0;
            $reste_a_payer = max(0, $montant_total - $total_paye);

            return [
                'success' => true,
                'nom_etd' => $etudiant['nom_etd'],
                'prenom_etd' => $etudiant['prenom_etd'],
                'id_niv_etd' => $etudiant['id_niv_etd'],
                'montant' => $montant_total,
                'total_paye' => $total_paye,
                'reste_a_payer' => $reste_a_payer,
                'numero_reglement' => $etudiant['numero_reglement'] ?? null,
                'statut' => $etudiant['statut'] ?? 'Non payé'
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération info étudiant: " . $e->getMessage());
            return ['error' => 'Erreur lors de la récupération des informations de l\'étudiant'];
        }
    }

    // Récupérer le montant des frais d'inscription par niveau
    public function getMontantTarif($niveau_id)
    {
        try {
            // Récupérer l'année académique en cours
            $stmt = $this->db->prepare("SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1");
            $stmt->execute();
            $annee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$annee) {
                return ['error' => 'Aucune année académique en cours trouvée'];
            }
            
            $id_ac = $annee['id_ac'];

            $stmt = $this->db->prepare("SELECT montant FROM frais_inscription WHERE id_niv_etd = ? AND id_ac = ?");
            $stmt->execute([$niveau_id, $id_ac]);
            $frais = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$frais) {
                return ['error' => 'Aucun tarif défini pour ce niveau et cette année académique'];
            }

            return [
                'success' => true,
                'montant' => $frais['montant']
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération montant tarif: " . $e->getMessage());
            return ['error' => 'Erreur lors de la récupération du montant'];
        }
    }

    // Récupérer l'historique des paiements
    public function getPaymentHistory($numero_reglement)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    pr.date_paiement,
                    pr.montant_paye,
                    pr.mode_de_paiement,
                    pr.motif_paiement,
                    pr.numero_cheque,
                    pr.numero_recu,
                    r.numero_reglement,
                    r.total_paye,
                    r.reste_a_payer
                FROM paiement_reglement pr
                JOIN reglement r ON pr.id_reglement = r.id_reglement
                WHERE r.numero_reglement = ?
                ORDER BY pr.date_paiement ASC
            ");
            $stmt->execute([$numero_reglement]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération historique paiements: " . $e->getMessage());
            return [];
        }
    }

    // Récupérer tous les niveaux d'étude
    public function getAllNiveaux()
    {
        try {
            $stmt = $this->db->query("SELECT id_niv_etd, lib_niv_etd FROM niveau_etude ORDER BY lib_niv_etd");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération niveaux: " . $e->getMessage());
            return [];
        }
    }
} 