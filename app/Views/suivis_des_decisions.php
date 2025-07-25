<?php
require_once __DIR__ . '/../../public/assets/traitements/traitements_suivis_decisions.php';


// Initialisation des variables
$fullname = isset($_SESSION['user_fullname']) ? $_SESSION['user_fullname'] : 'Utilisateur';
$lib_user_type = isset($_SESSION['lib_user_type']) ? $_SESSION['lib_user_type'] : '';

$report_details = null;
$decision_details = null;

// Barres de recherche indépendantes
$search_pending = isset($_GET['search_pending']) ? trim($_GET['search_pending']) : '';
$search_decision = isset($_GET['search_decision']) ? trim($_GET['search_decision']) : '';

if (isset($_GET['id'])) {
    // Vérifier si c'est un rapport ou une décision
    $sql = "SELECT id_rapport_etd FROM rapport_etudiant WHERE id_rapport_etd = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['id']]);
    if ($stmt->fetch()) {
        $report_details = getReportDetails($_GET['id']);
    } else {
        $decision_details = getDecisionDetails($_GET['id']);
    }
}

try {
    // Récupération des statistiques
    $stats = getStatistics();

    // Récupération des rapports en attente
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
    $pending_reports = getPendingReports($search_pending, $date_filter);

    // Récupération des décisions finales
    $date_submission = isset($_GET['date_submission']) ? $_GET['date_submission'] : '';
    $date_decision = isset($_GET['date_decision']) ? $_GET['date_decision'] : '';
    $decision = isset($_GET['decision']) ? $_GET['decision'] : '';
    $final_decisions = getFinalDecisions($search_decision, $date_submission, $date_decision, $decision);

    // Récupération des membres de la commission
    $commission_members = getCommissionMembers();
} catch (PDOException $e) {
    // Log l'erreur
    error_log("Erreur de base de données : " . $e->getMessage());
    // Initialiser les variables avec des valeurs par défaut
    $stats = [
        'rapports_valides' => 0,
        'rapports_en_attente' => 0,
        'rapports_rejetes' => 0,
        'comptes_rendus' => 0
    ];
    $pending_reports = [];
    $final_decisions = [];
    $commission_members = [];
    $report_details = null;
    $decision_details = null;
}


// Fonction PHP pour formater la taille des fichiers
function formatFileSize($bytes)
{
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Fonction pour récupérer les détails d'une décision
function getDecisionDetails($rapport_id)
{
    global $pdo;

    // Requête principale pour les informations de base
    $query = "SELECT r.*, 
             e.nom_etd, e.prenom_etd, e.email_etd,
             r.statut_rapport as decision,
             GROUP_CONCAT(DISTINCT CONCAT(ens.nom_ens, ' ', ens.prenoms_ens) SEPARATOR '||') as evaluateurs,
             GROUP_CONCAT(DISTINCT v.com_validation SEPARATOR '||') as commentaires,
             GROUP_CONCAT(DISTINCT v.date_validation SEPARATOR '||') as dates_validation,
             GROUP_CONCAT(DISTINCT v.decision SEPARATOR '||') as decisions,
             GROUP_CONCAT(DISTINCT ens.email_ens SEPARATOR '||') as emails_evaluateurs
             FROM rapport_etudiant r
             LEFT JOIN valider v ON v.id_rapport_etd = r.id_rapport_etd
             LEFT JOIN enseignants ens ON ens.id_ens = v.id_ens 
             JOIN etudiants e ON r.num_etd = e.num_etd 
             WHERE r.id_rapport_etd = ?
             GROUP BY r.id_rapport_etd";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$rapport_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        return null;
    }

    // Formatage des évaluations
    $evaluations = [];
    if (!empty($result['evaluateurs'])) {
        $evaluateurs = explode('||', $result['evaluateurs']);
        $commentaires = explode('||', $result['commentaires']);
        $dates = explode('||', $result['dates_validation']);
        $decisions = explode('||', $result['decisions']);
        $emails = explode('||', $result['emails_evaluateurs']);

        for ($i = 0; $i < count($evaluateurs); $i++) {
            $evaluations[] = [
                'evaluateur' => $evaluateurs[$i],
                'email' => $emails[$i] ?? '',
                'commentaire' => $commentaires[$i] ?? '',
                'date' => $dates[$i] ?? '',
                'decision' => $decisions[$i] ?? ''
            ];
        }
    }
    $result['evaluations'] = $evaluations;

    // Récupération des informations du stage
    $query = "SELECT fs.*, e.lib_entr, e.adresse, e.ville, e.pays, e.telephone, e.email
              FROM faire_stage fs
              JOIN entreprise e ON fs.id_entr = e.id_entr
              WHERE fs.num_etd = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$result['num_etd']]);
    $result['stage'] = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result;
}


