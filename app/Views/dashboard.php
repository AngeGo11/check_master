<?php
// Récupération des statistiques générales
$stats = [
    'total_etudiants' => 0,
    'etudiants_eligibles' => 0,
    'rapports_soumis' => 0,
    'rapports_valides' => 0,
    'demandes_soutenance' => 0,
    'demandes_traitees' => 0,
    'total_enseignants' => 0,
    'enseignants_commission' => 0
];

//Récupération de la photo de l'utilisateur
$profilePhoto = ''; // Image par défaut
$sql = "SELECT 
        u.id_utilisateur,
        u.login_utilisateur,
        ens.photo_ens as photo_profil_ens,    
        pa.photo_personnel_adm as photo_profil_pa
        FROM utilisateur u
        LEFT JOIN enseignants ens ON ens.email_ens = u.login_utilisateur
        LEFT JOIN personnel_administratif pa ON pa.email_personnel_adm = u.login_utilisateur
        WHERE u.id_utilisateur = ?";

$recupUser = $pdo->prepare($sql);
$recupUser->execute([$_SESSION['user_id']]);
$userData = $recupUser->fetch(PDO::FETCH_ASSOC);

$userType = $_SESSION['lib_user_type'];

// Déterminer la photo de profil correcte en fonction du type d'utilisateur pour l'affichage
if (explode(" ", $userType)[0] === 'Enseignant') {
    $profilePhoto = $userData['photo_profil_ens'] ?? $profilePhoto;
} elseif ($userType === 'Personnel administratif') {
    $profilePhoto = $userData['photo_profil_pa'] ?? $profilePhoto;
}

// Total étudiants et éligibles
$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN statut_eligibilite = 'Éligible' THEN 1 ELSE 0 END) as eligibles
    FROM etudiants";
$result = $pdo->query($query);
if ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $stats['total_etudiants'] = $row['total'];
    $stats['etudiants_eligibles'] = $row['eligibles'];
}

//Récupération des rapports en attente d'examen
$query = $pdo->prepare("SELECT * FROM partage_rapport pr
                LEFT JOIN rapport_etudiant re ON re.id_rapport_etd = pr.id_rapport_etd
                LEFT JOIN etudiants e ON e.num_etd = re.num_etd
                WHERE re.statut_rapport = 'En attente de validation'
                ORDER BY pr.date_partage ASC
                LIMIT 5");
$query->execute();
$rapports = $query->fetchAll( PDO::FETCH_ASSOC);

//Récupération de l'année académique en cours
$query = "SELECT date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours' ";
$result = $pdo->query($query)->fetch(PDO::FETCH_ASSOC);
$annee_debut = date("Y", strtotime($result["date_debut"]));
$annee_fin = date("Y", strtotime($result["date_fin"]));
$annee_en_cours = $annee_debut . '-' . $annee_fin;

// Rapports soumis et validés
$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN statut_rapport = 'Validé' THEN 1 ELSE 0 END) as valides
    FROM rapport_etudiant";
$result = $pdo->query($query);
if ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $stats['rapports_soumis'] = $row['total'];
    $stats['rapports_valides'] = $row['valides'];
}

// Demandes de soutenance
$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN statut_demande = 'Traitée' THEN 1 ELSE 0 END) as traitees
    FROM demande_soutenance";
$result = $pdo->query($query);
if ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $stats['demandes_soutenance'] = $row['total'];
    $stats['demandes_traitees'] = $row['traitees'];
}

// Total enseignants et en commission
$query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN id_ens IN (SELECT id_ens FROM posseder WHERE id_gu = 9) THEN 1 ELSE 0 END) as commission
    FROM enseignants";
$result = $pdo->query($query);
if ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $stats['total_enseignants'] = $row['total'];
    $stats['enseignants_commission'] = $row['commission'];
}

// Dernier rapport examiné
$query = "SELECT r.*, v.date_validation, e.nom_etd, e.prenom_etd, e.id_promotion, p.*, e.id_niv_etd, n.lib_niv_etd
    FROM rapport_etudiant r
    JOIN etudiants e ON r.num_etd = e.num_etd
    JOIN promotion p ON p.id_promotion = e.id_promotion
    JOIN compte_rendu cr ON cr.id_rapport_etd = r.id_rapport_etd
    JOIN valider v ON v.id_rapport_etd =  r.id_rapport_etd
    JOIN enseignants ens ON ens.id_ens = v.id_ens
    LEFT JOIN niveau_etude n ON e.id_niv_etd = n.id_niv_etd
    ORDER BY v.date_validation DESC
    LIMIT 1";
