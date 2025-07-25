<?php
require_once '../../config/db_connect.php';
require_once '../../config/mail.php';
$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// Fonction pour générer un mot de passe aléatoire
function generateRandomPassword($length = 12)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                if (isset($_POST['login']) && isset($_POST['type_utilisateur'])) {
                    $login = $_POST['login'];
                    $type_utilisateur = $_POST['type_utilisateur'];
                    $password = generateRandomPassword();
                    $hashed_password = hash('sha256', $password);

                    try {
                        $pdo->beginTransaction();

                        // Insertion dans la table utilisateur
                        $sql = "INSERT INTO utilisateur (login_utilisateur, mdp_utilisateur) VALUES (?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$login, $hashed_password]);
                        $id_utilisateur = $pdo->lastInsertId();

                        // Attribution du type d'utilisateur
                        $sql = "INSERT INTO utilisateur_type_utilisateur (id_utilisateur, id_tu) VALUES (?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$id_utilisateur, $type_utilisateur]);

                        // Envoi du mail avec le mot de passe
                        $subject = "✉️ Bienvenue sur la plateforme CHECK Master – Vos identifiants de connexion";
                        
                        // Récupération du nom complet de l'utilisateur
                        $stmt = $pdo->prepare("SELECT 
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
                        $nom_complet = $stmt->fetchColumn() ?: 'Utilisateur';
                        
                        $message = '
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
                                .header::before {
                                    content: "";
                                    position: absolute;
                                    top: 0;
                                    left: 0;
                                    right: 0;
                                    bottom: 0;
                                    background: url("data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
                                    opacity: 0.3;
                                }
                                .header h1 {
                                    margin: 0;
                                    font-size: 24px;
                                    font-weight: 700;
                                    position: relative;
                                    z-index: 1;
                                }
                                .header .subtitle {
                                    margin: 8px 0 0 0;
                                    font-size: 14px;
                                    opacity: 0.9;
                                    position: relative;
                                    z-index: 1;
                                }
                                .content {
                                    padding: 35px 25px;
                                }
                                .welcome-section {
                                    margin-bottom: 30px;
                                }
                                .welcome-section h2 {
                                    color: #1a5276;
                                    font-size: 20px;
                                    margin: 0 0 15px 0;
                                    font-weight: 600;
                                }
                                .welcome-section p {
                                    margin: 0 0 15px 0;
                                    color: #555;
                                    font-size: 15px;
                                }
                                .credentials-section {
                                    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                                    border-radius: 10px;
                                    padding: 25px;
                                    margin: 25px 0;
                                    border-left: 4px solid #1a5276;
                                }
                                .credentials-section h3 {
                                    color: #1a5276;
                                    margin: 0 0 20px 0;
                                    font-size: 18px;
                                    font-weight: 600;
                                    display: flex;
                                    align-items: center;
                                    gap: 8px;
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
                                .security-warning h4 {
                                    color: #856404;
                                    margin: 0 0 10px 0;
                                    font-size: 16px;
                                    font-weight: 600;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    gap: 8px;
                                }
                                .security-warning p {
                                    color: #856404;
                                    margin: 0;
                                    font-size: 14px;
                                }
                                .footer {
                                    background: #f8f9fa;
                                    padding: 20px 25px;
                                    text-align: center;
                                    border-top: 1px solid #e9ecef;
                                }
                                .footer p {
                                    margin: 0;
                                    color: #6c757d;
                                    font-size: 13px;
                                }
                                .logo {
                                    font-size: 28px;
                                    margin-bottom: 10px;
                                }
                                .features-list {
                                    background: #f8f9fa;
                                    border-radius: 8px;
                                    padding: 20px;
                                    margin: 20px 0;
                                }
                                .features-list h4 {
                                    color: #1a5276;
                                    margin: 0 0 15px 0;
                                    font-size: 16px;
                                    font-weight: 600;
                                }
                                .features-list ul {
                                    margin: 0;
                                    padding-left: 20px;
                                    color: #555;
                                }
                                .features-list li {
                                    margin: 8px 0;
                                    font-size: 14px;
                                }
                                @media (max-width: 600px) {
                                    body {
                                        padding: 10px;
                                    }
                                    .header, .content, .footer {
                                        padding: 20px 15px;
                                    }
                                    .credential-item {
                                        flex-direction: column;
                                        align-items: flex-start;
                                    }
                                    .credential-label {
                                        min-width: auto;
                                        margin-right: 0;
                                        margin-bottom: 5px;
                                    }
                                }
                            </style>
                        </head>
                        <body>
                            <div class="email-container">
                                <div class="header">
                                    <div class="logo">🎓</div>
                                    <h1>CHECK Master</h1>
                                    <div class="subtitle">Plateforme de Gestion Académique</div>
                                </div>
                                
                                <div class="content">
                                    <div class="welcome-section">
                                        <h2>Bonjour ' . htmlspecialchars($nom_complet) . ',</h2>
                                        <p>Bienvenue dans la plateforme <strong>CHECK Master</strong>, votre nouvel espace numérique de gestion académique et administrative.</p>
                                        <p>Votre compte a été créé avec succès. Vous pouvez dès maintenant vous connecter et accéder aux services qui vous sont destinés.</p>
                                    </div>
                                    
                                    <div class="features-list">
                                        <h4>🚀 Services disponibles :</h4>
                                        <ul>
                                            <li>Suivi de votre parcours académique</li>
                                            <li>Consultation des notes et évaluations</li>
                                            <li>Dépôt de rapports et documents</li>
                                            <li>Messagerie interne</li>
                                            <li>Gestion des règlements</li>
                                            <li>Et bien plus encore...</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="credentials-section">
                                        <h3>🔐 Vos identifiants de connexion</h3>
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
                                        <h4>⚠️ Important - Sécurité</h4>
                                        <p>Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe dès votre première connexion.</p>
                                    </div>
                                </div>
                                
                                <div class="footer">
                                    <p><strong>CHECK Master</strong> - Université Félix Houphouët-Boigny</p>
                                    <p>Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
                                </div>
                            </div>
                        </body>
                        </html>';
                        
                        sendEmail("Administrateur GSCV", "axelangegomez2004@gscv.com", $login, $subject, $message);

                        $pdo->commit();
                        $_SESSION['success'] = "Utilisateur ajouté avec succès";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage();
                    }
                }
                break;

            case 'generate_passwords':
                if (isset($_POST['selected_users']) && is_array($_POST['selected_users'])) {
                    $success_count = 0;
                    $error_count = 0;
                    $error_messages = [];

                    foreach ($_POST['selected_users'] as $id_utilisateur) {
                        try {
                            $pdo->beginTransaction();

                            // Vérification si l'utilisateur a déjà un mot de passe
                            $sql = "SELECT u.login_utilisateur, tu.lib_tu, u.mdp_utilisateur, u.statut_utilisateur
                                   FROM utilisateur u 
                                   JOIN utilisateur_type_utilisateur utu ON u.id_utilisateur = utu.id_utilisateur 
                                   JOIN type_utilisateur tu ON utu.id_tu = tu.id_tu 
                                   WHERE u.id_utilisateur = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$id_utilisateur]);
                            $user = $stmt->fetch();

                            if ($user) {
                                if ($user['statut_utilisateur'] == 'Actif') {
                                    $error_count++;
                                    $error_messages[] = "L'utilisateur " . $user['login_utilisateur'] . " possède déjà des identifiants.";
                                    continue;
                                }

                                $password = generateRandomPassword();
                                $hashed_password = hash('sha256', $password);

                                // Mise à jour du mot de passe dans la table utilisateur
                                $sql = "UPDATE utilisateur SET mdp_utilisateur = ? WHERE id_utilisateur = ?";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute([$hashed_password, $id_utilisateur]);

                                // Mise à jour du mot de passe dans la table spécifique selon le type
                                switch ($user['lib_tu']) {
                                    case 'Étudiant':
                                        $sql = "UPDATE etudiants SET mdp_etd = ? WHERE email_etd = ?";
                                        break;
                                    case 'Enseignant simple':
                                    case 'Enseignant administratif':
                                        $sql = "UPDATE enseignants SET mdp_ens = ? WHERE email_ens = ?";
                                        break;
                                    case 'Personnel administratif':
                                        $sql = "UPDATE personnel_administratif SET mdp_personnel_adm = ? WHERE email_personnel_adm = ?";
                                        break;
                                }

                                if (isset($sql)) {
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute([$hashed_password, $user['login_utilisateur']]);
                                }

                                // Envoi du mail avec le nouveau mot de passe
                                $subject = "✉️ Mise à jour de vos identifiants CHECK Master";
                                
                                // Récupération du nom complet de l'utilisateur
                                $stmt = $pdo->prepare("SELECT 
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
                                $stmt->execute([$user['login_utilisateur']]);
                                $nom_complet = $stmt->fetchColumn() ?: 'Utilisateur';
                                
                                $message = '
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
                                        .header::before {
                                            content: "";
                                            position: absolute;
                                            top: 0;
                                            left: 0;
                                            right: 0;
                                            bottom: 0;
                                            background: url("data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
                                            opacity: 0.3;
                                        }
                                        .header h1 {
                                            margin: 0;
                                            font-size: 24px;
                                            font-weight: 700;
                                            position: relative;
                                            z-index: 1;
                                        }
                                        .header .subtitle {
                                            margin: 8px 0 0 0;
                                            font-size: 14px;
                                            opacity: 0.9;
                                            position: relative;
                                            z-index: 1;
                                        }
                                        .content {
                                            padding: 35px 25px;
                                        }
                                        .welcome-section {
                                            margin-bottom: 30px;
                                        }
                                        .welcome-section h2 {
                                            color: #1a5276;
                                            font-size: 20px;
                                            margin: 0 0 15px 0;
                                            font-weight: 600;
                                        }
                                        .welcome-section p {
                                            margin: 0 0 15px 0;
                                            color: #555;
                                            font-size: 15px;
                                        }
                                        .credentials-section {
                                            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                                            border-radius: 10px;
                                            padding: 25px;
                                            margin: 25px 0;
                                            border-left: 4px solid #1a5276;
                                        }
                                        .credentials-section h3 {
                                            color: #1a5276;
                                            margin: 0 0 20px 0;
                                            font-size: 18px;
                                            font-weight: 600;
                                            display: flex;
                                            align-items: center;
                                            gap: 8px;
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
                                        .security-warning h4 {
                                            color: #856404;
                                            margin: 0 0 10px 0;
                                            font-size: 16px;
                                            font-weight: 600;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            gap: 8px;
                                        }
                                        .security-warning p {
                                            color: #856404;
                                            margin: 0;
                                            font-size: 14px;
                                        }
                                        .footer {
                                            background: #f8f9fa;
                                            padding: 20px 25px;
                                            text-align: center;
                                            border-top: 1px solid #e9ecef;
                                        }
                                        .footer p {
                                            margin: 0;
                                            color: #6c757d;
                                            font-size: 13px;
                                        }
                                        .logo {
                                            font-size: 28px;
                                            margin-bottom: 10px;
                                        }
                                        .update-notice {
                                            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
                                            border: 1px solid #bee5eb;
                                            border-radius: 10px;
                                            padding: 20px;
                                            margin: 20px 0;
                                            text-align: center;
                                        }
                                        .update-notice h4 {
                                            color: #0c5460;
                                            margin: 0 0 10px 0;
                                            font-size: 16px;
                                            font-weight: 600;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            gap: 8px;
                                        }
                                        .update-notice p {
                                            color: #0c5460;
                                            margin: 0;
                                            font-size: 14px;
                                        }
                                        @media (max-width: 600px) {
                                            body {
                                                padding: 10px;
                                            }
                                            .header, .content, .footer {
                                                padding: 20px 15px;
                                            }
                                            .credential-item {
                                                flex-direction: column;
                                                align-items: flex-start;
                                            }
                                            .credential-label {
                                                min-width: auto;
                                                margin-right: 0;
                                                margin-bottom: 5px;
                                            }
                                        }
                                    </style>
                                </head>
                                <body>
                                    <div class="email-container">
                                        <div class="header">
                                            <div class="logo">🎓</div>
                                            <h1>CHECK Master</h1>
                                            <div class="subtitle">Plateforme de Gestion Académique</div>
                                        </div>
                                        
                                        <div class="content">
                                            <div class="welcome-section">
                                                <h2>Bonjour ' . htmlspecialchars($nom_complet) . ',</h2>
                                                <p>Vos identifiants de connexion ont été mis à jour sur la plateforme <strong>CHECK Master</strong>.</p>
                                                <p>Vous pouvez dès maintenant vous connecter avec vos nouveaux identifiants.</p>
                                            </div>
                                            
                                            <div class="update-notice">
                                                <h4>🔄 Mise à jour des identifiants</h4>
                                                <p>Votre mot de passe a été régénéré pour des raisons de sécurité.</p>
                                            </div>
                                            
                                            <div class="credentials-section">
                                                <h3>🔐 Vos identifiants de connexion</h3>
                                                <div class="credential-item">
                                                    <span class="credential-label">Login :</span>
                                                    <span class="credential-value">' . htmlspecialchars($user['login_utilisateur']) . '</span>
                                                </div>
                                                <div class="credential-item">
                                                    <span class="credential-label">Nouveau mot de passe :</span>
                                                    <span class="credential-value">' . htmlspecialchars($password) . '</span>
                                                </div>
                                            </div>
                                            
                                            <div class="security-warning">
                                                <h4>⚠️ Important - Sécurité</h4>
                                                <p>Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe dès votre première connexion.</p>
                                            </div>
                                        </div>
                                        
                                        <div class="footer">
                                            <p><strong>CHECK Master</strong> - Université Félix Houphouët-Boigny</p>
                                            <p>Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
                                        </div>
                                    </div>
                                </body>
                                </html>';
                                
                                sendEmail("Administrateur GSCV", "axelangegomez2004@gscv.com", $user['login_utilisateur'], $subject, $message);

                                $success_count++;
                            }

                            $pdo->commit();
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $error_count++;
                            $error_messages[] = "Erreur pour l'utilisateur " . $user['login_utilisateur'] . " : " . $e->getMessage();
                        }
                    }

                    if ($success_count > 0) {
                        $_SESSION['success'] = $success_count . " mot(s) de passe généré(s) et envoyé(s) avec succès.";
                    }
                    if ($error_count > 0) {
                        $_SESSION['error'] = implode("<br>", $error_messages);
                    }
                }
                break;

            case 'edit_user':
                if (isset($_POST['id_utilisateur'], $_POST['type_utilisateur'])) {
                    $id_utilisateur = (int)$_POST['id_utilisateur'];
                    $type_utilisateur = (int)$_POST['type_utilisateur'];
                    $groupe_utilisateur = isset($_POST['groupe_utilisateur']) ? (int)$_POST['groupe_utilisateur'] : null;
                    $niveaux_acces = $_POST['niveaux_acces'] ?? [];

                    try {
                        $pdo->beginTransaction();

                        // 1. Mise à jour du type d'utilisateur
                        $stmt = $pdo->prepare("DELETE FROM utilisateur_type_utilisateur WHERE id_utilisateur = ?");
                        $stmt->execute([$id_utilisateur]);

                        $stmt = $pdo->prepare("INSERT INTO utilisateur_type_utilisateur (id_utilisateur, id_tu, date_attribution) VALUES (?, ?, CURDATE())");
                        $stmt->execute([$id_utilisateur, $type_utilisateur]);

                        // 2. Mise à jour du groupe utilisateur
                        if ($groupe_utilisateur !== null && $groupe_utilisateur !== 0) {
                            // Vérifier si l'utilisateur a déjà un groupe
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM posseder WHERE id_util = ?");
                            $stmt->execute([$id_utilisateur]);
                            $has_group = $stmt->fetchColumn();

                            if ($has_group) {
                                // Mettre à jour le groupe existant
                                $stmt = $pdo->prepare("UPDATE posseder SET id_gu = ?, date_poss = CURDATE() WHERE id_util = ?");
                                $stmt->execute([$groupe_utilisateur, $id_utilisateur]);
                            } else {
                                // Insérer un nouveau groupe
                                $stmt = $pdo->prepare("INSERT INTO posseder (id_util, id_gu, date_poss) VALUES (?, ?, CURDATE())");
                                $stmt->execute([$id_utilisateur, $groupe_utilisateur]);
                            }
                        } else {
                            // Si aucun groupe n'est sélectionné, supprimer le groupe existant
                            $stmt = $pdo->prepare("DELETE FROM posseder WHERE id_util = ?");
                            $stmt->execute([$id_utilisateur]);
                        }

                        // 3. Mise à jour du niveau d'accès
                        if (!empty($niveaux_acces) && is_array($niveaux_acces)) {
                            // Prendre le premier niveau d'accès sélectionné
                            $niveau_acces = (int)$niveaux_acces[0];
                            $stmt = $pdo->prepare("UPDATE utilisateur SET id_niveau_acces = ? WHERE id_utilisateur = ?");
                            $stmt->execute([$niveau_acces, $id_utilisateur]);
                        }

                        // 4. Mise à jour des informations spécifiques selon le type d'utilisateur
                        $stmt = $pdo->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
                        $stmt->execute([$id_utilisateur]);
                        $email = $stmt->fetchColumn();

                        if ($email) {
                            // Récupération du type d'utilisateur
                            $stmt = $pdo->prepare("SELECT lib_tu FROM type_utilisateur WHERE id_tu = ?");
                            $stmt->execute([$type_utilisateur]);
                            $type_utilisateur_lib = $stmt->fetchColumn();

                            // Traitement spécifique pour les enseignants
                            if (in_array($type_utilisateur_lib, ['Enseignant simple', 'Enseignant administratif'])) {
                                $stmt = $pdo->prepare("SELECT id_ens FROM enseignants WHERE email_ens = ?");
                                $stmt->execute([$email]);
                                $id_ens = $stmt->fetchColumn();

                                if ($id_ens) {
                                    // Mise à jour de la fonction
                                    if (!empty($_POST['fonction'])) {
                                        $stmt = $pdo->prepare("DELETE FROM occuper WHERE id_ens = ?");
                                        $stmt->execute([$id_ens]);

                                        $stmt = $pdo->prepare("INSERT INTO occuper (id_fonction, id_ens, date_occup) VALUES (?, ?, CURDATE())");
                                        $stmt->execute([$_POST['fonction'], $id_ens]);
                                    }

                                    // Mise à jour du grade
                                    if (!empty($_POST['grade'])) {
                                        $stmt = $pdo->prepare("DELETE FROM avoir WHERE id_ens = ?");
                                        $stmt->execute([$id_ens]);

                                        $stmt = $pdo->prepare("INSERT INTO avoir (id_grd, id_ens, date_grd) VALUES (?, ?, CURDATE())");
                                        $stmt->execute([$_POST['grade'], $id_ens]);
                                    }

                                    // Mise à jour de la spécialité
                                    if (!empty($_POST['specialite'])) {
                                        $stmt = $pdo->prepare("DELETE FROM enseignant_specialite WHERE id_ens = ?");
                                        $stmt->execute([$id_ens]);

                                        $stmt = $pdo->prepare("INSERT INTO enseignant_specialite (id_ens, id_spe) VALUES (?, ?)");
                                        $stmt->execute([$id_ens, $_POST['specialite']]);
                                    }
                                }
                            }
                        }

                        $pdo->commit();
                        $_SESSION['success'] = "Les informations de l'utilisateur ont été mises à jour avec succès.";
                        header("Location: ?page=liste_utilisateurs");
                        exit();
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['error'] = "Erreur lors de la mise à jour : " . $e->getMessage();
                        header("Location: ?page=liste_utilisateurs&action=edit&id=" . $id_utilisateur);
                        exit();
                    }
                }
                break;

            case 'desactivate_user':
                if (isset($_POST['id_utilisateur'])) {
                    try {
                        $pdo->beginTransaction();

                        $sql = "UPDATE utilisateur SET statut_utilisateur = 'Inactif' WHERE id_utilisateur = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$_POST['id_utilisateur']]);

                        $pdo->commit();
                        $_SESSION['success'] = "Utilisateur désactivé avec succès";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['error'] = "Erreur lors de la désactivation de l'utilisateur : " . $e->getMessage();
                    }
                }
                break;

            case 'activate_user':
                if (isset($_POST['id_utilisateur'])) {
                    try {
                        $pdo->beginTransaction();

                        $sql = "UPDATE utilisateur SET statut_utilisateur = 'Actif' WHERE id_utilisateur = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$_POST['id_utilisateur']]);

                        $pdo->commit();
                        $_SESSION['success'] = "Utilisateur activé avec succès";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['error'] = "Erreur lors de l'activation de l'utilisateur : " . $e->getMessage();
                    }
                }
                break;

            case 'assign_multiple':
                if (isset($_POST['selected_inactive_users'], $_POST['assign_type_utilisateur'])) {
                    $selected_users = $_POST['selected_inactive_users'];
                    $type_utilisateur = (int)$_POST['assign_type_utilisateur'];
                    $groupe_utilisateur = isset($_POST['assign_groupe_utilisateur']) ? (int)$_POST['assign_groupe_utilisateur'] : null;
                    $niveau_acces = isset($_POST['assign_niveau_acces']) ? (int)$_POST['assign_niveau_acces'] : null;

                    $success_count = 0;
                    $error_count = 0;
                    $error_messages = [];

                    foreach ($selected_users as $id_utilisateur) {
                        try {
                            $pdo->beginTransaction();

                            // 1. Mettre à jour le type d'utilisateur
                            $stmt = $pdo->prepare("DELETE FROM utilisateur_type_utilisateur WHERE id_utilisateur = ?");
                            $stmt->execute([$id_utilisateur]);

                            $stmt = $pdo->prepare("INSERT INTO utilisateur_type_utilisateur (id_utilisateur, id_tu, date_attribution) VALUES (?, ?, CURDATE())");
                            $stmt->execute([$id_utilisateur, $type_utilisateur]);

                            // 2. Mettre à jour le groupe utilisateur (si sélectionné)
                            if ($groupe_utilisateur !== null && $groupe_utilisateur !== 0) {
                                // Vérifier si l'utilisateur a déjà un groupe
                                $stmt = $pdo->prepare("SELECT COUNT(*) FROM posseder WHERE id_util = ?");
                                $stmt->execute([$id_utilisateur]);
                                $has_group = $stmt->fetchColumn();

                                if ($has_group) {
                                    // Mettre à jour le groupe existant
                                    $stmt = $pdo->prepare("UPDATE posseder SET id_gu = ?, date_poss = CURDATE() WHERE id_util = ?");
                                    $stmt->execute([$groupe_utilisateur, $id_utilisateur]);
                                } else {
                                    // Insérer un nouveau groupe
                                    $stmt = $pdo->prepare("INSERT INTO posseder (id_util, id_gu, date_poss) VALUES (?, ?, CURDATE())");
                                    $stmt->execute([$id_utilisateur, $groupe_utilisateur]);
                                }
                            } else {
                                // Si aucun groupe n'est sélectionné, supprimer le groupe existant
                                $stmt = $pdo->prepare("DELETE FROM posseder WHERE id_util = ?");
                                $stmt->execute([$id_utilisateur]);
                            }

                            // 3. Mettre à jour le niveau d'accès (si sélectionné)
                            if ($niveau_acces !== null && $niveau_acces !== 0) {
                                $stmt = $pdo->prepare("UPDATE utilisateur SET id_niveau_acces = ? WHERE id_utilisateur = ?");
                                $stmt->execute([$niveau_acces, $id_utilisateur]);
                            }

                            // 4. Mettre à jour le statut en 'Actif'
                            $stmt = $pdo->prepare("UPDATE utilisateur SET statut_utilisateur = 'Actif' WHERE id_utilisateur = ?");
                            $stmt->execute([$id_utilisateur]);

                            // 5. Générer et envoyer les identifiants par mail
                            $stmt = $pdo->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
                            $stmt->execute([$id_utilisateur]);
                            $email = $stmt->fetchColumn();

                            if ($email) {
                                $password = generateRandomPassword();
                                $hashed_password = hash('sha256', $password);

                                // Mise à jour du mot de passe
                                $stmt = $pdo->prepare("UPDATE utilisateur SET mdp_utilisateur = ? WHERE id_utilisateur = ?");
                                $stmt->execute([$hashed_password, $id_utilisateur]);

                                // Envoi du mail avec les identifiants
                                $subject = "✉️ Activation de votre compte CHECK Master – Vos identifiants de connexion";
                                
                                // Récupération du nom complet de l'utilisateur
                                $stmt = $pdo->prepare("SELECT 
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
                                $stmt->execute([$email]);
                                $nom_complet = $stmt->fetchColumn() ?: 'Utilisateur';
                                
                                $message = '
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
                                        .header::before {
                                            content: "";
                                            position: absolute;
                                            top: 0;
                                            left: 0;
                                            right: 0;
                                            bottom: 0;
                                            background: url("data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.1"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
                                            opacity: 0.3;
                                        }
                                        .header h1 {
                                            margin: 0;
                                            font-size: 24px;
                                            font-weight: 700;
                                            position: relative;
                                            z-index: 1;
                                        }
                                        .header .subtitle {
                                            margin: 8px 0 0 0;
                                            font-size: 14px;
                                            opacity: 0.9;
                                            position: relative;
                                            z-index: 1;
                                        }
                                        .content {
                                            padding: 35px 25px;
                                        }
                                        .welcome-section {
                                            margin-bottom: 30px;
                                        }
                                        .welcome-section h2 {
                                            color: #1a5276;
                                            font-size: 20px;
                                            margin: 0 0 15px 0;
                                            font-weight: 600;
                                        }
                                        .welcome-section p {
                                            margin: 0 0 15px 0;
                                            color: #555;
                                            font-size: 15px;
                                        }
                                        .credentials-section {
                                            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                                            border-radius: 10px;
                                            padding: 25px;
                                            margin: 25px 0;
                                            border-left: 4px solid #1a5276;
                                        }
                                        .credentials-section h3 {
                                            color: #1a5276;
                                            margin: 0 0 20px 0;
                                            font-size: 18px;
                                            font-weight: 600;
                                            display: flex;
                                            align-items: center;
                                            gap: 8px;
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
                                        .security-warning h4 {
                                            color: #856404;
                                            margin: 0 0 10px 0;
                                            font-size: 16px;
                                            font-weight: 600;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            gap: 8px;
                                        }
                                        .security-warning p {
                                            color: #856404;
                                            margin: 0;
                                            font-size: 14px;
                                        }
                                        .footer {
                                            background: #f8f9fa;
                                            padding: 20px 25px;
                                            text-align: center;
                                            border-top: 1px solid #e9ecef;
                                        }
                                        .footer p {
                                            margin: 0;
                                            color: #6c757d;
                                            font-size: 13px;
                                        }
                                        .logo {
                                            font-size: 28px;
                                            margin-bottom: 10px;
                                        }
                                        .activation-notice {
                                            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
                                            border: 1px solid #c3e6cb;
                                            border-radius: 10px;
                                            padding: 20px;
                                            margin: 20px 0;
                                            text-align: center;
                                        }
                                        .activation-notice h4 {
                                            color: #155724;
                                            margin: 0 0 10px 0;
                                            font-size: 16px;
                                            font-weight: 600;
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            gap: 8px;
                                        }
                                        .activation-notice p {
                                            color: #155724;
                                            margin: 0;
                                            font-size: 14px;
                                        }
                                        .features-list {
                                            background: #f8f9fa;
                                            border-radius: 8px;
                                            padding: 20px;
                                            margin: 20px 0;
                                        }
                                        .features-list h4 {
                                            color: #1a5276;
                                            margin: 0 0 15px 0;
                                            font-size: 16px;
                                            font-weight: 600;
                                        }
                                        .features-list ul {
                                            margin: 0;
                                            padding-left: 20px;
                                            color: #555;
                                        }
                                        .features-list li {
                                            margin: 8px 0;
                                            font-size: 14px;
                                        }
                                        @media (max-width: 600px) {
                                            body {
                                                padding: 10px;
                                            }
                                            .header, .content, .footer {
                                                padding: 20px 15px;
                                            }
                                            .credential-item {
                                                flex-direction: column;
                                                align-items: flex-start;
                                            }
                                            .credential-label {
                                                min-width: auto;
                                                margin-right: 0;
                                                margin-bottom: 5px;
                                            }
                                        }
                                    </style>
                                </head>
                                <body>
                                    <div class="email-container">
                                        <div class="header">
                                            <div class="logo">🎓</div>
                                            <h1>CHECK Master</h1>
                                            <div class="subtitle">Plateforme de Gestion Académique</div>
                                        </div>
                                        
                                        <div class="content">
                                            <div class="welcome-section">
                                                <h2>Bonjour ' . htmlspecialchars($nom_complet) . ',</h2>
                                                <p>Votre compte a été <strong>activé avec succès</strong> sur la plateforme <strong>CHECK Master</strong>.</p>
                                                <p>Vous pouvez dès maintenant vous connecter et accéder aux services qui vous sont destinés.</p>
                                            </div>
                                            
                                            <div class="activation-notice">
                                                <h4>✅ Compte activé</h4>
                                                <p>Votre compte utilisateur a été activé et vos identifiants ont été générés.</p>
                                            </div>
                                            
                                            <div class="features-list">
                                                <h4>🚀 Services disponibles :</h4>
                                                <ul>
                                                    <li>Suivi de votre parcours académique</li>
                                                    <li>Consultation des notes et évaluations</li>
                                                    <li>Dépôt de rapports et documents</li>
                                                    <li>Messagerie interne</li>
                                                    <li>Gestion des règlements</li>
                                                    <li>Et bien plus encore...</li>
                                                </ul>
                                            </div>
                                            
                                            <div class="credentials-section">
                                                <h3>🔐 Vos identifiants de connexion</h3>
                                                <div class="credential-item">
                                                    <span class="credential-label">Login :</span>
                                                    <span class="credential-value">' . htmlspecialchars($email) . '</span>
                                                </div>
                                                <div class="credential-item">
                                                    <span class="credential-label">Mot de passe temporaire :</span>
                                                    <span class="credential-value">' . htmlspecialchars($password) . '</span>
                                                </div>
                                            </div>
                                            
                                            <div class="security-warning">
                                                <h4>⚠️ Important - Sécurité</h4>
                                                <p>Pour des raisons de sécurité, nous vous recommandons de modifier ce mot de passe dès votre première connexion.</p>
                                            </div>
                                        </div>
                                        
                                        <div class="footer">
                                            <p><strong>CHECK Master</strong> - Université Félix Houphouët-Boigny</p>
                                            <p>Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
                                        </div>
                                    </div>
                                </body>
                                </html>';
                                
                                sendEmail("Administrateur GSCV", "axelangegomez2004@gmail.com", $email, $subject, $message);
                            }

                            $pdo->commit();
                            $success_count++;
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $error_count++;
                            $error_messages[] = "Erreur pour l'utilisateur ID " . $id_utilisateur . " : " . $e->getMessage();
                        }
                    }

                    if ($success_count > 0) {
                        $_SESSION['success'] = $success_count . " utilisateur(s) affecté(s) et activé(s) avec succès.";
                    }
                    if ($error_count > 0) {
                        $_SESSION['error'] = "Erreurs lors de l'affectation :<br>" . implode("<br>", $error_messages);
                    }
                }
                break;
        }
    }
}

$utilisateurs_par_page = 75;
$page_courante = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page_courante - 1) * $utilisateurs_par_page;

// Récupération des paramètres de filtrage
$type_filter = isset($_GET['type']) ? (int)$_GET['type'] : null;
$groupe_filter = isset($_GET['groupe']) ? (int)$_GET['groupe'] : null;
$statut_filter = isset($_GET['statut']) ? $_GET['statut'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

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

if ($type_filter) {
    $conditions[] = "utu.id_tu = ?";
    $params[] = $type_filter;
}

if ($groupe_filter) {
    $conditions[] = "p.id_gu = ?";
    $params[] = $groupe_filter;
}

if ($statut_filter) {
    $conditions[] = "u.statut_utilisateur = ?";
    $params[] = $statut_filter;
}

if ($search !== '') {
    $conditions[] = "(
        e.nom_ens LIKE ? OR e.prenoms_ens LIKE ? OR
        et.nom_etd LIKE ? OR et.prenom_etd LIKE ? OR
        pa.nom_personnel_adm LIKE ? OR pa.prenoms_personnel_adm LIKE ? OR
        u.login_utilisateur LIKE ? OR tu.lib_tu LIKE ?
    )";
    for ($i = 0; $i < 8; $i++) {
        $params[] = "%$search%";
    }
}

// Ajout des conditions à la requête
if (!empty($conditions)) {
    $sql_base .= " WHERE " . implode(" AND ", $conditions);
}

// Requête pour le comptage total
$sql_count = "SELECT COUNT(DISTINCT u.id_utilisateur) " . $sql_base;
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_utilisateurs = $stmt_count->fetchColumn();

$nb_pages = ceil($total_utilisateurs / $utilisateurs_par_page);

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
        u.statut_utilisateur
        " . $sql_base . "
        ORDER BY u.id_utilisateur
        LIMIT $utilisateurs_par_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des types d'utilisateurs pour le select
$types = $pdo->query("SELECT * FROM type_utilisateur ORDER BY id_tu")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des groupes d'utilisateurs pour le select
$groupes = $pdo->query("SELECT * FROM groupe_utilisateur ORDER BY id_gu")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des grades pour le select
$grades = $pdo->query("SELECT * FROM grade ORDER BY id_grd")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des fonctions pour le select
$fonctions = $pdo->query("SELECT * FROM fonction ORDER BY id_fonction")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des spécialités pour le select
$specialites = $pdo->query("SELECT * FROM specialite ORDER BY id_spe")->fetchAll(PDO::FETCH_ASSOC);

$niveaux_acces = $pdo->query("SELECT * FROM niveau_acces_donnees ORDER BY id_niveau_acces")->fetchAll(PDO::FETCH_ASSOC);

// Récupération des données pour les modales
$selected_user = null;
if (isset($_GET['action'], $_GET['id'])) {
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
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id']]);
    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

?>