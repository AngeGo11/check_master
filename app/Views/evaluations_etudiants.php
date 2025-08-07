<?php
require_once __DIR__ . '/../../config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



// Récupération de l'id du personnel administratif connecté
$stmt = $pdo->prepare("SELECT id_personnel_adm FROM personnel_administratif WHERE email_personnel_adm = (
    SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?
)");
$stmt->execute([$_SESSION['user_id']]);
$personnel_id = $stmt->fetchColumn();

if (!$personnel_id) {
    die("Erreur : personnel administratif non trouvé.");
}

$_SESSION['id_personnel_adm'] = $personnel_id;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$ue = isset($_GET['ue']) ? $_GET['ue'] : '';
$statut = isset($_GET['statut']) ? $_GET['statut'] : '';
$page_num = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$limit = 10;
$offset = ($page_num - 1) * $limit;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR e.num_carte_etd LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, array_fill(0, 3, $search_param));
}
if ($ue !== '') {
    $where[] = "(ue.id_ue = ?)";
    $params[] = $ue;
}
$where_sql = count($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

$where_conditions = [];
if ($statut !== '') {
    if (strtolower($statut) === 'validé') {
        $where_conditions[] = "mg.statut_academique = 'Validé'";
    } elseif (strtolower($statut) === 'ajourné') {
        $where_conditions[] = "mg.statut_academique = 'Ajourné'";
    } elseif (strtolower($statut) === 'autorisé') {
        $where_conditions[] = "mg.statut_academique = 'Autorisé'";
    }
}

if (!empty($where_conditions)) {
    $where_sql .= (empty($where_sql) ? 'WHERE ' : ' AND ') . implode(' AND ', $where_conditions);
}

$subquery = "
    SELECT 
        e.num_etd,
        e.num_carte_etd,
        e.nom_etd,
        e.prenom_etd,
        a.date_debut,
        a.date_fin,
        ne.lib_niv_etd AS niveau,
        mg.moyenne_generale AS moyenne_annuelle,
        mg.statut_academique,
        mg.total_credits_obtenus,
        mg.total_credits_inscrits
    FROM etudiants e
            JOIN (
            -- Évaluations ECUE
            SELECT 
                CAST(ev.num_etd AS UNSIGNED) as num_etd, 
                ev.id_ac, 
                ev.note, 
                ev.id_semestre,
                ec.id_ue,
                ue.lib_ue
            FROM evaluer_ecue ev
            JOIN ecue ec ON ev.id_ecue = ec.id_ecue
            JOIN ue ON ec.id_ue = ue.id_ue
            
            UNION ALL
            
            -- Évaluations UE directes
            SELECT 
                CAST(ev.num_etd AS UNSIGNED) as num_etd, 
                ev.id_ac, 
                ev.note, 
                ev.id_semestre,
                ev.id_ue,
                ue.lib_ue
            FROM evaluer_ue ev
            JOIN ue ON ev.id_ue = ue.id_ue
        ) ev ON e.num_etd = ev.num_etd
    JOIN annee_academique a ON a.id_ac = ev.id_ac
    JOIN niveau_etude ne ON ne.id_niv_etd = e.id_niv_etd
    LEFT JOIN moyenne_generale mg ON e.num_etd = mg.num_etd 
        AND mg.id_ac = a.id_ac
        AND mg.id_semestre = (
            SELECT s.id_semestre 
            FROM semestre s 
            WHERE s.id_niv_etd = e.id_niv_etd 
            ORDER BY s.id_semestre DESC 
            LIMIT 1
        )
    WHERE EXISTS (
        SELECT 1 FROM (
            SELECT CAST(num_etd AS UNSIGNED) as num_etd FROM evaluer_ecue WHERE id_ac = a.id_ac
            UNION
            SELECT CAST(num_etd AS UNSIGNED) as num_etd FROM evaluer_ue WHERE id_ac = a.id_ac
        ) ev WHERE ev.num_etd = e.num_etd
    )
    $where_sql
    GROUP BY e.num_etd, e.num_carte_etd, e.nom_etd, e.prenom_etd, a.date_debut, a.date_fin, ne.lib_niv_etd, ne.id_niv_etd, mg.moyenne_generale, mg.statut_academique, mg.total_credits_obtenus, mg.total_credits_inscrits
";

$count_sql = "SELECT COUNT(*) FROM ( $subquery ) AS t";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_records / $limit));

?>



