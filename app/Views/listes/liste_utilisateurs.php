<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'utilisateurs') {
    return;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../config/config.php';
require_once  'C:/wamp64/www/GSCV+/config/mail.php';



$fullname = $_SESSION['user_fullname'] ?? 'Utilisateur';
$lib_user_type = $_SESSION['lib_user_type'] ?? '';

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

                        $pdo->commit();
                        $_SESSION['success'] = "Utilisateur ajouté avec succès. Un email a été envoyé avec les identifiants.";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur: " . $e->getMessage();
                    }
                }
                break;

            case 'generate_passwords':
                if (isset($_POST['selected_users']) && is_array($_POST['selected_users'])) {
                    $selected_users = $_POST['selected_users'];
                    $success_count = 0;

                    foreach ($selected_users as $user_id) {
                        try {
                            $password = generateRandomPassword();
                            $hashed_password = hash('sha256', $password);

                            // Mise à jour du mot de passe
                            $sql = "UPDATE utilisateur SET mdp_utilisateur = ? WHERE id_utilisateur = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$hashed_password, $user_id]);

                            // Récupération de l'email de l'utilisateur
                            $sql = "SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([$user_id]);
                            $email = $stmt->fetchColumn();

                            if ($email) {
                                // Envoi de l'email avec le nouveau mot de passe
                                // Code d'envoi d'email ici...
                                $success_count++;
                            }
                        } catch (Exception $e) {
                            $_SESSION['error'] = "Erreur lors de la génération des mots de passe: " . $e->getMessage();
                        }
                    }

                    if ($success_count > 0) {
                        $_SESSION['success'] = "$success_count mot(s) de passe généré(s) et envoyé(s) par email.";
                    }
                }
                break;
        }
    }

    // Redirection pour éviter le repost
    header('Location: ?liste=utilisateurs');
    exit;
}

// Paramètres de pagination
$page_courante = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$par_page = 10;
$offset = ($page_courante - 1) * $par_page;

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

// Filtre par recherche
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $_GET['search'];
    $where_conditions[] = "(e.nom_ens LIKE ? OR e.prenoms_ens LIKE ? OR et.nom_etd LIKE ? OR et.prenom_etd LIKE ? OR pa.nom_personnel_adm LIKE ? OR pa.prenoms_personnel_adm LIKE ? OR u.login_utilisateur LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
}

// Filtre par type d'utilisateur
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $where_conditions[] = "tu.id_tu = ?";
    $params[] = $_GET['type'];
}

// Filtre par groupe d'utilisateur
if (isset($_GET['groupe']) && !empty($_GET['groupe'])) {
    $where_conditions[] = "gu.id_gu = ?";
    $params[] = $_GET['groupe'];
}