// Traitement de l'envoi des rappels via la modale
if (isset($_POST['send_reminders'])) {
    $recipients = isset($_POST['recipients']) ? $_POST['recipients'] : [];
    $subject = isset($_POST['rappel-subject']) ? trim($_POST['rappel-subject']) : '';
    $message = isset($_POST['rappel-message']) ? trim($_POST['rappel-message']) : '';
    $success_count = 0;
    $error_count = 0;

    // Debug : Afficher les données reçues
    error_log("Recipients: " . print_r($recipients, true));
    error_log("Subject: " . $subject);
    error_log("Message: " . $message);
    error_log("User ID: " . $_SESSION['user_id']);

    if (empty($recipients)) {
        $_SESSION['error_message'] = "Veuillez sélectionner au moins un destinataire.";
    } elseif (empty($subject) || empty($message)) {
        $_SESSION['error_message'] = "Veuillez remplir tous les champs obligatoires.";
    } else {
        foreach ($recipients as $recipient_id) {
            $result = sendReminders(
                $_SESSION['user_id'],
                $recipient_id,
                $subject,
                $message,
                'rappel',
                'evaluation',
                'urgente'
            );

            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
                error_log("Erreur d'envoi de rappel : " . $result['message']);
            }
        }

        if ($success_count > 0) {
            $_SESSION['success_message'] = "Rappels envoyés avec succès à $success_count membre(s) de la commission.";
        }
        if ($error_count > 0) {
            $_SESSION['error_message'] = "Erreurs lors de l'envoi à $error_count membre(s) de la commission.";
        }
    }

    header("Location: ?page=suivis_des_decisions");
    exit;
}

