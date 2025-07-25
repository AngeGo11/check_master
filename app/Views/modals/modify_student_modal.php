<div class="modal open" id="modify-student-modal">
    <div class="modal-content">
        <div class="top-text">
            <h2 class="modal-title">Modification des informations étudiants</h2>
            <a href="?page=etudiants" class="close" id="close-modal-modify-btn">
                <i class="fa fa-xmark fa-2x"></i>
            </a>
        </div>

        <form action="?page=etudiants&action=modify_process&id=<?php echo $etudiant_modify['etudiant']['num_etd']; ?>" method="POST" id="std-form" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="card">N° carte étudiant</label>
                    <input type="text" id="card" name="card" value="<?php echo htmlspecialchars($etudiant_modify['etudiant']['num_carte_etd']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="niveau_etude">Niveau d'étude</label>
                    <select name="id_niv_etd" id="niveau_etude" required>
                        <option value="">Sélectionnez un niveau d'étude</option>
                        <?php foreach ($lists['niveaux'] as $niv): ?>
                            <?php $selected = ($niv['id_niv_etd'] === $etudiant_modify['etudiant']['id_niv_etd']) ? 'selected' : ''; ?>
                            <option value="<?php echo $niv['id_niv_etd']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($niv['lib_niv_etd']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="promotion">Promotion</label>
                    <select name="id_promotion" id="promotion" required>
                        <option value="">-- Sélectionnez une promotion --</option>
                        <?php foreach ($lists['promotions'] as $promo): ?>
                            <?php $selected = ($promo['id_promotion'] == $etudiant_modify['etudiant']['id_promotion']) ? 'selected' : ''; ?>
                            <option value="<?php echo $promo['id_promotion']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($promo['lib_promotion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom de l'etudiant</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($etudiant_modify['etudiant']['nom_etd']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="prenoms">Prénoms Étudiant</label>
                    <input type="text" id="prenoms" name="prenoms" value="<?php echo htmlspecialchars($etudiant_modify['etudiant']['prenom_etd']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email de l'étudiant</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($etudiant_modify['etudiant']['email_etd']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="date">Date de naissance</label>
                    <input type="date" name="date" id="date" value="<?php echo $etudiant_modify['etudiant']['date_naissance_etd'] ? htmlspecialchars($etudiant_modify['etudiant']['date_naissance_etd']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="sexe">Sexe</label>
                    <select name="sexe" id="sexe" required>
                        <option value="">Sélectionnez un sexe</option>
                        <option value="Homme" <?php echo ($etudiant_modify['etudiant']['sexe_etd'] == 'Homme') ? 'selected' : ''; ?>>Homme</option>
                        <option value="Femme" <?php echo ($etudiant_modify['etudiant']['sexe_etd'] == 'Femme') ? 'selected' : ''; ?>>Femme</option>
                    </select>
                </div>
            </div>

            <input type="submit" class="submit-btn" value="Enregistrer ces modifications">
        </form>
    </div>
</div> 