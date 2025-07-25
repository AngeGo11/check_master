<?php

require_once __DIR__ . '/../Models/Utilisateur.php';
require_once __DIR__ . '/../../config/config.php';

class ParameterController {
    private $model;

    public function __construct() {
        $this->model = new \App\Models\Utilisateur(DataBase::getConnection());
    }

    /**
     * Affiche la page de gestion du profil utilisateur
     */
    public function index() {
        // Vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Récupération des informations de l'utilisateur connecté
        $userInfo = $this->model->getUserInfos($_SESSION['login'] ?? '');

        // Inclusion de la vue
        include __DIR__ . '/../Views/parameters.php';
    }

    /**
     * Met à jour le profil utilisateur (AJAX)
     */
    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $login = $_POST['login'] ?? '';
        $email = $_POST['email'] ?? '';
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';

        if (empty($login)) {
            echo json_encode(['success' => false, 'error' => 'Login requis']);
            return;
        }

        try {
            // Vérifier si l'utilisateur existe
            $userInfo = $this->model->getUserInfos($login);
            if (!$userInfo) {
                echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
                return;
            }

            // Si un nouveau mot de passe est fourni, utiliser updatePassword
            if (!empty($newPassword)) {
                $result = $this->model->updatePassword($userInfo->id_utilisateur, $newPassword);
            } else {
                // Sinon utiliser updateUtilisateur avec le mot de passe actuel
                $result = $this->model->updateUtilisateur(
                    $login, 
                    $userInfo->mdp_utilisateur, // Garder le mot de passe actuel
                    $userInfo->statut_utilisateur ?? 'Actif', 
                    $userInfo->id_niveau_acces ?? 1, 
                    $userInfo->id_utilisateur
                );
            }
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour du profil']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les informations du profil utilisateur (AJAX)
     */
    public function getProfile() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $login = $_GET['login'] ?? $_SESSION['login'] ?? '';

        if (empty($login)) {
            echo json_encode(['success' => false, 'error' => 'Login requis']);
            return;
        }

        try {
            $userInfo = $this->model->getUserInfos($login);
            if ($userInfo) {
                echo json_encode(['success' => true, 'user' => $userInfo]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la récupération: ' . $e->getMessage()]);
        }
    }

    /**
     * Change le mot de passe (AJAX)
     */
    public function changePassword() {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis']);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'error' => 'Les mots de passe ne correspondent pas']);
            return;
        }

        try {
            $userInfo = $this->model->getUserInfos($_SESSION['login'] ?? '');
            if (!$userInfo) {
                echo json_encode(['success' => false, 'error' => 'Utilisateur non trouvé']);
                return;
            }

            // Utiliser updatePassword pour changer uniquement le mot de passe
            $result = $this->model->updatePassword($userInfo->id_utilisateur, $newPassword);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Mot de passe changé avec succès']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Erreur lors du changement de mot de passe']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erreur lors du changement: ' . $e->getMessage()]);
        }
    }

    /**
     * Récupère les données de paramètres pour affichage dans la vue
     */
    public function viewParameters() {
        $login = $_SESSION['login'] ?? '';
        
        // Récupération des données utilisateur
        $userInfo = $this->model->getUserInfos($login);
        
        // Création des données pour la vue
        $userData = $userInfo ? (array)$userInfo : [];
        $userType = $userInfo->role ?? 'Utilisateur';
        $profilePhoto = '/GSCV+/public/assets/images/default-profile.png';
        
        return [
            'userData' => $userData,
            'userType' => $userType,
            'profilePhoto' => $profilePhoto,
            'errors' => [],
            'success_message' => ''
        ];
    }
} 