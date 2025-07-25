// Gestion de la modale de contact - VERSION CORRIGÉE
document.addEventListener('DOMContentLoaded', function() {
    // Sélection des éléments modaux
    const contactModal = document.getElementById('contact-modal');
    const viewMessageModal = document.getElementById('view-message-modal');
    const replyMessageModal = document.getElementById('reply-message-modal');
    const viewReminderModal = document.getElementById('view-reminder-modal');
    const confirmationModal = document.getElementById('confirmation-modal');

    // Sélection des boutons
    const contactButtons = document.querySelectorAll('.button[href*="action=contact"]');
    const replyButtons = document.querySelectorAll('.reply-btn');
    const viewButtons = document.querySelectorAll('.view-btn');
    const viewReminderBtn = document.getElementById('view-reminder-btn');

    console.log('Modales sélectionnées:', {
        contactModal,
        viewMessageModal,
        replyMessageModal,
        viewReminderModal,
        confirmationModal
    });

    console.log('Boutons sélectionnés:', {
        contactButtons,
        replyButtons,
        viewButtons,
        viewReminderBtn
    });

    // Variable globale pour stocker l'ID du message actuel
    let currentMessageId = null;

    // Fonction globale pour charger les détails du message pour la réponse
    async function loadMessageForReply(messageId) {
        try {
            console.log('Chargement du message pour réponse:', messageId);
            console.log('Type de messageId:', typeof messageId);
            
            // Vérifier que l'ID est valide avec une validation plus robuste
            if (!messageId || messageId === 'null' || messageId === 'undefined' || messageId === null || messageId === undefined) {
                console.error('ID du message invalide:', messageId);
                throw new Error('ID du message invalide');
            }
            
            // Convertir en string si c'est un nombre
            const messageIdStr = String(messageId).trim();
            if (!messageIdStr || messageIdStr === '' || messageIdStr === '0') {
                console.error('ID du message vide ou invalide:', messageIdStr);
                throw new Error('ID du message invalide');
            }
            
            const response = await fetch(`./assets/traitements/get_message.php?id=${encodeURIComponent(messageIdStr)}`);
            console.log('Statut de la réponse:', response.status);
            
            if (!response.ok) {
                let errorMessage = 'Erreur lors du chargement du message';
                try {
                    const errorData = await response.json();
                    errorMessage = errorData.message || errorMessage;
                } catch (e) {
                    // Si impossible de parser le JSON d'erreur, garder le message par défaut
                }
                throw new Error(errorMessage);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new TypeError("La réponse n'est pas au format JSON");
            }

            const message = await response.json();
            console.log('Message reçu pour réponse:', message);
            
            // Remplir les champs de la modale
            document.getElementById('reply-sender').textContent = message.expediteur_nom || 'Inconnu';
            document.getElementById('reply-email').textContent = message.expediteur_email || 'Non disponible';
            document.getElementById('reply-subject').textContent = message.objet || 'Sans objet';
            document.getElementById('reply-date').textContent = message.date_envoi || 'Date inconnue';
            document.getElementById('reply-original-content').innerHTML = message.contenu || 'Contenu vide';
            document.getElementById('reply-message-id').value = message.id_message;
            document.getElementById('reply-destinataire-id').value = message.expediteur_id;

            // Afficher la modale
            replyMessageModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        } catch (error) {
            console.error('Erreur lors du chargement pour réponse:', error);
            alert(error.message || 'Erreur lors du chargement du message');
        }
    }

    // Fonction pour afficher la modale de confirmation
    function showConfirmationModal(message) {
        const confirmationText = document.getElementById('confirmation-text');
        confirmationText.textContent = message;
        confirmationModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    // Gestion de la modale de contact
    if (contactModal) {
        const closeModalBtn = contactModal.querySelector('.close-modal-btn');
        const cancelBtn = contactModal.querySelector('.cancel-btn');
        const contactForm = contactModal.querySelector('#contact-form');

        console.log('Formulaire trouvé:', contactForm);
        if (contactForm) {
            console.log('Action du formulaire:', contactForm.action);
            
            contactForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                console.log('Formulaire soumis via JavaScript');

                const formData = new FormData(contactForm);
                const destinataireId = formData.get('destinataire_id');
                const objet = formData.get('objet');
                const contenu = formData.get('contenu');

                console.log('Données du formulaire:', {
                    destinataire_id: destinataireId,
                    objet: objet,
                    contenu: contenu
                });

                if (!destinataireId || !objet || !contenu) {
                    alert('Veuillez remplir tous les champs requis');
                    return;
                }

                const submitBtn = contactForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';

                try {
                    console.log('Envoi de la requête POST à:', contactForm.action);
                    const response = await fetch(contactForm.action, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    console.log('Statut de la réponse:', response.status);
                    console.log('Type de contenu:', response.headers.get('content-type'));

                    if (!response.ok) {
                        throw new Error(`Erreur HTTP: ${response.status}`);
                    }

                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new TypeError("La réponse n'est pas au format JSON");
                    }

                    const result = await response.json();
                    console.log('Résultat:', result);

                    if (result.success) {
                        contactModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        contactForm.reset();
                        showConfirmationModal(result.message || 'Votre message a bien été envoyé !');
                    } else {
                        showConfirmationModal(result.message || 'Erreur lors de l\'envoi du message');
                    }
                } catch (error) {
                    console.error('Erreur lors de l\'envoi:', error);
                    showConfirmationModal('Erreur lors de l\'envoi du message. Veuillez réessayer.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        }

        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                contactModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                contactModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }
    }

    // Gestion de la modale de vue de message
    if (viewMessageModal) {
        const closeViewBtn = viewMessageModal.querySelector('#close-view-btn');
        const closeViewModalBtn = viewMessageModal.querySelector('#close-view-modal-btn');
        const newReplyBtn = viewMessageModal.querySelector('#new-reply-btn');

        // Fonction pour charger les détails du message
        async function loadMessageDetails(messageId) {
            try {
                console.log('Chargement des détails du message:', messageId);
                console.log('Type de messageId:', typeof messageId);
                // Vérifier que l'ID est valide
                if (!messageId || messageId === 'null' || messageId === 'undefined') {
                    throw new Error('ID du message invalide');
                }
                // Stocker l'ID globalement de manière plus sûre
                currentMessageId = String(messageId).trim();
                console.log('ID stocké globalement:', currentMessageId);
                const response = await fetch(`./assets/traitements/get_message.php?id=${encodeURIComponent(currentMessageId)}`);
                if (!response.ok) throw new Error('Erreur lors du chargement du message');
                const message = await response.json();
                console.log('Message reçu:', message);
                // Remplir les champs de la modale
                document.getElementById('view-sender').textContent = message.expediteur_nom || 'Inconnu';
                document.getElementById('view-email').textContent = message.expediteur_email || 'Email inconnu';
                document.getElementById('view-subject').textContent = message.objet || 'Sans objet';
                document.getElementById('view-date').textContent = message.date_envoi || 'Date inconnue';
                document.getElementById('view-content').innerHTML = message.contenu || 'Contenu vide';
                // Mettre à jour l'ID du message dans le bouton d'archivage
                const archiveBtn = document.getElementById('close-archives-btn');
                if (archiveBtn) {
                    archiveBtn.dataset.messageId = currentMessageId;
                }
                // Si le message était non lu, retirer la classe et décrémenter le compteur
                const row = document.querySelector(`.message-checkbox[data-message-id='${currentMessageId}']`)?.closest('tr');
                if (row && row.classList.contains('unread')) {
                    row.classList.remove('unread');
                    // Mettre à jour le compteur (si présent dans la page)
                    const compteur = document.querySelector('.card-header h2, .action-card .fa-inbox')?.parentElement?.querySelector('p');
                    if (compteur) {
                        let nb = parseInt(compteur.textContent);
                        if (!isNaN(nb) && nb > 0) {
                            compteur.textContent = (nb - 1) + " nouveau(x) message(s) non lu(s)";
                        }
                    }
                }
                // Afficher la modale
                viewMessageModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors du chargement du message');
            }
        }

        // Gestionnaire d'événements pour les boutons de vue
        viewButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault(); // Empêcher le comportement par défaut
                const messageId = button.dataset.messageId || button.getAttribute('data-message-id');
                console.log('Bouton vue cliqué, messageId:', messageId);
                console.log('Élément bouton:', button);
                console.log('Dataset complet:', button.dataset);
                loadMessageDetails(messageId);
            });
        });

        // Gestionnaire pour le bouton "Nouvelle réponse"
        if (newReplyBtn) {
            newReplyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Empêcher la propagation de l'événement
                console.log('Bouton de réponse cliqué');
                console.log('ID du message actuel stocké:', currentMessageId);
                
                if (!currentMessageId || currentMessageId === 'null' || currentMessageId === 'undefined') {
                    console.error('ID du message non trouvé');
                    alert('Erreur: Impossible de répondre à ce message');
                    return;
                }
                
                // Fermer la modale de vue
                viewMessageModal.style.display = 'none';
                
                // Charger le message pour la réponse
                loadMessageForReply(currentMessageId);
            });
        }

        // Gestionnaire d'événements pour les boutons de réponse directs
        const directReplyButtons = document.querySelectorAll('.reply-btn:not(#new-reply-btn)');
        directReplyButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Empêcher la propagation de l'événement
                const messageId = button.dataset.messageId || button.getAttribute('data-message-id');
                console.log('Bouton réponse directe cliqué, messageId:', messageId);
                loadMessageForReply(messageId);
            });
        });

        // Gestionnaires d'événements pour les boutons de fermeture
        if (closeViewBtn) {
            closeViewBtn.addEventListener('click', () => {
                viewMessageModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                currentMessageId = null; // Réinitialiser l'ID
            });
        }

        if (closeViewModalBtn) {
            closeViewModalBtn.addEventListener('click', () => {
                viewMessageModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                currentMessageId = null; // Réinitialiser l'ID
            });
        }
    }

    // Gestion de la modale de réponse
    if (replyMessageModal) {
        const closeReplyBtn = replyMessageModal.querySelector('#close-reply-modal-btn');
        const cancelReplyBtn = replyMessageModal.querySelector('#cancel-reply-btn');
        const replyForm = replyMessageModal.querySelector('#reply-form');

        // Gestionnaire pour le formulaire de réponse
        if (replyForm) {
            replyForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                console.log('Soumission du formulaire de réponse');
                
                const submitBtn = replyForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
                
                try {
                    const formData = new FormData(replyForm);
                    const messageId = formData.get('message_id');
                    const destinataireId = formData.get('destinataire_id');
                    const contenu = formData.get('contenu');
                    
                    console.log('Données du formulaire:', {
                        message_id: messageId,
                        destinataire_id: destinataireId,
                        contenu: contenu
                    });

                    if (!messageId || !destinataireId || !contenu) {
                        showConfirmationModal('Tous les champs sont requis');
                        return;
                    }

                    const response = await fetch('./assets/traitements/send_reply.php', {
                        method: 'POST',
                        body: formData
                    });

                    console.log('Réponse du serveur:', response.status);
                    
                    if (!response.ok) {
                        let errorMessage = 'Erreur lors de l\'envoi de la réponse';
                        try {
                            const errorData = await response.json();
                            errorMessage = errorData.message || errorMessage;
                        } catch (e) {
                            // Si impossible de parser le JSON d'erreur
                        }
                        showConfirmationModal(errorMessage);
                        return;
                    }
                    
                    const result = await response.json();
                    console.log('Résultat:', result);

                    if (result.success) {
                        replyMessageModal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        replyForm.reset();
                        showConfirmationModal('Votre réponse a bien été envoyée !');
                    } else {
                        showConfirmationModal(result.message || 'Erreur lors de l\'envoi de la réponse');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    showConfirmationModal('Erreur lors de l\'envoi de la réponse. Veuillez réessayer.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        }

        // Gestionnaires d'événements pour les boutons de fermeture
        if (closeReplyBtn) {
            closeReplyBtn.addEventListener('click', () => {
                replyMessageModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }

        if (cancelReplyBtn) {
            cancelReplyBtn.addEventListener('click', () => {
                replyMessageModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }
    }

    // Gestionnaire pour la modale de confirmation
    if (confirmationModal) {
        const closeConfirmationBtn = confirmationModal.querySelector('#close-confirmation-modal-btn');
        const confirmBtn = confirmationModal.querySelector('#confirm-btn');

        if (closeConfirmationBtn) {
            closeConfirmationBtn.addEventListener('click', () => {
                confirmationModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            });
        }

        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                confirmationModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                window.location.reload();
            });
        }

        // Fermer la modale en cliquant en dehors
        confirmationModal.addEventListener('click', (e) => {
            if (e.target === confirmationModal) {
                confirmationModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }

    // Fermer les modales en cliquant en dehors du contenu
    [contactModal, viewMessageModal, replyMessageModal, viewReminderModal, confirmationModal].forEach(modal => {
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    // Réinitialiser l'ID si c'est la modale de vue
                    if (modal === viewMessageModal) {
                        currentMessageId = null;
                    }
                }
            });
        }
    });

    // Gestion de la sélection en masse
    const selectAllCheckbox = document.getElementById('select-all-messages');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('#tab-inbox-content .message-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Gestion de la suppression en masse
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('#tab-inbox-content .message-checkbox:checked');
            const messageIds = Array.from(checkedBoxes).map(cb => cb.dataset.messageId);

            if (messageIds.length === 0) {
                alert('Veuillez sélectionner au moins un message à supprimer.');
                return;
            }

            if (confirm(`Voulez-vous vraiment supprimer les ${messageIds.length} messages sélectionnés ?`)) {
                fetch('assets/traitements/supprimer_messages.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'message_ids=' + JSON.stringify(messageIds)
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message || data.error);
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => console.error('Erreur:', error));
            }
        });
    }
});

