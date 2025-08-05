<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Controllers/SauvegardesEtRestaurationsController.php';

// Initialisation du contrôleur
$sauvegardeController = new SauvegardesEtRestaurationsController($pdo);

// Vérification des permissions (seul l'administrateur peut faire des sauvegardes)
$stmt = $pdo->prepare("
    SELECT gu.lib_gu 
    FROM posseder p 
    JOIN groupe_utilisateur gu ON p.id_gu = gu.id_gu 
    WHERE p.id_util = ? AND gu.lib_gu = 'Administrateur plateforme'
");
$stmt->execute([$_SESSION['user_id']]);
$is_admin = $stmt->fetch();

// Récupération des sauvegardes existantes via le contrôleur
$backups = $sauvegardeController->index();

// Récupération des informations de l'utilisateur
$stmt = $pdo->prepare("
    SELECT u.login_utilisateur, 
           CASE 
               WHEN e.id_ens IS NOT NULL THEN CONCAT(e.nom_ens, ' ', e.prenoms_ens)
               WHEN pa.id_personnel_adm IS NOT NULL THEN CONCAT(pa.nom_personnel_adm, ' ', pa.prenoms_personnel_adm)
               WHEN et.num_etd IS NOT NULL THEN CONCAT(et.nom_etd, ' ', et.prenom_etd)
           END as fullname,
           gu.lib_gu as lib_user_type
    FROM utilisateur u
    LEFT JOIN enseignants e ON u.id_utilisateur = e.id_ens
    LEFT JOIN personnel_administratif pa ON u.id_utilisateur = pa.id_personnel_adm
    LEFT JOIN etudiants et ON u.id_utilisateur = et.num_etd
    JOIN posseder p ON u.id_utilisateur = p.id_util
    JOIN groupe_utilisateur gu ON p.id_gu = gu.id_gu
    WHERE u.id_utilisateur = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$fullname = $user['fullname'] ?? '';
$lib_user_type = $user['lib_user_type'] ?? '';

// Récupération des messages de session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Nettoyage des messages de session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    return $bytes;
}
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sauvegarde et Restauration des Données</title>
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">


            <!-- Messages d'alerte -->
            <?php if ($success_message): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 animate-slide-up">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Succès</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 animate-slide-up">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Erreur</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation par onglets -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8 animate-slide-up">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                        <button class="tab-btn active py-4 px-1 border-b-2 border-primary text-primary font-medium text-sm whitespace-nowrap" 
                                data-tab="backups" onclick="switchTab('backups')">
                            <i class="fas fa-cloud-upload-alt mr-2"></i>
                            Sauvegardes
                        </button>
                        <button class="tab-btn py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm whitespace-nowrap" 
                                data-tab="restore" onclick="switchTab('restore')">
                            <i class="fas fa-undo mr-2"></i>
                            Restauration
                        </button>
                        <button class="tab-btn py-4 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm whitespace-nowrap" 
                                data-tab="settings" onclick="switchTab('settings')">
                            <i class="fas fa-cog mr-2"></i>
                            Paramètres
                        </button>
                    </nav>
                </div>

                <!-- Contenu des onglets -->
                <div class="p-6">
                    <!-- Onglet Sauvegardes -->
                    <div id="backups" class="tab-content active">
                        <?php if ($is_admin): ?>
                            <!-- Section création de sauvegarde -->
                            <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-6 mb-8">
                                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-plus-circle text-primary mr-2"></i>
                                    Créer une nouvelle sauvegarde
                                </h2>
                                <form action="assets/traitements/backup_restore.php" method="POST" class="space-y-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label for="backup_name" class="block text-sm font-medium text-gray-700 mb-2">
                                                Nom de la sauvegarde
                                            </label>
                                            <input type="text" id="backup_name" name="backup_name" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                                   placeholder="Ex: Sauvegarde_<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div>
                                            <label for="backup_type" class="block text-sm font-medium text-gray-700 mb-2">
                                                Type de sauvegarde
                                            </label>
                                            <select id="backup_type" name="backup_type" 
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                                <option value="full">Sauvegarde complète</option>
                                                <option value="data_only">Données seulement</option>
                                                <option value="structure_only">Structure seulement</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="backup_description" class="block text-sm font-medium text-gray-700 mb-2">
                                            Description (optionnel)
                                        </label>
                                        <textarea id="backup_description" name="backup_description" rows="3"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                                  placeholder="Décrivez le contexte de cette sauvegarde..."></textarea>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" name="create_backup" 
                                                class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                            <i class="fas fa-play mr-2"></i>
                                            Créer la sauvegarde
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Liste des sauvegardes -->
                            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        <i class="fas fa-archive mr-2 text-primary"></i>
                                        Sauvegardes existantes
                                    </h3>
                                </div>
                                
                                <?php if (!empty($backups)): ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Nom du fichier
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Taille
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Date de création
                                                    </th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php foreach ($backups as $backup): ?>
                                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="flex items-center">
                                                                <i class="fas fa-file-archive text-primary mr-3"></i>
                                                                <div>
                                                                    <div class="text-sm font-medium text-gray-900">
                                                                        <?php echo htmlspecialchars($backup['nom_sauvegarde'] ?? 'Fichier inconnu'); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <?php echo formatFileSize($backup['taille_fichier'] ); ?>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            <div class="flex items-center">
                                                                <i class="fas fa-clock mr-2 text-gray-400"></i>
                                                                <?php 
                                                                echo $backup['date_creation'] ? date('d/m/Y H:i', strtotime($backup['date_creation'])) : 'Date inconnue';
                                                                ?>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                            <a href="assets/traitements/backup_restore.php?download=<?php echo urlencode($backup['nom_fichier']); ?>" 
                                                               class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-accent hover:bg-green-600 transition-colors duration-200">
                                                                <i class="fas fa-download mr-2"></i>
                                                                Télécharger
                                                            </a>
                                                            <button onclick="confirmRestore('<?php echo htmlspecialchars($backup['nom_fichier']); ?>')" 
                                                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-warning hover:bg-yellow-600 transition-colors duration-200">
                                                                <i class="fas fa-undo mr-2"></i>
                                                                Restaurer
                                                            </button>
                                                            <button onclick="confirmDelete('<?php echo htmlspecialchars($backup['nom_fichier']); ?>')" 
                                                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-danger hover:bg-red-600 transition-colors duration-200">
                                                                <i class="fas fa-trash mr-2"></i>
                                                                Supprimer
                                                            </button>
                                                           
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="px-6 py-12 text-center">
                                        <i class="fas fa-database text-4xl text-gray-400 mb-4"></i>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune sauvegarde trouvée</h3>
                                        <p class="text-gray-500">Créez votre première sauvegarde pour sécuriser vos données.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                                <i class="fas fa-lock text-4xl text-yellow-500 mb-4"></i>
                                <h3 class="text-lg font-medium text-yellow-800 mb-2">Accès restreint</h3>
                                <p class="text-yellow-700">Seuls les administrateurs peuvent gérer les sauvegardes.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Onglet Restauration -->
                    <div id="restore" class="tab-content hidden">
                        <?php if ($is_admin): ?>
                            <div class="bg-gradient-to-r from-yellow-50 to-yellow-100 rounded-lg p-6 mb-8">
                                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-upload text-warning mr-2"></i>
                                    Restaurer depuis un fichier
                                </h2>
                                <form action="assets/traitements/backup_restore.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                                    <div>
                                        <label for="restore_file" class="block text-sm font-medium text-gray-700 mb-2">
                                            Fichier de sauvegarde (.sql)
                                        </label>
                                        <input type="file" id="restore_file" name="restore_file" accept=".sql" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-warning focus:border-transparent" required>
                                        <p class="text-sm text-gray-500 mt-1">Formats acceptés: .sql (max 50MB)</p>
                                    </div>
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-exclamation-triangle text-red-400"></i>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-red-800">Attention</h3>
                                                <div class="mt-2 text-sm text-red-700">
                                                    La restauration remplacera toutes les données actuelles. Cette action est irréversible.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button type="submit" name="restore_backup" 
                                                class="px-6 py-3 bg-warning text-white rounded-lg hover:bg-yellow-600 transition-colors duration-200 flex items-center"
                                                onclick="return confirm('Êtes-vous sûr de vouloir restaurer cette sauvegarde ? Cette action est irréversible.')">
                                            <i class="fas fa-undo mr-2"></i>
                                            Restaurer la base de données
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                                <i class="fas fa-lock text-4xl text-yellow-500 mb-4"></i>
                                <h3 class="text-lg font-medium text-yellow-800 mb-2">Accès restreint</h3>
                                <p class="text-yellow-700">Seuls les administrateurs peuvent restaurer les sauvegardes.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Onglet Paramètres -->
                    <div id="settings" class="tab-content hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Configuration automatique -->
                            <div class="bg-white rounded-lg border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-robot text-primary mr-2"></i>
                                    Sauvegarde automatique
                                </h3>
                                <form class="space-y-4">
                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox" class="rounded border-gray-300 text-primary focus:ring-primary">
                                            <span class="ml-3 text-sm text-gray-700">Activer les sauvegardes automatiques</span>
                                        </label>
                                    </div>
                                    <div>
                                        <label for="auto_frequency" class="block text-sm font-medium text-gray-700 mb-2">
                                            Fréquence
                                        </label>
                                        <select id="auto_frequency" 
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                            <option value="daily">Quotidienne</option>
                                            <option value="weekly">Hebdomadaire</option>
                                            <option value="monthly">Mensuelle</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="retention_period" class="block text-sm font-medium text-gray-700 mb-2">
                                            Période de rétention (jours)
                                        </label>
                                        <input type="number" id="retention_period" value="30" min="1" max="365"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent">
                                    </div>
                                    <button type="submit" 
                                            class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                        Sauvegarder la configuration
                                    </button>
                                </form>
                            </div>

                            <!-- Informations système -->
                            <div class="bg-white rounded-lg border border-gray-200 p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                    <i class="fas fa-info-circle text-primary mr-2"></i>
                                    Informations système
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Espace disque utilisé:</span>
                                        <span class="text-sm text-gray-900">2.4 GB / 10 GB</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-primary h-2 rounded-full" style="width: 24%"></div>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Dernière sauvegarde:</span>
                                        <span class="text-sm text-gray-900"><?php 
                                        if (!empty($backups)) {
                                            $lastBackupDate = $backups[0]['created_at'] ?? $backups[0]['date_creation'] ?? $backups[0]['date'] ?? null;
                                            echo $lastBackupDate ? date('d/m/Y H:i', strtotime($lastBackupDate)) : 'Date inconnue';
                                        } else {
                                            echo 'Aucune';
                                        }
                                        ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Taille de la base:</span>
                                        <span class="text-sm text-gray-900">156 MB</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm font-medium text-gray-600">Version MySQL:</span>
                                        <span class="text-sm text-gray-900">8.0.28</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-bounce-in">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-warning/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-exclamation-triangle text-warning text-lg"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900" id="modalTitle">Confirmation</h3>
                </div>
                <p class="text-gray-600 mb-6" id="modalMessage">
                    Êtes-vous sûr de vouloir effectuer cette action ?
                </p>
                <div class="flex gap-3 justify-end">
                    <button onclick="closeModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Annuler
                    </button>
                    <button id="confirmButton" 
                            class="px-4 py-2 bg-warning text-white rounded-lg hover:bg-yellow-600 transition-colors">
                        Confirmer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gestion des onglets
        function switchTab(tabName) {
            // Masquer tous les contenus d'onglets
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
                content.classList.remove('active');
            });
            
            // Désactiver tous les boutons d'onglets
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active', 'border-primary', 'text-primary');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Activer l'onglet sélectionné
            document.getElementById(tabName).classList.remove('hidden');
            document.getElementById(tabName).classList.add('active');
            
            // Activer le bouton d'onglet correspondant
            const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
            activeBtn.classList.add('active', 'border-primary', 'text-primary');
            activeBtn.classList.remove('border-transparent', 'text-gray-500');
        }

        // Gestion du modal de confirmation
        function showModal(title, message, confirmAction) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('confirmButton').onclick = confirmAction;
            document.getElementById('confirmModal').classList.remove('hidden');
            document.getElementById('confirmModal').classList.add('flex');
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            document.getElementById('confirmModal').classList.remove('flex');
        }

        // Confirmation de restauration
        function confirmRestore(filename) {
            showModal(
                'Confirmer la restauration',
                `Êtes-vous sûr de vouloir restaurer la sauvegarde "${filename}" ? Cette action remplacera toutes les données actuelles.`,
                function() {
                    window.location.href = `assets/traitements/backup_restore.php?restore=${encodeURIComponent(filename)}`;
                }
            );
        }

        // Confirmation de suppression
        function confirmDelete(filename) {
            showModal(
                'Confirmer la suppression',
                `Êtes-vous sûr de vouloir supprimer la sauvegarde "${filename}" ? Cette action est irréversible.`,
                function() {
                    window.location.href = `assets/traitements/backup_restore.php?delete=${encodeURIComponent(filename)}`;
                }
            );
        }

        // Animation au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observer les éléments animés
        document.querySelectorAll('.animate-slide-up').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            observer.observe(el);
        });

        // Auto-hide success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.bg-green-50, .bg-red-50');
            alerts.forEach(alert => {
                if (alert.querySelector('.text-green-700, .text-red-700')) {
                    setTimeout(() => {
                        alert.style.opacity = '0';
                        alert.style.visibility = 'hidden';
                        setTimeout(() => {
                            alert.remove();
                        }, 300);
                    }, 5000);
                }
            });
        });
    </script>

</body>
</html>