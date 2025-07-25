<?php

require_once __DIR__ . '/../../config/config.php';

class AuthController
{
    private $db;
    private $enseignantModel;
    private $persAdminModel;
    private $etudiantModel;

    public function __construct($db)
    {
        $this->db = $db;
      //  $this->enseignantModel = new Enseignant($db);
      //  $this->persAdminModel = new PersonnelAdministratif($db);
       // $this->etudiantModel = new Etudiant($db);
    }

    /*------------------------------------------------------------*/
    /* 2. Authentification simple (utilisateur)                   */
    /*------------------------------------------------------------*/

    function getUserPermissions(int $userId): array
    {
        $sql = "
        SELECT DISTINCT t.lib_traitement
        FROM posseder p
        JOIN rattacher r ON p.id_gu = r.id_gu
        JOIN traitement t ON t.id_traitement = r.id_traitement
        JOIN type_a_groupe tag ON p.id_gu = tag.id_gu
        JOIN utilisateur_type_utilisateur utu ON utu.id_tu = tag.id_tu
        WHERE utu.id_utilisateur = :id
    ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('strtolower', $rows);
    }

    function verifyLogin(string $login, string $password): array|false
    {
        $sql = "SELECT id_utilisateur, mdp_utilisateur, statut_utilisateur
            FROM   utilisateur
            WHERE  login_utilisateur = :login
            LIMIT  1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['login' => $login]);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && hash_equals($row['mdp_utilisateur'], hash('sha256', $password))) {
            if ($row['statut_utilisateur'] == 'Actif') {
                // Ajout de la vérification du type d'utilisateur
                $typeSql = "SELECT id_tu FROM utilisateur_type_utilisateur WHERE id_utilisateur = :id LIMIT 1";
                $typeStmt = $this->db->prepare($typeSql);
                $typeStmt->execute(['id' => $row['id_utilisateur']]);
                $typeRow = $typeStmt->fetch(PDO::FETCH_ASSOC);

                if ($typeRow) {
                    return $row;
                } else {
                    $_SESSION['error_message'] = "Type d'utilisateur non défini";
                    return false;
                }
            } else {
                $_SESSION['error_message'] = "Désolé, votre compte a été désactivé";
                return false;
            }
        }

        $_SESSION['error_message'] = "Login ou mot de passe incorrect";
        return false;
    }

    /*------------------------------------------------------------*/
    /* 2. Groupe(s) réels via posséder                            */
    /*------------------------------------------------------------*/
    function getGroups(int $idUser): array
    {
        $sql = "SELECT gu.id_gu, gu.lib_gu
            FROM posseder p
            JOIN groupe_utilisateur gu ON gu.id_gu = p.id_gu
            WHERE p.id_util = :uid";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $idUser]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function getUserGroupsIds(int $idUser): array
    {
        $sql = "SELECT gu.id_gu
            FROM groupe_utilisateur gu
            JOIN posseder p ON p.id_gu = gu.id_gu
            WHERE p.id_util = :idUser";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['idUser' => $idUser]);
        $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $results);
    }

    function getUserGroupId(int $idUser): string
    {
        $sql = "SELECT *
            FROM groupe_utilisateur gu
            JOIN posseder p ON p.id_gu = gu.id_gu
            WHERE p.id_util = :idUser
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['idUser' => $idUser]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['id_gu'] : 0;
    }

    /*------------------------------------------------------------*/
    /* 3. Libellé(s) type utilisateur (via utilisateur_type_utilisateur) */
    /*------------------------------------------------------------*/
    function getUserType(int $idUser): string
    {
        $sql = "SELECT tu.lib_tu
            FROM type_utilisateur tu
            JOIN utilisateur_type_utilisateur utu ON utu.id_tu = tu.id_tu
            WHERE utu.id_utilisateur = :idUser
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['idUser' => $idUser]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['lib_tu'] : 'Inconnu';
    }

    function getUserTypeId(int $idUser): int
    {
        $sql = "SELECT tu.id_tu
            FROM type_utilisateur tu
            JOIN utilisateur_type_utilisateur utu ON utu.id_tu = tu.id_tu
            WHERE utu.id_utilisateur = :idUser
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['idUser' => $idUser]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int)$result['id_tu'] : 0;
    }

    function getProfilUser(int $idUser): string
    {
        $sql = "SELECT 
            u.id_utilisateur,
            u.login_utilisateur,
            tu.id_tu,
            tu.lib_tu,
            CASE 
                WHEN tu.lib_tu = 'Enseignant simple' OR tu.lib_tu = 'Enseignant administratif' THEN e.photo_ens
                WHEN tu.lib_tu = 'Étudiant' THEN et.photo_etd
                WHEN tu.lib_tu = 'Personnel administratif' THEN pa.photo_personnel_adm
                ELSE NULL
            END as photo
            FROM utilisateur u
            LEFT JOIN utilisateur_type_utilisateur utu ON u.id_utilisateur = utu.id_utilisateur
            LEFT JOIN type_utilisateur tu ON utu.id_tu = tu.id_tu
            LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
            LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd
            LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
            WHERE u.id_utilisateur = :idUser
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['idUser' => $idUser]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Retourne la photo si elle existe, sinon retourne le chemin de l'image par défaut
        return $result && $result['photo'] ? $result['photo'] : '../assets/images/default_profile.jpg';
    }

    function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    function getTypes(int $idUser): array
    {
        $sql = "SELECT tu.id_tu, tu.lib_tu
            FROM type_utilisateur tu
            JOIN utilisateur_type_utilisateur utu ON utu.id_tu = tu.id_tu
            WHERE utu.id_utilisateur = :idUser";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['idUser' => $idUser]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*------------------------------------------------------------*/
    /* 4. Nom + prénom (table dyn.)                               */
    /*------------------------------------------------------------*/
    function getUserFullName(int $idUser): string
    {
        $sql = "SELECT login_utilisateur 
            FROM utilisateur 
            WHERE id_utilisateur = :id 
            LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idUser]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return 'Inconnu';

        $email = $user['login_utilisateur'];
        $_SESSION['email'] = $email;

        // Chercher dans les tables avec l'email
        $sources = [
            ['table' => 'etudiants',              'col' => 'email_etd',          'nom' => 'nom_etd',              'pre' => 'prenom_etd'],
            ['table' => 'enseignants',            'col' => 'email_ens',          'nom' => 'nom_ens',              'pre' => 'prenoms_ens'],
            ['table' => 'personnel_administratif', 'col' => 'email_personnel_adm', 'nom' => 'nom_personnel_adm',    'pre' => 'prenoms_personnel_adm'],
        ];

        foreach ($sources as $s) {
            $sql = "SELECT {$s['nom']} AS nom, {$s['pre']} AS prenom
                FROM   {$s['table']}
                WHERE  {$s['col']} = :email
                LIMIT  1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['email' => $email]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $row['nom'] . ' ' . $row['prenom'];
            }
        }

        // Si l'utilisateur n'est trouvé dans aucune table, retourner son login
        return $email;
    }

    /*------------------------------------------------------------*/
    /* 5. Groupe prioritaire pour choisir l'espace d'accueil      */
    /*------------------------------------------------------------*/

    function getPrioriteGroupes(): array
    {
        // Exemple PDO
        $stmt = $this->db->query("SELECT id_gu FROM groupe_utilisateur ORDER BY id_gu ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    function pickMainGroup(int $userId): ?int
    {
        $userGroupIds = $this->getUserGroupsIds($userId);
        $prioriteGroupes = $this->getPrioriteGroupes();
        foreach ($prioriteGroupes as $groupe) {
            if (in_array($groupe, $userGroupIds)) {
                return $groupe;
            }
        }
        return null;
    }

    public function logout()
    {
        session_destroy();
        header('Location: pageConnection.php');
        exit();
    }

    public function forgot()
    {
        // Afficher ou traiter la demande de réinitialisation
        require __DIR__ . '/../Views/auth/forgotPwd.php';
    }

    public function reset()
    {
        // Afficher ou traiter la réinitialisation
        require __DIR__ . '/../Views/auth/resetPwd.php';
    }

    /**
     * Récupère le login (identifiant) de l'utilisateur à partir de son ID
     */
    public function getUserLogin(int $userId): ?string
    {
        $sql = "SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['login_utilisateur'] : null;
    }
}
