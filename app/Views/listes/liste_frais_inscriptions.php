<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'frais_inscriptions') {
    return;
}

require_once __DIR__ . '/../../config/config.php';

$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

// --- Recherche, filtres et pagination ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$niveau_filter = isset($_GET['niveau']) ? $_GET['niveau'] : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if ($search !== '') {
    $where_conditions[] = "(ne.lib_niv_etd LIKE ? OR CONCAT(aa.date_debut, ' - ', aa.date_fin) LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

if ($niveau_filter !== '') {
    $where_conditions[] = "f.id_niv_etd = ?";
    $params[] = $niveau_filter;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Compter le total pour la pagination
$sql_count = "SELECT COUNT(*) 
              FROM frais_inscription f 
              JOIN niveau_etude ne ON ne.id_niv_etd = f.id_niv_etd 
              JOIN annee_academique aa ON aa.id_ac = f.id_ac 
              $where_clause";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_tarifs = $stmt_count->fetchColumn();
$total_pages = max(1, ceil($total_tarifs / $per_page));

// Récupérer les tarifs filtrés et paginés
$sql = "SELECT f.*, ne.lib_niv_etd, aa.date_debut, aa.date_fin 
        FROM frais_inscription f 
        JOIN niveau_etude ne ON ne.id_niv_etd = f.id_niv_etd 
        JOIN annee_academique aa ON aa.id_ac = f.id_ac 
        $where_clause 
        ORDER BY f.id_frais 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tarifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Récupération de l'année en cours
$sql = "SELECT id_ac, date_debut, date_fin FROM annee_academique WHERE statut_annee = 'En cours'";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$annee = $stmt->fetch(PDO::FETCH_ASSOC);
$dateDebut = new DateTime($annee['date_debut']);
$dateFin = new DateTime($annee['date_fin']);
$date = $dateDebut->format('Y') . '-' . $dateFin->format('Y');

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
            $_SESSION['success'] = "Les frais d'inscription ont été mis à jour avec succès";
        } else {
            //Insertion dans la table frais inscription
            $sql = "INSERT INTO frais_inscription (id_niv_etd, id_ac, montant) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id_niveau_etd, $id_ac, $tarifs]);
            $_SESSION['success'] = "Frais d'inscriptions ajoutés avec succès";
        }
    } else {
        $_SESSION['error'] = "Le niveau d'étude sélectionné n'existe pas";
    }
    // Redirection pour éviter le repost
    header('Location: ?page=parametres_generaux&liste=frais_inscriptions');
    exit;
}

