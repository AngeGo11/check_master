<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../app/Controllers/DemandeSoutenanceController.php';
require_once __DIR__ . '/../../app/Controllers/SoutenanceController.php';

use App\Models\Etudiant;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function buildURL($search = '', $statut = '', $page = 1)
{
    $params = [];
    if ($search !== '') $params['search'] = $search;
    if ($statut !== '') $params['eligibility_filter'] = $statut;
    if ($page > 1) $params['page'] = $page;
    $baseURL = 'index_personnel_administratif.php?page=demandes_soutenances';
    if (!empty($params)) {
        $baseURL .= '&' . http_build_query($params);
    }
    return $baseURL;
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Initialiser les contrôleurs
$demandeController = new DemandeSoutenanceController($pdo);
$soutenanceController = new SoutenanceController($pdo);

// Paramètres de pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Paramètres de recherche et filtres
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$statut = isset($_GET['eligibility_filter']) ? $_GET['eligibility_filter'] : '';

// Récupérer les demandes via le contrôleur
$total_records = $demandeController->count($search, $statut);
$total_pages = ceil($total_records / $limit);
$demandes = $demandeController->search($search, $statut, $page, $limit);
$demandesEnAttente = $demandeController->countDemandeWaiting();
$demandesTraitee = $demandeController->countDemandeTreated();

// Récupérer la date de demande pour l'étudiant actuel si on est sur une page de détail
$date_demande = '';
if (isset($_GET['id'])) {
    $student_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT date_demande FROM demande_soutenance WHERE num_etd = ? ORDER BY date_demande DESC LIMIT 1");
    $stmt->execute([$student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $date_demande = date('d/m/Y', strtotime($result['date_demande']));
    }
}

// Récupérer le statut d'éligibilité pour l'étudiant actuel si on est sur une page de détail
$statut_eligibilite = '';
if (isset($_GET['id'])) {
    $student_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT statut_eligibilite FROM etudiants WHERE num_etd = ?");
    $stmt->execute([$student_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $statut_eligibilite = $result['statut_eligibilite'];
    }
}


// Définition d'un message de confirmation/erreur
$message = "";

// Vérification de l'existence de l'ID dans l'URL
$student_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
} elseif (isset($_GET['id'])) {
    $student_id = $_GET['id'];
}

// Traitement de la vérification d'éligibilité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_eligibility'])) {
    $critere1 = isset($_POST['critere1']) && $_POST['critere1'] === 'yes';
    $critere2 = isset($_POST['critere2']) && $_POST['critere2'] === 'yes';
    $critere3 = isset($_POST['critere3']) && $_POST['critere3'] === 'yes';

    // Récupérer les informations de l'étudiant
    $stmt = $pdo->prepare("SELECT email_etd, nom_etd, prenom_etd FROM etudiants WHERE num_etd = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($critere1 && $critere2 && $critere3) {
        // L'étudiant est éligible
        $subject = "Félicitations ! Vous êtes éligible pour la soutenance";
        $message = "
                <html>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color: #2c3e50;'>Félicitations {$student['prenom_etd']} {$student['nom_etd']} !</h2>
                        <p>Nous avons le plaisir de vous informer que vous êtes éligible pour la soutenance de votre mémoire de Master 2.</p>
                        <p>Vous recevrez prochainement les détails concernant la date et le lieu de votre soutenance.</p>
                        <p>Nous vous recommandons de :</p>
                        <ul>
                            <li>Finaliser votre mémoire</li>
                            <li>Préparer votre présentation</li>
                            <li>Vérifier que tous vos documents sont à jour</li>
                        </ul>
                        <p>Cordialement,<br>L'équipe Check Master</p>
                    </div>
                </body>
                </html>
            ";

        // Mettre à jour le statut dans la base de données
        $stmt = $pdo->prepare("UPDATE etudiants SET statut_eligibilite = 'Éligible' WHERE num_etd = ?");
        $stmt->execute([$student_id]);

        $stmt = $pdo->prepare("UPDATE demande_soutenance SET statut_demande = 'Traitée', date_traitement = NOW() WHERE num_etd = ?");
        $stmt->execute([$student_id]);

        // Définir le message de succès
        $_SESSION['success_message'] = "L'étudiant a été marqué comme éligible et un email a été envoyé.";
    } else {
        // L'étudiant n'est pas éligible
        $reasons = [];
        if (!$critere1) $reasons[] = "Vous n'êtes pas à jour dans votre scolarité";
        if (!$critere2) $reasons[] = "Vous n'êtes pas en Master 2";
        if (!$critere3) $reasons[] = "Vous n'êtes pas en stage";

        $reasonsHtml = "";
        foreach ($reasons as $reason) {
            $reasonsHtml .= "<li>" . $reason . "</li>";
        }

        $subject = "Information concernant votre éligibilité pour la soutenance";
        $message = "
                <html>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                        <h2 style='color: #2c3e50;'>Cher(e) {$student['prenom_etd']} {$student['nom_etd']},</h2>
                        <p>Nous regrettons de vous informer que vous n'êtes pas éligible pour la soutenance de votre mémoire de Master 2 pour les raisons suivantes :</p>
                        <ul style='color: #e74c3c;'>
                            " . $reasonsHtml . "
                        </ul>
                        <p>Nous vous invitons à régulariser votre situation dès que possible en :</p>
                        <ul>
                            <li>Mettant à jour votre scolarité si nécessaire</li>
                            <li>Vérifiant votre niveau d'études</li>
                            <li>Confirmant votre statut de stage</li>
                        </ul>
                        <p>Une fois ces points régularisés, vous pourrez soumettre à nouveau votre demande d'éligibilité.</p>
                        <p>Cordialement,<br>L'équipe Check Master</p>
                    </div>
                </body>
                </html>
            ";

        // Mettre à jour le statut dans la base de données
        $stmt = $pdo->prepare("UPDATE etudiants SET statut_eligibilite = 'Non éligible' WHERE num_etd = ?");
        $stmt->execute([$student_id]);

        $stmt = $pdo->prepare("UPDATE demande_soutenance SET statut_demande = 'Traitée', date_traitement = NOW() WHERE num_etd = ?");
        $stmt->execute([$student_id]);

        // Définir le message de non-éligibilité
        $_SESSION['success_message'] = "L'étudiant a été marqué comme non éligible et un email a été envoyé.";
    }

    // Envoyer l'email
    try {
        if (sendEmail('Check Master', 'axelangegomez2004@gmail.com', $student['email_etd'], $subject, $message)) {
            $_SESSION['success_message'] = "L'email a été envoyé avec succès à {$student['email_etd']}";
        } else {
            $_SESSION['error_message'] = "Erreur lors de l'envoi de l'email";
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur lors de l'envoi de l'email: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes de Soutenances - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2980b9',
                        'primary-lighter': '#3498db',
                        secondary: '#ff8c00',
                        accent: '#4caf50',
                        success: '#4caf50',
                        warning: '#f39c12',
                        danger: '#e74c3c',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'bounce-in': 'bounceIn 0.8s ease-out',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">


        <!-- Contenu principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 animate-slide-up">
                <!-- Demandes en attente -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-warning overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Demandes en attente</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $demandesEnAttente; ?></p>
                                <p class="text-sm text-warning mt-2">À évaluer</p>
                            </div>
                            <div class="bg-warning/10 rounded-full p-4">
                                <i class="fas fa-hourglass-half text-2xl text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Demandes traitées -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Demandes traitées</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $demandesTraitee; ?></p>
                                <p class="text-sm text-accent mt-2">Terminées</p>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-check-circle text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des demandes -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                <div class="border-l-4 border-primary bg-white rounded-r-lg shadow-sm p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-list text-primary mr-3"></i>
                        Liste des demandes en attente
                    </h2>
                    <p class="text-gray-600">
                        Évaluation des demandes d'éligibilité à la soutenance
                    </p>
                </div>

                <!-- Filtres et actions -->
                <div class="p-6 border-b border-gray-200">
                    <form method="get" class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                        <input type="hidden" name="page" value="demandes_soutenances">

                        <!-- Filtres de recherche -->
                        <div class="flex-1 w-full lg:w-auto">
                            <div class="flex flex-col sm:flex-row gap-4">
                                <!-- Recherche -->
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text" 
                                           id="search-input"
                                           name="search"
                                           class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           placeholder="Rechercher une demande d'étudiant..." 
                                           value="<?php echo htmlspecialchars($search); ?>"
                                           onkeyup="handleSearchKeyup(event)">
                                </div>
                                
                                <!-- Filtre statut -->
                                <select id="eligibility-filter" 
                                        name="eligibility_filter"
                                        class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        onchange="applyFilters()">
                                    <option value="">Statut de l'éligibilité</option>
                                    <option value="Éligible" <?php echo $statut === 'Éligible' ? 'selected' : ''; ?>>Éligible</option>
                                    <option value="Non éligible" <?php echo $statut === 'Non éligible' ? 'selected' : ''; ?>>Non éligible</option>
                                    <option value="En attente de confirmation" <?php echo $statut === 'En attente de confirmation' ? 'selected' : ''; ?>>En attente de confirmation</option>
                                </select>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <button type="button" 
                                    class="px-4 py-3 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center" 
                                    id="bulk-delete-btn">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer sélection
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table des demandes -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" id="select-all" 
                                           class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Étudiant
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    N° Carte
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Statut de la demande
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            if ($demandes) {
                                foreach ($demandes as $demande) { ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" 
                                                   class="demande-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                   value="<?php echo $demande['id_demande']; ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                                                        <span class="text-sm font-medium text-primary">
                                                            <?php echo substr($demande['nom_etd'], 0, 1); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo $demande["nom_etd"] . " " . $demande["prenom_etd"]; ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo $demande["email_etd"]; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo $demande["num_carte_etd"]; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="?page=demandes_soutenances&id=<?php echo $demande['num_etd']; ?>&action=verify" 
                                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary hover:bg-primary-light transition-colors duration-200">
                                                <i class="fas fa-user-check mr-2"></i>
                                                Vérifier éligibilité
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php 
                                            $status = isset($demande['statut_demande']) ? $demande['statut_demande'] : 'En attente';
                                            $statusClass = '';
                                            switch($status) {
                                                case 'Traitée':
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    break;
                                                case 'En attente':
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                            }
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                <?php echo $status; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php }
                            } else { ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <div class="text-gray-500">
                                            <i class="fas fa-clipboard-check text-4xl mb-4"></i>
                                            <h3 class="text-lg font-medium mb-2">Aucune demande trouvée</h3>
                                            <p>Aucune demande de soutenance ne correspond aux critères.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Page <?php echo $page; ?> sur <?php echo $total_pages; ?>
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="<?php echo buildURL($search, $statut, $page - 1); ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);

                                if ($start_page > 1): ?>
                                    <a href="<?php echo buildURL($search, $statut, 1); ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                        1
                                    </a>
                                    <?php if ($start_page > 2): ?>
                                        <span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="<?php echo buildURL($search, $statut, $i); ?>" 
                                       class="px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 <?php echo $i === $page ? 'bg-primary text-white' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span class="px-3 py-2 text-sm font-medium text-gray-500">...</span>
                                    <?php endif; ?>
                                    <a href="<?php echo buildURL($search, $statut, $total_pages); ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                        <?php echo $total_pages; ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="<?php echo buildURL($search, $statut, $page + 1); ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php
    // Fenêtre modale pour vérifier l'éligibilité d'un étudiant
    if (isset($_GET['action']) && $_GET['action'] === 'verify' && isset($_GET['id'])) {
        // Récupération des informations de l'étudiant
        $donneesEligibiliteEtudiant = $pdo->prepare("
            SELECT e.*, i.id_niv_etd,i.date_insc, n.lib_niv_etd 
            FROM etudiants e
            LEFT JOIN inscrire i ON e.num_etd = i.num_etd
            LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd
            WHERE e.num_etd = ?
        ");
        $donneesEligibiliteEtudiant->execute([$_GET['id']]);
        $etudiant = $donneesEligibiliteEtudiant->fetch();

        // Récupération des informations de stage
        $stageInfo = $pdo->prepare("
            SELECT f.*, e.lib_entr, e.adresse, e.ville, e.pays
            FROM faire_stage f
            JOIN entreprise e ON f.id_entr = e.id_entr
            WHERE f.num_etd = ?
        ");
        $stageInfo->execute([$_GET['id']]);
        $stage = $stageInfo->fetch();

        // Récupération des informations de scolarité
        $scolariteInfo = $pdo->prepare("
            SELECT 
                e.num_etd,
                r.num_etd,
                e.statut_eligibilite,
                r.montant_a_payer,
                r.reste_a_payer
            FROM etudiants e
             JOIN reglement r ON e.num_etd = r.num_etd
            WHERE e.num_etd = ?
            GROUP BY e.num_etd, r.num_etd, e.statut_eligibilite, r.montant_a_payer, r.reste_a_payer
        ");
        $scolariteInfo->execute([$_GET['id']]);
        $scolarite = $scolariteInfo->fetch();

        // Calcul de l'âge
        $age = null;
        if ($etudiant['date_naissance_etd']) {
            $dateNaissance = new DateTime($etudiant['date_naissance_etd']);
            $aujourdhui = new DateTime();
            $age = $aujourdhui->diff($dateNaissance)->y;
        }

        // Détermination du niveau d'étude
        $niveauEtude = $etudiant['lib_niv_etd'] ?? "Non renseigné";

        // Statut du stage
        $statutStage = $stage ? "En stage" : "Pas de stage déclaré";

        if (isset($scolarite['reste_a_payer'])) {
            if ($scolarite['reste_a_payer'] > 0) {
                $scolariteAJour = "Paiement partiel (Reste à payer: " . $scolarite['reste_a_payer'] . " FCFA)";
            } elseif ($scolarite['reste_a_payer'] == 0) {
                $scolariteAJour = "Soldé";
            }
        } else {
            $scolariteAJour = "Non payé";
        }
    ?>

        <!-- Modal d'éligibilité moderne -->
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" id="check-eligibility-student-modal">
            <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 h-5/6 overflow-y-auto animate-bounce-in">
                <div class="sticky top-0 bg-white border-b p-6 rounded-t-xl">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-user-check text-primary text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">
                                    Éligibilité - <?php echo $etudiant['prenom_etd'] . " " . $etudiant['nom_etd']; ?>
                                </h2>
                                <p class="text-sm text-gray-600">Vérification des critères d'éligibilité à la soutenance</p>
                            </div>
                        </div>
                        <a href="?page=demandes_soutenances" 
                           class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </a>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Section Informations Générales -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-info-circle text-primary mr-2"></i>
                            Informations Générales
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Nom complet</label>
                                    <p class="text-sm text-gray-900"><?php echo $etudiant['prenom_etd'] . " " . $etudiant['nom_etd']; ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Email</label>
                                    <p class="text-sm text-gray-900"><?php echo $etudiant['email_etd']; ?></p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">N° Carte Étudiant</label>
                                    <p class="text-sm text-gray-900"><?php echo $etudiant['num_carte_etd']; ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Âge</label>
                                    <p class="text-sm text-gray-900"><?php echo $age ? $age . " ans" : "Non renseigné"; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Scolarité -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-graduation-cap text-primary mr-2"></i>
                            Scolarité
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-gray-500">Niveau d'étude</label>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                    <?php echo $niveauEtude; ?>
                                </span>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-500">Statut scolarité</label>
                                <?php 
                                $statusClass = '';
                                if (strpos($scolariteAJour, 'Soldé') !== false) {
                                    $statusClass = 'bg-green-100 text-green-800';
                                } elseif (strpos($scolariteAJour, 'Paiement partiel') !== false) {
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                } else {
                                    $statusClass = 'bg-red-100 text-red-800';
                                }
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?> mt-1">
                                    <?php echo $scolariteAJour; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Section Stage -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-briefcase text-primary mr-2"></i>
                            Informations Stage
                        </h3>
                        <?php if ($stage): ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Entreprise</label>
                                        <p class="text-sm text-gray-900"><?php echo $stage['lib_entr']; ?></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Intitulé du stage</label>
                                        <p class="text-sm text-gray-900"><?php echo $stage['intitule_stage']; ?></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Type de stage</label>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mt-1">
                                            <?php echo ucfirst(str_replace('_', ' ', $stage['type_stage'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Période</label>
                                        <p class="text-sm text-gray-900">
                                            Du <?php echo date('d/m/Y', strtotime($stage['date_debut'])); ?>
                                            au <?php echo date('d/m/Y', strtotime($stage['date_fin'])); ?>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Lieu</label>
                                        <p class="text-sm text-gray-900"><?php echo $stage['ville'] . ", " . $stage['pays']; ?></p>
                                    </div>
                                    <div>
                                        <label class="text-sm font-medium text-gray-500">Tuteur</label>
                                        <p class="text-sm text-gray-900"><?php echo $stage['nom_tuteur'] . " (" . $stage['poste_tuteur'] . ")"; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="text-sm font-medium text-gray-500">Description du stage</label>
                                <div class="mt-1 p-3 bg-white rounded-lg border text-sm text-gray-900">
                                    <?php echo nl2br(htmlspecialchars($stage['description_stage'])); ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-exclamation-triangle text-4xl text-yellow-400 mb-4"></i>
                                <h4 class="text-lg font-medium text-gray-900 mb-2">Aucun stage déclaré</h4>
                                <p class="text-gray-600">Aucun stage n'a été déclaré par cet étudiant.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Section Critères d'Éligibilité -->
                    <div class="bg-white border rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-clipboard-check text-primary mr-2"></i>
                            Critères d'Éligibilité
                        </h3>

                        <form id="eligibility-form" action="?page=demandes_soutenances" method="POST">
                            <input type="hidden" name="check_eligibility" value="1">
                            <input type="hidden" name="student_id" value="<?php echo $_GET['id']; ?>">

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Critère</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut Actuel</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Conforme</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Non Conforme</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">Scolarité à jour</div>
                                                    <div class="text-sm text-gray-500">L'étudiant a soldé sa scolarité</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                                    <?php echo $scolariteAJour; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="radio" name="critere1" value="yes" id="critere1-yes"
                                                    <?php echo ($scolariteAJour == "Soldé") ? "checked" : ""; ?> 
                                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300" required>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="radio" name="critere1" value="no" id="critere1-no"
                                                    <?php echo ($scolariteAJour != "Soldé") ? "checked" : ""; ?>
                                                    class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">Niveau Master 2</div>
                                                    <div class="text-sm text-gray-500">L'étudiant est inscrit en Master 2</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo $niveauEtude; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="radio" name="critere2" value="yes" id="critere2-yes"
                                                    <?php echo ($niveauEtude == "Master 2") ? "checked" : ""; ?> 
                                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300" required>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="radio" name="critere2" value="no" id="critere2-no"
                                                    <?php echo ($niveauEtude != "Master 2") ? "checked" : ""; ?>
                                                    class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">Stage déclaré</div>
                                                    <div class="text-sm text-gray-500">L'étudiant a déclaré et effectue un stage</div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php 
                                                $stageStatusClass = $stage ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                                                ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $stageStatusClass; ?>">
                                                    <?php echo $statutStage; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="radio" name="critere3" value="yes" id="critere3-yes"
                                                    <?php echo $stage ? "checked" : ""; ?> 
                                                    class="h-4 w-4 text-primary focus:ring-primary border-gray-300" required>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="radio" name="critere3" value="no" id="critere3-no"
                                                    <?php echo !$stage ? "checked" : ""; ?>
                                                    class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">Demande initiée le :</div>
                                        <div class="text-sm text-gray-600"><?= $date_demande ? $date_demande : 'Non définie'; ?></div>
                                    </div>
                                    
                                    <?php if($statut_eligibilite === 'En attente de confirmation'): ?>
                                        <button type="submit" 
                                                class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                            <i class="fas fa-check mr-2"></i>
                                            Valider l'éligibilité
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }
    ?>

    <!-- Modal de confirmation moderne -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="confirmation-modal">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full mx-4 animate-bounce-in">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-question-circle text-primary text-lg"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmation</h3>
                </div>
                <p class="text-gray-600 mb-6" id="confirmation-text">
                    Voulez-vous vraiment effectuer cette action ?
                </p>
                <div class="flex gap-3 justify-end">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors" 
                            id="cancel-modal-btn">
                        Annuler
                    </button>
                    <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors" 
                            id="confirm-modal-btn">
                        Confirmer
                    </button>
                </div>
            </div>
            <button class="absolute top-4 right-4 text-gray-400 hover:text-gray-600" id="close-confirmation-modal-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <script>
        // Sélectionner les éléments nécessaires pour les ouvertures/fermetures modales
        document.addEventListener('DOMContentLoaded', function() {
            // Fermer les modales quand on clique sur la croix
            document.querySelectorAll('.close').forEach(function(button) {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const modal = this.closest('.modal');
                    if (modal) {
                        modal.classList.remove('open');
                    }
                });
            });

            // Fermer les modales quand on clique en dehors du contenu
            document.querySelectorAll('.modal').forEach(function(modal) {
                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        modal.classList.remove('open');
                    }
                });
            });

            // Gérer la suppression en masse
            const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
            if (bulkDeleteBtn) {
                bulkDeleteBtn.addEventListener('click', function() {
                    const checkedBoxes = document.querySelectorAll('.demande-checkbox:checked');
                    const demandeIds = Array.from(checkedBoxes).map(cb => cb.value);
                    if (demandeIds.length === 0) {
                        openConfirmationModal('Veuillez sélectionner au moins une demande à supprimer.', null);
                        return;
                    }
                    openConfirmationModal(
                        `Voulez-vous vraiment supprimer les ${demandeIds.length} demandes sélectionnées ?`,
                        function() {
                            fetch('../public/assets/traitements/supprimer_demandes.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: 'demande_ids=' + JSON.stringify(demandeIds)
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        openConfirmationModal(data.message || 'Demandes supprimées avec succès.', function() {
                                            location.reload();
                                        });
                                    } else {
                                        openConfirmationModal('Erreur : ' + (data.error || 'Erreur inconnue.'), null);
                                    }
                                })
                                .catch(error => {
                                    console.error('Erreur:', error);
                                    openConfirmationModal('Une erreur de communication est survenue.', null);
                                });
                        }
                    );
                });
            }
        });

        // Fonction pour construire l'URL correctement
        // Fonction de recherche (déclenché manuellement)
        function applySearch() {
            const searchText = document.getElementById('search-input').value;
            const eligibilityFilter = document.getElementById('eligibility-filter').value;

            const url = buildURL(searchText, eligibilityFilter, 1); // Reset à la page 1 pour une nouvelle recherche
            window.location.href = url;
        }

        // Fonction pour appliquer les filtres
        function applyFilters() {
            applySearch(); // Même logique que la recherche
        }

        // Fonction de pagination
        function goToPage(pageNumber) {
            const searchText = document.getElementById('search-input').value;
            const eligibilityFilter = document.getElementById('eligibility-filter').value;

            const url = buildURL(searchText, eligibilityFilter, pageNumber);
            window.location.href = url;
        }

        // Fonction pour gérer les touches dans la recherche
        function handleSearchKeyup(event) {
            // Recherche UNIQUEMENT sur Enter
            if (event.key === 'Enter') {
                applySearch();
            }
        }

        // Fonction pour effacer tous les filtres
        function clearFilters() {
            // Rediriger vers la page de base sans paramètres
            window.location.href = 'index_personnel_administratif.php?page=demandes_soutenances';
        }

        // Gestion des événements de recherche
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');

            if (searchInput) {
                // Recherche UNIQUEMENT sur Enter (géré par handleSearchKeyup dans l'HTML)
                // Mais on peut aussi l'ajouter ici pour la sécurité
                searchInput.addEventListener('keyup', function(event) {
                    if (event.key === 'Enter') {
                        applySearch();
                    }
                });
            }

            // Feedback visuel pour la recherche
            const searchBox = document.querySelector('.search-box');
            if (searchBox) {
                const input = searchBox.querySelector('input');
                if (input) {
                    input.addEventListener('focus', function() {
                        searchBox.style.borderColor = '#1a5276';
                    });

                    input.addEventListener('blur', function() {
                        searchBox.style.borderColor = '#e1e8ed';
                    });
                }
            }
        });

        // Modale de confirmation générique
        let confirmCallback = null;

        function openConfirmationModal(message, onConfirm) {
            document.getElementById('confirmation-text').textContent = message;
            document.getElementById('confirmation-modal').style.display = 'flex';
            confirmCallback = onConfirm;
        }

        function closeConfirmationModal() {
            document.getElementById('confirmation-modal').style.display = 'none';
            confirmCallback = null;
        }
        document.getElementById('confirm-modal-btn').onclick = function() {
            if (typeof confirmCallback === 'function') confirmCallback();
            closeConfirmationModal();
        };
        document.getElementById('cancel-modal-btn').onclick = closeConfirmationModal;
        document.getElementById('close-confirmation-modal-btn').onclick = closeConfirmationModal;
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('confirmation-modal');
            if (event.target === modal) closeConfirmationModal();
        });

        // Système de notifications moderne
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 max-w-sm bg-white border rounded-lg shadow-lg p-4 transform transition-all duration-300 translate-x-full`;
            
            const bgColor = type === 'success' ? 'border-l-4 border-l-green-500' : 
                           type === 'error' ? 'border-l-4 border-l-red-500' : 
                           'border-l-4 border-l-blue-500';
            
            const icon = type === 'success' ? 'fas fa-check-circle text-green-500' :
                        type === 'error' ? 'fas fa-exclamation-circle text-red-500' :
                        'fas fa-info-circle text-blue-500';
            
            notification.className += ` ${bgColor}`;
            
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="${icon} text-lg mr-3"></i>
                    <p class="text-gray-900 flex-1">${message}</p>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600 ml-2">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
        }

        // Vérifier les messages de session
        <?php if (isset($_SESSION['success_message'])): ?>
            showNotification('<?php echo $_SESSION['success_message']; ?>', 'success');
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            showNotification('<?php echo $_SESSION['error_message']; ?>', 'error');
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
    </script>

</body>

</html>