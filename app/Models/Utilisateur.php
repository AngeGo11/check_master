<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Utilisateur
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Génère un mot de passe aléatoire
     */
    public function generateRandomPassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    /**
     * Ajoute un nouvel utilisateur avec type et envoi d'email
     */
    public function addUserWithType($login, $type_utilisateur)
    {
        try {
            $this->db->beginTransaction();

            $password = $this->generateRandomPassword();
            $hashed_password = hash('sha256', $password);

            // Insertion dans la table utilisateur
            $sql = "INSERT INTO utilisateur (login_utilisateur, mdp_utilisateur) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$login, $hashed_password]);
            $id_utilisateur = $this->db->lastInsertId();

            // Attribution du type d'utilisateur
            $sql = "INSERT INTO utilisateur_type_utilisateur (id_utilisateur, id_tu) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_utilisateur, $type_utilisateur]);

            $this->db->commit();
            return ['success' => true, 'password' => $password, 'id_utilisateur' => $id_utilisateur];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Récupère le nom complet d'un utilisateur
     */
    public function getUserFullName($login)
    {
        $stmt = $this->db->prepare("SELECT 
            CASE 
                WHEN e.nom_ens IS NOT NULL THEN CONCAT(e.prenoms_ens, ' ', e.nom_ens)
                WHEN et.nom_etd IS NOT NULL THEN CONCAT(et.prenom_etd, ' ', et.nom_etd)
                WHEN pa.nom_personnel_adm IS NOT NULL THEN CONCAT(pa.prenoms_personnel_adm, ' ', pa.nom_personnel_adm)
                ELSE 'Utilisateur'
            END AS nom_complet
            FROM utilisateur u
            LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
            LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
            LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
            WHERE u.login_utilisateur = ?");
        $stmt->execute([$login]);
        return $stmt->fetchColumn() ?: 'Utilisateur';
    }

    /**
     * Génère et envoie des mots de passe pour plusieurs utilisateurs
     */
    public function generatePasswordsForUsers($user_ids)
    {
        $results = ['success_count' => 0, 'error_count' => 0, 'error_messages' => [], 'passwords' => []];
        
        error_log("Début de generatePasswordsForUsers pour les IDs: " . implode(',', $user_ids));

        foreach ($user_ids as $id_utilisateur) {
            try {
                error_log("Traitement de l'utilisateur ID: " . $id_utilisateur);
                $this->db->beginTransaction();

                // Vérification si l'utilisateur existe et récupération de ses informations
                $sql = "SELECT u.login_utilisateur, u.statut_utilisateur,
                       CASE
                           WHEN et.email_etd IS NOT NULL THEN 'Étudiant'
                           WHEN en.email_ens IS NOT NULL THEN 'Enseignant'
                           WHEN pa.email_personnel_adm IS NOT NULL THEN 'Personnel administratif'
                           ELSE 'Autre'
                       END as lib_tu
                       FROM utilisateur u
                       LEFT JOIN enseignants en ON u.login_utilisateur = en.email_ens
                       LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                       LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                       WHERE u.id_utilisateur = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$id_utilisateur]);
                $user = $stmt->fetch();

                if ($user) {
                    error_log("Utilisateur trouvé: " . $user['login_utilisateur'] . " - Type: " . $user['lib_tu']);
                    
                    // Permettre la génération de mot de passe même pour les utilisateurs actifs
                    // (pour la réinitialisation de mot de passe)

                    $password = $this->generateRandomPassword();
                    $hashed_password = hash('sha256', $password);
                    
                    error_log("Mot de passe généré pour " . $user['login_utilisateur']);

                    // Mise à jour du mot de passe et activation de l'utilisateur
                    $sql = "UPDATE utilisateur SET mdp_utilisateur = ?, statut_utilisateur = 'Actif' WHERE id_utilisateur = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$hashed_password, $id_utilisateur]);

                    // Mise à jour du mot de passe dans la table spécifique selon le type
                    switch ($user['lib_tu']) {
                        case 'Étudiant':
                            $sql = "UPDATE etudiants SET mdp_etd = ? WHERE email_etd = ?";
                            break;
                        case 'Enseignant':
                            $sql = "UPDATE enseignants SET mdp_ens = ? WHERE email_ens = ?";
                            break;
                        case 'Personnel administratif':
                            $sql = "UPDATE personnel_administratif SET mdp_personnel_adm = ? WHERE email_personnel_adm = ?";
                            break;
                        default:
                            error_log("Type d'utilisateur non reconnu: " . $user['lib_tu']);
                            $sql = null;
                            break;
                    }

                    if (isset($sql)) {
                        error_log("Mise à jour du mot de passe dans la table spécifique avec SQL: " . $sql);
                        $stmt = $this->db->prepare($sql);
                        $result = $stmt->execute([$hashed_password, $user['login_utilisateur']]);
                        error_log("Résultat de la mise à jour spécifique: " . ($result ? "succès" : "échec"));
                    } else {
                        error_log("Aucune mise à jour spécifique pour le type: " . $user['lib_tu']);
                    }

                    $results['passwords'][] = [
                        'login' => $user['login_utilisateur'],
                        'password' => $password,
                        'nom_complet' => $this->getUserFullName($user['login_utilisateur'])
                    ];

                    $results['success_count']++;
                    error_log("Succès pour l'utilisateur " . $user['login_utilisateur']);
                } else {
                    error_log("Utilisateur non trouvé pour l'ID: " . $id_utilisateur);
                }

                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                $results['error_count']++;
                $results['error_messages'][] = "Erreur pour l'utilisateur ID " . $id_utilisateur . " : " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Met à jour les informations d'un utilisateur
     */
    public function updateUserInfo($id_utilisateur, $type_utilisateur, $groupe_utilisateur = null, $niveaux_acces = [], $fonction = null, $grade = null, $specialite = null)
    {
        try {
            $this->db->beginTransaction();

            // 1. Mise à jour du type d'utilisateur
            $stmt = $this->db->prepare("DELETE FROM utilisateur_type_utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$id_utilisateur]);

            $stmt = $this->db->prepare("INSERT INTO utilisateur_type_utilisateur (id_utilisateur, id_tu, date_attribution) VALUES (?, ?, CURDATE())");
            $stmt->execute([$id_utilisateur, $type_utilisateur]);

            // 2. Mise à jour du groupe utilisateur
            if ($groupe_utilisateur !== null && $groupe_utilisateur !== 0) {
                // Vérifier si l'utilisateur a déjà un groupe
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM posseder WHERE id_util = ?");
                $stmt->execute([$id_utilisateur]);
                $has_group = $stmt->fetchColumn();

                if ($has_group) {
                    // Mettre à jour le groupe existant
                    $stmt = $this->db->prepare("UPDATE posseder SET id_gu = ?, date_poss = CURDATE() WHERE id_util = ?");
                    $stmt->execute([$groupe_utilisateur, $id_utilisateur]);
                } else {
                    // Insérer un nouveau groupe
                    $stmt = $this->db->prepare("INSERT INTO posseder (id_util, id_gu, date_poss) VALUES (?, ?, CURDATE())");
                    $stmt->execute([$id_utilisateur, $groupe_utilisateur]);
                }
            } else {
                // Si aucun groupe n'est sélectionné, supprimer le groupe existant
                $stmt = $this->db->prepare("DELETE FROM posseder WHERE id_util = ?");
                $stmt->execute([$id_utilisateur]);
            }

            // 3. Mise à jour du niveau d'accès
            if (!empty($niveaux_acces) && is_array($niveaux_acces)) {
                // Prendre le premier niveau d'accès sélectionné
                $niveau_acces = (int)$niveaux_acces[0];
                $stmt = $this->db->prepare("UPDATE utilisateur SET id_niveau_acces = ? WHERE id_utilisateur = ?");
                $stmt->execute([$niveau_acces, $id_utilisateur]);
            }

            // 4. Mise à jour des informations spécifiques selon le type d'utilisateur
            $stmt = $this->db->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$id_utilisateur]);
            $email = $stmt->fetchColumn();

            if ($email) {
                // Récupération du type d'utilisateur
                $stmt = $this->db->prepare("SELECT lib_tu FROM type_utilisateur WHERE id_tu = ?");
                $stmt->execute([$type_utilisateur]);
                $type_utilisateur_lib = $stmt->fetchColumn();

                // Traitement spécifique pour les enseignants
                if (in_array($type_utilisateur_lib, ['Enseignant simple', 'Enseignant administratif'])) {
                    $stmt = $this->db->prepare("SELECT id_ens FROM enseignants WHERE email_ens = ?");
                    $stmt->execute([$email]);
                    $id_ens = $stmt->fetchColumn();

                    if ($id_ens) {
                        // Mise à jour de la fonction
                        if (!empty($fonction)) {
                            $stmt = $this->db->prepare("DELETE FROM occuper WHERE id_ens = ?");
                            $stmt->execute([$id_ens]);

                            $stmt = $this->db->prepare("INSERT INTO occuper (id_fonction, id_ens, date_occup) VALUES (?, ?, CURDATE())");
                            $stmt->execute([$fonction, $id_ens]);
                        }

                        // Mise à jour du grade
                        if (!empty($grade)) {
                            $stmt = $this->db->prepare("DELETE FROM avoir WHERE id_ens = ?");
                            $stmt->execute([$id_ens]);

                            $stmt = $this->db->prepare("INSERT INTO avoir (id_grd, id_ens, date_grd) VALUES (?, ?, CURDATE())");
                            $stmt->execute([$grade, $id_ens]);
                        }

                        // Mise à jour de la spécialité
                        if (!empty($specialite)) {
                            $stmt = $this->db->prepare("DELETE FROM enseignant_specialite WHERE id_ens = ?");
                            $stmt->execute([$id_ens]);

                            $stmt = $this->db->prepare("INSERT INTO enseignant_specialite (id_ens, id_spe) VALUES (?, ?)");
                            $stmt->execute([$id_ens, $specialite]);
                        }
                    }
                }
            }

            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Affecte en masse des utilisateurs inactifs
     */
    public function assignMultipleUsers($selected_users, $type_utilisateur, $groupe_utilisateur = null, $niveau_acces = null)
    {
        $results = ['success_count' => 0, 'error_count' => 0, 'error_messages' => [], 'passwords' => []];

        foreach ($selected_users as $id_utilisateur) {
            try {
                $this->db->beginTransaction();

                // 1. Mettre à jour le type d'utilisateur
                $stmt = $this->db->prepare("DELETE FROM utilisateur_type_utilisateur WHERE id_utilisateur = ?");
                $stmt->execute([$id_utilisateur]);

                $stmt = $this->db->prepare("INSERT INTO utilisateur_type_utilisateur (id_utilisateur, id_tu, date_attribution) VALUES (?, ?, CURDATE())");
                $stmt->execute([$id_utilisateur, $type_utilisateur]);

                // 2. Mettre à jour le groupe utilisateur (si sélectionné)
                if ($groupe_utilisateur !== null && $groupe_utilisateur !== 0) {
                    // Vérifier si l'utilisateur a déjà un groupe
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM posseder WHERE id_util = ?");
                    $stmt->execute([$id_utilisateur]);
                    $has_group = $stmt->fetchColumn();

                    if ($has_group) {
                        // Mettre à jour le groupe existant
                        $stmt = $this->db->prepare("UPDATE posseder SET id_gu = ?, date_poss = CURDATE() WHERE id_util = ?");
                        $stmt->execute([$groupe_utilisateur, $id_utilisateur]);
                    } else {
                        // Insérer un nouveau groupe
                        $stmt = $this->db->prepare("INSERT INTO posseder (id_util, id_gu, date_poss) VALUES (?, ?, CURDATE())");
                        $stmt->execute([$id_utilisateur, $groupe_utilisateur]);
                    }
                } else {
                    // Si aucun groupe n'est sélectionné, supprimer le groupe existant
                    $stmt = $this->db->prepare("DELETE FROM posseder WHERE id_util = ?");
                    $stmt->execute([$id_utilisateur]);
                }

                // 3. Mettre à jour le niveau d'accès (si sélectionné)
                if ($niveau_acces !== null && $niveau_acces !== 0 && $niveau_acces !== '') {
                    // Vérifier que le niveau d'accès existe dans la table de référence
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM niveau_acces_donnees WHERE id_niveau_acces = ?");
                    $stmt->execute([$niveau_acces]);
                    if ($stmt->fetchColumn() > 0) {
                        $stmt = $this->db->prepare("UPDATE utilisateur SET id_niveau_acces = ? WHERE id_utilisateur = ?");
                        $stmt->execute([$niveau_acces, $id_utilisateur]);
                    } else {
                        throw new Exception("Le niveau d'accès sélectionné n'existe pas dans la base de données");
                    }
                } else {
                    // Si aucun niveau d'accès n'est sélectionné, mettre NULL
                    $stmt = $this->db->prepare("UPDATE utilisateur SET id_niveau_acces = NULL WHERE id_utilisateur = ?");
                    $stmt->execute([$id_utilisateur]);
                }

                // 4. Mettre à jour le statut en 'Actif'
                $stmt = $this->db->prepare("UPDATE utilisateur SET statut_utilisateur = 'Actif' WHERE id_utilisateur = ?");
                $stmt->execute([$id_utilisateur]);

                // 5. Générer et envoyer les identifiants par mail
                $stmt = $this->db->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
                $stmt->execute([$id_utilisateur]);
                $email = $stmt->fetchColumn();

                if ($email) {
                    $password = $this->generateRandomPassword();
                    $hashed_password = hash('sha256', $password);

                    // Mise à jour du mot de passe
                    $stmt = $this->db->prepare("UPDATE utilisateur SET mdp_utilisateur = ? WHERE id_utilisateur = ?");
                    $stmt->execute([$hashed_password, $id_utilisateur]);

                    $results['passwords'][] = [
                        'login' => $email,
                        'password' => $password,
                        'nom_complet' => $this->getUserFullName($email)
                    ];
                }

                $this->db->commit();
                $results['success_count']++;
            } catch (Exception $e) {
                $this->db->rollBack();
                $results['error_count']++;
                $results['error_messages'][] = "Erreur pour l'utilisateur ID " . $id_utilisateur . " : " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Récupère les utilisateurs avec pagination et filtres
     */
    public function getUtilisateursWithFilters($page = 1, $per_page = 75, $filters = [])
    {
        $offset = ($page - 1) * $per_page;
        
        // Construction de la requête de base
        $sql_base = "FROM utilisateur u
            LEFT JOIN utilisateur_type_utilisateur utu ON u.id_utilisateur = utu.id_utilisateur
            LEFT JOIN type_utilisateur tu ON utu.id_tu = tu.id_tu
            LEFT JOIN posseder p ON u.id_utilisateur = p.id_util
            LEFT JOIN groupe_utilisateur gu ON p.id_gu = gu.id_gu
            LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
            LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
            LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm";

        // Construction des conditions de filtrage
        $conditions = [];
        $params = [];

        if (!empty($filters['type'])) {
            $conditions[] = "utu.id_tu = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['groupe'])) {
            $conditions[] = "p.id_gu = ?";
            $params[] = $filters['groupe'];
        }

        if (!empty($filters['statut'])) {
            $conditions[] = "u.statut_utilisateur = ?";
            $params[] = $filters['statut'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = "(
                e.nom_ens LIKE ? OR e.prenoms_ens LIKE ? OR
                et.nom_etd LIKE ? OR et.prenom_etd LIKE ? OR
                pa.nom_personnel_adm LIKE ? OR pa.prenoms_personnel_adm LIKE ? OR
                u.login_utilisateur LIKE ? OR tu.lib_tu LIKE ?
            )";
            for ($i = 0; $i < 8; $i++) {
                $params[] = "%{$filters['search']}%";
            }
        }

        // Ajout des conditions à la requête
        if (!empty($conditions)) {
            $sql_base .= " WHERE " . implode(" AND ", $conditions);
        }

        // Requête pour le comptage total
        $sql_count = "SELECT COUNT(DISTINCT u.id_utilisateur) " . $sql_base;
        $stmt_count = $this->db->prepare($sql_count);
        $stmt_count->execute($params);
        $total_utilisateurs = $stmt_count->fetchColumn();

        $nb_pages = ceil($total_utilisateurs / $per_page);

        // Requête principale avec les filtres
        $sql = "SELECT DISTINCT u.id_utilisateur, u.login_utilisateur, 
                CASE 
                    WHEN e.nom_ens IS NOT NULL THEN CONCAT(e.nom_ens, ' ', e.prenoms_ens)
                    WHEN et.nom_etd IS NOT NULL THEN CONCAT(et.nom_etd, ' ', et.prenom_etd)
                    WHEN pa.nom_personnel_adm IS NOT NULL THEN CONCAT(pa.nom_personnel_adm, ' ', pa.prenoms_personnel_adm)
                    ELSE 'Inconnu'
                END AS nom_complet,
                u.login_utilisateur AS email,
                tu.lib_tu,
                gu.lib_gu,
                u.statut_utilisateur
                " . $sql_base . "
                ORDER BY u.id_utilisateur
                LIMIT $per_page OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'utilisateurs' => $utilisateurs,
            'total' => $total_utilisateurs,
            'pages' => $nb_pages,
            'current_page' => $page
        ];
    }

    /**
     * Récupère les utilisateurs inactifs pour le modal
     */
    public function getInactiveUsers()
    {
        $sql = "SELECT u.id_utilisateur, u.login_utilisateur, u.statut_utilisateur,
                CASE
                    WHEN et.email_etd IS NOT NULL THEN CONCAT(et.nom_etd, ' ', et.prenom_etd)
                    WHEN en.email_ens IS NOT NULL THEN CONCAT(en.nom_ens, ' ', en.prenoms_ens)
                    WHEN pa.email_personnel_adm IS NOT NULL THEN CONCAT(pa.nom_personnel_adm, ' ', pa.prenoms_personnel_adm)
                    ELSE u.login_utilisateur
                END as nom_complet,
                CASE
                    WHEN et.email_etd IS NOT NULL THEN 'Étudiant'
                    WHEN en.email_ens IS NOT NULL THEN 'Enseignant'
                    WHEN pa.email_personnel_adm IS NOT NULL THEN 'Personnel Administratif'
                    ELSE 'Autre'
                END as type_source,
                u.id_niveau_acces
                FROM utilisateur u
                LEFT JOIN enseignants en ON u.login_utilisateur = en.email_ens
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                WHERE u.statut_utilisateur = 'Inactif'
                ORDER BY type_source, nom_complet";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les données pour les selects (types, groupes, etc.)
     */
    public function getTypesUtilisateurs()
    {
        $stmt = $this->db->prepare("SELECT * FROM type_utilisateur ORDER BY id_tu");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGroupesUtilisateurs()
    {
        $stmt = $this->db->prepare("SELECT * FROM groupe_utilisateur ORDER BY id_gu");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGrades()
    {
        $stmt = $this->db->prepare("SELECT * FROM grade ORDER BY id_grd");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFonctions()
    {
        $stmt = $this->db->prepare("SELECT * FROM fonction ORDER BY id_fonction");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSpecialites()
    {
        $stmt = $this->db->prepare("SELECT * FROM specialite ORDER BY id_spe");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNiveauxAcces()
    {
        $stmt = $this->db->prepare("SELECT * FROM niveau_acces_donnees ORDER BY id_niveau_acces");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les statistiques des utilisateurs
     */
    public function getUtilisateursStats()
    {
        $sql = "SELECT 
            COUNT(*) as total_utilisateurs,
            COUNT(CASE WHEN statut_utilisateur = 'Actif' THEN 1 END) as utilisateurs_actifs,
            COUNT(CASE WHEN statut_utilisateur = 'Inactif' THEN 1 END) as utilisateurs_inactifs
            FROM utilisateur";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les détails complets d'un utilisateur pour l'édition
     */
    public function getUtilisateurDetails($id)
    {
        $sql = "SELECT u.id_utilisateur, u.login_utilisateur, 
                CASE 
                    WHEN e.nom_ens IS NOT NULL THEN CONCAT(e.nom_ens, ' ', e.prenoms_ens)
                    WHEN et.nom_etd IS NOT NULL THEN CONCAT(et.nom_etd, ' ', et.prenom_etd)
                    WHEN pa.nom_personnel_adm IS NOT NULL THEN CONCAT(pa.nom_personnel_adm, ' ', pa.prenoms_personnel_adm)
                    ELSE 'Inconnu'
                END AS nom_complet,
                CASE
                    WHEN e.sexe_ens IS NOT NULL THEN e.sexe_ens
                    WHEN et.sexe_etd IS NOT NULL THEN et.sexe_etd
                    WHEN pa.sexe_personnel_adm IS NOT NULL THEN pa.sexe_personnel_adm
                    ELSE 'Inconnu'
                END AS sexe,
                CASE
                    WHEN e.date_naissance_ens IS NOT NULL THEN e.date_naissance_ens
                    WHEN et.date_naissance_etd IS NOT NULL THEN et.date_naissance_etd
                    WHEN pa.date_naissance_personnel_adm IS NOT NULL THEN pa.date_naissance_personnel_adm
                    ELSE 'Inconnu'
                END AS date_naissance,
                CASE 
                    WHEN e.num_tel_ens IS NOT NULL THEN e.num_tel_ens
                    WHEN et.num_tel_etd IS NOT NULL THEN et.num_tel_etd
                    WHEN pa.tel_personnel_adm IS NOT NULL THEN pa.tel_personnel_adm
                    ELSE 'Inconnu'
                END AS telephone,
                CASE    
                    WHEN e.photo_ens IS NOT NULL THEN e.photo_ens
                    WHEN et.photo_etd IS NOT NULL THEN et.photo_etd
                    WHEN pa.photo_personnel_adm IS NOT NULL THEN pa.photo_personnel_adm
                    ELSE 'Inconnu'
                END AS photo,
                u.statut_utilisateur,
                tu.lib_tu,
                tu.id_tu,
                gu.lib_gu,
                gu.id_gu,
                et.num_carte_etd,
                et.id_promotion,
                prt.*,
                f.id_fonction,
                f.nom_fonction,
                g.id_grd,
                g.nom_grd,
                s.id_spe,
                s.lib_spe,
                na.id_niveau_acces,
                na.lib_niveau_acces
                FROM utilisateur u
                LEFT JOIN utilisateur_type_utilisateur utu ON u.id_utilisateur = utu.id_utilisateur
                LEFT JOIN type_utilisateur tu ON utu.id_tu = tu.id_tu
                LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
                LEFT JOIN posseder p ON u.id_utilisateur = p.id_util
                LEFT JOIN groupe_utilisateur gu ON p.id_gu = gu.id_gu
                LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
                LEFT JOIN promotion prt ON prt.id_promotion = et.id_promotion
                LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
                LEFT JOIN occuper o ON e.id_ens = o.id_ens
                LEFT JOIN fonction f ON o.id_fonction = f.id_fonction
                LEFT JOIN avoir a ON e.id_ens = a.id_ens
                LEFT JOIN grade g ON a.id_grd = g.id_grd
                LEFT JOIN specialite s ON e.id_ens = s.id_spe
                LEFT JOIN niveau_acces_donnees na ON na.id_niveau_acces = u.id_niveau_acces
               
                WHERE u.id_utilisateur = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Méthodes existantes conservées
    public function ajouterUtilisateur($login_utilisateur, $mdp_utilisateur, $statut_utilisateur, $id_niveau_acces)
    {
        try {
            $this->db->beginTransaction();
            $query = "INSERT INTO utilisateur (login_utilisateur, mdp_utilisateur, statut_utilisateur, id_niveau_acces) VALUES (:login_utilisateur, :mdp_utilisateur, :statut_utilisateur, :id_niveau_acces)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':login_utilisateur', $login_utilisateur);
            $stmt->bindParam(':mdp_utilisateur', $mdp_utilisateur);
            $stmt->bindParam(':statut_utilisateur', $statut_utilisateur);
            $stmt->bindParam(':id_niveau_acces', $id_niveau_acces);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout utilisateur: " . $e->getMessage());
            return false;
        }
    }

    public function updateUtilisateur($login_utilisateur, $mdp_utilisateur, $statut_utilisateur, $id_niveau_acces, $id)
    {
        try {
            $this->db->beginTransaction();
            $query = "UPDATE utilisateur SET login_utilisateur = :login_utilisateur, mdp_utilisateur = :mdp_utilisateur, statut_utilisateur = :statut_utilisateur, id_niveau_acces = :id_niveau_acces WHERE id_utilisateur = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':login_utilisateur', $login_utilisateur);
            $stmt->bindParam(':mdp_utilisateur', $mdp_utilisateur);
            $stmt->bindParam(':statut_utilisateur', $statut_utilisateur);
            $stmt->bindParam(':id_niveau_acces', $id_niveau_acces);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur modification utilisateur: " . $e->getMessage());
            return false;
        }
    }

    public function desactiverUtilisateur($id)
    {
        $sql = "UPDATE utilisateur SET statut_utilisateur = 'Inactif' WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function reactiverUtilisateur($id)
    {
        $sql = "UPDATE utilisateur SET statut_utilisateur = 'Actif' WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function updatePassword($id, $newPassword)
    {
        $query = "UPDATE utilisateur SET mdp_utilisateur = :mdp WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':mdp', $newPassword);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAllUtilisateursActifs()
    {
        $query = "SELECT u.id_utilisateur, u.nom_utilisateur, u.login_utilisateur, 
                    u.statut_utilisateur,
                    t.lib_type_utilisateur as role_utilisateur,
                    g.lib_GU as gu,
                    n.lib_niveau_acces_donnees as niveau_acces
              FROM utilisateur u
              LEFT JOIN type_utilisateur t ON u.id_type_utilisateur = t.id_type_utilisateur
              LEFT JOIN groupe_utilisateur g ON u.id_GU = g.id_GU
              LEFT JOIN niveau_acces_donnees n ON u.id_niv_acces_donnee = n.id_niveau_acces_donnees
              WHERE u.statut_utilisateur = 'Actif'
              ORDER BY u.nom_utilisateur";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getAllUtilisateursInactifs()
    {
        $query = "SELECT u.id_utilisateur, u.nom_utilisateur, u.login_utilisateur, 
                    u.statut_utilisateur,
                    t.lib_type_utilisateur as role_utilisateur,
                    g.lib_GU as gu,
                    n.lib_niveau_acces_donnees as niveau_acces
              FROM utilisateur u
              LEFT JOIN type_utilisateur t ON u.id_type_utilisateur = t.id_type_utilisateur
              LEFT JOIN groupe_utilisateur g ON u.id_GU = g.id_GU
              LEFT JOIN niveau_acces_donnees n ON u.id_niv_acces_donnee = n.id_niveau_acces_donnees
              WHERE u.statut_utilisateur = 'Inactif'
              ORDER BY u.nom_utilisateur";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getAllUtilisateurs()
    {
        $query = "SELECT * FROM utilisateur";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getUtilisateurById($id)
    {
        $query = "SELECT * FROM utilisateur WHERE id_utilisateur = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function getUserInfos($login)
    {
        $query = "SELECT u.*, e.*, et.*, 
            CASE 
                WHEN e.nom_ens IS NOT NULL THEN CONCAT(e.prenoms_ens, ' ', e.nom_ens)
                WHEN et.nom_etd IS NOT NULL THEN CONCAT(et.prenom_etd, ' ', et.nom_etd)
                WHEN pa.nom_personnel_adm IS NOT NULL THEN CONCAT(pa.prenoms_personnel_adm, ' ', pa.nom_personnel_adm)
                ELSE 'Utilisateur'
            END AS nom_complet
            FROM utilisateur u
            LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
            LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
            LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
            WHERE u.login_utilisateur = :login";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':login', $login);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    public function supprimerUtilisateur($id)
    {
        try {
            $this->db->beginTransaction();
            $query = "DELETE FROM utilisateur WHERE id_utilisateur = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression utilisateur: " . $e->getMessage());
            return false;
        }
    }
}
