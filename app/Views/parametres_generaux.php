<?php
require_once __DIR__ . '/../../config/config.php';

$_SESSION['messages'] = '';


// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement des actions
    if (isset($_POST['lib_action'])) {
        $lib_action = $_POST['lib_action'];
        $sql = "INSERT INTO action (lib_action) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_action]);
        $_SESSION['messages'] = "L'action a été ajoutée avec succès";
    }

    // Traitement des entreprises
    if (isset($_POST['lib_entreprise'])) {
        $lib_entreprise = $_POST['lib_entreprise'];
        $sql = "INSERT INTO entreprise (lib_entr) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_entreprise]);
        $_SESSION['messages'] = "L'entreprise a été ajoutée avec succès";
    }

    // Traitement de l'année académique
    if (isset($_POST['save_year']) && isset($_POST['date_debut']) && isset($_POST['date_fin'])) {
        $date_debut = $_POST['date_debut'];
        $date_fin = $_POST['date_fin'];

        if ($date_debut > $date_fin) {
            $_SESSION['messages'] = "La date de début ne peut pas être supérieure à la date de fin";
        }

        // Créer l'ID de l'année académique
        $debut = new DateTime($date_debut);
        $fin = new DateTime($date_fin);
        $id_annee = $fin->format('y') . $debut->format('y'); // Format: YY(année fin)YY(année début)

        // Vérifier si l'année académique existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM annee_academique WHERE id_ac = ?");
        $stmt->execute([$id_annee]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['messages'] = "Cette année académique existe déjà";
        }

        // Insérer la nouvelle année académique
        $sql = "INSERT INTO annee_academique (id_ac, date_debut, date_fin, statut_annee) VALUES (?, ?, ?, 'En cours')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_annee, $date_debut, $date_fin]);


        // Mettre à jour le statut des autres années académiques
        $sql = "UPDATE annee_academique SET statut_annee = 'Terminée' WHERE id_ac != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_annee]);

        // IMPORTANT: Rafraîchir la variable de session
        $newYear = refreshCurrentYear($pdo);
        $_SESSION['current_year'] = $newYear;

        $_SESSION['messages'] = "L'année académique a été ajoutée avec succès";
    }

    // Traitement des ECUE
    if (isset($_POST['lib_ecue']) && isset($_POST['credit_ecue']) && isset($_POST['lib_ue'])) {
        $lib_ecue = $_POST['lib_ecue'];
        $credit_ecue = $_POST['credit_ecue'];
        $id_ue = $_POST['lib_ue'];
        $volume_horaire_ecue = $_POST['volume_horaire_ecue'];
        $code_ecue = genererCodeECUEUnique($pdo, $id_ue);

        $sql = "INSERT INTO ecue (id_ecue, lib_ecue, credit_ecue, volume_horaire, id_ue) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$code_ecue, $lib_ecue, $credit_ecue, $volume_horaire_ecue, $id_ue]);
        $_SESSION['messages'] = "L'ECUE a été ajoutée avec succès";
    }

    // Traitement des UE
    if (isset($_POST['lib_ue']) && isset($_POST['credit_ue']) && isset($_POST['volume_horaire'])  && isset($_POST['code_ue'])) {
        $code_ue = $_POST['code_ue'];
        $lib_ue = $_POST['lib_ue'];
        $credit_ue = $_POST['credit_ue'];
        $volume_horaire = $_POST['volume_horaire'];

        $sql = "INSERT INTO ue (id_ue, lib_ue, credit_ue, volume_horaire) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$code_ue, $lib_ue, $credit_ue, $volume_horaire]);
        $_SESSION['messages'] = "L'UE a été ajoutée avec succès";
    }

    // Traitement des utilisateurs
    if (isset($_POST['user_login']) && isset($_POST['user_pswd'])) {
        $login = $_POST['user_login'];
        $password = password_hash($_POST['user_pswd'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO utilisateur (login_utilisateur, mdp_utilisateur) VALUES (?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$login, $password]);
        $_SESSION['messages'] = "L'utilisateur a été ajouté avec succès";
    }

    // Traitement des types d'utilisateurs
    if (isset($_POST['user_type'])) {
        $lib_type = $_POST['user_type'];
        $sql = "INSERT INTO type_utilisateur (lib_tu) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_type]);
        $_SESSION['messages'] = "Le type d'utilisateur a été ajouté avec succès";
    }

    // Traitement des groupes d'utilisateurs
    if (isset($_POST['user_group'])) {
        $lib_groupe = $_POST['user_group'];
        $sql = "INSERT INTO groupe_utilisateur (lib_gu) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_groupe]);
        $_SESSION['messages'] = "Le groupe d'utilisateurs a été ajouté avec succès";
    }

    // Traitement des fonctions
    if (isset($_POST['nom_fonction'])) {
        $lib_fonction = $_POST['nom_fonction'];
        $sql = "INSERT INTO fonction (nom_fonction) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_fonction]);
        $_SESSION['messages'] = "La fonction a été ajoutée avec succès";
    }

    // Traitement des grades
    if (isset($_POST['grade_name'])) {
        $lib_grade = $_POST['grade_name'];
        $sql = "INSERT INTO grade (nom_grd) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_grade]);
        $_SESSION['messages'] = "Le grade a été ajouté avec succès";
    }

    // Traitement des spécialités
    if (isset($_POST['lib_specialite'])) {
        $lib_specialite = $_POST['lib_specialite'];
        $sql = "INSERT INTO specialite (lib_spe) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_specialite]);
        $_SESSION['messages'] = "La spécialité a été ajoutée avec succès";
    }

    // Traitement des niveaux d'accès
    if (isset($_POST['lib_access_donnees'])) {
        $lib_niveau_acces = $_POST['lib_access_donnees'];
        $sql = "INSERT INTO niveau_acces_donnees (lib_niveau_acces) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_niveau_acces]);
        $_SESSION['messages'] = "Le niveau d'accès a été ajouté avec succès";
    }

    // Traitement des niveaux d'approbation
    if (isset($_POST['niveau_approbation'])) {
        $lib_niveau_approbation = $_POST['niveau_approbation'];
        $sql = "INSERT INTO niveau_approbation (lib_approb) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_niveau_approbation]);
        $_SESSION['messages'] = "Le niveau d'approbation a été ajouté avec succès";
    }

    // Traitement des niveaux d'étude
    if (isset($_POST['lib_niveau_etude'])) {
        $lib_niv_etd = $_POST['lib_niveau_etude'];
        $sql = "INSERT INTO niveau_etude (lib_niv_etd) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_niv_etd]);
        $_SESSION['messages'] = "Le niveau d'étude a été ajouté avec succès";
    }

    // Traitement des statuts du jury
    if (isset($_POST['lib_statut_jury'])) {
        $lib_statut_jury = $_POST['lib_statut_jury'];
        $sql = "INSERT INTO statut_jury (lib_jury) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_statut_jury]);
        $_SESSION['messages'] = "Le statut jury a été ajouté avec succès";
    }

    // Traitement des traitements
    if (isset($_POST['lib_traitement'])) {
        $lib_traitement = $_POST['lib_traitement'];
        $sql = "INSERT INTO traitement (lib_traitement) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_traitement]);
        $_SESSION['messages'] = "Le traitement a été ajouté avec succès";
    }

    // Traitement des frais d'inscriptions
    if (isset($_POST['tarifs']) && isset($_POST['id_niv_etd'])) {
        $id_niveau_etd = intval($_POST['id_niv_etd']);
        $tarifs = $_POST['tarifs'];

        //Récupération de l'année academique (id)
        $query = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' ";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $id_ac = $result['id_ac'];

        //Récupération de l'id du niveau étudiant
        $query = "SELECT COUNT(*) FROM niveau_etude WHERE id_niv_etd = ? ";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id_niveau_etd]);
        if ($stmt->fetchColumn() > 0) {
            // Vérifier si les frais existent déjà pour ce niveau et cette année
            $check_query = "SELECT COUNT(*) FROM frais_inscription WHERE id_niv_etd = ? AND id_ac = ?";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->execute([$id_niveau_etd, $id_ac]);

            if ($check_stmt->fetchColumn() > 0) {
                // Mettre à jour les frais existants
                $sql = "UPDATE frais_inscription SET montant = ? WHERE id_niv_etd = ? AND id_ac = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$tarifs, $id_niveau_etd, $id_ac]);
                $_SESSION['messages'] = "Les frais d'inscription ont été mis à jour avec succès";
            } else {
                //Insertion dans la table frais inscription
                $sql = "INSERT INTO frais_inscription (id_niv_etd, id_ac, montant) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_niveau_etd, $id_ac, $tarifs]);
                $_SESSION['messages'] = "Frais d'inscriptions ajoutés avec succès";
            }
        } else {
            $_SESSION['messages'] = "Le niveau d'étude sélectionné n'existe pas";
        }
    }

    // Chargé de compte rendu
    if (isset($_POST['id_ens_cr']) && !empty($_POST['id_ens_cr'])) {
        $id_ens_cr = intval($_POST['id_ens_cr']);

        // 1. Désactiver tous les responsables
        $pdo->exec("UPDATE responsable_compte_rendu SET actif = 0");

        // 2. Si ce prof est déjà dans la table, on le met actif = 1. Sinon, on l'insère.
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM responsable_compte_rendu WHERE id_ens = ?");
        $stmt->execute([$id_ens_cr]);

        if ($stmt->fetchColumn() > 0) {
            // Déjà existant => update
            $stmt = $pdo->prepare("UPDATE responsable_compte_rendu SET actif = 1 WHERE id_ens = ?");
            $stmt->execute([$id_ens_cr]);
            $_SESSION['messages'] = "Le responsable de compte rendu a été ajouté avec succès";
        } else {
            // Nouveau => insert
            $stmt = $pdo->prepare("INSERT INTO responsable_compte_rendu (id_ens, actif) VALUES (?, 1)");
            $stmt->execute([$id_ens_cr]);
            $_SESSION['messages'] = "Le responsable de compte rendu a été ajouté avec succès";
        }
    }


    // Traitement des semestres
    if (isset($_POST['lib_semestre'])) {
        $lib_semestre = $_POST['lib_semestre'];
        $sql = "INSERT INTO semestre (lib_semestre) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_semestre]);
        $_SESSION['messages'] = "Le semestre a été ajouté avec succès";
    }

    // Traitement de la promotion
    if (isset($_POST['lib_promotion'])) {
        $lib_promotion = $_POST['lib_promotion'];
        $sql = "INSERT INTO promotion (lib_promotion) VALUES (?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$lib_promotion]);
        $_SESSION['messages'] = "La promotion a été ajoutée avec succès";
    }
}

