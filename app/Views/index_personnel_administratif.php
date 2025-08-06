<?php
ob_start();
require_once __DIR__ . '/../../config/config.php'; // pour $pdo
require_once __DIR__ . '/../Controllers/MenuController.php';
require_once __DIR__ . '/../Controllers/MessageController.php';

// Vérification de connexion et du type d'utilisateur
if (!isset($_SESSION['user_id'])) {
    header('Location: /GSCV+/public/pageConnexion.php');
    exit();
}
$messageController = new MessageController($pdo);

$fullname = $_SESSION['user_fullname'] ?? 'Utilisateur';
$lib_user_type = $_SESSION['lib_user_type'] ?? 'Inconnu';
$messagesNonLus = $messageController->compterMessagesNonLus($_SESSION['user_id']);

// Page demandée
$page = isset($_GET['page']) ? basename($_GET['page']) : 'etudiants';

// Inclusion sécurisée du fichier - CORRECTION DU CHEMIN
$file_path = __DIR__ . '/' . $page . '.php';
if (!file_exists($file_path)) {
    $file_path = __DIR__ . '/etudiants.php';
}

//Récupération des traitements par groupe utilisateur
$sql = "SELECT t.titre_header, t.sous_titre_header, t.image_header 
        FROM traitement t
        WHERE t.lib_traitement = :page";

$stmt = $pdo->prepare($sql);
$stmt->execute(['page' => $page]);
$header = $stmt->fetch(PDO::FETCH_ASSOC);

// Valeurs par défaut si aucune donnée n'est trouvée
$titre_header = $header['titre_header'] ?? '';
$sous_titre_header = $header['sous_titre_header'] ?? '';
$image_header = $header['image_header'] ?? './assets/images/logo_mi_sbg.png';

$menuController = new MenuController($pdo);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Personnel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="./assets/images/logo_cm_sbg.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2563eb',
                        'primary-dark': '#1e40af',
                        'primary-lighter': '#3b82f6',
                        secondary: '#f8fafc',
                        accent: '#e2e8f0'
                    },
                    fontFamily: {
                        'primary': ['Poppins', 'sans-serif'],
                        'secondary': ['Montserrat', 'sans-serif']
                    }
                }
            }
        }
    </script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        
        /* Styles pour la sidebar moderne */
        .modern-sidebar {
            background: linear-gradient(180deg, #1a5276 0%, #163d5a 100%);
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }
        
        .sidebar-logo-section {
            background: rgba(22, 61, 90, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-logo-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-5px) rotate(2deg); }
        }
        
        .modern-menu a {
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 4px 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .modern-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
        }
        
        .modern-menu a.active {
            background: white;
            color: #1a5276;
            font-weight: 500;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        .modern-menu a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        /* Header moderne */
        .modern-header {
            background: #1a5276;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid white;
            object-fit: cover;
        }
        
        @media (max-width: 768px) {
            .sidebar-hidden {
                transform: translateX(-100%);
            }
        }
    </style>
</head>

<body class="bg-gray-50 font-primary">
    <!-- Messages de succès et d'erreur -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="fixed top-5 right-5 z-50 bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg shadow-lg animate-pulse">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-500"></i>
                    <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 hover:text-green-900 ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="fixed top-5 right-5 z-50 bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg shadow-lg animate-pulse">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-red-500"></i>
                    <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-700 hover:text-red-900 ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Mobile Sidebar Toggle -->
    <button class="fixed top-4 left-4 z-50 md:hidden bg-primary text-white p-3 rounded-lg shadow-lg hover:bg-primary-dark transition-colors" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden hidden" id="sidebarOverlay"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar Moderne -->
        <div class="fixed md:static inset-y-0 left-0 z-50 w-64 modern-sidebar sidebar-hidden md:translate-x-0" id="sidebar">
            <!-- Logo Section -->
            <div class="sidebar-logo-section p-6 text-center relative z-10">
                <img src="./assets/images/logo_cm_sbg.png" alt="MasterCheck" class="w-16 h-16 mx-auto mb-3 rounded-full bg-white bg-opacity-10 p-2">
                <h2 class="text-xl font-bold text-white">
                    <span class="text-white">CHECK</span> 
                    <span class="text-blue-200">MASTER</span>
                </h2>
                <p class="text-blue-200 text-sm mt-1 opacity-80">Personnel administratif</p>
            </div>

            <!-- Menu Navigation -->
            <div class="flex-1 overflow-y-auto py-4 modern-menu">
                <?php echo $menuController->displayMenu(); ?>
            </div>

            <!-- Footer utilisateur -->
            <div class="p-4 border-t border-white border-opacity-10 bg-black bg-opacity-10">
                <div class="text-center">
                    <div class="text-xs text-blue-200 opacity-80 mb-1">Connecté en tant que</div>
                    <div class="text-sm font-medium text-white truncate"><?php echo htmlspecialchars($fullname); ?></div>
                    <div class="text-xs text-blue-200 opacity-80"><?php echo htmlspecialchars($lib_user_type); ?></div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 md:ml-0">
            <!-- Header Moderne -->
            <header class="modern-header">
                <div class="px-6 py-4">
                    <div class="flex justify-between items-center">
                        <!-- Titre Section -->
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                    <img src="./assets/images/logo_mi_sbg.png" alt="MasterCheck" class="w-8 h-8">
                                </div>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($titre_header); ?></h1>
                                <p class="text-blue-100 text-sm"><?php echo htmlspecialchars($sous_titre_header); ?></p>
                            </div>
                        </div>

                        <!-- Actions Section -->
                        <div class="hidden md:flex items-center space-x-6">
                            <!-- Notifications -->
                            <div class="relative">
                                <button class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white p-3 rounded-full transition-all duration-200 relative">
                                    <i class="fas fa-bell text-lg"></i>
                                    <span class="notification-badge"><?= $messagesNonLus; ?></span>
                                </button>
                            </div>

                            <!-- Profil utilisateur -->
                            <div class="flex items-center space-x-3 bg-white bg-opacity-10 rounded-full pr-4 py-2 pl-2">
                                <img src="<?= $_SESSION['photo_profil'] ? '../storage/uploads/profiles/' . $_SESSION['photo_profil'] : '../storage/uploads/profiles/default_profile.jpg'; ?>" 
                                     alt="Profile" class="user-avatar">
                                <div class="text-white">
                                    <div class="text-sm font-medium"><?php echo htmlspecialchars($fullname); ?></div>
                                    <div class="text-xs text-blue-100 opacity-80"><?php echo htmlspecialchars($lib_user_type); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <?php include $file_path; ?>
            </main>
        </div>
    </div>

    <script>
        // Sidebar Toggle for Mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('sidebar-hidden');
            sidebarOverlay.classList.toggle('hidden');
        }

        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }

        // Auto-hide notifications
        setTimeout(() => {
            const notifications = document.querySelectorAll('.animate-pulse');
            notifications.forEach(notification => {
                notification.style.animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => notification.remove(), 500);
            });
        }, 5000);

        // Active menu highlighting
        function setActiveMenuItem() {
            const currentPage = new URLSearchParams(window.location.search).get('page') || 'etudiants';
            const menuItems = document.querySelectorAll('.modern-menu a');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                item.classList.remove('active');
                
                if (href && href.includes(`page=${currentPage}`)) {
                    item.classList.add('active');
                }
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            setActiveMenuItem();
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) {
                    sidebar.classList.remove('sidebar-hidden');
                    sidebarOverlay.classList.add('hidden');
                }
            });
        });
    </script>
</body>

</html>
<?php ob_end_flush(); ?>