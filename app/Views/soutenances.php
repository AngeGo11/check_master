<?php
// !! VÉRIFIER ABSOLUMENT qu'il n'y a aucun espace blanc, ligne vide ou caractère avant cette balise PHP !!
// !! Vérifier également le fichier ../config/db_connect.php pour la même chose !!
// !! Assurez-vous que le fichier est encodé en UTF-8 sans BOM !!

ob_start(); // Démarre la mise en mémoire tampon de sortie

// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../storage/logs/php-error.log');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Controllers/SoutenanceController.php';

// Enregistrer l'accès à la page des soutenances
//enregistrer_acces_module($pdo, $_SESSION['user_id'], 'soutenances');

// Initialiser le contrôleur
$soutenanceController = new SoutenanceController($pdo);

// Récupérer les données via le contrôleur
$data = $soutenanceController->index($_SESSION['user_id']);

// Débogage
if (isset($data['error'])) {
    echo "Erreur: " . $data['error'];
    die();
}

$student_data = $data['studentData'];
$stage_declare = $data['stageDeclare'];
$demande_soutenance = $data['demandeSoutenance'];
$rapport = $data['rapport'];
$compte_rendu = $data['compteRendu'];

$student_id = $student_data['num_etd'];
$eligibility_status = $student_data['statut_eligibilite'];

$_SESSION['eligibility_status'] = $eligibility_status;