// Récupération des données pour l'affichage
$actions = $pdo->query("SELECT * FROM action")->fetchAll(PDO::FETCH_ASSOC);
$entreprises = $pdo->query("SELECT * FROM entreprise")->fetchAll(PDO::FETCH_ASSOC);
$annees_academiques = $pdo->query("SELECT * FROM annee_academique")->fetchAll(PDO::FETCH_ASSOC);
$ecues = $pdo->query("SELECT * FROM ecue")->fetchAll(PDO::FETCH_ASSOC);
$ues = $pdo->query("SELECT * FROM ue")->fetchAll(PDO::FETCH_ASSOC);
$utilisateurs = $pdo->query("SELECT * FROM utilisateur")->fetchAll(PDO::FETCH_ASSOC);
$types_utilisateurs = $pdo->query("SELECT * FROM type_utilisateur")->fetchAll(PDO::FETCH_ASSOC);
$groupes_utilisateurs = $pdo->query("SELECT * FROM groupe_utilisateur")->fetchAll(PDO::FETCH_ASSOC);
$fonctions = $pdo->query("SELECT * FROM fonction")->fetchAll(PDO::FETCH_ASSOC);
$grades = $pdo->query("SELECT * FROM grade")->fetchAll(PDO::FETCH_ASSOC);
$specialites = $pdo->query("SELECT * FROM specialite")->fetchAll(PDO::FETCH_ASSOC);
$niveaux_acces = $pdo->query("SELECT * FROM niveau_acces_donnees")->fetchAll(PDO::FETCH_ASSOC);
$niveaux_approbation = $pdo->query("SELECT * FROM niveau_approbation")->fetchAll(PDO::FETCH_ASSOC);
$niveaux_etude = $pdo->query("SELECT * FROM niveau_etude")->fetchAll(PDO::FETCH_ASSOC);
$statuts_jury = $pdo->query("SELECT * FROM statut_jury")->fetchAll(PDO::FETCH_ASSOC);
$traitements = $pdo->query("SELECT * FROM traitement")->fetchAll(PDO::FETCH_ASSOC);
$responsable_compte_rendu = $pdo->query("SELECT * FROM responsable_compte_rendu")->fetchAll(PDO::FETCH_ASSOC);
$semestres = $pdo->query("SELECT * FROM semestre")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres Généraux - Tableau de Bord Commission</title>
    <link rel="stylesheet" href="/GSCV+/public/assets/css/parametres_generaux.css?v=<?php echo time(); ?>">

