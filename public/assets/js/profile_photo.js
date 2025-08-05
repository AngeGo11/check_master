document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la mise à jour de photo de profil
    const photoForm = document.getElementById('photo-form');
    if (photoForm) {
        photoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('validate_photo', '1');
            
            // Afficher un indicateur de chargement
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Mise à jour...';
            submitBtn.disabled = true;
            
            fetch('/GSCV+/public/assets/traitements/ajax_profile_photo.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Afficher le message de succès
                    showMessage(data.message, 'success');
                    
                    // Recharger la page pour afficher la nouvelle photo
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showMessage(data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur lors de la mise à jour de la photo', 'error');
            })
            .finally(() => {
                // Restaurer le bouton
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
    
    // Gestion de la suppression de photo de profil
    const deletePhotoBtn = document.getElementById('delete-photo');
    if (deletePhotoBtn) {
        deletePhotoBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?')) {
                const formData = new FormData();
                formData.append('delete', '1');
                
                // Afficher un indicateur de chargement
                const originalText = this.textContent;
                this.textContent = 'Suppression...';
                this.disabled = true;
                
                fetch('/GSCV+/public/assets/traitements/ajax_profile_photo.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        
                        // Recharger la page pour afficher la photo par défaut
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showMessage(data.error, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showMessage('Erreur lors de la suppression de la photo', 'error');
                })
                .finally(() => {
                    // Restaurer le bouton
                    this.textContent = originalText;
                    this.disabled = false;
                });
            }
        });
    }
    
    // Gestion de la prévisualisation de l'image
    const photoInput = document.getElementById('change');
    const previewImg = document.getElementById('photo-preview');
    
    if (photoInput && previewImg) {
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                // Vérifier le type de fichier
                if (!file.type.match('image.*')) {
                    showMessage('Veuillez sélectionner une image valide', 'error');
                    this.value = '';
                    return;
                }
                
                // Vérifier la taille (5MB max)
                if (file.size > 5 * 1024 * 1024) {
                    showMessage('L\'image ne doit pas dépasser 5MB', 'error');
                    this.value = '';
                    return;
                }
                
                // Afficher la prévisualisation
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Fonction pour afficher les messages
    function showMessage(message, type) {
        // Supprimer les messages existants
        const existingMessages = document.querySelectorAll('.alert');
        existingMessages.forEach(msg => msg.remove());
        
        // Créer le nouveau message
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insérer le message au début du conteneur principal
        const container = document.querySelector('.container') || document.body;
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}); 