<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluations Étudiants - GSCV+</title>
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
                <?php
                // Requête pour le total des étudiants évalués (UE + ECUE)
                $stmt = $pdo->prepare("
                    SELECT COUNT(DISTINCT num_etd) as count 
                    FROM (
                        SELECT CAST(num_etd AS UNSIGNED) as num_etd FROM evaluer_ue
                        UNION
                        SELECT CAST(num_etd AS UNSIGNED) as num_etd FROM evaluer_ecue
                    ) as all_evaluations
                ");
                $stmt->execute();
                $totalEvalues = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                // Requête pour les étudiants ayant validé
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM moyenne_generale mg
                    JOIN annee_academique a ON mg.id_ac = a.id_ac
                    WHERE a.statut_annee = 'En cours' 
                    AND mg.statut_academique = 'Validé'
                ");
                $stmt->execute();
                $valides = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                // Requête pour les étudiants ajournés
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM moyenne_generale mg
                    JOIN annee_academique a ON mg.id_ac = a.id_ac
                    WHERE a.statut_annee = 'En cours' 
                    AND mg.statut_academique = 'Ajourné'
                ");
                $stmt->execute();
                $nonValides = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                // Requête pour les étudiants en attente de note (pas d'évaluation)
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM etudiants e 
                    WHERE e.num_etd NOT IN (
                        SELECT DISTINCT CAST(num_etd AS UNSIGNED) as num_etd FROM evaluer_ue
                        UNION
                        SELECT DISTINCT CAST(num_etd AS UNSIGNED) as num_etd FROM evaluer_ecue
                    )
                ");
                $stmt->execute();
                $enAttente = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                ?>

                <!-- Total Évalués -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-primary hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Évalués</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $totalEvalues; ?></p>
                            <p class="text-sm text-green-600 flex items-center mt-1">
                                <i class="fas fa-chart-line mr-1"></i>
                                Étudiants évalués
                            </p>
                        </div>
                        <div class="p-3 bg-primary bg-opacity-10 rounded-full">
                            <i class="fas fa-users text-primary text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- En Attente -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-warning hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">En Attente</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $enAttente; ?></p>
                            <p class="text-sm text-yellow-600 flex items-center mt-1">
                                <i class="fas fa-clock mr-1"></i>
                                À évaluer
                            </p>
                        </div>
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-hourglass-half text-yellow-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Validés -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-success hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Validés</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $valides; ?></p>
                            <p class="text-sm text-green-600 flex items-center mt-1">
                                <i class="fas fa-check-circle mr-1"></i>
                                Réussite
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Non Validés -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-danger hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Non Validés</p>
                            <p class="text-3xl font-bold text-gray-900"><?= $nonValides; ?></p>
                            <p class="text-sm text-red-600 flex items-center mt-1">
                                <i class="fas fa-times-circle mr-1"></i>
                                Échec
                            </p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-full">
                            <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8 animate-slide-in">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Search and Filters -->
                    <div class="flex-1">
                        <form method="GET" class="flex flex-col sm:flex-row gap-4" id="filter-form">
                            <input type="hidden" name="page_num" id="page_num_input" value="<?php echo $page_num; ?>">
                            <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 'evaluations_etudiants'; ?>">
                            
                            <!-- Search Input -->
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" name="search" placeholder="Rechercher un étudiant..." 
                                       value="<?php echo htmlspecialchars($search); ?>"
                                       class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200">
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="relative">
                                <select name="statut" onchange="this.form.submit()"
                                        class="block w-full px-4 py-3 pr-8 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent bg-white transition-all duration-200">
                                    <option value="">Tous les statuts</option>
                                    <option value="Validé" <?php echo $statut === 'Validé' ? 'selected' : ''; ?>>Validé</option>
                                    <option value="Autorisé" <?php echo $statut === 'Autorisé' ? 'selected' : ''; ?>>Autorisé</option>
                                    <option value="Ajourné" <?php echo $statut === 'Ajourné' ? 'selected' : ''; ?>>Ajourné</option>
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
                        <button id="recalculer-moyennes" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-calculator mr-2"></i>Recalculer moyennes
                        </button>
                        <button id="bulk-delete-btn" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-trash mr-2"></i>Supprimer sélection
                        </button>
                        <button id="add_evaluation" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            <i class="fas fa-plus mr-2"></i>Nouvelle évaluation
                        </button>
                    </div>
                </div>
            </div>

            <!-- Evaluations List -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-fade-in">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-chart-bar mr-3 text-primary"></i>
                        Liste des Évaluations Étudiants
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Étudiant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prénoms</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moy. S1</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moy. S2</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moy. Annuelle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            try {
                                $id_personnel_adm = $_SESSION['id_personnel_adm'] ?? null;

                                if (!$id_personnel_adm) {
                                    echo "<tr><td colspan='10' class='px-6 py-4 text-center text-red-600'>Erreur : Identifiant personnel administratif non défini.</td></tr>";
                                    exit;
                                }

                                $sql = "$subquery ORDER BY nom_etd, prenom_etd LIMIT $limit OFFSET $offset";
                                $stmt = $pdo->prepare($sql);
                                $stmt->execute($params);
                                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                if (!$rows) {
                                    echo "<tr><td colspan='10' class='px-6 py-8 text-center text-gray-500'>Aucune évaluation enregistrée pour le moment.</td></tr>";
                                } else {
                                    foreach ($rows as $row) {
                                        $promotion = date('Y', strtotime($row['date_debut'])) . '-' . date('Y', strtotime($row['date_fin']));
                                        // Déterminer le statut selon la table moyenne_generale
                                        $statut_academique = $row['statut_academique'] ?? 'Non évalué';
                                        $moyenne_annuelle = $row['moyenne_annuelle'] ?? 0;
                                        
                                        switch ($statut_academique) {
                                            case 'Validé':
                                                $statutClass = 'text-green-600 bg-green-100';
                                                $statutText = 'Validé';
                                                $statutIcon = 'fa-check-circle';
                                                break;
                                            case 'Autorisé':
                                                $statutClass = 'text-blue-600 bg-blue-100';
                                                $statutText = 'Autorisé';
                                                $statutIcon = 'fa-user-check';
                                                break;
                                            case 'Ajourné':
                                                $statutClass = 'text-red-600 bg-red-100';
                                                $statutText = 'Ajourné';
                                                $statutIcon = 'fa-times-circle';
                                                break;
                                            default:
                                                $statutClass = 'text-gray-600 bg-gray-100';
                                                $statutText = 'Non évalué';
                                                $statutIcon = 'fa-question-circle';
                                                break;
                                        }

                                        // Récupérer les moyennes des deux semestres pour cet étudiant
                                        $sql_semestres = "
                                            SELECT s.id_semestre, s.lib_semestre,
                                                ROUND((
                                                    (COALESCE(SUM(CASE WHEN ev.credit < 4 THEN ev.note * ev.credit ELSE 0 END) / NULLIF(SUM(CASE WHEN ev.credit < 4 THEN ev.credit ELSE 0 END), 0), 0) * SUM(CASE WHEN ev.credit < 4 THEN ev.credit ELSE 0 END)) +
                                                    (COALESCE(SUM(CASE WHEN ev.credit >= 4 THEN ev.note * ev.credit ELSE 0 END) / NULLIF(SUM(CASE WHEN ev.credit >= 4 THEN ev.credit ELSE 0 END), 0), 0) * SUM(CASE WHEN ev.credit >= 4 THEN ev.credit ELSE 0 END))
                                                ) / NULLIF(SUM(ev.credit), 0), 2) AS moyenne_semestre
                                                FROM (
                                                    SELECT CAST(num_etd AS UNSIGNED) as num_etd, id_semestre, id_ac, id_personnel_adm, note, credit, date_eval
                                                    FROM evaluer_ecue
                                                    UNION ALL
                                                    SELECT CAST(num_etd AS UNSIGNED) as num_etd, id_semestre, id_ac, id_personnel_adm, note, credit, date_eval
                                                    FROM evaluer_ue
                                                ) ev
                                                INNER JOIN semestre s ON s.id_semestre = ev.id_semestre
                                                WHERE ev.num_etd = :num_etd AND ev.id_ac = :id_ac
                                                GROUP BY s.id_semestre, s.lib_semestre
                                                ORDER BY s.id_semestre ASC
                                            ";
                                        $stmt_sem = $pdo->prepare($sql_semestres);
                                        $stmt_sem->execute([
                                            'num_etd' => $row['num_etd'],
                                            'id_ac' => $pdo->query("SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' LIMIT 1")->fetchColumn()
                                        ]);
                                        $moyennes = $stmt_sem->fetchAll(PDO::FETCH_ASSOC);
                                        $moyenne_semestre1 = isset($moyennes[0]['moyenne_semestre']) ? number_format($moyennes[0]['moyenne_semestre'], 2) : '-';
                                        $moyenne_semestre2 = isset($moyennes[1]['moyenne_semestre']) ? number_format($moyennes[1]['moyenne_semestre'], 2) : '-';

                                        echo "<tr class='hover:bg-gray-50 transition-colors duration-200'>
                                            <td class='px-6 py-4 whitespace-nowrap'>
                                                <input type='checkbox' class='evaluation-checkbox rounded border-gray-300 text-primary focus:ring-primary' value='" . htmlspecialchars($row['num_carte_etd']) . "'>
                                            </td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900'>" . htmlspecialchars($row['num_carte_etd']) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($row['nom_etd']) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . htmlspecialchars($row['prenom_etd']) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900'>" . $promotion . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium'>" . $moyenne_semestre1 . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium'>" . $moyenne_semestre2 . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold'>" . number_format($moyenne_annuelle, 2) . "</td>
                                            <td class='px-6 py-4 whitespace-nowrap'>
                                                <span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $statutClass'>
                                                    <i class='fas $statutIcon mr-1'></i>$statutText
                                                </span>
                                            </td>
                                            <td class='px-6 py-4 whitespace-nowrap text-sm font-medium'>
                                                <div class='flex space-x-2'>
                                                    <button class='infos-button bg-blue-100 hover:bg-blue-200 text-blue-700 p-2 rounded-lg transition-all duration-200 hover:shadow-md transform hover:-translate-y-1' 
                                                            data-numero='" . htmlspecialchars($row['num_carte_etd']) . "' title='Voir détails'>
                                                        <i class='fas fa-info-circle'></i>
                                                    </button>
                                                    <button class='download-button bg-green-100 hover:bg-green-200 text-green-700 p-2 rounded-lg transition-all duration-200 hover:shadow-md transform hover:-translate-y-1' 
                                                            title='Télécharger relevé'>
                                                        <i class='fas fa-download'></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>";
                                    }
                                }
                            } catch (PDOException $e) {
                                echo "<tr><td colspan='10' class='px-6 py-4 text-center text-red-600'>Erreur de connexion ou requête : " . htmlspecialchars($e->getMessage()) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page_num > 1): ?>
                                <a href="#" class="page-item relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" data-page="<?php echo $page_num - 1; ?>">
                                    Précédent
                                </a>
                            <?php endif; ?>
                            <?php if ($page_num < $total_pages): ?>
                                <a href="#" class="page-item ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50" data-page="<?php echo $page_num + 1; ?>">
                                    Suivant
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Affichage de <span class="font-medium"><?php echo $offset + 1; ?></span> à 
                                    <span class="font-medium"><?php echo min($offset + $limit, $total_records); ?></span> sur 
                                    <span class="font-medium"><?php echo $total_records; ?></span> résultats
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page_num > 1): ?>
                                        <a href="#" class="page-item relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" data-page="<?php echo $page_num - 1; ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <a href="#" class="page-item relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i == $page_num ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>" data-page="<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page_num < $total_pages): ?>
                                        <a href="#" class="page-item relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50" data-page="<?php echo $page_num + 1; ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modern Evaluation Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm hidden items-center justify-center z-50" id="eval-modal">
        <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full mx-4 max-h-[90vh] overflow-hidden">
            <!-- Header -->
            <div class="flex justify-between items-center p-6 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-t-2xl">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-chart-line text-white text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Nouvelle Évaluation</h2>
                        <p class="text-sm text-gray-600">Saisie des notes et calcul des moyennes</p>
                    </div>
                </div>
                <button id="close-modal-eval-btn" class="w-8 h-8 bg-white rounded-full shadow-md flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-all duration-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="eval-form" action="assets/traitements/enregistrer_evaluation.php" method="POST" class="p-8 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- Progress Indicator -->
                <div class="mb-8">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold" id="step-1">1</div>
                            <span class="ml-2 text-sm font-medium text-gray-900">Informations Étudiant</span>
                        </div>
                        <div class="w-12 h-0.5 bg-gray-300"></div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-white text-sm font-bold" id="step-2">2</div>
                            <span class="ml-2 text-sm font-medium text-gray-500">Sélection Semestre</span>
                        </div>
                        <div class="w-12 h-0.5 bg-gray-300"></div>
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-white text-sm font-bold" id="step-3">3</div>
                            <span class="ml-2 text-sm font-medium text-gray-500">Saisie Notes</span>
                        </div>
                    </div>
                </div>

                <!-- Student Info Section -->
                <div class="mb-8" id="step-1-content">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-user-graduate mr-3 text-primary"></i>
                            Informations Étudiant
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="relative">
                                <label for="numero" class="block text-sm font-semibold text-gray-700 mb-3">N° carte étudiant *</label>
                                <div class="relative">
                                    <input type="text" id="numero" name="numero" placeholder="Ex: 2024-001"
                                           class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-lg">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Saisissez le numéro de carte pour rechercher l'étudiant</p>
                            </div>
                            <div>
                                <label for="nom" class="block text-sm font-semibold text-gray-700 mb-3">Nom</label>
                                <input type="text" id="nom" name="nom" placeholder="Nom de l'étudiant" readonly
                                       class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl bg-gray-50 text-lg">
                            </div>
                            <div>
                                <label for="prenom" class="block text-sm font-semibold text-gray-700 mb-3">Prénom</label>
                                <input type="text" id="prenom" name="prenom" placeholder="Prénom de l'étudiant" readonly
                                       class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl bg-gray-50 text-lg">
                            </div>
                            <div>
                                <label for="promotion" class="block text-sm font-semibold text-gray-700 mb-3">Promotion</label>
                                <input type="text" id="promotion" name="promotion" placeholder="Promotion" readonly
                                       class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl bg-gray-50 text-lg">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Semester Section -->
                <div class="mb-8" id="step-2-content" style="display: none;">
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-calendar-alt mr-3 text-green-600"></i>
                            Sélection du Semestre
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="niveau" class="block text-sm font-semibold text-gray-700 mb-3">Niveau</label>
                                <input type="text" id="niveau" name="niveau" placeholder="Niveau de l'étudiant" readonly
                                       class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl bg-gray-50 text-lg">
                            </div>
                            <div>
                                <label for="semestre" class="block text-sm font-semibold text-gray-700 mb-3">Semestre *</label>
                                <select id="semestre" name="semestre" 
                                        class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200 text-lg appearance-none bg-white">
                                    <option value="">Sélectionnez un semestre...</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="mb-8" id="step-3-content" style="display: none;">
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-edit mr-3 text-purple-600"></i>
                            Saisie des Notes
                        </h3>
                        
                        <!-- Instructions -->
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle text-blue-400"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-blue-700">
                                        <strong>Instructions :</strong> Saisissez les notes sur 20 pour chaque matière. Les moyennes sont calculées automatiquement.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Notes Table -->
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-lg font-medium text-gray-900">Matières à évaluer</h4>
                                    <div class="flex items-center space-x-4">
                                        <span class="text-sm text-gray-600">Total: <span id="total-subjects" class="font-semibold">0</span></span>
                                        <span class="text-sm text-gray-600">Remplies: <span id="filled-subjects" class="font-semibold text-green-600">0</span></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UE</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ECUE</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Crédits</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note /20</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody id="notes-list" class="bg-white divide-y divide-gray-200">
                                        <!-- Notes will be added dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-gray-700">Progression de saisie</span>
                                <span class="text-sm font-medium text-gray-700" id="progress-percentage">0%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-300" id="progress-bar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Section -->
                <div class="bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-6 mb-8" id="semester-average-section" style="display: none;">
                    <div class="text-center">
                        <h4 class="text-xl font-semibold text-gray-900 mb-4 flex items-center justify-center">
                            <i class="fas fa-calculator mr-3 text-orange-600"></i>
                            Moyenne Semestrielle
                        </h4>
                        <div class="text-6xl font-bold text-primary mb-4" id="semester-average">0.00</div>
                        <p class="text-sm text-gray-600 mb-6">Moyenne calculée automatiquement</p>
                        
                        <!-- Détails du calcul -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                            <div class="bg-white bg-opacity-50 rounded-lg p-4">
                                <div class="text-2xl font-bold text-blue-600" id="majeures-info">0.00</div>
                                <div class="text-sm text-gray-600">UE Majeures</div>
                                <div class="text-xs text-gray-500" id="majeures-credits">0 crédits</div>
                            </div>
                            <div class="bg-white bg-opacity-50 rounded-lg p-4">
                                <div class="text-2xl font-bold text-green-600" id="mineures-info">0.00</div>
                                <div class="text-sm text-gray-600">UE Mineures</div>
                                <div class="text-xs text-gray-500" id="mineures-credits">0 crédits</div>
                            </div>
                            <div class="bg-white bg-opacity-50 rounded-lg p-4">
                                <div class="text-2xl font-bold text-purple-600" id="total-credits-info">0</div>
                                <div class="text-sm text-gray-600">Total Crédits</div>
                                <div class="text-xs text-gray-500">Évalués</div>
                            </div>
                        </div>
                        
                        <!-- Indicateur de performance -->
                        <div class="mt-6">
                            <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium" id="performance-indicator">
                                <i class="fas fa-info-circle mr-2"></i>
                                Performance à évaluer
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <button type="button" id="prev-step" class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-medium transition-all duration-200 hover:bg-gray-50 hover:border-gray-400" style="display: none;">
                        <i class="fas fa-arrow-left mr-2"></i>Précédent
                    </button>
                    <div class="flex space-x-4">
                        <button type="button" id="next-step" class="px-8 py-3 bg-primary hover:bg-primary-dark text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1">
                            Suivant<i class="fas fa-arrow-right ml-2"></i>
                        </button>
                        <button type="submit" id="submit-eval" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg transform hover:-translate-y-1" style="display: none;">
                            <i class="fas fa-save mr-2"></i>Enregistrer l'évaluation
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modern Info Modal -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="info-modal">
        <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-user-graduate mr-3 text-primary"></i>
                    Détails de l'Étudiant
                </h2>
                <button id="info-close-btn" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-6">
                <!-- Student Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-id-card mr-2 text-primary"></i>
                            Informations Personnelles
                        </h3>
                        <div class="space-y-2">
                            <div><span class="font-medium">Nom:</span> <span id="info-nom" class="text-gray-900"></span></div>
                            <div><span class="font-medium">Prénom:</span> <span id="info-prenom" class="text-gray-900"></span></div>
                            <div><span class="font-medium">N° Carte:</span> <span id="info-numero" class="text-gray-900"></span></div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
                            <i class="fas fa-graduation-cap mr-2 text-green-600"></i>
                            Informations Académiques
                        </h3>
                        <div class="space-y-2">
                            <div><span class="font-medium">Promotion:</span> <span id="info-promotion" class="text-gray-900"></span></div>
                            <div><span class="font-medium">Niveau:</span> <span id="info-niveau" class="text-gray-900"></span></div>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-center" id="info-student-status">
                    <span class="text-lg font-medium text-gray-700">Statut de validation:</span>
                    <div class="mt-2">
                        <span class="status-badge inline-block w-3 h-3 rounded-full mr-2"></span>
                        <span class="status-text text-lg font-semibold"></span>
                    </div>
                </div>

                <!-- Evaluations Table -->
                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-6">
                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-primary"></i>
                            Historique des Évaluations
                        </h3>
                        <span id="evaluations-count" class="bg-primary text-white px-3 py-1 rounded-full text-sm">0 évaluation(s)</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">UE/ECUE</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Évaluateur</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Note</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Crédits</th>
                                </tr>
                            </thead>
                            <tbody id="evaluations-tbody" class="bg-white divide-y divide-gray-200">
                                <!-- Evaluations will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Average Summary -->
                <div class="bg-gradient-to-r from-primary to-primary-light rounded-lg text-white p-6 text-center">
                    <h4 class="text-lg font-medium mb-2 flex items-center justify-center">
                        <i class="fas fa-calculator mr-2"></i>
                        Moyenne Annuelle
                    </h4>
                    <div id="info-moyenne-generale" class="text-4xl font-bold mb-4">0.00</div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <div class="text-2xl font-bold" id="info-total-credits">0</div>
                            <div class="text-sm opacity-90">Total Crédits</div>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <div class="text-lg font-bold" id="info-semestres">-</div>
                            <div class="text-sm opacity-90">Semestres</div>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <div class="text-2xl font-bold" id="info-nombre-evaluations">0</div>
                            <div class="text-sm opacity-90">Évaluations</div>
                        </div>
                    </div>
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
        });

        // --- Remplissage automatique des infos étudiant et des notes existantes ---
        document.addEventListener('DOMContentLoaded', function() {
            const numeroInput = document.getElementById('numero');
            const semestreSelect = document.getElementById('semestre');
            const notesContainer = document.getElementById('notes-container');
            const notesList = document.getElementById('notes-list');
            const addEvalBtn = document.getElementById('add_evaluation');
            const evalModal = document.getElementById('eval-modal');
            const nomInput = document.getElementById('nom');
            const prenomInput = document.getElementById('prenom');
            const promotionInput = document.getElementById('promotion');
            const niveauInput = document.getElementById('niveau');

            // Variable pour contrôler le remplissage automatique
            let autoFillEnabled = true;

            // Gestionnaire pour le bouton "Nouvelle évaluation"
            if (addEvalBtn && evalModal) {
                addEvalBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Désactiver le remplissage automatique
                    autoFillEnabled = false;
                    // Ouvrir la modale
                    evalModal.classList.remove('hidden');
                    evalModal.classList.add('flex');
                    // Réinitialiser tous les champs
                    if (numeroInput) numeroInput.value = '';
                    if (nomInput) nomInput.value = '';
                    if (prenomInput) prenomInput.value = '';
                    if (promotionInput) promotionInput.value = '';
                    if (niveauInput) niveauInput.value = '';
                    if (semestreSelect) semestreSelect.innerHTML = '<option value="">-- Choisir un semestre --</option>';
                    if (notesList) notesList.innerHTML = '';
                    if (notesContainer) notesContainer.style.display = 'none';
                    // Réactiver le remplissage automatique après un court délai
                    setTimeout(() => {
                        autoFillEnabled = true;
                    }, 100);
                });
            }

            // Quand on saisit le numéro de carte étudiant
            numeroInput?.addEventListener('input', debounce(async function() {
                if (!autoFillEnabled) return; // Ne pas exécuter si désactivé
                const numero = numeroInput.value.trim();
                if (!numero) return;
                // Charger infos étudiant
                try {
                    const res = await fetch('assets/traitements/get_etudiant_info.php?num_carte=' + encodeURIComponent(numero));
                    const data = await res.json();
                    if (data.error) throw new Error(data.error);
                    document.getElementById('nom').value = data.nom_etd;
                    document.getElementById('prenom').value = data.prenom_etd;
                    document.getElementById('promotion').value = data.lib_promotion;
                    document.getElementById('niveau').value = data.lib_niv_etd;
                    // Charger les semestres
                    const semRes = await fetch('assets/traitements/get_semestres_by_niveau.php?id_niveau=' + data.id_niv_etd);
                    const semData = await semRes.json();
                    if (!semData.success) throw new Error(semData.message);
                    semestreSelect.innerHTML = '<option value="">-- Choisir un semestre --</option>';
                    semData.semestres.forEach(sem => {
                        const opt = document.createElement('option');
                        opt.value = sem.id_semestre;
                        opt.textContent = sem.lib_semestre;
                        semestreSelect.appendChild(opt);
                    });
                    // Ne pas vider les notes ici, seulement masquer le conteneur
                    if (notesContainer) notesContainer.style.display = 'none';
                } catch (err) {
                    document.getElementById('nom').value = '';
                    document.getElementById('prenom').value = '';
                    document.getElementById('promotion').value = '';
                    document.getElementById('niveau').value = '';
                    semestreSelect.innerHTML = '<option value="">-- Choisir un semestre --</option>';
                    if (notesContainer) notesContainer.style.display = 'none';
                }
            }, 500));

            // Quand on sélectionne un semestre
            semestreSelect?.addEventListener('change', async function() {
                const semestreId = this.value;
                const numero = numeroInput.value.trim();
                console.log('Sélection semestre:', semestreId, 'Numéro:', numero);

                if (!semestreId || !numero) {
                    if (notesContainer) notesContainer.style.display = 'none';
                    if (notesList) notesList.innerHTML = '';
                    return;
                }

                try {
                    // Charger la liste des matières (UE/ECUE) du semestre
                    console.log('Chargement des UE pour le semestre:', semestreId);
                    let ues = [];

                    try {
                        const uesRes = await fetch('assets/traitements/get_ues_by_semestre.php?id_semestre=' + encodeURIComponent(semestreId));
                        const uesData = await uesRes.json();
                        console.log('Réponse UE:', uesData);

                        if (uesData.success) {
                            ues = uesData.data;
                        } else {
                            throw new Error(uesData.message || 'Erreur lors du chargement des UE');
                        }
                    } catch (uesError) {
                        console.error('Erreur UE:', uesError);
                        // Fallback : essayer de charger toutes les UE
                        try {
                            const allUesRes = await fetch('assets/traitements/get_ecue_by_ue.php?all=1');
                            const allUesData = await allUesRes.json();
                            if (allUesData.success) {
                                ues = allUesData.data;
                            }
                        } catch (fallbackError) {
                            console.error('Erreur fallback UE:', fallbackError);
                            throw new Error('Impossible de charger les UE');
                        }
                    }

                    console.log('UEs trouvées:', ues);

                    // Essayer de charger les notes existantes, sinon supposer qu'il n'y en a pas
                    let notesMap = new Map();
                    try {
                        console.log('Chargement des notes existantes pour:', numero, semestreId);
                        const notesRes = await fetch('assets/traitements/get_notes_by_student_semester.php?numero=' + encodeURIComponent(numero) + '&semestre=' + encodeURIComponent(semestreId));
                        const notesData = await notesRes.json();
                        console.log('Réponse notes:', notesData);

                        if (notesData.success && Array.isArray(notesData.data)) {
                            notesData.data.forEach(note => {
                                notesMap.set(note.id_ecue, note);
                            });
                            console.log('Notes chargées:', notesMap.size);
                        }
                    } catch (notesError) {
                        console.log('Erreur lors du chargement des notes:', notesError);
                    }

                    // Afficher dynamiquement les lignes de saisie
                    if (notesList) notesList.innerHTML = '';
                    let hasNotes = false;
                    let itemsCreated = 0;

                    for (const ue of ues) {
                        console.log('Traitement UE:', ue.lib_ue);

                        try {
                            // Charger les ECUE de l'UE
                            const ecueRes = await fetch('assets/traitements/get_ecue_by_ue.php?id_ue=' + encodeURIComponent(ue.id_ue));
                            const ecueData = await ecueRes.json();
                            console.log('Réponse ECUE pour UE', ue.id_ue, ':', ecueData);

                            if (ecueData.success && ecueData.data.length > 0) {
                                for (const ecue of ecueData.data) {
                                    console.log('Traitement ECUE:', ecue.lib_ecue, 'ID:', ecue.id_ecue, 'Type:', typeof ecue.id_ecue);
                                    
                                    // Vérifier que les IDs sont bien définis
                                    if (!ecue.id_ecue || !ue.id_ue) {
                                        console.error('IDs manquants:', { ueId: ue.id_ue, ecueId: ecue.id_ecue });
                                        continue;
                                    }
                                    
                                    const note = notesMap.get(ecue.id_ecue);
                                    const row = document.createElement('tr');
                                    row.className = 'note-item hover:bg-gray-50 transition-colors duration-200';
                                    row.dataset.ueId = ue.id_ue;
                                    row.dataset.ecueId = ecue.id_ecue;
                                    
                                    const noteValue = note ? note.note : '';
                                    const statusClass = noteValue ? 'text-green-600' : 'text-gray-400';
                                    const statusText = noteValue ? 'Saisie' : 'En attente';
                                    const statusIcon = noteValue ? 'fa-check-circle' : 'fa-clock';
                                    
                                    console.log('Création ligne avec IDs:', { ueId: ue.id_ue, ecueId: ecue.id_ecue });
                                    
                                    row.innerHTML = `
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-book text-blue-500 mr-2"></i>
                                                ${ue.lib_ue}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-graduation-cap text-green-500 mr-2"></i>
                                                ${ecue.lib_ecue}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                ${ecue.credit_ecue || ecue.credit_ue || 0} crédits
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="relative">
                                                <input type="number" 
                                                       name="moyenne[]" 
                                                       placeholder="0.00" 
                                                       step="0.01" 
                                                       min="0" 
                                                       max="20" 
                                                       value="${noteValue}"
                                                       class="note-input block w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200"
                                                       data-ue-id="${ue.id_ue}"
                                                       data-ecue-id="${ecue.id_ecue}">
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 text-sm">/20</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                                                <i class="fas ${statusIcon} mr-1"></i>
                                                ${statusText}
                                            </span>
                                        </td>
                                    `;
                                    if (notesList) notesList.appendChild(row);
                                    if (note) hasNotes = true;
                                    itemsCreated++;
                                }
                            } else {
                                // Si pas d'ECUE, créer une ligne pour l'UE directement
                                const row = document.createElement('tr');
                                row.className = 'note-item hover:bg-gray-50 transition-colors duration-200';
                                row.dataset.ueId = ue.id_ue;
                                row.dataset.ecueId = ue.id_ue; // Même ID pour UE évaluée directement
                                
                                row.innerHTML = `
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <div class="flex items-center">
                                            <i class="fas fa-book text-blue-500 mr-2"></i>
                                            ${ue.lib_ue}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 italic">
                                        <div class="flex items-center">
                                            <i class="fas fa-minus text-gray-400 mr-2"></i>
                                            Évaluation directe
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            ${ue.credit_ue || 0} crédits
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="relative">
                                            <input type="number" 
                                                   name="moyenne[]" 
                                                   placeholder="0.00" 
                                                   step="0.01" 
                                                   min="0" 
                                                   max="20" 
                                                   value=""
                                                   class="note-input block w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200"
                                                   data-ue-id="${ue.id_ue}"
                                                   data-ecue-id="${ue.id_ue}">
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 text-sm">/20</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-gray-400">
                                            <i class="fas fa-clock mr-1"></i>
                                            En attente
                                        </span>
                                    </td>
                                `;
                                if (notesList) notesList.appendChild(row);
                                itemsCreated++;
                            }
                        } catch (ecueError) {
                            console.warn('Impossible de charger les ECUE pour UE', ue.id_ue, ':', ecueError);
                            // Créer une ligne simple pour l'UE
                            const row = document.createElement('tr');
                            row.className = 'note-item hover:bg-gray-50 transition-colors duration-200';
                            
                            row.innerHTML = `
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <div class="flex items-center">
                                        <i class="fas fa-book text-blue-500 mr-2"></i>
                                        ${ue.lib_ue}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 italic">
                                    <div class="flex items-center">
                                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                        Erreur de chargement
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        ${ue.credit_ue || 0} crédits
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="relative">
                                        <input type="number" 
                                               name="moyenne[]" 
                                               placeholder="0.00" 
                                               step="0.01" 
                                               min="0" 
                                               max="20" 
                                               value=""
                                               class="note-input block w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all duration-200"
                                               data-ue-id="${ue.id_ue}"
                                               data-ecue-id="${ue.id_ue}">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">/20</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-red-400">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Erreur
                                    </span>
                                </td>
                            `;
                            if (notesList) notesList.appendChild(row);
                            itemsCreated++;
                        }
                    }

                    console.log('Affichage terminé, items créés:', itemsCreated, 'hasNotes:', hasNotes);
                    
                    // Mettre à jour les compteurs et la barre de progression
                    updateProgressCounters();
                    
                    if (itemsCreated === 0) {
                        if (notesList) notesList.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center text-gray-500"><i class="fas fa-exclamation-triangle mr-2"></i>Aucune matière trouvée pour ce semestre.</td></tr>';
                    } else if (!hasNotes) {
                        const msg = document.createElement('tr');
                        msg.innerHTML = '<td colspan="5" class="px-6 py-4 text-center text-gray-500 italic"><i class="fas fa-info-circle mr-2"></i>Aucune note enregistrée pour ce semestre.</td>';
                        if (notesList) notesList.appendChild(msg);
                    }
                } catch (err) {
                    console.error('Erreur lors du chargement:', err);
                    if (notesList) notesList.innerHTML = '<div style="color:red;text-align:center;">Erreur lors du chargement des matières: ' + err.message + '</div>';
                    if (notesContainer) notesContainer.style.display = 'block';
                }
            });

            // Petite fonction utilitaire debounce
            function debounce(fn, delay) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            // Fonction pour calculer la moyenne semestrielle
            function calculerMoyenneSemestre() {
                const noteInputs = document.querySelectorAll('.note-input');
                const averageSection = document.getElementById('semester-average-section');

                let ueMajeures = [];
                let ueMineures = [];

                // Parcourir les notes et les classer
                noteInputs.forEach(input => {
                    const note = parseFloat(input.value) || 0;
                    const row = input.closest('tr');
                    const creditElement = row.querySelector('td:nth-child(3) span');
                    const creditText = creditElement ? creditElement.textContent : '0';
                    const credit = parseFloat(creditText.match(/\d+/)[0]) || 0;

                    if (note > 0 && credit > 0) {
                        if (credit >= 4) {
                            ueMajeures.push({
                                note,
                                credit
                            });
                        } else {
                            ueMineures.push({
                                note,
                                credit
                            });
                        }
                    }
                });

                // Calculer les moyennes
                let moyenneMajeures = 0;
                let totalCreditMajeures = 0;
                let moyenneMineures = 0;
                let totalCreditMineures = 0;

                // Moyenne UE majeures
                if (ueMajeures.length > 0) {
                    const sommeNotesMajeures = ueMajeures.reduce((sum, ue) => sum + (ue.note * ue.credit), 0);
                    totalCreditMajeures = ueMajeures.reduce((sum, ue) => sum + ue.credit, 0);
                    moyenneMajeures = sommeNotesMajeures / totalCreditMajeures;
                }

                // Moyenne UE mineures
                if (ueMineures.length > 0) {
                    const sommeNotesMineures = ueMineures.reduce((sum, ue) => sum + (ue.note * ue.credit), 0);
                    totalCreditMineures = ueMineures.reduce((sum, ue) => sum + ue.credit, 0);
                    moyenneMineures = sommeNotesMineures / totalCreditMineures;
                }

                // Moyenne semestrielle selon la formule
                const totalCredits = totalCreditMajeures + totalCreditMineures;
                let moyenneSemestre = 0;

                if (totalCredits > 0) {
                    moyenneSemestre = (moyenneMajeures * totalCreditMajeures + moyenneMineures * totalCreditMineures) / totalCredits;
                }

                // Afficher les résultats
                const averageElement = document.getElementById('semester-average');
                const majeuresInfo = document.getElementById('majeures-info');
                const mineuresInfo = document.getElementById('mineures-info');
                const majeuresCredits = document.getElementById('majeures-credits');
                const mineuresCredits = document.getElementById('mineures-credits');
                const totalCreditsInfo = document.getElementById('total-credits-info');
                const performanceIndicator = document.getElementById('performance-indicator');

                if (averageElement) {
                    averageElement.textContent = moyenneSemestre.toFixed(2);
                }

                if (majeuresInfo) {
                    majeuresInfo.textContent = moyenneMajeures.toFixed(2);
                }

                if (mineuresInfo) {
                    mineuresInfo.textContent = moyenneMineures.toFixed(2);
                }

                if (majeuresCredits) {
                    majeuresCredits.textContent = `${totalCreditMajeures} crédits`;
                }

                if (mineuresCredits) {
                    mineuresCredits.textContent = `${totalCreditMineures} crédits`;
                }

                if (totalCreditsInfo) {
                    totalCreditsInfo.textContent = totalCredits;
                }

                // Afficher la section de moyenne si des notes sont saisies
                if (averageSection) {
                    if (totalCredits > 0) {
                        averageSection.style.display = 'block';
                    } else {
                        averageSection.style.display = 'none';
                    }
                }

                // Mettre à jour l'indicateur de performance
                if (performanceIndicator) {
                    let indicatorClass = 'bg-gray-100 text-gray-800';
                    let indicatorIcon = 'fa-info-circle';
                    let indicatorText = 'Performance à évaluer';

                    if (moyenneSemestre >= 16) {
                        indicatorClass = 'bg-green-100 text-green-800';
                        indicatorIcon = 'fa-star';
                        indicatorText = 'Excellente performance';
                    } else if (moyenneSemestre >= 14) {
                        indicatorClass = 'bg-blue-100 text-blue-800';
                        indicatorIcon = 'fa-thumbs-up';
                        indicatorText = 'Bonne performance';
                    } else if (moyenneSemestre >= 12) {
                        indicatorClass = 'bg-yellow-100 text-yellow-800';
                        indicatorIcon = 'fa-check';
                        indicatorText = 'Performance satisfaisante';
                    } else if (moyenneSemestre >= 10) {
                        indicatorClass = 'bg-orange-100 text-orange-800';
                        indicatorIcon = 'fa-exclamation-triangle';
                        indicatorText = 'Performance passable';
                    } else if (moyenneSemestre > 0) {
                        indicatorClass = 'bg-red-100 text-red-800';
                        indicatorIcon = 'fa-times-circle';
                        indicatorText = 'Performance insuffisante';
                    }

                    performanceIndicator.className = `inline-flex items-center px-4 py-2 rounded-full text-sm font-medium ${indicatorClass}`;
                    performanceIndicator.innerHTML = `<i class="fas ${indicatorIcon} mr-2"></i>${indicatorText}`;
                }
            }

            // Fonction pour mettre à jour les compteurs de progression
            function updateProgressCounters() {
                const noteInputs = document.querySelectorAll('.note-input');
                const totalSubjects = noteInputs.length;
                const filledSubjects = Array.from(noteInputs).filter(input => input.value && input.value.trim() !== '').length;
                const percentage = totalSubjects > 0 ? Math.round((filledSubjects / totalSubjects) * 100) : 0;
                
                // Mettre à jour les compteurs
                const totalElement = document.getElementById('total-subjects');
                const filledElement = document.getElementById('filled-subjects');
                const percentageElement = document.getElementById('progress-percentage');
                const progressBar = document.getElementById('progress-bar');
                
                if (totalElement) totalElement.textContent = totalSubjects;
                if (filledElement) filledElement.textContent = filledSubjects;
                if (percentageElement) percentageElement.textContent = percentage + '%';
                if (progressBar) progressBar.style.width = percentage + '%';
                
                // Mettre à jour les statuts des lignes
                noteInputs.forEach(input => {
                    const row = input.closest('tr');
                    if (row) {
                        const statusCell = row.querySelector('td:last-child span');
                        if (statusCell) {
                            const hasValue = input.value && input.value.trim() !== '';
                            statusCell.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${hasValue ? 'text-green-600' : 'text-gray-400'}`;
                            statusCell.innerHTML = `<i class="fas ${hasValue ? 'fa-check-circle' : 'fa-clock'} mr-1"></i>${hasValue ? 'Saisie' : 'En attente'}`;
                        }
                    }
                });
            }

            // Ajouter des écouteurs d'événements pour recalculer les moyennes et mettre à jour la progression
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('note-input')) {
                    calculerMoyenneSemestre();
                    updateProgressCounters();
                    
                    // Validation en temps réel
                    const value = parseFloat(e.target.value);
                    if (value < 0 || value > 20) {
                        e.target.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                        e.target.classList.remove('border-gray-300', 'focus:ring-primary', 'focus:border-primary');
                    } else {
                        e.target.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                        e.target.classList.add('border-gray-300', 'focus:ring-primary', 'focus:border-primary');
                    }
                }
            });

            // Ajouter des écouteurs d'événements pour les effets visuels
            document.addEventListener('focus', function(e) {
                if (e.target.classList.contains('note-input')) {
                    e.target.closest('tr').classList.add('bg-blue-50');
                }
            });

            document.addEventListener('blur', function(e) {
                if (e.target.classList.contains('note-input')) {
                    e.target.closest('tr').classList.remove('bg-blue-50');
                }
            });


            document.querySelectorAll('.download-button').forEach(btn => {
                btn.addEventListener('click', () => {
                    const numero = btn.closest('tr').querySelector('td:nth-child(2)').textContent;
                    window.open('assets/traitements/bulletin_etudiant.php?numero=' + encodeURIComponent(numero), '_blank', 'width=800,height=600');
                });
            });

            // Gestionnaire de soumission du formulaire
            const evalForm = document.getElementById('eval-form');
            if (evalForm) {
                evalForm.addEventListener('submit', function(e) {
                    const numero = document.getElementById('numero').value.trim();
                    const semestre = document.getElementById('semestre').value;

                    if (!numero || !semestre) {
                        e.preventDefault();
                        alert('Veuillez remplir le numéro d\'étudiant et sélectionner un semestre');
                        return;
                    }

                    // Collecter toutes les notes
                    const notes = {};
                    const credits = {};
                    const noteItems = document.querySelectorAll('.note-item');

                    if (noteItems.length === 0) {
                        e.preventDefault();
                        alert('Aucune matière à évaluer');
                        return;
                    }

                    let hasNotes = false;

                    noteItems.forEach(item => {
                        const noteInput = item.querySelector('input[name="moyenne[]"]');
                        
                        if (noteInput) {
                            const note = parseFloat(noteInput.value);
                            const ueId = noteInput.dataset.ueId;
                            const ecueId = noteInput.dataset.ecueId;
                            
                            // Récupérer le crédit depuis le span dans la 3ème colonne
                            const creditSpan = item.querySelector('td:nth-child(3) span');
                            let credit = 0;
                            if (creditSpan) {
                                const creditText = creditSpan.textContent;
                                const creditMatch = creditText.match(/(\d+)\s*crédits?/);
                                if (creditMatch) {
                                    credit = parseInt(creditMatch[1]);
                                }
                            }

                            console.log('Note trouvée:', { ueId, ecueId, note, credit, noteInputValue: noteInput.value });

                            if (note >= 0 && note <= 20 && credit > 0 && ueId && ecueId) {
                                if (!notes[ueId]) {
                                    notes[ueId] = {};
                                    credits[ueId] = {};
                                }
                                notes[ueId][ecueId] = note;
                                credits[ueId][ecueId] = credit;
                                hasNotes = true;
                                console.log('Note ajoutée:', { ueId, ecueId, note, credit });
                            } else {
                                console.log('Note invalide ou manquante:', { 
                                    ueId, 
                                    ecueId, 
                                    note, 
                                    credit, 
                                    noteValid: note >= 0 && note <= 20,
                                    creditValid: credit > 0,
                                    ueIdValid: !!ueId,
                                    ecueIdValid: !!ecueId
                                });
                            }
                        } else {
                            console.log('Aucun input de note trouvé dans l\'item');
                        }
                    });

                    console.log('Résultat validation:', { hasNotes, notes, credits });

                    if (!hasNotes) {
                        e.preventDefault();
                        alert('Veuillez saisir au moins une note valide');
                        return;
                    }

                    // Vérifier que les objets ne sont pas vides
                    const notesKeys = Object.keys(notes);
                    const creditsKeys = Object.keys(credits);
                    
                    console.log('Clés des notes:', notesKeys);
                    console.log('Clés des crédits:', creditsKeys);
                    
                    if (notesKeys.length === 0) {
                        e.preventDefault();
                        alert('Aucune note valide trouvée dans les données');
                        return;
                    }
                    
                    if (creditsKeys.length === 0) {
                        e.preventDefault();
                        alert('Aucun crédit valide trouvé dans les données');
                        return;
                    }

                    // Debug des données JSON avant envoi
                    console.log('=== DEBUG AVANT ENVOI ===');
                    console.log('Notes JSON:', JSON.stringify(notes));
                    console.log('Credits JSON:', JSON.stringify(credits));
                    console.log('Notes object:', notes);
                    console.log('Credits object:', credits);

                    // Créer des champs cachés pour les données JSON
                    const notesInput = document.createElement('input');
                    notesInput.type = 'hidden';
                    notesInput.name = 'notes';
                    notesInput.value = JSON.stringify(notes);
                    evalForm.appendChild(notesInput);

                    const creditsInput = document.createElement('input');
                    creditsInput.type = 'hidden';
                    creditsInput.name = 'credits';
                    creditsInput.value = JSON.stringify(credits);
                    evalForm.appendChild(creditsInput);

                    // Afficher un indicateur de chargement
                    const submitBtn = evalForm.querySelector('.submit-btn');
                    if (submitBtn) {
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
                        submitBtn.disabled = true;
                    }

                    // Le formulaire se soumettra normalement
                    // La redirection sera gérée par le script PHP
                });
            }

            // Gestion de la modale de détails d'évaluation
            const infoModal = document.getElementById('info-modal');
            const closeInfoModalBtn = document.getElementById('info-close-btn');

            // Fermer la modale de détails
            if (closeInfoModalBtn) {
                closeInfoModalBtn.addEventListener('click', function() {
                    if (infoModal) {
                        infoModal.classList.add('hidden');
                        infoModal.classList.remove('flex');
                    }
                });
            }

            // Fermer la modale en cliquant à l'extérieur
            if (infoModal) {
                infoModal.addEventListener('click', function(e) {
                    if (e.target === infoModal) {
                        infoModal.classList.add('hidden');
                        infoModal.classList.remove('flex');
                    }
                });
            }

            // Gestion de la modale d'évaluation
            const closeEvalBtn = document.getElementById('close-modal-eval-btn');

            // Fermer la modale d'évaluation
            if (closeEvalBtn) {
                closeEvalBtn.addEventListener('click', function() {
                    if (evalModal) {
                        evalModal.classList.add('hidden');
                        evalModal.classList.remove('flex');
                    }
                });
            }

            // Fermer la modale d'évaluation en cliquant à l'extérieur
            if (evalModal) {
                evalModal.addEventListener('click', function(e) {
                    if (e.target === evalModal) {
                        evalModal.classList.add('hidden');
                        evalModal.classList.remove('flex');
                    }
                });
            }

            // Gestionnaire pour les boutons d'info
            document.addEventListener('click', function(e) {
                if (e.target.closest('.infos-button')) {
                    const button = e.target.closest('.infos-button');
                    const numero = button.dataset.numero;

                    if (numero) {
                        afficherDetailsEvaluation(numero);
                    }
                }
            });

            // Fonction pour afficher les détails d'évaluation
            async function afficherDetailsEvaluation(numero) {
                try {
                    // Afficher un indicateur de chargement
                    if (infoModal) {
                        infoModal.classList.remove('hidden');
                        infoModal.classList.add('flex');
                        document.getElementById('evaluations-tbody').innerHTML = '<tr><td colspan="5" class="px-6 py-4 text-center">Chargement...</td></tr>';
                    }

                    // Récupérer les informations de l'étudiant
                    const studentRes = await fetch('assets/traitements/get_etudiant_info.php?num_carte=' + encodeURIComponent(numero));
                    const studentData = await studentRes.json();

                    if (!studentData.success) {
                        throw new Error(studentData.message || 'Erreur lors du chargement des informations étudiant');
                    }

                    // Remplir les informations de base
                    document.getElementById('info-nom').textContent = studentData.nom_etd;
                    document.getElementById('info-prenom').textContent = studentData.prenom_etd;
                    document.getElementById('info-numero').textContent = studentData.num_carte_etd;

                    // Récupérer l'année universitaire en cours
                    try {
                        const yearRes = await fetch('assets/traitements/get_current_year.php', {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const yearData = await yearRes.json();
                        const anneeUniversitaire = yearData.success ? yearData.current_year : 'À définir';
                        document.getElementById('info-promotion').textContent = anneeUniversitaire;
                    } catch (error) {
                        console.error('Erreur lors de la récupération de l\'année universitaire:', error);
                        document.getElementById('info-promotion').textContent = 'À définir';
                    }

                    document.getElementById('info-niveau').textContent = studentData.lib_niv_etd;

                    // Récupérer toutes les évaluations de l'étudiant
                    const evaluationsRes = await fetch('assets/traitements/get_evaluations_etudiant.php?numero=' + encodeURIComponent(numero));
                    const evaluationsData = await evaluationsRes.json();

                    if (!evaluationsData.success) {
                        throw new Error(evaluationsData.message || 'Erreur lors du chargement des évaluations');
                    }

                    const evaluations = evaluationsData.data || [];

                    // Calculer la moyenne générale et autres statistiques
                    let totalNotes = 0;
                    let totalCredits = 0; // Déclaration au début
                    const semestres = new Set();

                    evaluations.forEach(eval => {
                        if (eval.note && !isNaN(parseFloat(eval.note)) && eval.credit && !isNaN(parseInt(eval.credit))) {
                            totalNotes += parseFloat(eval.note) * parseInt(eval.credit);
                            totalCredits += parseInt(eval.credit);
                        }
                        if (eval.lib_semestre) {
                            semestres.add(eval.lib_semestre);
                        }
                    });

                    const moyenneGenerale = totalCredits > 0 ? totalNotes / totalCredits : 0;

                    // Afficher la moyenne et le statut
                    document.getElementById('info-moyenne-generale').textContent = moyenneGenerale.toFixed(2);
                    document.getElementById('info-total-credits').textContent = totalCredits;
                    document.getElementById('info-semestres').textContent = Array.from(semestres).join(', ') || '-';
                    document.getElementById('info-nombre-evaluations').textContent = evaluations.length;
                    document.getElementById('evaluations-count').textContent = `${evaluations.length} évaluation(s)`;

                    const statusBadge = document.querySelector('#info-student-status .status-badge');
                    const statusText = document.querySelector('#info-student-status .status-text');

                    if (moyenneGenerale >= 10) {
                        statusBadge.className = 'status-badge validated';
                        statusText.className = 'status-text validated';
                        statusText.textContent = 'Validé';
                    } else {
                        statusBadge.className = 'status-badge not-validated';
                        statusText.className = 'status-text not-validated';
                        statusText.textContent = 'Non validé';
                    }

                    // Remplir le tableau des évaluations
                    const tbody = document.getElementById('evaluations-tbody');
                    tbody.innerHTML = '';

                    // Filtrage unique et calculs
                    const uniqueRows = [];
                    const seen = new Set();
                    evaluations.forEach(ev => {
                        const ue = ev.lib_ue?.trim() || '';
                        const ecue = ev.lib_ecue?.trim() || '';
                        const semestre = ev.lib_semestre?.trim() || '';
                        const key = `${ue}|${ecue}|${semestre}`;
                        if (!seen.has(key)) {
                            seen.add(key);
                            uniqueRows.push(ev);
                        }
                    });

                    // Calcul du total des crédits uniques et du nombre d'évaluations uniques
                    let totalCreditsUniques = 0;
                    uniqueRows.forEach(ev => {
                        const credit = parseFloat(ev.credit);
                        if (!isNaN(credit) && credit > 0) {
                            totalCreditsUniques += credit;
                        }
                    });
                    const totalEvaluations = uniqueRows.length;

                    // Mise à jour avec les valeurs uniques
                    document.getElementById('info-total-credits').textContent = totalCreditsUniques;
                    document.getElementById('info-nombre-evaluations').textContent = totalEvaluations;

                    if (uniqueRows.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#6c757d;padding:2rem;">Aucune évaluation trouvée</td></tr>';
                    } else {
                        uniqueRows.forEach(eval => {
                            const enseignant = eval.nom_enseignant && eval.nom_enseignant.trim() !== '' ?
                                `<i class="fas fa-user-tie"></i> ${eval.nom_enseignant}` :
                                '<i class="fas fa-user-slash"></i> <span style="color: #6c757d; font-style: italic;">Non renseigné</span>';
                            let matiere = '';
                            if (eval.lib_ue && eval.lib_ecue && eval.lib_ecue !== eval.lib_ue) {
                                matiere = `<strong>${eval.lib_ue}</strong> / ${eval.lib_ecue}`;
                            } else if (eval.lib_ue && eval.lib_ecue && eval.lib_ecue === eval.lib_ue) {
                                matiere = `<strong>${eval.lib_ue}</strong>`;
                            } else if (eval.lib_ue && !eval.lib_ecue) {
                                matiere = `<strong>${eval.lib_ue}</strong>`;
                            } else if (!eval.lib_ue && eval.lib_ecue) {
                                matiere = `<strong>${eval.lib_ecue}</strong>`;
                            } else {
                                matiere = 'N/A';
                            }
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${matiere}</td>
                                <td>${enseignant}</td>
                                <td>${eval.date_eval ? new Date(eval.date_eval).toLocaleDateString('fr-FR') : 'N/A'}</td>
                                <td><span class="note-value">${eval.note && !isNaN(parseFloat(eval.note)) ? parseFloat(eval.note).toFixed(2) : 'N/A'}</span></td>
                                <td>${eval.credit || 'N/A'}</td>
                            `;
                            tbody.appendChild(row);
                        });
                    }

                } catch (error) {
                    console.error('Erreur lors du chargement des détails:', error);
                    document.getElementById('evaluations-tbody').innerHTML =
                        '<tr><td colspan="5" class="px-6 py-4 text-center text-red-600">Erreur: ' + error.message + '</td></tr>';
                }
            }

            // Handle select all checkbox
            const selectAllCheckbox = document.getElementById('select-all');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.evaluation-checkbox');
                    checkboxes.forEach(checkbox => {
                        checkbox.checked = this.checked;
                    });
                });
            }

            // Handle individual checkboxes
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('evaluation-checkbox')) {
                    const allCheckboxes = document.querySelectorAll('.evaluation-checkbox');
                    const checkedCheckboxes = document.querySelectorAll('.evaluation-checkbox:checked');
                    const selectAllCheckbox = document.getElementById('select-all');
                    
                    if (selectAllCheckbox) {
                        selectAllCheckbox.checked = allCheckboxes.length === checkedCheckboxes.length;
                        selectAllCheckbox.indeterminate = checkedCheckboxes.length > 0 && checkedCheckboxes.length < allCheckboxes.length;
                    }
                }
            });

            // Handle pagination
            document.addEventListener('click', function(e) {
                if (e.target.closest('.page-item')) {
                    e.preventDefault();
                    const pageItem = e.target.closest('.page-item');
                    const page = pageItem.dataset.page;
                    
                    if (page) {
                        const pageInput = document.getElementById('page_num_input');
                        if (pageInput) {
                            pageInput.value = page;
                        }
                        
                        const filterForm = document.getElementById('filter-form');
                        if (filterForm) {
                            filterForm.submit();
                        }
                    }
                }
            });

            // Handle recalcul des moyennes
            const recalculerBtn = document.getElementById('recalculer-moyennes');
            if (recalculerBtn) {
                recalculerBtn.addEventListener('click', async function() {
                    if (confirm('Voulez-vous recalculer toutes les moyennes générales ? Cette opération peut prendre quelques minutes.')) {
                        try {
                            recalculerBtn.disabled = true;
                            recalculerBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Calcul en cours...';
                            
                            const response = await fetch('assets/traitements/recalculer_moyennes.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                }
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                showNotification(`Moyennes recalculées avec succès ! ${result.success_count} étudiants traités.`, 'success');
                                // Recharger la page pour afficher les nouvelles moyennes
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                showNotification('Erreur lors du recalcul : ' + (result.message || 'Erreur inconnue'), 'error');
                            }
                        } catch (error) {
                            showNotification('Erreur de connexion lors du recalcul', 'error');
                        } finally {
                            recalculerBtn.disabled = false;
                            recalculerBtn.innerHTML = '<i class="fas fa-calculator mr-2"></i>Recalculer moyennes';
                        }
                    }
                });
            }

            // ===== GESTION DES ÉTAPES DE LA MODALE D'ÉVALUATION =====
            let currentStep = 1;
            const totalSteps = 3;

            // Fonction pour mettre à jour l'indicateur de progression
            function updateProgressIndicator() {
                for (let i = 1; i <= totalSteps; i++) {
                    const stepElement = document.getElementById(`step-${i}`);
                    const stepText = stepElement.nextElementSibling;
                    
                    if (i < currentStep) {
                        // Étapes complétées
                        stepElement.className = 'w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-bold';
                        stepElement.innerHTML = '<i class="fas fa-check"></i>';
                        if (stepText) stepText.className = 'ml-2 text-sm font-medium text-green-600';
                    } else if (i === currentStep) {
                        // Étape actuelle
                        stepElement.className = 'w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-bold';
                        stepElement.innerHTML = i;
                        if (stepText) stepText.className = 'ml-2 text-sm font-medium text-gray-900';
                    } else {
                        // Étapes à venir
                        stepElement.className = 'w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center text-white text-sm font-bold';
                        stepElement.innerHTML = i;
                        if (stepText) stepText.className = 'ml-2 text-sm font-medium text-gray-500';
                    }
                }
            }

            // Fonction pour afficher/masquer les étapes
            function showStep(stepNumber) {
                // Masquer toutes les étapes
                for (let i = 1; i <= totalSteps; i++) {
                    const stepContent = document.getElementById(`step-${i}-content`);
                    if (stepContent) stepContent.style.display = 'none';
                }
                
                // Afficher l'étape actuelle
                const currentStepContent = document.getElementById(`step-${stepNumber}-content`);
                if (currentStepContent) currentStepContent.style.display = 'block';
                
                // Gérer les boutons
                const prevBtn = document.getElementById('prev-step');
                const nextBtn = document.getElementById('next-step');
                const submitBtn = document.getElementById('submit-eval');
                
                if (prevBtn) prevBtn.style.display = stepNumber > 1 ? 'block' : 'none';
                if (nextBtn) nextBtn.style.display = stepNumber < totalSteps ? 'block' : 'none';
                if (submitBtn) submitBtn.style.display = stepNumber === totalSteps ? 'block' : 'none';
                
                updateProgressIndicator();
            }

            // Gestionnaire pour le bouton "Suivant"
            const nextStepBtn = document.getElementById('next-step');
            if (nextStepBtn) {
                nextStepBtn.addEventListener('click', function() {
                    if (currentStep === 1) {
                        // Validation de l'étape 1 : vérifier que l'étudiant est sélectionné
                        const numero = document.getElementById('numero').value.trim();
                        if (!numero) {
                            showNotification('Veuillez saisir le numéro de carte de l\'étudiant', 'error');
                            return;
                        }
                        // Ici on pourrait ajouter une validation AJAX pour vérifier que l'étudiant existe
                    } else if (currentStep === 2) {
                        // Validation de l'étape 2 : vérifier que le semestre est sélectionné
                        const semestre = document.getElementById('semestre').value;
                        if (!semestre) {
                            showNotification('Veuillez sélectionner un semestre', 'error');
                            return;
                        }
                    }
                    
                    if (currentStep < totalSteps) {
                        currentStep++;
                        showStep(currentStep);
                    }
                });
            }

            // Gestionnaire pour le bouton "Précédent"
            const prevStepBtn = document.getElementById('prev-step');
            if (prevStepBtn) {
                prevStepBtn.addEventListener('click', function() {
                    if (currentStep > 1) {
                        currentStep--;
                        showStep(currentStep);
                    }
                });
            }

            // Réinitialiser les étapes lors de l'ouverture de la modale
            const evalModalSteps = document.getElementById('eval-modal');
            if (evalModalSteps) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                            if (evalModalSteps.classList.contains('flex')) {
                                // Modale ouverte, réinitialiser les étapes
                                currentStep = 1;
                                showStep(1);
                                
                                // Réinitialiser les champs
                                document.getElementById('numero').value = '';
                                document.getElementById('nom').value = '';
                                document.getElementById('prenom').value = '';
                                document.getElementById('promotion').value = '';
                                document.getElementById('niveau').value = '';
                                document.getElementById('semestre').innerHTML = '<option value="">Sélectionnez un semestre...</option>';
                                document.getElementById('notes-list').innerHTML = '';
                                document.getElementById('semester-average-section').style.display = 'none';
                            }
                        }
                    });
                });
                
                observer.observe(evalModalSteps, { attributes: true });
            }

            // Amélioration de la recherche d'étudiant avec autocomplétion
            const numeroInputSteps = document.getElementById('numero');
            if (numeroInputSteps) {
                let searchTimeout;
                
                numeroInputSteps.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const numero = this.value.trim();
                    
                    if (numero.length >= 3) {
                        searchTimeout = setTimeout(() => {
                            // Simuler une recherche AJAX (à implémenter selon vos besoins)
                            // Pour l'instant, on utilise la logique existante
                            if (numero) {
                                // Déclencher la recherche d'étudiant
                                const event = new Event('blur');
                                numeroInputSteps.dispatchEvent(event);
                            }
                        }, 500);
                    }
                });
            }
        });
    </script>
</body>

</html>