// Gestion de la sélection multiple
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-messages');
    const messageCheckboxes = document.querySelectorAll('.message-checkbox');
    const bulkActions = document.querySelector('.bulk-actions');
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    const bulkArchiveBtn = document.getElementById('bulk-archive-btn');
    const bulkActionModal = document.getElementById('bulk-action-modal');
    const confirmBulkActionBtn = document.getElementById('confirm-bulk-action-btn');
    const cancelBulkActionBtn = document.getElementById('cancel-bulk-action-btn');
    const closeBulkModalBtn = document.getElementById('close-bulk-modal-btn');
    const bulkConfirmationText = document.getElementById('bulk-confirmation-text');

    let currentBulkAction = null;

    // Gestion de la case "Tout sélectionner"
    selectAllCheckbox.addEventListener('change', function() {
        messageCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
            updateRowSelection(checkbox);
        });
        
    });

    // Gestion des cases individuelles
    messageCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateRowSelection(this);
            
            updateSelectAllCheckbox();
        });
    });

    // Mise à jour de l'apparence des lignes sélectionnées
    function updateRowSelection(checkbox) {
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    }

    // Mise à jour de la visibilité des boutons d'action en masse
    function updateBulkActionsVisibility() {
        const hasSelectedMessages = Array.from(messageCheckboxes).some(checkbox => checkbox.checked);
        bulkActions.style.display = hasSelectedMessages ? 'flex' : 'none';
    }

    // Mise à jour de l'état de la case "Tout sélectionner"
    function updateSelectAllCheckbox() {
        const allChecked = Array.from(messageCheckboxes).every(checkbox => checkbox.checked);
        const someChecked = Array.from(messageCheckboxes).some(checkbox => checkbox.checked);
        selectAllCheckbox.checked = allChecked;
        selectAllCheckbox.indeterminate = someChecked && !allChecked;
    }

    // Gestion des actions en masse
    bulkDeleteBtn.addEventListener('click', () => {
        currentBulkAction = 'delete';
        bulkConfirmationText.textContent = 'Êtes-vous sûr de vouloir supprimer les messages sélectionnés ?';
        bulkActionModal.style.display = 'flex';
    });

    bulkArchiveBtn.addEventListener('click', () => {
        currentBulkAction = 'archive';
        bulkConfirmationText.textContent = 'Êtes-vous sûr de vouloir archiver les messages sélectionnés ?';
        bulkActionModal.style.display = 'flex';
    });

    // Fermeture de la modale
    function closeBulkModal() {
        bulkActionModal.style.display = 'none';
        currentBulkAction = null;
    }

    closeBulkModalBtn.addEventListener('click', closeBulkModal);
    cancelBulkActionBtn.addEventListener('click', closeBulkModal);

    // Confirmation de l'action en masse
    confirmBulkActionBtn.addEventListener('click', function() {
        const selectedIds = Array.from(messageCheckboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.dataset.messageId);

        if (selectedIds.length === 0) return;

        const action = currentBulkAction;
        const url = action === 'delete' ? 'assets/traitements/delete_messages.php' : 'assets/traitements/archive_messages.php';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ messageIds: selectedIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le statut des messages dans le tableau
                selectedIds.forEach(id => {
                    const row = document.querySelector(`tr[data-message-id="${id}"]`);
                    if (row) {
                        const statusCell = row.querySelector('.col-status');
                        if (statusCell) {
                            const newStatus = action === 'delete' ? 'supprimé' : 'archivé';
                            statusCell.innerHTML = `
                                <span class="status-pill status-${newStatus}">
                                    ${newStatus.charAt(0).toUpperCase() + newStatus.slice(1)}
                                </span>
                            `;
                        }
                    }
                });
                // Réinitialiser la sélection
                selectAllCheckbox.checked = false;
                messageCheckboxes.forEach(checkbox => checkbox.checked = false);
                
                // Afficher un message de succès
                showConfirmation(data.message || 'Action effectuée avec succès');
            } else {
                showError(data.message || 'Une erreur est survenue');
            }
        })
        .catch(error => {
            showError('Une erreur est survenue lors de l\'exécution de l\'action');
        })
        .finally(() => {
            closeBulkModal();
        });
    });
});

