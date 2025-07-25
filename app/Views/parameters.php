<?php

// Initialisation du contrôleur
require_once '../app/Controllers/ParameterController.php';
$controller = new ParameterController();

// Récupération des données via le contrôleur
$data = $controller->viewParameters();

// Extraction des variables pour la vue
$userData = $data['userData'];
$userType = $data['userType'];
$profilePhoto = $data['profilePhoto'];
$errors = $data['errors'];
$success_message = $data['success_message'];
?>

<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres du Compte</title>
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
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 animate-slide-up">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Erreurs détectées</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
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

            <!-- Section profil -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8 animate-slide-up">
                <div class="flex items-center space-x-6">
                    <div class="relative">
                        <div class="w-24 h-24 rounded-full bg-primary/10 flex items-center justify-center overflow-hidden">
                            <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Photo de profil" 
                                 class="w-full h-full object-cover" id="profile-image">
                        </div>
                        <label for="photo-upload" class="absolute bottom-0 right-0 bg-primary text-white rounded-full p-2 hover:bg-primary-light transition-colors cursor-pointer">
                            <i class="fas fa-camera text-sm"></i>
                        </label>
                        <input type="file" id="photo-upload" accept="image/*" class="hidden">
                    </div>
                    <div>
                        
                        <h2 class="text-2xl font-bold text-gray-900">
                            <?php echo htmlspecialchars($_SESSION['user_fullname']); ?>
                        </h2>
                        <p class="text-primary font-medium"><?php echo htmlspecialchars($_SESSION['lib_user_type']); ?></p>
                        <p class="text-gray-600"><?php echo htmlspecialchars($userData['email'] ?? $userData['email_ens'] ?? $userData['email_personnel_adm'] ?? $userData['email_etd'] ?? ''); ?></p>
                    </div>
                </div>
            </div>

            <!-- Grille des paramètres -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Informations Personnelles -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-user mr-2 text-primary"></i>
                            Informations Personnelles
                        </h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" id="personal-info-form" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                                    <input type="text" id="nom" name="nom" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           value="<?php echo htmlspecialchars($userData['nom'] ?? $userData['nom_ens'] ?? $userData['nom_personnel_adm'] ?? ''); ?>" required>
                                </div>
                                <div>
                                    <label for="prenoms" class="block text-sm font-medium text-gray-700 mb-2">Prénoms *</label>
                                    <input type="text" id="prenoms" name="prenoms" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           value="<?php echo htmlspecialchars($userData['prenoms'] ?? $userData['prenoms_ens'] ?? $userData['prenoms_personnel_adm'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="adresse_mail" class="block text-sm font-medium text-gray-700 mb-2">Adresse email *</label>
                                    <input type="email" id="adresse_mail" name="adresse_mail" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           value="<?php echo htmlspecialchars($userData['email'] ?? $userData['email_ens'] ?? $userData['email_personnel_adm'] ?? $userData['email_etd'] ?? ''); ?>" required>
                                </div>
                                <div>
                                    <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone</label>
                                    <input type="tel" id="telephone" name="telephone" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           value="<?php echo htmlspecialchars($userData['telephone'] ?? $userData['num_tel_ens'] ?? $userData['tel_personnel_adm'] ?? $userData['num_tel_etd'] ?? ''); ?>">
                                </div>
                            </div>

                            <?php if ($userType === 'Étudiant'): ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="adresse" class="block text-sm font-medium text-gray-700 mb-2">Adresse *</label>
                                        <input type="text" id="adresse" name="adresse" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                               value="<?php echo htmlspecialchars($userData['adresse_etd'] ?? ''); ?>" required>
                                    </div>
                                    <div>
                                        <label for="ville" class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
                                        <input type="text" id="ville" name="ville" 
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                               value="<?php echo htmlspecialchars($userData['ville_etd'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div>
                                    <label for="pays" class="block text-sm font-medium text-gray-700 mb-2">Pays *</label>
                                    <input type="text" id="pays" name="pays" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                           value="<?php echo htmlspecialchars($userData['pays_etd'] ?? ''); ?>" required>
                                </div>
                            <?php endif; ?>

                            <div class="flex justify-end">
                                <button type="submit" name="save-modification" 
                                        class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center">
                                    <i class="fas fa-save mr-2"></i>
                                    Enregistrer les modifications
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Changement de Mot de Passe -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-key mr-2 text-primary"></i>
                            Changement de Mot de Passe
                        </h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" id="password-form" class="space-y-6">
                            <div>
                                <label for="ancien_mdp" class="block text-sm font-medium text-gray-700 mb-2">Ancien mot de passe *</label>
                                <input type="password" id="ancien_mdp" name="ancien_mdp" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nouveau_mdp" class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe *</label>
                                    <input type="password" id="nouveau_mdp" name="nouveau_mdp" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                    <p class="text-sm text-gray-500 mt-1">Le mot de passe doit contenir au moins 8 caractères</p>
                                </div>
                                <div>
                                    <label for="confirm_mdp" class="block text-sm font-medium text-gray-700 mb-2">Confirmer le nouveau mot de passe *</label>
                                    <input type="password" id="confirm_mdp" name="confirm_mdp" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" required>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" name="save-modification" value="change-password"
                                        class="px-6 py-3 bg-warning text-white rounded-lg hover:bg-yellow-600 transition-colors duration-200 flex items-center">
                                    <i class="fas fa-key mr-2"></i>
                                    Changer le mot de passe
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Préférences de Notification -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-bell mr-2 text-primary"></i>
                            Préférences de Notification
                        </h3>
                    </div>
                    <div class="p-6">
                        <form method="POST" id="notification-form" class="space-y-6">
                            <div class="space-y-4">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notif_email" value="1" 
                                           class="rounded border-gray-300 text-primary focus:ring-primary"
                                           <?php echo ($userData['notif_email'] ?? false) ? 'checked' : ''; ?>>
                                    <span class="ml-3 text-sm text-gray-700">Recevoir les notifications par email</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="notif_sms" value="1" 
                                           class="rounded border-gray-300 text-primary focus:ring-primary"
                                           <?php echo ($userData['notif_sms'] ?? false) ? 'checked' : ''; ?>>
                                    <span class="ml-3 text-sm text-gray-700">Recevoir les notifications par SMS</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="notif_push" value="1" 
                                           class="rounded border-gray-300 text-primary focus:ring-primary"
                                           <?php echo ($userData['notif_push'] ?? false) ? 'checked' : ''; ?>>
                                    <span class="ml-3 text-sm text-gray-700">Recevoir les notifications push</span>
                                </label>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" name="save-notifications" 
                                        class="px-6 py-3 bg-secondary text-white rounded-lg hover:bg-orange-600 transition-colors duration-200 flex items-center">
                                    <i class="fas fa-bell mr-2"></i>
                                    Enregistrer les préférences
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sécurité du Compte -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-shield-alt mr-2 text-primary"></i>
                            Sécurité du Compte
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">Authentification à deux facteurs</h4>
                                <p class="text-sm text-gray-600">Ajoutez une couche de sécurité supplémentaire</p>
                            </div>
                            <button onclick="enable2FA()" 
                                    class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors duration-200">
                                <i class="fas fa-shield-alt mr-2"></i>
                                Activer
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">Sessions actives</h4>
                                <p class="text-sm text-gray-600">Gérez vos sessions sur différents appareils</p>
                            </div>
                            <button onclick="manageSessions()" 
                                    class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors duration-200">
                                <i class="fas fa-desktop mr-2"></i>
                                Gérer
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900">Historique de connexion</h4>
                                <p class="text-sm text-gray-600">Consultez l'historique de vos connexions</p>
                            </div>
                            <button onclick="viewLoginHistory()" 
                                    class="px-4 py-2 border border-primary text-primary rounded-lg hover:bg-primary hover:text-white transition-colors duration-200">
                                <i class="fas fa-history mr-2"></i>
                                Voir
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Zone Dangereuse -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden mt-8 border border-red-200 animate-slide-up">
                <div class="px-6 py-4 border-b border-red-200 bg-red-50">
                    <h3 class="text-lg font-semibold text-red-900">
                        <i class="fas fa-exclamation-triangle mr-2 text-red-600"></i>
                        Zone Dangereuse
                    </h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between p-4 border border-red-200 rounded-lg bg-red-50">
                        <div>
                            <h4 class="font-medium text-red-900">Supprimer le compte</h4>
                            <p class="text-sm text-red-700">Cette action est irréversible. Toutes vos données seront définitivement supprimées.</p>
                        </div>
                        <button onclick="deleteAccount()" 
                                class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors duration-200">
                            <i class="fas fa-trash mr-2"></i>
                            Supprimer le compte
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation pour la suppression -->
    <div id="delete-account-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-bounce-in">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-danger/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-exclamation-triangle text-danger text-lg"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmer la suppression</h3>
                </div>
                <p class="text-gray-600 mb-4">Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.</p>
                <p class="text-gray-600 mb-4">Tapez "SUPPRIMER" pour confirmer :</p>
                <input type="text" id="delete-confirmation" placeholder="SUPPRIMER"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-danger focus:border-transparent mb-6">
                <div class="flex gap-3 justify-end">
                    <button onclick="closeDeleteModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                        Annuler
                    </button>
                    <button onclick="confirmDeleteAccount()" 
                            class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-red-600 transition-colors">
                        Supprimer définitivement
                    </button>
                </div>
            </div>
            <button onclick="closeDeleteModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <script>
        // Gestion du changement de photo de profil
        document.getElementById('photo-upload').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const formData = new FormData();
                formData.append('profile_photo', file);

                fetch('./assets/traitements/upload_profile_photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profile-image').src = data.photo_url;
                        showNotification('Photo de profil mise à jour avec succès !', 'success');
                    } else {
                        showNotification('Erreur lors du téléchargement : ' + data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Une erreur est survenue lors du téléchargement.', 'error');
                });
            }
        });

        // Validation du formulaire de mot de passe
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const nouveauMdp = document.getElementById('nouveau_mdp').value;
            const confirmMdp = document.getElementById('confirm_mdp').value;

            if (nouveauMdp !== confirmMdp) {
                e.preventDefault();
                showNotification('Les mots de passe ne correspondent pas.', 'error');
                return false;
            }

            if (nouveauMdp.length < 8) {
                e.preventDefault();
                showNotification('Le mot de passe doit contenir au moins 8 caractères.', 'error');
                return false;
            }
        });

        // Fonctions de sécurité
        function enable2FA() {
            showNotification('Fonctionnalité d\'authentification à deux facteurs à implémenter.', 'info');
        }

        function manageSessions() {
            showNotification('Gestion des sessions à implémenter.', 'info');
        }

        function viewLoginHistory() {
            showNotification('Historique de connexion à implémenter.', 'info');
        }

        // Fonctions de suppression de compte
        function deleteAccount() {
            document.getElementById('delete-account-modal').classList.remove('hidden');
            document.getElementById('delete-account-modal').classList.add('flex');
        }

        function closeDeleteModal() {
            document.getElementById('delete-account-modal').classList.add('hidden');
            document.getElementById('delete-account-modal').classList.remove('flex');
            document.getElementById('delete-confirmation').value = '';
        }

        function confirmDeleteAccount() {
            const confirmation = document.getElementById('delete-confirmation').value;
            if (confirmation === 'SUPPRIMER') {
                if (confirm('Êtes-vous absolument sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) {
                    fetch('./assets/traitements/delete_account.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'confirm_delete=1'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Compte supprimé avec succès.', 'success');
                            setTimeout(() => window.location.href = '../login.php', 2000);
                        } else {
                            showNotification('Erreur lors de la suppression : ' + data.error, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Erreur:', error);
                        showNotification('Une erreur est survenue lors de la suppression.', 'error');
                    });
                }
            } else {
                showNotification('Veuillez taper "SUPPRIMER" pour confirmer.', 'error');
            }
        }

        // Système de notifications
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;
            
            const colors = {
                success: 'bg-green-500 text-white',
                error: 'bg-red-500 text-white',
                info: 'bg-blue-500 text-white',
                warning: 'bg-yellow-500 text-white'
            };
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-exclamation-circle',
                info: 'fas fa-info-circle',
                warning: 'fas fa-exclamation-triangle'
            };
            
            notification.className += ` ${colors[type]}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="${icons[type]} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Animation d'entrée
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            // Animation de sortie
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }

        // Fermer la modale en cliquant en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('delete-account-modal');
            if (event.target === modal) {
                closeDeleteModal();
            }
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
    </script>

</body>

</html>