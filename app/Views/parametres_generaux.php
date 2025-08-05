<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres Généraux - Tableau de Bord Commission</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2c7aa7',
                        'primary-dark': '#0f3d5a',
                        'primary-50': '#f0f8ff',
                        'primary-100': '#e0f2fe',
                        'primary-200': '#bae6fd',
                        'primary-300': '#7dd3fc',
                        'primary-400': '#38bdf8',
                        'primary-500': '#1a5276',
                        'primary-600': '#0284c7',
                        'primary-700': '#0369a1',
                        'primary-800': '#075985',
                        'primary-900': '#0c4a6e',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen">
    

    <!-- Alert Info -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg shadow-sm">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Les modifications des paramètres généraux affectent le fonctionnement global du système. Veuillez procéder avec précaution.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages de succès -->
    <?php if (isset($_SESSION['messages']) && !empty($_SESSION['messages'])) { ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg shadow-sm animate-fade-in">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($_SESSION['messages']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const alert = document.querySelector('.bg-green-50');
                if (alert) {
                    alert.style.opacity = '0';
                    alert.style.visibility = 'hidden';
                    setTimeout(() => alert.remove(), 300);
                }
            }, 5000);
        </script>
    <?php 
        unset($_SESSION['messages']); 
    } ?>

    <!-- Container principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Section Header -->
        <div class="mb-8">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                    <i class="fas fa-cogs text-white text-lg"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Configuration du système</h2>
            </div>
            <p class="text-gray-600">Gérez tous les paramètres essentiels de votre application</p>
        </div>

        <!-- Grille des paramètres -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            
            <!-- Actions de la commission -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Actions de la commission</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-info-circle text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les actions disponibles pour la commission</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=actions" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Entreprises -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Entreprises</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-building text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez la liste des entreprises partenaires</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=entreprises" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Année académique -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Année académique</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Configurez les années académiques</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=annees_academiques" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Unités d'enseignements (UE) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Unités d'enseignements (UE)</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-tasks text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les unités d'enseignement</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=ue" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Élements constitutifs des unités d'enseignements (ECUE) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Élements constitutifs (ECUE)</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shield-alt text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les éléments constitutifs des UE</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=ecue" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Gestion des utilisateurs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Gestion des utilisateurs</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les utilisateurs du système</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=utilisateurs" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Type utilisateur -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Type utilisateur</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-tag text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les types d'utilisateurs</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=types_utilisateurs" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Groupe d'utilisateurs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Groupe d'utilisateurs</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users-cog text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les groupes d'utilisateurs</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=groupes_utilisateurs" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Fonctions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Fonctions</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-briefcase text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les fonctions des utilisateurs</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=fonctions" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Grades -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Grades</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-star text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les grades des utilisateurs</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=grades" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Spécialités -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Spécialités</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-graduation-cap text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les spécialités académiques</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=specialites" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Niveau d'accès aux données -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Niveau d'accès aux données</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-shield-alt text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les niveaux d'accès</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=niveaux_acces" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Niveaux d'approbation -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Niveaux d'approbation</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-double text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les niveaux d'approbation</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=niveaux_approbation" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Niveau d'étude -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Niveau d'étude</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-book text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les niveaux d'étude</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=niveaux_etudes" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Statut du jury -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Statut du jury</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-gavel text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les statuts du jury</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=statuts_jury" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Traitement -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Traitement</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cogs text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les types de traitement</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=traitements" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Chargé de compte rendu -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Chargé de compte rendu</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-pen-nib text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les enseignants chargés</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=enseignants" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Semestre -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Semestre</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-week text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les semestres académiques</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=semestres" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Gestion des frais d'inscription -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Frais d'inscription</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-money-bill-wave text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les tarifs d'inscription</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=frais_inscriptions" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>

            <!-- Promotion -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-primary">Promotion</h3>
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-primary text-lg"></i>
                        </div>
                    </div>
                    <p class="text-gray-600 text-sm mb-4">Gérez les promotions d'étudiants</p>
                </div>
                <div class="px-6 pb-6">
                    <a href="?liste=promotions" class="block w-full text-center px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium">
                        <i class="fas fa-list mr-2"></i>Accéder à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>