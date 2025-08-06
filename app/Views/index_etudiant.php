<?php
require_once __DIR__ . '/../config/config.php'; // pour $pdo
require_once __DIR__ . '/../Controllers/MenuController.php';
require_once __DIR__ . '/../Controllers/MessageController.php';

// Vérification de connexion et du type d'utilisateur
if (!isset($_SESSION['user_id'])) {
    header('Location: /GSCV+/public/pageConnexion.php');
    exit();
}

$messageController = new MessageController($pdo);

$profilePhoto = ''; // Image par défaut
$sql = "SELECT 
        u.id_utilisateur,
        u.login_utilisateur,
        etd.photo_etd 
        FROM utilisateur u
        LEFT JOIN etudiants etd ON etd.email_etd = u.login_utilisateur
        WHERE u.id_utilisateur = ?";

$recupUser = $pdo->prepare($sql);
$recupUser->execute([$_SESSION['user_id']]);
$userData = $recupUser->fetch(PDO::FETCH_ASSOC);

$_SESSION['photo_etd'] = $userData['photo_etd'];
$messagesNonLus = $messageController->compterMessagesNonLus($_SESSION['user_id']);

$page = isset($_GET['page']) ? basename($_GET['page']) : 'soutenances';

// Rediriger vers la page par défaut si aucune page n'est spécifiée
if (!isset($_GET['page'])) {
    header('Location: ?page=soutenances');
    exit();
}

// Inclusion sécurisée du fichier - CORRECTION DU CHEMIN
$file_path = __DIR__ . '/' . $page . '.php';
if (!file_exists($file_path)) {
    $file_path = __DIR__ . '/soutenances.php';
}

$menuController = new MenuController($pdo);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="shortcut icon" href="./assets/images/logo_cm_sbg.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/GSCV+/public/assets/css/index_etudiant.css?v=<?php echo time(); ?>">
    <script src="../app/Views/assets/js/messages.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'student-primary': '#154360',
                        'student-secondary': '#1a5276',
                        'student-light': '#2563eb',
                        'student-dark': '#0f2f3f',
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #154360 0%, #1a5276 100%);
            padding: 0.8rem 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .infos-global {
            display: flex;
            align-items: center;
            gap: 3rem;
        }

        .top {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: transform 0.3s ease;
        }

        .top:hover {
            transform: scale(1.02);
        }

        .sidebar-menu a {
            color: white;
        }

        .sidebar-menu a:hover {
            color: #1a5276;
            background-color: white;
            border-radius: 10px;
        }

        .top img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 12px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .top h1 {
            font-size: 1.3rem;
            margin: 0;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1.5rem;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .user-profile:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.4);
            transition: border-color 0.3s ease;
        }

        .user-profile:hover .profile-pic {
            border-color: rgba(255, 255, 255, 0.8);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }



        .username {
            font-weight: 600;
            font-size: 0.95rem;
            color: white;
            line-height: 1.2;
        }

        .user-role {
            font-size: 0.8rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.3s ease;
        }

        .header-logo:hover {
            transform: scale(1.02);
        }

        .header-logo img {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            padding: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .header-logo h1 {
            font-size: 1.3rem;
            margin: 0;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .notification {
            background: rgba(255, 255, 255, 0.15);
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 12px;
            position: relative;
            color: white;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .notification:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.1);
        }

        .notification i {
            font-size: 1.1rem;
        }

        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            width: 22px;
            height: 22px;
            border-radius: 50%;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 0.8rem 1rem;
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .infos-global {
                gap: 1rem;
            }

            .top h1, .header-logo h1 {
                font-size: 1.1rem;
            }

            .user-profile {
                padding: 0.4rem 1rem;
            }

            .top img, .header-logo img {
                width: 50px;
                height: 50px;
            }
        }

        /* Améliorations pour la sidebar */
        .sidebar {
            background: linear-gradient(180deg, #154360 0%, #0f2f3f 100%);
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            color:#fff;
        }

        .sidebar .logo {
            background: rgba(15, 47, 63, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>

</head>

<body class="bg-gray-50 font-sans">
    <!-- Messages de succès et d'erreur améliorés -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="fixed top-5 right-5 z-50 bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg shadow-lg animate-pulse">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3 text-green-500"></i>
                    <span class="font-medium"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-green-700 hover:text-green-900 ml-4 transition-colors">
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
                    <span class="font-medium"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="text-red-700 hover:text-red-900 ml-4 transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <button class="sidebar-toggle fixed top-4 left-4 z-50 md:hidden bg-student-primary text-white p-3 rounded-lg shadow-lg hover:bg-student-dark transition-all duration-300 hover:scale-105" id="sidebarToggle">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden" id="sidebarOverlay"></div>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="infos-global">
                <div class="top">
                    <img src="../public/assets/images/logo_mi_sbg.png" alt="UFHB Logo">
                    <h1>Espace Étudiant</h1>
                </div>
            </div>

            <div class="user-profile">
                <img src="<?= "../storage/uploads/profiles/" . $_SESSION['photo_profil'] ?>" alt="Photo de profil" class="profile-pic">
                <div class="user-info">
                    <span class="username"><?= $_SESSION['user_fullname'] ?? 'Inconnu' ?></span>
                    <span class="user-role"><?= $_SESSION['lib_user_type'] ?? 'Étudiant' ?></span>
                </div>
                
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge"><?= $messagesNonLus; ?></span>
                </div>
            </div>

            <div class="header-logo">
                <img src="./assets/images/logo_cm_sbg.png" alt="CheckMaster Logo">
                <h1>Check Master</h1>
            </div>
        </div>

        <div class="dashboard-sections text-white">
            <div class="sidebar" id="sidebar">
                <div class="logo p-6 text-center">
                    <img src="./assets/images/logo_cm_sbg.png" width="90px" height="85px" alt="CheckMaster Logo" class="mx-auto mb-3 rounded-lg">
                    <h3 class="text-white font-semibold text-lg">Check Master</h3>
                </div>
                <?php echo $menuController->displayMenu(); ?>
            </div>
            <div class="content bg-white rounded-t-xl shadow-lg" id="content">
                <?php include $file_path; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/sidebar_open.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/siderbar_active.js?v=<?php echo time(); ?>"></script>

    <script>
        // Auto-hide notifications
        setTimeout(() => {
            const notifications = document.querySelectorAll('.animate-pulse');
            notifications.forEach(notification => {
                notification.style.animation = 'fadeOut 0.5s ease-out forwards';
                setTimeout(() => notification.remove(), 500);
            });
        }, 5000);
    </script>

</body>

</html>