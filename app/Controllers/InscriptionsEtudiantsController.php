<?php

require_once __DIR__ . '/../Models/Reglement.php';
require_once __DIR__ . '/../Models/AnneeAcademique.php';

use App\Models\Reglement;
use App\Models\AnneeAcademique;

class InscriptionsEtudiantsController {
    private $reglementModel;
    private $anneeModel;
    private $pdo;

    public function __construct(PDO $db) {
        $this->pdo = $db;
        $this->reglementModel = new Reglement($db);
        $this->anneeModel = new AnneeAcademique($db);
    }

    public function index() {
        // Récupérer l'année académique courante
        $currentYear = $this->anneeModel->getCurrentAcademicYear();
        $_SESSION['current_year'] = $currentYear;

        // Récupérer les paramètres de filtrage et pagination
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $filter_niveau = isset($_GET['filter_niveau']) ? $_GET['filter_niveau'] : '';
        $filter_statut = isset($_GET['filter_statut']) ? $_GET['filter_statut'] : '';
        $filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
        $page = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;

        // Récupérer les règlements avec filtres et pagination
        $reglementsData = $this->reglementModel->getAllReglements(
            $search, $filter_niveau, $filter_statut, $filter_date, $page, 10
        );

        // Récupérer les statistiques
        $stats = $this->reglementModel->getReglementStats();

        // Récupérer tous les niveaux pour le formulaire
        $niveaux = $this->reglementModel->getAllNiveaux();

        // Passer les données à la vue
        return [
            'reglements' => $reglementsData['reglements'],
            'total_pages' => $reglementsData['total_pages'],
            'current_page' => $page,
            'stats' => $stats,
            'niveaux' => $niveaux,
            'filters' => [
                'search' => $search,
                'filter_niveau' => $filter_niveau,
                'filter_statut' => $filter_statut,
                'filter_date' => $filter_date
            ]
        ];
    }

    // Méthode pour gérer toutes les requêtes AJAX
    public function handleAjaxRequest() {
        $action = $_REQUEST['action'] ?? '';
        
        switch ($action) {
            case 'get_etudiant_info':
                return $this->handleGetEtudiantInfo();
                
            case 'get_montant_tarif':
                return $this->handleGetMontantTarif();
                
            case 'get_payment_history':
                return $this->handleGetPaymentHistory();
                
            case 'enregistrer_reglement':
                return $this->handleEnregistrerReglement();
                
            case 'supprimer_reglement':
                return $this->handleSupprimerReglement();
                
            case 'supprimer_reglements':
                return $this->handleSupprimerReglements();
                
            default:
                return ['error' => 'Action non reconnue'];
        }
    }

    // Gestion de la récupération des informations d'un étudiant
    private function handleGetEtudiantInfo() {
        $num_carte = $_GET['num_carte'] ?? '';
        if (empty($num_carte)) {
            return ['error' => 'Numéro carte requis'];
        }
        
        return $this->reglementModel->getEtudiantInfo($num_carte);
    }

    // Gestion de la récupération du montant des frais
    private function handleGetMontantTarif() {
        $niveau_id = $_POST['niveau'] ?? '';
        if (empty($niveau_id)) {
            return ['error' => 'Niveau requis'];
        }
        
        return $this->reglementModel->getMontantTarif($niveau_id);
    }

    // Gestion de la récupération de l'historique des paiements
    private function handleGetPaymentHistory() {
        $numero_reglement = $_POST['numero_reglement'] ?? '';
        if (empty($numero_reglement)) {
            return ['error' => 'Numéro de règlement requis'];
        }
        
        $history = $this->reglementModel->getPaymentHistory($numero_reglement);
        return $history;
    }

