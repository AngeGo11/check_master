<?php

use App\Models\Enseignant;
use App\Models\Utilisateur;

require_once __DIR__ . '/../Controllers/ValidationController.php';
require_once __DIR__ . '/../Controllers/UtilisateurController.php';
require_once __DIR__ . '/../Controllers/EnseignantController.php';




// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');

// Récupération du login utilisateur connecté
$login = $_SESSION['login_utilisateur'];
$ensController = new EnseignantController($pdo);
$id_ens = $ensController->getEnseignantByLogin($login);
$controller = new ValidationController();



if (!isset($rapport) || !isset($messages) || !isset($validations) || !isset($user_validation)) {
    $id_rapport = isset($_GET['rapport']) ? $_GET['rapport'] : null;
    $rapport = $controller->getRapports($id_rapport);
    $messages = $controller->getMessages($id_rapport);
    $validations = $controller->getValidations($id_rapport);
    $user_validation = $controller->getValidationByEns($id_rapport, $id_ens);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $message = $_POST['message'];
    $id_rapport = $_GET['rapport'];
    $controller->addMessages($id_rapport, $id_ens, $message);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'valider') {
    $decision = $_POST['decision'] ?? null;
    $commentaire = $_POST['commentaire_validation'] ?? '';

    $controller->validerRapports( $id_rapport,$id_ens, $commentaire, $decision);
}


if (!$rapport) {
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processus de Validation des Rapports</title>
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
                    }
                }
            }
        }
    </script>
</head>