$result = $pdo->query($query);
$dernier_rapport = $result->fetch(PDO::FETCH_ASSOC);

// Membres de la commission
$query = "SELECT e.*, f.nom_fonction
    FROM enseignants e
    JOIN posseder p ON e.id_ens = p.id_util
    JOIN occuper o ON e.id_ens = o.id_ens
    JOIN fonction f ON o.id_fonction = f.id_fonction
    WHERE p.id_gu = 9
    ORDER BY e.id_ens
    LIMIT 3";
$result = $pdo->query($query);
$membres_commission = [];
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $membres_commission[] = $row;
}
$query = "
    SELECT DISTINCT gu.lib_gu, COUNT(DISTINCT p.id_util) AS count
    FROM groupe_utilisateur gu
    LEFT JOIN posseder p ON gu.id_gu = p.id_gu
    GROUP BY gu.lib_gu, gu.id_gu
    ORDER BY gu.id_gu
";
$result = $pdo->query($query);
$groupes = [];
$total_groupes = 0;
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $groupes[] = $row;
    $total_groupes += $row['count'];
}

foreach ($groupes as &$groupe) {
    $groupe['percentage'] = $total_groupes > 0 ? round(($groupe['count'] / $total_groupes) * 100, 1) : 0;
}

// Données pour le graphique de l'évolution des étudiants par promotion et niveau
$query_promo_niveau = "
    SELECT 
        p.lib_promotion, 
        ne.lib_niv_etd, 
        COUNT(e.num_etd) as student_count 
    FROM etudiants e
    JOIN promotion p ON e.id_promotion = p.id_promotion
    JOIN niveau_etude ne ON e.id_niv_etd = ne.id_niv_etd
    GROUP BY p.lib_promotion, ne.lib_niv_etd
    ORDER BY p.lib_promotion, ne.lib_niv_etd;
";
$result_promo_niveau = $pdo->query($query_promo_niveau);
$promo_niveau_data = [];
while($row = $result_promo_niveau->fetch(PDO::FETCH_ASSOC)) {
    $promo_niveau_data[] = $row;
}

// Préparer les données pour Chart.js
$promotions = [];
$niveaux = [];
$datasets_promo_niveau = [];

foreach ($promo_niveau_data as $data) {
    if (!in_array($data['lib_promotion'], $promotions)) {
        $promotions[] = $data['lib_promotion'];
    }
    if (!in_array($data['lib_niv_etd'], $niveaux)) {
        $niveaux[] = $data['lib_niv_etd'];
    }
}

sort($promotions);
sort($niveaux);

$colors = ['#1a5276', '#2980b9', '#3498db', '#4caf50', '#ff8c00'];
$color_index = 0;

$datasets_promo_niveau = [];
foreach ($niveaux as $niveau) {
    $data = [];
    foreach ($promotions as $promotion) {
        $count = 0;
        foreach ($promo_niveau_data as $row) {
            if ($row['lib_promotion'] === $promotion && $row['lib_niv_etd'] === $niveau) {
                $count = (int)$row['student_count'];
                break;
            }
        }
        $data[] = $count;
    }
    $datasets_promo_niveau[] = [
        'label' => $niveau,
        'data' => $data,
        'backgroundColor' => $colors[$color_index % count($colors)],
        'borderRadius' => 4,
        'barThickness' => 16
    ];
    $color_index++;
}

$chart_data_promo_niveau = [
    'labels' => $promotions,
    'datasets' => $datasets_promo_niveau
];

// Données pour le graphique linéaire des demandes de soutenance
$query_soutenances = "
    SELECT
        DATE_FORMAT(date_demande, '%Y-%m') as mois,
        SUM(CASE WHEN statut_demande = 'Traitée' THEN 1 ELSE 0 END) as traitees,
        SUM(CASE WHEN statut_demande = 'En attente' THEN 1 ELSE 0 END) as en_attente
    FROM demande_soutenance
    WHERE date_demande >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mois
    ORDER BY mois;
";
$result_soutenances = $pdo->query($query_soutenances);
$soutenance_data = $result_soutenances->fetchAll(PDO::FETCH_ASSOC);

$line_chart_labels = [];
$line_chart_data_traitees = [];
$line_chart_data_en_attente = [];

for ($i = 11; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime("-$i months"));
    $line_chart_labels[] = $month_label;
    $line_chart_data_traitees[$month_key] = 0;
    $line_chart_data_en_attente[$month_key] = 0;
}

