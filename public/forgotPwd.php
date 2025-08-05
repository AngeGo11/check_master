<?php
session_start();

// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

// Créer la connexion PDO
$pdo = DataBase::getConnection();
$authController = new AuthController($pdo);

// Envoi du mail
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send-link'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $authController->sendLinkResetPassword($email);
}
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHECK MASTER - Mot de passe oublié</title>
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
                        'fade-in': 'fadeIn 0.8s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'bounce-in': 'bounceIn 0.8s ease-out',
                        'slide-in-left': 'slideInLeft 0.6s ease-out',
                        'slide-in-right': 'slideInRight 0.6s ease-out',
                        'float': 'float 3s ease-in-out infinite',
                    },
                    backgroundImage: {
                        'login-gradient': 'linear-gradient(135deg, #1a5276 0%, #2980b9 50%, #3498db 100%)',
                        'mesh-gradient': 'radial-gradient(circle at 20% 50%, rgba(41, 128, 185, 0.3) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(26, 82, 118, 0.3) 0%, transparent 50%), radial-gradient(circle at 40% 80%, rgba(52, 152, 219, 0.2) 0%, transparent 50%)',
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
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .input-focus:focus {
            box-shadow: 0 0 0 3px rgba(41, 128, 185, 0.1);
            border-color: #2980b9;
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, rgba(41, 128, 185, 0.1), rgba(52, 152, 219, 0.2));
            animation: float 6s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 15%;
            animation-delay: 2s;
        }
        
        .shape-3 {
            width: 80px;
            height: 80px;
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }
    </style>
</head>