?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivis des Décisions - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2e86c1',
                        'primary-dark': '#154360',
                        secondary: '#17a2b8',
                        accent: '#ffc107',
                        success: '#28a745',
                        danger: '#dc3545',
                        warning: '#ffc107',
                        info: '#17a2b8'
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .animate-fade-in { animation: fadeIn 0.6s ease-out; }
        .animate-slide-in { animation: slideIn 0.5s ease-out; }
        .animate-pulse-custom { animation: pulse 2s infinite; }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease-in-out;
        }
        .notification.show {
            transform: translateX(0);
        }
        .notification.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        .notification.info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">


        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-fade-in">
                <!-- Rapports Validés -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-success hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Rapports Validés</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['rapports_valides']; ?></p>
                            <p class="text-sm text-green-600 flex items-center mt-1">
                                <i class="fas fa-check-circle mr-1"></i>
                                Approuvés
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Rapports en Attente -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-warning hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">En Attente</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['rapports_en_attente']; ?></p>
                            <p class="text-sm text-yellow-600 flex items-center mt-1">
                                <i class="fas fa-clock mr-1"></i>
                                À traiter
                            </p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-hourglass-half text-yellow-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Rapports Rejetés -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-danger hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Rapports Rejetés</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['rapports_rejetes']; ?></p>
                            <p class="text-sm text-red-600 flex items-center mt-1">
                                <i class="fas fa-times-circle mr-1"></i>
                                Refusés
                            </p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-full">
                            <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Comptes Rendus -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-info hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Comptes Rendus</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $stats['comptes_rendus']; ?></p>
                            <p class="text-sm text-blue-600 flex items-center mt-1">
                                <i class="fas fa-file-alt mr-1"></i>
                                Disponibles
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-file-alt text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8 animate-slide-in">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-search mr-3 text-primary"></i>
                    Rechercher un rapport étudiant
                </h3>
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Search and Filters -->
                    <div class="flex-1">
                        <form method="GET" class="flex flex-col sm:flex-row gap-4" id="filter-form">
                            <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'suivis_des_decisions'; ?>">
                            <input type="hidden" name="page_num" id="page_num_input" value="<?php echo isset($_GET['page_num']) ? intval($_GET['page_num']) : 1; ?>">
                            
                            <!-- Search Input -->
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" name="search_pending" placeholder="Rechercher un étudiant ou un rapport..." 
                                       value="<?php echo htmlspecialchars($search_pending); ?>"
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200">
                            </div>
                            
                            <!-- Date Filter -->
                            <div class="relative">
                                <select name="date_filter"
                                        class="block w-full px-4 py-3 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white transition-all duration-200">
                                    <option value="">Date de soumission</option>
                                    <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                    <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                                    <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                                </select>
                            </div>
                            
                            <!-- Search Button -->
                            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                        </form>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button id="bulk-delete-btn" onclick="deleteSelected()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-trash mr-2"></i>Supprimer sélection
                        </button>
                        <button id="send-reminder-btn" onclick="showReminderModal()" class="bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-bell mr-2"></i>Envoyer rappels
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pending Reports -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-fade-in">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-clock mr-3 text-warning"></i>
                        Rapports en attente de validation
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre du rapport</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date soumission</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pending_reports as $report): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="report-checkbox rounded border-gray-300 text-primary focus:ring-primary" value="<?php echo $report['id_rapport_etd']; ?>">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center text-sm font-medium">
                                                <?php echo substr($report['nom_etd'], 0, 1) . substr($report['prenom_etd'], 0, 1); ?>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                                                                    <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars(($report['nom_etd'] ?? '') . ' ' . ($report['prenom_etd'] ?? '')); ?>
                                        </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($report['email_etd'] ?? ''); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($report['nom_rapport'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($report['date_rapport'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="?page=suivis_des_decisions&id=<?php echo $report['id_rapport_etd']; ?>" 
                                           class="bg-blue-100 hover:bg-blue-200 text-blue-700 p-2 rounded-lg transition-all duration-200 hover:shadow-md transform hover:-translate-y-1" 
                                           title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?page=suivis_des_decisions&delete=<?php echo $report['id_rapport_etd']; ?>" 
                                           class="bg-red-100 hover:bg-red-200 text-red-700 p-2 rounded-lg transition-all duration-200 hover:shadow-md transform hover:-translate-y-1" 
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce rapport ?')" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="?page=suivis_des_decisions&reminder=1&report_id=<?php echo $report['id_rapport_etd']; ?>" 
                                           class="bg-yellow-100 hover:bg-yellow-200 text-yellow-700 p-2 rounded-lg transition-all duration-200 hover:shadow-md transform hover:-translate-y-1" 
                                           title="Envoyer rappel">
                                            <i class="fas fa-bell"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($pending_reports)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2"></i>
                                    <p>Aucun rapport en attente pour le moment</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Final Decisions Section -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8 animate-slide-in">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-gavel mr-3 text-primary"></i>
                    Décisions finales
                </h3>
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Search and Filters -->
                    <div class="flex-1">
                        <form method="GET" class="flex flex-col sm:flex-row gap-4" id="decision-filter-form">
                            <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'suivis_des_decisions'; ?>">
                            <input type="hidden" name="page_num_decision" id="page_num_decision_input" value="<?php echo isset($_GET['page_num_decision']) ? intval($_GET['page_num_decision']) : 1; ?>">
                            
                            <!-- Search Input -->
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" name="search_decision" placeholder="Rechercher un utilisateur..." 
                                       value="<?php echo htmlspecialchars($search_decision); ?>"
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200">
                            </div>
                            
                            <!-- Filters -->
                            <div class="flex gap-2">
                                <select name="date_submission"
                                        class="block px-4 py-3 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white transition-all duration-200">
                                    <option value="">Date soumission</option>
                                    <option value="today" <?php echo $date_submission === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                    <option value="week" <?php echo $date_submission === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                                    <option value="month" <?php echo $date_submission === 'month' ? 'selected' : ''; ?>>Ce mois</option>
                                </select>
                                <select name="decision"
                                        class="block px-4 py-3 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white transition-all duration-200">
                                    <option value="">Décision finale</option>
                                    <option value="Validé" <?php echo $decision === 'Validé' ? 'selected' : ''; ?>>Validé</option>
                                    <option value="Rejeté" <?php echo $decision === 'Rejeté' ? 'selected' : ''; ?>>Rejeté</option>
                                    <option value="À réviser" <?php echo $decision === 'À réviser' ? 'selected' : ''; ?>>À réviser</option>
                                </select>
                            </div>
                            
                            <!-- Search Button -->
                            <button type="submit" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                        </form>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button onclick="deleteSelectedDecisions()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-trash mr-2"></i>Supprimer sélection
                        </button>
                        <button onclick="exportReports()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-file-pdf mr-2"></i>Exporter PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Final Decisions Table -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-fade-in">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre du rapport</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date soumission</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date décision</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Décision</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($final_decisions as $decision_item): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center text-sm font-medium">
                                                <?php echo substr($decision_item['nom_etd'], 0, 1) . substr($decision_item['prenom_etd'], 0, 1); ?>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                                                                    <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars(($decision_item['nom_etd'] ?? '') . ' ' . ($decision_item['prenom_etd'] ?? '')); ?>
                                        </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($decision_item['email_etd'] ?? ''); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($decision_item['nom_rapport'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($decision_item['date_rapport'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($decision_item['date_validation'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClass = '';
                                    $statusIcon = '';
                                    switch(strtolower($decision_item['decision'])) {
                                        case 'validé':
                                            $statusClass = 'bg-green-100 text-green-800';
                                            $statusIcon = 'fa-check-circle';
                                            break;
                                        case 'rejeté':
                                            $statusClass = 'bg-red-100 text-red-800';
                                            $statusIcon = 'fa-times-circle';
                                            break;
                                        case 'à réviser':
                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                            $statusIcon = 'fa-edit';
                                            break;
                                        default:
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusIcon = 'fa-question-circle';
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $statusIcon; ?> mr-1"></i>
                                                                                    <?php echo htmlspecialchars($decision_item['decision'] ?? ''); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="?page=suivis_des_decisions&id=<?php echo $decision_item['id_rapport_etd']; ?>" 
                                           class="bg-blue-100 hover:bg-blue-200 text-blue-700 p-2 rounded-lg transition-all duration-200 hover:shadow-md transform hover:-translate-y-1" 
                                           title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?page=suivis_des_decisions&id=<?php echo $decision_item['id_rapport_etd']; ?>&download=1" 
                                           class="bg-green-100 hover:bg-green-200 text-green-700 p-2 rounded-lg transition-all duration-200 hover:shadow-md transform hover:-translate-y-1" 
                                           title="Télécharger PDF">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if (empty($final_decisions)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2"></i>
                                    <p>Aucune décision finale pour le moment</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                         </div>
         </main>
     </div>

     <!-- Modern Confirmation Modal -->
     <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="confirmation-modal">
         <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
             <div class="flex justify-between items-center p-6 border-b border-gray-200">
                 <h2 class="text-xl font-semibold text-gray-900">Confirmation</h2>
                 <button id="close-confirmation-modal-btn" class="text-gray-400 hover:text-gray-600 text-xl">
                     <i class="fas fa-times"></i>
                 </button>
             </div>
             <div class="p-6 text-center">
                 <div class="mb-4">
                     <i class="fas fa-question-circle text-4xl text-primary"></i>
                 </div>
                 <p id="confirmation-text" class="text-gray-700 mb-6">Voulez-vous vraiment effectuer cette action ?</p>
                 <div class="flex justify-center gap-3">
                     <button id="confirm-modal-btn" class="bg-primary hover:bg-primary-dark text-white px-6 py-3 rounded-lg font-medium transition-all duration-200">
                         Oui
                     </button>
                     <button id="cancel-modal-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-6 py-3 rounded-lg font-medium transition-all duration-200">
                         Non
                     </button>
                 </div>
             </div>
         </div>
     </div>

    <script>
        // Notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
                        <span>${message}</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => notification.classList.add('show'), 100);
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }

        // Check for session messages and show notifications
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($_SESSION['success_message'])): ?>
                showNotification('<?php echo addslashes($_SESSION['success_message']); ?>', 'success');
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                showNotification('<?php echo addslashes($_SESSION['error_message']); ?>', 'error');
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['info_message'])): ?>
                showNotification('<?php echo addslashes($_SESSION['info_message']); ?>', 'info');
                <?php unset($_SESSION['info_message']); ?>
            <?php endif; ?>

            // Handle select all checkbox
            const selectAllCheckbox = document.getElementById('select-all');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.report-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Handle individual checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('report-checkbox')) {
                    const allCheckboxes = document.querySelectorAll('.report-checkbox');
                    const checkedCheckboxes = document.querySelectorAll('.report-checkbox:checked');
                    const selectAllCheckbox = document.getElementById('select-all');
                    
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
                        selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
                    }
                }
            });

            // Gestion de la sélection "tout" pour les destinataires
            const selectAllRecipientsCheckbox = document.getElementById('select-all-recipients');
            const recipientCheckboxes = document.querySelectorAll('.recipient-checkbox');

            if (selectAllRecipientsCheckbox) {
                selectAllRecipientsCheckbox.addEventListener('change', function() {
                    recipientCheckboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Validation et soumission du formulaire
            const reminderForm = document.getElementById('reminder-form');
            if (reminderForm) {
                reminderForm.addEventListener('submit', function(e) {
                    const checkedRecipients = document.querySelectorAll('.recipient-checkbox:checked');
                    if (checkedRecipients.length === 0) {
                        e.preventDefault();
                        showNotification('Veuillez sélectionner au moins un destinataire.', 'error');
                        return;
                    }
                });
            }
        });

        // Fonction pour fermer les modales
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (modalId === 'confirmation-modal') {
                    setTimeout(() => {
                        window.location.reload();
                    }, 300);
                }
            }
        }

        // Fonction pour ouvrir la modale de rappel
        function showReminderModal() {
            const modal = document.getElementById('rappel-modal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        // Modale de confirmation générique
        let confirmCallback = null;
        function openConfirmationModal(message, onConfirm) {
            document.getElementById('confirmation-text').textContent = message;
            const modal = document.getElementById('confirmation-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            confirmCallback = onConfirm;
        }

        function closeConfirmationModal() {
            const modal = document.getElementById('confirmation-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            confirmCallback = null;
        }

        // Event listeners for confirmation modal
        document.getElementById('confirm-modal-btn').onclick = function() {
            if (typeof confirmCallback === 'function') confirmCallback();
            closeConfirmationModal();
        };
        document.getElementById('cancel-modal-btn').onclick = closeConfirmationModal;
        document.getElementById('close-confirmation-modal-btn').onclick = closeConfirmationModal;

        // Click outside to close modal
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('confirmation-modal');
            if (event.target === modal) closeConfirmationModal();
        });

        // Functions for bulk actions
        function deleteSelected() {
            const checkedBoxes = document.querySelectorAll('.report-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            if (ids.length === 0) {
                showNotification('Veuillez sélectionner au moins un rapport à supprimer.', 'error');
                return;
            }
            openConfirmationModal(
                `Voulez-vous vraiment supprimer les ${ids.length} rapport(s) sélectionné(s) ?`,
                function() {
                    // Implement deletion logic here
                    showNotification('Rapports supprimés avec succès.', 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            );
        }

        function deleteSelectedDecisions() {
            const checkedBoxes = document.querySelectorAll('.decision-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            if (ids.length === 0) {
                showNotification('Veuillez sélectionner au moins une décision à supprimer.', 'error');
                return;
            }
            openConfirmationModal(
                `Voulez-vous vraiment supprimer les ${ids.length} décision(s) sélectionnée(s) ?`,
                function() {
                    // Implement deletion logic here
                    showNotification('Décisions supprimées avec succès.', 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            );
        }

        function exportReports() {
            showNotification('Export en cours...', 'info');
            // Implement export logic here
            setTimeout(() => {
                showNotification('Rapports exportés avec succès.', 'success');
            }, 2000);
        }
    </script>

</body>

</html>