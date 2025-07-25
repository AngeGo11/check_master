<?php

namespace App\Models;
use PDO;
use PDOException;
use Exception;

require_once __DIR__ . '/../../config/config.php';
// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');

class ValidationModel {
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Récupère les rapports en attente de validation
     */
    public function getRapportsEnAttente($search = '', $date_filter = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $where = ["re.statut_rapport = 'En attente de validation'"];
        $params = [];

        if (!empty($search)) {
            $where[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR re.nom_rapport LIKE ? OR re.theme_memoire LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }

        if (!empty($date_filter)) {
            switch ($date_filter) {
                case 'today':
                    $where[] = "DATE(re.date_rapport) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "re.date_rapport >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "re.date_rapport >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
                case 'year':
                    $where[] = "YEAR(re.date_rapport) = YEAR(CURDATE())";
                    break;
            }
        }

        $where_clause = implode(" AND ", $where);

        // Requête de comptage simplifiée
        $count_sql = "SELECT COUNT(*) as total 
                     FROM rapport_etudiant re
                     LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                     WHERE $where_clause";
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Requête principale simplifiée
        $sql = "SELECT re.*, e.nom_etd, e.prenom_etd, e.email_etd,
                       d.date_depot, a.date_approbation, a.com_appr
                FROM rapport_etudiant re
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN approuver a ON a.id_rapport_etd = re.id_rapport_etd
                WHERE $where_clause
                ORDER BY re.date_rapport DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les statistiques de validation séparément pour éviter les boucles
        foreach ($rapports as &$rapport) {
            $rapport['nb_validations'] = $this->getNbValidations($rapport['id_rapport_etd']);
            $rapport['nb_rejets'] = $this->getNbRejets($rapport['id_rapport_etd']);
        }

        return [
            'rapports' => $rapports,
            'total' => $total_records,
            'pages' => ceil($total_records / $limit)
        ];
    }

    /**
     * Récupère le nombre de validations pour un rapport
     */
    private function getNbValidations($rapport_id) {
        $sql = "SELECT COUNT(*) FROM valider WHERE id_rapport_etd = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rapport_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Récupère le nombre de rejets pour un rapport
     */
    private function getNbRejets($rapport_id) {
        $sql = "SELECT COUNT(*) FROM valider WHERE id_rapport_etd = ? AND decision = 'Rejeté'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rapport_id]);
        return $stmt->fetchColumn();
    }

    /**
     * Récupère les détails d'un rapport
     */
    public function getRapportDetails($rapport_id) {
        $sql = "SELECT re.*, e.nom_etd, e.prenom_etd, e.email_etd,
                       d.date_depot, a.date_approbation, a.com_appr
                FROM rapport_etudiant re
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN approuver a ON a.id_rapport_etd = re.id_rapport_etd
                WHERE re.id_rapport_etd = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rapport_id]);
        $rapport = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($rapport) {
            $rapport['nb_validations'] = $this->getNbValidations($rapport_id);
            $rapport['nb_rejets'] = $this->getNbRejets($rapport_id);
        }
        
        return $rapport;
    }

