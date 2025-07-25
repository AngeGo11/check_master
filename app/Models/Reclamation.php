<?php
namespace App\Models;

use PDO;
use PDOException;

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

            $sql = "INSERT INTO reclamations (num_etd, motif_reclamation, matieres, piece_jointe, date_reclamation, statut_reclamation) 
                    VALUES (?, ?, ?, ?, CURDATE(), 'En attente')";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
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
                return $r['statut_reclamation'] === 'En attente' || $r['statut_reclamation'] === 'En cours';
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

    // CREATE
    public function ajouterReclamation($sujet, $description, $etudiant_id, $date_creation) {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO reclamation (sujet, description, etudiant_id, date_creation) VALUES (:sujet, :description, :etudiant_id, :date_creation)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':sujet', $sujet);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':etudiant_id', $etudiant_id);
            $stmt->bindParam(':date_creation', $date_creation);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout réclamation: " . $e->getMessage());
            return false;
        }
    }

    // READ
    public function getAllReclamations() {
        $query = "SELECT * FROM reclamation ORDER BY date_creation DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getReclamationById($id) {
        $query = "SELECT * FROM reclamation WHERE id_reclamation = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getReclamationsByEtudiant($etudiant_id) {
        $query = "SELECT * FROM reclamation WHERE etudiant_id = :etudiant_id ORDER BY date_creation DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':etudiant_id', $etudiant_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // UPDATE
    public function modifierReclamation($id, $sujet, $description, $statut) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE reclamation SET sujet = :sujet, description = :description, statut = :statut WHERE id_reclamation = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':sujet', $sujet);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification réclamation: " . $e->getMessage());
            return false;
        }
    }

    // DELETE
    public function supprimerReclamation($id) {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM reclamation WHERE id_reclamation = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression réclamation: " . $e->getMessage());
            return false;
        }
    }

    public function traiterReclamation($id, $reponse, $statut) {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE reclamation SET reponse = :reponse, statut = :statut, date_traitement = NOW() WHERE id_reclamation = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':reponse', $reponse);
            $stmt->bindParam(':statut', $statut);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur traitement réclamation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer toutes les réclamations avec pagination pour la vue admin
     */
    public function getAllReclamationsWithPagination() {
        try {
            // Paramètres de pagination
            $page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
            $offset = ($page - 1) * $perPage;

            // Filtres
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
            $statusFilter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';

            // Construction de la requête
            $whereConditions = [];
            $params = [];

            if (!empty($search)) {
                $whereConditions[] = "(sujet LIKE :search OR description LIKE :search)";
                $params[':search'] = "%$search%";
            }

            if (!empty($dateFilter)) {
                $whereConditions[] = "DATE(date_creation) = :date_filter";
                $params[':date_filter'] = $dateFilter;
            }

            if (!empty($statusFilter)) {
                $whereConditions[] = "statut = :status_filter";
                $params[':status_filter'] = $statusFilter;
            }

            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

            // Requête principale
            $sql = "SELECT r.*, e.nom_etd, e.prenom_etd 
                    FROM reclamation r 
                    LEFT JOIN etudiants e ON r.etudiant_id = e.num_etd 
                    $whereClause 
                    ORDER BY r.date_creation DESC 
                    LIMIT :perPage OFFSET :offset";

            $stmt = $this->db->prepare($sql);
            
            // Binding des paramètres
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Comptage total pour la pagination
            $countSql = "SELECT COUNT(*) FROM reclamation r $whereClause";
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $totalRecords = $countStmt->fetchColumn();

            // Statistiques
            $statsSql = "SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
                            SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
                            SUM(CASE WHEN statut = 'resolu' THEN 1 ELSE 0 END) as resolu
                         FROM reclamation";
            $statsStmt = $this->db->prepare($statsSql);
            $statsStmt->execute();
            $statistics = $statsStmt->fetch(PDO::FETCH_ASSOC);

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