// Fonction pour afficher les messages de confirmation/erreur
function showConfirmation(message) {
    const confirmationModal = document.getElementById('confirmation-modal');
    const confirmationText = document.getElementById('confirmation-text');
    confirmationText.textContent = message;
    confirmationModal.style.display = 'flex';
}

function showError(message) {
    // Implémenter l'affichage des erreurs selon votre design
    alert(message);
}

// Gestion des modales de confirmation
const deleteModal = document.getElementById('delete-confirmation-modal');
const archiveModal = document.getElementById('archive-confirmation-modal');
const successModal = document.getElementById('success-modal');

// Fonction pour ouvrir une modale
function openModal(modal) {
    modal.style.display = 'flex';
}

// Fonction pour fermer une modale
function closeModal(modal) {
    modal.style.display = 'none';
}

// Gestionnaires d'événements pour les boutons de fermeture
document.querySelectorAll('.close-modal-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = btn.closest('.modal');
        closeModal(modal);
    });
});

// Gestionnaires d'événements pour les boutons d'action en masse
document.getElementById('bulk-delete-btn').addEventListener('click', () => {
    const selectedMessages = getSelectedMessages();
    if (selectedMessages.length > 0) {
        openModal(deleteModal);
    }
});

document.getElementById('bulk-archive-btn').addEventListener('click', () => {
    const selectedMessages = getSelectedMessages();
    if (selectedMessages.length > 0) {
        openModal(archiveModal);
    }
});

