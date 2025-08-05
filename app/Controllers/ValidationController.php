<?php

require_once __DIR__ . '/../Models/ValidationModel.php';
require_once __DIR__ . '/../../config/config.php';

// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');

class ValidationController {
    private $model;

    public function __construct() {
        $pdo = DataBase::getConnection();
        $this->model = new \App\Models\ValidationModel($pdo);
    }

    /**
     * Affiche la page des validations
     */
    public function index() {
        // Vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Vérification des permissions (commission)
        $allowedGroups = [5, 6, 7, 8, 9]; // Enseignant, Responsable niveau, Responsable filière, Administrateur, Commission
        $userGroups = $_SESSION['user_groups'] ?? [];
        
        $hasAccess = false;
        foreach ($userGroups as $group) {
            if (in_array($group, $allowedGroups)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            $_SESSION['error_message'] = "Accès non autorisé à l'interface de validation";
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date_filter = $_GET['date_filter'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));

        // Récupération des données
        $rapports_data = $this->model->getRapportsEnAttente($search, $date_filter, $page, 10);
        $stats = $this->model->getValidationStats();

        // Extraction des données pour la vue
        $rapports = $rapports_data['rapports'];
        $total_pages = $rapports_data['pages'];
        $total_rapports = $rapports_data['total'];

       // require __DIR__ . '/../Views/validations.php';


        
    }

    /**
     * Valide un rapport (AJAX)
     */
    public function validerRapports($rapport_id, $enseignant_id, $commentaire, $decision) {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            exit;
        }

        if (empty($rapport_id) || empty($enseignant_id) || empty($decision)) {
            echo json_encode(['success' => false, 'error' => 'Tous les champs obligatoires doivent être remplis']);
            return;
        }

        try {
            $this->model->validerRapport( $rapport_id, $enseignant_id,$commentaire, $decision);
            echo json_encode(['success' => true, 'message' => 'Rapport validé avec succès']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la validation: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Récupère les détails d'un rapport (AJAX)
     */
    public function getRapportDetails() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $rapport_id = $_GET['rapport_id'] ?? '';

        if (empty($rapport_id)) {
            echo json_encode(['success' => false, 'error' => 'ID du rapport requis']);
            return;
        }

        try {
            $rapport = $this->model->getRapportDetails($rapport_id);
            $validations = $this->model->getValidationsRapport($rapport_id);
            
            echo json_encode([
                'success' => true,
                'rapport' => $rapport,
                'validations' => $validations
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les rapports pour affichage dans la vue
     */
    public function viewRapports() {
        return $this->model->getRapportsEnAttente('', '', 1, 10);
    }

    public function viewRapport($rapport_id){
        $rapport = $this->model->getRapportDetails($rapport_id);
        $validations = $this->model->getValidationsRapport($rapport_id);
        
        // Récupérer la validation de l'utilisateur actuel
        $user_validation = null;
        if (isset($_SESSION['user_id'])) {
            foreach ($validations as $validation) {
                if ($validation['id_ens'] == $_SESSION['user_id']) {
                    $user_validation = $validation;
                    break;
                }
            }
        }
        
        // Pour l'instant, on retourne un tableau vide pour les messages
        // TODO: Implémenter la récupération des messages de chat
        $messages = [];
        
        return [
            'rapport' => $rapport,
            'validations' => $validations,
            'user_validation' => $user_validation,
            'messages' => $messages
        ];
    }

    public function afficherValidation($rapport_id) {
        $data = $this->viewRapport($rapport_id);
        extract($data);
        require __DIR__ . '/../Views/validations.php';
    }

    
    /**
     * Récupère les infos détaillées d'un rapport par son ID
     */
    public function getRapports($id_rapport) {        
        return $this->model->getRapportById($id_rapport);
    }

    /**
     * Récupère les messages de discussion pour un rapport
     */
    public function getMessages($id_rapport) {
        return $this->model->getMessagesByRapport($id_rapport);

    }

    /**
     * Récupère les validations (votes, commentaires, etc.) pour un rapport
     */
    public function getValidations($id_rapport) {
        return $this->model->getValidationsByRapport($id_rapport);

    }

    /**
     * Récupère la validation de l'enseignant courant pour ce rapport
     */
    public function getValidationByEns($id_rapport, $id_ens) {
        return $this->model->getUserValidation($id_rapport, $id_ens);

    }

    /**
     * Ajoute un message de discussion à un rapport (chat commission)
     */
    public function addMessages($id_rapport, $id_ens, $message) {
        return $this->model->addMessageToRapport($id_rapport, $id_ens, $message);
    }

    /**
     * Vérifie si tous les membres de la commission ont évalué un rapport
     */
    public function tousMembresOntEvalue($rapport_id) {
        return $this->model->tousMembresOntEvalue($rapport_id);
    }

    /**
     * Force la mise à jour du statut d'un rapport
     */
    public function forcerMiseAJourStatut($rapport_id) {
        // Utiliser la réflexion pour accéder à la méthode privée
        $reflection = new ReflectionClass($this->model);
        $method = $reflection->getMethod('updateRapportStatus');
        $method->setAccessible(true);
        return $method->invoke($this->model, $rapport_id);
    }
} 