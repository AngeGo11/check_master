<!-- Modal pour l'envoi d'email -->
<div class="modal" id="email-modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="top-text">
            <h2 class="modal-title"><i class="fas fa-envelope"></i> Envoi par email</h2>
            <button class="close-modal-btn close" id="close-email-modal-btn">×</button>
        </div>
        <div class="modal-body">
            <form method="post" action="assets/email/send_bilan.php" id="email-form">
                <div class="form-group">
                    <label for="email-address">Adresse email du destinataire :</label>
                    <input type="email" id="email-address" name="email" required placeholder="exemple@email.com">
                </div>
                <div class="form-group">
                    <label for="email-subject">Sujet :</label>
                    <input type="text" id="email-subject" name="subject" required>
                </div>
                <div class="form-group">
                    <label for="email-attachment">Pièce jointe :</label>
                    <input type="text" id="email-attachment" name="attachment" readonly>
                </div>
                <div class="form-group">
                    <label for="email-message">Message (optionnel) :</label>
                    <textarea id="email-message" name="message" rows="4" placeholder="Ajoutez un message personnel..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="button" id="cancel-email-btn">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="button">
                        <i class="fas fa-paper-plane"></i> Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>