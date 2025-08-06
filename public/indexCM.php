<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheckMaster - Gestion des Soutenances MIAGE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="shortcut icon" href="./assets/images/logo_cm_sbg.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                        'fade-in': 'fadeIn 1s ease-in-out',
                        'slide-up': 'slideUp 0.8s ease-out',
                        'slide-in-left': 'slideInLeft 0.8s ease-out',
                        'slide-in-right': 'slideInRight 0.8s ease-out',
                        'bounce-in': 'bounceIn 1s ease-out',
                        'float': 'float 3s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                        'spin-slow': 'spin 6s linear infinite',
                    },
                    backgroundImage: {
                        'hero-gradient': 'linear-gradient(135deg, rgba(26, 82, 118, 0.9) 0%, rgba(41, 128, 185, 0.8) 50%, rgba(52, 152, 219, 0.7) 100%)',
                        'card-gradient': 'linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)',
                        'feature-gradient': 'linear-gradient(135deg, #1a5276 0%, #2980b9 100%)',
                    },
                    fontFamily: {
                        'poppins': ['Poppins', 'sans-serif'],
                        'montserrat': ['Montserrat', 'sans-serif'],
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
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes bounceIn {
            0% { opacity: 0; transform: scale(0.3); }
            50% { opacity: 1; transform: scale(1.05); }
            100% { opacity: 1; transform: scale(1); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #1a5276 0%, #3498db 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .floating-shape {
            position: absolute;
            pointer-events: none;
        }
        
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .service-card {
            transition: all 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(26, 82, 118, 0.25);
        }
    </style>
</head>

<body class="font-poppins bg-gray-50">
    <!-- Navigation moderne -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-primary/95 backdrop-blur-sm transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo CheckMaster -->
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-primary-light rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-double text-white text-xl"></i>
                    </div>
                    <div class="text-white">
                        <h1 class="text-xl font-bold">Check Master</h1>
                        <p class="text-xs text-white/80">MIAGE - UFHB</p>
                    </div>
                </div>

                <!-- Menu desktop -->
                <div class="hidden md:flex items-center  space-x-8">
                    <a href="#hero" class="text-white hover:text-primary-lighter transition-colors font-medium">Accueil</a>
                    <a href="#features" class="text-white hover:text-primary-lighter transition-colors font-medium">Fonctionnalités</a>
                    <a href="#services" class="text-white hover:text-primary-lighter transition-colors font-medium">Services</a>
                    <a href="index.php" class="text-white hover:text-primary-lighter transition-colors font-medium">UFHB</a>
                    <a href="pageConnexion.php" class="bg-primary-light text-white px-6 py-2 rounded-lg hover:bg-primary-lighter transition-colors font-medium">
                        <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                    </a>
                </div>

                <!-- Menu mobile toggle -->
                <button class="md:hidden text-white" id="mobile-menu-toggle">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Menu mobile -->
        <div class="md:hidden bg-primary/95 backdrop-blur-sm border-t border-white/20 hidden" id="mobile-menu">
            <div class="px-4 py-4 space-y-4">
                <a href="#hero" class="block text-white hover:text-primary-lighter transition-colors font-medium">Accueil</a>
                <a href="#features" class="block text-white hover:text-primary-lighter transition-colors font-medium">Fonctionnalités</a>
                <a href="#services" class="block text-white hover:text-primary-lighter transition-colors font-medium">Services</a>
                <a href="index.php" class="block text-white hover:text-primary-lighter transition-colors font-medium">UFHB</a>
                <a href="pageConnexion.php" class="block bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-lighter transition-colors font-medium text-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                </a>
            </div>
        </div>
    </nav>

    <!-- Section Hero redesignée -->
    <section id="hero" class="bg-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- En-tête centré -->
            <div class="text-center mb-16">
                <div class="inline-block px-6 py-3 bg-primary/10 rounded-full text-primary font-medium mb-6 mt-10">
                    <i class="fas fa-graduation-cap mr-2"></i>Plateforme MIAGE - UFHB
                </div>
                
                <h1 class="text-5xl md:text-7xl font-bold text-gray-900 mb-8 leading-tight">
                    <span class="block">Gérer la</span>
                    <span class="block text-primary">Commission de validation</span>
                    <span class="block">Devient Plus <span class="text-accent">Simple</span></span>
                </h1>
            </div>

            <!-- Contenu principal en deux colonnes -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12 items-start">
                <!-- Colonne gauche - Description -->
                <div class="lg:col-span-2">
                    <div class="bg-gray-50 rounded-2xl p-8 mb-8">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            <span class="text-primary font-bold">Check</span>Master
                        </h2>
                        <p class="text-lg text-gray-600 leading-relaxed mb-6">
                            Une plateforme innovante dédiée à la gestion des commissions de validation de soutenance pour les étudiants en Master 2 MIAGE à l'UFHB. Elle facilite l'évaluation des étudiants, le dépôt des documents, et le suivi du processus.
                        </p>
                        
                        <!-- Points clés -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-check text-primary text-sm"></i>
                                </div>
                                <span class="text-gray-700">Gestion simplifiée</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-accent/10 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-users text-accent text-sm"></i>
                                </div>
                                <span class="text-gray-700">Interface collaborative</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-secondary/10 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-chart-line text-secondary text-sm"></i>
                                </div>
                                <span class="text-gray-700">Suivi en temps réel</span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-8 h-8 bg-primary-light/10 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fas fa-shield-alt text-primary-light text-sm"></i>
                                </div>
                                <span class="text-gray-700">Sécurisé et fiable</span>
                            </div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <a href="pageConnexion.php" 
                               class="bg-primary text-white px-8 py-4 rounded-xl font-semibold hover:bg-primary-light transition-all duration-300 inline-flex items-center justify-center group">
                                <i class="fas fa-sign-in-alt mr-2 group-hover:translate-x-1 transition-transform"></i>
                                Accéder à la plateforme
                            </a>
                            <a href="index.php" 
                               class="bg-white border-2 border-gray-200 text-gray-700 px-8 py-4 rounded-xl font-semibold hover:border-primary hover:text-primary transition-all duration-300 inline-flex items-center justify-center">
                                <i class="fas fa-university mr-2"></i>
                                Retour à UFHB
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Colonne droite - Logo et stats -->
                <div class="space-y-8">
                    <!-- Logo container -->
                    <div class="bg-gradient-to-br from-primary/5 to-primary-light/5 rounded-2xl p-8 text-center">
                        <div class="w-48 h-48 mx-auto mb-6 bg-white rounded-xl shadow-lg flex items-center justify-center">
                            <img src="./assets/images/logo_cm_sbg.png" alt="CheckMaster Logo" 
                                 class="w-32 h-32 object-contain">
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">CheckMaster</h3>
                        <p class="text-gray-600">Votre partenaire pour la validation</p>
                    </div>

                    <!-- Statistiques rapides -->
                    <div class="bg-white border border-gray-200 rounded-2xl p-6">
                        <h4 class="font-bold text-gray-900 mb-4">En un coup d'œil</h4>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Étudiants</span>
                                <span class="font-bold text-primary">150+</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Enseignants</span>
                                <span class="font-bold text-accent">25+</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Soutenances</span>
                                <span class="font-bold text-secondary">100+</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Taux de réussite</span>
                                <span class="font-bold text-primary-light">95%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                </div>
            </div>
        </div>

        <!-- Indicateur de scroll -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <a href="#features" class="text-white/70 hover:text-white transition-colors">
                <i class="fas fa-chevron-down text-2xl"></i>
            </a>
        </div>
    </section>

    <!-- Section Fonctionnalités -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Titre de section -->
            <div class="text-center mb-16 animate-fade-in">
                <h2 class="text-4xl md:text-5xl font-bold gradient-text mb-6 font-montserrat">
                    Fonctionnalités
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Découvrez les fonctionnalités puissantes qui rendent CheckMaster indispensable pour la gestion de vos soutenances
                </p>
            </div>

            <!-- Grille des fonctionnalités -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Espace Personnalisé -->
                <div class="feature-card bg-white rounded-2xl shadow-lg p-8 text-center border border-gray-100 animate-slide-up">
                    <div class="w-20 h-20 bg-gradient-to-br from-primary to-primary-light rounded-2xl flex items-center justify-center mx-auto mb-6 transform hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-users text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Espace Personnalisé</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Chaque utilisateur dispose d'un espace dédié pour accomplir ses tâches spécifiques avec une interface intuitive et adaptée à son rôle.
                    </p>
                </div>

                <!-- Gestion Documentaire -->
                <div class="feature-card bg-white rounded-2xl shadow-lg p-8 text-center border border-gray-100 animate-slide-up" style="animation-delay: 0.1s">
                    <div class="w-20 h-20 bg-gradient-to-br from-accent to-green-600 rounded-2xl flex items-center justify-center mx-auto mb-6 transform hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-folder text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Gestion Documentaire</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Archivage sécurisé et centralisé de tous les documents liés aux soutenances, facilitant la recherche et la consultation des documents importants.
                    </p>
                </div>

                <!-- Suivi en Temps Réel -->
                <div class="feature-card bg-white rounded-2xl shadow-lg p-8 text-center border border-gray-100 animate-slide-up" style="animation-delay: 0.2s">
                    <div class="w-20 h-20 bg-gradient-to-br from-secondary to-yellow-600 rounded-2xl flex items-center justify-center mx-auto mb-6 transform hover:scale-110 transition-transform duration-300">
                        <i class="fa-solid fa-check-to-slot text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-4">Suivi en Temps Réel</h3>
                    <p class="text-gray-600 leading-relaxed">
                        Visualisation claire de l'avancement du processus de soutenance avec notifications automatiques à chaque étape validée par les différents acteurs.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Services -->
    <section id="services" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Titre de section -->
            <div class="text-center mb-16 animate-fade-in">
                <h2 class="text-4xl md:text-5xl font-bold gradient-text mb-6 font-montserrat">
                    Nos Services
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Des espaces dédiés pour chaque type d'utilisateur, optimisés pour leurs besoins spécifiques
                </p>
            </div>

            <!-- Grille des services -->
            <div class="space-y-12">
                <!-- Service Étudiant -->
                <div class="service-card bg-white rounded-2xl shadow-lg overflow-hidden animate-slide-up">
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        <div class="relative h-64 lg:h-auto">
                            <img src="./assets/images/etudiants_img.jpg" alt="Espace étudiant" 
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-r from-primary/80 to-transparent"></div>
                        </div>
                        <div class="p-8 lg:p-12 flex flex-col justify-center">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-user-graduate text-primary text-xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900">Espace Étudiant</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed mb-6">
                                Accédez à votre espace personnel dédié pour soumettre votre mémoire, consulter la composition détaillée de votre jury et recevoir tous les retours et évaluations nécessaires à votre préparation.
                            </p>
                            <a href="pageConnexion.php" class="inline-flex items-center text-primary hover:text-primary-light font-semibold transition-colors group">
                                Accéder à mon espace 
                                <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-2 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Service Enseignant -->
                <div class="service-card bg-white rounded-2xl shadow-lg overflow-hidden animate-slide-up" style="animation-delay: 0.1s">
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        <div class="order-2 lg:order-1 p-8 lg:p-12 flex flex-col justify-center">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-chalkboard-teacher text-accent text-xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900">Espace Enseignant</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed mb-6">
                                Gérez efficacement votre travail, accédez aux rapports et mémoires complets des étudiants et saisissez vos notes et commentaires dans un environnement de travail optimisé.
                            </p>
                            <a href="pageConnexion.php" class="inline-flex items-center text-accent hover:text-green-600 font-semibold transition-colors group">
                                Accéder à mon espace 
                                <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-2 transition-transform"></i>
                            </a>
                        </div>
                        <div class="order-1 lg:order-2 relative h-64 lg:h-auto">
                            <img src="./assets/images/enseignants_img.jpg" alt="Espace enseignant" 
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-l from-accent/80 to-transparent"></div>
                        </div>
                    </div>
                </div>

                <!-- Service Commission -->
                <div class="service-card bg-white rounded-2xl shadow-lg overflow-hidden animate-slide-up" style="animation-delay: 0.2s">
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        <div class="relative h-64 lg:h-auto">
                            <img src="./assets/images/administration_img.jpg" alt="Administration" 
                                 class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-r from-secondary/80 to-transparent"></div>
                        </div>
                        <div class="p-8 lg:p-12 flex flex-col justify-center">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-users-cog text-secondary text-xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900">Membres de la commission</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed mb-6">
                                Évaluez les rapports des étudiants, consultez les comptes rendus de commission et supervisez l'ensemble du processus via un tableau de bord complet et intuitif.
                            </p>
                            <a href="pageConnexion.php" class="inline-flex items-center text-secondary hover:text-yellow-600 font-semibold transition-colors group">
                                Accéder à mon espace 
                                <i class="fas fa-arrow-right ml-2 transform group-hover:translate-x-2 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section CTA final -->
    <section class="py-20 bg-gradient-to-r from-primary to-primary-light">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <div class="animate-fade-in">
                <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-6 animate-float">
                    <i class="fas fa-check-double text-4xl text-white"></i>
                </div>
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6 font-montserrat">
                    Prêt à commencer ?
                </h2>
                <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto leading-relaxed">
                    Rejoignez la plateforme CheckMaster et simplifiez la gestion de vos soutenances MIAGE dès aujourd'hui.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="pageConnexion.php" 
                       class="bg-white text-primary px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 inline-flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Connexion à la plateforme
                    </a>
                    <a href="index.php" 
                       class="bg-primary-light text-white px-8 py-4 rounded-lg font-semibold hover:bg-primary-lighter transition-all duration-300 transform hover:scale-105 inline-flex items-center justify-center border-2 border-white/30">
                        <i class="fas fa-university mr-2"></i>
                        En savoir plus sur l'UFHB
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer moderne -->
    <footer class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Logo et description -->
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-12 h-12 bg-gradient-to-br from-primary to-primary-light rounded-xl flex items-center justify-center">
                            <i class="fas fa-check-double text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">CheckMaster</h3>
                            <p class="text-gray-400 text-sm">Gestion des Soutenances MIAGE</p>
                        </div>
                    </div>
                    <p class="text-gray-400 mb-6 max-w-md">
                        Plateforme innovante dédiée à la gestion des commissions de validation de soutenance pour les étudiants en Master 2 MIAGE à l'UFHB.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary transition-colors">
                            <i class="fab fa-facebook text-sm"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary transition-colors">
                            <i class="fab fa-twitter text-sm"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary transition-colors">
                            <i class="fab fa-linkedin text-sm"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary transition-colors">
                            <i class="fab fa-envelope text-sm"></i>
                        </a>
                    </div>
                </div>

                <!-- Liens rapides -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Navigation</h4>
                    <ul class="space-y-2">
                        <li><a href="#hero" class="text-gray-400 hover:text-white transition-colors">Accueil</a></li>
                        <li><a href="#features" class="text-gray-400 hover:text-white transition-colors">Fonctionnalités</a></li>
                        <li><a href="#services" class="text-gray-400 hover:text-white transition-colors">Services</a></li>
                        <li><a href="pageConnexion.php" class="text-gray-400 hover:text-white transition-colors">Connexion</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center">
                            <i class="fas fa-university mr-2 text-primary"></i>
                            MIAGE - UFHB
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
                            Cocody, Abidjan
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2 text-primary"></i>
                            checkmaster@univ-fhb.edu.ci
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-2 text-primary"></i>
                            +225 22 44 81 00
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="border-gray-800 my-8">
            
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    © 2024 CheckMaster - MIAGE UFHB. Tous droits réservés.
                </p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Conditions d'utilisation</a>
                    <a href="#" class="text-gray-400 hover:text-white text-sm transition-colors">Politique de confidentialité</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bouton retour en haut -->
    <button class="fixed bottom-8 right-8 w-12 h-12 bg-primary text-white rounded-full shadow-lg hover:bg-primary-light transition-all duration-300 transform hover:scale-110 z-50 hidden" id="back-to-top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script>
        // Gestion de la navigation
        const navbar = document.getElementById('navbar');
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        const backToTop = document.getElementById('back-to-top');

        // Back to top button scroll effect
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                backToTop.classList.remove('hidden');
            } else {
                backToTop.classList.add('hidden');
            }
        });

        // Mobile menu toggle
        mobileMenuToggle.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Back to top
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Smooth scroll pour les liens d'ancrage
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
                // Fermer le menu mobile si ouvert
                mobileMenu.classList.add('hidden');
            });
        });

        // Animation on scroll
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
        document.querySelectorAll('.animate-slide-up, .animate-slide-in-left, .animate-slide-in-right, .animate-fade-in').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            observer.observe(el);
        });

        // Effet parallax léger sur les formes flottantes
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const shapes = document.querySelectorAll('.floating-shape');
            
            shapes.forEach((shape, index) => {
                const speed = 0.2 + (index * 0.1);
                shape.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });

        // Animation d'entrée progressive des cartes
        window.addEventListener('load', function() {
            const cards = document.querySelectorAll('.feature-card, .service-card');
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