    /**
     * Récupère les validations d'un rapport
     */
    public function getValidationsRapport($rapport_id) {
        $sql = "SELECT v.*, e.nom_ens, e.prenoms_ens, e.email_ens
                FROM valider v
                LEFT JOIN enseignants e ON e.id_ens = v.id_ens
                WHERE v.id_rapport_etd = ?
                ORDER BY v.date_validation DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$rapport_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Valide un rapport
     */
    public function validerRapport($rapport_id, $enseignant_id, $decision, $commentaire = null) {
        try {
            $this->pdo->beginTransaction();

            // Vérifier si l'enseignant a déjà validé ce rapport
            $check_sql = "SELECT COUNT(*) FROM valider WHERE id_ens = ? AND id_rapport_etd = ?";
            $check_stmt = $this->pdo->prepare($check_sql);
            $check_stmt->execute([$enseignant_id, $rapport_id]);
            
            if ($check_stmt->fetchColumn() > 0) {
                // Mise à jour de la validation existante
                $sql = "UPDATE valider SET 
                        com_validation = ?, 
                        decision = ?, 
                        date_validation = CURDATE()
                        WHERE id_ens = ? AND id_rapport_etd = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$commentaire, $decision, $enseignant_id, $rapport_id]);
            } else {
                // Nouvelle validation
                $sql = "INSERT INTO valider (id_ens, id_rapport_etd, date_validation, com_validation, decision) 
                        VALUES (?, ?, CURDATE(), ?, ?)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$enseignant_id, $rapport_id, $commentaire, $decision]);
            }

            // Mettre à jour le statut du rapport si nécessaire
            $this->updateRapportStatus($rapport_id);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Erreur lors de la validation : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Met à jour le statut du rapport basé sur les validations
     */
    private function updateRapportStatus($rapport_id) {
        // Compter les validations
        $count_sql = "SELECT 
                        COUNT(*) as total_validations,
                        SUM(CASE WHEN decision = 'Validé' THEN 1 ELSE 0 END) as nb_valides,
                        SUM(CASE WHEN decision = 'Rejeté' THEN 1 ELSE 0 END) as nb_rejets
                     FROM valider 
                     WHERE id_rapport_etd = ?";
        
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute([$rapport_id]);
        $counts = $count_stmt->fetch(PDO::FETCH_ASSOC);

        $new_status = 'En attente de validation';

        if ($counts['total_validations'] > 0) {
            if ($counts['nb_rejets'] > 0) {
                $new_status = 'Rejeté';
            } elseif ($counts['nb_valides'] === 4) { // Au moins 2 validations positives
                $new_status = 'Validé';
            }
        }

        // Mettre à jour le statut
        $update_sql = "UPDATE rapport_etudiant SET statut_rapport = ? WHERE id_rapport_etd = ?";
        $update_stmt = $this->pdo->prepare($update_sql);
        $update_stmt->execute([$new_status, $rapport_id]);
    }

    /**
     * Récupère les statistiques de validation
     */
    public function getValidationStats() {
        $sql = "SELECT 
                    COUNT(*) as total_rapports,
                    SUM(CASE WHEN statut_rapport = 'En attente de validation' THEN 1 ELSE 0 END) as en_attente,
                    SUM(CASE WHEN statut_rapport = 'Validé' THEN 1 ELSE 0 END) as valides,
                    SUM(CASE WHEN statut_rapport = 'Rejeté' THEN 1 ELSE 0 END) as rejetes
                FROM rapport_etudiant";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un enseignant peut valider un rapport
     */
    public function canValidateRapport($rapport_id, $enseignant_id) {
        // Vérifier si l'enseignant a déjà validé ce rapport
        $sql = "SELECT COUNT(*) FROM valider WHERE id_ens = ? AND id_rapport_etd = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$enseignant_id, $rapport_id]);
        
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Récupère les rapports par statut
     */
    public function getRapportsByStatut($statut, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $where = ["re.statut_rapport = ?"];
        $params = [$statut];

        $where_clause = implode(" AND ", $where);

        // Requête de comptage
        $count_sql = "SELECT COUNT(*) as total 
                     FROM rapport_etudiant re
                     LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                     WHERE $where_clause";
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Requête principale
        $sql = "SELECT re.*, e.nom_etd, e.prenom_etd, e.email_etd,
                       d.date_depot, a.date_approbation
                FROM rapport_etudiant re
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN approuver a ON a.id_rapport_etd = re.id_rapport_etd
                WHERE $where_clause
                ORDER BY re.date_rapport DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les statistiques de validation séparément
        foreach ($rapports as &$rapport) {
            $rapport['nb_validations'] = $this->getNbValidations($rapport['id_rapport_etd']);
        }

        return [
            'rapports' => $rapports,
            'total' => $total_records,
            'pages' => ceil($total_records / $limit)
        ];
    }

    /**
     * Recherche de rapports
     */
    public function rechercherRapports($terme_recherche, $filtres = []) {
        $where = [];
        $params = [];

        if (!empty($terme_recherche)) {
            $where[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR re.nom_rapport LIKE ? OR re.theme_memoire LIKE ?)";
            $search_param = "%$terme_recherche%";
            $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
        }

        if (!empty($filtres['statut'])) {
            $where[] = "re.statut_rapport = ?";
            $params[] = $filtres['statut'];
        }

        if (!empty($filtres['date_debut'])) {
            $where[] = "re.date_rapport >= ?";
            $params[] = $filtres['date_debut'];
        }

        if (!empty($filtres['date_fin'])) {
            $where[] = "re.date_rapport <= ?";
            $params[] = $filtres['date_fin'];
        }

        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "SELECT re.*, e.nom_etd, e.prenom_etd, e.email_etd,
                       d.date_depot, a.date_approbation
                FROM rapport_etudiant re
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                LEFT JOIN deposer d ON d.id_rapport_etd = re.id_rapport_etd
                LEFT JOIN approuver a ON a.id_rapport_etd = re.id_rapport_etd
                $where_clause
                ORDER BY re.date_rapport DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer les statistiques de validation séparément
        foreach ($rapports as &$rapport) {
            $rapport['nb_validations'] = $this->getNbValidations($rapport['id_rapport_etd']);
        }

        return $rapports;
    }

    /**
     * Récupère les infos détaillées d'un rapport par son ID
     */
    public function getRapportById($id_rapport) {
        $sql = "SELECT re.*, e.nom_etd, e.prenom_etd, e.email_etd, a.date_approbation, a.com_appr
                FROM rapport_etudiant re
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                LEFT JOIN approuver a ON a.id_rapport_etd = re.id_rapport_etd
                WHERE re.id_rapport_etd = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_rapport]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les messages de discussion pour un rapport
     */
    public function getMessagesByRapport($id_rapport) {
        $sql = "SELECT m.*, e.nom_ens, e.prenoms_ens, e.id_ens
                FROM messages m
                JOIN chat_commission c ON m.id_message = c.id_message
                JOIN enseignants e ON c.id_ens = e.id_ens
                WHERE c.id_rapport_etd = ?
                ORDER BY m.date_creation ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_rapport]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Formatage pour la vue
        $result = [];
        foreach ($messages as $msg) {
            $result[] = [
                'auteur' => 'Prof. ' . $msg['nom_ens'] . ' ' . $msg['prenoms_ens'],
                'date' => date('d/m/Y à H:i', strtotime($msg['date_creation'])),
                'contenu' => $msg['contenu']
            ];
        }
        return $result;
    }

    /**
     * Récupère les validations (votes, commentaires, etc.) pour un rapport
     */
    public function getValidationsByRapport($id_rapport) {
        $sql = "SELECT v.*, e.nom_ens, e.prenoms_ens
                FROM valider v
                LEFT JOIN enseignants e ON v.id_ens = e.id_ens
                WHERE v.id_rapport_etd = ?
                ORDER BY v.date_validation DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_rapport]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère la validation de l'utilisateur courant pour ce rapport
     */
    public function getUserValidation($id_rapport, $id_ens) {
        $sql = "SELECT v.*, e.nom_ens, e.prenoms_ens
                FROM valider v
                LEFT JOIN enseignants e ON v.id_ens = e.id_ens
                WHERE v.id_rapport_etd = ? AND v.id_ens = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id_rapport, $id_ens]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un message de discussion à un rapport (chat commission)
     */
    public function addMessageToRapport($id_rapport, $id_ens, $message) {
        try {
            $sql = "INSERT INTO messages (expediteur_id, destinataire_type, contenu, type_message, categorie, priorite, statut, rapport_id)
                    VALUES (:expediteur_id, 'commission', :contenu, 'chat', 'commission', 'normale', 'envoyé', :rapport_id)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'expediteur_id' => $id_ens,
                'contenu' => $message,
                'rapport_id' => $id_rapport
            ]);
            $id_message = $this->pdo->lastInsertId();

            $sql2 = "INSERT INTO chat_commission (id_ens, id_rapport_etd, id_message)
                      VALUES (:id_ens, :id_rapport_etd, :id_message)";
            $stmt2 = $this->pdo->prepare($sql2);
            $stmt2->execute([
                'id_ens' => $id_ens,
                'id_rapport_etd' => $id_rapport,
                'id_message' => $id_message
            ]);
            return $id_message;
        } catch (\PDOException $e) {
            error_log('Erreur ajout message commission : ' . $e->getMessage());
            return false;
        }
    }
} 