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

    /*
    /*  4. Libellé(s) groupe utilisateur (via posseder)
    */

    function getUserGroup(int $idUser): string
    {
        $sql = "SELECT gu.lib_gu
            FROM groupe_utilisateur gu
            JOIN posseder p ON p.id_gu = gu.id_gu
            WHERE p.id_util = :idUser
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['idUser' => $idUser]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? $result['lib_gu'] : 'Inconnu';
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
        header('Location: pageConnexion.php');
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

    /**
     * Envoie un lien de réinitialisation de mot de passe
     */
    public function sendLinkResetPassword($email)
    {
        if (!$email) {
            $_SESSION['error_message'] = "Adresse e-mail invalide.";
            return false;
        }

        // Vérifier si l'utilisateur existe
        $stmt = $this->db->prepare("SELECT id_utilisateur FROM utilisateur WHERE login_utilisateur = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = $this->generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));



            // Supprimer les anciens tokens
            $this->db->prepare("DELETE FROM reset_password WHERE email = ?")->execute([$email]);

            // Insérer le nouveau token
            $this->db->prepare("INSERT INTO reset_password (email, token, expires_at) VALUES (?, ?, ?)")->execute([$email, $token, $expires]);

            // Lien de réinitialisation
            $link = "http://localhost/GSCV+/public/resetPwd.php?token=$token";

            // Envoyer l'email
            if ($this->sendResetEmail($email, $link)) {
                $_SESSION['success_message'] = "Un lien de réinitialisation a été envoyé à votre adresse e-mail.";
                return true;
            } else {
                $_SESSION['error_message'] = "Erreur lors de l'envoi de l'email. Veuillez réessayer.";
                return false;
            }
        } else {
            $_SESSION['error_message'] = "Aucun compte trouvé avec cette adresse e-mail.";
            return false;
        }
    }

    /**
     * Réinitialise le mot de passe avec un token
     */
    public function resetPassword($token, $newPassword, $confirmPassword)
    {
        if (empty($token)) {
            $_SESSION['error_message'] = "Token de réinitialisation manquant.";
            return false;
        }

        if (empty($newPassword)) {
            $_SESSION['error_message'] = "Le nouveau mot de passe est requis.";
            return false;
        }

        if (strlen($newPassword) < 8) {
            $_SESSION['error_message'] = "Le mot de passe doit contenir au moins 8 caractères.";
            return false;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error_message'] = "Les mots de passe ne correspondent pas.";
            return false;
        }

        // Vérifier le token
        $stmt = $this->db->prepare("SELECT email, expires_at FROM reset_password WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $resetData = $stmt->fetch();

        if (!$resetData) {
            $_SESSION['error_message'] = "Token invalide ou expiré.";
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Mettre à jour le mot de passe
            $hashedPassword = hash('sha256', $newPassword);
            $stmt = $this->db->prepare("UPDATE utilisateur SET mdp_utilisateur = ? WHERE login_utilisateur = ?");
            $stmt->execute([$hashedPassword, $resetData['email']]);

            // Supprimer le token utilisé
            $stmt = $this->db->prepare("DELETE FROM reset_password WHERE token = ?");
            $stmt->execute([$token]);

            $this->db->commit();

            $_SESSION['success_message'] = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.";
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error_message'] = "Erreur lors de la réinitialisation du mot de passe.";
            return false;
        }
    }



    /**
     * Envoie l'email de réinitialisation 
     */
    private function sendResetEmail($email, $link)
    {
        try {
            require_once __DIR__ . '/../../vendor/autoload.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'axelangegomez2004@gmail.com';
            $mail->Password = 'yxxhpqgfxiulawhd';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('axelangegomez2004@gmail.com', 'CHECK MASTER');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Réinitialisation de votre mot de passe - CHECK MASTER';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <div style='background: linear-gradient(135deg, #1a5276 0%, #2980b9 50%, #3498db 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
                        <h1 style='margin: 0;'>CHECK MASTER</h1>
                        <p style='margin: 10px 0 0 0;'>Réinitialisation de mot de passe</p>
                    </div>
                    <div style='background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px;'>
                        <h2 style='color: #1a5276; margin-top: 0;'>Bonjour,</h2>
                        <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte CHECK MASTER.</p>
                        <p>Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe :</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='$link' style='background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold;'>Réinitialiser mon mot de passe</a>
                        </div>
                        <p><strong>Important :</strong> Ce lien expire dans 1 heure pour des raisons de sécurité.</p>
                        <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                        <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>
                        <p style='color: #666; font-size: 12px;'>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                    </div>
                </div>
            ";

            $mail->send();
            error_log("Email de réinitialisation envoyé avec succès à $email");
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi de l'email de réinitialisation: " . $e->getMessage());
            return false;
        }
    }
}
