<?php

/**
 * Module de gestion de rapports/mémoires pour étudiants
 * 
 * Ce fichier gère l'interface et les fonctionnalités liées aux rapports de stage
 * Il permet aux étudiants de créer, éditer et soumettre leurs rapports
 */

// Connexion à la base de données et récupération des données de l'étudiant
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Controllers/RapportController.php';

// Initialiser le contrôleur
$rapportController = new RapportController($pdo);

// Récupérer les données via le contrôleur
$data = $rapportController->index($_SESSION['user_id']);

if (isset($data['error'])) {
    die($data['error']);
}

$student_data = $data['studentData'];
$hasExistingReport = $data['hasExistingReport'];
$rapport_status = $data['reportStatus'];
$eligibility_status = $data['eligibilityStatus'];
$comments = $data['comments'];

$student_id = $student_data['num_etd'];
$name_report = $student_data['nom_etd'] . '_' . $student_data['prenom_etd'] . '_' . date('Y-m-d');
$_SESSION['name_report'] = $name_report;

// ====== TRAITEMENT DU FORMULAIRE ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_report') {
    $themeMemoire = $_POST['theme_memoire'] ?? '';
    $content = $_POST['content'] ?? '';
    $studentId = $_POST['student_id'] ?? '';

    // Si on a du contenu HTML, le sauvegarder
    if (!empty($content)) {
        // Créer un nom de fichier unique
        $htmlFileName = $name_report . '_' . date('Y-m-d_H-i-s') . '.html';
        $filePath = 'storage/uploads/rapports/' . $htmlFileName;

        // Créer le dossier s'il n'existe pas
        $uploadDir = __DIR__ . '/../../storage/uploads/rapports/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Sauvegarder le contenu HTML
        file_put_contents($uploadDir . $htmlFileName, $content);

        // Créer le rapport dans la base de données
        if ($rapportController->createReport($studentId, $themeMemoire, $filePath)) {
            $_SESSION['success_message'] = "Rapport créé avec succès !";
        } else {
            $_SESSION['error_message'] = "Erreur lors de la création du rapport.";
        }
    } else {
        $_SESSION['error_message'] = "Aucun contenu fourni pour le rapport.";
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!-- Styles CSS -->
<link rel="stylesheet" href="/GSCV+/public/assets/css/rapports.css?v=<?php echo time(); ?>">
<style>
    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .action-card {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(26, 82, 118, 0.15);
        border: 2px solid #1a5276;
        position: relative;
        overflow: hidden;
        max-height: 375px;
    }

    .action-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, rgba(26, 82, 118, 0.05) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }

    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(26, 82, 118, 0.25);
        border-color: #154360;
    }

    .action-card i {
        font-size: 3rem;
        color: #1a5276;
        margin-bottom: 1rem;
    }

    .action-card h3 {
        color: #1a5276;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .action-card p {
        color: #1a5276;
        font-size: 1rem;
        margin-bottom: 1.5rem;
        opacity: 0.8;
    }

    .action-card button {
        background: #1a5276;
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 0.95rem;
    }

    .action-card button:hover {
        background: #154360;
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(26, 82, 118, 0.3);
    }

    #left-col i {
        color: #1a5276;
        font-size: 1rem;
        margin-bottom: 1rem;
        text-align: center;
        margin-left: auto;
        margin-right: auto;

    }
</style>

<!-- Section principale de gestion des rapports -->
<div id="rapport" class="section">
    <h2 style=" color: #154360;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-align: center;">Gestion du Rapport/Mémoire</h2>
    <div class="action-grid">

        <div class="action-card">
            <i class="fas fa-file-pen"></i>
            <h3>Création du rapport</h3>
            <p>Saisissez votre rapport</p>
            <button onclick="window.open('?page=redaction_rapport')" class="write-btn<?php echo ($hasExistingReport || $eligibility_status !== 'Éligible') ? ' btn-desactive' : ''; ?>">
                Nouveau rapport
            </button>
        </div>

        <div class="action-card">
            <i class="fas fa-clipboard-list"></i>
            <h3>Statut du Dépôt</h3>
            <p>Consultez l'état de votre dernier dépôt</p>
            <button id="check-status-report" class="write-btn<?php echo !$hasExistingReport ? ' btn-desactive' : ''; ?>">
                Vérifier
            </button>
        </div>

        <div class="action-card">
            <i class="fas fa-comments"></i>
            <h3>Commentaires</h3>
            <p>Suivre les retours de vos encadrants</p>
            <button id="view-comments" class="write-btn">
                Consulter
            </button>
        </div>
    </div>
