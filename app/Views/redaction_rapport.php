<?php

/**
 * Page de rédaction de rapport
 * 
 * Cette page permet aux étudiants de créer et rédiger leur rapport de stage
 * Fonctionnalités :
 * - Chargement de modèles de rapport
 * - Éditeur en temps réel avec barre d'outils
 * - Sauvegarde automatique
 * - Export PDF
 * - Soumission du rapport final
 */

// Configuration et dépendances
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Controllers/RapportController.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialisation du contrôleur
$rapportController = new RapportController($pdo);

// Récupération des données utilisateur
$data = $rapportController->index($_SESSION['user_id']);

if (isset($data['error'])) {
    die($data['error']);
}

// Extraction des données
$student_data = $data['studentData'];
$hasExistingReport = $data['hasExistingReport'];
$rapport_status = $data['reportStatus'];
$eligibility_status = $data['eligibilityStatus'];

// Variables de session
$student_id = $student_data['num_etd'];
$name_report = $student_data['nom_etd'] . '_' . $student_data['prenom_etd'] . '_' . date('Y-m-d');
$_SESSION['name_report'] = $name_report;

// ========================================
// TRAITEMENT DU FORMULAIRE DE CRÉATION
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_report') {

    // Récupération et validation des données
    $themeMemoire = trim($_POST['theme_memoire'] ?? '');
    $content = $_POST['content'] ?? '';
    $studentId = $_POST['student_id'] ?? '';

    // Validation des champs obligatoires
    if (empty($themeMemoire)) {
        $_SESSION['error_message'] = "Le thème du mémoire est obligatoire.";
        $redirectToError = true;
    } elseif (empty($content)) {
        $_SESSION['error_message'] = "Aucun contenu fourni pour le rapport.";
        $redirectToError = true;
    } else {

        // Traitement de sauvegarde
        try {
            // Génération du nom de fichier unique
            $htmlFileName = $name_report . '_' . date('Y-m-d_H-i-s') . '.html';
            $filePath = 'storage/uploads/rapports/' . $htmlFileName;

            // Création du dossier de destination
            $uploadDir = __DIR__ . '/../../storage/uploads/rapports/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Sauvegarde du fichier HTML
            if (file_put_contents($uploadDir . $htmlFileName, $content)) {

                // Création en base de données
                if ($rapportController->createReport($studentId, $themeMemoire, $filePath)) {
                    $_SESSION['success_message'] = "Rapport créé avec succès !";
                    // Utiliser JavaScript pour la redirection au lieu de header()
                    echo "<script>
                        // Attendre un peu pour que le message soit enregistré
                        setTimeout(function() {
                            window.location.href = '?page=rapports.php';
                        }, 100);
                    </script>";
                    exit();
                } else {
                    $_SESSION['error_message'] = "Erreur lors de l'enregistrement en base de données.";
                    $redirectToError = true;
                }
            } else {
                $_SESSION['error_message'] = "Erreur lors de la sauvegarde du fichier.";
                $redirectToError = true;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Erreur système : " . $e->getMessage();
            $redirectToError = true;
        }
    }
    
    // Redirection en cas d'erreur
    if (isset($redirectToError) && $redirectToError) {
        echo "<script>
            // Attendre un peu pour que le message soit enregistré
            setTimeout(function() {
                window.location.href = '?page=redaction_rapport';
            }, 100);
        </script>";
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rédaction du Rapport - GSCV+</title>

    <!-- Styles externes -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Candara:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Configuration Tailwind -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1a5276',
                        'primary-light': '#2c7aa7',
                        'primary-dark': '#0f3d5a',
                        'primary-second': '#1a5276db'
                    }
                }
            }
        }
    </script>

    <style>
        input,
        select {
            color: #000;
        }

        /* Styles personnalisés pour la scrollbar */
        #text-editor::-webkit-scrollbar {
            width: 12px;
        }

        #text-editor::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 6px;
        }

        #text-editor::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 6px;
            border: 2px solid #f1f5f9;
        }

        #text-editor::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }

        /* Pour Firefox */
        #text-editor {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f1f5f9;
        }

        /* Styles pour isoler le contenu des modèles */
        #text-editor {
            background-color: white !important;
            color: #1f2937 !important;
            font-family: 'Candara', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            line-height: 1.6 !important;
        }

        #text-editor * {
            color: #1f2937 !important;
            font-family: inherit !important;
        }

        /* Styles spécifiques pour les modèles de rapport */
        #text-editor .rapport-template {
            background-color: white !important;
            color: #1f2937 !important;
            font-family: 'Candara', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            line-height: 1.6 !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 40px !important;
        }

        #text-editor .rapport-template * {
            color: #1f2937 !important;
            font-family: inherit !important;
        }

        /* Assurer que les images s'affichent correctement */
        #text-editor .rapport-template img {
            display: block !important;
            max-width: 100% !important;
            height: auto !important;
        }

        /* Assurer que les tokens restent visibles */
        #text-editor [style*="color"] {
            color: #000 !important;
        }

        /* Corriger les problèmes de flexbox dans l'éditeur */
        #text-editor .rapport-template div[style*="display: flex"] {
            display: flex !important;
            align-items: flex-start !important;
            justify-content: space-between !important;
        }

        /* Styles pour les sauts de page */
        #text-editor .page-break {
            page-break-before: always !important;
            break-before: page !important;
            margin-top: 40px !important;
            padding-top: 40px !important;
            border-top: 2px solid #e5e7eb !important;
            min-height: 100px !important;
        }

        /* Styles pour l'impression PDF */
        @media print {
            .page-break {
                page-break-before: always !important;
                break-before: page !important;
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen font-['Candara']">

    <!-- ========================================
         HEADER DE NAVIGATION
         ======================================== -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">

                <!-- Navigation gauche -->
                <div class="flex items-center space-x-4">
                    <a href="rapports.php" class="text-primary hover:text-primary-dark transition-colors">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-pen text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-primary">Rédaction du Rapport</h1>
                        <p class="text-gray-600 text-sm">Créez et rédigez votre rapport de stage</p>
                    </div>
                </div>


            </div>
        </div>
    </header>

    <!-- ========================================
         MESSAGES DE NOTIFICATION
         ======================================== -->
    <?php if (isset($_SESSION['success_message'])) : ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-green-700"><?php echo htmlspecialchars($_SESSION['success_message']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])) : ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo htmlspecialchars($_SESSION['error_message']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <!-- ========================================
         CONTAINER PRINCIPAL
         ======================================== -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- En-tête de section -->
        <div class="mb-8">
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                    <i class="fas fa-edit text-white text-lg"></i>
                </div>
                <h2 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Saisir votre rapport</h2>
            </div>
            <p class="text-gray-600 text-lg font-medium">Remplissez les informations à gauche et visualisez votre rapport en temps réel à droite.</p>
        </div>

        <!-- ========================================
             FORMULAIRE PRINCIPAL
             ======================================== -->
        <form id="report-form" method="POST" action="">
            <input type="hidden" name="action" value="create_report">
            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
            <input type="hidden" name="content" id="content-input">

            <!-- Layout en deux colonnes -->
            <div class="flex gap-6 h-[calc(100vh-300px)] min-h-[800px]">

                <!-- ========================================
                     COLONNE GAUCHE - FORMULAIRE DE SAISIE
                     ======================================== -->
                <div class="flex-shrink-0 w-96 bg-white/90 backdrop-blur-sm rounded-3xl shadow-2xl border border-white/20 p-8 overflow-y-auto">
                    <h3 class="text-xl font-bold text-gray-800 mb-8 flex items-center">
                        <i class="fas fa-edit mr-4 text-blue-600"></i>
                        Informations du rapport
                    </h3>

                    <!-- Sélection du modèle -->
                    <div class="mb-8">
                        <label for="model-select" class="block text-base font-semibold text-gray-700 mb-3">
                            <i class="fas fa-file-alt mr-3 text-blue-600"></i>Modèle de rapport
                        </label>
                        <select id="model-select" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                            <option value="">-- Sélectionner un modèle --</option>
                            <option value="template_rapport.html">Mémoire académique complet</option>
                        </select>
                    </div>

                    <!-- Champs de saisie -->
                    <div class="space-y-6">

                        <!-- Thème du mémoire -->
                        <div>
                            <label for="theme_report" class="block text-base font-semibold text-gray-700 mb-3">
                                <i class="fas fa-lightbulb mr-3 text-blue-600"></i>Thème du mémoire *
                            </label>
                            <input type="text"
                                id="theme_report"
                                name="theme_memoire"
                                placeholder="Ex: Développement d'une application web..."
                                class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 bg-white/80 backdrop-blur-sm"
                                required>
                        </div>

                        <!-- Nom de l'étudiant -->
                        <div>
                            <label for="nom_etudiant" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2"></i>Nom de l'étudiant *
                            </label>
                            <input type="text"
                                id="nom_etudiant"
                                name="nom_etudiant"
                                value="<?php echo htmlspecialchars($student_data['nom_etd'] . ' ' . $student_data['prenom_etd']); ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                                required>
                        </div>

                        <!-- Encadreur -->
                        <div>
                            <label for="encadreur" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-tie mr-2"></i>Encadreur académique
                            </label>
                            <input type="text"
                                id="encadreur"
                                name="encadreur"
                                placeholder="Nom de l'encadreur"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                        </div>

                        <!-- Maître de stage -->
                        <div>
                            <label for="maitre_stage" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-tie mr-2"></i>Maître de stage
                            </label>
                            <input type="text"
                                id="maitre_stage"
                                name="maitre_stage"
                                placeholder="Nom du maître de stage"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                        </div>

                        <!-- Nom de l'entreprise -->
                        <div>
                            <label for="nom_entreprise" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-building mr-2"></i>Nom de l'entreprise
                            </label>
                            <input type="text"
                                id="nom_entreprise"
                                name="nom_entreprise"
                                placeholder="Nom de l'entreprise d'accueil"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                        </div>

                        <!-- Logo de l'entreprise -->
                        <div>
                            <label for="logo_entreprise" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-image mr-2"></i>Logo de l'entreprise
                            </label>
                            <input type="file"
                                id="logo_entreprise"
                                name="logo_entreprise"
                                accept="image/*"
                                placeholder="Logo de l'entreprise"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                            <div class="mt-2 flex gap-2">
                                <button type="button" id="insert-logo-btn" class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                    <i class="fas fa-plus mr-1"></i>Insérer dans l'éditeur
                                </button>
                                <button type="button" id="remove-logo-btn" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition-colors">
                                    <i class="fas fa-trash mr-1"></i>Supprimer
                                </button>
                            </div>
                        </div>


                    </div>

                    <!-- Boutons d'action -->
                    <div class="mt-8">
                        <h4 class="text-sm font-semibold text-primary mb-4">Actions disponibles</h4>
                        <div class="space-y-3">
                            <button type="button"
                                id="load-template"
                                class="w-full px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium flex items-center justify-center">
                                <i class="fas fa-download mr-2"></i>Charger le modèle
                            </button>
                            <button type="button"
                                id="download-pdf"
                                class="w-full px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium flex items-center justify-center">
                                <i class="fas fa-file-pdf mr-2"></i>Exporter PDF
                            </button>
                            <button type="button"
                                id="save-report-btn"
                                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center justify-center">
                                <i class="fas fa-save mr-2"></i>Déposer le rapport
                            </button>
                        </div>
                    </div>

                    <!-- Statut de sauvegarde -->
                    <div id="save-status" class="mt-4 p-3 rounded-lg hidden">
                        <div class="flex items-center">
                            <i class="fas fa-circle-notch fa-spin mr-2"></i>
                            <span class="text-sm">Sauvegarde en cours...</span>
                        </div>
                    </div>
                </div>

                <!-- ========================================
                     COLONNE DROITE - ÉDITEUR DE RAPPORT
                     ======================================== -->
                <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">

                    <!-- En-tête de l'éditeur -->
                    <div class="px-6 py-4 bg-primary text-white flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold flex items-center">
                                <i class="fas fa-file-word mr-4"></i>
                                Éditeur de rapport
                            </h3>
                            <p class="text-base opacity-90 mt-2">Modifications en temps réel</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span id="word-count" class="text-base opacity-90 font-medium">0 mots</span>
                            <span class="text-base opacity-60">|</span>
                            <span id="auto-save-indicator" class="text-base opacity-90 font-medium">
                                <i class="fas fa-check mr-2"></i>Sauvegardé
                            </span>
                        </div>
                    </div>

                    <!-- Conteneur principal de l'éditeur -->
                    <div id="editor-container" class="flex-1 relative">

                        <!-- Placeholder initial -->
                        <div id="placeholder" class="flex items-center justify-center h-full bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
                            <div class="text-center">
                                <i class="fas fa-file-word text-8xl text-blue-600 mb-8 opacity-60"></i>
                                <p class="text-xl text-gray-700 font-semibold mb-4">Remplissez les informations à gauche et chargez un modèle</p>
                                <p class="text-base text-gray-600">Votre rapport apparaîtra ici une fois le modèle chargé</p>
                            </div>
                        </div>

                        <!-- Éditeur de texte -->
                        <div id="editor-wrapper" class="hidden w-full h-full flex flex-col">

                            <!-- Barre d'outils -->
                            <div id="editor-toolbar" class="px-6 py-3 bg-primary-second border-b border-gray-200 flex flex-wrap gap-2 items-center">
                                <!-- Sélecteur de police -->
                                <div class="flex items-center space-x-2">
                                    <label for="font-family" class="text-sm font-medium text-gray-700 whitespace-nowrap">
                                        <i class="fas fa-font mr-1"></i>Police:
                                    </label>
                                    <select id="font-family" onchange="changeFontFamily(this.value)" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                        <option value="Arial, sans-serif">Arial</option>
                                        <option value="'Times New Roman', serif">Times New Roman</option>
                                        <option value="'Candara', sans-serif" selected>Candara</option>
                                        <option value="'Calibri', sans-serif">Calibri</option>
                                        <option value="'Georgia', serif">Georgia</option>
                                        <option value="'Verdana', sans-serif">Verdana</option>
                                        <option value="'Tahoma', sans-serif">Tahoma</option>
                                        <option value="'Trebuchet MS', sans-serif">Trebuchet MS</option>
                                        <option value="'Courier New', monospace">Courier New</option>
                                        <option value="'Lucida Console', monospace">Lucida Console</option>
                                        <option value="'Impact', sans-serif">Impact</option>
                                        <option value="'Comic Sans MS', cursive">Comic Sans MS</option>
                                    </select>
                                </div>

                                <!-- Sélecteur de taille -->
                                <div class="flex items-center space-x-2">
                                    <label for="font-size" class="text-sm font-medium text-gray-700 whitespace-nowrap">
                                        <i class="fas fa-text-height mr-1"></i>Taille:
                                    </label>
                                    <select id="font-size" onchange="changeFontSize(this.value)" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                        <option value="8">8</option>
                                        <option value="9">9</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12" selected>12</option>
                                        <option value="14">14</option>
                                        <option value="16">16</option>
                                        <option value="18">18</option>
                                        <option value="20">20</option>
                                        <option value="24">24</option>
                                        <option value="28">28</option>
                                        <option value="32">32</option>
                                        <option value="36">36</option>
                                        <option value="48">48</option>
                                        <option value="72">72</option>
                                    </select>
                                </div>

                                <div class="w-px h-6 bg-gray-300 mx-2"></div>

                                <button type="button" onclick="execCmd('bold')" class="toolbar-btn">
                                    <i class="fas fa-bold mr-1"></i>Gras
                                </button>
                                <button type="button" onclick="execCmd('italic')" class="toolbar-btn">
                                    <i class="fas fa-italic mr-1"></i>Italique
                                </button>
                                <button type="button" onclick="execCmd('underline')" class="toolbar-btn">
                                    <i class="fas fa-underline mr-1"></i>Souligné
                                </button>

                                <div class="w-px h-6 bg-gray-300 mx-2"></div>

                                <button type="button" onclick="execCmd('formatBlock', 'h1')" class="toolbar-btn">
                                    <i class="fas fa-heading mr-1"></i>Titre 1
                                </button>
                                <button type="button" onclick="execCmd('formatBlock', 'h2')" class="toolbar-btn">
                                    <i class="fas fa-heading mr-1"></i>Titre 2
                                </button>
                                <button type="button" onclick="execCmd('formatBlock', 'h3')" class="toolbar-btn">
                                    <i class="fas fa-heading mr-1"></i>Titre 3
                                </button>

                                <div class="w-px h-6 bg-gray-300 mx-2"></div>

                                <button type="button" onclick="execCmd('insertUnorderedList')" class="toolbar-btn">
                                    <i class="fas fa-list-ul mr-1"></i>Liste
                                </button>
                                <button type="button" onclick="execCmd('insertOrderedList')" class="toolbar-btn">
                                    <i class="fas fa-list-ol mr-1"></i>Numérotée
                                </button>

                                <div class="w-px h-6 bg-gray-300 mx-2"></div>

                                <button type="button" onclick="execCmd('justifyLeft')" class="toolbar-btn">
                                    <i class="fas fa-align-left"></i>
                                </button>
                                <button type="button" onclick="execCmd('justifyCenter')" class="toolbar-btn">
                                    <i class="fas fa-align-center"></i>
                                </button>
                                <button type="button" onclick="execCmd('justifyRight')" class="toolbar-btn">
                                    <i class="fas fa-align-right"></i>
                                </button>

                                <div class="w-px h-6 bg-gray-300 mx-2"></div>

                                <button type="button" onclick="addNewPage()" class="toolbar-btn">
                                    <i class="fas fa-plus mr-1"></i>Nouvelle page
                                </button>
                            </div>

                            <!-- Zone d'édition -->
                            <div id="text-editor"
                                contenteditable="true"
                                class="flex-1 p-10 overflow-y-auto focus:outline-none bg-white/80 backdrop-blur-sm"
                                style="min-height: 500px; max-height: calc(100vh - 400px); overflow-y: scroll;">
                                <p class="text-gray-600 italic mb-4 font-medium">
                                    Chargez un modèle de rapport pour commencer à rédiger...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================
         SCRIPTS JAVASCRIPT
         ======================================== -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>

    <style>
        .toolbar-btn {
            @apply px-4 py-3 bg-white/90 backdrop-blur-sm border-2 border-gray-200 text-gray-700 rounded-xl hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700 transition-all duration-300 font-semibold text-base shadow-sm hover:shadow-md;
        }
    </style>

    <script>
        // ========================================
        // VARIABLES GLOBALES
        // ========================================
        let autoSaveInterval;
        let isEditorLoaded = false;
        let lastSaveTime = null;

        // ========================================
        // INITIALISATION AU CHARGEMENT
        // ========================================
        document.addEventListener('DOMContentLoaded', function() {
            initializeComponents();
            setupEventListeners();
            restoreFromStorage();
        });

        // ========================================
        // INITIALISATION DES COMPOSANTS
        // ========================================
        function initializeComponents() {
            // Récupération des éléments DOM
            window.elements = {
                modelSelect: document.getElementById('model-select'),
                loadTemplateBtn: document.getElementById('load-template'),
                downloadPdfBtn: document.getElementById('download-pdf'),
                saveReportBtn: document.getElementById('save-report-btn'),
                textEditor: document.getElementById('text-editor'),
                placeholder: document.getElementById('placeholder'),
                editorWrapper: document.getElementById('editor-wrapper'),
                wordCount: document.getElementById('word-count'),
                autoSaveIndicator: document.getElementById('auto-save-indicator'),
                contentInput: document.getElementById('content-input'),
                reportForm: document.getElementById('report-form')
            };
        }

        // ========================================
        // CONFIGURATION DES ÉVÉNEMENTS
        // ========================================
        function setupEventListeners() {
            const {
                loadTemplateBtn,
                downloadPdfBtn,
                saveReportBtn,
                textEditor
            } = window.elements;

            // Chargement de modèle
            loadTemplateBtn.addEventListener('click', handleLoadTemplate);

            // Export PDF
            downloadPdfBtn.addEventListener('click', handleExportPDF);

            // Sauvegarde du rapport
            saveReportBtn.addEventListener('click', handleSaveReport);

            // Édition en temps réel
            textEditor.addEventListener('input', handleEditorInput);

            // Gestion du logo de l'entreprise
            setupLogoHandling();

            // Mise à jour automatique des champs
            setupFieldUpdates();

            // Sauvegarde avant fermeture
            window.addEventListener('beforeunload', handleBeforeUnload);
        }

        // ========================================
        // GESTION DES MODÈLES
        // ========================================
        function handleLoadTemplate() {
            const selectedModel = window.elements.modelSelect.value;

            if (!selectedModel) {
                showNotification('Veuillez sélectionner un modèle', 'error');
                return;
            }

            showNotification('Chargement du modèle...', 'info');

            setTimeout(() => {
                loadTemplate(selectedModel);
            }, 500);
        }

        async function loadTemplate(templateName) {
            try {
                // Afficher un indicateur de chargement
                showNotification('Chargement du modèle en cours...', 'info');

                // Faire une requête AJAX pour récupérer le modèle
                const response = await fetch(`assets/traitements/get_template.php?template=${encodeURIComponent(templateName)}`);
                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Erreur lors du chargement du modèle');
                }

                let templateContent = '';

                if (data.type === 'docx') {
                    // Pour les fichiers DOCX, utiliser mammoth.js pour la conversion
                    if (typeof mammoth !== 'undefined') {
                        const arrayBuffer = Uint8Array.from(atob(data.content), c => c.charCodeAt(0)).buffer;
                        const result = await mammoth.convertToHtml({
                            arrayBuffer: arrayBuffer
                        });
                        templateContent = result.value;
                    } else {
                        // Fallback si mammoth.js n'est pas disponible
                        templateContent = getStandardTemplate();
                    }
                } else if (data.type === 'html') {
                    // Pour les fichiers HTML, ajouter une classe pour isoler les styles
                    templateContent = '<div class="rapport-template">' + data.content + '</div>';
                } else {
                    throw new Error('Type de fichier non supporté');
                }

                window.elements.textEditor.innerHTML = templateContent;
                window.elements.placeholder.classList.add('hidden');
                window.elements.editorWrapper.classList.remove('hidden');

                isEditorLoaded = true;

                // Mettre à jour les tokens avec les valeurs actuelles
                updateTemplateFields();

                updateWordCount();
                startAutoSave();

                showNotification('Modèle chargé avec succès', 'success');

            } catch (error) {
                console.error('Erreur lors du chargement du modèle:', error);
                showNotification('Erreur lors du chargement du modèle: ' + error.message, 'error');

                // Fallback vers le modèle par défaut
                if (templateName === 'template_rapport.html') {
                    window.elements.textEditor.innerHTML = getAcademicTemplate();
                }

                // Masquer le placeholder et afficher l'éditeur même en cas d'erreur
                window.elements.placeholder.classList.add('hidden');
                window.elements.editorWrapper.classList.remove('hidden');
                isEditorLoaded = true;

                // Mettre à jour les tokens avec les valeurs actuelles
                updateTemplateFields();

                updateWordCount();
                startAutoSave();
            }
        }

        // ========================================
        // MODÈLE DE RAPPORT PAR DÉFAUT
        // ========================================
        function getAcademicTemplate() {
            return `
                <div class="rapport-template">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 60px; width: 100%;">
                        <div style="text-align: center; flex: 1;">
                            <h3 style="font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px; line-height: 1.3; color: #000;">Ministère de l'Enseignement Supérieur<br>et de la Recherche Scientifique</h3>
                            <img src='C:/wamp64/www/GSCV+/public/assets/images/logo_ufhb_blanc.png'>
                              
                            <div style="font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 30px; color: #000;">Université Félix Houphouët Boigny</div>
                            <div style="font-size: 11px; font-weight: bold; text-transform: uppercase; line-height: 1.3; margin-bottom: 20px; color: #000;">
                                UFR Mathématiques et Informatique<br>
                                Filières Professionnalisées MIAGE-CI
                            </div>
                        </div>
                        
                        <div style="text-align: center; flex: 1;">
                            <h3 style="font-size: 12px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px; line-height: 1.3; color: #000;">République de Côte d'Ivoire<br>Union - Discipline - Travail</h3>
                            <img src='../../public/assets/images/logo_mi_sbg.png'>

                            <div style="font-size: 10px; text-transform: uppercase; margin-top: 20px; color: #000;">[Logo de l'entreprise]</div>
                            <div style="border: 2px solid black; padding: 8px 15px; display: inline-block; font-weight: bold; margin-top: 10px; color: #000;">[Nom de l'entreprise]</div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin: 80px 0;">
                        <div style="font-size: 14px; font-weight: bold; margin-bottom: 40px; text-transform: uppercase; color: #000;">Mémoire de fin de cycle pour l'obtention du :</div>
                        <div style="font-size: 14px; font-weight: bold; margin-bottom: 30px; border: 2px solid black; padding: 10px; display: inline-block; color: #000;">DIPLÔME D'INGENIERIE EN INFORMATIQUE</div>
                        
                        <div style="font-size: 12px; margin-bottom: 20px; color: #000;">Option Méthodes Informatiques Appliquées à la Gestion des Entreprises</div>
                        <div style="font-size: 12px; font-weight: bold; margin-bottom: 40px; color: #000;">Thème :</div>
                        
                        <div style="border: 3px solid #4a7c4a; padding: 30px 20px; margin: 60px auto; max-width: 500px;">
                            <div style="font-size: 16px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #000;">[THÈME DU MÉMOIRE]</div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 100px;">
                        <div style="text-align: center; margin-bottom: 60px;">
                            <div style="font-size: 12px; font-weight: bold; text-decoration: underline; margin-bottom: 10px; color: #000;">PRÉSENTÉ PAR :</div>
                            <div style="font-size: 12px; border: 2px solid black; padding: 8px 15px; display: inline-block; color: #000;">[NOM DE L'ÉTUDIANT]</div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-around; max-width: 600px; margin: 0 auto;">
                            <div style="text-align: center; border: 2px solid black; padding: 20px; min-width: 200px;">
                                <div style="font-size: 12px; font-weight: bold; text-decoration: underline; margin-bottom: 15px; text-transform: uppercase; color: #000;">Encadreur</div>
                                <div style="font-size: 12px; border: 1px solid black; padding: 8px 10px; background-color: #f8f8f8; color: #000;">[NOM DE L'ENCADREUR]</div>
                            </div>
                            
                            <div style="text-align: center; border: 2px solid black; padding: 20px; min-width: 200px;">
                                <div style="font-size: 12px; font-weight: bold; text-decoration: underline; margin-bottom: 15px; text-transform: uppercase; color: #000;">Maître de Stage</div>
                                <div style="font-size: 12px; border: 1px solid black; padding: 8px 10px; background-color: #f8f8f8; color: #000;">[NOM DU MAÎTRE DE STAGE]</div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // ========================================
        // UTILITAIRES
        // ========================================
        function getFormData() {
            return {
                theme: document.getElementById('theme_report')?.value || '[Thème du mémoire]',
                nom: document.getElementById('nom_etudiant')?.value || '[Nom de l\'étudiant]',
                encadreur: document.getElementById('encadreur')?.value || '[Nom de l\'encadreur]',
                maitreStage: document.getElementById('maitre_stage')?.value || '[Nom du maître de stage]',
                entreprise: document.getElementById('nom_entreprise')?.value || '[Nom de l\'entreprise]',
            };
        }

        function setupFieldUpdates() {
            const fields = ['theme_report', 'nom_etudiant', 'encadreur', 'maitre_stage', 'nom_entreprise'];

            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', debounce(updateTemplateFields, 500));
                }
            });
        }

        function updateTemplateFields() {
            if (!isEditorLoaded) return;

            const currentContent = window.elements.textEditor.innerHTML;
            const formData = getFormData();

            // Remplacer les tokens dans le contenu actuel
            let updatedContent = currentContent;

            // Remplacer les tokens par les valeurs actuelles
            // Tokens du modèle standard
            updatedContent = updatedContent.replace(/\[Thème du mémoire\]/g, formData.theme);
            updatedContent = updatedContent.replace(/\[Nom de l'étudiant\]/g, formData.nom);
            updatedContent = updatedContent.replace(/\[Nom de l'encadreur\]/g, formData.encadreur);
            updatedContent = updatedContent.replace(/\[Nom du maître de stage\]/g, formData.maitreStage);

            // Tokens du modèle académique
            updatedContent = updatedContent.replace(/\[THÈME DU MÉMOIRE\]/g, formData.theme);
            updatedContent = updatedContent.replace(/\[NOM DE L'ÉTUDIANT\]/g, formData.nom);
            updatedContent = updatedContent.replace(/\[NOM DE L'ENCADREUR\]/g, formData.encadreur);
            updatedContent = updatedContent.replace(/\[NOM DU MAÎTRE DE STAGE\]/g, formData.maitreStage);
            updatedContent = updatedContent.replace(/\[Nom de l'entreprise\]/g, formData.entreprise);

            // Le logo est géré séparément par les fonctions d'insertion/suppression

            // Mettre à jour l'éditeur avec le nouveau contenu
            window.elements.textEditor.innerHTML = updatedContent;

            // Mettre à jour le compteur de mots
            updateWordCount();
        }

        // ========================================
        // GESTION DE L'ÉDITEUR
        // ========================================
        function handleEditorInput() {
            updateWordCount();
            updateAutoSaveIndicator(false);
        }

        function updateWordCount() {
            const text = window.elements.textEditor.innerText || '';
            const words = text.trim().split(/\s+/).filter(word => word.length > 0);
            window.elements.wordCount.textContent = `${words.length} mots`;
        }

        function execCmd(command, value = null) {
            document.execCommand(command, false, value);
            window.elements.textEditor.focus();
        }

        function changeFontFamily(fontFamily) {
            applyStyleToSelection('fontFamily', fontFamily);
            showNotification(`Police changée vers ${fontFamily.split(',')[0].replace(/'/g, '')}`, 'success');
        }

        function changeFontSize(size) {
            applyStyleToSelection('fontSize', size + 'px');
            showNotification(`Taille de police changée à ${size}px`, 'success');
        }

        // Fonction utilitaire pour appliquer les styles de manière plus fiable
        function applyStyleToSelection(styleProperty, value) {
            if (!isEditorLoaded) {
                showNotification('Veuillez d\'abord charger un modèle', 'error');
                return;
            }

            const selection = window.getSelection();
            if (selection.rangeCount === 0) {
                // Aucune sélection, appliquer à tout l'éditeur
                window.elements.textEditor.style[styleProperty] = value;
                return;
            }

            const range = selection.getRangeAt(0);
            
            if (!range.collapsed) {
                // Il y a du texte sélectionné
                const span = document.createElement('span');
                span.style[styleProperty] = value;
                
                // Extraire le contenu sélectionné et l'envelopper dans le span
                const contents = range.extractContents();
                span.appendChild(contents);
                range.insertNode(span);
                
                // Nettoyer les spans vides
                cleanupEmptySpans();
            } else {
                // Aucune sélection, appliquer au paragraphe actuel
                const currentElement = range.startContainer.nodeType === Node.TEXT_NODE 
                    ? range.startContainer.parentElement 
                    : range.startContainer;
                
                if (currentElement && currentElement !== window.elements.textEditor) {
                    currentElement.style[styleProperty] = value;
                } else {
                    window.elements.textEditor.style[styleProperty] = value;
                }
            }
            
            window.elements.textEditor.focus();
        }

        // Fonction pour nettoyer les spans vides
        function cleanupEmptySpans() {
            const spans = window.elements.textEditor.querySelectorAll('span');
            spans.forEach(span => {
                if (span.innerHTML.trim() === '') {
                    span.remove();
                }
            });
        }

        function addNewPage() {
            if (!isEditorLoaded) {
                showNotification('Veuillez d\'abord charger un modèle', 'error');
                return;
            }

            // Demander le titre de la nouvelle section
            const sectionTitle = prompt('Entrez le titre de la nouvelle section :', 'Nouvelle section');
            
            if (sectionTitle === null) {
                return; // L'utilisateur a annulé
            }

            const editor = window.elements.textEditor;
            const pageBreak = document.createElement('div');
            pageBreak.className = 'page-break';
            pageBreak.style.cssText = `
                page-break-before: always;
                break-before: page;
                margin-top: 40px;
                padding-top: 40px;
                border-top: 2px solid #e5e7eb;
                min-height: 100px;
            `;
            
            // Ajouter le titre personnalisé
            pageBreak.innerHTML = `
                <h2 style="color: #1a5276; border-bottom: 1px solid #1a5276; padding-bottom: 5px; margin-bottom: 20px; font-size: 18px; font-weight: bold;">
                    ${sectionTitle}
                </h2>
                <p style="color: #000; margin-bottom: 15px;">
                    Commencez à rédiger votre contenu ici...
                </p>
            `;

            // Insérer la nouvelle page à la fin du contenu
            editor.appendChild(pageBreak);
            
            // Faire défiler vers la nouvelle page
            pageBreak.scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Placer le curseur dans la nouvelle page
            const range = document.createRange();
            const selection = window.getSelection();
            range.selectNodeContents(pageBreak.querySelector('p'));
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
            
            showNotification(`Nouvelle page "${sectionTitle}" ajoutée`, 'success');
            updateWordCount();
        }

        // ========================================
        // SAUVEGARDE AUTOMATIQUE
        // ========================================
        function startAutoSave() {
            if (autoSaveInterval) {
                clearInterval(autoSaveInterval);
            }

            autoSaveInterval = setInterval(() => {
                saveToStorage();
                updateAutoSaveIndicator(true);
            }, 30000); // Sauvegarde toutes les 30 secondes
        }

        function saveToStorage() {
            const content = window.elements.textEditor.innerHTML;
            const data = {
                content: content,
                lastSave: new Date().toISOString(),
                formData: getFormData()
            };

            try {
                sessionStorage.setItem('rapport_draft', JSON.stringify(data));
                lastSaveTime = new Date();
            } catch (error) {
                console.error('Erreur de sauvegarde:', error);
            }
        }

        function restoreFromStorage() {
            try {
                const savedData = sessionStorage.getItem('rapport_draft');
                if (savedData) {
                    const data = JSON.parse(savedData);

                    // Restaurer le contenu de l'éditeur
                    if (data.content && data.content.trim() !== '') {
                        window.elements.textEditor.innerHTML = data.content;
                        window.elements.placeholder.classList.add('hidden');
                        window.elements.editorWrapper.classList.remove('hidden');
                        isEditorLoaded = true;
                        updateWordCount();
                        startAutoSave();

                        // Restaurer les données du formulaire
                        if (data.formData) {
                            Object.keys(data.formData).forEach(key => {
                                const element = document.getElementById(key === 'nom' ? 'nom_etudiant' :
                                    key === 'theme' ? 'theme_report' : key);
                                if (element && data.formData[key] && !data.formData[key].includes('[')) {
                                    element.value = data.formData[key];
                                }
                            });
                        }

                        showNotification('Brouillon restauré', 'info');
                    }
                }
            } catch (error) {
                console.error('Erreur lors de la restauration:', error);
            }
        }

        function updateAutoSaveIndicator(saved) {
            const indicator = window.elements.autoSaveIndicator;

            if (saved) {
                indicator.innerHTML = '<i class="fas fa-check mr-1"></i>Sauvegardé';
                indicator.className = 'text-sm opacity-90';
            } else {
                indicator.innerHTML = '<i class="fas fa-clock mr-1"></i>Non sauvegardé';
                indicator.className = 'text-sm opacity-90 text-yellow-200';
            }
        }

        // ========================================
        // EXPORT PDF
        // ========================================
        function handleExportPDF() {
            if (!isEditorLoaded) {
                showNotification('Veuillez d\'abord charger un modèle', 'error');
                return;
            }

            const content = window.elements.textEditor.innerHTML;
            
            // Vérifier que le contenu n'est pas vide
            if (!content || content.trim() === '' || content.includes('Chargez un modèle')) {
                showNotification('Votre rapport ne peut pas être vide', 'error');
                return;
            }

            showNotification('Génération du PDF en cours...', 'info');

            const studentName = document.getElementById('nom_etudiant').value.replace(/\s+/g, '_') || 'rapport';
            const filename = `${studentName}_rapport_${new Date().toISOString().split('T')[0]}`;

            // Utiliser fetch pour une meilleure gestion des erreurs
            const formData = new FormData();
            formData.append('content', content);
            formData.append('filename', filename);

            fetch('assets/traitements/export_pdf.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                
                // Vérifier si la réponse est un PDF
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/pdf')) {
                    // Créer un blob et télécharger le PDF
                    return response.blob();
                } else {
                    // Si ce n'est pas un PDF, c'est probablement une erreur JSON
                    return response.json().then(data => {
                        throw new Error(data.error || 'Erreur lors de la génération du PDF');
                    });
                }
            })
            .then(blob => {
                // Créer un lien de téléchargement
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename + '.pdf';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                showNotification('PDF généré avec succès', 'success');
            })
            .catch(error => {
                console.error('Erreur lors de la génération du PDF:', error);
                showNotification('Erreur lors de la génération du PDF: ' + error.message, 'error');
            });
        }

        // ========================================
        // SAUVEGARDE FINALE
        // ========================================
        function handleSaveReport() {
            if (!isEditorLoaded) {
                showNotification('Veuillez d\'abord charger un modèle et rédiger votre rapport', 'error');
                return;
            }

            // Validation des champs obligatoires
            const theme = document.getElementById('theme_report').value.trim();
            if (!theme) {
                showNotification('Veuillez renseigner le thème du mémoire', 'error');
                document.getElementById('theme_report').focus();
                return;
            }

            const content = window.elements.textEditor.innerHTML;
            if (!content || content.trim() === '' || content.includes('Chargez un modèle')) {
                showNotification('Votre rapport ne peut pas être vide', 'error');
                return;
            }

            // Vérification de la longueur minimale
            const wordCount = (window.elements.textEditor.innerText || '').trim().split(/\s+/).filter(w => w.length > 0).length;
            if (wordCount < 100) {
                if (!confirm(`Votre rapport ne contient que ${wordCount} mots. Êtes-vous sûr de vouloir le déposer ?`)) {
                    return;
                }
            }

            // Confirmation finale
            if (confirm('Êtes-vous sûr de vouloir déposer ce rapport ? Cette action est définitive.')) {
                // Mise à jour du champ caché
                window.elements.contentInput.value = content;

                showNotification('Dépôt du rapport en cours...', 'info');

                // Supprimer le brouillon
                sessionStorage.removeItem('rapport_draft');

                // Soumettre le formulaire
                window.elements.reportForm.submit();
            }
        }

        // ========================================
        // GESTION DU LOGO DE L'ENTREPRISE
        // ========================================
        function setupLogoHandling() {
            const logoInput = document.getElementById('logo_entreprise');
            const insertLogoBtn = document.getElementById('insert-logo-btn');

            // Écouter les changements sur l'input file
            logoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    insertLogoBtn.disabled = false;
                    insertLogoBtn.textContent = 'Insérer dans l\'éditeur';
                } else {
                    insertLogoBtn.disabled = true;
                    insertLogoBtn.textContent = 'Insérer dans l\'éditeur';
                }
            });

            // Écouter le clic sur le bouton d'insertion
            insertLogoBtn.addEventListener('click', function() {
                const file = logoInput.files[0];
                if (file) {
                    insertLogoIntoEditor(file);
                }
            });

            // Écouter le clic sur le bouton de suppression
            const removeLogoBtn = document.getElementById('remove-logo-btn');
            removeLogoBtn.addEventListener('click', function() {
                removeLogoFromEditor();
            });
        }

        function insertLogoIntoEditor(file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const imageData = e.target.result;
                
                // Créer l'élément image
                const imgElement = document.createElement('img');
                imgElement.src = imageData;
                imgElement.style.cssText = 'width: 100px; height: 100px; border: 2px solid #2d5a2d; border-radius: 50%; margin: 0 auto 20px auto; display: block; object-fit: cover;';
                imgElement.alt = 'Logo de l\'entreprise';
                
                // Remplacer le placeholder dans l'éditeur
                const editor = window.elements.textEditor;
                const content = editor.innerHTML;
                
                // Rechercher et remplacer le placeholder [Logo de l'entreprise]
                const updatedContent = content.replace(
                    /\[Logo de l'entreprise\]/g,
                    `<img src="${imageData}" style="width: 100px; height: 100px; border: 2px solid #2d5a2d; border-radius: 50%; margin: 0 auto 20px auto; display: block; object-fit: cover;" alt="Logo de l'entreprise">`
                );
                
                editor.innerHTML = updatedContent;
                
                showNotification('Logo inséré avec succès', 'success');
                updateWordCount();
            };
            
            reader.readAsDataURL(file);
        }

        function removeLogoFromEditor() {
            const editor = window.elements.textEditor;
            const content = editor.innerHTML;
            
            // Remplacer toutes les images de logo par le placeholder
            const updatedContent = content.replace(
                /<img[^>]*alt="Logo de l'entreprise"[^>]*>/g,
                '[Logo de l\'entreprise]'
            );
            
            editor.innerHTML = updatedContent;
            
            // Réinitialiser l'input file
            const logoInput = document.getElementById('logo_entreprise');
            logoInput.value = '';
            
            // Désactiver le bouton d'insertion
            const insertLogoBtn = document.getElementById('insert-logo-btn');
            insertLogoBtn.disabled = true;
            
            showNotification('Logo supprimé', 'info');
            updateWordCount();
        }

        // ========================================
        // GESTION DES ÉVÉNEMENTS
        // ========================================
        function handleBeforeUnload(e) {
            if (isEditorLoaded) {
                saveToStorage();

                // Avertir si des modifications non sauvegardées
                const indicator = window.elements.autoSaveIndicator;
                if (indicator.innerHTML.includes('Non sauvegardé')) {
                    e.preventDefault();
                    e.returnValue = 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter ?';
                    return e.returnValue;
                }
            }
        }

        // ========================================
        // UTILITAIRES GÉNÉRAUX
        // ========================================
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function showNotification(message, type) {
            // Supprimer les notifications existantes
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notif => notif.remove());

            // Créer la notification
            const notification = document.createElement('div');
            notification.className = 'notification fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full';

            let bgColor, textColor, icon;
            switch (type) {
                case 'success':
                    bgColor = 'bg-green-500';
                    textColor = 'text-white';
                    icon = 'fas fa-check-circle';
                    break;
                case 'error':
                    bgColor = 'bg-red-500';
                    textColor = 'text-white';
                    icon = 'fas fa-exclamation-circle';
                    break;
                case 'info':
                    bgColor = 'bg-blue-500';
                    textColor = 'text-white';
                    icon = 'fas fa-info-circle';
                    break;
                default:
                    bgColor = 'bg-gray-500';
                    textColor = 'text-white';
                    icon = 'fas fa-bell';
            }

            notification.className += ` ${bgColor} ${textColor}`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="${icon} mr-3"></i>
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            // Animation d'entrée
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);

            // Suppression automatique
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // ========================================
        // RACCOURCIS CLAVIER
        // ========================================
        document.addEventListener('keydown', function(e) {
            if (isEditorLoaded && e.ctrlKey) {
                switch (e.key) {
                    case 's':
                        e.preventDefault();
                        saveToStorage();
                        updateAutoSaveIndicator(true);
                        showNotification('Brouillon sauvegardé', 'success');
                        break;
                    case 'b':
                        e.preventDefault();
                        execCmd('bold');
                        break;
                    case 'i':
                        e.preventDefault();
                        execCmd('italic');
                        break;
                    case 'u':
                        e.preventDefault();
                        execCmd('underline');
                        break;
                }
            }
        });
    </script>
</body>

</html>