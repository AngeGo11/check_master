<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Université Félix Houphouët-Boigny - GSCV+</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
                    },
                    backgroundImage: {
                        'hero-gradient': 'linear-gradient(135deg, rgba(26, 82, 118, 0.9) 0%, rgba(41, 128, 185, 0.8) 50%, rgba(52, 152, 219, 0.7) 100%)',
                        'card-gradient': 'linear-gradient(135deg, #ffffff 0%, #f8fafc 100%)',
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
        
        .parallax-bg {
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .hero-slider {
            position: relative;
            overflow: hidden;
        }
        
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }
        
        .slide.active {
            opacity: 1;
        }
        
        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #1a5276 0%, #3498db 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>

<body class="font-poppins bg-gray-50">
    <!-- Navigation moderne -->
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <!-- Logo -->
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-primary to-primary-light rounded-xl flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-white text-xl"></i>
                    </div>
                    <div class="text-white">
                        <h1 class="text-xl font-bold">UFHB</h1>
                        <p class="text-xs text-white/80">Université Félix Houphouët-Boigny</p>
                    </div>
                </div>

                <!-- Menu desktop -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#accueil" class="text-white hover:text-primary-lighter transition-colors font-medium">Accueil</a>
                    <a href="#about" class="text-white hover:text-primary-lighter transition-colors font-medium">À propos</a>
                    <a href="#formations" class="text-white hover:text-primary-lighter transition-colors font-medium">Formations</a>
                    <a href="#platform" class="text-white hover:text-primary-lighter transition-colors font-medium">Plateforme</a>
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
                <a href="#accueil" class="block text-white hover:text-primary-lighter transition-colors font-medium">Accueil</a>
                <a href="#about" class="block text-white hover:text-primary-lighter transition-colors font-medium">À propos</a>
                <a href="#formations" class="block text-white hover:text-primary-lighter transition-colors font-medium">Formations</a>
                <a href="#platform" class="block text-white hover:text-primary-lighter transition-colors font-medium">Plateforme</a>
                <a href="pageConnexion.php" class="block bg-primary-light text-white px-4 py-2 rounded-lg hover:bg-primary-lighter transition-colors font-medium text-center">
                    <i class="fas fa-sign-in-alt mr-2"></i>Connexion
                </a>
            </div>
        </div>
    </nav>

    <!-- Section Hero avec slider -->
    <section id="accueil" class="relative h-screen hero-slider">
        <!-- Slides -->
        <div class="slide active">
            <img src="./assets/images/ufhb3.jpg" alt="Université Félix Houphouët-Boigny">
            <div class="absolute inset-0 bg-hero-gradient"></div>
        </div>
        <div class="slide">
            <img src="./assets/images/ufhb2.jpeg" alt="Formations universitaires">
            <div class="absolute inset-0 bg-hero-gradient"></div>
        </div>
        <div class="slide">
            <img src="./assets/images/ufhb4.jpg" alt="Recherche universitaire">
            <div class="absolute inset-0 bg-hero-gradient"></div>
        </div>

        <!-- Contenu Hero -->
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <div class="animate-slide-up">
                    <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 font-montserrat">
                        UNIVERSITÉ FÉLIX
                        <span class="block text-primary-lighter">HOUPHOUËT-BOIGNY</span>
                    </h1>
                    <p class="text-xl md:text-2xl text-white/90 mb-8 max-w-3xl mx-auto">
                        Excellence académique et innovation au service du développement de l'Afrique
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="https://w.univ-fhb.edu.ci" target="_blank" 
                           class="bg-white text-primary px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-university mr-2"></i>
                            Découvrir l'université
                        </a>
                        <a href="#platform" 
                           class="bg-primary-light text-white px-8 py-4 rounded-lg font-semibold hover:bg-primary-lighter transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-graduation-cap mr-2"></i>
                            Notre plateforme
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Indicateurs de slides -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex space-x-3">
            <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition-colors slide-indicator active" data-slide="0"></button>
            <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition-colors slide-indicator" data-slide="1"></button>
            <button class="w-3 h-3 rounded-full bg-white/50 hover:bg-white transition-colors slide-indicator" data-slide="2"></button>
        </div>

        <!-- Flèches de navigation -->
        <button class="absolute left-8 top-1/2 transform -translate-y-1/2 bg-white/20 backdrop-blur-sm text-white p-3 rounded-full hover:bg-white/30 transition-colors" id="prev-slide">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="absolute right-8 top-1/2 transform -translate-y-1/2 bg-white/20 backdrop-blur-sm text-white p-3 rounded-full hover:bg-white/30 transition-colors" id="next-slide">
            <i class="fas fa-chevron-right"></i>
        </button>
    </section>

    <!-- Section À propos -->
    <section id="about" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Titre de section -->
            <div class="text-center mb-16 animate-fade-in">
                <h2 class="text-4xl md:text-5xl font-bold gradient-text mb-6 font-montserrat">
                    À PROPOS DE L'UNIVERSITÉ
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Plus de 50 ans d'excellence académique au service de l'éducation supérieure en Côte d'Ivoire
                </p>
            </div>

            <!-- Grille des contenus -->
            <div class="space-y-20">
                <!-- Histoire et héritage -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="animate-slide-in-left">
                        <div class="relative">
                            <img src="./assets/images/about_img1.jpg" alt="Histoire de l'université" 
                                 class="rounded-2xl shadow-2xl w-full h-80 object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-primary/30 to-transparent rounded-2xl"></div>
                        </div>
                    </div>
                    <div class="animate-slide-in-right">
                        <div class="bg-card-gradient p-8 rounded-2xl shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-history text-primary text-xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900">Notre histoire et notre héritage</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed">
                                Fondée en 1964, l'Université Félix Houphouët-Boigny (anciennement Université de Cocody) porte le nom du père fondateur de la nation ivoirienne. Avec plus de cinq décennies d'excellence académique, notre institution a formé des générations de leaders et contribué significativement au développement du pays.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Campus moderne -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="order-2 lg:order-1 animate-slide-in-left">
                        <div class="bg-card-gradient p-8 rounded-2xl shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-accent/10 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-building text-accent text-xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900">Un campus moderne et dynamique</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed">
                                Situé au cœur de Cocody, notre campus s'étend sur plusieurs hectares et offre un environnement propice aux études et à la recherche. Nos infrastructures modernes comprennent des amphithéâtres, des laboratoires équipés, une bibliothèque universitaire riche de milliers d'ouvrages et des espaces de vie communautaire.
                            </p>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2 animate-slide-in-right">
                        <div class="relative">
                            <img src="./assets/images/about_img2.png" alt="Campus universitaire" 
                                 class="rounded-2xl shadow-2xl w-full h-80 object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-accent/30 to-transparent rounded-2xl"></div>
                        </div>
                    </div>
                </div>

                <!-- Corps professoral -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="animate-slide-in-left">
                        <div class="relative">
                            <img src="./assets/images/about_img3.jpg" alt="Corps professoral" 
                                 class="rounded-2xl shadow-2xl w-full h-80 object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-secondary/30 to-transparent rounded-2xl"></div>
                        </div>
                    </div>
                    <div class="animate-slide-in-right">
                        <div class="bg-card-gradient p-8 rounded-2xl shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-users text-secondary text-xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900">Un corps professoral d'excellence</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed">
                                L'UFHB s'enorgueillit de compter parmi son personnel enseignant des professeurs de renommée internationale, des chercheurs passionnés et des experts dans leurs domaines respectifs. Notre équipe pédagogique s'engage à offrir un enseignement de qualité et à accompagner les étudiants vers la réussite.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Coopération internationale -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div class="order-2 lg:order-1 animate-slide-in-left">
                        <div class="bg-card-gradient p-8 rounded-2xl shadow-lg">
                            <div class="flex items-center mb-4">
                                <div class="w-12 h-12 bg-primary-light/10 rounded-lg flex items-center justify-center mr-4">
                                    <i class="fas fa-globe text-primary-light text-xl"></i>
                                </div>
                                <h3 class="text-2xl font-bold text-gray-900">Coopération internationale</h3>
                            </div>
                            <p class="text-gray-600 leading-relaxed">
                                L'Université Félix Houphouët-Boigny entretient des partenariats stratégiques avec de nombreuses universités et institutions de recherche à travers le monde. Ces collaborations favorisent la mobilité des étudiants et des enseignants, les projets de recherche conjoints et l'échange de bonnes pratiques académiques.
                            </p>
                        </div>
                    </div>
                    <div class="order-1 lg:order-2 animate-slide-in-right">
                        <div class="relative">
                            <img src="./assets/images/about_img4.jpg" alt="Coopération internationale" 
                                 class="rounded-2xl shadow-2xl w-full h-80 object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-primary-light/30 to-transparent rounded-2xl"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Formations -->
    <section id="formations" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 animate-fade-in">
                <h2 class="text-4xl md:text-5xl font-bold gradient-text mb-6 font-montserrat">
                    NOS FORMATIONS
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Une large gamme de formations dans divers domaines pour répondre aux besoins du marché du travail
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Sciences -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-slide-up">
                    <div class="bg-gradient-to-r from-primary to-primary-light p-6">
                        <i class="fas fa-atom text-4xl text-white mb-4"></i>
                        <h3 class="text-xl font-bold text-white">Sciences</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Mathématiques, Physique, Chimie, Biologie et Sciences de la Terre.</p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><i class="fas fa-check text-accent mr-2"></i>Licence, Master, Doctorat</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Laboratoires équipés</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Recherche appliquée</li>
                        </ul>
                    </div>
                </div>

                <!-- Lettres et Sciences Humaines -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-slide-up" style="animation-delay: 0.1s">
                    <div class="bg-gradient-to-r from-accent to-green-500 p-6">
                        <i class="fas fa-book text-4xl text-white mb-4"></i>
                        <h3 class="text-xl font-bold text-white">Lettres & Sciences Humaines</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Langues, Littérature, Histoire, Géographie, Philosophie, Sociologie.</p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><i class="fas fa-check text-accent mr-2"></i>Formation pluridisciplinaire</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Échanges internationaux</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Recherche culturelle</li>
                        </ul>
                    </div>
                </div>

                <!-- Droit et Sciences Politiques -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-slide-up" style="animation-delay: 0.2s">
                    <div class="bg-gradient-to-r from-secondary to-yellow-500 p-6">
                        <i class="fas fa-balance-scale text-4xl text-white mb-4"></i>
                        <h3 class="text-xl font-bold text-white">Droit & Sciences Politiques</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Droit privé, Droit public, Sciences politiques, Relations internationales.</p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><i class="fas fa-check text-accent mr-2"></i>Formation professionnalisante</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Stages en entreprise</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Réseau professionnel</li>
                        </ul>
                    </div>
                </div>

                <!-- Économie et Gestion -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-slide-up" style="animation-delay: 0.3s">
                    <div class="bg-gradient-to-r from-purple-600 to-purple-800 p-6">
                        <i class="fas fa-chart-line text-4xl text-white mb-4"></i>
                        <h3 class="text-xl font-bold text-white">Économie & Gestion</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Économie, Gestion, Finance, Marketing, Comptabilité.</p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><i class="fas fa-check text-accent mr-2"></i>Partenariats entreprises</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Incubateur d'entreprises</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Formation continue</li>
                        </ul>
                    </div>
                </div>

                <!-- Médecine -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-slide-up" style="animation-delay: 0.4s">
                    <div class="bg-gradient-to-r from-red-500 to-pink-600 p-6">
                        <i class="fas fa-heartbeat text-4xl text-white mb-4"></i>
                        <h3 class="text-xl font-bold text-white">Médecine & Santé</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Médecine générale, Pharmacie, Odontologie, Sciences infirmières.</p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><i class="fas fa-check text-accent mr-2"></i>CHU moderne</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Formations spécialisées</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Recherche médicale</li>
                        </ul>
                    </div>
                </div>

                <!-- Technologie -->
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl animate-slide-up" style="animation-delay: 0.5s">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6">
                        <i class="fas fa-laptop-code text-4xl text-white mb-4"></i>
                        <h3 class="text-xl font-bold text-white">Sciences & Technologies</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-600 mb-4">Informatique, MIAGE, Génie civil, Télécommunications.</p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><i class="fas fa-check text-accent mr-2"></i>Technologies avancées</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Projets innovants</li>
                            <li><i class="fas fa-check text-accent mr-2"></i>Partenariats tech</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Plateforme GSCV+ -->
    <section id="platform" class="py-20 bg-gradient-to-r from-primary to-primary-light">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center animate-fade-in">
                <div class="mb-8">
                    <div class="w-24 h-24 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-6 animate-float">
                        <i class="fas fa-graduation-cap text-4xl text-white"></i>
                    </div>
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-6 font-montserrat">
                        Découvrez notre Plateforme Check Master
                    </h2>
                    <p class="text-xl text-white/90 max-w-4xl mx-auto mb-8 leading-relaxed">
                        La filière MIAGE (Méthodes Informatiques Appliquées à la Gestion des Entreprises) de l'UFHB dispose d'une plateforme moderne et innovante dédiée à la gestion de la commission de validation des étudiants en Master 2.
                    </p>
                </div>

                <!-- Fonctionnalités de la plateforme -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                    <div class="glass-effect rounded-2xl p-6 transform transition-all duration-300 hover:scale-105">
                        <div class="w-16 h-16 bg-primary/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-check text-3xl text-primary"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Validation des Rapports</h3>
                        <p class="text-gray-600">Processus de validation numérique des rapports de stage et mémoires en temps réel.</p>
                    </div>

                    <div class="glass-effect rounded-2xl p-6 transform transition-all duration-300 hover:scale-105" style="animation-delay: 0.1s">
                        <div class="w-16 h-16 bg-accent/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users-cog text-3xl text-accent"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Gestion Collaborative</h3>
                        <p class="text-gray-600">Interface collaborative pour les enseignants, étudiants et personnel administratif.</p>
                    </div>

                    <div class="glass-effect rounded-2xl p-6 transform transition-all duration-300 hover:scale-105" style="animation-delay: 0.2s">
                        <div class="w-16 h-16 bg-secondary/20 rounded-xl flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-chart-bar text-3xl text-secondary"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Tableaux de Bord</h3>
                        <p class="text-gray-600">Tableaux de bord analytiques et statistiques en temps réel pour le suivi des validations.</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="indexCM.php" 
                       class="bg-white text-primary px-8 py-4 rounded-lg font-semibold hover:bg-gray-100 transition-all duration-300 transform hover:scale-105 inline-flex items-center justify-center">
                        <i class="fas fa-rocket mr-2"></i>
                        Accéder à la plateforme
                    </a>
                    <a href="pageConnexion.php" 
                       class="bg-primary-light text-white px-8 py-4 rounded-lg font-semibold hover:bg-primary-lighter transition-all duration-300 transform hover:scale-105 inline-flex items-center justify-center border-2 border-white/30">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Se connecter
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
                            <i class="fas fa-graduation-cap text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">UFHB</h3>
                            <p class="text-gray-400 text-sm">Université Félix Houphouët-Boigny</p>
                        </div>
                    </div>
                    <p class="text-gray-400 mb-6 max-w-md">
                        Plus de 50 ans d'excellence académique au service de l'éducation supérieure en Côte d'Ivoire et en Afrique.
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
                            <i class="fab fa-youtube text-sm"></i>
                        </a>
                    </div>
                </div>

                <!-- Liens rapides -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Liens rapides</h4>
                    <ul class="space-y-2">
                        <li><a href="#accueil" class="text-gray-400 hover:text-white transition-colors">Accueil</a></li>
                        <li><a href="#about" class="text-gray-400 hover:text-white transition-colors">À propos</a></li>
                        <li><a href="#formations" class="text-gray-400 hover:text-white transition-colors">Formations</a></li>
                        <li><a href="#platform" class="text-gray-400 hover:text-white transition-colors">Plateforme</a></li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
                            Cocody, Abidjan, Côte d'Ivoire
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-2 text-primary"></i>
                            +225 22 44 81 00
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2 text-primary"></i>
                            info@univ-fhb.edu.ci
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="border-gray-800 my-8">
            
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">
                    © 2024 Université Félix Houphouët-Boigny. Tous droits réservés.
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
        // Gestion du slider hero
        let currentSlide = 0;
        const slides = document.querySelectorAll('.slide');
        const indicators = document.querySelectorAll('.slide-indicator');
        const totalSlides = slides.length;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.toggle('active', i === index);
            });
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('bg-white', i === index);
                indicator.classList.toggle('bg-white/50', i !== index);
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(currentSlide);
        }

        // Auto-play du slider
        setInterval(nextSlide, 5000);

        // Event listeners pour la navigation
        document.getElementById('next-slide').addEventListener('click', nextSlide);
        document.getElementById('prev-slide').addEventListener('click', prevSlide);

        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => {
                currentSlide = index;
                showSlide(currentSlide);
            });
        });

        // Gestion de la navigation
        const navbar = document.getElementById('navbar');
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        const backToTop = document.getElementById('back-to-top');

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                navbar.classList.add('bg-primary/95', 'backdrop-blur-sm');
                navbar.classList.remove('bg-transparent');
                backToTop.classList.remove('hidden');
            } else {
                navbar.classList.remove('bg-primary/95', 'backdrop-blur-sm');
                navbar.classList.add('bg-transparent');
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

        // Parallax effect léger sur les images
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const parallaxElements = document.querySelectorAll('.parallax-bg');
            
            parallaxElements.forEach(element => {
                const speed = 0.5;
                element.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    </script>
</body>
</html>