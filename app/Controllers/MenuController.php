<?php

require_once __DIR__ . '/../Models/Traitement.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../../config/paths.php';
use \APP\Models\Traitement;




class MenuController
{
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function hasPermission($pageKey)
    {
        if (!isset($_SESSION['user_permissions']) || !isset($_SESSION['user_id'])) {
            return false;
        }

        // Vérification de l'expiration de la session (30 minutes)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            session_unset();
            session_destroy();
            header('Location: pageConnexion.php');
            exit();
        }

        // Mise à jour du timestamp de dernière activité
        $_SESSION['last_activity'] = time();
        
        return in_array(strtolower($pageKey), $_SESSION['user_permissions']);
    }

    public function genererMenu($idGroupe) {
        $traitement = new Traitement($this->db);
        return $traitement->getTraitementByGU($idGroupe);
    }

    // Fonction pour afficher le menu avec contrôle des permissions
    public function displayMenu()
    {
        $currentPage = $_GET['page'] ?? 'dashboard';
        $currentType = $_GET['type'] ?? '';
        $userGroupId = $_SESSION['id_user_group'] ?? 0;
        
        // Déterminer le chemin de base selon le contexte
        $basePath = $this->getBasePath();
        
        $menuHtml = '<ul class="sidebar-menu">';

        // Utiliser le système de génération de menu basé sur les traitements
        $traitements = $this->genererMenu($userGroupId);

        if (!empty($traitements)) {
            foreach ($traitements as $traitement) {
                $activeClass = ($currentPage === $traitement['lib_traitement']) ? 'class="active"' : '';
                $typeParam = $currentType ? "&type={$currentType}" : '';
                $menuHtml .= "<li>
                                <a href=\"{$basePath}app.php?page={$traitement['lib_traitement']}{$typeParam}\" {$activeClass}>
                                    <i class=\"{$traitement['classe_icone']}\"></i>
                                    <span>{$traitement['nom_traitement']}</span>
                                </a>
                              </li>";
            }
        } else {
            // Menu par défaut si aucun traitement trouvé
            $menuHtml .= $this->getDefaultMenu($currentPage, $currentType, $basePath);
        }

        // Ajouter le lien de déconnexion
        $menuHtml .= '<li>
                        <a href="' . $basePath . 'logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Déconnexion</span>
                        </a>
                      </li>';
        $menuHtml .= '</ul>';

        return $menuHtml;
    }

    // Fonction pour déterminer le chemin de base
    private function getBasePath()
    {
        return BASE_PATH;
    }

    // Menu par défaut (basé sur les permissions de la base de données)
    private function getDefaultMenu($currentPage, $currentType = '', $basePath = '')
    {
        $menu = '';
        
        // S'assurer que $basePath a une valeur par défaut
        if (empty($basePath)) {
            $basePath = $this->getBasePath();
        }
        
        // Récupération des traitements accessibles à l'utilisateur
        $sql = "SELECT DISTINCT t.lib_traitement, t.nom_traitement, t.classe_icone
                FROM traitement t
                JOIN rattacher r ON t.id_traitement = r.id_traitement
                JOIN groupe_utilisateur gu ON r.id_gu = gu.id_gu
                JOIN posseder p ON gu.id_gu = p.id_gu
                WHERE p.id_util = :user_id
                ORDER BY t.id_traitement";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        $traitements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($traitements as $t) {
            $key = $t['lib_traitement'];
            $activeClass = ($currentPage === $key) ? 'class="active"' : '';
            $typeParam = $currentType ? "&type={$currentType}" : '';
            
            $menu .= "<li>
                        <a href=\"{$basePath}app.php?page={$key}{$typeParam}\" {$activeClass}>
                            <i class=\"{$t['classe_icone']}\"></i>
                            <span>{$t['nom_traitement']}</span>
                        </a>
                      </li>";
        }

        return $menu;
    }
}