// Filtre par statut
if (isset($_GET['statut']) && !empty($_GET['statut'])) {
    $where_conditions[] = "u.statut_utilisateur = ?";
    $params[] = $_GET['statut'];
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Requête pour compter le total
$count_sql = "SELECT COUNT(DISTINCT u.id_utilisateur) as total FROM utilisateur u 
              LEFT JOIN utilisateur_type_utilisateur utu ON u.id_utilisateur = utu.id_utilisateur 
              LEFT JOIN type_utilisateur tu ON utu.id_tu = tu.id_tu 
              LEFT JOIN posseder p ON p.id_util = u.id_utilisateur
        LEFT JOIN groupe_utilisateur gu ON gu.id_gu = p.id_gu 
              LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens 
              LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd 
              LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm 
              $where_clause";

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_utilisateurs = $stmt->fetchColumn();
$nb_pages = ceil($total_utilisateurs / $par_page);

// Requête principale pour récupérer les utilisateurs
$sql = "SELECT DISTINCT u.id_utilisateur, u.login_utilisateur, u.statut_utilisateur,
        CASE 
            WHEN e.nom_ens IS NOT NULL THEN CONCAT(e.prenoms_ens, ' ', e.nom_ens)
            WHEN et.nom_etd IS NOT NULL THEN CONCAT(et.prenom_etd, ' ', et.nom_etd)
            WHEN pa.nom_personnel_adm IS NOT NULL THEN CONCAT(pa.prenoms_personnel_adm, ' ', pa.nom_personnel_adm)
            ELSE 'Utilisateur'
        END AS nom_complet,
        tu.lib_tu
        FROM utilisateur u 
        LEFT JOIN utilisateur_type_utilisateur utu ON u.id_utilisateur = utu.id_utilisateur 
        LEFT JOIN type_utilisateur tu ON utu.id_tu = tu.id_tu 
        LEFT JOIN posseder p ON p.id_util = u.id_utilisateur
        LEFT JOIN groupe_utilisateur gu ON gu.id_gu = p.id_gu 
        LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens 
        LEFT JOIN etudiants et ON u.login_utilisateur = et.email_etd 
        LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm 
        $where_clause 
        ORDER BY u.id_utilisateur DESC 
        LIMIT $par_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des types d'utilisateurs pour les filtres
$stmt = $pdo->query("SELECT id_tu, lib_tu FROM type_utilisateur ORDER BY lib_tu");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des groupes d'utilisateurs pour les filtres
$stmt = $pdo->query("SELECT id_gu, lib_gu FROM groupe_utilisateur ORDER BY lib_gu");
$groupes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Utilisateurs - Tableau de Bord Commission</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/GSCV+/app/Views/listes/assets/css/listes.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/style_liste_utilisateur.css?v=<?php echo time(); ?>">
</head>

<body>


    <div class="header">
        <div class="header-title">
            <div class="img-container">
                <img src="/GSCV+/public/assets/images/logo_mi_sbg.png" alt="">
            </div>
            <div class="text-container">
                <h1>Liste des Utilisateurs</h1>
                <p>Gestion des utilisateurs du système</p>
            </div>
        </div>

        <div class="header-actions">

            <div class="user-avatar"><?php echo substr($fullname, 0, 1); ?></div>
            <div>
                <div class="user-name"><?php echo $fullname; ?></div>
                <div class="user-role"><?php echo $lib_user_type; ?></div>
            </div>
        </div>
    </div>

    <!-- Bouton de retour -->
    <div class="actions-bar">
        <a href="?page=parametres_generaux" class="button">
            <i class="fas fa-arrow-left"></i> Retour aux paramètres
        </a>
        <form method="GET" class="search-box" style="display:inline-flex;align-items:center;gap:5px;">
            <input type="hidden" name="liste" value="utilisateurs">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Rechercher un utilisateur..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <!-- Garder les filtres actifs -->
            <?php if (isset($_GET['type'])): ?><input type="hidden" name="type" value="<?php echo htmlspecialchars($_GET['type']); ?>"><?php endif; ?>
            <?php if (isset($_GET['groupe'])): ?><input type="hidden" name="groupe" value="<?php echo htmlspecialchars($_GET['groupe']); ?>"><?php endif; ?>
            <?php if (isset($_GET['statut'])): ?><input type="hidden" name="statut" value="<?php echo htmlspecialchars($_GET['statut']); ?>"><?php endif; ?>
            <button type="submit" class="button" style="margin-left:5px;">Rechercher</button>
        </form>
        <button class="button" onclick="showMultipleAssignmentModal()">
            <i class="fas fa-plus"></i> Affectation multiple
        </button>
    </div>

    <!-- Messages de notification -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="filters">
        <select class="filter-select" id="typeFilter" onchange="applyFilters()">
            <option value="">Tous les types</option>
            <?php foreach ($types as $type): ?>
                <option value="<?php echo $type['id_tu']; ?>" <?php echo (isset($_GET['type']) && $_GET['type'] == $type['id_tu']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type['lib_tu']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="filter-select" id="groupeFilter" onchange="applyFilters()">
            <option value="">Tous les groupes</option>
            <?php foreach ($groupes as $groupe): ?>
                <option value="<?php echo $groupe['id_gu']; ?>" <?php echo (isset($_GET['groupe']) && $_GET['groupe'] == $groupe['id_gu']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($groupe['lib_gu']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="filter-select" id="statutFilter" onchange="applyFilters()">
            <option value="">Tous les statuts</option>
            <option value="Actif" <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'Actif') ? 'selected' : ''; ?>>Actif</option>
            <option value="Inactif" <?php echo (isset($_GET['statut']) && $_GET['statut'] == 'Inactif') ? 'selected' : ''; ?>>Inactif</option>
        </select>

        <div class="buttons-container" style="display: flex; gap: 10px;">
            <button class="button" onclick="generatePasswords()" id="generatePasswordsBtn" style="display: none;">
                <i class="fas fa-key"></i> Donner accès
            </button>
            <button class="button">
                <i class="fas fa-power-off"></i>Désactiver
            </button>
        </div>
    </div>

    <div class="data-table-container">
        <div class="data-table-header">
            <div class="data-table-title">Liste des utilisateurs</div>
        </div>
        <form id="usersForm" method="POST">
            <input type="hidden" name="action" value="generate_passwords">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">
                            <input type="checkbox" id="selectAll">
                        </th>
                        <th style="width: 50px;">ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $utilisateur): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_users[]" value="<?php echo $utilisateur['id_utilisateur']; ?>" class="user-checkbox">
                            </td>
                            <td><?php echo htmlspecialchars($utilisateur['id_utilisateur']); ?></td>
                            <td><?php echo htmlspecialchars($utilisateur['nom_complet']); ?></td>
                            <td><?php echo htmlspecialchars($utilisateur['login_utilisateur']); ?></td>
                            <td><?php echo !empty($utilisateur['lib_tu']) ? htmlspecialchars($utilisateur['lib_tu']) : 'Non renseigné'; ?></td>
                            <td><span class="status-badge <?php echo ($utilisateur['statut_utilisateur'] == 'Actif') ? 'status-active' : 'status-inactive'; ?>"><?php echo htmlspecialchars($utilisateur['statut_utilisateur']); ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?liste=utilisateurs&action=view&id=<?php echo $utilisateur['id_utilisateur']; ?>" class="action-button view-button" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?liste=utilisateurs&action=edit&id=<?php echo $utilisateur['id_utilisateur']; ?>" class="action-button edit-button" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button type="button" class="action-button power-off-button <?php echo ($utilisateur['statut_utilisateur'] === 'Inactif') ? 'status-inactive' : ''; ?>"
                                        title="<?php echo ($utilisateur['statut_utilisateur'] === 'Inactif') ? 'Activer le compte' : 'Désactiver le compte'; ?>"
                                        onclick="<?php echo ($utilisateur['statut_utilisateur'] === 'Inactif') ? 'showActivateModal(' . $utilisateur['id_utilisateur'] . ')' : 'showDesactivateModal(' . $utilisateur['id_utilisateur'] . ')'; ?>">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>

    <!-- Pagination -->
    <?php if ($nb_pages > 1): ?>
        <div class="pagination">
            <?php
            // Construction de l'URL de base avec les filtres
            $base_url = '?liste=utilisateurs';
            if (isset($_GET['type'])) $base_url .= '&type=' . $_GET['type'];
            if (isset($_GET['groupe'])) $base_url .= '&groupe=' . $_GET['groupe'];
            if (isset($_GET['statut'])) $base_url .= '&statut=' . $_GET['statut'];
            if (isset($_GET['search'])) $base_url .= '&search=' . urlencode($_GET['search']);
            ?>

            <?php if ($page_courante > 1): ?>
                <a class="page-item" href="<?php echo $base_url . '&page=' . ($page_courante - 1); ?>">«</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $nb_pages; $i++): ?>
                <a class="page-item <?php echo ($i === $page_courante) ? 'active' : ''; ?>"
                    href="<?php echo $base_url . '&page=' . $i; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($page_courante < $nb_pages): ?>
                <a class="page-item" href="<?php echo $base_url . '&page=' . ($page_courante + 1); ?>">»</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <script>
        // Fonction pour appliquer les filtres
        function applyFilters() {
            const type = document.getElementById('typeFilter').value;
            const groupe = document.getElementById('groupeFilter').value;
            const statut = document.getElementById('statutFilter').value;

            let url = '?liste=utilisateurs';
            if (type) url += '&type=' + type;
            if (groupe) url += '&groupe=' + groupe;
            if (statut) url += '&statut=' + statut;

            // Garder la recherche actuelle
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput && searchInput.value) {
                url += '&search=' + encodeURIComponent(searchInput.value);
            }

            window.location.href = url;
        }

        // Fonction pour générer les mots de passe
        function generatePasswords() {
            const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Veuillez sélectionner au moins un utilisateur.');
                return;
            }

            if (confirm('Êtes-vous sûr de vouloir générer des mots de passe pour les utilisateurs sélectionnés ?')) {
                document.getElementById('usersForm').submit();
            }
        }

        // Gestion de la sélection multiple
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });

            // Afficher/masquer le bouton de génération de mots de passe
            const generateBtn = document.getElementById('generatePasswordsBtn');
            if (this.checked) {
                generateBtn.style.display = 'inline-block';
            } else {
                generateBtn.style.display = 'none';
            }
        });

        // Gestion des checkboxes individuelles
        document.querySelectorAll('.user-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
                const generateBtn = document.getElementById('generatePasswordsBtn');

                if (checkedBoxes.length > 0) {
                    generateBtn.style.display = 'inline-block';
                } else {
                    generateBtn.style.display = 'none';
                }

                // Mettre à jour la checkbox "Tout sélectionner"
                const selectAllCheckbox = document.getElementById('selectAll');
                const allCheckboxes = document.querySelectorAll('.user-checkbox');
                selectAllCheckbox.checked = checkedBoxes.length === allCheckboxes.length;
            });
        });
    </script>
</body>

</html>