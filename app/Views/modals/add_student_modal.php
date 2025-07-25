<div class="modal open" id="add-student-modal">
    <div class="modal-content">
        <div class="top-text">
            <h2 class="modal-title">Ajouter un étudiant</h2>
            <a href="?page=etudiants" class="close" id="close-modal-btn">
                <i class="fa fa-xmark fa-2x"></i>
            </a>
        </div>

        <form action="?page=etudiants&action=add_process" method="POST" id="std-form" enctype="multipart/form-data">
            <!-- Première section - 3 colonnes -->
            <div class="form-row">
                <div class="form-group">
                    <label for="card">N° carte étudiant</label>
                    <input type="text" id="card" name="card" placeholder="Saisissez le numéro de carte étudiant ici..." required>
                </div>
                <div class="form-group">
                    <label for="niveau">Niveau d'étude</label>
                    <select name="id_niv_etd" id="niveau" required>
                        <option value="">-- Sélectionnez un niveau d'étude--</option>
                        <?php foreach ($lists['niveaux'] as $niv): ?>
                            <option value="<?php echo $niv['id_niv_etd']; ?>"><?php echo htmlspecialchars($niv['lib_niv_etd']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="promotion">Promotion</label>
                    <select name="id_promotion" id="promotion" required>
                        <option value="">-- Sélectionnez une promotion --</option>
                        <?php foreach ($lists['promotions'] as $promo): ?>
                            <option value="<?php echo $promo['id_promotion']; ?>"><?php echo htmlspecialchars($promo['lib_promotion']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Deuxième section - 3 colonnes -->
            <div class="form-row">
                <div class="form-group">
                    <label for="nom">Nom de l'etudiant</label>
                    <input type="text" id="nom" name="nom" placeholder="Saisissez le nom de l'étudiant ici..." required>
                </div>
                <div class="form-group">
                    <label for="prenoms">Prénoms de l'etudiant</label>
                    <input type="text" id="prenoms" name="prenoms" placeholder="Saisissez le prénom de l'étudiant ici..." required>
                </div>
                <div class="form-group">
                    <label for="email">Email de l'étudiant</label>
                    <input type="email" id="email" name="email" placeholder="Saisissez l'email de l'étudiant ici..." required>
                </div>
            </div>

            <!-- Troisième section - 2 colonnes (reste) -->
            <div class="form-row">
                <div class="form-group">
                    <label for="date">Date de naissance</label>
                    <input type="date" name="date" id="date">
                </div>
                <div class="form-group">
                    <label for="sexe">Sexe</label>
                    <select name="sexe" id="sexe">
                        <option value="Homme">Homme</option>
                        <option value="Femme">Femme</option>
                    </select>
                </div>
            </div>

            <input type="submit" class="submit-btn" value="Ajouter cet étudiant">
        </form>
    </div>
</div> 