    // Gestion de l'enregistrement d'un règlement
    private function handleEnregistrerReglement() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['error' => 'Méthode non autorisée'];
        }

        // Récupération de l'id de l'année en cours
        $query = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours'";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return ['error' => 'Aucune année académique en cours trouvée'];
        }

        $num_carte = $_POST['card'] ?? '';
        $id_ac = $result['id_ac'];
        $id_niv_etd = $_POST['niveau'] ?? null;
        $mode_paiement = $_POST['mode_paiement'] ?? '';
        $motif_paiement = $_POST['motif_paiement'] ?? '';
        $numero_cheque = $_POST['numero_cheque'] ?? 'Néant';
        $montant_paye = intval($_POST['montant_paye'] ?? 0);
        $date_reglement = date('Y-m-d');

        if (!$num_carte || !$id_ac || !$id_niv_etd) {
            return ['error' => 'Tous les champs obligatoires ne sont pas remplis.'];
        }

        try {
            // 1. Récupérer l'étudiant et son niveau actuel
            $stmt = $this->pdo->prepare("SELECT num_etd, id_niv_etd FROM etudiants WHERE num_carte_etd = ?");
            $stmt->execute([$num_carte]);
            $etudiant = $stmt->fetch();

            if (!$etudiant) {
                return ['error' => 'Étudiant introuvable.'];
            }

            $num_etd = $etudiant['num_etd'];

            // S'assurer que id_niv_etd est défini
            if (!$id_niv_etd) {
                $id_niv_etd = $etudiant['id_niv_etd'];
                if (!$id_niv_etd) {
                    return ['error' => 'Le niveau de l\'étudiant n\'est pas défini.'];
                }
            }

            // 2. Mettre à jour le niveau si besoin
            if ($etudiant['id_niv_etd'] != $id_niv_etd) {
                $stmt = $this->pdo->prepare("UPDATE etudiants SET id_niv_etd = ? WHERE num_etd = ?");
                $stmt->execute([$id_niv_etd, $num_etd]);
            }

            // 3. Récupérer le montant à payer depuis la table frais_inscription
            $stmt = $this->pdo->prepare("SELECT montant FROM frais_inscription WHERE id_niv_etd = ? AND id_ac = ?");
            $stmt->execute([$id_niv_etd, $id_ac]);
            $frais = $stmt->fetch();

            if (!$frais) {
                return ['error' => 'Aucun tarif défini pour ce niveau et cette année académique.'];
            }

            $montant_total = intval($frais['montant']);

            // 4. Vérifier si un règlement existe déjà pour cette année et ce niveau
            $stmt = $this->pdo->prepare("SELECT * FROM reglement WHERE num_etd = ? AND id_ac = ? AND id_niv_etd = ? AND statut != 'Soldé' LIMIT 1");
            $stmt->execute([$num_etd, $id_ac, $id_niv_etd]);
            $reglement = $stmt->fetch();

            // Générer le numéro de reçu
            $numero_recu = 'REC-' . date('Y') . strtoupper(bin2hex(random_bytes(3)));

            if ($reglement) {
                // Règlement existant
                $id_reglement = $reglement['id_reglement'];
                $numero_reglement = $reglement['numero_reglement'];

                // Calculer le total payé actuel pour ce règlement
                $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(montant_paye), 0) as total_paye_actuel FROM paiement_reglement WHERE id_reglement = ?");
                $stmt->execute([$id_reglement]);
                $total_paye_actuel = $stmt->fetchColumn();

                // Calculer le nouveau total payé
                $nouveau_total = $total_paye_actuel + $montant_paye;

                // Vérifier si le nouveau total ne dépasse pas le montant total
                if ($nouveau_total > $montant_total) {
                    return ['error' => "Le montant total payé (" . number_format($nouveau_total, 0, ',', ' ') . " FCFA) dépasse le montant à payer (" . number_format($montant_total, 0, ',', ' ') . " FCFA)."];
                }

                // Calculer le nouveau reste à payer
                $reste_a_payer = max(0, $montant_total - $nouveau_total);

                // Déterminer le statut
                if ($nouveau_total >= $montant_total) {
                    $statut = 'Soldé';
                } else {
                    $statut = 'Partiel';
                }

                // Mettre à jour le règlement
                $stmt = $this->pdo->prepare("UPDATE reglement SET 
                    total_paye = ?, 
                    reste_a_payer = ?,
                    statut = ?,
                    date_reglement = NOW()
                    WHERE id_reglement = ?");
                $stmt->execute([$nouveau_total, $reste_a_payer, $statut, $id_reglement]);

                // Ajouter le paiement
                $stmt = $this->pdo->prepare("INSERT INTO paiement_reglement (id_reglement, numero_recu, mode_de_paiement, motif_paiement, numero_cheque, montant_paye, date_paiement) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$id_reglement, $numero_recu, $mode_paiement, $motif_paiement, $numero_cheque, $montant_paye]);
                
                return ['success' => true, 'message' => "Paiement enregistré avec succès (Règlement : $numero_reglement)."];
            } else {
                // Nouveau règlement
                $prefix = 'REG-' . date('Y');
                $stmt = $this->pdo->query("SELECT MAX(CAST(SUBSTRING(numero_reglement, 10) AS UNSIGNED)) FROM reglement WHERE numero_reglement LIKE '$prefix%'");
                $max = $stmt->fetchColumn();
                $next = str_pad(((int)$max ?: 0) + 1, 4, '0', STR_PAD_LEFT);
                $numero_reglement = $prefix . $next;

                // Calculer les paiements précédents pour la même année académique
                $stmt = $this->pdo->prepare("
                    SELECT COALESCE(SUM(p.montant_paye), 0) as total_deja_paye
                    FROM paiement_reglement p
                    JOIN reglement r ON r.id_reglement = p.id_reglement
                    WHERE r.num_etd = ? AND r.id_ac = ? AND r.id_niv_etd = ?
                ");
                $stmt->execute([$num_etd, $id_ac, $id_niv_etd]);
                $total_deja_paye = intval($stmt->fetchColumn());

                // Nouveau total payé (paiement actuel + paiements précédents)
                $nouveau_total_paye = $total_deja_paye + $montant_paye;

                // Calculer le nouveau reste à payer
                $reste_a_payer = max(0, $montant_total - $nouveau_total_paye);

                // Déterminer le statut pour le nouveau règlement
                if ($nouveau_total_paye >= $montant_total) {
                    $statut = 'Soldé';
                } elseif ($montant_paye > 0) {
                    $statut = 'Partiel';
                } else {
                    $statut = 'Non payé';
                }

                $stmt = $this->pdo->prepare("INSERT INTO reglement (num_etd, id_ac, numero_reglement, montant_a_payer, total_paye, reste_a_payer, id_niv_etd, date_reglement, statut, mode_de_paiement, numero_cheque, motif_paiement)
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$num_etd, $id_ac, $numero_reglement, $montant_total, $nouveau_total_paye, $reste_a_payer, $id_niv_etd, $date_reglement, $statut, $mode_paiement, $numero_cheque, $motif_paiement]);
                $id_reglement = $this->pdo->lastInsertId();

                // Ajouter le paiement
                $stmt = $this->pdo->prepare("INSERT INTO paiement_reglement (id_reglement, numero_recu, mode_de_paiement, motif_paiement, numero_cheque, montant_paye, date_paiement) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$id_reglement, $numero_recu, $mode_paiement, $motif_paiement, $numero_cheque, $montant_paye]);
                
                return ['success' => true, 'message' => "Paiement enregistré avec succès (Règlement : $numero_reglement)."];
            }
        } catch (PDOException $e) {
            return ['error' => "Erreur lors de l'enregistrement du règlement : " . $e->getMessage()];
        }
    }

    // Gestion de la suppression d'un règlement
    private function handleSupprimerReglement() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['error' => 'Méthode non autorisée'];
        }

        $numero_reglement = $_POST['numero_reglement'] ?? '';
        if (empty($numero_reglement)) {
            return ['error' => 'Numéro de règlement requis'];
        }

        try {
            $this->pdo->beginTransaction();

            // Récupérer l'ID du règlement
            $stmt = $this->pdo->prepare("SELECT id_reglement FROM reglement WHERE numero_reglement = ?");
            $stmt->execute([$numero_reglement]);
            $reglement = $stmt->fetch();

            if (!$reglement) {
                $this->pdo->rollBack();
                return ['error' => 'Règlement introuvable'];
            }

            $id_reglement = $reglement['id_reglement'];

            // Supprimer les paiements associés
            $stmt = $this->pdo->prepare("DELETE FROM paiement_reglement WHERE id_reglement = ?");
            $stmt->execute([$id_reglement]);

            // Supprimer le règlement
            $stmt = $this->pdo->prepare("DELETE FROM reglement WHERE id_reglement = ?");
            $stmt->execute([$id_reglement]);

            $this->pdo->commit();
            return ['success' => true, 'message' => 'Règlement supprimé avec succès'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['error' => 'Erreur lors de la suppression : ' . $e->getMessage()];
        }
    }

    // Gestion de la suppression multiple de règlements
    private function handleSupprimerReglements() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return ['error' => 'Méthode non autorisée'];
        }

        $reglement_ids = $_POST['reglement_ids'] ?? '';
        if (empty($reglement_ids)) {
            return ['error' => 'Aucun règlement sélectionné'];
        }

        try {
            $ids = json_decode($reglement_ids, true);
            if (!is_array($ids) || empty($ids)) {
                return ['error' => 'Format de données invalide'];
            }

            $this->pdo->beginTransaction();

            // Supprimer les paiements associés
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $this->pdo->prepare("DELETE FROM paiement_reglement WHERE id_reglement IN (SELECT id_reglement FROM reglement WHERE numero_reglement IN ($placeholders))");
            $stmt->execute($ids);

            // Supprimer les règlements
            $stmt = $this->pdo->prepare("DELETE FROM reglement WHERE numero_reglement IN ($placeholders)");
            $stmt->execute($ids);

            $this->pdo->commit();
            return ['success' => true, 'message' => count($ids) . ' règlement(s) supprimé(s) avec succès'];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['error' => 'Erreur lors de la suppression : ' . $e->getMessage()];
        }
    }

    // Méthodes publiques pour compatibilité (dépréciées)
    public function getEtudiantInfo($num_carte) {
        return $this->reglementModel->getEtudiantInfo($num_carte);
    }

    public function getMontantTarif($niveau_id) {
        return $this->reglementModel->getMontantTarif($niveau_id);
    }

    public function getPaymentHistory($numero_reglement) {
        return $this->reglementModel->getPaymentHistory($numero_reglement);
    }
} 
