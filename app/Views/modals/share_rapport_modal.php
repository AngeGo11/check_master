<?php
// Désactiver l'affichage des erreurs pour éviter de polluer les réponses JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
// Assurez-vous que ce chemin est accessible en écriture par le serveur web
ini_set('error_log', __DIR__ . '/../../../storage/logs/php-error.log');

$rapport_details = $controller->getRapportDetails($_GET['rapport'], $_GET['id']);
$enseignantsControllers = new EnseignantController($pdo);
$membresCommission = $enseignantsControllers->commissionMembers();

?>

<div class="modal" id="share-rapport-modal">
    <div class="modal-content share-modal">
        <div class="top-text">
            <h2 class="modal-title">Partager le rapport</h2>
            <a href="?page=etudiants" class="close">
                <i class="fa fa-xmark fa-2x"></i>
            </a>
        </div>

        <div class="rapport-info">
            <div class="rapport-card">
                <div class="rapport-icon">
                    <i class="fa fa-file-alt"></i>
                </div>
                <div class="rapport-details">
                    <p class="rapport-title"><strong><?php echo $rapport_details['nom_rapport']; ?></strong></p>
                    <p class="rapport-student"><?php echo $rapport_details['prenom_etd'] . ' ' . $rapport_details['nom_etd']; ?></p>
                </div>
            </div>
        </div>

        <form action="?page=etudiants&id=<?php echo $_GET['id']; ?>&rapport=<?php echo $_GET['rapport']; ?>&action=share" method="POST">
            <div class="form-group">
                <label class="recipients-label">Sélectionnez les destinataires:</label>
                <div class="users-list">
                    <?php foreach ($membresCommission as $member): ?>
                        <div class="user-item">
                            <div class="user-checkbox">
                                <input type="checkbox" name="share_users[]" id="user_<?php echo $member['id_utilisateur']; ?>" value="<?php echo $member['id_utilisateur']; ?>">
                                <label for="user_<?php echo $member['id_utilisateur']; ?>" class="custom-checkbox"></label>
                            </div>
                            <div class="user-info">
                                <div class="user-avatar"><?php echo substr($member['prenoms_ens'], 0, 1); ?></div>
                                <div class="user-details">
                                    <span class="user-name"><?php echo $member['prenoms_ens'] . ' ' . $member['nom_ens']; ?></span>
                                    <span class="user-email"><?php echo $member['login_utilisateur']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="share_message">Message (optionnel):</label>
                <textarea name="share_message" id="share_message" rows="3" placeholder="Ajoutez un message personnalisé..."></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="button share-btn">
                    <i class="fa-solid fa-paper-plane"></i> Partager
                </button>
                <a href="?page=etudiants" class="button cancel-btn">
                    <i class="fa-solid fa-times"></i> Annuler
                </a>
            </div>
        </form>
    </div>
</div>

