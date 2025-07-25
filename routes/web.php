<?php


require_once '../app/config/config.php';

// Vérification de connexion (déjà faite dans router.php mais sécurité supplémentaire)
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../public/pageConnexion.php');
    exit();
}

$page = $_GET['page'] ?? 'dashboard';

// Système de routage simple avec if/elseif
if (isset($_GET['page']) && $_GET['page'] === 'analyses') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/AnalysesController.php';
    $controller = new AnalysesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'archivage_documents') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ArchivageDocumentsController.php';
    $controller = new ArchivageDocumentsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'archives') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ArchivesController.php';
    $controller = new ArchivesController($pdo);
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'boites_messages') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/BoitesMessagesController.php';
    $controller = new BoitesMessagesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'comptes_rendus') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ComptesRendusController.php';
    $controller = new ComptesRendusController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'consultations') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ConsultationsController.php';
    $controller = new ConsultationsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'dashboard') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/DashboardController.php';
    $controller = new DashboardController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'dashboard_secretaire') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/DashboardSecretaireController.php';
    $controller = new DashboardSecretaireController($pdo);
    $data = $controller->index();
    
    // Extraire les données pour la vue
    $stats = $data['stats'];
    $activites = $data['activites'];
    $evolutionEffectifs = $data['evolutionEffectifs'];
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'demandes_soutenances') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/DemandesSoutenancesController.php';
    $controller = new DemandesSoutenancesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'etudiants') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/EtudiantsController.php';
    $controller = new EtudiantsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'evaluations_etudiants') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/EvaluationsEtudiantsController.php';
    $controller = new EvaluationsEtudiantsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'index_commission') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/IndexCommissionController.php';
    $controller = new IndexCommissionController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'index_etudiant') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/IndexEtudiantController.php';
    $controller = new IndexEtudiantController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'index_personnel_administratif') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/IndexPersonnelAdministratifController.php';
    $controller = new IndexPersonnelAdministratifController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'index') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/HomeController.php';
    $controller = new HomeController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'inscriptions_etudiants') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/InscriptionsEtudiantsController.php';
    $controller = new InscriptionsEtudiantsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'messages') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/MessagesController.php';
    $controller = new MessagesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'parameters') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ParametersController.php';
    $controller = new ParametersController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'parametres_generaux') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ParametresGenerauxController.php';
    $controller = new ParametresGenerauxController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'piste_audit') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/PisteAuditController.php';
    $controller = new PisteAuditController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'profils') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ProfilsController.php';
    $controller = new ProfilsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'rapports') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/RapportsController.php';
    $controller = new RapportsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'reclamations_etudiants') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ReclamationsEtudiantsController.php';
    $controller = new ReclamationsEtudiantsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'reclamations') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ReclamationsController.php';
    $controller = new ReclamationsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'ressources_humaines') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/RessourcesHumainesController.php';
    $controller = new RessourcesHumainesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'reunions') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ReunionsController.php';
    $controller = new ReunionsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'sauvegardes_et_restaurations') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/SauvegardesEtRestaurationsController.php';
    $controller = new SauvegardesEtRestaurationsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'soutenances') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/SoutenancesController.php';
    $controller = new SoutenancesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'suivis_des_decisions') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/SuivisDesDecisionsController.php';
    $controller = new SuivisDesDecisionsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'validations') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ValidationController.php';
    $controller = new ValidationController();
    $controller->index();
    ob_end_flush();
}

// Routes pour les listes
elseif (isset($_GET['page']) && $_GET['page'] === 'liste_actions') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeActionsController.php';
    $controller = new ListeActionsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_annees_academiques') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeAnneesAcademiquesController.php';
    $controller = new ListeAnneesAcademiquesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_ecue') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeEcueController.php';
    $controller = new ListeEcueController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_enseignants') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeEnseignantsController.php';
    $controller = new ListeEnseignantsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_entreprises') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeEntreprisesController.php';
    $controller = new ListeEntreprisesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_fonctions') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeFonctionsController.php';
    $controller = new ListeFonctionsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_frais_inscriptions') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeFraisInscriptionsController.php';
    $controller = new ListeFraisInscriptionsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_grades') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeGradesController.php';
    $controller = new ListeGradesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_groupes_utilisateurs') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeGroupesUtilisateursController.php';
    $controller = new ListeGroupesUtilisateursController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_niveaux_acces') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeNiveauxAccesController.php';
    $controller = new ListeNiveauxAccesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_niveaux_approbation') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeNiveauxApprobationController.php';
    $controller = new ListeNiveauxApprobationController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_niveaux_etudes') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeNiveauxEtudesController.php';
    $controller = new ListeNiveauxEtudesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_semestres') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeSemestresController.php';
    $controller = new ListeSemestresController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_specialites') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeSpecialitesController.php';
    $controller = new ListeSpecialitesController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_statuts_jury') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeStatutsJuryController.php';
    $controller = new ListeStatutsJuryController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_traitements') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeTraitementsController.php';
    $controller = new ListeTraitementsController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_types_utilisateurs') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeTypesUtilisateursController.php';
    $controller = new ListeTypesUtilisateursController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_ue') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeUeController.php';
    $controller = new ListeUeController();
    $controller->index();
    ob_end_flush();
}

elseif (isset($_GET['page']) && $_GET['page'] === 'liste_utilisateurs') {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/ListeUtilisateursController.php';
    $controller = new ListeUtilisateursController();
    $controller->index();
    ob_end_flush();
}

// Page par défaut
else {
    ob_start();
    require_once __DIR__ . '/../app/Controllers/DashboardController.php';
    $controller = new DashboardController();
    $controller->index();
    ob_end_flush();
} 