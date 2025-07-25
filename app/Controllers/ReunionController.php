<?php

require_once __DIR__ . '/../Models/ReunionModel.php';

class ReunionController {
    private $model;

    public function __construct() {
        $this->model = new ReunionModel();
    }

    /**
     * Affiche la page des réunions
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
            $_SESSION['error_message'] = "Accès non autorisé à l'interface des réunions";
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date_filter = $_GET['date_filter'] ?? '';
        $statut_filter = $_GET['statut_filter'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));

        // Récupération des données
        $reunions_data = $this->model->getReunions($search, $date_filter, $statut_filter, $page, 10);

        // Extraction des données pour la vue
        $reunions = $reunions_data['reunions'];
        $total_pages = $reunions_data['pages'];
        $total_reunions = $reunions_data['total'];

        // Inclusion de la vue
        include __DIR__ . '/../Views/reunions.php';
    }

    /**
     * Crée une nouvelle réunion (AJAX)
     */
    public function createReunion() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $titre = $_POST['titre'] ?? '';
        $description = $_POST['description'] ?? '';
        $date_reunion = $_POST['date_reunion'] ?? '';
        $heure_debut = $_POST['heure_debut'] ?? '';
        $heure_fin = $_POST['heure_fin'] ?? '';
        $lieu = $_POST['lieu'] ?? '';
        $type = $_POST['type'] ?? 'normale';

        if (empty($titre) || empty($date_reunion) || empty($heure_debut) || empty($lieu)) {
            echo json_encode(['success' => false, 'error' => 'Tous les champs obligatoires doivent être remplis']);
            return;
        }

        // Calculer la durée en minutes
        $duree = 60; // Durée par défaut
        if (!empty($heure_fin)) {
            $debut = new DateTime($heure_debut);
            $fin = new DateTime($heure_fin);
            $duree = ($fin->getTimestamp() - $debut->getTimestamp()) / 60; // Durée en minutes
        }

        try {
            $this->model->createReunion($titre, $description, $date_reunion, $heure_debut, $duree, $lieu, $type);
            echo json_encode(['success' => true, 'message' => 'Réunion créée avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la création: ' . $e->getMessage()]);
        }
    }

    /**
     * Met à jour une réunion (AJAX)
     */
    public function updateReunion() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $id = $_POST['id'] ?? '';
        $titre = $_POST['titre'] ?? '';
        $description = $_POST['description'] ?? '';
        $date_reunion = $_POST['date_reunion'] ?? '';
        $heure_debut = $_POST['heure_debut'] ?? '';
        $heure_fin = $_POST['heure_fin'] ?? '';
        $lieu = $_POST['lieu'] ?? '';
        $type = $_POST['type'] ?? 'normale';
        $statut = $_POST['statut'] ?? 'programmée';

        if (empty($id) || empty($titre) || empty($date_reunion) || empty($heure_debut) || empty($lieu)) {
            echo json_encode(['success' => false, 'error' => 'Tous les champs obligatoires doivent être remplis']);
            return;
        }

        // Calculer la durée en minutes
        $duree = 60; // Durée par défaut
        if (!empty($heure_fin)) {
            $debut = new DateTime($heure_debut);
            $fin = new DateTime($heure_fin);
            $duree = ($fin->getTimestamp() - $debut->getTimestamp()) / 60; // Durée en minutes
        }

        try {
            $this->model->updateReunion($id, $titre, $description, $date_reunion, $heure_debut, $duree, $lieu, $type, $statut);
            echo json_encode(['success' => true, 'message' => 'Réunion mise à jour avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    /**
     * Supprime une réunion (AJAX)
     */
    public function deleteReunion() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'ID de la réunion requis']);
            return;
        }

        try {
            $this->model->deleteReunion($id);
            echo json_encode(['success' => true, 'message' => 'Réunion supprimée avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la suppression: ' . $e->getMessage()]);
        }
    }

    /**
     * Change le statut d'une réunion (AJAX)
     */
    public function changeStatut() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $id = $_POST['id'] ?? '';
        $statut = $_POST['statut'] ?? '';

        if (empty($id) || empty($statut)) {
            echo json_encode(['success' => false, 'error' => 'ID et statut requis']);
            return;
        }

        try {
            $this->model->changeStatutReunion($id, $statut);
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les données de réunions pour affichage dans la vue
     */
    public function viewReunions() {
        // Récupération des paramètres
        $search = $_GET['search'] ?? '';
        $date_filter = $_GET['date'] ?? '';
        $statut_filter = $_GET['statut'] ?? '';
        $page = max(1, intval($_GET['page_num'] ?? 1));
        
        // Récupération des données avec les bons paramètres
        $reunions_data = $this->model->getReunions($search, $date_filter, $statut_filter, $page, 10);
        
        // Création de statistiques basiques
        $statistics = [
            'reunions_planifiees' => 0,
            'rapports_a_examiner' => 0,
            'membres_actifs' => 0,
            'notes_archives' => 0
        ];
        
        // Compter les réunions planifiées
        if (isset($reunions_data['reunions'])) {
            foreach ($reunions_data['reunions'] as $reunion) {
                if ($reunion['status'] === 'programmée') {
                    $statistics['reunions_planifiees']++;
                }
            }
        }
        
        return [
            'reunions' => $reunions_data['reunions'],
            'statistics' => $statistics,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $reunions_data['pages'],
                'total_items' => $reunions_data['total']
            ],
            'filters' => [
                'search' => $search,
                'statut' => $statut_filter,
                'type' => $_GET['type'] ?? '',
                'date' => $date_filter
            ]
        ];
    }
} 