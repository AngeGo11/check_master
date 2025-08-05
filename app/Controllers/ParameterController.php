<?php

require_once __DIR__ . '/../Models/Utilisateur.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../public/assets/traitements/check_database_structure.php';

class ParameterController
{
    private $model;

    public function __construct()
    {
        $this->model = new \App\Models\Utilisateur(DataBase::getConnection());
    }

    /**
     * Affiche la page de gestion du profil utilisateur
     */
    public function index()
    {
        // Vérification de connexion
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../../public/pageConnexion.php');
            exit();
        }

        // Récupération des informations de l'utilisateur connecté
        $userInfo = $this->model->getUserInfos($_SESSION['login_utilisateur'] ?? '');

        // Inclusion de la vue
        include __DIR__ . '/../Views/parameters.php';
    }

    /**
     * Met à jour le profil utilisateur
     */
    public function updateProfile()
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'error' => 'Non autorisé'];
        }

        $userId = $_SESSION['user_id'];
        $userInfo = $this->getInfosCurrentUser($userId);
        
        if (!$userInfo) {
            return ['success' => false, 'error' => 'Utilisateur non trouvé'];
        }

        $userData = $userInfo['userData'];
        $userType = $userInfo['userType'];

        // Récupération et nettoyage des données du formulaire
        $nom = trim($_POST['nom'] ?? '');
        $prenoms = trim($_POST['prenoms'] ?? '');
        $email = trim($_POST['adresse_mail'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        
        // Inclure les champs adresse, ville, pays si présents dans le formulaire (pour les étudiants)
        $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : null;
        $ville = isset($_POST['ville']) ? trim($_POST['ville']) : null;
        $pays = isset($_POST['pays']) ? trim($_POST['pays']) : null;

        $errors = [];

        // Validation des champs communs
        if (empty($nom)) {
            $errors[] = "Le nom est requis";
        }
        if (empty($prenoms)) {
            $errors[] = "Les prénoms sont requis";
        }
        if (empty($email)) {
            $errors[] = "L'email est requis";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'email invalide";
        }

        // Validation des champs spécifiques pour les étudiants
        if ($userType === 'Étudiant') {
            if (empty($adresse)) {
                $errors[] = "L'adresse est requise";
            }
            if (empty($ville)) {
                $errors[] = "La ville est requise";
            }
            if (empty($pays)) {
                $errors[] = "Le pays est requis";
            }
        }

        // Si il y a des erreurs de validation, les retourner
        if (!empty($errors)) {
            return ['success' => false, 'error' => implode(', ', $errors)];
        }

        try {
            $pdo = DataBase::getConnection();
            $pdo->beginTransaction();

            // Déterminer le type d'utilisateur pour la mise à jour
            if ($userType === 'Enseignant simple' || $userType === 'Enseignant administratif') {
                // Mise à jour pour un enseignant
                $sql = "UPDATE enseignants SET 
                        nom_ens = ?, 
                        prenoms_ens = ?, 
                        email_ens = ?, 
                        num_tel_ens = ? 
                        WHERE email_ens = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nom, $prenoms, $email, $telephone, $userData['email_ens']]);
                
            } elseif ($userType === 'Personnel administratif') {
                // Mise à jour pour le personnel administratif
                $sql = "UPDATE personnel_administratif SET 
                        nom_personnel_adm = ?, 
                        prenoms_personnel_adm = ?, 
                        email_personnel_adm = ?, 
                        tel_personnel_adm = ? 
                        WHERE email_personnel_adm = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nom, $prenoms, $email, $telephone, $userData['email_personnel_adm']]);
                
            } elseif ($userType === 'Étudiant') {
                // Mise à jour pour un étudiant
                $sql = "UPDATE etudiants SET 
                        nom_etd = ?, 
                        prenom_etd = ?, 
                        email_etd = ?, 
                        num_tel_etd = ?, 
                        adresse_etd = ?, 
                        ville_etd = ?, 
                        pays_etd = ? 
                        WHERE num_etd = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nom, $prenoms, $email, $telephone, $adresse, $ville, $pays, $userData['num_etd']]);
            }

            // Mise à jour de l'email dans la table utilisateur
            $sql = "UPDATE utilisateur SET login_utilisateur = ? WHERE id_utilisateur = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email, $userId]);

            $pdo->commit();
            return ['success' => true, 'message' => "Vos modifications ont été enregistrées avec succès (elles se mettront à jour après la prochaine connexion)"];
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => "Une erreur est survenue lors de la mise à jour : " . $e->getMessage()];
        }
    }

    /**
     * Met à jour la photo de profil
     */
    public function updateProfilePhoto()
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'error' => 'Non autorisé'];
        }

        // Vérifier si un fichier a été uploadé
        if (!isset($_FILES['change']) || $_FILES['change']['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Aucun fichier uploadé ou erreur lors de l\'upload'];
        }

        $userId = $_SESSION['user_id'];
        $userInfo = $this->getInfosCurrentUser($userId);
        
        if (!$userInfo) {
            return ['success' => false, 'error' => 'Utilisateur non trouvé'];
        }

        $userData = $userInfo['userData'];
        $userType = $userInfo['userType'];

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        $currentPhoto = null;
        $updateIdentifier = null;
        $tableName = null;
        $photoColumn = null;
        $whereColumn = null;

        // Déterminer la table et la colonne de photo en fonction du type d'utilisateur
        if ($userType === 'Enseignant simple' || $userType === 'Enseignant administratif') {
            $currentPhoto = $userData['photo_profil_ens'] ?? null;
            $updateIdentifier = $userData['login_utilisateur'] ?? null;
            $tableName = 'enseignants';
            $photoColumn = 'photo_ens';
            $whereColumn = 'email_ens';
        } elseif ($userType === 'Personnel administratif') {
            $currentPhoto = $userData['photo_profil_pa'] ?? null;
            $updateIdentifier = $userData['login_utilisateur'] ?? null;
            $tableName = 'personnel_administratif';
            $photoColumn = 'photo_personnel_adm';
            $whereColumn = 'email_personnel_adm';
        } elseif ($userType === 'Étudiant') {
            $currentPhoto = $userData['photo_profil_etd'] ?? null;
            $updateIdentifier = $userData['num_etd'] ?? null;
            $tableName = 'etudiants';
            $photoColumn = 'photo_etd';
            $whereColumn = 'num_etd';
        }

        if (!$tableName || !$updateIdentifier) {
            return ['success' => false, 'error' => "Type d'utilisateur inconnu ou identifiant manquant pour la mise à jour de la photo."];
        }

        if (!in_array($_FILES['change']['type'], $allowedTypes)) {
            return ['success' => false, 'error' => "Format d'image non supporté. Utilisez JPG, PNG ou GIF"];
        }

        if ($_FILES['change']['size'] > $maxSize) {
            return ['success' => false, 'error' => "L'image ne doit pas dépasser 5MB"];
        }

        $uploadDir = 'storage/uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . basename($_FILES['change']['name']);
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['change']['tmp_name'], $uploadPath)) {
            try {
                $pdo = DataBase::getConnection();
                $pdo->beginTransaction();

                // Supprimer l'ancienne photo si elle existe et n'est pas l'image par défaut
                if (!empty($currentPhoto) && file_exists($currentPhoto) && $currentPhoto !== 'images/pdp.jpg') {
                    unlink($currentPhoto);
                }

                // Enregistrer uniquement le nom du fichier dans la base de données
                $sql = "UPDATE {$tableName} SET {$photoColumn} = ? WHERE {$whereColumn} = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$fileName, $updateIdentifier]);

                $pdo->commit();
                return ['success' => true, 'message' => 'Photo de profil mise à jour avec succès'];
            } catch (PDOException $e) {
                $pdo->rollBack();
                return ['success' => false, 'error' => 'Erreur lors de la mise à jour de la photo : ' . $e->getMessage()];
            }
        } else {
            return ['success' => false, 'error' => 'Erreur lors du téléchargement de l\'image.'];
        }
    }

    /**
     * Supprime la photo de profil
     */
    public function deleteProfilePhoto()
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'error' => 'Non autorisé'];
        }

        $userId = $_SESSION['user_id'];
        $userInfo = $this->getInfosCurrentUser($userId);
        
        if (!$userInfo) {
            return ['success' => false, 'error' => 'Utilisateur non trouvé'];
        }

        $userData = $userInfo['userData'];
        $userType = $userInfo['userType'];

        $currentPhoto = null;
        $deleteIdentifier = null;
        $tableName = null;
        $photoColumn = null;
        $whereColumn = null;

        // Déterminer la table et la colonne de photo en fonction du type d'utilisateur
        if ($userType === 'Enseignant simple' || $userType === 'Enseignant administratif') {
            $currentPhoto = $userData['photo_profil_ens'] ?? null;
            $deleteIdentifier = $userData['login_utilisateur'] ?? null;
            $tableName = 'enseignants';
            $photoColumn = 'photo_ens';
            $whereColumn = 'email_ens';
        } elseif ($userType === 'Personnel administratif') {
            $currentPhoto = $userData['photo_profil_pa'] ?? null;
            $deleteIdentifier = $userData['login_utilisateur'] ?? null;
            $tableName = 'personnel_administratif';
            $photoColumn = 'photo_personnel_adm';
            $whereColumn = 'email_personnel_adm';
        } elseif ($userType === 'Étudiant') {
            $currentPhoto = $userData['photo_profil_etd'] ?? null;
            $deleteIdentifier = $userData['num_etd'] ?? null;
            $tableName = 'etudiants';
            $photoColumn = 'photo_etd';
            $whereColumn = 'num_etd';
        }

        if (!$tableName || !$deleteIdentifier) {
            return ['success' => false, 'error' => "Type d'utilisateur inconnu ou identifiant manquant pour la suppression de la photo."];
        }

        try {
            $pdo = DataBase::getConnection();
            $pdo->beginTransaction();

            // Supprimer le fichier photo si elle existe et n'est pas l'image par défaut
            if (!empty($currentPhoto) && file_exists($currentPhoto) && $currentPhoto !== 'images/pdp.jpg') {
                unlink($currentPhoto);
            }

            // Mettre à NULL la colonne photo dans la base de données
            $sql = "UPDATE {$tableName} SET {$photoColumn} = NULL WHERE {$whereColumn} = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$deleteIdentifier]);

            $pdo->commit();
            return ['success' => true, 'message' => 'Photo de profil supprimée avec succès'];
        } catch (PDOException $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la suppression de la photo : ' . $e->getMessage()];
        }
    }

    /**
     * Change le mot de passe
     */
    public function changePassword()
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'error' => 'Non autorisé'];
        }

        $ancienMdp = $_POST['ancien_mdp'] ?? '';
        $nouveauMdp = $_POST['nouveau_mdp'] ?? '';
        $confirmMdp = $_POST['confirm_mdp'] ?? '';
        $userId = $_SESSION['user_id'];

        // Validation des champs
        if (empty($ancienMdp)) {
            return ['success' => false, 'error' => "L'ancien mot de passe est requis"];
        }
        if (empty($nouveauMdp)) {
            return ['success' => false, 'error' => "Le nouveau mot de passe est requis"];
        } elseif (strlen($nouveauMdp) < 8) {
            return ['success' => false, 'error' => "Le nouveau mot de passe doit contenir au moins 8 caractères"];
        }
        if (empty($confirmMdp)) {
            return ['success' => false, 'error' => "La confirmation du mot de passe est requise"];
        } elseif ($nouveauMdp !== $confirmMdp) {
            return ['success' => false, 'error' => "Les mots de passe ne correspondent pas"];
        }

        // Vérification de l'ancien mot de passe si pas d'erreurs de validation
        if ($userId) {
        try {
            $pdo = DataBase::getConnection();
                $pdo->beginTransaction();

                // Récupérer le mot de passe actuel de l'utilisateur
                $sql = "SELECT mdp_utilisateur FROM utilisateur WHERE id_utilisateur = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$userId]);
                $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$currentUser) {
                    $pdo->rollBack();
                    return ['success' => false, 'error' => "Utilisateur non trouvé"];
                } else {
                    // Vérifier l'ancien mot de passe (avec le même système de hachage)
                    $hashedOldPassword = hash('sha256', $ancienMdp);

                    if ($hashedOldPassword !== $currentUser['mdp_utilisateur']) {
                        $pdo->rollBack();
                        return ['success' => false, 'error' => "L'ancien mot de passe est incorrect"];
                    } else {
                        // Vérifier que le nouveau mot de passe est différent de l'ancien
                        if ($ancienMdp === $nouveauMdp) {
                            $pdo->rollBack();
                            return ['success' => false, 'error' => "Le nouveau mot de passe doit être différent de l'ancien"];
                        } else {
                            // Tout est valide, procéder à la mise à jour
                            $hashedNewPassword = hash('sha256', $nouveauMdp);

                            $sqlUpdate = "UPDATE utilisateur SET mdp_utilisateur = ? WHERE id_utilisateur = ?";
                            $stmtUpdate = $pdo->prepare($sqlUpdate);
                            $stmtUpdate->execute([$hashedNewPassword, $userId]);

                            $pdo->commit();
                            return ['success' => true, 'message' => "Votre mot de passe a été modifié avec succès"];
                        }
                    }
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                return ['success' => false, 'error' => "Une erreur est survenue lors de la modification du mot de passe : " . $e->getMessage()];
            }
        }

        return ['success' => false, 'error' => 'Erreur lors du changement de mot de passe'];
    }

    /**
     * Met à jour les préférences de notification
     */
    public function updateNotifications()
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'error' => 'Non autorisé'];
        }

        $userId = $_SESSION['user_id'];
        $userTypeLabel = $_SESSION['lib_user_type'] ?? 'Utilisateur';
        $userType = $this->getTechnicalUserType($userTypeLabel);
        
        $notifEmail = isset($_POST['notif_email']) ? 1 : 0;
        $notifSms = isset($_POST['notif_sms']) ? 1 : 0;
        $notifPush = isset($_POST['notif_push']) ? 1 : 0;

        try {
            $pdo = DataBase::getConnection();
            
            // Supprimer les anciennes préférences de notification
            $stmt = $pdo->prepare("
                DELETE FROM messages 
                WHERE expediteur_id = ? 
                AND type_message = 'notification' 
                AND categorie = 'preferences'
            ");
            $stmt->execute([$userId]);
            
            // Créer un message pour stocker les préférences de notification
            $preferencesData = json_encode([
                'notif_email' => $notifEmail,
                'notif_sms' => $notifSms,
                'notif_push' => $notifPush,
                'user_type' => $userType
            ]);
            
            $stmt = $pdo->prepare("
                INSERT INTO messages (
                    expediteur_id, 
                    destinataire_id, 
                    destinataire_type, 
                    objet, 
                    contenu, 
                    type_message, 
                    categorie, 
                    priorite, 
                    statut, 
                    date_creation
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $userId,           // expediteur_id (l'utilisateur lui-même)
                $userId,           // destinataire_id (l'utilisateur lui-même)
                'individuel',      // destinataire_type
                'Préférences de notification', // objet
                $preferencesData,  // contenu (JSON des préférences)
                'notification',    // type_message
                'preferences',     // categorie
                'basse',           // priorite
                'envoyé'           // statut
            ]);

            return ['success' => true, 'message' => 'Préférences mises à jour avec succès'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()];
        }
    }

    /**
     * Récupère les données de paramètres pour affichage dans la vue
     */
    public function viewParameters()
    {
        $userId = $_SESSION['user_id'] ?? '';
        
        if (empty($userId)) {
            return [
                'userData' => [],
                'userType' => 'Utilisateur',
                'profilePhoto' => 'default_profile.jpg',
                'errors' => ['Session expirée'],
                'success_message' => ''
            ];
        }
        
        // Récupération des données utilisateur avec la nouvelle fonction
        $userInfo = $this->getInfosCurrentUser($userId);
        
        if (!$userInfo) {
            return [
                'userData' => [],
                'userType' => 'Utilisateur',
                'profilePhoto' => 'default_profile.jpg',
                'errors' => ['Erreur lors de la récupération des données utilisateur'],
                'success_message' => ''
            ];
        }
        
        // Récupérer les préférences de notification
        $notifications = $this->getNotificationPreferences($userId, $userInfo['userType']);
        
        // Récupérer le statut 2FA
        $twoFactorStatus = $this->getTwoFactorStatus($userId, $userInfo['userType']);
        
        // Création des données pour la vue en fusionnant toutes les informations
        $userData = array_merge($userInfo['userData'], $notifications, $twoFactorStatus);
        
        return [
            'userData' => $userData,
            'userType' => $userInfo['userType'],
            'profilePhoto' => $userInfo['profilePhoto'],
            'errors' => [],
            'success_message' => ''
        ];
    }

    /**
     * Traite les actions POST
     */
    public function handlePost()
    {
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'error' => 'Non autorisé'];
        }

        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_profile':
                return $this->updateProfile();
            case 'change_password':
                return $this->changePassword();
            case 'update_notifications':
                return $this->updateNotifications();
            case 'update_profile_photo':
                return $this->updateProfilePhoto();
            case 'delete_profile_photo':
                return $this->deleteProfilePhoto();
            default:
                return ['success' => false, 'error' => 'Action non reconnue'];
        }
    }

    // Fonctions utilitaires
    private function getUserTypeLabel($userType)
    {
        switch ($userType) {
            case 'enseignant':
                return 'Enseignant';
            case 'etudiant':
                return 'Étudiant';
            case 'personnel_adm':
                return 'Personnel Administratif';
            default:
                return 'Utilisateur';
        }
    }

    private function getNotificationPreferences($userId, $userType)
    {
        $pdo = DataBase::getConnection();
        
        $stmt = $pdo->prepare("
            SELECT contenu 
            FROM messages 
            WHERE expediteur_id = ? 
            AND type_message = 'notification' 
            AND categorie = 'preferences'
            ORDER BY date_creation DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['contenu']) {
            $preferences = json_decode($result['contenu'], true);
            if ($preferences) {
                return [
                    'notif_email' => (bool)($preferences['notif_email'] ?? true),
                    'notif_sms' => (bool)($preferences['notif_sms'] ?? false),
                    'notif_push' => (bool)($preferences['notif_push'] ?? false)
                ];
            }
        }
        
        // Valeurs par défaut si aucune préférence n'est trouvée
        return [
            'notif_email' => true,
            'notif_sms' => false,
            'notif_push' => false
        ];
    }

    private function getTwoFactorStatus($userId, $userType)
    {
        $pdo = DataBase::getConnection();
        
        $stmt = $pdo->prepare("
            SELECT enabled FROM two_factor_auth 
            WHERE user_id = ? AND user_type = ? AND enabled = TRUE
        ");
        $stmt->execute([$userId, $userType]);
        $enabled = $stmt->fetchColumn();
        
        return ['two_factor_enabled' => (bool)$enabled];
    }

    /**
     * Détermine le type technique à partir du libellé du type d'utilisateur
     */
    private function getTechnicalUserType($userTypeLabel)
    {
        switch (strtolower($userTypeLabel)) {
            case 'étudiant':
            case 'etudiant':
                return 'etudiant';
            case 'enseignant':
                return 'enseignant';
            case 'personnel administratif':
            case 'personnel_adm':
                return 'personnel_adm';
            default:
                return 'utilisateur';
        }
    }


    private function getInfosCurrentUser($userId)
    {
        $pdo = DataBase::getConnection();

        $sql = "SELECT 
        u.id_utilisateur,
        u.login_utilisateur,
        ens.nom_ens,
        ens.prenoms_ens,
        ens.email_ens,
        ens.photo_ens as photo_profil_ens,
        ens.num_tel_ens,
        etd.nom_etd,
        etd.prenom_etd,
        etd.email_etd,
        etd.photo_etd as photo_profil_etd,
        etd.num_tel_etd,
        etd.adresse_etd,
        etd.ville_etd,
        etd.pays_etd,
        tu.lib_tu,
        gu.lib_gu,
        f.nom_fonction,
        pa.nom_personnel_adm,
        pa.prenoms_personnel_adm,
        pa.email_personnel_adm,
        pa.tel_personnel_adm,
        pa.photo_personnel_adm as photo_profil_pa,
        etd.num_etd
    FROM utilisateur u
    LEFT JOIN enseignants ens ON ens.email_ens = u.login_utilisateur
    LEFT JOIN personnel_administratif pa ON pa.email_personnel_adm = u.login_utilisateur
    LEFT JOIN etudiants etd ON etd.email_etd = u.login_utilisateur
    LEFT JOIN posseder p ON p.id_util = u.id_utilisateur
    LEFT JOIN groupe_utilisateur gu ON p.id_gu = gu.id_gu
    LEFT JOIN type_a_groupe tag ON tag.id_gu = gu.id_gu
    LEFT JOIN type_utilisateur tu ON tu.id_tu = tag.id_tu
    LEFT JOIN occuper o ON o.id_ens = ens.id_ens
    LEFT JOIN fonction f ON f.id_fonction = o.id_fonction
    WHERE u.id_utilisateur = ?";

        try {
            $recupUser = $pdo->prepare($sql);
            $recupUser->execute([$userId]);
            $userData = $recupUser->fetch(PDO::FETCH_ASSOC);

            // Vérification des données
            if (!$userData) {
                return null;
            }

            $userType = $userData['lib_tu'] ?? '';
            $profilePhoto = 'default_profile.jpg';

            // Déterminer la photo de profil correcte en fonction du type d'utilisateur pour l'affichage
            if ($userType === 'Enseignant simple' || $userType === 'Enseignant administratif') {
                $profilePhoto = $userData['photo_profil_ens'] ? $userData['photo_profil_ens'] : 'default_profile.jpg';
            } elseif ($userType === 'Personnel administratif') {
                $profilePhoto = $userData['photo_profil_pa'] ? $userData['photo_profil_pa'] : 'default_profile.jpg';
            } elseif ($userType === 'Étudiant') {
                $profilePhoto = $userData['photo_profil_etd'] ? $userData['photo_profil_etd'] : 'default_profile.jpg';
            }

            return [
                'userData' => $userData,
                'userType' => $userType,
                'profilePhoto' => $profilePhoto
            ];
        } catch (Exception $e) {
            error_log('Erreur dans getInfosCurrentUser: ' . $e->getMessage());
            return null;
        }
    }

}