</div>



<style>
    /* Styles pour les boutons au survol */
    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2) !important;
    }

    #editor-toolbar button:hover {
        background: white !important;
        transform: translateY(-1px);
    }

    /* Styles pour les champs de saisie au focus */
    input:focus,
    select:focus {
        border-color: #1a5276 !important;
        outline: none;
        box-shadow: 0 0 0 3px rgba(26, 82, 118, 0.1) !important;
    }

    /* Animation pour l'affichage de l'éditeur */
    #local-editor-wrapper.show {
        display: flex !important;
        animation: fadeInEditor 0.5s ease-in-out;
    }

    @keyframes fadeInEditor {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive design pour la modal */
    @media (max-width: 1200px) {
        .modal-content {
            width: 98% !important;
        }

        #left-col {
            flex: 0 0 300px !important;
        }
    }

    @media (max-width: 768px) {
      
        #left-col {
            flex: none !important;
            max-height: none !important;
            margin-bottom: 20px;
        }

        #editor-toolbar {
            padding: 12px !important;
        }

        #editor-toolbar button {
            font-size: 11px !important;
            padding: 6px 8px !important;
        }
    }

    /* Style pour le statut */
    #status.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    #status.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    #status.info {
        background: #cce7ff;
        color: #004085;
        border: 1px solid #b3d7ff;
    }
</style>

