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
        $page_num = max(1, intval($_GET['page_num'] ?? 1));
        $limit = 50;

        // Récupération des données
        $etudiants = $this->model->getAllEtudiants($search, $promotion, $niveau, $page_num, $limit);
        $total_records = $this->model->getTotalEtudiants($search, $promotion, $niveau);
        $total_pages = max(1, ceil($total_records / $limit));
        $statistics = $this->model->getStatistiques();

        // Récupération des listes pour les filtres
        $promotions = $this->getPromotions();
        $niveaux = $this->getNiveaux();

        return [
            'etudiants' => $etudiants,
            'total_records' => $total_records,
            'total_pages' => $total_pages,
            'current_page' => $page_num,
            'statistics' => $statistics,
            'filters' => [
                'search' => $search,
                'promotion' => $promotion,
                'niveau' => $niveau
            ],
            'lists' => [
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
} 
