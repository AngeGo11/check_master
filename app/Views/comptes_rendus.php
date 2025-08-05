<?php
require_once __DIR__ . '/../../public/assets/traitements/traitements_comptes_rendus.php';
require_once __DIR__ . '/../Controllers/ConsultationController.php';
require_once __DIR__ . '/../Controllers/CompteRenduController.php';


// Initialisation des variables
$fullname = isset($_SESSION['user_fullname']) ? $_SESSION['user_fullname'] : 'Utilisateur';
$lib_user_type = isset($_SESSION['lib_user_type']) ? $_SESSION['lib_user_type'] : '';

try {
    // Récupération des statistiques
    $stats = getStatistics();

    // Récupération des comptes rendus
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';
    $comptes_rendus = getComptesRendus($search, $date_filter);
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
    $comptes_rendus = [];
}

$controller = new ConsultationController();
$compteRenduController = new CompteRenduController($pdo);

// Récupération des données via le contrôleur
$data = $controller->viewConsultations();
$comptesRendus = $compteRenduController->indexWithAuthor();

// Utiliser les données du contrôleur ConsultationController
$comptes_rendus = $data['comptes_rendus'];



// Récupération des comptes rendus existants avec les informations de l'auteur


// Groupement des comptes rendus par titre
$comptesRendusGroupes = [];
if (!empty($comptes_rendus)) {
    foreach ($comptes_rendus as $compteRendu) {
        $titre = $compteRendu['nom_cr'] ?? 'Sans titre';
        $responsable = $compteRenduController->getResponsableCr($compteRendu['id_cr']);
        if (!isset($comptesRendusGroupes[$titre])) {
            $comptesRendusGroupes[$titre] = [
                'titre' => $titre,
                'nombre_total' => 0,
                'date_creation' => $compteRendu['date_cr'] ?? 'now',
                'auteur' => $responsable, // Responsable du compte rendu
                'rapports' => []
            ];
        }
        $comptesRendusGroupes[$titre]['nombre_total']++;
        $comptesRendusGroupes[$titre]['rapports'][] = $compteRendu;
    }
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comptes Rendus - GSCV+</title>
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
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }

            50% {
                opacity: 1;
                transform: scale(1.05);
            }

            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">


        <!-- Contenu principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-slide-up">
                <!-- Rapports validés -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-accent overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Rapports validés</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['rapports_valides']; ?></p>
                            </div>
                            <div class="bg-accent/10 rounded-full p-4">
                                <i class="fas fa-check-circle text-2xl text-accent"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapports en attente -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-warning overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Rapports en attente</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['rapports_en_attente']; ?></p>
                            </div>
                            <div class="bg-warning/10 rounded-full p-4">
                                <i class="fas fa-hourglass-half text-2xl text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rapports rejetés -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-danger overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Rapports rejetés</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['rapports_rejetes']; ?></p>
                            </div>
                            <div class="bg-danger/10 rounded-full p-4">
                                <i class="fas fa-times-circle text-2xl text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comptes rendus disponibles -->
                <div class="bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Comptes rendus disponibles</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo $stats['comptes_rendus']; ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-file-alt text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des comptes rendus -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                <div class="border-l-4 border-primary bg-white rounded-r-lg shadow-sm p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-list text-primary mr-3"></i>
                        Liste des comptes rendus des Commissions
                    </h2>
                    <p class="text-gray-600">
                        Gestion et consultation des comptes rendus
                    </p>
                </div>

                <!-- Filtres et actions -->
                <div class="p-6 border-b border-gray-200">
                    <form method="get" class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">

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
                                        placeholder="Rechercher un étudiant ou un compte rendu..."
                                        value="<?php echo htmlspecialchars($search); ?>">
                                </div>

                                <!-- Filtre date -->
                                <select id="date-filter"
                                    name="date_filter"
                                    class="px-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                    onchange="applyFilters()">
                                    <option value="">Date de création du compte rendu</option>
                                    <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                    <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>Cette semaine</option>
                                    <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>Ce mois</option>
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

                <!-- Table des comptes rendus -->
                <!-- Tableau des comptes rendus -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">Titre du compte rendu</th>
                                <th scope="col" class="px-6 py-3">Auteur</th>
                                <th scope="col" class="px-6 py-3">Date de création</th>
                                <th scope="col" class="px-6 py-3">Rapports associés</th>
                                <th scope="col" class="px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($comptesRendusGroupes)) : ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        <i class="fas fa-inbox text-2xl mb-2 opacity-50"></i>
                                        <p>Aucun compte rendu créé pour le moment</p>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach (array_slice($comptesRendusGroupes, 0, 5) as $groupe) : ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-file-signature text-blue-600"></i>
                                                <span><?php echo htmlspecialchars($groupe['titre'] ?? ''); ?></span>
                                                <?php if ($groupe['nombre_total'] > 1) : ?>
                                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                                        <?php echo $groupe['nombre_total']; ?> versions
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-user-edit text-green-600"></i>
                                                <span class="font-medium text-gray-700"><?php echo htmlspecialchars($groupe['auteur']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-calendar-alt text-purple-600"></i>
                                                <span><?php echo date('d/m/Y H:i', strtotime($groupe['date_creation'])); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <i class="fas fa-file-alt text-orange-600"></i>
                                                <span class="text-sm font-medium text-gray-700">
                                                    <?php
                                                    // Compter le nombre total de rapports associés à ce titre
                                                    $totalRapports = 0;
                                                    foreach ($groupe['rapports'] as $rapport) {
                                                        $totalRapports += $rapport['nombre_rapports'] ?? 1;
                                                    }
                                                    echo $totalRapports . ' rapport(s)';
                                                    ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center space-x-2">
                                                <!-- Bouton pour voir tous les comptes rendus de ce titre -->
                                                <button type="button"
                                                    onclick="showCompteRenduDetails('<?php echo htmlspecialchars($groupe['titre']); ?>')"
                                                    class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors"
                                                    title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </button>

                                                <!-- Bouton pour télécharger le dernier compte rendu -->
                                                <?php
                                                $dernierCompteRendu = end($groupe['rapports']);
                                                if ($dernierCompteRendu) :
                                                ?>
                                                    <a href="?page=consultations&action=download_cr&id=<?php echo $dernierCompteRendu['id_cr']; ?>"
                                                        class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition-colors"
                                                        title="Télécharger le dernier">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Bouton pour supprimer le groupe -->
                                                <button type="button"
                                                    onclick="deleteCompteRenduGroup('<?php echo htmlspecialchars($groupe['titre']); ?>')"
                                                    class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors"
                                                    title="Supprimer le groupe">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination simple -->
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-center">
                        <div class="flex space-x-2">
                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <span class="px-3 py-2 text-sm font-medium bg-primary text-white rounded-md">1</span>
                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">2</button>
                            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal détail décision -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="decision-modal">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 h-5/6 overflow-y-auto animate-bounce-in">
            <div class="sticky top-0 bg-white border-b p-6 rounded-t-xl">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900">Détails de la décision</h2>
                    <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Étudiant</label>
                        <p class="text-sm text-gray-900" id="modal-student">Amadou Diallo (amadou.diallo@example.com)</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Titre du rapport</label>
                        <p class="text-sm text-gray-900" id="modal-title">Système de gestion RH</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Filière</label>
                        <p class="text-sm text-gray-900" id="modal-department">Master 2 Informatique</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Évaluateur principal</label>
                        <p class="text-sm text-gray-900" id="modal-evaluator">M. DIARRA</p>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-500">Décision finale</label>
                    <div id="modal-decision">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Validé</span>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-500">Commentaire détaillé</label>
                    <div class="mt-1 p-3 bg-gray-50 rounded-lg text-sm text-gray-900" id="modal-comment">
                        Le rapport présente une analyse complète et bien structurée du système de gestion des ressources humaines.
                        Les méthodologies utilisées sont appropriées et bien documentées.
                    </div>
                </div>

                <div class="flex gap-3">
                    <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center">
                        <i class="fas fa-download mr-2"></i> Télécharger le rapport
                    </button>
                    <button class="px-4 py-2 bg-secondary text-white rounded-lg hover:bg-orange-600 transition-colors flex items-center">
                        <i class="fas fa-file-pdf mr-2"></i> Exporter la décision
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal détail compte rendu -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="compte-rendu-modal">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 h-5/6 overflow-y-auto animate-bounce-in">
            <div class="sticky top-0 bg-white border-b p-6 rounded-t-xl">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900">Détails du compte rendu</h2>
                    <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- Contenu du modal -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Étudiant</label>
                        <p class="text-sm text-gray-900" id="modal-student"></p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Date de création</label>
                        <p class="text-sm text-gray-900" id="modal-date"></p>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-500">Contenu du compte rendu</label>
                    <div class="mt-1 p-3 bg-gray-50 rounded-lg text-sm text-gray-900" id="modal-contenu"></div>
                </div>

                <div class="flex gap-3">
                    <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center" onclick="downloadCompteRendu()">
                        <i class="fas fa-download mr-2"></i> Télécharger le compte rendu
                    </button>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Modale aperçu compte rendu -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="preview-cr-modal">
        <div class="bg-white rounded-xl shadow-xl max-w-6xl w-full mx-4 h-5/6">
            <div class="sticky top-0 bg-white border-b p-6 rounded-t-xl">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900">Aperçu du compte rendu</h2>
                    <button class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors" id="close-preview-modal-btn">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Étudiant :</label>
                        <span id="preview-student" class="text-sm text-gray-900"></span>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Rapport :</label>
                        <span id="preview-report" class="text-sm text-gray-900"></span>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500">Date de création :</label>
                        <span id="preview-date" class="text-sm text-gray-900"></span>
                    </div>
                </div>
                <div class="h-96">
                    <iframe id="preview-iframe" src="" width="100%" height="100%" frameborder="0" class="rounded-lg border"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale détails compte rendu -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="details-cr-modal">
        <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 h-5/6 overflow-y-auto animate-bounce-in">
            <div class="sticky top-0 bg-white border-b p-6 rounded-t-xl">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-900">Détails du compte rendu</h2>
                    <button class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors" id="close-details-modal-btn">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations générales</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">Étudiant :</label>
                            <span id="details-student" class="text-sm text-gray-900"></span>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Rapport :</label>
                            <span id="details-report" class="text-sm text-gray-900"></span>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Date de création :</label>
                            <span id="details-date" class="text-sm text-gray-900"></span>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-500">Enseignants évaluateurs :</label>
                            <span id="details-enseignants" class="text-sm text-gray-900"></span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                    <div class="flex gap-3">
                        <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors flex items-center" onclick="previewCompteRendu(currentCrId)">
                            <i class="fas fa-eye mr-2"></i> Aperçu
                        </button>
                        <button class="px-4 py-2 bg-secondary text-white rounded-lg hover:bg-orange-600 transition-colors flex items-center" onclick="downloadCompteRendu(currentCrId)">
                            <i class="fas fa-download mr-2"></i> Télécharger
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour appliquer la recherche
        function applySearch() {
            const search = document.getElementById('search-input').value;
            const url = new URL(window.location.href);
            url.searchParams.set('search', search);
            window.location.href = url.toString();
        }

        // Fonction pour appliquer les filtres
        function applyFilters() {
            const dateFilter = document.getElementById('date-filter').value;
            const url = new URL(window.location.href);
            url.searchParams.set('date_filter', dateFilter);
            window.location.href = url.toString();
        }

        // Variables globales pour les modales
        let currentCrId = null;
        let currentRapportId = null;

        // Gestionnaires pour fermer les modales
        document.getElementById('close-modal-btn').addEventListener('click', () => {
            document.getElementById('compte-rendu-modal').style.display = 'none';
        });

        document.getElementById('close-preview-modal-btn').addEventListener('click', () => {
            document.getElementById('preview-cr-modal').style.display = 'none';
        });

        document.getElementById('close-details-modal-btn').addEventListener('click', () => {
            document.getElementById('details-cr-modal').style.display = 'none';
        });

        // Fermer les modales si on clique en dehors
        window.addEventListener('click', (event) => {
            const previewModal = document.getElementById('preview-cr-modal');
            const detailsModal = document.getElementById('details-cr-modal');
            const compteRenduModal = document.getElementById('compte-rendu-modal');

            if (event.target === previewModal) {
                previewModal.style.display = 'none';
            }
            if (event.target === detailsModal) {
                detailsModal.style.display = 'none';
            }
            if (event.target === compteRenduModal) {
                compteRenduModal.style.display = 'none';
            }
        });

        // Gestionnaires pour la sélection/désélection de tous les comptes rendus
        document.getElementById('select-all-cr').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.cr-checkbox');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });

        // Fonction pour afficher l'aperçu d'un compte rendu
        function previewCompteRendu(crId) {
            currentCrId = crId;

            fetch(`./assets/traitements/get_cr_details.php?id=${crId}`)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        document.getElementById('preview-student').textContent = `${data.nom_etd} ${data.prenom_etd}`;
                        document.getElementById('preview-report').textContent = data.nom_rapport;
                        document.getElementById('preview-date').textContent = new Date(data.date_cr).toLocaleDateString('fr-FR');

                        // Charger le PDF dans l'iframe en utilisant le nouveau fichier d'aperçu
                        const iframe = document.getElementById('preview-iframe');
                        iframe.src = `./assets/traitements/preview_cr.php?id=${crId}`;

                        document.getElementById('preview-cr-modal').style.display = 'flex';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des détails:', error);
                    alert('Une erreur est survenue lors de la récupération des détails.');
                });
        }

        // Fonction pour afficher les détails d'un compte rendu
        function showCompteRenduDetails(titre) {
            // Créer une modal pour afficher les détails
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-gray-900">Détails du compte rendu: ${titre}</h3>
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                    <div id="compte-rendu-details-content">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                            <span class="ml-2 text-gray-600">Chargement des détails...</span>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);

            // Charger les détails via AJAX
            fetch(`ajax_consultations.php?action=getCompteRenduDetails&titre=${encodeURIComponent(titre)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderCompteRenduDetails(data.details, document.getElementById('compte-rendu-details-content'));
                    } else {
                        document.getElementById('compte-rendu-details-content').innerHTML = `
                            <div class="text-center py-8 text-red-600">
                                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                <p>Erreur: ${data.error || 'Impossible de charger les détails'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('compte-rendu-details-content').innerHTML = `
                        <div class="text-center py-8 text-red-600">
                            <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                            <p>Erreur de connexion: ${error.message}</p>
                        </div>
                    `;
                });
        }

        // Fonction pour supprimer un groupe de comptes rendus
        function deleteCompteRenduGroup(titre) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer tous les comptes rendus avec le titre "${titre}" ?`)) {
                fetch('ajax_consultations.php?action=deleteCompteRenduGroup', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ titre: titre })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Groupe de comptes rendus supprimé avec succès', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Erreur: ' + (data.error || 'Impossible de supprimer le groupe'), 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erreur de connexion: ' + error.message, 'error');
                });
            }
        }

        // Fonction pour rendre les détails d'un compte rendu
        function renderCompteRenduDetails(details, container) {
            let html = `
                <div class="space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-2">Informations générales</h4>
                        <p><strong>Titre:</strong> ${details.titre}</p>
                        <p><strong>Nombre de versions:</strong> ${details.nombre_total}</p>
                        <p><strong>Date de création:</strong> ${new Date(details.date_creation).toLocaleDateString('fr-FR')}</p>
                        <p><strong>Auteur:</strong> ${details.auteur}</p>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-semibold text-gray-900 mb-2">Rapports associés</h4>
                        <div class="space-y-2">
            `;

            details.rapports.forEach((rapport, index) => {
                html += `
                    <div class="border-l-4 border-blue-500 pl-4 py-2">
                        <p><strong>Version ${index + 1}:</strong></p>
                        <p><strong>Étudiant:</strong> ${rapport.nom_etd} ${rapport.prenom_etd}</p>
                        <p><strong>Rapport:</strong> ${rapport.nom_rapport || 'N/A'}</p>
                        <p><strong>Date:</strong> ${rapport.date_cr ? new Date(rapport.date_cr).toLocaleDateString('fr-FR') : 'N/A'}</p>
                        <div class="flex space-x-2 mt-2">
                            <a href="?page=consultations&action=download_cr&id=${rapport.id_cr}" 
                               class="text-blue-600 hover:text-blue-800 text-sm">
                                <i class="fas fa-download mr-1"></i>Télécharger
                            </a>
                            <button onclick="deleteCompteRendu(${rapport.id_cr})" 
                                    class="text-red-600 hover:text-red-800 text-sm">
                                <i class="fas fa-trash mr-1"></i>Supprimer
                            </button>
                        </div>
                    </div>
                `;
            });

            html += `
                        </div>
                    </div>
                </div>
            `;
            container.innerHTML = html;
        }

        // Fonction pour supprimer un compte rendu individuel
        function deleteCompteRendu(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce compte rendu ?')) {
                fetch('ajax_consultations.php?action=deleteCompteRendu', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('Compte rendu supprimé avec succès', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification('Erreur: ' + (data.error || 'Impossible de supprimer le compte rendu'), 'error');
                    }
                })
                .catch(error => {
                    showNotification('Erreur de connexion: ' + error.message, 'error');
                });
            }
        }

        // Fonction pour télécharger un compte rendu
        function downloadCompteRendu(crId) {
            if (crId) {
                window.location.href = `./assets/traitements/download_compte_rendu.php?id=${crId}`;
            }
        }

        // Gestionnaires d'événements pour les boutons d'action
        document.addEventListener('DOMContentLoaded', function() {
            // Gestionnaire pour les boutons d'aperçu
            document.querySelectorAll('.preview-bilan-button').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const crId = this.getAttribute('data-id');
                    previewCompteRendu(crId);
                });
            });

            // Gestionnaire pour les boutons de détails
            document.querySelectorAll('.view-bilan-button').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const rapportId = this.getAttribute('data-id');
                    showCompteRenduDetails(rapportId);
                });
            });
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

        // Remplacement suppression multiple
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function() {
                const checkedBoxes = document.querySelectorAll('.cr-checkbox:checked');
                const crIds = Array.from(checkedBoxes).map(cb => cb.value);
                if (crIds.length === 0) {
                    openConfirmationModal('Veuillez sélectionner au moins un compte rendu à supprimer.', null);
                    return;
                }
                openConfirmationModal(
                    `Voulez-vous vraiment supprimer les ${crIds.length} comptes rendus sélectionnés ?`,
                    function() {
                        fetch('./assets/traitements/supprimer_comptes_rendus.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'cr_ids=' + JSON.stringify(crIds)
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    openConfirmationModal(data.message || 'Comptes rendus supprimés avec succès.', function() {
                                        location.reload();
                                    });
                                } else {
                                    openConfirmationModal('Une erreur est survenue : ' + (data.error || 'Erreur inconnue'), null);
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
    </script>
</body>

</html>