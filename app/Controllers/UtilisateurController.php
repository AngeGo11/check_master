<?php

require_once __DIR__ . '/../Models/Utilisateur.php';
require_once __DIR__ . '/../../config/mail.php';

use App\Models\Utilisateur;

class UtilisateurController {
    private $model;

    public function __construct(PDO $db) {
        $this->model = new Utilisateur($db);
    }

    // Méthodes existantes
    public function index() {
        return $this->model->getAllUtilisateurs();
    }

    public function show($id) {
        return $this->model->getUtilisateurById($id);
    }

    public function store($data) {
        return $this->model->ajouterUtilisateur(
            $data['login_utilisateur'],
            $data['mdp_utilisateur'],
            $data['statut_utilisateur'],
            $data['id_niveau_acces']
        );
    }

    public function update($id, $data) {
        return $this->model->updateUtilisateur(
            $data['login_utilisateur'],
            $data['mdp_utilisateur'],
            $data['statut_utilisateur'],
            $data['id_niveau_acces'],
            $id
        );
    }

    public function delete($id) {
        return $this->model->supprimerUtilisateur($id);
    }

    public function getInfosUser($login){
        return $this->model->getUserInfos($login);
    }

    // Nouvelles méthodes pour la gestion des utilisateurs

    /**
     * Ajoute un nouvel utilisateur avec type et envoi d'email
     */
    public function addUser($login, $type_utilisateur) {
        $result = $this->model->addUserWithType($login, $type_utilisateur);
        
        if ($result['success']) {
            // Envoi de l'email avec les identifiants
            $nom_complet = $this->model->getUserFullName($login);
            $this->sendWelcomeEmail($login, $result['password'], $nom_complet);
            return ['success' => true, 'message' => 'Utilisateur ajouté avec succès'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de l\'ajout de l\'utilisateur : ' . $result['error']];
        }
    }

    /**
     * Génère des mots de passe pour plusieurs utilisateurs et les active
     */
    public function generatePasswords($user_ids) {
        $result = $this->model->generatePasswordsForUsers($user_ids);
        
        if ($result['success_count'] > 0) {
            // Activer les utilisateurs
            foreach ($user_ids as $user_id) {
                $this->model->reactiverUtilisateur($user_id);
            }
            
            // Envoi des emails avec les nouveaux mots de passe
            foreach ($result['passwords'] as $user_data) {
                $this->sendActivationEmail($user_data['login'], $user_data['password'], $user_data['nom_complet']);
            }
        }
        
        return $result;
    }

