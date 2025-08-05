<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../public/assets/traitements/messages_functions.php';
require_once __DIR__ . '/../Controllers/MessageController.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pageConnexion.php');
    exit;
}

// Initialiser le contrôleur
$messageController = new MessageController($pdo);

// Récupérer les données via le contrôleur
$data = $messageController->index($_SESSION['user_id']);

$messages = $data['messages'] ?? [];
$contacts = $data['contacts'] ?? [];
$messagesNonLus = $data['messagesNonLus'] ?? [];
$messagesArchives = $data['messagesArchives'] ?? [];

// ====== TRAITEMENT DES ACTIONS ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'send_message':
            $messageData = [
                'expediteur_id' => $_SESSION['user_id'],
                'destinataire_id' => $_POST['destinataire_id'],
                'objet' => $_POST['objet'],
                'contenu' => $_POST['contenu'],
                'priorite' => $_POST['priorite'] ?? 'normale'
            ];

            if ($messageController->sendMessage($messageData)) {
                $_SESSION['success_message'] = "Message envoyé avec succès !";
            } else {
                $_SESSION['error_message'] = "Erreur lors de l'envoi du message.";
            }
            break;

        case 'archive_messages':
            $messageIds = $_POST['message_ids'] ?? [];
            if ($messageController->archiveMessages($messageIds)) {
                $_SESSION['success_message'] = "Messages archivés avec succès !";
            } else {
                $_SESSION['error_message'] = "Erreur lors de l'archivage.";
            }
            break;

        case 'delete_messages':
            $messageIds = $_POST['message_ids'] ?? [];
            if ($messageController->deleteMessages($messageIds)) {
                $_SESSION['success_message'] = "Messages supprimés avec succès !";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la suppression.";
            }
            break;

        case 'restore_messages':
            $messageIds = $_POST['message_ids'] ?? [];
            if ($messageController->restoreMessages($messageIds)) {
                $_SESSION['success_message'] = "Messages restaurés avec succès !";
            } else {
                $_SESSION['error_message'] = "Erreur lors de la restauration.";
            }
            break;
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - Modales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/boites_messages.css?v=<?php echo time(); ?>">
</head>