<body class="h-full bg-mesh-gradient">
    <!-- Messages de notification -->
    <?php if (!empty($message)): ?>
        <div class="fixed top-4 right-4 z-50 <?php echo $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> border px-6 py-4 rounded-lg shadow-lg animate-slide-in-right max-w-md">
            <div class="flex items-center">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3 text-lg"></i>
                <span class="font-medium"><?php echo $message; ?></span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 <?php echo $messageType === 'success' ? 'text-green-700 hover:text-green-900' : 'text-red-700 hover:text-red-900'; ?>">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <div class="min-h-full flex flex-row-reverse">
        <!-- Section gauche - Formulaire de réinitialisation -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:flex-none lg:px-20 xl:px-24 relative">
            <!-- Formes flottantes pour l'animation -->
            <div class="floating-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
            
            <div class="mx-auto w-full max-w-sm lg:w-96 relative z-10">
                <!-- Logo et titre -->
                <div class="text-center animate-slide-up">
                    <div class="flex justify-center mb-6">
                        <div class="w-20 h-20 bg-gradient-to-br from-primary to-primary-light rounded-2xl flex items-center justify-center shadow-lg animate-bounce-in">
                            <img src="./assets/images/logo_cm_sbg.png" alt="CHECK MASTER" class="w-12 h-12">
                        </div>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">
                        <span class="text-primary">CHECK</span> 
                        <span class="text-primary-light">MASTER</span>
                    </h1>
                    <p class="text-lg font-semibold text-gray-700 mb-2">Mot de passe oublié ?</p>
                    <p class="text-sm text-gray-600 max-w-md mx-auto">
                        Pas de panique ! Indiquez votre adresse e-mail et nous vous enverrons un lien de réinitialisation.
                    </p>
                </div>

                <!-- Formulaire de réinitialisation -->
                <div class="mt-8 animate-slide-up" style="animation-delay: 0.2s">
                    <div class="glass-effect rounded-2xl shadow-xl p-8">
                        <form method="POST" class="space-y-6">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-2 text-primary"></i>
                                    Adresse e-mail
                                </label>
                                <div class="relative">
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           required 
                                           class="input-focus appearance-none relative block w-full px-4 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-primary focus:border-primary focus:z-10 transition-all duration-200"
                                           placeholder="votre@email.com">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <i class="fas fa-user text-gray-400"></i>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <input type="submit" 
                                        name="send-link" value="Envoyer le lien de réinitialisation"
                                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-primary to-primary-light hover:from-primary-light hover:to-primary-lighter focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all duration-300 transform hover:scale-105 hover:shadow-lg">
                            </div>
                        </form>

                        <!-- Lien retour -->
                        <div class="mt-6 text-center">
                            <a href="pageConnexion.php" 
                               class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-primary transition-colors duration-200">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Retour à la connexion
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Footer info -->
                <div class="mt-8 text-center animate-fade-in" style="animation-delay: 0.4s">
                    <p class="text-xs text-gray-500">
                        © 2025 CHECK MASTER. Plateforme de gestion académique.
                    </p>
                </div>
            </div>
        </div>

        <!-- Section droite - Image/Design -->
        <div class="hidden lg:block relative flex-1">
            <div class="absolute inset-0 bg-login-gradient">
                <!-- Overlay avec motifs -->
                <div class="absolute inset-0 bg-mesh-gradient opacity-30"></div>
                
                <!-- Contenu de la section droite -->
                <div class="relative h-full flex flex-col justify-center items-center text-white p-12">
                    <!-- Illustration principale -->
                    <div class="animate-float mb-8">
                        <div class="w-64 h-64 bg-white/10 backdrop-blur-sm rounded-full flex items-center justify-center">
                            <div class="w-48 h-48 bg-white/20 rounded-full flex items-center justify-center">
                                <i class="fas fa-key text-8xl text-white/90"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Texte descriptif -->
                    <div class="text-center max-w-md animate-slide-in-right" style="animation-delay: 0.3s">
                        <h2 class="text-3xl font-bold mb-4">
                            Récupération Sécurisée
                        </h2>
                        <p class="text-lg text-white/90 mb-6">
                            Notre système de récupération de mot de passe garantit la sécurité de votre compte.
                        </p>
                        
                        <!-- Fonctionnalités -->
                        <div class="space-y-3 text-left">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-shield-alt text-sm"></i>
                                </div>
                                <span class="text-white/90">Lien sécurisé avec expiration</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-clock text-sm"></i>
                                </div>
                                <span class="text-white/90">Valide pendant 1 heure seulement</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-envelope text-sm"></i>
                                </div>
                                <span class="text-white/90">Envoyé directement dans votre boîte mail</span>
                            </div>
                        </div>
                    </div>

                    <!-- Éléments décoratifs -->
                    <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full animate-pulse"></div>
                    <div class="absolute bottom-20 right-20 w-16 h-16 bg-white/10 rounded-full animate-bounce"></div>
                    <div class="absolute top-1/3 right-10 w-12 h-12 bg-white/10 rounded-full animate-ping"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animation des éléments au chargement
        window.addEventListener('load', function() {
            document.body.classList.add('loaded');
            
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach((shape, index) => {
                setTimeout(() => {
                    shape.style.opacity = '1';
                }, index * 200);
            });
        });

        // Auto-suppression des messages après 5 secondes
        setTimeout(() => {
            const alerts = document.querySelectorAll('.fixed.top-4.right-4');
            alerts.forEach(alert => {
                alert.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.remove();
                    }
                }, 300);
            });
        }, 5000);

        // Effet de focus progressif sur les inputs
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('transform', 'scale-105');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('transform', 'scale-105');
            });
        });

        // Animation du bouton
        document.querySelector('button[type="submit"]').addEventListener('click', function(e) {
            if (this.disabled) return;
            
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Envoi en cours...';
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 2000);
        });

        // Parallax effect pour les formes flottantes
        document.addEventListener('mousemove', function(e) {
            const shapes = document.querySelectorAll('.shape');
            const mouseX = e.clientX / window.innerWidth;
            const mouseY = e.clientY / window.innerHeight;
            
            shapes.forEach((shape, index) => {
                const speed = (index + 1) * 0.5;
                const x = (mouseX - 0.5) * speed * 20;
                const y = (mouseY - 0.5) * speed * 20;
                
                shape.style.transform = `translate(${x}px, ${y}px)`;
            });
        });
    </script>
</body>
</html>
