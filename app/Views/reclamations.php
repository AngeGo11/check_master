<?php

require_once '../app/config/config.php';
require_once __DIR__ . "/../../public/assets/traitements/config_year.php";
require_once __DIR__ . '/../Controllers/ReclamationController.php';

// Enregistrer l'accès à la page des réclamations
//enregistrer_acces_module($pdo, $_SESSION['user_id'], 'reclamations');

// Initialiser le contrôleur
$reclamationController = new ReclamationController($pdo);

// Récupérer les données via le contrôleur
$data = $reclamationController->index($_SESSION['user_id']);

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
                <form id="nouvelleReclamationForm" method="POST" action="assets/traitements/traitement_reclamation.php" enctype="multipart/form-data">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_year">Année en cours : </label>
                            <input type="text" id="current_year" name="current_year" value="<?php echo $_SESSION['current_year']; ?>" disabled>
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
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-tasks"></i>
                    Suivi des Réclamations
                </h3>
                <span class="close" onclick="closeModal('suiviReclamationsModal')">&times;</span>
            </div>
            <div class="modal-body">
                <div id="reclamationsEnCours">
                    <?php
                    if (empty($reclamationsEnCours)): ?>
                        <div class="no-data">Aucune réclamation en cours</div>
                    <?php else: ?>
                        <?php foreach ($reclamationsEnCours as $reclamation): ?>
                            <div class="reclamation-item">
                                <div class="reclamation-header">
                                    <span class="reclamation-id">#REC-<?php echo htmlspecialchars($reclamation['id_reclamation']); ?></span>
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
                                <div class="reclamation-date">Créée le: <?php echo $reclamation['date_creation_reclamation']; ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button btn-primary" onclick="rafraichirSuivi()">
                    <i class="fas fa-sync-alt"></i>
                    Actualiser
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
    </script>
</body>

</html>