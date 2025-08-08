<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Models/Etudiant.php';

use App\Models\Etudiant;

class EtudiantsController {
    private $model;
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getConnection();
        $this->model = new Etudiant($this->pdo);
    }

    /**
     * Affiche la page principale des étudiants
     */
    public function index() {
        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $promotion = $_GET['promotion'] ?? '';
        $niveau = $_GET['niveau'] ?? '';
        $statut_etudiant = $_GET['statut_etudiant'] ?? '';
        $page_num = max(1, intval($_GET['page_num'] ?? 1));
        $limit = 50;

      

        // Récupération des données
        $etudiants = $this->model->getAllEtudiants($search, $promotion, $niveau, $statut_etudiant, $page_num, $limit);
        $total_records = $this->model->getTotalEtudiants($search, $promotion, $niveau, $statut_etudiant);
        $total_pages = max(1, ceil($total_records / $limit));
        $statistics = $this->model->getStatistiques();
        $statut_etudiant_list = $this->model->getStatutEtudiant();
        $etudiants_a_cheval = $this->model->getAllEtudiants($search, $promotion, $niveau, $statut_etudiant, $page_num, $limit);

        // Récupération des listes pour les filtres
        $promotions = $this->getPromotions();
        $niveaux = $this->getNiveaux();
        

        return [
            'etudiants' => $etudiants,
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $page_num,
            'statistics' => $statistics,
            'etudiants_a_cheval' => $etudiants_a_cheval,
            'filters' => [
                'search' => $search,
                'promotion' => $promotion,
                'niveau' => $niveau,
                'statut_etudiant' => $statut_etudiant
            ],
            'lists' => [
                'statut_etudiant' => $statut_etudiant_list,  
                'promotions' => $promotions,
                'niveaux' => $niveaux
            ]
        ];
    }

    /**
     * Récupère les détails d'un étudiant
     */
    public function getEtudiantDetails($id) {
        $etudiant = $this->model->getEtudiantById($id);
        
        if (!$etudiant) {
            return null;
        }

        // Récupération des informations supplémentaires
        $stage = $this->getStageInfo($id);
        $scolarite = $this->getScolariteInfo($id);
        $promotion = $this->getPromotionInfo($etudiant['id_promotion']);

        return [
            'etudiant' => $etudiant,
            'stage' => $stage,
            'scolarite' => $scolarite,
            'promotion' => $promotion
        ];
    }

    /**
     * Ajoute un nouvel étudiant
     */
    public function ajouterEtudiant($data) {
        // Validation des données
        if (empty($data['card']) || empty($data['nom']) || empty($data['prenoms']) || 
            empty($data['email']) || empty($data['id_niv_etd']) || empty($data['id_promotion'])) {
            return ['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis'];
        }

        // Vérification si l'étudiant existe déjà
        if ($this->etudiantExists($data['email'], $data['card'])) {
            return ['success' => false, 'message' => 'Un étudiant avec cet email ou ce numéro de carte existe déjà'];
        }

        try {
            $result = $this->model->ajouterEtudiant(
                $data['nom'],
                $data['prenoms'],
                $data['email'],
                $data['card'],
                $data['id_niv_etd'],
                $data['id_promotion'],
                $data['date'] ?? null,
                $data['sexe'] ?? 'Homme'
            );

            if ($result) {
                return ['success' => true, 'message' => "L'étudiant a été ajouté avec succès"];
            } else {
                return ['success' => false, 'message' => "Erreur lors de l'ajout de l'étudiant"];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de l'ajout de l'étudiant: " . $e->getMessage()];
        }
    }

    /**
     * Modifie un étudiant existant
     */
    public function modifierEtudiant($id, $data) {
        // Validation des données
        if (empty($data['card']) || empty($data['nom']) || empty($data['prenoms']) || 
            empty($data['email']) || empty($data['id_niv_etd']) || empty($data['id_promotion'])) {
            return ['success' => false, 'message' => 'Tous les champs obligatoires doivent être remplis'];
        }

        try {
            $result = $this->model->modifierEtudiant(
                $id,
                $data['card'],
                $data['nom'],
                $data['prenoms'],
                $data['email'],
                $data['id_niv_etd'],
                $data['id_promotion'],
                $data['date'] ?? null,
                $data['sexe'] ?? 'Homme'
            );

            if ($result) {
                return ['success' => true, 'message' => "Les informations de l'étudiant ont été mises à jour"];
            } else {
                return ['success' => false, 'message' => "Erreur lors de la modification de l'étudiant"];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de la modification de l'étudiant: " . $e->getMessage()];
        }
    }

    /**
     * Supprime un étudiant
     */
    public function supprimerEtudiant($id) {
        try {
            $result = $this->model->supprimerEtudiant($id);
            
            if ($result) {
                return ['success' => true, 'message' => "L'étudiant a été supprimé avec succès"];
            } else {
                return ['success' => false, 'message' => "Erreur lors de la suppression de l'étudiant"];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Erreur lors de la suppression de l'étudiant: " . $e->getMessage()];
        }
    }

    /**
     * Gère les actions sur les rapports (approbation, rejet, partage)
     */
    public function gererRapport($action, $etudiant_id, $rapport_id, $data = []) {
        switch ($action) {
            case 'approve':
                return $this->approuverRapport($etudiant_id, $rapport_id);
            case 'reject':
                return $this->rejeterRapport($etudiant_id, $rapport_id, $data['reject_reason'] ?? '');
            case 'share':
                return $this->partagerRapport($etudiant_id, $rapport_id, $data);
            default:
                return ['success' => false, 'message' => 'Action non reconnue'];
        }
    }

    /**
     * Récupère les détails d'un rapport
     */
    public function getRapportDetails($rapport_id, $etudiant_id) {
        $query = "SELECT e.*, r.* 
                  FROM etudiants e
                  JOIN rapport_etudiant r ON r.num_etd = e.num_etd 
                  WHERE e.num_etd = ? AND r.id_rapport_etd = ?";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([$etudiant_id, $rapport_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les rapports des étudiants
     */
    public function getRapportsEtudiants($search = '', $date_filter = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR e.email_etd LIKE ? OR r.nom_rapport LIKE ?)";
            $search_param = "%$search%";
            $params = array_merge($params, array_fill(0, 4, $search_param));
        }

        if ($date_filter === 'today') {
            $where[] = "DATE(r.date_rapport) = CURDATE()";
        } elseif ($date_filter === 'week') {
            $where[] = "YEARWEEK(r.date_rapport, 1) = YEARWEEK(CURDATE(), 1)";
        } elseif ($date_filter === 'month') {
            $where[] = "MONTH(r.date_rapport) = MONTH(CURDATE()) AND YEAR(r.date_rapport) = YEAR(CURDATE())";
        }

        // Statuts à afficher
        $where[] = "(r.statut_rapport = ? OR r.statut_rapport = ?)";
        $params[] = "En attente d'approbation";
        $params[] = "Approuvé";

        $where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Requête principale
        $sql = "SELECT e.nom_etd, e.prenom_etd, e.email_etd, e.num_etd, 
                       r.id_rapport_etd, r.nom_rapport, r.date_rapport, r.theme_memoire, 
                       d.date_depot, r.statut_rapport
                FROM etudiants e
                JOIN rapport_etudiant r ON r.num_etd = e.num_etd 
                JOIN deposer d ON d.id_rapport_etd = r.id_rapport_etd
                JOIN utilisateur u ON u.login_utilisateur = e.email_etd
                $where_sql
                ORDER BY r.date_rapport DESC
                LIMIT $limit OFFSET $offset";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compter le total
        $count_sql = "SELECT COUNT(*) 
                      FROM etudiants e
                      JOIN rapport_etudiant r ON r.num_etd = e.num_etd 
                      JOIN deposer d ON d.id_rapport_etd = r.id_rapport_etd
                      JOIN utilisateur u ON u.login_utilisateur = e.email_etd
                      $where_sql";
        $count_stmt = $this->pdo->prepare($count_sql);
        $count_stmt->execute($params);
        $total_records = $count_stmt->fetchColumn();
        $total_pages = max(1, ceil($total_records / $limit));

        return [
            'rapports' => $rapports,
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $page
        ];
    }

    // Méthodes privées pour les données auxiliaires

    private function getPromotions() {
        $stmt = $this->pdo->prepare("SELECT * FROM promotion ORDER BY lib_promotion DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getNiveaux() {
        $stmt = $this->pdo->prepare("SELECT * FROM niveau_etude");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getStageInfo($etudiant_id) {
        $stmt = $this->pdo->prepare("
            SELECT f.*, e.lib_entr, e.adresse, e.ville, e.pays
            FROM faire_stage f
            JOIN entreprise e ON f.id_entr = e.id_entr
            WHERE f.num_etd = ?
        ");
        $stmt->execute([$etudiant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getScolariteInfo($etudiant_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.*
            FROM reglement r
            LEFT JOIN etudiants e ON e.num_etd = r.num_etd
            WHERE e.num_etd = ?
        ");
        $stmt->execute([$etudiant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getPromotionInfo($promotion_id) {
        $stmt = $this->pdo->prepare("SELECT lib_promotion FROM promotion WHERE id_promotion = ?");
        $stmt->execute([$promotion_id]);
        return $stmt->fetchColumn();
    }

    private function etudiantExists($email, $carte) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM etudiants WHERE email_etd = ? OR num_carte_etd = ?");
        $stmt->execute([$email, $carte]);
        return $stmt->fetchColumn() > 0;
    }

    private function approuverRapport($etudiant_id, $rapport_id) {
        try {
            $this->pdo->beginTransaction();

            // Mettre à jour le statut du rapport
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiant SET statut_rapport = 'Approuvé' WHERE id_rapport_etd = ? AND num_etd = ?");
            $stmt->execute([$rapport_id, $etudiant_id]);

            // Récupérer les informations pour l'email
            $stmt = $this->pdo->prepare("SELECT e.email_etd, e.nom_etd, e.prenom_etd, r.nom_rapport, r.theme_memoire
                                       FROM etudiants e 
                                       JOIN rapport_etudiant r ON e.num_etd = r.num_etd
                                       WHERE e.num_etd = ? AND r.id_rapport_etd = ?");
            $stmt->execute([$etudiant_id, $rapport_id]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($info) {
                // Envoyer l'email
                $subject = "Votre rapport a été approuvé";
                $message = $this->generateApprovalEmail($info);
                
                require_once __DIR__ . '/../../config/mail.php';
                if (sendEmail('Check Master', 'axelangegomez2004@gmail.com', $info['email_etd'], $subject, $message)) {
                    $this->pdo->commit();
                    return ['success' => true, 'message' => "Le rapport a été approuvé et un email a été envoyé à l'étudiant"];
                } else {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => "Le rapport a été approuvé mais l'email n'a pas pu être envoyé"];
                }
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => "Le rapport a été approuvé"];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => "Erreur lors de l'approbation: " . $e->getMessage()];
        }
    }

    private function rejeterRapport($etudiant_id, $rapport_id, $reason) {
        try {
            $this->pdo->beginTransaction();

            // Mettre à jour le statut du rapport
            $stmt = $this->pdo->prepare("UPDATE rapport_etudiant SET statut_rapport = 'Rejeté' WHERE id_rapport_etd = ? AND num_etd = ?");
            $stmt->execute([$rapport_id, $etudiant_id]);

            // Récupérer les informations pour l'email
            $stmt = $this->pdo->prepare("SELECT e.email_etd, e.nom_etd, e.prenom_etd, r.nom_rapport, r.theme_memoire
                                       FROM etudiants e 
                                       JOIN rapport_etudiant r ON e.num_etd = r.num_etd
                                       WHERE e.num_etd = ? AND r.id_rapport_etd = ?");
            $stmt->execute([$etudiant_id, $rapport_id]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($info) {
                // Envoyer l'email
                $subject = "Votre rapport n'a pas été approuvé";
                $message = $this->generateRejectionEmail($info, $reason);
                
                require_once __DIR__ . '/../../config/mail.php';
                if (sendEmail('Check Master', 'axelangegomez2004@gmail.com', $info['email_etd'], $subject, $message)) {
                    $this->pdo->commit();
                    return ['success' => true, 'message' => "Le rapport a été rejeté et un email a été envoyé à l'étudiant"];
                } else {
                    $this->pdo->rollBack();
                    return ['success' => false, 'message' => "Le rapport a été rejeté mais l'email n'a pas pu être envoyé"];
                }
            }

            $this->pdo->commit();
            return ['success' => true, 'message' => "Le rapport a été rejeté"];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => "Erreur lors du rejet: " . $e->getMessage()];
        }
    }

    private function partagerRapport($etudiant_id, $rapport_id, $data) {
        try {
            $this->pdo->beginTransaction();

            $users = $data['share_users'] ?? [];
            $message = $data['share_message'] ?? '';

            // Vérifier si l'approbation existe déjà
            $checkApprove = $this->pdo->prepare("SELECT COUNT(*) FROM approuver WHERE id_personnel_adm = ? AND id_rapport_etd = ?");
            $checkApprove->execute([$_SESSION['user_id'], $rapport_id]);
            if ($checkApprove->fetchColumn() == 0) {
                $sqlApprove = $this->pdo->prepare("INSERT INTO approuver (id_personnel_adm, id_rapport_etd, date_approbation, com_appr) VALUES (?, ?, ?, ?)");
                $sqlApprove->execute([$_SESSION['user_id'], $rapport_id, date('Y-m-d'), $message]);
            }

            // Récupérer les informations du rapport
            $stmt = $this->pdo->prepare("SELECT r.nom_rapport, e.nom_etd, e.prenom_etd FROM rapport_etudiant r JOIN etudiants e ON r.num_etd = e.num_etd WHERE r.id_rapport_etd = ? AND r.num_etd = ?");
            $stmt->execute([$rapport_id, $etudiant_id]);
            $report_info = $stmt->fetch(PDO::FETCH_ASSOC);

            
            $placeholders = implode(',', array_fill(0, count($users), '?'));
            $stmt = $this->pdo->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur IN ($placeholders)");
            $stmt->execute($users);
            $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $success_count = 0;
            $error_count = 0;

            foreach ($emails as $email) {
                $subject = "Partage de rapport: " . $report_info['nom_rapport'];
                $email_message = "
                    <html>
                    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                            <h2 style='color: #2c3e50;'>Rapport partagé</h2>
                            <p>Un rapport a été partagé avec vous:</p>
                            <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #3498db;'>
                                <p><strong>Titre:</strong> {$report_info['nom_rapport']}</p>
                                <p><strong>Étudiant:</strong> {$report_info['prenom_etd']} {$report_info['nom_etd']}</p>
                            </div>";
                if (!empty($message)) {
                    $email_message .= "
                        <div style='margin-top: 15px;'>
                            <p><strong>Message:</strong></p>
                            <p>{$message}</p>
                        </div>";
                }
                $email_message .= "
                            <p>Vous pouvez accéder au rapport en vous connectant à la plateforme Check Master.</p>
                            <p>Cordialement,<br>L'équipe Check Master</p>
                        </div>
                    </body>
                    </html>
                ";

                try {
                    if (sendEmail('Check Master', 'axelangegomez2004@gmail.com', $email, $subject, $email_message)) {
                        $success_count++;
                       

                        // Récupérer id_personnel_adm
                        $sql = "SELECT * FROM personnel_administratif pa
                                JOIN utilisateur u ON pa.email_personnel_adm = u.login_utilisateur
                                WHERE u.id_utilisateur = ?";
                        $stmtPers = $this->pdo->prepare($sql);
                        $stmtPers->execute([$_SESSION["user_id"]]);
                        $recupPers = $stmtPers->fetch(PDO::FETCH_ASSOC);

                        if ($recupPers) {
                            $id_pa = $recupPers["id_personnel_adm"];
                            // Vérifier si le partage existe déjà
                            $checkShare = $this->pdo->prepare("SELECT COUNT(*) FROM partage_rapport WHERE id_rapport_etd = ? AND id_personnel_adm = ?");
                            $checkShare->execute([$rapport_id, $id_pa]);
                            if ($checkShare->fetchColumn() == 0) {
                                $stmtShare = $this->pdo->prepare("INSERT INTO partage_rapport (id_rapport_etd, id_personnel_adm, date_partage) VALUES (?, ?, NOW())");
                                $stmtShare->execute([$rapport_id, $id_pa]);
                            }
                        }
                    } else {
                        $error_count++;
                    }
                } catch (Exception $e) {
                    $error_count++;
                }
            }

            if ($success_count > 0) {
                // Statut changé une seule fois, si au moins un email est parti
                $stmtEditStatut = $this->pdo->prepare("UPDATE rapport_etudiant SET statut_rapport = 'En attente de validation' WHERE id_rapport_etd = ? AND num_etd = ?");
                $stmtEditStatut->execute([$rapport_id, $etudiant_id]);
                $this->pdo->commit();
                return ['success' => true, 'message' => "Le rapport a été partagé avec $success_count destinataire(s)." . ($error_count > 0 ? " ($error_count échec(s))" : "")];
            } else {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => "Erreur lors du partage avec $error_count destinataire(s)."];
            }
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => "Erreur lors du partage : " . $e->getMessage()];
        }
    }

    private function generateApprovalEmail($info) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #2c3e50;'>Cher(e) {$info['prenom_etd']} {$info['nom_etd']},</h2>
                    <p>Nous avons le plaisir de vous informer que votre rapport <strong>{$info['theme_memoire']}</strong> a été approuvé.</p>
                    <p>Cordialement,<br>L'équipe Check Master</p>
                </div>
            </body>
            </html>
        ";
    }

    private function generateRejectionEmail($info, $reason) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #2c3e50;'>Cher(e) {$info['prenom_etd']} {$info['nom_etd']},</h2>
                    <p>Nous regrettons de vous informer que votre rapport <strong>{$info['theme_memoire']}</strong> n'a pas été approuvé pour les raisons suivantes:</p>
                    <div style='background-color: #f8f9fa; padding: 15px; border-left: 4px solid #e74c3c;'>
                        <p style='margin: 0;'>{$reason}</p>
                    </div>
                    <p>Nous vous encourageons à apporter les modifications nécessaires et à soumettre une nouvelle version.</p>
                    <p>Cordialement,<br>L'équipe Check Master</p>
                </div>
            </body>
            </html>
        ";
    }


    public function rapportEnAttente($search_rapport = '', $statut = '', $date_rapport = '', $page = 1, $limit = 10){
        return $this->model->getRapportsEnAttente($search_rapport, $statut, $date_rapport, $page, $limit);
    }




    /*=================================Étudiants à cheval=================================*/
    
    /**
     * Récupère les données pour l'inscription à cheval
     */
    public function getInscriptionChevalData() {
        try {
            // Récupérer les étudiants autorisés seulement et non déjà à cheval
            $sql = "SELECT DISTINCT e.num_etd, e.nom_etd, e.prenom_etd, e.email_etd, e.id_niv_etd, e.id_promotion,
                           ne.lib_niv_etd, mg.statut_academique
                    FROM etudiants e
                    JOIN niveau_etude ne ON e.id_niv_etd = ne.id_niv_etd
                    JOIN moyenne_generale mg ON e.num_etd = mg.num_etd
                    WHERE mg.statut_academique = 'Autorisé'
                      AND e.id_statut <> 2
                    ORDER BY e.nom_etd, e.prenom_etd";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $etudiants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les années académiques
            $sql = "SELECT id_ac, CONCAT(YEAR(date_debut), '-', YEAR(date_fin)) as annee_ac 
                    FROM annee_academique 
                    ORDER BY date_debut DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $annees = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les promotions
            $sql = "SELECT id_promotion, lib_promotion FROM promotion ORDER BY lib_promotion";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $promotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les niveaux
            $sql = "SELECT id_niv_etd, lib_niv_etd FROM niveau_etude ORDER BY lib_niv_etd";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'etudiants' => $etudiants,
                'annees' => $annees,
                'promotions' => $promotions,
                'niveaux' => $niveaux
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération données inscription cheval: " . $e->getMessage());
            return [
                'etudiants' => [],
                'annees' => [],
                'promotions' => [],
                'niveaux' => []
            ];
        }
    }

    /**
     * Récupère les matières disponibles pour le rattrapage
     */
    public function getMatieresRattrapage($annee_id,  $etudiants_ids) {
        try {
            if (empty($etudiants_ids)) {
                return ['success' => false, 'message' => 'Aucun étudiant sélectionné'];
            }

            error_log("=== DEBUG MATIERES RATTRAPAGE ===");
            error_log("Annee ID: " . $annee_id);
            error_log("Etudiants IDs: " . print_r($etudiants_ids, true));

            // Récupérer les niveaux des étudiants sélectionnés
            $placeholders = str_repeat('?,', count($etudiants_ids) - 1) . '?';
            $sql = "SELECT DISTINCT e.id_niv_etd, ne.lib_niv_etd
                    FROM etudiants e
                    JOIN niveau_etude ne ON e.id_niv_etd = ne.id_niv_etd
                    WHERE e.num_etd IN ($placeholders)";
            
            error_log("SQL Niveaux: " . $sql);
            error_log("Params Niveaux: " . print_r($etudiants_ids, true));
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($etudiants_ids);
            $niveaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Niveaux trouvés: " . print_r($niveaux, true));

            if (empty($niveaux)) {
                return ['success' => false, 'message' => 'Aucun niveau trouvé pour les étudiants sélectionnés'];
            }

            // Récupérer les matières disponibles pour ces niveaux et l'année académique
            $niveaux_ids = array_column($niveaux, 'id_niv_etd');
            $placeholders = str_repeat('?,', count($niveaux_ids) - 1) . '?';
            
            $sql = "SELECT DISTINCT ec.id_ecue, ec.lib_ecue, ec.credit_ecue, ne.lib_niv_etd,
                           COALESCE(ec.prix_matiere_cheval_ecue, 25000.00) as prix_matiere_cheval
                    FROM ecue ec
                    JOIN ue u ON ec.id_ue = u.id_ue
                    JOIN niveau_etude ne ON u.id_niv_etd = ne.id_niv_etd
                    WHERE u.id_niv_etd IN ($placeholders)";
            
            $params = $niveaux_ids;
            
            // Ajouter le filtre par année académique si spécifiée
            if ($annee_id && $annee_id != '') {
                $sql .= " AND u.id_annee_academique = ?";
                $params[] = $annee_id;
            }
            
            $sql .= " ORDER BY ne.lib_niv_etd, ec.lib_ecue";
            
            error_log("SQL Matières: " . $sql);
            error_log("Params Matières: " . print_r($params, true));
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Matières trouvées: " . print_r($matieres, true));

            return [
                'success' => true,
                'matieres' => $matieres,
                'niveaux' => $niveaux
            ];
        } catch (PDOException $e) {
            error_log("Erreur récupération matières rattrapage: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Erreur lors de la récupération des matières: ' . $e->getMessage()];
        }
    }

    /**
     * Inscrit plusieurs étudiants à cheval
     */
    public function inscrireEtudiantsCheval($data) {
        try {
            // Extraire les données du formulaire
            $etudiants_ids = $data['selected_etudiants'] ?? [];
            $matieres_ids = $data['selected_matieres'] ?? [];
            $annee_id = $data['id_ac'] ?? '';
            $promotion_principale = $data['promotion_principale'] ?? '';
            $montant_inscription = $data['montant_inscription'] ?? 0;
            $commentaire = $data['commentaire'] ?? '';

            // Validation des données
            if (empty($etudiants_ids)) {
                return ['success' => false, 'message' => 'Aucun étudiant sélectionné'];
            }

            if (empty($matieres_ids)) {
                return ['success' => false, 'message' => 'Aucune matière sélectionnée'];
            }

            if (empty($annee_id)) {
                return ['success' => false, 'message' => 'Année académique requise'];
            }

            if (empty($promotion_principale)) {
                return ['success' => false, 'message' => 'Promotion principale requise'];
            }

            $success_count = 0;
            $error_messages = [];

            foreach ($etudiants_ids as $etudiant_id) {
                try {
                    // Vérifier si l'étudiant n'est pas déjà inscrit à cheval pour cette année
                    if ($this->model->isEtudiantCheval($etudiant_id, $annee_id)) {
                        $error_messages[] = "L'étudiant $etudiant_id est déjà inscrit à cheval pour cette année";
                        continue;
                    }

                    // Inscrire l'étudiant à cheval (le modèle gère sa propre transaction)
                    $nombre_matieres = count($matieres_ids);
                    $result = $this->model->inscrireEtudiantCheval(
                        $etudiant_id,
                        $annee_id,
                        $promotion_principale,
                        $nombre_matieres,
                        $montant_inscription,
                        $commentaire
                    );

                    if ($result) {
                        // Ajouter les matières de rattrapage pour cet étudiant
                        foreach ($matieres_ids as $matiere_id) {
                            $this->model->ajouterMatiereRattrapage(
                                $etudiant_id,
                                $matiere_id,
                                $annee_id,
                                $promotion_principale,
                                $promotion_principale
                            );
                        }
                        // Le statut est déjà mis à jour dans inscrireEtudiantCheval
                        $success_count++;
                    } else {
                        $error_messages[] = "Erreur lors de l'inscription de l'étudiant $etudiant_id";
                    }
                } catch (Exception $e) {
                    $error_messages[] = "Erreur pour l'étudiant $etudiant_id: " . $e->getMessage();
                }
            }

            if ($success_count > 0) {
                $message = "$success_count étudiant(s) inscrit(s) avec succès à cheval.";
                if (!empty($error_messages)) {
                    $message .= " Erreurs: " . implode(', ', $error_messages);
                }
                return ['success' => true, 'message' => $message];
            } else {
                return ['success' => false, 'message' => 'Aucun étudiant n\'a pu être inscrit. ' . implode(', ', $error_messages)];
            }
        } catch (Exception $e) {
            error_log("Erreur inscription multiple étudiants à cheval: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription: ' . $e->getMessage()];
        }
    }

    /**
     * Calcule les frais d'inscription à cheval avec les prix des matières
     */
    public function calculerFraisCheval($niveau_id, $annee_id, $matieres_ids = []) {
        try {
            // Récupérer les frais de base pour le niveau et l'année
            $sql = "SELECT montant FROM frais_inscription 
                    WHERE id_niv_etd = ? AND id_ac = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$niveau_id, $annee_id]);
            $frais_base = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $montant_base = $frais_base ? $frais_base['montant'] : 0;

            // Calculer le total des prix des matières sélectionnées
            $total_prix_matieres = 0;
            if (!empty($matieres_ids)) {
                $placeholders = str_repeat('?,', count($matieres_ids) - 1) . '?';
                $sql = "SELECT SUM(COALESCE(prix_matiere_cheval, 25000.00)) as total_prix
                        FROM ecue 
                        WHERE id_ecue IN ($placeholders)";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($matieres_ids);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $total_prix_matieres = $result ? $result['total_prix'] : 0;
            }

            $total_frais = $montant_base + $total_prix_matieres;

            return [
                'success' => true,
                'frais_base' => $montant_base,
                'total_prix_matieres' => $total_prix_matieres,
                'total_frais' => $total_frais
            ];
        } catch (PDOException $e) {
            error_log("Erreur calcul frais cheval: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors du calcul des frais'
            ];
        }
    }


    public function getEtudiantsCheval($id_ac)
    {
        return $this->model->getEtudiantCheval($id_ac);
    }
} 
