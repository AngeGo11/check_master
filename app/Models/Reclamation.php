<?php
namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Reclamation {
    private $db;
    public function __construct($db) { $this->db = $db; }

    /**
     * Récupérer les données de l'étudiant pour les réclamations
     */
    public function getStudentData($userId)
    {
        try {
            $sql = "SELECT * FROM etudiants e 
                    JOIN utilisateur u ON u.login_utilisateur = e.email_etd
                    WHERE id_utilisateur = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération données étudiant réclamation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer le niveau d'étude de l'étudiant
     */
    public function getStudentLevel($studentId)
    {
        try {
            $query = "SELECT n.lib_niv_etd 
                      FROM etudiants e
                      JOIN niveau_etude n ON n.id_niv_etd = e.id_niv_etd
                      WHERE e.num_etd = ?";

            $stmt = $this->db->prepare($query);
            $stmt->execute([$studentId]);
            return $stmt->fetchColumn() ?: 'Non défini';
        } catch (PDOException $e) {
            error_log("Erreur récupération niveau étude: " . $e->getMessage());
            return 'Non défini';
        }
    }

    /**
     * Récupérer les réclamations d'un étudiant
     */
    public function getStudentReclamations($studentId)
    {
        try {
            $sql = "SELECT r.*,  
                    DATE_FORMAT(r.date_reclamation, '%d/%m/%Y') as date_creation_reclamation
                    FROM reclamations r
                    WHERE r.num_etd = ?
                    ORDER BY r.date_reclamation DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération réclamations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Créer une nouvelle réclamation
     */
    public function createReclamation($studentId, $data)
    {
        try {
            $this->db->beginTransaction();

            // Récupération de l'année académique en cours
            $query = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $id_ac = $result['id_ac'];

            $sql = "INSERT INTO reclamations (id_ac, num_etd, motif_reclamation, matieres, piece_jointe, date_reclamation, statut_reclamation) 
                    VALUES (?, ?, ?, ?, ?, CURDATE(), 'En attente de traitement')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $id_ac,
                $studentId,
                $data['motif_reclamation'],
                json_encode($data['noms_matieres']),
                $data['piece_jointe'] ?? null
            ]);

            $this->db->commit();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur création réclamation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les réclamations en cours
     */
    public function getReclamationsEnCours($studentId)
    {
        try {
            $reclamations = $this->getStudentReclamations($studentId);
            return array_filter($reclamations, function ($r) {
                return $r['statut_reclamation'] === 'En attente de traitement';
            });
        } catch (PDOException $e) {
            error_log("Erreur récupération réclamations en cours: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Filtrer les réclamations par statut
     */
    public function filterReclamationsByStatus($studentId, $status)
    {
        try {
            $reclamations = $this->getStudentReclamations($studentId);
            
            if (empty($status)) {
                return $reclamations;
            }

            return array_filter($reclamations, function ($r) use ($status) {
                $reclamationStatus = strtolower(str_replace(' ', '-', $r['statut_reclamation']));
                return $reclamationStatus === $status;
            });
        } catch (PDOException $e) {
            error_log("Erreur filtrage réclamations: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les statistiques des réclamations
     */
    public function getStatistics($userGroup = '')
    {
        try {
            // Récupération de l'année académique en cours
            $query = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $id_ac = $result['id_ac'];

            $stats = [
                'total' => 0,
                'en_cours' => 0,
                'resolues' => 0
            ];

            // Construire la condition de filtrage selon le groupe utilisateur
            $userFilter = '';
            if (!empty($userGroup)) {
                switch ($userGroup) {
                    case 'Responsable scolarité':
                        // Le responsable scolarité voit toutes les réclamations
                        break;
                    case 'Responsable filière':
                    case 'Administrateur plateforme':
                        // Le responsable filière et l'admin voient seulement les réclamations traitées par le responsable scolarité
                        $userFilter = " AND r.statut_reclamation = 'Traitée par le responsable de scolarité'";
                        break;
                    default:
                        // Par défaut, ne voir que les réclamations en attente
                        $userFilter = " AND r.statut_reclamation = 'En attente de traitement'";
                        break;
                }
            }

            // Total des réclamations (selon le filtre utilisateur)
            $sql_total = "SELECT COUNT(*) as total FROM reclamations r WHERE r.id_ac = ?" . $userFilter;
            $stmt_total = $this->db->prepare($sql_total);
            $stmt_total->execute([$id_ac]);
            $stats['total'] = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

            // Réclamations en cours (selon le filtre utilisateur)
            if (empty($userGroup) || $userGroup == 'Responsable scolarité') {
                // Pour le responsable scolarité, compter les réclamations en attente
                $sql_en_cours = "SELECT COUNT(*) as en_cours FROM reclamations WHERE id_ac = ? AND statut_reclamation = 'En attente de traitement'";
                $stmt_en_cours = $this->db->prepare($sql_en_cours);
                $stmt_en_cours->execute([$id_ac]);
                $stats['en_cours'] = $stmt_en_cours->fetch(PDO::FETCH_ASSOC)['en_cours'];
            } else {
                // Pour les autres, compter les réclamations traitées par le responsable scolarité
                $sql_en_cours = "SELECT COUNT(*) as en_cours FROM reclamations WHERE id_ac = ? AND statut_reclamation = 'Traitée par le responsable de scolarité'";
                $stmt_en_cours = $this->db->prepare($sql_en_cours);
                $stmt_en_cours->execute([$id_ac]);
                $stats['en_cours'] = $stmt_en_cours->fetch(PDO::FETCH_ASSOC)['en_cours'];
            }

            // Réclamations résolues (selon le filtre utilisateur)
            if (empty($userGroup) || $userGroup == 'Responsable scolarité') {
                // Pour le responsable scolarité, compter toutes les réclamations traitées
                $sql_resolues = "SELECT COUNT(*) as resolues FROM reclamations WHERE id_ac = ? AND (statut_reclamation = 'Traitée par le responsable de scolarité' OR statut_reclamation = 'Traitée par le responsable de filière')";
                $stmt_resolues = $this->db->prepare($sql_resolues);
                $stmt_resolues->execute([$id_ac]);
                $stats['resolues'] = $stmt_resolues->fetch(PDO::FETCH_ASSOC)['resolues'];
            } else {
                // Pour les autres, compter les réclamations traitées par le responsable filière
                $sql_resolues = "SELECT COUNT(*) as resolues FROM reclamations WHERE id_ac = ? AND statut_reclamation = 'Traitée par le responsable de filière'";
                $stmt_resolues = $this->db->prepare($sql_resolues);
                $stmt_resolues->execute([$id_ac]);
                $stats['resolues'] = $stmt_resolues->fetch(PDO::FETCH_ASSOC)['resolues'];
            }

            return $stats;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des statistiques : " . $e->getMessage());
            return [
                'total' => 0,
                'en_cours' => 0,
                'resolues' => 0
            ];
        }
    }

    /**
     * Récupérer les réclamations avec filtres pour la vue admin
     */
    public function getReclamationsWithFilters($search = '', $date_filter = '', $status_filter = '', $userGroup = '')
    {
        try {
            // Récupération de l'année académique en cours
            $query = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours'";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $id_ac = $result['id_ac'];
            
            $params = [$id_ac];

            $sql = "SELECT r.*,
                            e.nom_etd,
                            e.prenom_etd,
                            e.num_carte_etd
                            FROM reclamations r
                            JOIN etudiants e ON e.num_etd = r.num_etd
                            WHERE r.id_ac = ?";

            if (!empty($search)) {
                $sql .= " AND (e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR r.motif_reclamation LIKE ?)";
                $search_param = "%$search%";
                $params = array_merge($params, [$search_param, $search_param, $search_param]);
            }

            if (!empty($date_filter)) {
                switch ($date_filter) {
                    case 'today':
                        $sql .= " AND DATE(r.date_reclamation) = CURDATE()";
                        break;
                    case 'week':
                        $sql .= " AND YEARWEEK(r.date_reclamation) = YEARWEEK(CURDATE())";
                        break;
                    case 'month':
                        $sql .= " AND MONTH(r.date_reclamation) = MONTH(CURDATE()) AND YEAR(r.date_reclamation) = YEAR(CURDATE())";
                        break;
                }
            }

            if (!empty($status_filter)) {
                $sql .= " AND r.statut_reclamation = ?";
                $params[] = $status_filter;
            }

            // Filtrer selon le groupe utilisateur
            if (!empty($userGroup)) {
                switch ($userGroup) {
                    case 'Responsable scolarité':
                        // Le responsable scolarité voit toutes les réclamations
                        break;
                    case 'Responsable filière':
                    case 'Administrateur plateforme':
                        // Le responsable filière et l'admin voient seulement les réclamations traitées par le responsable scolarité
                        $sql .= " AND r.statut_reclamation = 'Traitée par le responsable de scolarité'";
                        break;
                    default:
                        // Par défaut, ne voir que les réclamations en attente
                        $sql .= " AND r.statut_reclamation = 'En attente de traitement'";
                        break;
                }
            }

            $sql .= " ORDER BY r.date_reclamation DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des réclamations : " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer les détails d'une réclamation
     */
    public function getReclamationDetails($id)
    {
        try {
            $sql = "SELECT r.*, e.nom_etd, e.prenom_etd, e.num_carte_etd, n.lib_niv_etd,
                    DATE_FORMAT(r.date_reclamation, '%d/%m/%Y') as date_reclamation
                    FROM reclamations r
                    JOIN etudiants e ON e.num_etd = r.num_etd
                    JOIN niveau_etude n ON n.id_niv_etd = e.id_niv_etd
                    WHERE r.id_reclamation = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur récupération détails réclamation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Traiter une réclamation (changer le statut)
     */
    /**
     * Traiter une réclamation selon le type d'utilisateur
     */
    public function traiterReclamation($reclamationId, $commentaire, $userGroup)
    {
        try {
            $this->db->beginTransaction();
            
            $nouveauStatut = '';
            $commentaireField = '';
            
            // Déterminer le nouveau statut selon le groupe utilisateur
            switch ($userGroup) {
                case 'Responsable scolarité':
                    $nouveauStatut = 'Traitée par le responsable de scolarité';
                    $commentaireField = 'retour_traitement';
                    break;
                case 'Responsable filière':
                    $nouveauStatut = 'Traitée par le responsable de filière';
                    $commentaireField = 'retour_traitement';
                    break;
                case 'Administrateur plateforme':
                    $nouveauStatut = 'Traitée par le responsable de filière';
                    $commentaireField = 'retour_traitement';
                    break;
                default:
                    throw new Exception("Groupe utilisateur non autorisé pour le traitement");
            }
            
            $sql = "UPDATE reclamations SET 
                    statut_reclamation = ?,
                    $commentaireField = ?,
                    date_traitement = NOW()
                    WHERE id_reclamation = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$nouveauStatut, $commentaire, $reclamationId]);

            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors du traitement de la réclamation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Transférer une réclamation vers un niveau supérieur
     */
    public function transfererReclamation($reclamationId, $commentaireTransfert, $userGroup)
    {
        try {
            $this->db->beginTransaction();
            
            $nouveauStatut = '';
            
            // Déterminer le nouveau statut selon le groupe utilisateur qui transfère
            switch ($userGroup) {
                case 'Responsable scolarité':
                    $nouveauStatut = 'Traitée par le responsable de scolarité';
                    break;
                default:
                    throw new Exception("Groupe utilisateur non autorisé pour le transfert");
            }
            
            $sql = "UPDATE reclamations SET 
                    statut_reclamation = ?,
                    retour_traitement = ?,
                    date_traitement = NOW()
                    WHERE id_reclamation = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$nouveauStatut, $commentaireTransfert, $reclamationId]);

            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Erreur lors du transfert de la réclamation : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une ou plusieurs réclamations
     */
    public function supprimerReclamations($reclamationIds)
    {
        try {
            $this->db->beginTransaction();

            // Récupérer les chemins des fichiers à supprimer
            $placeholders = implode(',', array_fill(0, count($reclamationIds), '?'));
            $sql_files = "SELECT piece_jointe FROM reclamations WHERE id_reclamation IN ($placeholders) AND piece_jointe IS NOT NULL";
            $stmt_files = $this->db->prepare($sql_files);
            $stmt_files->execute($reclamationIds);
            $files_to_delete = $stmt_files->fetchAll(PDO::FETCH_COLUMN);

            // Supprimer les réclamations
            $sql_delete = "DELETE FROM reclamations WHERE id_reclamation IN ($placeholders)";
            $stmt_delete = $this->db->prepare($sql_delete);
            $stmt_delete->execute($reclamationIds);
            $deleted_count = $stmt_delete->rowCount();

            // Supprimer les fichiers physiques
            foreach ($files_to_delete as $file_path) {
                if ($file_path && file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $this->db->commit();
            return $deleted_count;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur lors de la suppression des réclamations : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer toutes les réclamations avec pagination pour la vue admin
     */
    public function getAllReclamationsWithPagination($userGroup = '') {
        try {
            // Paramètres de pagination
            $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
            $offset = ($page - 1) * $perPage;

            // Filtres
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
            $statusFilter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

            // Récupération des réclamations avec filtres
            $reclamations = $this->getReclamationsWithFilters($search, $dateFilter, $statusFilter, $userGroup);
            
            // Pagination manuelle
            $totalRecords = count($reclamations);
            $reclamations = array_slice($reclamations, $offset, $perPage);

            // Statistiques
            $statistics = $this->getStatistics($userGroup);

            return [
                'reclamations' => $reclamations,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_records' => $totalRecords,
                    'total_pages' => ceil($totalRecords / $perPage)
                ],
                'statistics' => $statistics,
                'filters' => [
                    'search' => $search,
                    'date_filter' => $dateFilter,
                    'status_filter' => $statusFilter
                ]
            ];

        } catch (PDOException $e) {
            error_log("Erreur récupération réclamations: " . $e->getMessage());
            return [
                'reclamations' => [],
                'pagination' => [],
                'statistics' => [],
                'filters' => [],
                'error' => 'Erreur lors de la récupération des réclamations'
            ];
        }
    }
} 