</head>

<body>



    <div class="alert alert-info">
        <span class="alert-icon"><i class="fas fa-info-circle"></i></span>
        <span>Les modifications des paramètres généraux affectent le fonctionnement global du système. Veuillez procéder avec précaution.</span>
    </div>

    <?php
    if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) {
        echo '<div class="alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <span>' . htmlspecialchars($_SESSION['messages']) . '</span>
              </div>';
        // Supprimer immédiatement la variable de session
        unset($_SESSION['messages']);
    }
    ?>

    <script>
        // Faire disparaître le message après 5 secondes
        const sessionMessage = document.querySelector('.alert-success');
        if (sessionMessage) {
            setTimeout(() => {
                sessionMessage.style.opacity = '0';
                sessionMessage.style.visibility = 'hidden';
                setTimeout(() => {
                    sessionMessage.remove();
                }, 300);
            }, 5000);
        }
    </script>

    <div class="parametres-container">
        <div class="section-header">
            <i class="fas fa-cogs" style="margin-right: 10px;"></i> Configuration du système
        </div>


        <div class="parameters-grid">
            <!-- Paramètre 1 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Actions de la commission</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_action">Libellé action : </label>
                            <input type="text" id="lib_action" name="lib_action" placeholder="Entrez une action">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=actions" class="button">Voir la liste des actions</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 2 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Entreprises</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_entreprise">Libellé entreprise</label>
                            <input type="text" id="lib_entreprise" name="lib_entreprise" placeholder="entreprise">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=entreprises" class="button">Voir la liste des entreprises</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 3 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Année académique</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="current_year">Année académique en cours</label>
                            <?php
                            // Récupérer l'année académique en cours
                            $sql = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1";
                            $stmt = $pdo->query($sql);
                            $annee = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($annee) {
                                $dateDebut = new DateTime($annee['date_debut']);
                                $dateFin = new DateTime($annee['date_fin']);
                                $anneeAcademique = $dateDebut->format('Y') . '-' . $dateFin->format('Y');
                            } else {
                                $anneeAcademique = "À définir";
                            }

                            $_SESSION['current_year'] = $anneeAcademique;


                            ?>
                            <input type="text" id="current_year" name="current_year" value="<?php echo $_SESSION['current_year']; ?>" placeholder="Année académique" disabled>
                        </div>
                        <div class="parameter-row">
                            <label for="date_debut">Date de début: </label>
                            <input type="date" id="date_debut" name="date_debut" value="<?php echo $annee ? $annee['date_debut'] : ''; ?>" required>
                        </div>
                        <div class="parameter-row">
                            <label for="date_fin">Date de fin: </label>
                            <input type="date" id="date_fin" name="date_fin" value="<?php echo $annee ? $annee['date_fin'] : ''; ?>" required>
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" name="save_year" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=annees_academiques" class="button">Voir la liste des années académiques</a>
                        </div>
                    </div>
                </form>
            </div>



            <!-- Paramètre 4 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Unités d'enseignements (UE)</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                    </div>


                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_ue">Libellé UE: </label>
                            <input type="text" id="lib_ue" name="lib_ue">
                        </div>

                        <div class="parameter-row">
                            <label for="credit_ue">Crédit UE</label>
                            <input type="number" id="credit_ue" name="credit_ue" min="1" max="100">
                        </div>
                        <div class="parameter-row">
                            <label for="volume_horaire_ue">Volume horaire : </label>
                            <input type="number" id="volume_horaire_ue" name="volume_horaire_ue">
                        </div>
                    </div>

                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=ue" class="button">Voir la liste des UE</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 5 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Élements constitutifs des unités d'enseignements (ECUE)</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_ecue">Libellé ECUE: </label>
                            <input type="text" id="lib_ecue" name="lib_ecue">
                        </div>
                        <div class="parameter-row">
                            <label for="credit_ecue">Crédit ECUE: </label>
                            <input type="number" id="credit_ecue" name="credit_ecue" min="1" max="10">
                        </div>
                        <div class="parameter-row">
                            <label for="volume_horaire_ecue">Volume horaire ECUE: </label>
                            <input type="number" id="volume_horaire_ecue" name="volume_horaire_ecue" min="1" max="100">
                        </div>
                        <div class="parameter-row">
                            <label for="lib_ue">UE: </label>
                            <select id="lib_ue" name="lib_ue">
                                <option value="">-- Sélectionnez une UE --</option>
                                <?php
                                $ues = $pdo->prepare("
                                SELECT id_ue, lib_ue FROM ue ORDER BY lib_ue");
                                $ues->execute();
                                $ues_list = $ues->fetchAll();
                                foreach ($ues_list as $ue) {
                                    echo "<option value=\"{$ue['id_ue']}\">{$ue['lib_ue']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=ecue" class="button">Voir la liste des ECUE</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 6 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Gestion des utilisateurs</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="user_login">login utilisateur</label>
                            <input type="text" id="user_login" name="user_login">
                        </div>
                        <div class="parameter-row">
                            <label for="user_pswd">Mot de passe utilisateur</label>
                            <input type="password" id="user_pswd" name="user_pswd" placeholder="****************">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=utilisateurs" class="button">Voir la liste des utilisateurs</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 7 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Type utilisateur</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-plug"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="user_type">Libellé type utilisateur: </label>
                            <input type="text" id="user_type" name="user_type">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=types_utilisateurs" class="button">Voir la liste des types d'utilisateurs</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 8 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Groupe d'utilisateurs</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="user_group">Libellé groupe utilisateur: </label>
                            <input type="text" id="user_group" name="user_group">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=groupes_utilisateurs" class="button">Voir la liste des groupes d'utilisateurs</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 9 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Fonctions</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-desktop"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="nom_fonction">Nom de la fonction : </label>
                            <input type="text" id="nom_fonction" name="nom_fonction">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=fonctions" class="button">Voir la liste des fonctions</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 10 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Grades</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="grade_name">Nom du grade : </label>
                            <input type="text" id="grade_name" name="grade_name">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=grades" class="button">Voir la liste des grades</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 11 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Spécialités</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-database"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_specialite">Libellé de la spécialité: </label>
                            <input type="text" id="lib_specialite" name="lib_specialite">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=specialites" class="button">Voir la liste des spécialités</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 12 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Niveau d'accès aux données</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_access_donnees">Libellé du niveau d'accès: </label>
                            <input type="text" id="lib_access_donnees" name="lib_access_donnees">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=niveaux_acces" class="button">Voir la liste des niveaux d'accès</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 13 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Niveaux d'approbation</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="niveau_approbation">Niveau d'approbation : </label>
                            <input type="text" id="niveau_approbation" name="niveau_approbation">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=niveaux_approbation" class="button">Voir la liste des niveaux d'approbation</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 14 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Niveau d'étude</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>

                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_niveau_etude">Libellé du niveau d'étude : </label>
                            <input type="text" id="lib_niveau_etude" name="lib_niveau_etude">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=niveaux_etudes" class="button">Voir la liste des niveaux d'étude</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 15 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Statut du jury</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_statut_jury">Libellé statut du jury: </label>
                            <input type="text" id="lib_statut_jury" name="lib_statut_jury">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=statuts_jury" class="button">Voir la liste des statuts du jury</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 16 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Traitement</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-gavel"></i>
                        </div>

                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_traitement">Libellé du traitement: </label>
                            <input type="text" id="lib_traitement" name="lib_traitement">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=traitements" class="button">Voir la liste des traitements</a>
                        </div>
                    </div>
                </form>
            </div>


            <!-- Paramètre 17 -->

            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Chargé de compte rendu</h3>
                        <div class="parameter-icon">
                            <i class="fa-solid fa-pen-nib"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="id_ens_cr">Nom de l'enseignant: </label>
                            <select name="id_ens_cr" id="id_ens_cr">
                                <option value="">-- Sélectionnez un enseignant --</option>
                                <?php
                                $enseignants = $pdo->prepare("
                                SELECT e.id_ens, e.nom_ens, e.prenoms_ens
                                FROM enseignants e
                                JOIN utilisateur u ON u.login_utilisateur = e.email_ens
                                JOIN posseder p ON p.id_util = u.id_utilisateur
                                WHERE p.id_gu = 9 OR p.id_gu = 8
                                ");
                                $enseignants->execute();
                                $enseignants_list = $enseignants->fetchAll();
                                foreach ($enseignants_list as $ens) {
                                    echo "<option value=\"{$ens['id_ens']}\">{$ens['nom_ens']} {$ens['prenoms_ens']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Assigner</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=enseignants" class="button">Voir la liste des enseignants</a>
                        </div>
                    </div>
                </form>
            </div>


            <!-- Paramètre 18 -->


            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Semestre</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_semestre">Libellé du semestre: </label>
                            <input type="text" name="lib_semestre" id="lib_semestre">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=semestres" class="button">Voir la liste des semestres</a>
                        </div>
                    </div>
                </form>
            </div>



            <!-- Paramètre 19 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Gestion des frais d'inscription</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-calendar-week"></i>
                        </div>
                    </div>


                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="id_niv_etd">Niveau d'étude: </label>
                            <select name="id_niv_etd" id="id_niv_etd">
                                <option value="">-- Sélectionnez un niveau d'étude--</option>
                                <?php
                                $niveaux = $pdo->prepare("SELECT * FROM niveau_etude");
                                $niveaux->execute();
                                $niveaux_list = $niveaux->fetchAll();
                                foreach ($niveaux_list as $niv) {
                                    echo "<option value=\"{$niv['id_niv_etd']}\">{$niv['lib_niv_etd']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="tarifs">Montant : </label>
                            <input type="text" name="tarifs" id="tarifs">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=frais_inscriptions" class="button">Voir la liste des tarifs inscriptions</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Paramètre 20 -->
            <div class="parameter-card">
                <form method="POST" action="">
                    <div class="parameter-header">
                        <h3>Promotion</h3>
                        <div class="parameter-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <div class="parameter-body">
                        <div class="parameter-row">
                            <label for="lib_promotion">Libellé de la promotion: </label>
                            <input type="text" name="lib_promotion" id="lib_promotion">
                        </div>
                    </div>
                    <div class="parameter-footer">
                        <div class="parameter-footer-top">
                            <button type="reset" class="button">Annuler</button>
                            <button type="submit" class="button">Enregistrer</button>
                        </div>
                        <div class="parameter-footer-bottom">
                            <a href="?liste=promotions" class="button">Voir la liste des promotions</a>
                        </div>
                    </div>
                </form>
            </div>

        </div>




        <script>
            // Script simple pour la démonstration des onglets
            document.addEventListener('DOMContentLoaded', function() {
                const tabs = document.querySelectorAll('.tabs li a');

                tabs.forEach(tab => {
                    tab.addEventListener('click', function(e) {
                        e.preventDefault();

                        // Supprimer la classe active de tous les onglets
                        tabs.forEach(t => t.classList.remove('active'));

                        // Ajouter la classe active à l'onglet cliqué
                        this.classList.add('active');

                        // Ici vous pourriez ajouter une logique pour afficher le contenu correspondant
                        // Par exemple, cacher/montrer différentes sections de paramètres
                    });
                });

                // Gestion des boutons d'enregistrement
                const saveButtons = document.querySelectorAll('.btn-primary');
                saveButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        // Simuler un enregistrement réussi
                        const card = this.closest('.parameter-card');

                        // Animation simple
                        card.style.transition = 'all 0.3s ease';
                        card.style.borderColor = '#4caf50';
                        card.style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.2)';

                        setTimeout(() => {
                            card.style.borderColor = '';
                            card.style.boxShadow = '';
                        }, 2000);

                        // En production, ici vous placeriez votre code AJAX pour enregistrer les modifications
                    });
                });

                // Bouton d'enregistrement global
                const saveAllButton = document.querySelector('.save-all-btn');
                saveAllButton.addEventListener('click', function() {
                    // Simuler un enregistrement de tous les paramètres
                    const cards = document.querySelectorAll('.parameter-card');

                    cards.forEach((card, index) => {
                        setTimeout(() => {
                            card.style.transition = 'all 0.3s ease';
                            card.style.borderColor = '#4caf50';
                            card.style.boxShadow = '0 0 0 3px rgba(76, 175, 80, 0.2)';

                            setTimeout(() => {
                                card.style.borderColor = '';
                                card.style.boxShadow = '';
                            }, 1000);
                        }, index * 100);
                    });

                    // Afficher une notification de succès (pourrait être remplacé par un système de notification plus élégant)
                    alert('Tous les paramètres ont été enregistrés avec succès !');
                });

                const dateDebut = document.getElementById('date_debut');
                const dateFin = document.getElementById('date_fin');
                const currentYear = document.getElementById('current_year');

                function updateAcademicYear() {
                    if (dateDebut.value && dateFin.value) {
                        const debut = new Date(dateDebut.value);
                        const fin = new Date(dateFin.value);
                        currentYear.value = debut.getFullYear() + '-' + fin.getFullYear();
                    }
                }

                dateDebut.addEventListener('change', updateAcademicYear);
                dateFin.addEventListener('change', updateAcademicYear);
            });
        </script>

</body>

</html>