// Gestionnaires d'événements pour les boutons d'action individuels
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const messageId = btn.dataset.messageId;
        openModal(deleteModal);
        // Stocker l'ID du message pour l'action individuelle
        deleteModal.dataset.messageId = messageId;
    });
});

document.querySelectorAll('.archives-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const messageId = btn.dataset.messageId;
        console.log('Archivage individuel - ID du message:', messageId);
        if (!messageId) {
            console.error('ID du message non trouvé sur le bouton d\'archivage');
            return;
        }
        openModal(archiveModal);
        archiveModal.dataset.messageId = messageId;
    });
});

// Gestionnaires d'événements pour les boutons de confirmation
document.getElementById('delete-confirm-btn').addEventListener('click', async () => {
    const messageId = deleteModal.dataset.messageId;
    const messageIds = messageId ? [messageId] : getSelectedMessages();
    
    if (messageIds.length > 0) {
        try {
            const response = await fetch('assets/traitements/delete_messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ messageIds })
            });

            const data = await response.json();
            if (data.success) {
                updateMessageStatus(messageIds, 'supprimé');
                showSuccessMessage(data.message);
            } else {
                showErrorMessage(data.message);
            }
        } catch (error) {
            showErrorMessage('Une erreur est survenue lors de la suppression des messages.');
        }
        closeModal(deleteModal);
        // Réinitialiser l'ID stocké
        delete deleteModal.dataset.messageId;
    }
});