<!-- Modal pour les commentaires du rapport -->
<div class="modal" id="comments-modal">
    <div class="modal-content">
        <div class="top-text">
            <h2 class="modal-title">Commentaires</h2>
            <a href="#" class="close" id="close-modal-comments-btn">
                <i class="fa fa-xmark fa-2x"></i>
            </a>
        </div>
        <div class="comments-container">
            <?php
            // Récupérer les commentaires du rapport
            // La variable $comments est déjà transmise par le contrôleur

            if (count($comments) > 0) {
                foreach ($comments as $comment) {
                    if (!empty($comment['texte_commentaire'])) {
                        $badge_class = '';
                        $badge_text = '';

                        // Définir la classe et le texte du badge selon le type de commentaire
                        if ($comment['type'] == 'approbation') {
                            $badge_class = 'badge-approval';
                            $badge_text = 'Approbation';
                        } else {
                            $badge_class = 'badge-validation';
                            $badge_text = 'Validation';
                        }

                        // Formater la date
                        $date_formatted = 'Date non spécifiée';
                        if (!empty($comment['date_commentaire'])) {
                            $date_obj = new DateTime($comment['date_commentaire']);
                            $date_formatted = $date_obj->format('d/m/Y');
                        }

                        echo '<div class="comment-card ' . $comment['type'] . '-card">';
                        echo '<div class="comment-header">';
                        echo '<div class="comment-author-container">';
                        echo '<span class="comment-author">' . htmlspecialchars($comment['nom_encadrant']) . '</span>';
                        echo '<span class="comment-badge ' . $badge_class . '">' . $badge_text . '</span>';
                        echo '</div>';
                        echo '<span class="comment-date">' . $date_formatted . '</span>';
                        echo '</div>';
                        echo '<div class="comment-body">';
                        echo '<p>' . nl2br(htmlspecialchars($comment['texte_commentaire'])) . '</p>';
                        echo '</div>';
                        echo '</div>';
                    }
                }
            } else {
                echo '<div class="no-comments">';
                echo '<i class="fa-solid fa-comment-slash"></i>';
                echo '<p>Aucun commentaire n\'a été laissé sur votre rapport pour le moment.</p>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</div>

<!-- Modal d'interdiction de dépôt multiple -->
<div class="modal" id="forbidden-modal">
    <div class="modal-content">
        <div class="top-text">
            <h2 class="modal-title">Dépôt non autorisé</h2>
            <a href="#" class="close" id="close-modal-forbidden-btn">
                <i class="fa fa-xmark fa-2x"></i>
            </a>
        </div>
        <div class="forbidden-container">
            <div class="forbidden-icon">
                <i class="fa-solid fa-ban"></i>
            </div>
            <div class="forbidden-message">
                <h3>Vous avez déjà un rapport en cours d'évaluation</h3>
                <p>Il n'est pas possible de déposer plusieurs rapports simultanément. Veuillez attendre la finalisation de l'évaluation de votre rapport actuel avant de soumettre un nouveau document.</p>
                <div class="forbidden-info">
                    <p><strong>Que faire ?</strong></p>
                    <ul>
                        <li>Consultez le statut de votre rapport actuel</li>
                        <li>Attendez la validation ou le rejet de votre rapport</li>
                        <li>En cas de rejet, vous pourrez soumettre une nouvelle version</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="button secondary" id="close-forbidden-btn">
                <i class="fa-solid fa-times"></i> Fermer
            </button>
            <button type="button" class="button primary" id="check-current-status-btn">
                <i class="fa-solid fa-eye"></i> Vérifier le statut
            </button>
        </div>
    </div>
</div>

<!-- Overlay pour les modales -->
<div class="modal-overlay"></div>

<!-- Messages de statut -->
<?php if (!$hasExistingReport): ?>
    <div class="alert not_submitted">
        <i style="color:gray;" class="fa-solid fa-face-meh-blank"></i>
        <h1>Aucun rapport soumis !</h1>
        <p>Soumettez votre rapport et suivez son état de validation !</p>
        <button class="continue-btn">
            CONTINUE
            <span class="arrow">›</span>
        </button>
    </div>
<?php endif; ?>

<div class="alert wait_approbation">
    <i style="color:orange;" class="fa-solid fa-face-grin-beam-sweat"></i>
    <h1>Un peu de patience !</h1>
    <p>Votre demande est en cours de traitement!</p>
    <button class="continue-btn">
        CONTINUE
        <span class="arrow">›</span>
    </button>
</div>

<div class="alert refuse_approbation">
    <i style="color:red;" class="fa-solid fa-face-sad-tear"></i>
    <h1>Désolé !</h1>
    <p>Votre rapport n'a pas été approuvé ! <br> Consultez votre boite mail ou votre messagerie pour plus de details.</p>
    <button class="continue-btn">
        CONTINUE
        <span class="arrow">›</span>
    </button>
</div>

<div class="alert success_approbation">
    <i style="color:green;" class="fa-solid fa-face-grin-beam"></i>
    <h1>Félicitations !</h1>
    <p>Votre rapport a été approuvé !</p>
    <button class="continue-btn">
        CONTINUE
        <span class="arrow">›</span>
    </button>
</div>

<div class="alert wait_validation">
    <i style="color:orange;" class="fa-solid fa-hourglass-half"></i>
    <h1>Un peu de patience !</h1>
    <p>Votre rapport est à présent en attente de validation par le jury !</p>
    <button class="continue-btn">
        CONTINUE
        <span class="arrow">›</span>
    </button>
</div>

<div class="alert refuse_validation">
    <i style="color:red;" class="fa-solid fa-circle-xmark"></i>
    <h1>Désolé !</h1>
    <p>Votre rapport a été refusé par les membres du jury !</p>
    <button class="continue-btn">
        CONTINUE
        <span class="arrow">›</span>
    </button>
</div>

<div class="alert success_validation">
    <i style="color:green;" class="fa-solid fa-check-to-slot"></i>
    <h1>Félicitations !</h1>
    <p>Votre rapport a été validé par les membres du jury !</p>
    <button class="continue-btn">
        CONTINUE
        <span class="arrow">›</span>
    </button>
</div>

<!-- Scripts JavaScript -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://unpkg.com/mammoth@1.4.21/mammoth.browser.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    window.hasExistingReport = <?php echo $hasExistingReport ? 'true' : 'false'; ?>;
    window.reportStatus = <?php echo json_encode($rapport_status); ?>;
    window.eligibilityStatus = <?php echo json_encode($eligibility_status); ?>;
    
    // Debug
    console.log('Debug - Variables de rapport:', {
        hasExistingReport: window.hasExistingReport,
        reportStatus: window.reportStatus,
        eligibilityStatus: window.eligibilityStatus
    });
</script>
<?php include 'C:/wamp64/www/GSCV+/app/Views/assets/js/jsRapports.php'; ?>