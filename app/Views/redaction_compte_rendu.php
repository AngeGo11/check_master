<?php

/**
 * Page de rédaction de compte rendu
 * 
 * Cette page permet aux responsables de créer et rédiger des comptes rendus
 * pour les rapports validés ou rejetés
 * Fonctionnalités :
 * - Sélection des rapports validés/rejetés
 * - Éditeur en temps réel avec barre d'outils
 * - Sauvegarde automatique
 * - Export PDF
 * - Soumission du compte rendu final
 */

// Configuration et dépendances
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Controllers/CompteRenduController.php';

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de sécurité
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_groups'])) {
    header('Location: ../pageConnection.php');
    exit;
}

// Initialisation du contrôleur
$compteRenduController = new CompteRenduController($pdo);

// Récupération des rapports validés ou rejetés via le contrôleur
$rapports_valides = $compteRenduController->getRapportsValidesOuRejetes();

// Récupération des comptes rendus existants avec les informations de l'auteur
$comptesRendus = $compteRenduController->indexWithAuthor();

$user_name = $_SESSION['user_fullname'] ?? 'Utilisateur';


// Grouper les comptes rendus par titre
$comptesRendusGroupes = [];
foreach ($comptesRendus as $compteRendu) {
    $titre = $compteRendu['nom_cr'] ?? 'Sans titre';
    if (!isset($comptesRendusGroupes[$titre])) {
        $comptesRendusGroupes[$titre] = [
            'titre' => $titre,
            'nombre_total' => 0,
            'date_creation' => $compteRendu['date_cr'] ?? 'now',
            'auteur' => $user_name, // Utilisateur actuel
            'rapports' => []
        ];
    }
    $comptesRendusGroupes[$titre]['nombre_total']++;
    $comptesRendusGroupes[$titre]['rapports'][] = $compteRendu;
}

// Variables de session
$user_id = $_SESSION['user_id'] ?? null;