<body class="h-full bg-gray-50">
    <div class="min-h-full">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-info-circle text-4xl text-gray-400"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Aucun rapport en cours d'évaluation</h2>
                <p class="text-gray-600 mb-8">Il n'y a actuellement aucun rapport disponible pour validation.</p>
                <a href="?page=analyses" 
                   class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-primary-light transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour à la liste des rapports
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processus de Validation des Rapports</title>
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


            <!-- Barre de progression -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8 animate-slide-up">
                <div class="mb-6">
                    <div class="flex justify-between text-xs font-medium text-gray-600 mb-2">
                        <span>Étape 1</span>
                        <span>Étape 2</span>
                        <span>Étape 3</span>
                        <span>Étape 4</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-primary h-2 rounded-full transition-all duration-500" id="progress-bar" style="width: 25%"></div>
                    </div>
                </div>

                <!-- Étapes -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="step-indicator active" data-step="1">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-medium mr-3">
                                1
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Consultation</h4>
                                <p class="text-xs text-gray-500">Lecture du rapport</p>
                            </div>
                        </div>
                    </div>
                    <div class="step-indicator" data-step="2">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium mr-3">
                                2
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Discussion</h4>
                                <p class="text-xs text-gray-500">Commentaires et délibération</p>
                            </div>
                        </div>
                    </div>
                    <div class="step-indicator" data-step="3">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium mr-3">
                                3
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Décision</h4>
                                <p class="text-xs text-gray-500">Validation ou rejet</p>
                            </div>
                        </div>
                    </div>
                    <div class="step-indicator" data-step="4">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium mr-3">
                                4
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Compte rendu</h4>
                                <p class="text-xs text-gray-500">Génération du document</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu des étapes -->
            <div class="space-y-8">
                <!-- Étape 1 - Consultation -->
                <div class="step-content active" id="step-1">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-primary to-primary-light">
                            <h3 class="text-lg font-semibold text-white">
                                <i class="fas fa-book-open mr-2"></i>
                                Étape 1 : Consultation du rapport
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Informations du rapport -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Informations sur le rapport</h4>
                                    <div class="space-y-4">
                                        <div class="flex justify-between py-2 border-b border-gray-100">
                                            <span class="font-medium text-gray-600">Rapport N°</span>
                                            <span class="text-gray-900"><?php echo htmlspecialchars($rapport['id_rapport_etd'] ?? ''); ?></span>
                                        </div>
                                        <div class="flex justify-between py-2 border-b border-gray-100">
                                            <span class="font-medium text-gray-600">Étudiant</span>
                                            <span class="text-gray-900"><?php echo htmlspecialchars(($rapport['nom_etd'] ?? '') . ' ' . ($rapport['prenom_etd'] ?? '')); ?></span>
                                        </div>
                                        <div class="flex justify-between py-2 border-b border-gray-100">
                                            <span class="font-medium text-gray-600">Titre</span>
                                            <span class="text-gray-900"><?php echo htmlspecialchars($rapport['theme_memoire'] ?? ''); ?></span>
                                        </div>
                                        <div class="flex justify-between py-2 border-b border-gray-100">
                                            <span class="font-medium text-gray-600">Date de soumission</span>
                                            <span class="text-gray-900"><?php echo htmlspecialchars($rapport['date_approbation'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6 flex flex-col sm:flex-row gap-3">
                                        <button onclick="openPreviewModal()" 
                                                class="flex-1 bg-primary text-white px-4 py-3 rounded-lg hover:bg-primary-light transition-colors flex items-center justify-center">
                                            <i class="fas fa-eye mr-2"></i>
                                            Aperçu du rapport
                                        </button>
                                        <button onclick="downloadAsPDF()" 
                                                class="flex-1 bg-secondary text-white px-4 py-3 rounded-lg hover:bg-yellow-600 transition-colors flex items-center justify-center">
                                            <i class="fas fa-file-pdf mr-2"></i>
                                            Télécharger PDF
                                        </button>
                                    </div>
                                </div>

                                <!-- Commentaire du personnel administratif -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Commentaire du personnel administratif</h4>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <textarea class="w-full h-32 border-0 bg-transparent resize-none focus:outline-none" 
                                                  readonly><?php echo htmlspecialchars($rapport['com_appr'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                            <a href="/GSCV+/public/app.php?page=analyses" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Retour à la liste
                            </a>
                            <button class="next-step inline-flex items-center px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                Étape suivante
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Étape 2 - Discussion -->
                <div class="step-content hidden" id="step-2">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-accent to-green-600">
                            <h3 class="text-lg font-semibold text-white">
                                <i class="fas fa-comments mr-2"></i>
                                Étape 2 : Discussion et Délibération
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Formulaire de message -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">Envoyer un message</h4>
                                    <form method="POST" id="message-form" action="/GSCV+/public/app.php?page=validations&rapport=<?php echo urlencode($rapport['id_rapport_etd']); ?>">
                                        <input type="hidden" name="action" value="send_message">
                                        <textarea name="message" 
                                                  class="w-full h-32 border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" 
                                                  placeholder="Rédigez votre commentaire ou remarque sur le rapport..." 
                                                  required></textarea>
                                        <button type="submit" 
                                                class="mt-3 w-full bg-accent text-white px-4 py-3 rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center">
                                            <i class="fas fa-paper-plane mr-2"></i>
                                            Publier le message
                                        </button>
                                    </form>
                                </div>

                                <!-- Fil de discussion -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-file-alt mr-2"></i>
                                        Fil de discussion
                                    </h4>
                                    <div class="bg-gray-50 rounded-lg p-4 h-64 overflow-y-auto" id="chat-messages">
                                        <?php if (!empty($messages)): ?>
                                            <?php foreach ($messages as $message): ?>
                                                <div class="mb-4 p-3 bg-white rounded-lg shadow-sm">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <span class="font-medium text-primary"><?php echo htmlspecialchars($message['auteur']); ?></span>
                                                        <span class="text-xs text-gray-500"><?php echo $message['date']; ?></span>
                                                    </div>
                                                    <p class="text-gray-700"><?php echo htmlspecialchars($message['contenu']); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-center text-gray-500 py-8">
                                                <i class="fas fa-comments text-2xl mb-2"></i>
                                                <p>Aucun message pour ce rapport.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                            <button class="prev-step inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Étape précédente
                            </button>
                            <button class="next-step inline-flex items-center px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                Étape suivante
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Étape 3 - Décision -->
                <div class="step-content hidden" id="step-3">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-warning to-yellow-600">
                            <h3 class="text-lg font-semibold text-white">
                                <i class="fas fa-gavel mr-2"></i>
                                Étape 3 : Décision finale
                            </h3>
                        </div>
                        <div class="p-8">
                            <h4 class="text-xl font-semibold text-gray-900 mb-8 text-center">Votre décision finale</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
                                <!-- Option Valider -->
                                <div class="decision-card group cursor-pointer" id="btnExcellent">
                                    <div class="bg-gradient-to-br from-accent to-green-600 rounded-2xl p-8 text-white transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                                        <div class="text-center">
                                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <i class="fas fa-award text-3xl"></i>
                                            </div>
                                            <h5 class="text-2xl font-bold mb-3">Valider</h5>
                                            <p class="text-green-100">Rapport respectant tous les critères de validation</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Option Rejeter -->
                                <div class="decision-card group cursor-pointer" id="btnRejeter">
                                    <div class="bg-gradient-to-br from-danger to-red-600 rounded-2xl p-8 text-white transform transition-all duration-300 hover:scale-105 hover:shadow-xl">
                                        <div class="text-center">
                                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <i class="fas fa-times-circle text-3xl"></i>
                                            </div>
                                            <h5 class="text-2xl font-bold mb-3">Rejeter</h5>
                                            <p class="text-red-100">Rapport nécessitant des modifications majeures</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                            <button class="prev-step inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Étape précédente
                            </button>
                            <button class="next-step inline-flex items-center px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                                Étape suivante
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Étape 4 - Compte rendu -->
                <div class="step-content hidden" id="step-4">
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden animate-slide-up">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-secondary to-yellow-600">
                            <h3 class="text-lg font-semibold text-white">
                                <i class="fas fa-file-alt mr-2"></i>
                                Étape 4 : Génération du compte rendu
                            </h3>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Formulaire de commentaire -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-pen mr-2"></i>
                                        Justification de la décision
                                    </h4>
                                    <textarea id="commentaire_validation" 
                                              name="commentaire_validation" 
                                              class="w-full h-32 border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent" 
                                              placeholder="Rédigez votre commentaire final pour le compte rendu..."></textarea>
                                    <button class="mt-3 w-full bg-primary text-white px-4 py-3 rounded-lg hover:bg-primary-light transition-colors flex items-center justify-center" 
                                            id="btn-update-preview">
                                        <i class="fas fa-sync-alt mr-2"></i>
                                        Mettre à jour l'aperçu
                                    </button>
                                </div>

                                <!-- Aperçu du compte rendu -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                                        <i class="fas fa-file-alt mr-2"></i>
                                        Fiche de validation
                                    </h4>
                                    <div class="bg-gray-50 rounded-lg p-6 text-sm">
                                        <div class="mb-6">
                                            <h5 class="font-bold text-gray-900 mb-3">Informations générales</h5>
                                            <div class="grid grid-cols-2 gap-4 text-xs">
                                                <div>
                                                    <span class="font-medium">Rapport N°:</span>
                                                    <span id="cr-rapport-id"><?php echo htmlspecialchars($rapport['id_rapport_etd'] ?? ''); ?></span>
                                                </div>
                                                <div>
                                                    <span class="font-medium">Titre:</span>
                                                    <span id="cr-titre"><?php echo htmlspecialchars($rapport['theme_memoire'] ?? ''); ?></span>
                                                </div>
                                                <div>
                                                    <span class="font-medium">Étudiant:</span>
                                                    <span id="cr-etudiant"><?php echo htmlspecialchars(($rapport['nom_etd'] ?? '') . ' ' . ($rapport['prenom_etd'] ?? '')); ?></span>
                                                </div>
                                                <div>
                                                    <span class="font-medium">Date:</span>
                                                    <span id="cr-date-validation"><?php echo date('Y-m-d à H:i'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-4">
                                            <h5 class="font-bold text-gray-900 mb-2">Évaluation</h5>
                                            <p class="mb-2">
                                                <span class="font-medium">Décision:</span>
                                                <span class="decision-highlight font-bold" id="cr-decision">En attente de validation</span>
                                            </p>
                                            <p class="mb-2">
                                                <span class="font-medium">Commentaire final:</span>
                                            </p>
                                            <div class="bg-white p-3 rounded border" id="cr-commentaire-final">
                                                <?php echo isset($user_validation['com_validation']) ? htmlspecialchars($user_validation['com_validation']) : 'Aucun commentaire'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
                            <button class="prev-step inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Étape précédente
                            </button>
                            <button class="inline-flex items-center px-6 py-2 bg-accent text-white rounded-lg hover:bg-green-600 transition-colors" 
                                    id="btn-terminer">
                                <i class="fas fa-check-circle mr-2"></i>
                                Terminer et retourner à la liste
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="confirmationModal">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 animate-bounce-in">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-accent/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-check-circle text-accent text-lg"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">Confirmation</h3>
                </div>
                <p class="text-gray-600 mb-6">Votre décision a été enregistrée avec succès.</p>
                <div class="flex justify-end">
                    <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors" 
                            id="btn-confirmer">
                        OK
                    </button>
                </div>
            </div>
            <button class="absolute top-4 right-4 text-gray-400 hover:text-gray-600" onclick="closeConfirmationModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Modal d'aperçu du rapport -->
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50" id="previewModal">
        <div class="bg-white rounded-lg shadow-xl w-11/12 h-5/6 max-w-6xl">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Aperçu du rapport</h3>
                <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4 h-full overflow-auto" id="reportBody">
                <!-- Contenu du rapport chargé ici -->
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentStepIndex = 0;
        let decisionTemporaire = null;
        let commentaireTemporaire = '';

        // Gestion des étapes
        function updateSteps(index) {
            // Mettre à jour la barre de progression
            const progressBar = document.getElementById('progress-bar');
            progressBar.style.width = `${(index + 1) * 25}%`;

            // Mettre à jour les indicateurs d'étapes
            const stepIndicators = document.querySelectorAll('.step-indicator');
            stepIndicators.forEach((indicator, i) => {
                const circle = indicator.querySelector('div > div');
                if (i < index) {
                    circle.className = 'w-8 h-8 bg-accent text-white rounded-full flex items-center justify-center text-sm font-medium mr-3';
                    circle.innerHTML = '<i class="fas fa-check"></i>';
                } else if (i === index) {
                    circle.className = 'w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center text-sm font-medium mr-3';
                    circle.textContent = i + 1;
                    indicator.classList.add('active');
                } else {
                    circle.className = 'w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-medium mr-3';
                    circle.textContent = i + 1;
                    indicator.classList.remove('active');
                }
            });

            // Afficher/masquer le contenu des étapes
            const stepContents = document.querySelectorAll('.step-content');
            stepContents.forEach((content, i) => {
                if (i === index) {
                    content.classList.remove('hidden');
                    content.classList.add('active');
                } else {
                    content.classList.add('hidden');
                    content.classList.remove('active');
                }
            });
        }

        // Navigation
        document.addEventListener('click', function(e) {
            if (e.target.closest('.next-step')) {
                if (currentStepIndex < 3) {
                    currentStepIndex++;
                    updateSteps(currentStepIndex);
                }
            }
            
            if (e.target.closest('.prev-step')) {
                if (currentStepIndex > 0) {
                    currentStepIndex--;
                    updateSteps(currentStepIndex);
                }
            }
        });

        // Gestion des décisions
        document.getElementById('btnExcellent').addEventListener('click', function(e) {
            e.preventDefault();
            decisionTemporaire = 'Validé';
            document.getElementById('cr-decision').textContent = 'Validé';
            document.getElementById('cr-decision').className = 'decision-highlight font-bold text-accent';
            this.classList.add('ring-4', 'ring-accent');
            document.getElementById('btnRejeter').classList.remove('ring-4', 'ring-danger');
        });

        document.getElementById('btnRejeter').addEventListener('click', function(e) {
            e.preventDefault();
            decisionTemporaire = 'Rejeté';
            document.getElementById('cr-decision').textContent = 'Rejeté';
            document.getElementById('cr-decision').className = 'decision-highlight font-bold text-danger';
            this.classList.add('ring-4', 'ring-danger');
            document.getElementById('btnExcellent').classList.remove('ring-4', 'ring-accent');
        });

        // Mise à jour du commentaire
        document.getElementById('btn-update-preview').addEventListener('click', function() {
            const commentaire = document.getElementById('commentaire_validation').value;
            document.getElementById('cr-commentaire-final').textContent = commentaire || 'Aucun commentaire';
            commentaireTemporaire = commentaire;
        });

        // Terminer le processus
        document.getElementById('btn-terminer').addEventListener('click', function() {
            if (!decisionTemporaire) {
                alert('Veuillez d\'abord prendre une décision (Valider ou Rejeter)');
                return;
            }

            document.getElementById('confirmationModal').classList.remove('hidden');
            document.getElementById('confirmationModal').classList.add('flex');
        });

        // Confirmation finale
        document.getElementById('btn-confirmer').addEventListener('click', function() {
            const commentaireFinal = document.getElementById('commentaire_validation').value;

            const formData = new FormData();
            formData.append('action', 'valider');
            formData.append('decision', decisionTemporaire);
            formData.append('commentaire_validation', commentaireFinal);
            formData.append('id_ens', '<?php echo $id_ens; ?>');
            formData.append('id_rapport', '<?php echo $id_rapport; ?>');

            fetch('../public/assets/traitements/ajax_validation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeConfirmationModal();
                    window.location.href = '/GSCV+/public/app.php?page=consultations';
                } else {
                    alert(data.error || 'Erreur lors de la validation');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'enregistrement');
            });
        });

        function closeConfirmationModal() {
            document.getElementById('confirmationModal').classList.add('hidden');
            document.getElementById('confirmationModal').classList.remove('flex');
        }

        // Aperçu et téléchargement
        function openPreviewModal() {
            const modal = document.getElementById('previewModal');
            const reportBody = document.getElementById('reportBody');
            const rapportPath = '<?php echo $rapport['fichier_rapport']; ?>';

            if (!rapportPath.toLowerCase().endsWith('.pdf')) {
                alert('Le fichier n\'est pas un PDF valide.');
                return;
            }

            reportBody.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i><p class="mt-2 text-gray-600">Chargement du PDF...</p></div>';
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            fetch(rapportPath)
            .then(response => {
                if (!response.ok) throw new Error(`Erreur HTTP: ${response.status}`);
                return response.blob();
            })
            .then(blob => {
                const object = document.createElement('object');
                object.data = URL.createObjectURL(blob);
                object.type = 'application/pdf';
                object.width = '100%';
                object.height = '100%';
                reportBody.innerHTML = '';
                reportBody.appendChild(object);
            })
            .catch(error => {
                console.error('Erreur:', error);
                reportBody.innerHTML = `<div class="text-center py-8 text-red-600"><i class="fas fa-exclamation-triangle text-2xl mb-2"></i><p>Erreur lors du chargement du PDF</p></div>`;
            });
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
            document.getElementById('previewModal').classList.remove('flex');
            document.getElementById('reportBody').innerHTML = '';
        }

        function downloadAsPDF() {
            try {
                const rapportPath = '<?php echo $rapport['fichier_rapport']; ?>';
                if (!rapportPath.toLowerCase().endsWith('.pdf')) {
                    throw new Error('Le fichier n\'est pas un PDF valide.');
                }

                const link = document.createElement('a');
                link.href = rapportPath;
                link.download = '<?php echo $rapport["nom_etd"] . "_" . $rapport["prenom_etd"] . "_" . date("Y-m-d") . ".pdf"; ?>';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors du téléchargement: ' + error.message);
            }
        }

        // Gestion des messages
        document.getElementById('message-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageInput = this.querySelector('textarea[name="message"]');
            const submitButton = this.querySelector('button[type="submit"]');

            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Envoi en cours...';

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                messageInput.value = '';
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Publier le message';
                
                // Recharger les messages
                location.reload();
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de l\'envoi du message.');
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Publier le message';
            });
        });

        // Initialisation
        updateSteps(currentStepIndex);
    </script>
</body>
</html>