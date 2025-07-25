<?php
// Calcul de l'âge
$age = null;
if ($etudiant_details['etudiant']['date_naissance_etd']) {
    $dateNaissance = new DateTime($etudiant_details['etudiant']['date_naissance_etd']);
    $aujourdhui = new DateTime();
    $age = $aujourdhui->diff($dateNaissance)->y;
}

// Détermination du niveau d'étude
$niveauEtude = $etudiant_details['etudiant']['lib_niv_etd'] ?? "Non renseigné";

// Statut du stage
$statutStage = $etudiant_details['stage'] ? "En stage" : "Pas de stage déclaré";

// Statut de la scolarité
if (isset($etudiant_details['scolarite']['reste_a_payer'])) {
    if ($etudiant_details['scolarite']['reste_a_payer'] > 0) {
        $scolariteAJour = "Paiement partiel (Reste à payer: " . $etudiant_details['scolarite']['reste_a_payer'] . " FCFA)";
    } elseif ($etudiant_details['scolarite']['reste_a_payer'] == 0) {
        $scolariteAJour = "Soldé";
    }
} else {
    $scolariteAJour = "Non payé";
}
?>


<div class="modal open" id="check-details-student-modal">
    <div class="modal-content details enhanced-details">
        <div class="top-text">
            <h2 class="modal-title">
                <i class="fas fa-user-check"></i>
                INFORMATION DE L'ÉTUDIANT : <?php echo $etudiant_details['etudiant']['prenom_etd'] . " " . $etudiant_details['etudiant']['nom_etd']; ?>
            </h2>
            <a href="?page=etudiants" class="close" id="close-modal-details-btn">
                <i class="fa fa-xmark fa-2x"></i>
            </a>
        </div>

        <!-- Section Informations Générales -->
        <div class="info-section">
            <h3><i class="fas fa-info-circle"></i> Informations Générales</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Nom complet :</label>
                    <span class="info-value"><?php echo $etudiant_details['etudiant']['prenom_etd'] . " " . $etudiant_details['etudiant']['nom_etd']; ?></span>
                </div>
                <div class="info-item">
                    <label>N° Carte Étudiant :</label>
                    <span class="info-value"><?php echo $etudiant_details['etudiant']['num_carte_etd']; ?></span>
                </div>
                <div class="info-item">
                    <label>Email :</label>
                    <span class="info-value"><?php echo $etudiant_details['etudiant']['email_etd']; ?></span>
                </div>

                <div class="info-item">
                    <label>Téléphone :</label>
                    <span class="info-value"><?php echo $etudiant_details['etudiant']['num_tel_etd'] ? $etudiant_details['etudiant']['num_tel_etd'] : "Non renseigné"; ?></span>
                </div>

                <div class="info-item">
                    <label>Sexe:</label>
                    <span class="info-value"><?php echo $etudiant_details['etudiant']['sexe_etd'] ? $etudiant_details['etudiant']['sexe_etd'] : "Non renseigné"; ?></span>
                </div>

                <div class="info-item">
                    <label>Date de naissance :</label>
                    <span class="info-value"><?php echo $etudiant_details['etudiant']['date_naissance_etd'] ? date('d/m/Y', strtotime($etudiant_details['etudiant']['date_naissance_etd'])) : "Non renseigné"; ?></span>
                </div>

                <div class="info-item">
                    <label>Âge :</label>
                    <span class="info-value"><?php echo $age ? $age . " ans" : "Non renseigné"; ?></span>
                </div>
            </div>
        </div>

        <!-- Section Scolarité -->
        <div class="info-section">
            <h3><i class="fas fa-graduation-cap"></i> Scolarité</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Promotion :</label>
                    <span class="info-value"><?php echo $etudiant_details['promotion'] ? htmlspecialchars($etudiant_details['promotion']) : 'Non renseignée'; ?></span>
                </div>
                <div class="info-item">
                    <label>Niveau d'étude :</label>
                    <span class="info-value level-badge"><?php echo $niveauEtude; ?></span>
                </div>
                <div class="info-item">
                    <label>Statut scolarité :</label>
                    <span class="info-value status-badge <?php echo strtolower(str_replace(' ', '-', $scolariteAJour)); ?>">
                        <?php echo $scolariteAJour; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div> 