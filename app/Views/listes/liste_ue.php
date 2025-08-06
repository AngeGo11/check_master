<?php
// Vérifier si on est dans le bon contexte
if (!isset($_GET['liste']) || $_GET['liste'] !== 'ue') {
    return;
}

require_once __DIR__ . '/../../config/config.php';


$fullname = $_SESSION['user_fullname'];
$lib_user_type = $_SESSION['lib_user_type'];

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = '';
$params = [];

$perPage = 8; // Nombre d'éléments par page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

if ($search !== '') {
    $where = "WHERE lib_ue LIKE :search OR id_ue LIKE :search";
    $params[':search'] = "%$search%";
}

// Récupération des UE
$sql = "SELECT * FROM ue $where ORDER BY id_ue LIMIT $offset, $perPage";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}
$stmt->execute();
$ues = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countSql = "SELECT COUNT(*) FROM ue $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalUes = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalUes / $perPage));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement des actions
    if (isset($_POST['lib_ue'])) {
        $lib_ue = trim($_POST['lib_ue']);
        $credit_ue = intval($_POST['credit_ue']);
        $volume_horaire = intval($_POST['volume_horaire']);
        $niveau = intval($_POST['niveau']);
        $semestre = intval($_POST['semestre']);
        $id_ens = !empty($_POST['id_ens']) ? intval($_POST['id_ens']) : null;

        // Validation des données
        if (empty($lib_ue)) {
            $_SESSION['error'] = "Le libellé de l'UE ne peut pas être vide.";
        } elseif ($credit_ue < 1 || $credit_ue > 30) {
            $_SESSION['error'] = "Le nombre de crédits doit être entre 1 et 30.";
        } elseif ($volume_horaire < 1) {
            $_SESSION['error'] = "Le volume horaire doit être supérieur à 0.";
        } elseif ($niveau <= 0) {
            $_SESSION['error'] = "Veuillez sélectionner un niveau.";
        } elseif ($semestre <= 0) {
            $_SESSION['error'] = "Veuillez sélectionner un semestre.";
        } else {
            // Génération du code UE unique
            try {
                $code_ue = genererCodeUEUnique($pdo, $niveau, $semestre);

                $sql = "INSERT INTO ue (id_ue, lib_ue, credit_ue, volume_horaire, id_niv_etd, id_semestre, id_ens) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$code_ue, $lib_ue, $credit_ue, $volume_horaire, $niveau, $semestre, $id_ens]);
                $_SESSION['success'] = "UE ajoutée avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'UE.";
            } catch (Exception $e) {
                $_SESSION['error'] = "Erreur lors de la génération du code UE : " . $e->getMessage();
            }
        }

        // Redirection pour éviter la soumission multiple
        header('Location: ?page=parametres_generaux&liste=ue');
        exit();
    }

    // Suppression simple
    if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id_ue'])) {
        $id_ue = trim($_POST['id_ue']);
        if (!empty($id_ue)) {
            try {
                $sql = "DELETE FROM ue WHERE id_ue = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_ue]);
                $_SESSION['success'] = "UE supprimée avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression de l'UE.";
            }
        } else {
            $_SESSION['error'] = "ID de l'UE invalide.";
        }
        header('Location: ?page=parametres_generaux&liste=ue');
        exit;
    }

    // Ajout du traitement PHP pour la suppression multiple
    if (isset($_POST['delete_selected_ids']) && is_array($_POST['delete_selected_ids'])) {
        $ids = array_filter($_POST['delete_selected_ids'], 'strlen');
        if (!empty($ids)) {
            try {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $pdo->prepare("DELETE FROM ue WHERE id_ue IN ($placeholders)");
                $stmt->execute($ids);
                $_SESSION['success'] = count($ids) . " UE supprimée(s) avec succès.";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Erreur lors de la suppression multiple.";
            }
        } else {
            $_SESSION['error'] = "Aucune UE sélectionnée.";
        }
        header('Location: ?page=parametres_generaux&liste=ue');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Unités d'Enseignement - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2980b9',
                        accent: '#27ae60',
                        warning: '#f39c12',
                        danger: '#e74c3c',
                        success: '#27ae60'
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.3s ease-in-out',
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
                                <i class="fas fa-book text-2xl text-primary"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-gray-900 mb-2">Liste des Unités d'Enseignement</h1>
                                <p class="text-gray-600">Gestion des UE du système</p>
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

            <!-- KPI Card -->
            <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mb-8 animate-slide-up">
                <div class="stat-card bg-white rounded-xl shadow-lg border-l-4 border-primary-light overflow-hidden transition-all duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600 mb-1">Total des UE</p>
                                <p class="text-3xl font-bold text-gray-900"><?php echo number_format($totalUes); ?></p>
                            </div>
                            <div class="bg-primary-light/10 rounded-full p-4">
                                <i class="fas fa-book text-2xl text-primary-light"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Liste des UE -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                <!-- Barre d'actions -->
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
                        <!-- Bouton de retour -->
                        <a href="?page=parametres_generaux"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Retour aux paramètres
                        </a>

                        <!-- Recherche -->
                        <div class="flex-1 w-full lg:w-auto">
                            <form method="GET" class="flex gap-3">
                                <input type="hidden" name="page" value="parametres_generaux">
                                <input type="hidden" name="liste" value="ue">
                                <div class="relative flex-1">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                    <input type="text"
                                        name="search"
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                        placeholder="Rechercher une UE..."
                                        value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <button type="submit"
                                    class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                    <i class="fas fa-search mr-2"></i>
                                    Rechercher
                                </button>
                            </form>
                        </div>

                        <!-- Bouton d'ajout -->
                        <button onclick="showAddModal()"
                            class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-green-600 transition-colors duration-200 flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter une UE
                        </button>
                    </div>
                </div>

                <!-- Messages d'alerte -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-lg fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-green-800"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="mx-6 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg fade-in">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <span class="text-red-800"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bouton de suppression multiple -->
                <div class="px-6 pt-4">
                    <form id="deleteMultipleForm" method="POST">
                        <input type="hidden" name="page" value="parametres_generaux">
                        <input type="hidden" name="liste" value="ue">
                        <input type="hidden" name="delete_multiple" value="1">
                        <input type="hidden" name="delete_selected_ids[]" id="delete_selected_ids">
                        <button type="button" 
                            class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 flex items-center"
                            id="bulkDeleteBtn">
                            <i class="fas fa-trash mr-2"></i>
                            Supprimer la sélection
                        </button>
                    </form>
                </div>

                <!-- Tableau des données -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-primary focus:ring-primary">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Code UE
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Libellé
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Crédits
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Volume Horaire
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($ues)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-gray-500 text-lg">
                                                <?php echo empty($search) ? 'Aucune UE trouvée.' : 'Aucun résultat pour "' . htmlspecialchars($search) . '".'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ues as $ue): ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input type="checkbox" 
                                                class="row-checkbox rounded border-gray-300 text-primary focus:ring-primary" 
                                                value="<?php echo htmlspecialchars($ue['id_ue']); ?>">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($ue['id_ue']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($ue['lib_ue']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($ue['credit_ue']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($ue['volume_horaire']); ?>h
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button class="btn-icon text-blue-600 hover:text-blue-900" 
                                                    title="Modifier"
                                                    onclick="editUE('<?php echo htmlspecialchars($ue['id_ue'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($ue['lib_ue'], ENT_QUOTES); ?>', <?php echo $ue['credit_ue']; ?>, <?php echo $ue['volume_horaire']; ?>, <?php echo $ue['id_niv_etd']; ?>, <?php echo $ue['id_semestre']; ?>, <?php echo $ue['id_ens'] ?: 'null'; ?>)">
                                                    <i class="fas fa-pen"></i>
                                                </button>
                                                <button class="btn-icon text-red-600 hover:text-red-900" 
                                                    title="Supprimer"
                                                    onclick="deleteUE('<?php echo htmlspecialchars($ue['id_ue'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($ue['lib_ue'], ENT_QUOTES); ?>')">
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
                <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Page <?php echo $page; ?> sur <?php echo $totalPages; ?>
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=parametres_generaux&liste=ue&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Précédent
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <a href="?page=parametres_generaux&liste=ue&search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" 
                                        class="px-3 py-2 text-sm font-medium <?php echo $page == $i ? 'text-white bg-primary' : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=parametres_generaux&liste=ue&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" 
                                        class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Suivant
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal d'ajout/modification -->
    <div id="ueModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white modal-transition">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Ajouter une UE</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="ueForm" method="POST">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="liste" value="ue">
                    <input type="hidden" id="id_ue" name="id_ue">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label for="lib_ue" class="block text-sm font-medium text-gray-700 mb-2">Libellé de l'UE :</label>
                            <input type="text" 
                                id="lib_ue" 
                                name="lib_ue" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="credit_ue" class="block text-sm font-medium text-gray-700 mb-2">Crédits :</label>
                            <input type="number" 
                                id="credit_ue" 
                                name="credit_ue" 
                                min="1" 
                                max="30" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="volume_horaire" class="block text-sm font-medium text-gray-700 mb-2">Volume horaire (heures) :</label>
                            <input type="number" 
                                id="volume_horaire" 
                                name="volume_horaire" 
                                min="1" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="niveau" class="block text-sm font-medium text-gray-700 mb-2">Niveau :</label>
                            <select id="niveau" 
                                name="niveau" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Sélectionner un niveau</option>
                                <?php
                                $niveaux = $pdo->query("SELECT * FROM niveau_etude ORDER BY id_niv_etd")->fetchAll();
                                foreach ($niveaux as $niveau) {
                                    echo '<option value="' . $niveau['id_niv_etd'] . '">' . htmlspecialchars($niveau['lib_niv_etd']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="semestre" class="block text-sm font-medium text-gray-700 mb-2">Semestre :</label>
                            <select id="semestre" 
                                name="semestre" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Sélectionner un semestre</option>
                                <?php
                                $semestres = $pdo->query("SELECT * FROM semestre ORDER BY id_semestre")->fetchAll();
                                foreach ($semestres as $semestre) {
                                    echo '<option value="' . $semestre['id_semestre'] . '">' . htmlspecialchars($semestre['lib_semestre']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="id_ens" class="block text-sm font-medium text-gray-700 mb-2">Enseignant (optionnel) :</label>
                            <select id="id_ens" 
                                name="id_ens"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                <option value="">Aucun enseignant assigné</option>
                                <?php
                                $enseignants = $pdo->query("SELECT * FROM enseignant ORDER BY nom_ens")->fetchAll();
                                foreach ($enseignants as $enseignant) {
                                    echo '<option value="' . $enseignant['id_ens'] . '">' . htmlspecialchars($enseignant['nom_ens'] . ' ' . $enseignant['prenom_ens']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" 
                            onclick="closeModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200">
                            Annuler
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-light transition-colors duration-200">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="confirmation-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Confirmer la suppression</h3>
                <div class="mt-2 px-7 pt-6">
                    <p class="text-sm text-gray-500" id="confirmation-text">
                        Êtes-vous sûr de vouloir supprimer cette UE ?
                    </p>
                </div>
                <div class="flex justify-center space-x-3 mt-6">
                    <button id="cancel-delete"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors duration-200">
                        Annuler
                    </button>
                    <button id="confirm-delete"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors duration-200">
                        Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let idToDelete = null;

        // Fonctions pour les modales
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Ajouter une UE';
            document.getElementById('id_ue').value = '';
            document.getElementById('lib_ue').value = '';
            document.getElementById('credit_ue').value = '';
            document.getElementById('volume_horaire').value = '';
            document.getElementById('niveau').value = '';
            document.getElementById('semestre').value = '';
            document.getElementById('id_ens').value = '';
            document.getElementById('ueModal').classList.remove('hidden');
        }

        function editUE(id, libelle, credits, volume, niveau, semestre, enseignant) {
            document.getElementById('modalTitle').textContent = 'Modifier l\'UE';
            document.getElementById('id_ue').value = id;
            document.getElementById('lib_ue').value = libelle;
            document.getElementById('credit_ue').value = credits;
            document.getElementById('volume_horaire').value = volume;
            document.getElementById('niveau').value = niveau;
            document.getElementById('semestre').value = semestre;
            document.getElementById('id_ens').value = enseignant || '';
            document.getElementById('ueModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('ueModal').classList.add('hidden');
        }

        function deleteUE(id, libelle) {
            idToDelete = id;
            document.getElementById('confirmation-text').textContent = `Êtes-vous sûr de vouloir supprimer l'UE "${libelle}" ?`;
            document.getElementById('confirmation-modal').classList.remove('hidden');
        }

        function confirmDeleteSingle() {
            // Créer un formulaire temporaire pour la suppression
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const pageInput = document.createElement('input');
            pageInput.type = 'hidden';
            pageInput.name = 'page';
            pageInput.value = 'parametres_generaux';
            
            const listeInput = document.createElement('input');
            listeInput.type = 'hidden';
            listeInput.name = 'liste';
            listeInput.value = 'ue';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id_ue';
            idInput.value = idToDelete;
            
            form.appendChild(pageInput);
            form.appendChild(listeInput);
            form.appendChild(actionInput);
            form.appendChild(idInput);
            
            document.body.appendChild(form);
            form.submit();
        }

        // Gestion de la sélection multiple
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkDeleteButton();
        });

        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkDeleteButton();
                updateSelectAll();
            });
        });

        function updateBulkDeleteButton() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            if (checkedBoxes.length > 0) {
                bulkDeleteBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                bulkDeleteBtn.classList.add('cursor-pointer');
            } else {
                bulkDeleteBtn.classList.add('opacity-50', 'cursor-not-allowed');
                bulkDeleteBtn.classList.remove('cursor-pointer');
            }
        }

        function updateSelectAll() {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            const selectAll = document.getElementById('selectAll');
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            
            if (checkedBoxes.length === 0) {
                selectAll.checked = false;
                selectAll.indeterminate = false;
            } else if (checkedBoxes.length === checkboxes.length) {
                selectAll.checked = true;
                selectAll.indeterminate = false;
            } else {
                selectAll.checked = false;
                selectAll.indeterminate = true;
            }
        }

        // Suppression multiple
        document.getElementById('bulkDeleteBtn').addEventListener('click', function() {
            const checked = document.querySelectorAll('.row-checkbox:checked');
            if (checked.length === 0) {
                alert('Veuillez sélectionner au moins une UE à supprimer.');
                return;
            }
            
            document.getElementById('confirmation-text').textContent = `Êtes-vous sûr de vouloir supprimer ${checked.length} UE sélectionnée(s) ?`;
            document.getElementById('confirmation-modal').classList.remove('hidden');
            
            document.getElementById('confirm-delete').onclick = function() {
                const checked = Array.from(document.querySelectorAll('.row-checkbox:checked'));
                const ids = checked.map(cb => cb.value);
                document.getElementById('delete_selected_ids').value = ids.join(',');
                document.getElementById('deleteMultipleForm').submit();
            };
        });

        document.getElementById('cancel-delete').onclick = function() {
            document.getElementById('confirmation-modal').classList.add('hidden');
        };

        // Fermer les modales si on clique en dehors
        window.onclick = function(event) {
            const ueModal = document.getElementById('ueModal');
            const confirmationModal = document.getElementById('confirmation-modal');
            
            if (event.target === ueModal) {
                closeModal();
            }
            if (event.target === confirmationModal) {
                document.getElementById('confirmation-modal').classList.add('hidden');
            }
        };

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            updateBulkDeleteButton();
        });
    </script>
</body>
</html>