// ... Ajout du traitement PHP pour la suppression multiple ...
if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
    $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM frais_inscription WHERE id_frais IN ($placeholders)");
        $stmt->execute($ids);
        $_SESSION['success'] = count($ids) . " frais supprimé(s) avec succès.";
    } else {
        $_SESSION['error'] = "Aucun frais sélectionné.";
    }
    header('Location: ?page=parametres_generaux&liste=frais_inscriptions');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Frais d'Inscription - GSCV+</title>
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
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(26, 82, 118, 0.1), 0 10px 10px -5px rgba(26, 82, 118, 0.04);
        }
        .modal-transition {
            transition: all 0.3s ease-in-out;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        .btn-icon {
            transition: all 0.2s ease-in-out;
        }
        .btn-icon:hover {
            transform: scale(1.1);
        }
        .bg-gradient {
            background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%);
        }
    </style>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">
        <!-- Contenu principal -->
        <main class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

            <!-- En-tête de page -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                <div class="border-l-4 border-primary bg-white rounded-r-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="bg-primary/10 rounded-lg p-3 mr-4">
                                <i class="fas fa-money-bill-wave text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Frais d'Inscription</h1>
                                <p class="text-gray-600">Gestion des frais d'inscription par niveau</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-right">
                                <div class="text-sm text-gray-500">Connecté en tant que</div>
                                <div class="font-semibold text-gray-900"><?php echo $fullname; ?></div>
                                <div class="text-sm text-primary"><?php echo $lib_user_type; ?></div>
                            </div>
                            <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-bold text-lg">
                                <?php echo substr($fullname, 0, 1); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6 stat-card transition-all duration-300">
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-lg p-3 mr-4">
                            <i class="fas fa-money-bill-wave text-2xl text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Tarifs</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total_tarifs; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6 stat-card transition-all duration-300">
                    <div class="flex items-center">
                        <div class="bg-blue-100 rounded-lg p-3 mr-4">
                            <i class="fas fa-calendar-alt text-2xl text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-600">Année en Cours</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $date; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Barre d'outils -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center space-y-4 lg:space-y-0 lg:space-x-4">
                    <!-- Recherche -->
                    <div class="flex-1 max-w-md">
                        <form method="GET" class="flex">
                            <input type="hidden" name="page" value="parametres_generaux">
                            <input type="hidden" name="liste" value="frais_inscriptions">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Rechercher par niveau ou année..."
                                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <button type="submit" class="ml-3 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200">
                                <i class="fas fa-search mr-2"></i>Rechercher
                            </button>
                        </form>
                    </div>

                    <!-- Filtres -->
                    <div class="flex-1 max-w-md">
                        <form method="GET" class="flex">
                            <input type="hidden" name="page" value="parametres_generaux">
                            <input type="hidden" name="liste" value="frais_inscriptions">
                            <?php if ($search !== ''): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                            <select name="niveau" onchange="this.form.submit()" class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                                <option value="">Tous les niveaux</option>
                                <?php
                                $niveaux = $pdo->prepare("SELECT * FROM niveau_etude ORDER BY lib_niv_etd");
                                $niveaux->execute();
                                $niveaux_list = $niveaux->fetchAll();
                                foreach ($niveaux_list as $niv) {
                                    $selected = ($niveau_filter == $niv['id_niv_etd']) ? 'selected' : '';
                                    echo "<option value=\"{$niv['id_niv_etd']}\" $selected>{$niv['lib_niv_etd']}</option>";
                                }
                                ?>
                            </select>
                        </form>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex space-x-3">
                        <button onclick="openAddModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-200 flex items-center">
                            <i class="fas fa-plus mr-2"></i>Ajouter
                        </button>
                        <button onclick="deleteSelected()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors duration-200 flex items-center">
                            <i class="fas fa-trash mr-2"></i>Supprimer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tableau -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N°</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Niveau d'étude</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année académique</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($tarifs) === 0): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    <div class="flex flex-col items-center py-8">
                                        <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                                        <p class="text-lg font-medium">Aucun tarif trouvé</p>
                                        <p class="text-sm">Essayez de modifier vos critères de recherche</p>
                                    </div>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($tarifs as $index => $tarif): ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" name="delete_selected_ids[]" value="<?php echo $tarif['id_frais']; ?>" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo $offset + $index + 1; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-3">
                                                <i class="fas fa-graduation-cap mr-1"></i>
                                            </span>
                                            <?php echo htmlspecialchars($tarif['lib_niv_etd']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <div class="flex items-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-3">
                                                <i class="fas fa-calendar mr-1"></i>
                                            </span>
                                            <?php
                                            $dateDebut = new DateTime($tarif['date_debut']);
                                            $dateFin = new DateTime($tarif['date_fin']);
                                            echo $dateDebut->format('Y') . ' - ' . $dateFin->format('Y');
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                            <i class="fas fa-money-bill mr-1"></i>
                                            <?php echo number_format($tarif['montant'], 0, ',', ' '); ?> FCFA
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="openEditModal(<?php echo $tarif['id_frais']; ?>, <?php echo $tarif['id_niv_etd']; ?>, <?php echo $tarif['montant']; ?>)" 
                                                    class="text-indigo-600 hover:text-indigo-900 btn-icon">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteFrais(<?php echo $tarif['id_frais']; ?>)" 
                                                    class="text-red-600 hover:text-red-900 btn-icon">
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                            <a href="?page=parametres_generaux&liste=frais_inscriptions&search=<?php echo urlencode($search); ?>&niveau=<?php echo urlencode($niveau_filter); ?>&page=<?php echo $page - 1; ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Précédent
                            </a>
                            <?php endif; ?>
                            <?php if ($page < $total_pages): ?>
                            <a href="?page=parametres_generaux&liste=frais_inscriptions&search=<?php echo urlencode($search); ?>&niveau=<?php echo urlencode($niveau_filter); ?>&page=<?php echo $page + 1; ?>" 
                               class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Suivant
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Affichage de <span class="font-medium"><?php echo ($offset + 1); ?></span> à <span class="font-medium"><?php echo min($offset + $per_page, $total_tarifs); ?></span> sur <span class="font-medium"><?php echo $total_tarifs; ?></span> résultats
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=parametres_generaux&liste=frais_inscriptions&search=<?php echo urlencode($search); ?>&niveau=<?php echo urlencode($niveau_filter); ?>&page=<?php echo $i; ?>" 
                                       class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i == $page ? 'z-10 bg-primary border-primary text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal Ajout -->
    <div id="fraisModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Ajouter des frais d'inscription</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="p-6">
                <input type="hidden" name="page" value="parametres_generaux">
                <input type="hidden" name="liste" value="frais_inscriptions">
                <div class="mb-4">
                    <label for="id_niv_etd" class="block text-sm font-medium text-gray-700 mb-2">Niveau d'étude</label>
                    <select name="id_niv_etd" id="id_niv_etd" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
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
                <div class="mb-4">
                    <label for="tarifs" class="block text-sm font-medium text-gray-700 mb-2">Montant (FCFA)</label>
                    <input type="number" 
                           name="tarifs" 
                           id="tarifs" 
                           required 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Modification -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Modifier les frais d'inscription</h2>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="./traitements/modifier_frais_inscription.php" class="p-6">
                <input type="hidden" name="page" value="parametres_generaux">
                <input type="hidden" name="liste" value="frais_inscriptions">
                <input type="hidden" id="edit_id_frais" name="id_frais">
                <div class="mb-4">
                    <label for="edit_id_niv_etd" class="block text-sm font-medium text-gray-700 mb-2">Niveau d'étude</label>
                    <select name="id_niv_etd" id="edit_id_niv_etd" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
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
                <div class="mb-4">
                    <label for="edit_tarifs" class="block text-sm font-medium text-gray-700 mb-2">Montant (FCFA)</label>
                    <input type="number" 
                           name="tarifs" 
                           id="edit_tarifs" 
                           required 
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        Annuler
                    </button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Gestion des modales
        function openAddModal() {
            document.getElementById('fraisModal').classList.remove('hidden');
            document.getElementById('fraisModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('fraisModal').classList.add('hidden');
            document.getElementById('fraisModal').classList.remove('flex');
        }

        function openEditModal(id, niveauId, montant) {
            document.getElementById('edit_id_frais').value = id;
            document.getElementById('edit_id_niv_etd').value = niveauId;
            document.getElementById('edit_tarifs').value = montant;
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }

        // Gestion de la sélection multiple
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="delete_selected_ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Suppression d'un frais
        function deleteFrais(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ces frais d\'inscription ?')) {
                window.location.href = 'supprimer_frais_inscription.php?id=' + id;
            }
        }

        // Suppression multiple
        function deleteSelected() {
            const selected = document.querySelectorAll('input[name="delete_selected_ids[]"]:checked');
            if (selected.length === 0) {
                alert('Veuillez sélectionner au moins un frais à supprimer.');
                return;
            }
            
            if (confirm(`Êtes-vous sûr de vouloir supprimer ${selected.length} frais ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="delete_selected_ids" value="${Array.from(selected).map(cb => cb.value).join(',')}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Fermer les modales en cliquant à l'extérieur
        document.getElementById('fraisModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>