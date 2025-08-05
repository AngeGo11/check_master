<?php
// Récupération des détails du rapport
$rapport_details = $controller->getRapportDetails($_GET['rapport'], $_GET['id']);
?>


<div class="modal" id="view-rapport-modal">
    <div class="modal-content">
        <div class="top-text">
            <h2 class="modal-title"><i class="fas fa-file-signature"></i> Détails du Rapport</h2>
            <a href="?page=etudiants" class="close" id="close-modal-rapport-btn">
                <i class="fa fa-xmark"></i>
            </a>
        </div>

        <div class="rapport-details">
            <div class="details-grid">
                <div class="student-info info-box">
                    <h3><i class="fas fa-user-graduate"></i> Informations sur l'étudiant</h3>
                    <div class="info-item"><label>Nom:</label><span><?php echo htmlspecialchars($rapport_details['nom_etd'] . ' ' . $rapport_details['prenom_etd']); ?></span></div>
                    <div class="info-item"><label>Email:</label><span><?php echo htmlspecialchars($rapport_details['email_etd']); ?></span></div>
                    <div class="info-item"><label>N° Carte:</label><span><?php echo htmlspecialchars($rapport_details['num_carte_etd']); ?></span></div>
                </div>

                <div class="rapport-info info-box">
                    <h3><i class="fas fa-file-alt"></i> Informations sur le rapport</h3>
                    <div class="info-item"><label>Titre:</label><span><?php echo htmlspecialchars($rapport_details['nom_rapport']); ?></span></div>
                    <div class="info-item"><label>Soumission:</label><span><?php echo date('d/m/Y', strtotime($rapport_details['date_rapport'])); ?></span></div>
                    <?php if (isset($rapport_details['fichier_rapport'])): ?>
                        <div class="info-item">
                            <label>Fichier:</label>
                            <span>
                                <a href="<?php echo htmlspecialchars($rapport_details['fichier_rapport']); ?>" target="_blank">
                                    <i class="fas fa-download"></i> Télécharger le rapport
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="rapport-actions">
                <button class="button preview-btn" data-path="<?php echo htmlspecialchars($rapport_details['fichier_rapport'] ?? ''); ?>">
                    <i class="fas fa-eye"></i> Aperçu
                </button>
                <a href="?page=etudiants&id=<?php echo $rapport_details['num_etd']; ?>&rapport=<?php echo $rapport_details['id_rapport_etd']; ?>&action=approve" class="button approve-btn">
                    <i class="fas fa-check"></i> Approuver
                </a>
                <a href="?page=etudiants&id=<?php echo $rapport_details['num_etd']; ?>&rapport=<?php echo $rapport_details['id_rapport_etd']; ?>&action=reject" class="button reject-btn">
                    <i class="fas fa-times"></i> Rejeter
                </a>
            </div>
        </div>
    </div>
</div> 