// Traitement déclaration de stage
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['action'])) {
    $required_fields = [
        'nom_entreprise',
        'adresse_entreprise',
        'ville_entreprise',
        'pays_entreprise',
        'telephone_entreprise',
        'email_entreprise',
        'intitule_stage',
        'description_stage',
        'type_stage',
        'date_debut_stage',
        'date_fin_stage',
        'nom_tuteur',
        'poste_tuteur',
        'telephone_tuteur',
        'email_tuteur'
    ];

    $missing_fields = array_filter($required_fields, fn($field) => empty($_POST[$field]));

    // Vérifier si c'est une requête AJAX
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($missing_fields) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Veuillez remplir tous les champs obligatoires.'
            ]);
            exit();
        } else {
            $_SESSION['error_message'] = "Veuillez remplir tous les champs obligatoires.";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    // Contrôle côté serveur sur la cohérence des dates
    if (!empty($_POST['date_debut_stage']) && !empty($_POST['date_fin_stage'])) {
        if ($_POST['date_fin_stage'] < $_POST['date_debut_stage']) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'La date de fin ne peut pas être antérieure à la date de début.'
                ]);
                exit();
            } else {
                $_SESSION['error_message'] = "La date de fin ne peut pas être antérieure à la date de début.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    }

    try {
        if ($soutenanceController->declareInternship($student_id, $_POST)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Déclaration de stage enregistrée avec succès.'
                ]);
                exit();
            } else {
                $_SESSION['success_message'] = "Déclaration de stage enregistrée avec succès.";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            throw new Exception('Erreur lors de l\'enregistrement');
        }
    } catch (Exception $e) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erreur déclaration de stage : ' . $e->getMessage()
            ]);
            exit();
        } else {
            $_SESSION['error_message'] = "Erreur déclaration de stage : " . $e->getMessage();
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// Traitement de la demande de soutenance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'demande_soutenance') {
    try {
        if ($soutenanceController->createSoutenanceRequest($student_id)) {
            $_SESSION['success_message'] = "Votre demande de soutenance a été enregistrée avec succès.";
            $_SESSION['show_confirmation'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            throw new Exception('Erreur lors de la création de la demande');
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Classes pour les boutons
$checkEligibilityClass = $demande_soutenance ? 'write-btn' : 'btn-desactive';
$requestValidationClass = $stage_declare ? 'write-btn' : 'btn-desactive';

// Vérifier l'existence et les permissions du fichier
$file_path = $compte_rendu['fichier_cr'] ?? null;
?>

<link rel="stylesheet" href="/GSCV+/public/assets/css/soutenances.css?v=<?php echo time(); ?>">

<style>
    .action-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: repeat(2, auto);
        gap: 2rem;
        margin-top: 2rem;
        justify-items: center;
    }

    @media (max-width: 800px) {
        .action-grid {
            grid-template-columns: 1fr;
            grid-template-rows: auto;
        }
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
        max-height: none;
        min-width: 250px;
        max-width: 350px;
        width: 100%;
        height: 320px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
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
</style>
</style>
<div id="candidature" class="section">
    <h2 style=" color: #154360;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-align: center;">Candidature à la Soutenance</h2>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <?php
            unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <?php
            unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <div class="action-grid">

        <div class="action-card">
            <i class="fas fa-building"></i>
            <h3>Déclaration du Stage</h3>
            <p>Déclarer votre stage et entreprise d'accueil</p>
            <button id="declare-stage" class="write-btn">
                Déclarer
            </button>
        </div>


        <div class="action-card">
            <i class="fas fa-check-circle"></i>
            <h3>Éligibilité</h3>
            <p>Vérifier votre admissibilité</p>
            <button id="check-eligibility" class="<?php echo $checkEligibilityClass; ?>">Vérifier</button>
        </div>

        <div class="action-card">
            <i class="fas fa-file-download"></i>
            <h3>Compte rendu</h3>
            <p>Télécharger le compte rendu</p>
            <?php if (isset($compte_rendu) && isset($compte_rendu['fichier_cr'])): ?>
                <form method="GET" action="assets/traitements/download_cr.php">
                    <button type="submit" class="download-cr">Télécharger le compte rendu</button>
                </form>

            <?php else: ?>
                <button class="download-cr" disabled>
                    Compte rendu non disponible
                </button>
            <?php endif; ?>
        </div>

        <div class="action-card">
            <i class="fas fa-clipboard-check"></i>
            <h3>Validation Soutenance</h3>
            <p>Demander la validation pour soutenir</p>
            <button id="request-validation" class="<?php echo $requestValidationClass; ?>">Demander</button>
        </div>
    </div>
</div>






<!-- Overlay pour les modales -->
<div class="modal-overlay"></div>

<!-- Message de succès (vert) -->
<div class="container success_declaration">
    <i class="fas fa-thumbs-up" style="color:#1a5276;"></i>
    <p>Votre déclaration de stage a été enregistrée avec succès !</p>
    <button class="continue-btn" style="background-color: #1a5276; color: white;">
        OK
    </button>
</div>

<div class="container success">
    <i style="color:green;" class="fa-regular fa-face-smile"></i>
    <h1>Félicitations !</h1>
    <p>Vous êtes admissible !</p>
    <button class="continue-btn">
        CONTINUE
        <span class="arrow">›</span>
    </button>
</div>



<!-- Message de confirmation -->
<div class="container confirm">
    <i style="color:blue;" class="fa-regular fa-circle-question"></i>
    <h1>Confirmation</h1>
    <p>Êtes-vous sûr de vouloir demander la validation pour soutenir ?</p>

    <form method="POST" action="">
        <div class="confirm-buttons">
            <button type="button" class="btn-cancel">Non</button>
            <button type="submit" name="action" value="demande_soutenance" class="btn-confirm">Oui</button>
        </div>
    </form>
</div>



<!-- Message de refus (rouge) -->
<div class="container refuse">
    <i style="color:red;" class="fa-regular fa-face-sad-tear"></i>
    <h1>Désolé !</h1>
    <p>Vous n'êtes pas admissible !</p>

    <button class="continue-btn">
        CONTINUE
        <span class="arrow">›</span>
    </button>
</div>

<!-- Message d'attente (orange) -->
<div class="container wait">
    <i style="color:orange;" class="fa-regular fa-face-meh-blank"></i>
    <h1>Un peu de patience !</h1>
    <p>Votre demande est en cours de traitement!</p>

    <button class="continue-btn">
        CONTINUE
        <span class="arrow">›</span>
    </button>
</div>

<!-- Message de succès pour la demande de soutenance -->
<div class="container success_soutenance">
    <i class="fas fa-check-circle" style="color:#1a5276;"></i>
    <h1>Succès !</h1>
    <p>Votre demande de soutenance a été enregistrée avec succès !</p>
    <button class="continue-btn" style="background-color: #1a5276; color: white;">
        OK
    </button>
</div>

<!-- Message d'erreur pour la vérification d'éligibilité -->
<div class="container error_eligibility">
    <i class="fas fa-exclamation-circle" style="color:red;"></i>
    <h1>Attention !</h1>
    <p>Vous devez d'abord soumettre une demande de soutenance avant de vérifier votre éligibilité.</p>
    <button class="continue-btn" style="background-color: #dc3545; color: white;">
        OK
    </button>
</div>

<!-- Message d'erreur pour la demande de soutenance -->
<div class="container error_stage">
    <i class="fas fa-exclamation-circle" style="color:red;"></i>
    <h1>Attention !</h1>
    <p>Vous devez d'abord déclarer votre stage avant de demander une validation pour soutenir.</p>
    <button class="continue-btn" style="background-color: #dc3545; color: white;">
        OK
    </button>
</div>

<!-- Message pour demande existante -->
<div class="container existing_request">
    <i class="fas fa-info-circle" style="color:#1a5276;"></i>
    <h1>Information</h1>
    <p>Vous avez déjà une demande de soutenance en cours de traitement.</p>
    <button class="continue-btn" style="background-color: #1a5276; color: white;">
        OK
    </button>
</div>

<!-- Message de confirmation après soumission -->
<div class="container confirmation_soumission">
    <i class="fas fa-check-circle" style="color:#1a5276;"></i>
    <h1>Demande Enregistrée !</h1>
    <p>Votre demande de soutenance a été enregistrée avec succès.</p>
    <button class="continue-btn" style="background-color: #1a5276; color: white;">
        OK
    </button>
</div>

<!-- Message pour demande en cours -->
<div class="container demande_en_cours">
    <i class="fas fa-info-circle" style="color:#1a5276;"></i>
    <h1>Demande en cours</h1>
    <p>Vous avez déjà une demande de soutenance en cours de traitement.</p>
    <button class="continue-btn" style="background-color: #1a5276; color: white;">
        OK
    </button>
</div>

<!-- Message pour compte rendu indisponible -->
<div class="container compte_rendu_indisponible">
    <i class="fas fa-exclamation-circle" style="color:#1a5276;"></i>
    <h1>Information</h1>
    <p>Le compte rendu n'est pas encore disponible pour votre rapport.</p>
    <button class="continue-btn" style="background-color: #1a5276; color: white;">
        OK
    </button>
</div>

<!-- Message pour compte rendu disponible -->
<div class="container compte_rendu_disponible">
    <i class="fas fa-check-circle" style="color:#1a5276;"></i>
    <h1>Compte rendu disponible</h1>
    <p>Un compte rendu est disponible pour votre rapport.</p>
    <div class="compte-rendu-info">
        <p><strong>Date de création :</strong> <span id="date-compte-rendu"></span></p>
        <p class="file-size"></p>
    </div>
    <div class="modal-buttons">
        <button class="btn-cancel">Annuler</button>
        <button class="btn-preview">Aperçu</button>
        <button class="btn-confirm download-confirm">Confirmer</button>
    </div>
</div>

<!-- Message de confirmation après téléchargement -->
<div class="container download_success">
    <i class="fas fa-check-circle" style="color:#1a5276;"></i>
    <h1>Téléchargement réussi</h1>
    <p>Votre compte rendu a été téléchargé avec succès.</p>
    <button class="continue-btn" style="background-color: #1a5276; color: white;">
        OK
    </button>
</div>

<!-- Modal de déclaration de stage -->
<div class="modal" id="stage-declaration-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Déclaration de Stage</h2>
            <button class="close-modal" onclick="closeModal('stage-declaration-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" id="stage-declaration-form" class="stage-form">
            <!-- Informations de l'entreprise -->
            <div class="form-section">
                <h3>Informations de l'Entreprise</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom_entreprise">Nom de l'entreprise *</label>
                        <input type="text" id="nom_entreprise" name="nom_entreprise" required>
                    </div>
                    <div class="form-group">
                        <label for="adresse_entreprise">Adresse *</label>
                        <input type="text" id="adresse_entreprise" name="adresse_entreprise" required>
                    </div>
                    <div class="form-group">
                        <label for="ville_entreprise">Ville *</label>
                        <input type="text" id="ville_entreprise" name="ville_entreprise" required>
                    </div>
                    <div class="form-group">
                        <label for="pays_entreprise">Pays *</label>
                        <input type="text" id="pays_entreprise" name="pays_entreprise" required>
                    </div>
                    <div class="form-group">
                        <label for="telephone_entreprise">Téléphone *</label>
                        <input type="tel" id="telephone_entreprise" name="telephone_entreprise" required>
                    </div>
                    <div class="form-group">
                        <label for="email_entreprise">Email *</label>
                        <input type="email" id="email_entreprise" name="email_entreprise" required>
                    </div>
                </div>
            </div>

            <!-- Informations du stage -->
            <div class="form-section">
                <h3>Informations du Stage</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="intitule_stage">Intitulé du stage *</label>
                        <input type="text" id="intitule_stage" name="intitule_stage" required>
                    </div>
                    <div class="form-group">
                        <label for="type_stage">Type de stage *</label>
                        <select id="type_stage" name="type_stage" required>
                            <option value="">Sélectionnez le type</option>
                            <option value="stage_fin_etude">Stage de fin d'études</option>
                            <option value="stage_immersion">Stage d'immersion</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date_debut_stage">Date de début *</label>
                        <input type="date" id="date_debut_stage" name="date_debut_stage" required>
                    </div>
                    <div class="form-group">
                        <label for="date_fin_stage">Date de fin *</label>
                        <input type="date" id="date_fin_stage" name="date_fin_stage" required>
                    </div>
                    <div class="form-group full-width">
                        <div id="date-error" style="color: red; display: none; text-align: center; margin-bottom: 10px;"></div>
                    </div>
                    <div class="form-group full-width">
                        <label for="description_stage">Description du stage *</label>
                        <textarea id="description_stage" name="description_stage" rows="4" placeholder="Donnez une brève description du stage" required></textarea>
                    </div>
                </div>
            </div>

            <!-- Informations du tuteur -->
            <div class="form-section">
                <h3>Informations du Tuteur</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="nom_tuteur">Nom complet *</label>
                        <input type="text" id="nom_tuteur" name="nom_tuteur" required>
                    </div>
                    <div class="form-group">
                        <label for="poste_tuteur">Poste *</label>
                        <input type="text" id="poste_tuteur" name="poste_tuteur" required>
                    </div>
                    <div class="form-group">
                        <label for="telephone_tuteur">Téléphone *</label>
                        <input type="tel" id="telephone_tuteur" name="telephone_tuteur" required>
                    </div>
                    <div class="form-group">
                        <label for="email_tuteur">Email *</label>
                        <input type="email" id="email_tuteur" name="email_tuteur" required>
                    </div>
                </div>
            </div>


            <div class="form-actions">
                <button type="button" class="btn-cancel" onclick="closeModal('stage-declaration-modal')">Annuler</button>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Soumettre la déclaration
                </button>
            </div>
        </form>
    </div>
</div>

<div class="container errorDeclarationStage" style="display:none;">
    <i class="fas fa-exclamation-circle" style="color:red;"></i>
    <h1>Erreur</h1>
    <p class="errorMessageStage">Une erreur est survenue.</p>
    <button class="continue-btn" style="background-color: #dc3545; color: white;">OK</button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const downloadReportBtn = document.getElementById('download-report');
        const requestValidationBtn = document.getElementById('request-validation');
        const success_declaration = document.querySelector('.container.success_declaration');
        const declareStageBtn = document.getElementById('declare-stage');
        const checkEligibilityBtn = document.getElementById('check-eligibility');
        const successModal = document.querySelector('.container.success');
        const refuseModal = document.querySelector('.container.refuse');
        const waitModal = document.querySelector('.container.wait');
        const confirmModal = document.querySelector('.container.confirm');
        const existingRequestModal = document.querySelector('.container.existing_request');
        const continueBtns = document.querySelectorAll('.continue-btn');
        const modalOverlay = document.querySelector('.modal-overlay');
        const stageDeclarationForm = document.getElementById('stage-declaration-form');
        const successSoutenance = document.querySelector('.container.success_soutenance');
        const errorEligibility = document.querySelector('.container.error_eligibility');
        const errorStage = document.querySelector('.container.error_stage');
        const modalCompteRenduIndisponible = document.querySelector('.container.compte_rendu_indisponible');
        const modalCompteRenduDisponible = document.querySelector('.container.compte_rendu_disponible');
        const modalDownloadSuccess = document.querySelector('.container.download_success');
        const btnCancelCompteRendu = modalCompteRenduDisponible?.querySelector('.btn-cancel');
        const btnDownloadConfirm = modalCompteRenduDisponible?.querySelector('.download-confirm');
        const btnPreviewCompteRendu = modalCompteRenduDisponible?.querySelector('.btn-preview');

        // Variable pour stocker l'état de la demande
        const hasExistingRequest = <?php echo isset($demande_soutenance) && $demande_soutenance ? 'true' : 'false'; ?>;

        // Ajout de la déclaration des variables pour la gestion d'erreur
        const errorDeclarationStage = document.querySelector('.container.errorDeclarationStage');
        const errorMessageStage = errorDeclarationStage ? errorDeclarationStage.querySelector('.errorMessageStage') : null;

        // Gestion de la déclaration du stage
        if (declareStageBtn) {
            declareStageBtn.addEventListener('click', function() {
                const modal = document.getElementById('stage-declaration-modal');
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
        }

        // Gestion du formulaire de déclaration
        if (stageDeclarationForm) {
            stageDeclarationForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Vérification côté client
                const dateDebut = document.getElementById('date_debut_stage');
                const dateFin = document.getElementById('date_fin_stage');
                const dateError = document.getElementById('date-error');
                if (dateDebut.value && dateFin.value && dateFin.value < dateDebut.value) {
                    dateError.textContent = "La date de fin ne peut pas être antérieure à la date de début.";
                    dateError.style.display = "block";
                    dateError.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    return false;
                } else {
                    dateError.textContent = "";
                    dateError.style.display = "none";
                }

                // Animation de chargement
                const submitBtn = this.querySelector('.btn-submit');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
                submitBtn.disabled = true;

                fetch('../public/assets/traitements/declare_stage.php', {
                        method: 'POST',
                        body: new FormData(this),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;

                        if (data.success) {
                            requestValidationBtn.classList.remove('btn-desactive');
                            // Fermer la modale de déclaration de stage correctement
                            const declarationModal = document.getElementById('stage-declaration-modal');
                            if (declarationModal) {
                                hideModal(declarationModal);
                                document.body.style.overflow = 'auto';
                            }
                            // Afficher la modale de succès de déclaration de stage
                            showModal(success_declaration);
                        } else {
                            errorMessageStage.textContent = data.message || "Une erreur est survenue lors de l'enregistrement de votre déclaration de stage.";
                            showModal(errorDeclarationStage);
                        }
                    })
                    .catch(error => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        errorMessageStage.textContent = "Une erreur est survenue lors de l'enregistrement de votre déclaration de stage.";
                        showModal(errorDeclarationStage);
                    });
            });
        }

        // Gestion des boutons "OK" et "CONTINUE"
        continueBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = this.closest('.container');
                hideModal(modal);
            });
        });

        // Gestion demande validation
        if (requestValidationBtn) {
            requestValidationBtn.addEventListener('click', function() {
                if (this.classList.contains('btn-desactive')) {
                    showModal(errorStage);
                } else if (<?php echo isset($demande_soutenance) && $demande_soutenance['statut_demande'] === 'En attente' ? 'true' : 'false'; ?>) {
                    showModal(document.querySelector('.container.demande_en_cours'));
                } else {
                    showModal(confirmModal);
                }
            });
        }

        // Gestion des boutons de confirmation
        const btnConfirm = confirmModal.querySelector('.btn-confirm');
        const btnCancel = confirmModal.querySelector('.btn-cancel');
        const confirmForm = confirmModal.querySelector('form');

        if (btnCancel) {
            btnCancel.addEventListener('click', function() {
                hideModal(confirmModal);
            });
        }

        // Afficher la modale de succès si un message de succès existe
        <?php if (isset($_SESSION['success_message'])): ?>
            showModal(successSoutenance);
        <?php endif; ?>

        // Gestion du bouton OK de la modale de succès
        const successBtn = successSoutenance.querySelector('.continue-btn');
        if (successBtn) {
            successBtn.addEventListener('click', function() {
                hideModal(successSoutenance);
                checkEligibilityBtn.classList.remove('btn-desactive');
            });
        }

        // Fonction pour afficher une modale
        function showModal(modal) {
            hideAllModals();
            modalOverlay.style.display = 'block';
            modal.style.display = 'block';
            modal.classList.add('fade-in');
        }

        // Fonction pour cacher une modale spécifique
        function hideModal(modal) {
            modal.classList.add('fade-out');
            modal.classList.remove('fade-in');

            setTimeout(() => {
                modal.style.display = 'none';
                modal.classList.remove('fade-out');
                modalOverlay.style.display = 'none';
            }, 300);
        }

        // Fonction pour cacher toutes les modales
        function hideAllModals() {
            [
                successModal, refuseModal, waitModal, confirmModal, success_declaration,
                successSoutenance, errorEligibility, errorStage, existingRequestModal,
                modalCompteRenduIndisponible, modalCompteRenduDisponible, modalDownloadSuccess,
                errorDeclarationStage,
                document.getElementById('stage-declaration-modal')
            ].forEach(modal => {
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('fade-in');
                    modal.classList.remove('active');
                }
            });
            modalOverlay.style.display = 'none';
        }

        // Fermeture quand on clique en dehors (overlay)
        modalOverlay.addEventListener('click', function() {
            hideAllModals();
        });

        // Gestion du bouton de vérification d'éligibilité
        if (checkEligibilityBtn) {
            checkEligibilityBtn.addEventListener('click', function() {
                if (this.classList.contains('btn-desactive')) {
                    showModal(errorEligibility);
                } else {
                    // Afficher la modale appropriée selon le statut d'éligibilité
                    <?php if ($eligibility_status === 'Éligible'): ?>
                        showModal(successModal);
                    <?php elseif ($eligibility_status === 'Non éligible'): ?>
                        showModal(refuseModal);
                    <?php else: ?>
                        showModal(waitModal);
                    <?php endif; ?>
                }
            });
        }

        // Afficher la modale de confirmation après soumission
        <?php if (isset($_SESSION['show_confirmation'])): ?>
            showModal(document.querySelector('.container.confirmation_soumission'));
            <?php unset($_SESSION['show_confirmation']); ?>
        <?php endif; ?>

        // Gestion du bouton OK de la modale de confirmation après soumission
        const confirmationBtn = document.querySelector('.container.confirmation_soumission .continue-btn');
        if (confirmationBtn) {
            confirmationBtn.addEventListener('click', function() {
                hideModal(document.querySelector('.container.confirmation_soumission'));
            });
        }

        // Gestion du bouton OK de la modale demande en cours
        const demandeEnCoursBtn = document.querySelector('.container.demande_en_cours .continue-btn');
        if (demandeEnCoursBtn) {
            demandeEnCoursBtn.addEventListener('click', function() {
                hideModal(document.querySelector('.container.demande_en_cours'));
            });
        }

        // Contrôle des dates de début et de fin de stage
        const dateDebut = document.getElementById('date_debut_stage');
        const dateFin = document.getElementById('date_fin_stage');
        const dateError = document.getElementById('date-error');
        if (stageDeclarationForm) {
            function checkDates(e) {
                if (dateDebut.value && dateFin.value) {
                    if (dateFin.value < dateDebut.value) {
                        dateError.textContent = "Attention ! La date de fin ne peut pas être antérieure à la date de début.";
                        dateError.style.display = "block";
                        if (e) e.preventDefault();
                        return false;
                    } else {
                        dateError.textContent = "";
                        dateError.style.display = "none";
                    }
                } else {
                    dateError.textContent = "";
                    dateError.style.display = "none";
                }
            }
            dateDebut.addEventListener('change', checkDates);
            dateFin.addEventListener('change', checkDates);
            stageDeclarationForm.addEventListener('submit', function(e) {
                if (dateFin.value < dateDebut.value) {
                    dateError.textContent = "La date de fin ne peut pas être antérieure à la date de début.";
                    dateError.style.display = "block";
                    e.preventDefault();
                    return false;
                }
            });
        }

    });

    // Fonction globale pour fermer les modales
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }
</script>