foreach ($soutenance_data as $data) {
    if (isset($line_chart_data_traitees[$data['mois']])) {
        $line_chart_data_traitees[$data['mois']] = (int)$data['traitees'];
        $line_chart_data_en_attente[$data['mois']] = (int)$data['en_attente'];
    }
}

$line_chart_final_data = [
    'labels' => $line_chart_labels,
    'datasets' => [
        [
            'label' => 'Traitées',
            'data' => array_values($line_chart_data_traitees),
            'borderColor' => '#1a5276',
            'backgroundColor' => '#1a5276',
            'tension' => 0.1,
        ],
        [
            'label' => 'En attente',
            'data' => array_values($line_chart_data_en_attente),
            'borderColor' => '#ff8c00',
            'backgroundColor' => '#ff8c00',
            'tension' => 0.1,
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',        // Bleu de la sidebar
                        'primary-light': '#2980b9', // Bleu plus clair
                        'primary-lighter': '#3498db', // Encore plus clair
                        secondary: '#ff8c00',      // Orange de l'app
                        accent: '#4caf50',         // Vert de l'app
                        success: '#4caf50',        // Vert
                        warning: '#f39c12',        // Jaune/Orange
                        danger: '#e74c3c',         // Rouge
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.3s ease-out',
                        'bounce-in': 'bounceIn 0.6s ease-out',
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
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(26, 82, 118, 0.1), 0 10px 10px -5px rgba(26, 82, 118, 0.04);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
        }
        .gradient-card {
            background: linear-gradient(135deg, #3498db 0%, #1a5276 100%);
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">


        <!-- Contenu principal -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Message de bienvenue -->
            <div class="mb-8 animate-fade-in">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">
                    Tableau de bord 📊
                </h2>
                <p class="text-gray-600">Bienvenue, <?= explode(' ', $_SESSION['user_fullname'])[0] ?? 'Utilisateur' ?> ! Voici un aperçu de l'activité du système.</p>
            </div>

            <!-- Cartes de statistiques principales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Étudiants inscrits -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up">
                    <div class="flex items-center justify-between">
                                                 <div>
                             <p class="text-2xl font-bold text-primary-light"><?= number_format($stats['total_etudiants']) ?></p>
                             <p class="text-sm font-medium text-gray-600 mt-1">Étudiants inscrits</p>
                             <div class="flex items-center mt-2">
                                 <span class="text-xs px-2 py-1 bg-primary-light/10 text-primary-light rounded-full">
                                     <i class="fas fa-arrow-up text-xs mr-1"></i>
                                     <?= $stats['etudiants_eligibles'] ?> éligibles
                                 </span>
                             </div>
                         </div>
                         <div class="bg-primary-light/10 rounded-full p-4">
                             <i class="fas fa-user-graduate text-2xl text-primary-light"></i>
                         </div>
                    </div>
                </div>

                <!-- Rapports soumis -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.1s">
                    <div class="flex items-center justify-between">
                                                 <div>
                             <p class="text-2xl font-bold text-primary-light"><?= number_format($stats['rapports_soumis']) ?></p>
                             <p class="text-sm font-medium text-gray-600 mt-1">Rapports soumis</p>
                             <div class="flex items-center mt-2">
                                 <span class="text-xs px-2 py-1 bg-primary-light/10 text-primary-light rounded-full">
                                     <i class="fas fa-check-double text-xs mr-1"></i>
                                     <?= $stats['rapports_valides'] ?> validés
                                 </span>
                             </div>
                         </div>
                         <div class="bg-primary-light/10 rounded-full p-4">
                             <i class="fas fa-file-alt text-2xl text-primary-light"></i>
                         </div>
                    </div>
                </div>

                <!-- Demandes de soutenance -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.2s">
                    <div class="flex items-center justify-between">
                                                 <div>
                             <p class="text-2xl font-bold text-primary-light"><?= number_format($stats['demandes_soutenance']) ?></p>
                             <p class="text-sm font-medium text-gray-600 mt-1">Demandes soutenance</p>
                             <div class="flex items-center mt-2">
                                 <span class="text-xs px-2 py-1 bg-primary-light/10 text-primary-light rounded-full">
                                     <i class="fas fa-check text-xs mr-1"></i>
                                     <?= $stats['demandes_traitees'] ?> traitées
                                 </span>
                             </div>
                         </div>
                         <div class="bg-primary-light/10 rounded-full p-4">
                             <i class="fas fa-calendar-check text-2xl text-primary-light"></i>
                         </div>
                    </div>
                </div>

                <!-- Enseignants -->
                <div class="stat-card bg-white rounded-2xl shadow-lg p-6 border-l-4 border-primary-light transform transition-all duration-300 hover:scale-105 animate-slide-up" style="animation-delay: 0.3s">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-2xl font-bold text-primary-light"><?= number_format($stats['total_enseignants']) ?></p>
                            <p class="text-sm font-medium text-gray-600 mt-1">Enseignants</p>
                            <div class="flex items-center mt-2">
                                <span class="text-xs px-2 py-1 bg-primary/10 text-primary rounded-full">
                                    <i class="fas fa-star text-xs mr-1"></i>
                                    <?= $stats['enseignants_commission'] ?> en commission
                                </span>
                            </div>
                        </div>
                        <div class="bg-primary-light/10 rounded-full p-4">
                            <i class="fas fa-chalkboard-teacher text-2xl text-primary-light"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grille principale des graphiques -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
                <!-- Graphique principal -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 flex items-center">
                                    <div class="bg-primary/10 rounded-lg p-2 mr-3">
                                        <i class="fas fa-chart-line text-primary"></i>
                                    </div>
                                    Évolution des étudiants
                                </h3>
                                <p class="text-gray-600 text-sm mt-1">Par promotion et niveau d'étude</p>
                            </div>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 text-xs bg-primary/10 text-primary rounded-full font-medium">Mensuel</button>
                                <button class="px-3 py-1 text-xs bg-gray-100 text-gray-600 rounded-full font-medium">Annuel</button>
                            </div>
                        </div>
                        <div class="h-80">
                            <canvas id="promotionChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Répartition des utilisateurs -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in">
                    <div class="flex items-center mb-6">
                        <div class="bg-primary-light/10 rounded-lg p-2 mr-3">
                            <i class="fas fa-chart-pie text-primary-light"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Répartition</h3>
                            <p class="text-gray-600 text-sm">Types d'utilisateurs</p>
                        </div>
                    </div>
                    
                    <div class="h-48 mb-6">
                        <canvas id="usersChart"></canvas>
                    </div>
                    
                    <div class="space-y-3">
                        <?php foreach ($groupes as $index => $groupe): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full mr-3" style="background-color: <?= ['#1a5276', '#4caf50', '#ff8c00', '#e74c3c'][$index % 4] ?>"></div>
                                    <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($groupe['lib_gu']) ?></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-sm font-bold text-gray-900"><?= $groupe['count'] ?></span>
                                    <span class="text-xs text-gray-500 ml-1">(<?= $groupe['percentage'] ?>%)</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Graphique des demandes de soutenance -->
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8 animate-fade-in">
                <div class="flex items-center mb-6">
                    <div class="bg-accent/10 rounded-lg p-2 mr-3">
                        <i class="fas fa-chart-area text-accent"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Suivi des demandes de soutenance</h3>
                        <p class="text-gray-600 text-sm">Évolution sur les 12 derniers mois</p>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="soutenanceChart"></canvas>
                </div>
            </div>

            <!-- Section des informations supplémentaires -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Dernier rapport examiné -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in">
                    <div class="flex items-center mb-4">
                        <div class="bg-accent/10 rounded-lg p-2 mr-3">
                            <i class="fas fa-file-check text-accent"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Dernier rapport</h3>
                    </div>
                    
                    <?php if ($dernier_rapport): ?>
                        <div class="space-y-4">
                            <div class="flex items-start space-x-3 p-4 bg-gray-50 rounded-xl">
                                <div class="w-12 h-12 bg-gradient-to-br from-accent to-green-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                    <?= substr($dernier_rapport['nom_etd'], 0, 1) . substr($dernier_rapport['prenom_etd'], 0, 1) ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">
                                        <?= htmlspecialchars($dernier_rapport['nom_etd'] . ' ' . $dernier_rapport['prenom_etd']) ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <?= htmlspecialchars($dernier_rapport['lib_promotion'] ?? 'Promotion inconnue') ?>
                                        <?php if ($dernier_rapport['lib_niv_etd']): ?>
                                            - <?= htmlspecialchars($dernier_rapport['lib_niv_etd']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <div class="flex items-center mt-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent">
                                            <i class="fas fa-check-circle text-xs mr-1"></i>
                                            Validé
                                        </span>
                                        <span class="text-xs text-gray-500 ml-2">
                                            <?= date('d/m/Y', strtotime($dernier_rapport['date_validation'])) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-file-alt text-gray-400 text-xl"></i>
                            </div>
                            <p class="text-gray-500">Aucun rapport examiné récemment</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Rapports en attente -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in">
                    <div class="flex items-center mb-4">
                        <div class="bg-secondary/10 rounded-lg p-2 mr-3">
                            <i class="fas fa-clock text-secondary"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Rapports en attente</h3>
                    </div>
                    
                    <div class="space-y-3 max-h-64 overflow-y-auto">
                        <?php if ($rapports): ?>
                            <?php foreach (array_slice($rapports, 0, 4) as $rapport): ?>
                                <div class="flex items-center space-x-3 p-3 bg-secondary/5 rounded-lg hover:bg-secondary/10 transition-colors">
                                    <div class="w-8 h-8 bg-secondary/20 rounded-full flex items-center justify-center">
                                        <i class="fas fa-file text-secondary text-xs"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <?= htmlspecialchars($rapport['nom_etd'] . ' ' . $rapport['prenom_etd']) ?>
                                        </p>
                                        <p class="text-xs text-gray-600 truncate">
                                            <?= htmlspecialchars($rapport['theme_memoire']) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-check-circle text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-gray-500 text-sm">Aucun rapport en attente</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Commission d'évaluation -->
                <div class="bg-white rounded-2xl shadow-lg p-6 animate-fade-in">
                    <div class="flex items-center mb-4">
                        <div class="bg-primary-light/10 rounded-lg p-2 mr-3">
                            <i class="fas fa-users-cog text-primary-light"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Commission</h3>
                    </div>
                    
                    <div class="space-y-3">
                        <?php if (empty($membres_commission)): ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-users text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-gray-500 text-sm">Aucun membre trouvé</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($membres_commission as $membre): ?>
                                <div class="flex items-center space-x-3 p-3 bg-primary-light/5 rounded-lg">
                                    <?php if (!empty($membre['photo_ens']) && file_exists("public/images/profiles/" . $membre['photo_ens'])): ?>
                                        <img src="/GSCV+/public/images/profiles/<?= htmlspecialchars($membre['photo_ens']) ?>" 
                                             alt="Photo" class="w-10 h-10 rounded-full object-cover">
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-gradient-to-br from-primary-light to-primary rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            <?= substr($membre['nom_ens'], 0, 1) . substr($membre['prenoms_ens'], 0, 1) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            <?= htmlspecialchars($membre['nom_ens'] . ' ' . $membre['prenoms_ens']) ?>
                                        </p>
                                        <p class="text-xs text-gray-600 truncate">
                                            <?= htmlspecialchars($membre['nom_fonction'] ?? 'Enseignant') ?>
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-primary-light/10 text-primary-light">
                                            <i class="fas fa-star text-xs mr-1"></i>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Mise à jour de l'heure
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            const timeElement = document.getElementById('current-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }

        setInterval(updateTime, 1000);
        updateTime();

        // Configuration des graphiques
        Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
        Chart.defaults.color = '#6b7280';

        // Graphique des promotions
        const promotionCtx = document.getElementById('promotionChart');
        if (promotionCtx) {
            new Chart(promotionCtx, {
                type: 'bar',
                data: <?= json_encode($chart_data_promo_niveau) ?>,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6'
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    },
                    elements: {
                        bar: {
                            borderRadius: 6
                        }
                    }
                }
            });
        }

        // Graphique des utilisateurs (doughnut)
        const usersCtx = document.getElementById('usersChart');
        if (usersCtx) {
            new Chart(usersCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_column($groupes, 'lib_gu')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($groupes, 'percentage')) ?>,
                        backgroundColor: ['#1a5276', '#4caf50', '#ff8c00', '#e74c3c'],
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Graphique des soutenances
        const soutenanceCtx = document.getElementById('soutenanceChart');
        if (soutenanceCtx) {
            new Chart(soutenanceCtx, {
                type: 'line',
                data: <?= json_encode($line_chart_final_data) ?>,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6'
                            }
                        }
                    },
                    elements: {
                        point: {
                            radius: 6,
                            hoverRadius: 8,
                            backgroundColor: '#ffffff',
                            borderWidth: 3
                        },
                        line: {
                            tension: 0.4,
                            borderWidth: 3
                        }
                    }
                }
            });
        }

        // Animation des cartes au chargement
        window.addEventListener('load', function() {
            const cards = document.querySelectorAll('.stat-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.animationDelay = `${index * 0.1}s`;
                    card.classList.add('animate-bounce-in');
                }, index * 100);
            });
        });
    </script>
</body>
</html>