// ========================================
// TRAITEMENT DU FORMULAIRE DE CRÉATION
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_compte_rendu') {

    // Récupération et validation des données
    $nomCr = trim($_POST['titre'] ?? '');
    $contenu = $_POST['contenu'] ?? '';
    $rapport_ids = $_POST['rapport_ids'] ?? [];
    $date_creation = date('Y-m-d H:i:s');
    $auteur_id = $_SESSION['user_id'];

    // Validation des champs obligatoires
    if (empty($nomCr)) {
        $_SESSION['error_message'] = "Le titre du compte rendu est obligatoire.";
        $redirectToError = true;
    } elseif (empty($contenu)) {
        $_SESSION['error_message'] = "Aucun contenu fourni pour le compte rendu.";
        $redirectToError = true;
    } elseif (empty($rapport_ids)) {
        $_SESSION['error_message'] = "Veuillez sélectionner au moins un rapport.";
        $redirectToError = true;
    } else {

        // Traitement de sauvegarde
        try {
            // Génération du nom de fichier unique
            $htmlFileName = 'compte_rendu_' . date('Y-m-d_H-i-s') . '.pdf';
            $filePath = 'storage/uploads/compte_rendu/' . $htmlFileName;

            // Création du dossier de destination
            $uploadDir = __DIR__ . '/../../storage/uploads/compte_rendu/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Sauvegarde du fichier HTML
            if (file_put_contents($uploadDir . $htmlFileName, $contenu)) {

                // Création en base de données via le contrôleur
                $rapport_ids_array = is_array($rapport_ids) ? $rapport_ids : explode(',', $rapport_ids);
                $compte_rendu_id = $compteRenduController->createCompteRendu(
                    $nomCr,
                    
                    $date_creation,
                    $auteur_id,
                    $filePath,
                    $rapport_ids_array
                );

                if ($compte_rendu_id) {
                    $_SESSION['success_message'] = "Compte rendu créé avec succès !";
                    // Utiliser JavaScript pour la redirection au lieu de header()
                    echo "<script>
                        // Attendre un peu pour que le message soit enregistré
                        setTimeout(function() {
                            window.location.href = '?page=consultations';
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
                window.location.href = '?page=redaction_compte_rendu';
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
    <title>Rédaction du Compte Rendu - GSCV+</title>

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

        /* Styles spécifiques pour les modèles de compte rendu */
        #text-editor .compte-rendu-template {
            background-color: white !important;
            color: #1f2937 !important;
            font-family: 'Candara', 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
            line-height: 1.6 !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 40px !important;
        }

        #text-editor .compte-rendu-template * {
            color: #1f2937 !important;
            font-family: inherit !important;
        }

        /* Assurer que les images s'affichent correctement */
        #text-editor .compte-rendu-template img {
            display: block !important;
            max-width: 100% !important;
            height: auto !important;
        }

        /* Assurer que les tokens restent visibles */
        #text-editor [style*="color"] {
            color: #000 !important;
        }

        /* Corriger les problèmes de flexbox dans l'éditeur */
        #text-editor .compte-rendu-template div[style*="display: flex"] {
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
                    <a href="?page=consultations" class="text-primary hover:text-primary-dark transition-colors">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </a>
                    <div class="w-12 h-12 bg-primary rounded-lg flex items-center justify-center">
                        <i class="fas fa-file-signature text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-primary">Rédaction du Compte Rendu</h1>
                        <p class="text-gray-600 text-sm">Créez et rédigez des comptes rendus pour les rapports</p>
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
                <h2 class="text-3xl font-bold bg-gradient-to-r from-gray-800 to-gray-600 bg-clip-text text-transparent">Sélectionner les rapports et rédiger le compte rendu</h2>
            </div>
            <p class="text-gray-600 text-lg font-medium">Sélectionnez les rapports validés ou rejetés à gauche et rédigez votre compte rendu à droite.</p>
        </div>

        <!-- ========================================
             FORMULAIRE PRINCIPAL
             ======================================== -->
        <form id="compte-rendu-form" method="POST" action="">
            <input type="hidden" name="action" value="create_compte_rendu">
            <input type="hidden" name="contenu" id="contenu-input">
            <input type="hidden" name="rapport_ids" id="rapport-ids-input">

            <!-- Layout en deux colonnes -->
            <div class="flex gap-6 h-[calc(100vh-300px)] min-h-[800px]">

                <!-- ========================================
                     COLONNE GAUCHE - SÉLECTION DES RAPPORTS
                     ======================================== -->
                <div class="flex-shrink-0 w-96 bg-white/90 backdrop-blur-sm rounded-3xl shadow-2xl border border-white/20 p-8 overflow-y-auto">
                    <h3 class="text-xl font-bold text-gray-800 mb-8 flex items-center">
                        <i class="fas fa-list mr-4 text-blue-600"></i>
                        Rapports disponibles
                    </h3>

                    <!-- Statistiques -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-semibold text-gray-700">Total des rapports</span>
                            <span class="text-lg font-bold text-primary"><?php echo count($rapports_valides); ?></span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-green-100 rounded-lg p-2 text-center">
                                <span class="font-semibold text-green-800">
                                    <?php echo count(array_filter($rapports_valides, function ($r) {
                                        return $r['statut_rapport'] === 'Validé';
                                    })); ?>
                                </span>
                                <div class="text-green-600">Validés</div>
                            </div>
                            <div class="bg-red-100 rounded-lg p-2 text-center">
                                <span class="font-semibold text-red-800">
                                    <?php echo count(array_filter($rapports_valides, function ($r) {
                                        return $r['statut_rapport'] === 'Rejeté';
                                    })); ?>
                                </span>
                                <div class="text-red-600">Rejetés</div>
                            </div>
                        </div>
                    </div>

                    <!-- Liste des rapports -->
                    <div class="space-y-3 max-h-[500px] overflow-y-auto">
                        <?php if (empty($rapports_valides)) : ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4 opacity-50"></i>
                                <p class="text-sm">Aucun rapport validé ou rejeté disponible</p>
                            </div>
                        <?php else : ?>
                            <?php foreach ($rapports_valides as $rapport) : ?>
                                <div class="rapport-item bg-white rounded-xl border-2 border-gray-200 p-4 cursor-pointer hover:border-blue-300 transition-all duration-300"
                                    data-rapport-id="<?php echo htmlspecialchars($rapport['id_rapport_etd']); ?>"
                                    data-nom-etd="<?php echo htmlspecialchars($rapport['nom_etd']); ?>"
                                    data-prenom-etd="<?php echo htmlspecialchars($rapport['prenom_etd']); ?>"
                                    data-theme="<?php echo htmlspecialchars($rapport['theme_memoire']); ?>"
                                    data-statut="<?php echo htmlspecialchars($rapport['statut_rapport']); ?>">

                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900 text-sm">
                                                <?php echo htmlspecialchars($rapport['nom_etd'] . ' ' . $rapport['prenom_etd']); ?>
                                            </h4>
                                            <p class="text-xs text-gray-600 mt-1">
                                                <?php echo htmlspecialchars($rapport['theme_memoire']); ?>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="statut-badge <?php echo $rapport['statut_rapport'] === 'Validé' ? 'statut-valide' : 'statut-rejete'; ?>">
                                                <?php echo $rapport['statut_rapport']; ?>
                                            </span>
                                            <input type="checkbox" class="rapport-checkbox hidden"
                                                value="<?php echo $rapport['id_rapport_etd']; ?>">
                                        </div>
                                    </div>

                                    <div class="text-xs text-gray-500">
                                        <span>Déposé le <?php echo $rapport['date_depot'] ? date('d/m/Y', strtotime($rapport['date_depot'])) : 'N/A'; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h4 class="text-sm font-semibold text-primary mb-4">Actions disponibles</h4>
                        <div class="space-y-3">
                            <button type="button"
                                id="select-all-btn"
                                class="w-full px-4 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium flex items-center justify-center">
                                <i class="fas fa-check-double mr-2"></i>Sélectionner tout
                            </button>
                            <button type="button"
                                id="clear-selection-btn"
                                class="w-full px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium flex items-center justify-center">
                                <i class="fas fa-times mr-2"></i>Effacer la sélection
                            </button>
                        </div>

                        <!-- Compteur de sélection -->
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                            <div class="text-center">
                                <span class="text-sm font-medium text-blue-800">
                                    <span id="selected-count">0</span> rapport(s) sélectionné(s)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================
                     COLONNE DROITE - ÉDITEUR DE COMPTE RENDU
                     ======================================== -->
                <div class="flex-1 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">

                    <!-- En-tête de l'éditeur -->
                    <div class="px-6 py-4 bg-primary text-white flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold flex items-center">
                                <i class="fas fa-file-signature mr-4"></i>
                                Éditeur de compte rendu
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
                                <i class="fas fa-file-signature text-8xl text-blue-600 mb-8 opacity-60"></i>
                                <p class="text-xl text-gray-700 font-semibold mb-4">Sélectionnez des rapports à gauche pour commencer</p>
                                <p class="text-base text-gray-600">Votre compte rendu apparaîtra ici une fois les rapports sélectionnés</p>
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
                                    </select>
                                </div>

                                <!-- Sélecteur de taille -->
                                <div class="flex items-center space-x-2">
                                    <label for="font-size" class="text-sm font-medium text-gray-700 whitespace-nowrap">
                                        <i class="fas fa-text-height mr-1"></i>Taille:
                                    </label>
                                    <select id="font-size" onchange="changeFontSize(this.value)" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors">
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12" selected>12</option>
                                        <option value="14">14</option>
                                        <option value="16">16</option>
                                        <option value="18">18</option>
                                        <option value="20">20</option>
                                        <option value="24">24</option>
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
                                    Sélectionnez des rapports pour commencer à rédiger votre compte rendu...
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section des informations du compte rendu -->
            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center">
                    <i class="fas fa-info-circle mr-4 text-blue-600"></i>
                    Informations du compte rendu
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Titre du compte rendu -->
                    <div>
                        <label for="titre" class="block text-base font-semibold text-gray-700 mb-3">
                            <i class="fas fa-heading mr-3 text-blue-600"></i>Titre du compte rendu *
                        </label>
                        <input type="text"
                            id="titre"
                            name="titre"
                            placeholder="Ex: Procès verbal de séance de validation de thèmes - Date de la séance"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 bg-white/80 backdrop-blur-sm"
                            required>
                    </div>

                    <!-- Date de création -->
                    <div>
                        <label for="date_creation" class="block text-base font-semibold text-gray-700 mb-3">
                            <i class="fas fa-calendar mr-3 text-blue-600"></i>Date de création
                        </label>
                        <input type="date"
                            id="date_creation"
                            name="date_creation"
                            value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 transition-all duration-300 bg-white/80 backdrop-blur-sm">
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="mt-8 flex justify-end space-x-4">
                    <button type="button"
                        id="download-pdf"
                        class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium flex items-center">
                        <i class="fas fa-file-pdf mr-2"></i>Exporter PDF
                    </button>
                    <button type="button"
                        id="save-compte-rendu-btn"
                        class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium flex items-center">
                        <i class="fas fa-save mr-2"></i>Créer le compte rendu
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================
         SECTION LISTE DES COMPTES RENDUS
         ======================================== -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white/90 backdrop-blur-sm rounded-3xl shadow-2xl border border-white/20 p-8">

            <!-- En-tête de section -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                        <i class="fas fa-list text-white text-lg"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-800">Liste des comptes rendus</h2>
                </div>
                <a href="comptes_rendus.php" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium flex items-center">
                    <i class="fas fa-external-link-alt mr-2"></i>Voir tous
                </a>
            </div>

            <!-- Statistiques des comptes rendus -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Total des titres</p>
                            <p class="text-2xl font-bold"><?php echo count($comptesRendusGroupes); ?></p>
                        </div>
                        <i class="fas fa-file-signature text-3xl opacity-80"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Total des comptes rendus</p>
                            <p class="text-2xl font-bold"><?php echo count($comptesRendus); ?></p>
                        </div>
                        <i class="fas fa-file-alt text-3xl opacity-80"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Auteur actuel</p>
                            <p class="text-lg font-bold"><?php echo htmlspecialchars($user_name); ?></p>
                        </div>
                        <i class="fas fa-user-edit text-3xl opacity-80"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm opacity-90">Rapports traités</p>
                            <p class="text-2xl font-bold">
                                <?php 
                                $totalRapportsTraites = 0;
                                foreach ($comptesRendusGroupes as $groupe) {
                                    foreach ($groupe['rapports'] as $rapport) {
                                        $totalRapportsTraites += $rapport['nombre_rapports'] ?? 1;
                                    }
                                }
                                echo $totalRapportsTraites;
                                ?>
                            </p>
                        </div>
                        <i class="fas fa-file-alt text-3xl opacity-80"></i>
                    </div>
                </div>
            </div>

            <!-- Tableau des comptes rendus -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3">Titre du compte rendu</th>
                            <th scope="col" class="px-6 py-3">Auteur</th>
                            <th scope="col" class="px-6 py-3">Date de création</th>
                            <th scope="col" class="px-6 py-3">Rapports associés</th>
                            <th scope="col" class="px-6 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comptesRendusGroupes)) : ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    <i class="fas fa-inbox text-2xl mb-2 opacity-50"></i>
                                    <p>Aucun compte rendu créé pour le moment</p>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach (array_slice($comptesRendusGroupes, 0, 5) as $groupe) : ?>
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-file-signature text-blue-600"></i>
                                            <span><?php echo htmlspecialchars($groupe['titre'] ?? ''); ?></span>
                                            <?php if ($groupe['nombre_total'] > 1) : ?>
                                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">
                                                    <?php echo $groupe['nombre_total']; ?> versions
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-user-edit text-green-600"></i>
                                            <span class="font-medium text-gray-700"><?php echo htmlspecialchars($groupe['auteur']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-calendar-alt text-purple-600"></i>
                                            <span><?php echo date('d/m/Y H:i', strtotime($groupe['date_creation'])); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-file-alt text-orange-600"></i>
                                            <span class="text-sm font-medium text-gray-700">
                                                <?php 
                                                // Compter le nombre total de rapports associés à ce titre
                                                $totalRapports = 0;
                                                foreach ($groupe['rapports'] as $rapport) {
                                                    $totalRapports += $rapport['nombre_rapports'] ?? 1;
                                                }
                                                echo $totalRapports . ' rapport(s)';
                                                ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <!-- Bouton pour voir tous les comptes rendus de ce titre -->
                                            <button type="button" 
                                                onclick="showCompteRenduDetails('<?php echo htmlspecialchars($groupe['titre']); ?>')"
                                                class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors" 
                                                title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <!-- Bouton pour télécharger le dernier compte rendu -->
                                            <?php 
                                            $dernierCompteRendu = end($groupe['rapports']);
                                            if ($dernierCompteRendu) : 
                                            ?>
                                            <a href="?page=consultations&action=download_cr&id=<?php echo $dernierCompteRendu['id_cr']; ?>"
                                                class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition-colors" 
                                                title="Télécharger le dernier">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php endif; ?>
                                            
                                            <!-- Bouton pour supprimer le groupe -->
                                            <button type="button" 
                                                onclick="deleteCompteRenduGroup('<?php echo htmlspecialchars($groupe['titre']); ?>')"
                                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors" 
                                                title="Supprimer le groupe">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
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
        let isEditorLoaded = false;
        let lastSaveTime = null;
        let selectedRapports = []; // Array to store selected rapport IDs

        // ========================================
        // ÉLÉMENTS DOM
        // ========================================
        window.elements = {
            textEditor: document.getElementById('text-editor'),
            placeholder: document.getElementById('placeholder'),
            editorWrapper: document.getElementById('editor-wrapper'),
            contenuInput: document.getElementById('contenu-input'),
            compteRenduForm: document.getElementById('compte-rendu-form'),
            rapportIdInput: document.getElementById('rapport-ids-input'), // Changed to handle multiple IDs
            selectedCount: document.getElementById('selected-count'),
            selectAllBtn: document.getElementById('select-all-btn'),
            clearSelectionBtn: document.getElementById('clear-selection-btn'),
            downloadPdfBtn: document.getElementById('download-pdf'),
            saveCompteRenduBtn: document.getElementById('save-compte-rendu-btn')
        };

        // ========================================
        // INITIALISATION
        // ========================================
        document.addEventListener('DOMContentLoaded', function() {
            setupEventListeners();
            setupRapportSelection();
            updateSelectedCount();
        });

        // ========================================
        // CONFIGURATION DES ÉVÉNEMENTS
        // ========================================
        function setupEventListeners() {
            const {
                textEditor,
                compteRenduForm,
                rapportIdInput,
                selectedCount,
                selectAllBtn,
                clearSelectionBtn,
                downloadPdfBtn,
                saveCompteRenduBtn
            } = window.elements;

            // Sauvegarde brouillon
            compteRenduForm.addEventListener('submit', handleSaveCompteRendu);

            // Édition en temps réel
            textEditor.addEventListener('input', handleEditorInput);
            textEditor.addEventListener('keydown', handleEditorKeydown);

            // Sauvegarde automatique
            setInterval(handleAutoSave, 30000); // Toutes les 30 secondes

            // Sauvegarde avant fermeture
            window.addEventListener('beforeunload', handleBeforeUnload);

            // Gestion de la sélection multiple
            selectAllBtn.addEventListener('click', selectAllRapports);
            clearSelectionBtn.addEventListener('click', clearAllSelections);

            // Export PDF
            downloadPdfBtn.addEventListener('click', handleExportPDF);

            // Sauvegarde du compte rendu
            saveCompteRenduBtn.addEventListener('click', handleSaveCompteRendu);
        }

        // ========================================
        // MISE À JOUR DYNAMIQUE DU TEMPLATE
        // ========================================
        function updateTemplateWithSelectedReports() {
            if (window.elements.textEditor && selectedRapports.length > 0) {
                // Récupérer les rapports sélectionnés (éléments avec checkbox cochée)
                const selectedReports = Array.from(document.querySelectorAll('.rapport-item')).filter(item => {
                    return item.querySelector('.rapport-checkbox').checked;
                });

                const template = getCompteRenduTemplate(selectedReports);
                window.elements.textEditor.innerHTML = template;
                updateWordCount();
            }
        }

        // ========================================
        // GESTION DE LA SÉLECTION DES RAPPORTS
        // ========================================
        function setupRapportSelection() {
            const rapportItems = document.querySelectorAll('.rapport-item');

            rapportItems.forEach(item => {
                item.addEventListener('click', function() {
                    const rapportId = this.dataset.rapportId;
                    const isChecked = this.querySelector('.rapport-checkbox').checked;

                    if (isChecked) {
                        // If already checked, uncheck it
                        this.querySelector('.rapport-checkbox').checked = false;
                        selectedRapports = selectedRapports.filter(id => id !== rapportId);
                    } else {
                        // If not checked, check it
                        this.querySelector('.rapport-checkbox').checked = true;
                        selectedRapports.push(rapportId);
                    }
                    updateSelectedCount();
                });
            });
        }

        // ========================================
        // METTRE À JOUR LE COMPTEUR DE SÉLECTION
        // ========================================
        function updateSelectedCount() {
            window.elements.selectedCount.textContent = selectedRapports.length;
            window.elements.rapportIdInput.value = selectedRapports.join(',');

            // Activer/désactiver le bouton de sauvegarde
            window.elements.saveCompteRenduBtn.disabled = selectedRapports.length === 0;

            // Afficher/masquer l'éditeur
            if (selectedRapports.length > 0 && !isEditorLoaded) {
                showEditor();
                loadCompteRenduTemplate();
            } else if (selectedRapports.length === 0) {
                hideEditor();
            } else if (selectedRapports.length > 0 && isEditorLoaded) {
                // Mettre à jour le template avec les nouveaux rapports sélectionnés
                updateTemplateWithSelectedReports();
            }
        }

        // ========================================
        // SÉLECTIONNER TOUS LES RAPPORTS
        // ========================================
        function selectAllRapports() {
            const rapportItems = document.querySelectorAll('.rapport-item');
            rapportItems.forEach(item => {
                item.querySelector('.rapport-checkbox').checked = true;
            });
            selectedRapports = Array.from(document.querySelectorAll('.rapport-item')).map(item => item.dataset.rapportId);
            updateSelectedCount();
        }

        // ========================================
        // EFFACER TOUTE LA SÉLECTION
        // ========================================
        function clearAllSelections() {
            const rapportItems = document.querySelectorAll('.rapport-item');
            rapportItems.forEach(item => {
                item.querySelector('.rapport-checkbox').checked = false;
            });
            selectedRapports = [];
            updateSelectedCount();
        }

        // ========================================
        // AFFICHER/MASQUER L'ÉDITEUR
        // ========================================
        function showEditor() {
            window.elements.placeholder.classList.add('hidden');
            window.elements.editorWrapper.classList.remove('hidden');
            isEditorLoaded = true;
        }

        function hideEditor() {
            window.elements.placeholder.classList.remove('hidden');
            window.elements.editorWrapper.classList.add('hidden');
            isEditorLoaded = false;
        }

        // ========================================
        // CHARGER LE MODÈLE DE COMPTE RENDU
        // ========================================
        function loadCompteRenduTemplate() {
            if (selectedRapports.length === 0) {
                showNotification('Veuillez sélectionner au moins un rapport', 'error');
                return;
            }

            // Récupérer les rapports sélectionnés (éléments avec checkbox cochée)
            const selectedReports = Array.from(document.querySelectorAll('.rapport-item')).filter(item => {
                return item.querySelector('.rapport-checkbox').checked;
            });

            const template = getCompteRenduTemplate(selectedReports);
            window.elements.textEditor.innerHTML = template;

            // Focus sur l'éditeur
            window.elements.textEditor.focus();

            // Mettre à jour le compteur de mots
            updateWordCount();
        }

        // ========================================
        // GÉNÉRER LE MODÈLE DE COMPTE RENDU
        // ========================================
        function getCompteRenduTemplate(selectedReports = []) {
            const currentDate = new Date().toLocaleDateString('fr-FR');
            const currentTime = new Date().toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });

            // Générer la liste des étudiants et thèmes
            let studentsList = '';
            let themesList = '';

            if (selectedReports.length > 0) {
                selectedReports.forEach((report, index) => {
                    // Récupérer les données depuis les éléments HTML
                    const studentNameElement = report.querySelector('h4');
                    const themeElement = report.querySelector('p.text-xs.text-gray-600');

                    const studentName = studentNameElement ? studentNameElement.textContent.trim() : 'Nom étudiant';
                    const theme = themeElement ? themeElement.textContent.trim() : 'Thème du mémoire';

                    studentsList += `Étudiant : ${studentName}\n`;
                    themesList += `Thème : ${theme}\n`;

                    if (index < selectedReports.length - 1) {
                        studentsList += '\n';
                        themesList += '\n';
                    }
                });
            }

            return `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Rendu de Validation</title>
    <style>
        body {
            font-family: 'Times New Roman', serif;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        .header img {
            height: 80px;
            position: absolute;
            top: 0;
        }
        .header img.left {
            left: 0;
        }
        .header img.right {
            right: 0;
        }
        .header-text {
            margin: 0 100px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .subtitle {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        .content {
            text-align: justify;
            margin-bottom: 15px;
        }
        .student-block {
            margin-bottom: 20px;
            padding: 15px;
            border-left: 3px solid #007bff;
            background-color: #f8f9fa;
        }
        .student-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .theme {
            font-style: italic;
            margin-bottom: 10px;
        }
        .recommendations {
            margin-left: 20px;
        }
        .recommendations li {
            margin-bottom: 5px;
        }
        .signature {
            text-align: right;
            margin-top: 30px;
        }
        .page-break {
            page-break-before: always;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="../../public/assets/images/logo_civ.png" alt="Logo République" class="left">
        <div class="header-text">
            <div class="title">RÉPUBLIQUE DE CÔTE D'IVOIRE</div>
            <div class="subtitle">Ministère de l'Enseignement Supérieur et de la Recherche Scientifique</div>
        </div>
        <img src="../../public/assets/images/logo ufhb.png" alt="Logo UFHB" class="right">
    </div>

    <div class="section">
        <div class="section-title">1. Cadre de la séance</div>
        <div class="content">
            La séance de validation de thèmes s'est tenue dans le bureau du Prof. KOUA Brou, Responsable de la filière MIAGE-GI, le ${currentDate}, de ${currentTime} à ${currentTime}.
            <br><br>
            La réunion a été présidée par Prof. KOUA Brou et a réuni les membres de la commission de validation suivants :
            <br>
            [ÉNUMÉRER LES MEMBRES PRÉSENTS]
            <br><br>
            Au cours de cette séance, ${selectedReports.length} dossier(s) d'étudiant(s) en fin de cycle ont été examiné(s).
        </div>
    </div>

    <div class="section">
        <div class="section-title">2. Ordre du jour</div>
        <div class="content">
            1. Informations<br>
            2. Validation de thèmes<br>
            3. Divers
        </div>
    </div>

    <div class="section">
        <div class="section-title">3. Informations</div>
        <div class="content">
            Le responsable de la filière a rappelé l'importance des séances de validation pour :
            <ul>
                <li>Structurer l'encadrement académique des étudiants ;</li>
                <li>S'assurer de la pertinence et de la faisabilité des sujets proposés ;</li>
                <li>Organiser un suivi rigoureux des travaux de mémoire.</li>
            </ul>
            Il a également précisé que ces séances se tiendront de manière mensuelle, et qu'elles permettront de faire le point sur les encadrements, le contenu des thèmes, ainsi que sur la progression des étudiants.
        </div>
    </div>

    <div class="section">
        <div class="section-title">4. Validation de thèmes</div>
        ${selectedReports.length > 0 ? selectedReports.map((report, index) => {
            // Récupérer les données depuis les éléments HTML
            const studentNameElement = report.querySelector('h4');
            const themeElement = report.querySelector('p.text-xs.text-gray-600');
            
            const studentName = studentNameElement ? studentNameElement.textContent.trim() : 'Nom étudiant';
            const theme = themeElement ? themeElement.textContent.trim() : 'Thème du mémoire';
            
            return `
            <div class="student-block">
                <div class="student-name">Étudiant : ${studentName}</div>
                <div class="theme">Thème : ${theme}</div>
                <div>Recommandations de la commission :</div>
                <ul class="recommendations">
                    <li>[RECOMMANDATION N°1]</li>
                    <li>[RECOMMANDATION N°2]</li>
                    <li>[RECOMMANDATION N°3]</li>
                </ul>
                <div>Directeur de mémoire : [NOM DU DIRECTEUR DE MÉMOIRE]</div>
                <div>Encadreur pédagogique : [NOM DE L'ENCADREUR PÉDAGOGIQUE]</div>
            </div>
            `;
        }).join('') : ` <
                div class = "student-block" >
                <
                div class = "student-name" > Étudiant: [NOM DE L 'ÉTUDIANT]</div> <
                    div class = "theme" > Thème: [THEME DU MÉMOIRE DE L 'ÉTUDIANT]</div> <
                        div > Recommandations de la commission: < /div> <
                        ul class = "recommendations" >
                        <
                        li > [RECOMMANDATION N° 1] < /li> <
                        li > [RECOMMANDATION N° 2] < /li> <
                        li > [RECOMMANDATION N° 3] < /li> <
                        /ul> <
                        div > Directeur de mémoire: [NOM DU DIRECTEUR DE MÉMOIRE] < /div> <
                        div > Encadreur pédagogique: [NOM DE L 'ENCADREUR PÉDAGOGIQUE]</div> <
                            /div>
                            `}
    </div>

    <div class="section">
        <div class="section-title">5. Divers</div>
        <div class="content">
            La commission a recommandé à la direction de la filière de renforcer le partenariat avec les entreprises, qui apprécient la qualité du travail des stagiaires issus de la formation.
            <br><br>
            Des recommandations générales ont également été formulées à l'endroit des étudiants :
            <ul>
                <li>[RECOMMANDATION N°1]</li>
                <li>[RECOMMANDATION N°2]</li>
                <li>[RECOMMANDATION N°3]</li>
            </ul>
            Les travaux de la commission ont pris fin à ${currentTime}.
        </div>
    </div>

    <div class="signature">
        Fait à Abidjan, le ${currentDate}<br>
        Pour la commission de validation,<br>
        <br>
        <br>
        _____________________________<br>
        Prof. KOUA Brou<br>
        Responsable de la filière MIAGE-GI
    </div>
</body>
</html>`;
                        }

                        // ========================================
                        // GESTION DE L'ÉDITEUR
                        // ========================================
                        function handleEditorInput() {
                            updateWordCount();
                            updateAutoSaveIndicator();
                        }

                        function handleEditorKeydown(event) {
                            // Sauvegarde automatique sur Ctrl+S
                            if (event.ctrlKey && event.key === 's') {
                                event.preventDefault();
                                handleAutoSave();
                            }
                        }

                        // ========================================
                        // COMPTEUR DE MOTS
                        // ========================================
                        function updateWordCount() {
                            const text = window.elements.textEditor.innerText || window.elements.textEditor.textContent;
                            const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
                            document.getElementById('word-count').textContent = `${wordCount} mots`;
                        }

                        // ========================================
                        // INDICATEUR DE SAUVEGARDE AUTOMATIQUE
                        // ========================================
                        function updateAutoSaveIndicator() {
                            const indicator = document.getElementById('auto-save-indicator');
                            indicator.innerHTML = '<i class="fas fa-clock mr-2"></i>Modifications non sauvegardées';
                            indicator.className = 'text-base opacity-90 font-medium text-yellow-600';
                        }

                        // ========================================
                        // SAUVEGARDE AUTOMATIQUE
                        // ========================================
                        function handleAutoSave() {
                            const content = window.elements.textEditor.innerHTML;
                            if (content && content.trim()) {
                                // Sauvegarder dans le localStorage
                                localStorage.setItem('compte_rendu_draft', content);
                                localStorage.setItem('compte_rendu_timestamp', Date.now());

                                // Mettre à jour l'indicateur
                                const indicator = document.getElementById('auto-save-indicator');
                                indicator.innerHTML = '<i class="fas fa-check mr-2"></i>Sauvegardé';
                                indicator.className = 'text-base opacity-90 font-medium text-green-600';

                                lastSaveTime = Date.now();
                            }
                        }

                        // ========================================
                        // SAUVEGARDE AVANT FERMETURE
                        // ========================================
                        function handleBeforeUnload(event) {
                            const content = window.elements.textEditor.innerHTML;
                            if (content && content.trim()) {
                                handleAutoSave();
                            }
                        }

                        // ========================================
                        // SAUVEGARDE DU COMPTE RENDU
                        // ========================================
                        function handleSaveCompteRendu(event) {
                            event.preventDefault();

                            if (selectedRapports.length === 0) {
                                showNotification('Veuillez sélectionner au moins un rapport', 'error');
                                return;
                            }

                            const titre = document.getElementById('titre').value.trim();
                            if (!titre) {
                                showNotification('Veuillez saisir un titre pour le compte rendu', 'error');
                                return;
                            }

                            const contenu = window.elements.textEditor.innerHTML;
                            if (!contenu || contenu.trim() === '') {
                                showNotification('Veuillez saisir le contenu du compte rendu', 'error');
                                return;
                            }

                            // Mettre à jour les champs cachés
                            window.elements.contenuInput.value = contenu;
                            window.elements.rapportIdInput.value = selectedRapports.join(',');

                            // Soumettre le formulaire
                            window.elements.compteRenduForm.submit();
                        }

                        // ========================================
                        // EXPORT PDF
                        // ========================================
                        function handleExportPDF() {
                            const content = window.elements.textEditor.innerHTML;
                            if (!content || content.trim() === '') {
                                showNotification('Aucun contenu à exporter', 'error');
                                return;
                            }

                            // Créer une nouvelle fenêtre pour l'impression
                            const printWindow = window.open('', '_blank');
                            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Compte Rendu - Export PDF</title>
                    <style>
                        body { font-family: 'Candara', sans-serif; line-height: 1.6; color: #1f2937; }
                        @media print {
                            body { margin: 0; padding: 20px; }
                            .page-break { page-break-before: always; }
                        }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `);
                            printWindow.document.close();
                            printWindow.print();
                        }

                        // ========================================
                        // FONCTIONS UTILITAIRES
                        // ========================================
                        function showNotification(message, type = 'info') {
                            // Créer une notification temporaire
                            const notification = document.createElement('div');
                            notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
                type === 'error' ? 'bg-red-500 text-white' : 
                type === 'success' ? 'bg-green-500 text-white' : 
                'bg-blue-500 text-white'
            }`;
                            notification.textContent = message;

                            document.body.appendChild(notification);

                            setTimeout(() => {
                                notification.remove();
                            }, 3000);
                        }

                        // ========================================
                        // FONCTIONS DE LA BARRE D'OUTILS
                        // ========================================
                        function execCmd(command, value = null) {
                            document.execCommand(command, false, value);
                            window.elements.textEditor.focus();
                        }

                        function changeFontFamily(fontFamily) {
                            execCmd('fontName', fontFamily);
                        }

                        function changeFontSize(size) {
                            execCmd('fontSize', size);
                        }

                        function addNewPage() {
                            const pageBreak = document.createElement('div');
                            pageBreak.className = 'page-break';
                            pageBreak.innerHTML = '<br><br><br><br><br><br><br><br><br><br>';
                            window.elements.textEditor.appendChild(pageBreak);
                            window.elements.textEditor.focus();
                        }

                        // ========================================
                        // RÉCUPÉRATION DU BROUILLON
                        // ========================================
                        function loadDraft() {
                            const draft = localStorage.getItem('compte_rendu_draft');
                            const timestamp = localStorage.getItem('compte_rendu_timestamp');

                            if (draft && timestamp) {
                                const draftAge = Date.now() - parseInt(timestamp);
                                const oneDay = 24 * 60 * 60 * 1000; // 24 heures

                                if (draftAge < oneDay) {
                                    window.elements.textEditor.innerHTML = draft;
                                    updateWordCount();
                                    showNotification('Brouillon récupéré', 'success');
                                } else {
                                    // Supprimer le brouillon trop ancien
                                    localStorage.removeItem('compte_rendu_draft');
                                    localStorage.removeItem('compte_rendu_timestamp');
                                }
                            }
                        }

                        // Charger le brouillon au démarrage
                        window.addEventListener('load', loadDraft);

                        // ========================================
                        // FONCTIONS POUR LA GESTION DES GROUPES DE COMPTES RENDUS
                        // ========================================
                        
                        // Afficher les détails d'un groupe de comptes rendus
                        function showCompteRenduDetails(titre) {
                            // Créer une modal pour afficher les détails
                            const modal = document.createElement('div');
                            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                            modal.innerHTML = `
                                <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-xl font-bold text-gray-800">Détails du compte rendu : ${titre}</h3>
                                        <button onclick="this.closest('.fixed').remove()" class="text-gray-500 hover:text-gray-700">
                                            <i class="fas fa-times text-xl"></i>
                                        </button>
                                    </div>
                                    <div class="space-y-4">
                                        <p class="text-gray-600">Fonctionnalité en cours de développement...</p>
                                        <p class="text-sm text-gray-500">Cette modal affichera bientôt la liste détaillée de tous les comptes rendus avec ce titre.</p>
                                    </div>
                                </div>
                            `;
                            document.body.appendChild(modal);
                            
                            // Fermer la modal en cliquant à l'extérieur
                            modal.addEventListener('click', function(e) {
                                if (e.target === modal) {
                                    modal.remove();
                                }
                            });
                        }

                        // Supprimer un groupe de comptes rendus
                        function deleteCompteRenduGroup(titre) {
                            if (confirm(`Êtes-vous sûr de vouloir supprimer tous les comptes rendus avec le titre "${titre}" ?`)) {
                                // Ici, vous pouvez ajouter une requête AJAX pour supprimer le groupe
                                showNotification('Fonctionnalité de suppression en cours de développement', 'info');
                                
                                // Pour l'instant, on simule la suppression
                                setTimeout(() => {
                                    showNotification('Groupe de comptes rendus supprimé avec succès', 'success');
                                    // Recharger la page pour mettre à jour la liste
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 1000);
                                }, 1000);
                            }
                        }
    </script>

    <!-- Styles pour les boutons de la barre d'outils -->
    <style>
        .toolbar-btn {
            @apply px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors;
        }

        .statut-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .statut-valide {
            background-color: #dcfce7;
            color: #166534;
        }

        .statut-rejete {
            background-color: #fef2f2;
            color: #dc2626;
        }
    </style>
</body>

</html>