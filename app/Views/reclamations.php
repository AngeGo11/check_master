<?php

require_once '../app/config/config.php';
require_once __DIR__ . '/../Controllers/ReclamationController.php';
require_once __DIR__ . '/../Controllers/AnneeAcademiqueController.php';

// Enregistrer l'accès à la page des réclamations
//enregistrer_acces_module($pdo, $_SESSION['user_id'], 'reclamations');

// Initialiser le contrôleur
$reclamationController = new ReclamationController($pdo);
$anneeAcademiqueController = new AnneeAcademiqueController($pdo);

// Récupérer les données via le contrôleur
$data = $reclamationController->index($_SESSION['user_id']);
$annee_academique = $anneeAcademiqueController->getCurrentYear();

if (isset($data['error'])) {
    die($data['error']);
}

$recupStudentData = $data['studentData'];
$niveau_etudiant = $data['niveauEtudiant'];
$reclamations = $data['reclamations'];
$reclamationsEnCours = $data['reclamationsEnCours'];

$student_id = $recupStudentData['num_etd'];

// ====== TRAITEMENT DU FORMULAIRE ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reclamation'])) {
    $reclamationData = [
        'motif_reclamation' => $_POST['motif_reclamation'] ?? '',
        'noms_matieres' => $_POST['noms_matieres'] ?? [],
        'piece_jointe' => $_POST['pieceJointe'] ?? null
    ];

    if ($reclamationController->createReclamation($student_id, $reclamationData)) {
        $_SESSION['show_confirmation'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['error_message'] = "Erreur lors de la création de la réclamation.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réclamations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/GSCV+/public/assets/css/reclamations.css?v=<?php echo time(); ?>">
</head>

<body>
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

        .form-group input, textarea, select {
            color: #000;
        }

        /* Styles spécifiques pour la timeline */
        .timeline-container {
            position: relative;
            padding-left: 60px;
            max-height: 500px;
            overflow-y: auto;
        }

        .timeline-line {
            position: absolute;
            left: 30px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #1a5276, #2196f3, #4caf50);
            border-radius: 2px;
            z-index: 1;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 40px;
            opacity: 0;
            transform: translateX(-20px);
            animation: slideIn 0.6s ease forwards;
            transition: all 0.3s ease;
        }

        .timeline-item:nth-child(even) {
            animation-delay: 0.2s;
        }

        .timeline-item:nth-child(odd) {
            animation-delay: 0.4s;
        }

        .timeline-item:hover {
            transform: translateX(5px);
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .timeline-dot {
            position: absolute;
            left: -45px;
            top: 25px;
            width: 30px;
            height: 30px;
            background: white;
            border: 3px solid #1a5276;
            border-radius: 50%;
            z-index: 3;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
        }

        .timeline-dot:hover {
            transform: scale(1.2);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .timeline-dot i {
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .timeline-content {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
            transition: all 0.3s ease;
            margin-left: 20px;
        }

        .timeline-content:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .timeline-content::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 25px;
            width: 0;
            height: 0;
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            border-right: 8px solid white;
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .timeline-id {
            background: linear-gradient(135deg, #1a5276, #154360);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(26, 82, 118, 0.3);
        }

        .timeline-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .timeline-date {
            color: #666;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .timeline-section {
            margin-bottom: 15px;
        }

        .timeline-section-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeline-section-content {
            padding: 15px;
            border-radius: 8px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-left: 4px solid #1a5276;
            color: black;
        }

        .timeline-matiere-tag {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1976d2;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 500;
            box-shadow: 0 1px 3px rgba(25, 118, 210, 0.2);
            display: inline-block;
            margin: 2px;
            transition: all 0.3s ease;
        }

        .timeline-matiere-tag:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 6px rgba(25, 118, 210, 0.3);
        }

        .timeline-progress {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .timeline-progress-bar {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .timeline-progress-track {
            flex: 1;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
        }

        .timeline-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #1a5276, #2196f3, #4caf50);
            border-radius: 3px;
            transition: width 0.8s ease;
        }

        .timeline-progress-text {
            font-size: 12px;
            color: #666;
            font-weight: 600;
            min-width: 40px;
        }

        /* Animations pour les statuts */
        .status-en-attente .timeline-dot {
            border-color: #ff9800;
            animation: pulse 2s infinite;
        }

        .status-en-attente .timeline-status {
            background: #ff9800;
            color: white;
        }

        .status-scolarite .timeline-dot {
            border-color: #2196f3;
        }

        .status-scolarite .timeline-status {
            background: #2196f3;
            color: white;
        }

        .status-filiere .timeline-dot {
            border-color: #4caf50;
        }

        .status-filiere .timeline-status {
            background: #4caf50;
            color: white;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(255, 152, 0, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 152, 0, 0); }
        }

        /* Responsive design pour la timeline */
        @media (max-width: 768px) {
            .timeline-container {
                padding-left: 40px;
            }
            
            .timeline-line {
                left: 20px;
            }
            
            .timeline-dot {
                left: -25px;
                width: 25px;
                height: 25px;
            }
            
            .timeline-content {
                margin-left: 10px;
            }
            
            .timeline-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Animation de chargement */
        .loading-timeline {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .loading-timeline i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        /* Styles pour les filtres améliorés */
        .filters-section {
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }

        .filters-section select,
        .filters-section input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .filters-section select:focus,
        .filters-section input:focus {
            outline: none;
            border-color: #1a5276;
            box-shadow: 0 0 0 2px rgba(26, 82, 118, 0.2);
        }

        .filters-section button {
            background: #1a5276;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filters-section button:hover {
            background: #154360;
            transform: translateY(-1px);
        }
    </style>
    <div id="reclamations" class="section">
        <h2 style=" color: #154360;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-align: center;">Gestion des Réclamations</h2>
        <div class="action-grid">
            <div class="action-card">
                <i class="fas fa-exclamation-circle"></i>
                <h3>Nouvelle Réclamation</h3>
                <p>Soumettre une nouvelle demande ou signalement</p>
                <button onclick="openModal('nouvelleReclamationModal')">
                    Soumettre
                </button>
            </div>
            <div class="action-card">
                <i class="fas fa-tasks"></i>
                <h3>Suivi des Réclamations</h3>
                <p>Consultez l'état de vos réclamations en cours</p>
                <button onclick="openModal('suiviReclamationsModal')">
                    Consulter
                </button>
            </div>
            <div class="action-card">
                <i class="fas fa-history"></i>
                <h3>Historique</h3>
                <p>Accédez à l'ensemble de vos réclamations passées</p>
                <button onclick="openModal('historiqueModal')">
                    Afficher
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Nouvelle Réclamation -->
    <div id="nouvelleReclamationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-exclamation-circle"></i>
                    Nouvelle Réclamation
                </h3>
                <span class="close" onclick="closeModal('nouvelleReclamationModal')">&times;</span>
            </div>
            <div class="modal-body">
                <form id="nouvelleReclamationForm" method="POST" action="assets/traitements/traitement_reclamation_mvc.php" enctype="multipart/form-data">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_year">Année en cours : </label>
                            <input type="text" id="current_year" name="current_year" value="<?php echo $annee_academique ?? date('Y'); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label for="level">Niveau d'étude: </label>
                            <input type="text" id="level" name="level" value="<?= htmlspecialchars($niveau_etudiant); ?>" disabled>
                        </div>
                    </div>

                    <div class="form-row name">
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($recupStudentData['nom_etd'] ?? ''); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($recupStudentData['prenom_etd'] ?? ''); ?>" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="motif_reclamation">Motif de la réclamation (précis et succinct) *</label>
                        <textarea name="motif_reclamation" id="motif_reclamation" required></textarea>
                    </div>

                    <div class="form-group" id="matiere-container">
                        <label for="noms_matieres">Matière à préciser *</label>
                        <div class="matiere-group">
                            <input type="text" name="noms_matieres[]" placeholder="Nom de la matière" required>
                            <button type="button" class="remove-matiere-btn" onclick="removeMatiereItem(this)" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="control-container">
                            <button type="button" class="add-matiere-btn" onclick="addMatiereItem()">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pieceJointe">Pièce jointe (optionnel)</label>
                        <input type="file" id="pieceJointe" name="pieceJointe" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="button btn-secondary" onclick="closeModal('nouvelleReclamationModal')">
                    <i class="fas fa-times"></i>
                    Annuler
                </button>

                <button type="submit" form="nouvelleReclamationForm" name="send_reclamation" class="button btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Soumettre
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Suivi des Réclamations -->
    <div id="suiviReclamationsModal" class="modal">
        <div class="modal-content" style="max-width: 800px; width: 90%;">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-tasks"></i>
                    Suivi des Réclamations
                </h3>
                <span class="close" onclick="closeModal('suiviReclamationsModal')">&times;</span>
            </div>
            <div class="modal-body">
                <!-- Filtres -->
                <div class="filters-section" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <label for="filterStatus" style="font-weight: 600; color: #1a5276; margin-right: 8px;">Statut:</label>
                            <select id="filterStatus" onchange="filterReclamations()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px;">
                                <option value="">Tous les statuts</option>
                                <option value="En attente de traitement">En attente</option>
                                <option value="Traitée par le responsable de scolarité">Traitée par scolarité</option>
                                <option value="Traitée par le responsable de filière">Traitée par filière</option>
                            </select>
                        </div>
                        <div>
                            <label for="searchReclamation" style="font-weight: 600; color: #1a5276; margin-right: 8px;">Rechercher:</label>
                            <input type="text" id="searchReclamation" placeholder="Motif ou matière..." onkeyup="filterReclamations()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 200px;">
                        </div>
                        <button onclick="rafraichirSuivi()" style="background: #1a5276; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        <button onclick="exporterTimeline()" style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-download"></i> Exporter
                        </button>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="stats-section" style="margin-bottom: 20px; display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
                    <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #2196f3;">
                        <div style="font-size: 24px; font-weight: bold; color: #2196f3;"><?php echo count($reclamationsEnCours); ?></div>
                        <div style="font-size: 14px; color: #666;">Total</div>
                    </div>
                    <div style="background: #fff3e0; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #ff9800;">
                        <div style="font-size: 24px; font-weight: bold; color: #ff9800;"><?php echo count(array_filter($reclamationsEnCours, function($r) { return $r['statut_reclamation'] === 'En attente de traitement'; })); ?></div>
                        <div style="font-size: 14px; color: #666;">En attente</div>
                    </div>
                    <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; text-align: center; border-left: 4px solid #4caf50;">
                        <div style="font-size: 24px; font-weight: bold; color: #4caf50;"><?php echo count(array_filter($reclamationsEnCours, function($r) { return strpos($r['statut_reclamation'], 'Traitée') !== false; })); ?></div>
                        <div style="font-size: 14px; color: #666;">Traitées</div>
                    </div>
                </div>

                <!-- Timeline des réclamations -->
                <div id="reclamationsEnCours" class="timeline-container">
                    <?php if (empty($reclamations)): ?>
                        <div class="loading-timeline">
                            <i class="fas fa-inbox"></i>
                            <div>Aucune réclamation en cours</div>
                        </div>
                    <?php else: ?>
                        <div class="timeline-line"></div>
                        <?php foreach ($reclamations as $index => $reclamation): ?>
                            <div class="timeline-item" data-status="<?php echo htmlspecialchars($reclamation['statut_reclamation']); ?>" data-content="<?php echo htmlspecialchars(strtolower($reclamation['motif_reclamation'] . ' ' . $reclamation['matieres'])); ?>">
                                
                                <!-- Point de timeline -->
                                <div class="timeline-dot" data-status="<?php echo htmlspecialchars($reclamation['statut_reclamation']); ?>">
                                    <?php
                                    $statusIcon = '';
                                    $statusColor = '#1a5276';
                                    switch($reclamation['statut_reclamation']) {
                                        case 'En attente de traitement':
                                            $statusIcon = 'fas fa-clock';
                                            $statusColor = '#ff9800';
                                            break;
                                        case 'Traitée par le responsable de scolarité':
                                            $statusIcon = 'fas fa-user-check';
                                            $statusColor = '#2196f3';
                                            break;
                                        case 'Traitée par le responsable de filière':
                                            $statusIcon = 'fas fa-check-circle';
                                            $statusColor = '#4caf50';
                                            break;
                                        default:
                                            $statusIcon = 'fas fa-exclamation-circle';
                                            $statusColor = '#666';
                                    }
                                    ?>
                                    <i class="<?php echo $statusIcon; ?>" style="color: <?php echo $statusColor; ?>;"></i>
                                </div>

                                <!-- Contenu de la réclamation -->
                                <div class="timeline-content" data-status="<?php echo htmlspecialchars($reclamation['statut_reclamation']); ?>">
                                    
                                    <!-- En-tête avec numéro et statut -->
                                    <div class="timeline-header">
                                        <span class="timeline-id">
                                            #REC-<?php echo htmlspecialchars($reclamation['id_reclamation']); ?>
                                        </span>
                                        <span class="timeline-status" style="color: black;" data-status="<?php echo htmlspecialchars($reclamation['statut_reclamation']); ?>">
                                            <?php echo htmlspecialchars($reclamation['statut_reclamation']); ?>
                                        </span>
                                    </div>

                                    <!-- Motif de la réclamation -->
                                    <div class="timeline-section">
                                        <div class="timeline-section-title">
                                            <i class="fas fa-exclamation-circle" style="color: #1a5276;"></i>
                                            Motif de la réclamation
                                        </div>
                                        <div class="timeline-section-content">
                                            <?php echo htmlspecialchars($reclamation['motif_reclamation']); ?>
                                        </div>
                                    </div>

                                    <!-- Matières concernées -->
                                    <?php
                                    $matieres = json_decode($reclamation['matieres'], true);
                                    if (!empty($matieres)):
                                    ?>
                                    <div class="timeline-section">
                                        <div class="timeline-section-title">
                                            <i class="fas fa-book" style="color: #1a5276;"></i>
                                            Matières concernées
                                        </div>
                                        <div class="timeline-section-content">
                                            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                                <?php foreach ($matieres as $matiere): ?>
                                                    <span class="timeline-matiere-tag">
                                                        <?php echo htmlspecialchars($matiere); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Retour de traitement -->
                                    <?php if (!empty($reclamation['retour_traitement'])): ?>
                                    <div class="timeline-section">
                                        <div class="timeline-section-title">
                                            <i class="fas fa-comment" style="color: #1a5276;"></i>
                                            Retour de traitement
                                        </div>
                                        <div class="timeline-section-content">
                                            <?php echo htmlspecialchars($reclamation['retour_traitement']); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Date de traitement -->
                                    <?php if (!empty($reclamation['date_traitement'])): ?>
                                    <div class="timeline-section">
                                        <div class="timeline-section-title">
                                            <i class="fas fa-calendar-alt" style="color: #666;"></i>
                                            Date de traitement
                                        </div>
                                        <div class="timeline-section-content">
                                            <div class="timeline-date">
                                                <i class="fas fa-clock"></i>
                                                Traitée le: <?php echo date('d/m/Y', strtotime($reclamation['date_traitement'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Barre de progression -->
                                    <div class="timeline-progress">
                                        <div class="timeline-progress-bar">
                                            <span class="timeline-progress-text">Progression:</span>
                                            <div class="timeline-progress-track">
                                                <div class="timeline-progress-fill" style="width: <?php
                                                    $progress = 0;
                                                    switch($reclamation['statut_reclamation']) {
                                                        case 'En attente de traitement':
                                                            $progress = 33;
                                                            break;
                                                        case 'Traitée par le responsable de scolarité':
                                                            $progress = 66;
                                                            break;
                                                        case 'Traitée par le responsable de filière':
                                                            $progress = 100;
                                                            break;
                                                    }
                                                    echo $progress; ?>%;"></div>
                                            </div>
                                            <span class="timeline-progress-text"><?php echo $progress; ?>%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button btn-secondary" onclick="closeModal('suiviReclamationsModal')">
                    <i class="fas fa-times"></i>
                    Fermer
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Historique -->
    <div id="historiqueModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-history"></i>
                    Historique des Réclamations
                </h3>
                <span class="close" onclick="closeModal('historiqueModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="filtreStatut">Filtrer par statut:</label>
                    <select id="filtreStatut" onchange="filtrerHistorique()">
                        <option value="">Tous les statuts</option>
                        <option value="resolu">Résolu</option>
                        <option value="ferme">Fermé</option>
                        <option value="en-attente">En attente</option>
                        <option value="en-cours">En cours</option>
                    </select>
                </div>

                <div id="historiqueReclamations">
                    <?php if (empty($reclamations)): ?>
                        <div class="no-data">Aucune réclamation trouvée</div>
                    <?php else: ?>
                        <?php foreach ($reclamations as $reclamation): ?>
                            <div class="reclamation-item">
                                <div class="reclamation-header">
                                    <span class="reclamation-id">#<?php echo htmlspecialchars($reclamation['id_reclamation']); ?></span>
                                    <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $reclamation['statut_reclamation'])); ?>">
                                        <?php echo htmlspecialchars($reclamation['statut_reclamation']); ?>
                                    </span>
                                </div>
                                <div class="reclamation-content">
                                    <strong><?php echo htmlspecialchars($reclamation['motif_reclamation']); ?></strong><br>
                                    <?php
                                    $matieres = json_decode($reclamation['matieres'], true);
                                    if (!empty($matieres)) {
                                        echo "Matières concernées : " . implode(", ", array_map('htmlspecialchars', $matieres));
                                    }
                                    ?>
                                </div>
                                <div class="reclamation-date">
                                    Créée le: <?php echo $reclamation['date_creation_reclamation']; ?>
                                </div>
                                <div class="reclamation-actions">
                                    <a href="assets/traitements/imprimer_reclamation.php?id_reclamation=<?php echo urlencode($reclamation['id_reclamation']); ?>"
                                        target="_blank"
                                        class="button btn-secondary">
                                        <i class="fas fa-print"></i>
                                        Imprimer
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal de Confirmation -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-check-circle"></i>
                    Confirmation
                </h3>
                <span class="close" onclick="closeModal('confirmationModal')">&times;</span>
            </div>
            <div class="modal-body">
                <p style="text-align: center; font-size: 16px;">
                    Votre réclamation a bien été prise en compte
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="button btn-primary" onclick="closeModal('confirmationModal')">
                    <i class="fas fa-check"></i>
                    OK
                </button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'flex';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }

        // Fermer la modale en cliquant à l'extérieur
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                setTimeout(() => {
                    event.target.style.display = 'none';
                }, 300);
            }
        }

        // Afficher la modal de confirmation si nécessaire
        <?php if (isset($_SESSION['show_confirmation']) && $_SESSION['show_confirmation']): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openModal('confirmationModal');
                <?php unset($_SESSION['show_confirmation']); ?>
            });
        <?php endif; ?>

        function resetMatiereFields() {
            const matiereContainer = document.getElementById('matiere-container');
            const matiereGroups = matiereContainer.querySelectorAll('.matiere-group');

            // Supprimer tous les groupes sauf le premier
            for (let i = 1; i < matiereGroups.length; i++) {
                matiereGroups[i].remove();
            }

            // Vider le premier champ et cacher le bouton de suppression
            const firstGroup = matiereContainer.querySelector('.matiere-group');
            firstGroup.querySelector('input').value = '';
            firstGroup.querySelector('.remove-matiere-btn').style.display = 'none';
        }

        function rafraichirSuivi() {
            const icon = document.querySelector('#suiviReclamationsModal .fa-sync-alt');
            icon.style.animation = 'spin 1s linear';
            setTimeout(() => {
                icon.style.animation = '';
                window.location.reload();
            }, 1000);
        }

        function filtrerHistorique() {
            const filtre = document.getElementById('filtreStatut').value;
            const reclamations = document.querySelectorAll('#historiqueReclamations .reclamation-item');

            reclamations.forEach(item => {
                const statusBadge = item.querySelector('.status-badge');
                if (!filtre || statusBadge.classList.contains('status-' + filtre)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function filterReclamations() {
            const statusFilter = document.getElementById('filterStatus').value;
            const searchFilter = document.getElementById('searchReclamation').value.toLowerCase();
            const reclamations = document.querySelectorAll('#reclamationsEnCours .timeline-item');

            reclamations.forEach(item => {
                const status = item.getAttribute('data-status');
                const content = item.getAttribute('data-content');
                
                let showByStatus = true;
                let showBySearch = true;

                // Filtre par statut
                if (statusFilter && status !== statusFilter) {
                    showByStatus = false;
                }

                // Filtre par recherche
                if (searchFilter && !content.includes(searchFilter)) {
                    showBySearch = false;
                }

                // Afficher ou masquer l'élément
                if (showByStatus && showBySearch) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function exporterHistorique() {
            alert('Export en cours... Le fichier sera téléchargé dans quelques instants.');
        }

        // Fonction pour ajouter un nouveau champ de matière
        function addMatiereItem() {
            const matiereContainer = document.getElementById('matiere-container');
            const newMatiereGroup = document.createElement('div');
            newMatiereGroup.className = 'matiere-group';
            newMatiereGroup.innerHTML = `
                <input type="text" name="noms_matieres[]" placeholder="Nom de la matière" required>
                <button type="button" class="remove-matiere-btn" onclick="removeMatiereItem(this)">
                    <i class="fas fa-times"></i>
                </button>
            `;
            matiereContainer.insertBefore(newMatiereGroup, matiereContainer.querySelector('.control-container'));

            // Afficher le bouton de suppression du premier groupe s'il y a plus d'un groupe
            const matiereGroups = matiereContainer.querySelectorAll('.matiere-group');
            if (matiereGroups.length > 1) {
                matiereGroups[0].querySelector('.remove-matiere-btn').style.display = 'block';
            }
        }

        // Fonction pour supprimer un champ de matière
        function removeMatiereItem(button) {
            const matiereGroup = button.closest('.matiere-group');
            const matiereContainer = document.getElementById('matiere-container');
            const matiereGroups = matiereContainer.querySelectorAll('.matiere-group');

            if (matiereGroups.length > 1) {
                matiereGroup.remove();
                // Cacher le bouton de suppression du premier groupe s'il ne reste qu'un groupe
                if (matiereGroups.length === 2) {
                    matiereGroups[0].querySelector('.remove-matiere-btn').style.display = 'none';
                }
            }
        }

        // Animation CSS pour le spin
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);

        // Fonctionnalités spécifiques à la timeline
        document.addEventListener('DOMContentLoaded', function() {
            initializeTimeline();
        });

        function initializeTimeline() {
            // Ajouter des événements de clic sur les points de timeline
            const timelineDots = document.querySelectorAll('.timeline-dot');
            timelineDots.forEach(dot => {
                dot.addEventListener('click', function() {
                    const timelineItem = this.closest('.timeline-item');
                    const content = timelineItem.querySelector('.timeline-content');
                    
                    // Animation de focus sur l'élément cliqué
                    timelineItem.style.transform = 'scale(1.02)';
                    content.style.boxShadow = '0 12px 35px rgba(0,0,0,0.2)';
                    
                    setTimeout(() => {
                        timelineItem.style.transform = '';
                        content.style.boxShadow = '';
                    }, 300);
                });
            });

            // Animation des barres de progression
            const progressBars = document.querySelectorAll('.timeline-progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });

            // Effet de survol sur les éléments de timeline
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.zIndex = '10';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.zIndex = '';
                });
            });
        }

        // Fonction pour actualiser la timeline avec animation
        function rafraichirSuivi() {
            const icon = document.querySelector('#suiviReclamationsModal .fa-sync-alt');
            const timelineContainer = document.querySelector('#reclamationsEnCours');
            
            icon.style.animation = 'spin 1s linear';
            
            // Ajouter un effet de chargement
            timelineContainer.style.opacity = '0.5';
            timelineContainer.style.pointerEvents = 'none';
            
            setTimeout(() => {
                icon.style.animation = '';
                timelineContainer.style.opacity = '';
                timelineContainer.style.pointerEvents = '';
                window.location.reload();
            }, 1000);
        }

        // Fonction améliorée pour filtrer les réclamations
        function filterReclamations() {
            const statusFilter = document.getElementById('filterStatus').value;
            const searchFilter = document.getElementById('searchReclamation').value.toLowerCase();
            const reclamations = document.querySelectorAll('#reclamationsEnCours .timeline-item');

            reclamations.forEach((item, index) => {
                const status = item.getAttribute('data-status');
                const content = item.getAttribute('data-content');
                
                let showByStatus = true;
                let showBySearch = true;

                // Filtre par statut
                if (statusFilter && status !== statusFilter) {
                    showByStatus = false;
                }

                // Filtre par recherche
                if (searchFilter && !content.includes(searchFilter)) {
                    showBySearch = false;
                }

                // Animation de filtrage
                if (showByStatus && showBySearch) {
                    item.style.display = 'block';
                    item.style.animation = 'slideIn 0.5s ease forwards';
                    item.style.animationDelay = (index * 0.1) + 's';
                } else {
                    item.style.display = 'none';
                }
            });

            // Afficher un message si aucun résultat
            const visibleItems = document.querySelectorAll('#reclamationsEnCours .timeline-item[style*="display: block"]');
            const noResultsMessage = document.querySelector('#reclamationsEnCours .no-results');
            
            if (visibleItems.length === 0 && (statusFilter || searchFilter)) {
                if (!noResultsMessage) {
                    const message = document.createElement('div');
                    message.className = 'no-results';
                    message.innerHTML = `
                        <div class="loading-timeline">
                            <i class="fas fa-search"></i>
                            <div>Aucune réclamation trouvée avec ces critères</div>
                        </div>
                    `;
                    document.querySelector('#reclamationsEnCours').appendChild(message);
                }
            } else if (noResultsMessage) {
                noResultsMessage.remove();
            }
        }

        // Fonction pour exporter les données de la timeline
        function exporterTimeline() {
            const reclamations = document.querySelectorAll('#reclamationsEnCours .timeline-item');
            const data = [];
            
            reclamations.forEach(item => {
                const id = item.querySelector('.timeline-id').textContent;
                const status = item.querySelector('.timeline-status').textContent;
                const motif = item.querySelector('.timeline-section-content').textContent.trim();
                
                data.push({
                    id: id,
                    status: status,
                    motif: motif,
                    date: new Date().toLocaleDateString('fr-FR')
                });
            });
            
            // Créer un fichier CSV
            const csvContent = "data:text/csv;charset=utf-8," 
                + "ID,Statut,Motif,Date d'export\n"
                + data.map(row => `${row.id},${row.status},${row.motif},${row.date}`).join("\n");
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "reclamations_timeline.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>

</html>