<body>

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

        .message-info{
            color: black;
        }

        input, textarea, select{
            color: black;
        }
    </style>
    <div class="section">
        <h2 style=" color: #154360;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-align: center;">Messagerie</h2>
        <div class="action-grid">
            <div class="action-card">
                <i class="fas fa-inbox"></i>
                <h3>Boîte de Réception</h3>
                <p> <?= count($messagesNonLus); ?> nouveau(x) message(s) non lu(s)</p>
                <button onclick="openModal('inbox')">Voir Messages</button>
            </div>
            <div class="action-card">
                <i class="fas fa-pen-to-square"></i>
                <h3>Nouveau Message</h3>
                <p>Commencez une nouvelle conversation</p>
                <button onclick="openModal('compose')">Composer</button>
            </div>
            <div class="action-card">
                <i class="fas fa-box-archive"></i>
                <h3>Messages Archivés</h3>
                <p>Retrouvez vos anciennes conversations</p>
                <button onclick="openModal('archives')">Accéder aux Archives</button>
            </div>
            <div class="action-card">
                <i class="fas fa-bell"></i>
                <h3>Notifications</h3>
                <p>Paramètres de notification</p>
                <button onclick="openModal('notifications')">Gérer</button>
            </div>
        </div>
    </div>


    <!-- Modal Boîte de Réception -->
    <div id="inbox-modal" class="modal-overlay">
        <div class="modal inbox-modal">
            <div class="modal-header">
                <h3><i class="fas fa-inbox"></i> Boîte de Réception</h3>
                <button class="modal-close" onclick="closeModal('inbox')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="search-box">
                    <input type="text" placeholder="Rechercher dans les messages...">
                    <button class="btn-search"><i class="fas fa-search"></i></button>
                </div>

                <?php if ($messages): ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message-item <?php echo $message['statut'] === 'non lu' ? 'unread' : ''; ?>">
                            <div class="message-avatar"><?php echo substr($message['expediteur_nom'], 0, 2); ?></div>
                            <div class="message-content">
                                <div class="message-sender"><?php echo htmlspecialchars($message['expediteur_nom']); ?></div>
                                <div class="message-preview"><?php echo htmlspecialchars($message['objet']); ?></div>
                                <div class="message-time"><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></div>
                            </div>
                            <div class="message-actions">
                                <button title="Répondre" data-message-id="<?php echo $message['id_message']; ?>">
                                    <i class="fas fa-reply"></i>
                                </button>
                                <button title="Archiver" data-message-id="<?php echo $message['id_message']; ?>">
                                    <i class="fas fa-archive"></i>
                                </button>
                                <button title="Supprimer" data-message-id="<?php echo $message['id_message']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-messages" style="text-align: center;">Aucun message dans votre boîte de réception</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Composer -->
    <div id="compose-modal" class="modal-overlay">
        <div class="modal compose-modal">
            <div class="modal-header">
                <h3><i class="fas fa-pen-to-square"></i> Nouveau Message</h3>
                <button class="modal-close" onclick="closeModal('compose')">&times;</button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label for="recipient">Destinataire</label>
                        <select id="recipient" name="destinataire_id" required>
                            <option value="">Choisir un destinataire...</option>
                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?php echo $contact['id_utilisateur']; ?>">
                                    <?php echo htmlspecialchars($contact['nom_complet']); ?> (<?php echo htmlspecialchars($contact['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="subject">Objet</label>
                        <input type="text" id="subject" name="objet" placeholder="Entrez l'objet du message..." required>
                    </div>

                    <div class="form-group">
                        <label for="priority">Priorité</label>
                        <select id="priority" name="priorite">
                            <option value="normale">Normale</option>
                            <option value="haute">Haute</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="contenu" placeholder="Écrivez votre message ici..." required></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('compose')">Annuler</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Archives -->
    <div id="archives-modal" class="modal-overlay">
        <div class="modal archives-modal">
            <div class="modal-header">
                <h3><i class="fas fa-box-archive"></i> Messages Archivés</h3>
                <button class="modal-close" onclick="closeModal('archives')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="archive-filters">
                    <div class="filter-group">
                        <label>Rechercher</label>
                        <input type="text" placeholder="Mots-clés...">
                    </div>
                    <div class="filter-group">
                        <label>Date de</label>
                        <input type="date">
                    </div>
                    <div class="filter-group">
                        <label>Date à</label>
                        <input type="date">
                    </div>
                    <div class="filter-group">
                        <label>Expéditeur</label>
                        <select>
                            <option value="">Tous</option>
                            <?php foreach ($contacts as $contact): ?>
                                <option value="<?php echo $contact['id_utilisateur']; ?>">
                                    <?php echo htmlspecialchars($contact['nom_complet']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if ($messagesArchives): ?>
                    <?php foreach ($messagesArchives as $message): ?>
                        <div class="message-item">
                            <div class="message-avatar"><?php echo substr($message['expediteur_nom'], 0, 2); ?></div>
                            <div class="message-content">
                                <div class="message-sender"><?php echo htmlspecialchars($message['expediteur_nom']); ?></div>
                                <div class="message-preview"><?php echo htmlspecialchars($message['objet']); ?></div>
                                <div class="message-time"><?php echo date('d/m/Y H:i', strtotime($message['date_envoi'])); ?></div>
                            </div>
                            <div class="message-actions">
                                <button title="Restaurer" data-message-id="<?php echo $message['id_message']; ?>">
                                    <i class="fas fa-undo"></i>
                                </button>
                                <button title="Supprimer définitivement" data-message-id="<?php echo $message['id_message']; ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-messages">Aucun message archivé</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Notifications -->
    <div id="notifications-modal" class="modal-overlay">
        <div class="modal notifications-modal">
            <div class="modal-header">
                <h3><i class="fas fa-bell"></i> Paramètres de Notification</h3>
                <button class="modal-close" onclick="closeModal('notifications')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="notification-setting">
                    <div class="notification-info">
                        <h4>Nouveaux messages</h4>
                        <p>Recevoir une notification pour chaque nouveau message</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="notification-setting">
                    <div class="notification-info">
                        <h4>Messages prioritaires</h4>
                        <p>Notification spéciale pour les messages marqués comme prioritaires</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="notification-setting">
                    <div class="notification-info">
                        <h4>Résumé quotidien</h4>
                        <p>Recevoir un résumé des messages de la journée</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="notification-setting">
                    <div class="notification-info">
                        <h4>Notifications par email</h4>
                        <p>Recevoir les notifications sur votre adresse email</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="notification-setting">
                    <div class="notification-info">
                        <h4>Son de notification</h4>
                        <p>Jouer un son lors de l'arrivée d'un nouveau message</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="notification-setting">
                    <div class="notification-info">
                        <h4>Mode Ne pas déranger</h4>
                        <p>Désactiver toutes les notifications entre 22h et 8h</p>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-primary">
                        <i class="fas fa-save"></i> Sauvegarder
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Réponse -->
    <div id="reply-modal" class="modal-overlay">
        <div class="modal reply-modal">
            <div class="modal-header">
                <h3><i class="fas fa-reply"></i> Répondre au message</h3>
                <button class="modal-close" onclick="closeModal('reply')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="message-info">
                    <div class="message-header">
                        <div class="message-meta">
                            <div><strong>De :</strong> <span id="reply-sender"></span></div>
                            <div><strong>Objet :</strong> <span id="reply-subject"></span></div>
                            <div><strong>Date :</strong> <span id="reply-date"></span></div>
                        </div>
                    </div>
                    <div class="message-divider"></div>
                    <div class="message-content">
                        <h4>Message original :</h4>
                        <div class="message-body" id="reply-original-content"></div>
                    </div>
                </div>

                <form id="reply-form" method="POST" action="assets/traitements/send_reply.php">
                    <input type="hidden" name="message_id" id="reply-message-id">
                    <input type="hidden" name="destinataire_id" id="reply-destinataire-id">

                    <div class="form-group">
                        <label for="reply-content">Votre réponse</label>
                        <textarea id="reply-content" name="contenu" class="form-control" rows="6" placeholder="Rédigez votre réponse ici..." required></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal('reply')">Annuler</button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane"></i> Envoyer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmation -->
    <div id="confirmation-modal" class="modal-overlay">
        <div class="modal confirmation-modal">
            <div class="modal-header">
                <h3><i class="fas fa-check-circle"></i> Confirmation</h3>
                <button class="modal-close" onclick="closeModal('confirmation')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirmation-message">
                    <p id="confirmation-text"></p>
                </div>
                <div class="form-actions">
                    <button class="btn-primary" onclick="closeModal('confirmation')">
                        <i class="fas fa-check"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmation de Suppression -->
    <div id="delete-confirmation-modal" class="modal-overlay">
        <div class="modal confirmation-modal">
            <div class="modal-header">
                <h3><i class="fas fa-trash-alt"></i> Confirmer la suppression</h3>
                <button class="modal-close" onclick="closeModal('delete-confirmation')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="confirmation-message">
                    <p>Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible.</p>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('delete-confirmation')">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="button" class="btn-primary" id="confirm-delete-btn" style="background-color: #e74c3c;">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonctions de base pour les modales
        function openModal(modalName) {
            const modal = document.getElementById(modalName + '-modal');
            if (modal) {
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
                console.log('Modal ouverte:', modalName); // Debug
            } else {
                console.error('Modal non trouvée:', modalName + '-modal');
            }
        }

        function closeModal(modalName) {
            const modal = document.getElementById(modalName + '-modal');
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = 'auto';
                console.log('Modal fermée:', modalName); // Debug
            }
        }

        // Attacher les événements aux boutons au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM chargé, initialisation des événements'); // Debug

            // Gestion des boutons d'ouverture de modale avec onclick déjà défini dans le HTML
            // Ces boutons ont déjà onclick="openModal('inbox')" etc. dans le HTML

            // Vérification que les fonctions sont bien disponibles globalement
            window.openModal = openModal;
            window.closeModal = closeModal;

            // Gestion alternative au cas où les onclick ne fonctionnent pas
            const actionCards = document.querySelectorAll('.action-card button');
            actionCards.forEach((button, index) => {
                button.addEventListener('click', function(e) {
                    console.log('Bouton cliqué:', index); // Debug
                    switch (index) {
                        case 0: // Boîte de Réception
                            openModal('inbox');
                            break;
                        case 1: // Nouveau Message
                            openModal('compose');
                            break;
                        case 2: // Messages Archivés
                            openModal('archives');
                            break;
                        case 3: // Notifications
                            openModal('notifications');
                            break;
                    }
                });
            });

            // Gestion du formulaire de composition
            const composeForm = document.querySelector('#compose-modal form');
            if (composeForm) {
                composeForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;

                    try {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';

                        const response = await fetch('assets/traitements/send_message.php', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Erreur HTTP: ${response.status}`);
                        }

                        const result = await response.json();
                        console.log('Résultat de l\'envoi:', result);

                        if (result.success) {
                            closeModal('compose');
                            this.reset();
                            showConfirmationModal('Votre message a bien été envoyé !');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            throw new Error(result.message || 'Erreur lors de l\'envoi du message');
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        showConfirmationModal(error.message || 'Erreur lors de l\'envoi du message');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                });
            }

            // Gestion des actions sur les messages
            document.querySelectorAll('.message-actions button').forEach(button => {
                button.addEventListener('click', async function(e) {
                    e.stopPropagation();
                    const messageId = this.dataset.messageId;
                    const action = this.title;

                    try {
                        let endpoint = '';
                        let options = {};

                        if (action.includes('Répondre')) {
                            // Gestion de la réponse
                            const response = await fetch(`assets/traitements/get_message.php?id=${messageId}`);
                            const message = await response.json();

                            // Remplir les champs de la modale de réponse
                            document.getElementById('reply-sender').textContent = message.expediteur_nom;
                            document.getElementById('reply-subject').textContent = message.objet;
                            document.getElementById('reply-date').textContent = new Date(message.date_envoi).toLocaleString();
                            document.getElementById('reply-original-content').innerHTML = message.contenu;
                            document.getElementById('reply-message-id').value = message.id_message;
                            document.getElementById('reply-destinataire-id').value = message.expediteur_id;

                            openModal('reply');
                            return;
                        } else if (action.includes('Archiver')) {
                            endpoint = 'assets/traitements/archive_messages.php';
                            options = {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    messageIds: [messageId]
                                })
                            };
                        } else if (action.includes('Supprimer')) {
                            const deleteModal = document.getElementById('delete-confirmation-modal');
                            if (deleteModal) {
                                deleteModal.dataset.messageId = messageId;
                                openModal('delete-confirmation');
                            }
                            return; // Arrêter l'exécution ici, la suite se fait dans la modale
                        } else if (action.includes('Restaurer')) {
                            // NOTE: Le script pour restaurer n'est pas défini, l'action échouera probablement.
                            endpoint = 'assets/traitements/restore_messages.php';
                            options = {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    messageIds: [messageId]
                                })
                            };
                        }

                        if (!endpoint) return;

                        const response = await fetch(endpoint, options);

                        if (!response.ok) {
                            throw new Error(`Erreur HTTP: ${response.status}`);
                        }

                        const result = await response.json();
                        console.log('Résultat de l\'action:', result);

                        if (result.success) {
                            const messageItem = this.closest('.message-item');
                            messageItem.style.animation = 'slideOut 0.3s ease forwards';
                            setTimeout(() => messageItem.remove(), 300);
                            showConfirmationModal('Action effectuée avec succès');
                        } else {
                            throw new Error(result.message || 'Erreur lors de l\'action');
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        showConfirmationModal(error.message || 'Erreur lors de l\'action');
                    }
                });
            });

            // Gestion des clics sur les messages
            // Dans votre fichier principal, remplacez la section de gestion des clics sur les messages par :

            // Gestion des clics sur les messages
            document.querySelectorAll('.message-item .message-content').forEach(content => {
                content.addEventListener('click', async function(e) {
                    // Éviter de déclencher l'événement si on clique sur un bouton d'action
                    if (e.target.closest('.message-actions')) {
                        return;
                    }

                    const messageItem = this.closest('.message-item');
                    const messageId = messageItem.querySelector('[data-message-id]').dataset.messageId;
                    const wasUnread = messageItem.classList.contains('unread');

                    try {
                        const response = await fetch(`assets/traitements/get_message.php?id=${messageId}`);
                        const message = await response.json();

                        // Afficher le message dans une modale
                        const modal = document.createElement('div');
                        modal.className = 'modal-overlay active';
                        modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3>${message.objet}</h3>
                        <button class="modal-close" onclick="this.closest('.modal-overlay').remove(); document.body.style.overflow = 'auto';">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="message-details">
                            <p><strong>De :</strong> ${message.expediteur_nom}</p>
                            <p><strong>Date :</strong> ${new Date(message.date_envoi).toLocaleString()}</p>
                            <div class="message-content">${message.contenu}</div>
                        </div>
                    </div>
                </div>
            `;
                        document.body.appendChild(modal);
                        document.body.style.overflow = 'hidden';

                        // Si le message était non lu, retirer la classe et décrémenter le compteur
                        if (wasUnread && message.statut === 'lu') {
                            messageItem.classList.remove('unread');

                            // Mettre à jour le compteur
                            const compteurElement = document.querySelector('.action-card .fa-inbox').parentElement.querySelector('p');
                            if (compteurElement) {
                                const currentText = compteurElement.textContent;
                                const currentCount = parseInt(currentText.match(/\d+/)[0]);
                                if (!isNaN(currentCount) && currentCount > 0) {
                                    const newCount = currentCount - 1;
                                    compteurElement.textContent = newCount + " nouveau(x) message(s) non lu(s)";
                                }
                            }
                        }

                    } catch (error) {
                        console.error('Erreur lors du chargement du message:', error);
                        showConfirmationModal('Erreur lors du chargement du message');
                    }
                });
            });

            // Gestion du formulaire de réponse
            const replyForm = document.getElementById('reply-form');
            if (replyForm) {
                replyForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const submitBtn = this.querySelector('button[type="submit"]');
                    const originalBtnText = submitBtn.innerHTML;

                    try {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';

                        const response = await fetch('assets/traitements/send_reply.php', {
                            method: 'POST',
                            body: formData
                        });

                        if (!response.ok) {
                            throw new Error(`Erreur HTTP: ${response.status}`);
                        }

                        const result = await response.json();
                        console.log('Résultat de la réponse:', result);

                        if (result.success) {
                            closeModal('reply');
                            this.reset();
                            showConfirmationModal('Votre réponse a bien été envoyée !');
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            throw new Error(result.message || 'Erreur lors de l\'envoi de la réponse');
                        }
                    } catch (error) {
                        console.error('Erreur:', error);
                        showConfirmationModal(error.message || 'Erreur lors de l\'envoi de la réponse');
                    } finally {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                });
            }

            // Gestion de la confirmation de suppression
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', async function() {
                    const deleteModal = document.getElementById('delete-confirmation-modal');
                    const messageId = deleteModal.dataset.messageId;

                    if (!messageId) return;

                    try {
                        const response = await fetch('assets/traitements/delete_messages.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                messageIds: [messageId]
                            })
                        });

                        if (!response.ok) {
                            throw new Error(`Erreur HTTP: ${response.status}`);
                        }

                        const result = await response.json();
                        if (result.success) {
                            const messageItem = document.querySelector(`.message-actions button[data-message-id='${messageId}']`).closest('.message-item');
                            if (messageItem) {
                                messageItem.style.animation = 'slideOut 0.3s ease forwards';
                                setTimeout(() => messageItem.remove(), 300);
                            }
                            showConfirmationModal('Message supprimé avec succès.');
                        } else {
                            throw new Error(result.message || 'Erreur lors de la suppression.');
                        }
                    } catch (error) {
                        showConfirmationModal(error.message);
                    } finally {
                        closeModal('delete-confirmation');
                        delete deleteModal.dataset.messageId;
                    }
                });
            }
        });

        // Fonction pour afficher la modale de confirmation
        function showConfirmationModal(message) {
            document.getElementById('confirmation-text').textContent = message;
            openModal('confirmation');
        }

        // Rendre les fonctions globales pour les onclick dans le HTML
        window.openModal = openModal;
        window.closeModal = closeModal;
        window.showConfirmationModal = showConfirmationModal;

        // Animation de sortie
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
            
            @keyframes modalAppear {
                from {
                    opacity: 0;
                    transform: scale(0.9);
                }
                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>

</html>