document.getElementById('archive-confirm-btn').addEventListener('click', async () => {
    const messageId = archiveModal.dataset.messageId;
    console.log('Confirmation d\'archivage - ID du message:', messageId);
    const messageIds = messageId ? [messageId] : getSelectedMessages();
    
    if (messageIds.length > 0) {
        try {
            console.log('Envoi de la requête d\'archivage avec les IDs:', messageIds);
            const response = await fetch('assets/traitements/archive_messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ messageIds })
            });

            const data = await response.json();
            console.log('Réponse du serveur:', data);
            
            if (data.success) {
                updateMessageStatus(messageIds, 'archivé');
                showSuccessMessage(data.message);
            } else {
                showErrorMessage(data.message || 'Erreur lors de l\'archivage du message');
            }
        } catch (error) {
            console.error('Erreur lors de l\'archivage:', error);
            showErrorMessage('Une erreur est survenue lors de l\'archivage du message');
        }
        closeModal(archiveModal);
        delete archiveModal.dataset.messageId;
    }
});

// Fonction pour obtenir les IDs des messages sélectionnés
function getSelectedMessages() {
    const checkboxes = document.querySelectorAll('.message-checkbox:checked');
    return Array.from(checkboxes).map(checkbox => checkbox.dataset.messageId);
}

// Fonction pour mettre à jour le statut des messages
function updateMessageStatus(messageIds, newStatus) {
    messageIds.forEach(id => {
        const row = document.querySelector(`tr[data-message-id="${id}"]`);
        if (row) {
            const statusCell = row.querySelector('.status-cell');
            if (statusCell) {
                statusCell.innerHTML = `<span class="status-pill status-${newStatus}">${newStatus}</span>`;
            }
        }
    });
    // Réinitialiser les sélections
    document.querySelectorAll('.message-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Fonction pour afficher un message de succès
function showSuccessMessage(message) {
    const successMessage = document.querySelector('#success-modal .confirmation-message p');
    successMessage.textContent = message;
    openModal(successModal);
    setTimeout(() => closeModal(successModal), 3000);
}

// Fonction pour afficher un message d'erreur
function showErrorMessage(message) {
    alert(message);
}

// Gestionnaire d'événements pour la sélection/désélection de tous les messages
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.message-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Gestionnaires d'événements pour les cases à cocher individuelles
document.querySelectorAll('.message-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        updateSelectAllCheckbox();
    });
});

// Initialiser la visibilité des boutons d'action en masse