    /**
     * Met à jour les informations d'un utilisateur
     */
    public function editUser($id_utilisateur, $type_utilisateur, $groupe_utilisateur = null, $niveaux_acces = [], $fonction = null, $grade = null, $specialite = null) {
        $result = $this->model->updateUserInfo($id_utilisateur, $type_utilisateur, $groupe_utilisateur, $niveaux_acces, $fonction, $grade, $specialite);
        
        if ($result['success']) {
            return ['success' => true, 'message' => 'Les informations de l\'utilisateur ont été mises à jour avec succès.'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour : ' . $result['error']];
        }
    }

    /**
     * Désactive un utilisateur
     */
    public function desactivateUser($id_utilisateur) {
        $result = $this->model->desactiverUtilisateur($id_utilisateur);
        
        if ($result) {
            return ['success' => true, 'message' => 'Utilisateur désactivé avec succès'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la désactivation de l\'utilisateur'];
        }
    }

    /**
     * Active un utilisateur
     */
    public function activateUser($id_utilisateur) {
        $result = $this->model->reactiverUtilisateur($id_utilisateur);
        
        if ($result) {
            return ['success' => true, 'message' => 'Utilisateur activé avec succès'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de l\'activation de l\'utilisateur'];
        }
    }

    /**
     * Affecte en masse des utilisateurs inactifs
     */
    public function assignMultipleUsers($selected_users, $type_utilisateur, $groupe_utilisateur = null, $niveau_acces = null) {
        $result = $this->model->assignMultipleUsers($selected_users, $type_utilisateur, $groupe_utilisateur, $niveau_acces);
        
        if ($result['success_count'] > 0) {
            // Envoi des emails avec les identifiants
            foreach ($result['passwords'] as $user_data) {
                $this->sendActivationEmail($user_data['login'], $user_data['password'], $user_data['nom_complet']);
            }
        }
        
        return $result;
    }

    /**
     * Récupère les utilisateurs avec pagination et filtres
     */
    public function getUtilisateursWithFilters($page = 1, $per_page = 75, $filters = []) {
        return $this->model->getUtilisateursWithFilters($page, $per_page, $filters);
    }

    /**
     * Récupère les utilisateurs inactifs pour le modal
     */
    public function getInactiveUsers() {
        return $this->model->getInactiveUsers();
    }

    /**
     * Récupère les détails complets d'un utilisateur pour l'édition
     */
    public function getUtilisateurDetails($id) {
        return $this->model->getUtilisateurDetails($id);
    }

    // Méthodes pour récupérer les données des selects
    public function getTypesUtilisateurs() {
        return $this->model->getTypesUtilisateurs();
    }

    public function getGroupesUtilisateurs() {
        return $this->model->getGroupesUtilisateurs();
    }

    public function getGrades() {
        return $this->model->getGrades();
    }

    public function getFonctions() {
        return $this->model->getFonctions();
    }

    public function getSpecialites() {
        return $this->model->getSpecialites();
    }

    public function getNiveauxAcces() {
        return $this->model->getNiveauxAcces();
    }

    public function getUtilisateursStats() {
        return $this->model->getUtilisateursStats();
    }

    // Méthodes d'envoi d'emails
    private function sendWelcomeEmail($login, $password, $nom_complet) {
        $subject = "✉️ Bienvenue sur la plateforme CHECK Master – Vos identifiants de connexion";
        $message = $this->generateWelcomeEmailTemplate($login, $password, $nom_complet);
        sendEmail("Administrateur GSCV", "axelangegomez2004@gscv.com", $login, $subject, $message);
    }

    private function sendPasswordUpdateEmail($login, $password, $nom_complet) {
        $subject = "✉️ Mise à jour de vos identifiants CHECK Master";
        $message = $this->generatePasswordUpdateEmailTemplate($login, $password, $nom_complet);
        sendEmail("Administrateur GSCV", "axelangegomez2004@gscv.com", $login, $subject, $message);
    }

    private function sendActivationEmail($login, $password, $nom_complet) {
        $subject = "✉️ Activation de votre compte CHECK Master – Vos identifiants de connexion";
        $message = $this->generateActivationEmailTemplate($login, $password, $nom_complet);
        sendEmail("Administrateur GSCV", "axelangegomez2004@gmail.com", $login, $subject, $message);
    }

    // Templates d'emails
    private function generateWelcomeEmailTemplate($login, $password, $nom_complet) {
        return '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Bienvenue sur CHECK Master</title>
            <style>
                body {
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #f4f4f4;
                    padding: 20px;
                }
                .email-container {
                    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                    border-radius: 12px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    border: 1px solid #e9ecef;
                }
                .header {
                    background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
                    color: white;
                    padding: 30px 25px;
                    text-align: center;
                    position: relative;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 700;
                }
                .content {
                    padding: 35px 25px;
                }
                .welcome-section h2 {
                    color: #1a5276;
                    font-size: 20px;
                    margin: 0 0 15px 0;
                    font-weight: 600;
                }
                .credentials-section {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 10px;
                    padding: 25px;
                    margin: 25px 0;
                    border-left: 4px solid #1a5276;
                }
                .credential-item {
                    display: flex;
                    align-items: center;
                    margin: 15px 0;
                    padding: 12px 15px;
                    background: white;
                    border-radius: 8px;
                    border: 1px solid #dee2e6;
                }
                .credential-label {
                    font-weight: 600;
                    color: #495057;
                    min-width: 120px;
                    margin-right: 15px;
                }
                .credential-value {
                    font-family: "Courier New", monospace;
                    background: #f8f9fa;
                    padding: 8px 12px;
                    border-radius: 6px;
                    border: 1px solid #dee2e6;
                    color: #1a5276;
                    font-weight: 600;
                    letter-spacing: 1px;
                }
                .security-warning {
                    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
                    border: 1px solid #ffc107;
                    border-radius: 10px;
                    padding: 20px;
                    margin: 25px 0;
                    text-align: center;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px 25px;
                    text-align: center;
                    border-top: 1px solid #e9ecef;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div style="font-size: 28px; margin-bottom: 10px;">🎓</div>
                    <h1>CHECK Master</h1>
                    <div style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.9;">Plateforme de Gestion Académique</div>
                </div>
                
                <div class="content">
                    <div class="welcome-section">
                        <h2>Bonjour ' . htmlspecialchars($nom_complet) . ',</h2>
                        <p>Bienvenue dans la plateforme <strong>CHECK Master</strong>, votre nouvel espace numérique de gestion académique et administrative.</p>
                        <p>Votre compte a été créé avec succès. Vous pouvez dès maintenant vous connecter et accéder aux services qui vous sont destinés.</p>
                    </div>
                    
                    <div class="credentials-section">
                        <h3 style="color: #1a5276; margin: 0 0 20px 0; font-size: 18px; font-weight: 600;">🔐 Vos identifiants de connexion</h3>
                        <div class="credential-item">
                            <span class="credential-label">Login :</span>
                            <span class="credential-value">' . htmlspecialchars($login) . '</span>
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">Mot de passe temporaire :</span>
                            <span class="credential-value">' . htmlspecialchars($password) . '</span>
                        </div>
                    </div>
                    
                    <div class="security-warning">
                        <h4 style="color: #856404; margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">⚠️ Important - Sécurité</h4>
                        <p style="color: #856404; margin: 0; font-size: 14px;">Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe dès votre première connexion.</p>
                    </div>
                </div>
                
                <div class="footer">
                    <p style="margin: 0; color: #6c757d; font-size: 13px;"><strong>CHECK Master</strong> - Université Félix Houphouët-Boigny</p>
                    <p style="margin: 0; color: #6c757d; font-size: 13px;">Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>';
    }

    private function generatePasswordUpdateEmailTemplate($login, $password, $nom_complet) {
        return '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Mise à jour des identifiants CHECK Master</title>
            <style>
                body {
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #f4f4f4;
                    padding: 20px;
                }
                .email-container {
                    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                    border-radius: 12px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    border: 1px solid #e9ecef;
                }
                .header {
                    background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
                    color: white;
                    padding: 30px 25px;
                    text-align: center;
                    position: relative;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 700;
                }
                .content {
                    padding: 35px 25px;
                }
                .welcome-section h2 {
                    color: #1a5276;
                    font-size: 20px;
                    margin: 0 0 15px 0;
                    font-weight: 600;
                }
                .credentials-section {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 10px;
                    padding: 25px;
                    margin: 25px 0;
                    border-left: 4px solid #1a5276;
                }
                .credential-item {
                    display: flex;
                    align-items: center;
                    margin: 15px 0;
                    padding: 12px 15px;
                    background: white;
                    border-radius: 8px;
                    border: 1px solid #dee2e6;
                }
                .credential-label {
                    font-weight: 600;
                    color: #495057;
                    min-width: 120px;
                    margin-right: 15px;
                }
                .credential-value {
                    font-family: "Courier New", monospace;
                    background: #f8f9fa;
                    padding: 8px 12px;
                    border-radius: 6px;
                    border: 1px solid #dee2e6;
                    color: #1a5276;
                    font-weight: 600;
                    letter-spacing: 1px;
                }
                .security-warning {
                    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
                    border: 1px solid #ffc107;
                    border-radius: 10px;
                    padding: 20px;
                    margin: 25px 0;
                    text-align: center;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px 25px;
                    text-align: center;
                    border-top: 1px solid #e9ecef;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div style="font-size: 28px; margin-bottom: 10px;">🎓</div>
                    <h1>CHECK Master</h1>
                    <div style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.9;">Plateforme de Gestion Académique</div>
                </div>
                
                <div class="content">
                    <div class="welcome-section">
                        <h2>Bonjour ' . htmlspecialchars($nom_complet) . ',</h2>
                        <p>Vos identifiants de connexion ont été mis à jour sur la plateforme <strong>CHECK Master</strong>.</p>
                        <p>Vous pouvez dès maintenant vous connecter avec vos nouveaux identifiants.</p>
                    </div>
                    
                    <div class="credentials-section">
                        <h3 style="color: #1a5276; margin: 0 0 20px 0; font-size: 18px; font-weight: 600;">🔐 Vos identifiants de connexion</h3>
                        <div class="credential-item">
                            <span class="credential-label">Login :</span>
                            <span class="credential-value">' . htmlspecialchars($login) . '</span>
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">Nouveau mot de passe :</span>
                            <span class="credential-value">' . htmlspecialchars($password) . '</span>
                        </div>
                    </div>
                    
                    <div class="security-warning">
                        <h4 style="color: #856404; margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">⚠️ Important - Sécurité</h4>
                        <p style="color: #856404; margin: 0; font-size: 14px;">Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe dès votre première connexion.</p>
                    </div>
                </div>
                
                <div class="footer">
                    <p style="margin: 0; color: #6c757d; font-size: 13px;"><strong>CHECK Master</strong> - Université Félix Houphouët-Boigny</p>
                    <p style="margin: 0; color: #6c757d; font-size: 13px;">Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>';
    }

    private function generateActivationEmailTemplate($login, $password, $nom_complet) {
        return '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Activation de votre compte CHECK Master</title>
            <style>
                body {
                    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    background-color: #f4f4f4;
                    padding: 20px;
                }
                .email-container {
                    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
                    border-radius: 12px;
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
                    overflow: hidden;
                    border: 1px solid #e9ecef;
                }
                .header {
                    background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
                    color: white;
                    padding: 30px 25px;
                    text-align: center;
                    position: relative;
                }
                .header h1 {
                    margin: 0;
                    font-size: 24px;
                    font-weight: 700;
                }
                .content {
                    padding: 35px 25px;
                }
                .welcome-section h2 {
                    color: #1a5276;
                    font-size: 20px;
                    margin: 0 0 15px 0;
                    font-weight: 600;
                }
                .credentials-section {
                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                    border-radius: 10px;
                    padding: 25px;
                    margin: 25px 0;
                    border-left: 4px solid #1a5276;
                }
                .credential-item {
                    display: flex;
                    align-items: center;
                    margin: 15px 0;
                    padding: 12px 15px;
                    background: white;
                    border-radius: 8px;
                    border: 1px solid #dee2e6;
                }
                .credential-label {
                    font-weight: 600;
                    color: #495057;
                    min-width: 120px;
                    margin-right: 15px;
                }
                .credential-value {
                    font-family: "Courier New", monospace;
                    background: #f8f9fa;
                    padding: 8px 12px;
                    border-radius: 6px;
                    border: 1px solid #dee2e6;
                    color: #1a5276;
                    font-weight: 600;
                    letter-spacing: 1px;
                }
                .security-warning {
                    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
                    border: 1px solid #ffc107;
                    border-radius: 10px;
                    padding: 20px;
                    margin: 25px 0;
                    text-align: center;
                }
                .footer {
                    background: #f8f9fa;
                    padding: 20px 25px;
                    text-align: center;
                    border-top: 1px solid #e9ecef;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <div style="font-size: 28px; margin-bottom: 10px;">🎓</div>
                    <h1>CHECK Master</h1>
                    <div style="margin: 8px 0 0 0; font-size: 14px; opacity: 0.9;">Plateforme de Gestion Académique</div>
                </div>
                
                <div class="content">
                    <div class="welcome-section">
                        <h2>Bonjour ' . htmlspecialchars($nom_complet) . ',</h2>
                        <p>Votre compte a été <strong>activé avec succès</strong> sur la plateforme <strong>CHECK Master</strong>.</p>
                        <p>Vous pouvez dès maintenant vous connecter et accéder aux services qui vous sont destinés.</p>
                    </div>
                    
                    <div class="credentials-section">
                        <h3 style="color: #1a5276; margin: 0 0 20px 0; font-size: 18px; font-weight: 600;">🔐 Vos identifiants de connexion</h3>
                        <div class="credential-item">
                            <span class="credential-label">Login :</span>
                            <span class="credential-value">' . htmlspecialchars($login) . '</span>
                        </div>
                        <div class="credential-item">
                            <span class="credential-label">Mot de passe temporaire :</span>
                            <span class="credential-value">' . htmlspecialchars($password) . '</span>
                        </div>
                    </div>
                    
                    <div class="security-warning">
                        <h4 style="color: #856404; margin: 0 0 10px 0; font-size: 16px; font-weight: 600;">⚠️ Important - Sécurité</h4>
                        <p style="color: #856404; margin: 0; font-size: 14px;">Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe dès votre première connexion.</p>
                    </div>
                </div>
                
                <div class="footer">
                    <p style="margin: 0; color: #6c757d; font-size: 13px;"><strong>CHECK Master</strong> - Université Félix Houphouët-Boigny</p>
                    <p style="margin: 0; color: #6c757d; font-size: 13px;">Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>';
    }
} 
