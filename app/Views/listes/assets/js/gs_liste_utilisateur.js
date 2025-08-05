// Gestionnaire d'événements pour le chargement du document
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des gestionnaires d'événements
    initializeModalHandlers();
    initializeCheckboxHandlers();
});

// Initialisation des gestionnaires de modales
function initializeModalHandlers() {
    // Gestionnaire pour fermer les modales en cliquant en dehors
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal(event.target.id);
        }
    };

    // Empêcher la propagation des clics dans les modales
    document.addEventListener('click', function(event) {
        if (event.target.closest('.modal-content')) {
            event.stopPropagation();
        }
    });
}

// Initialisation des gestionnaires de checkboxes
function initializeCheckboxHandlers() {
    const selectAllCheckbox = document.getElementById('selectAllInactiveUsers');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.inactive-user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }
}

// Fonction générique pour ouvrir une modale
function showModal(modalId) {
    console.log('Opening modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    } else {
        console.error('Modal not found:', modalId);
    }
}

// Fonction générique pour fermer une modale
function closeModal(modalId) {
    console.log('Closing modal:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        if (modalId === 'viewUserModal' || modalId === 'editUserModal') {
            window.location.href = '?page=liste_utilisateurs';
        }
    }
}

// Fonctions spécifiques pour chaque type de modale
function showAddModal() {
    showModal('addUserModal');
}

function showMultipleAssignmentModal() {
    showModal('multipleAssignmentModal');
}

function showViewModal(id) {
    showModal('viewUserModal');
}

function showEditModal(id) {
    showModal('editUserModal');
}

function showDesactivateModal(id) {
    showModal('desactivateUserModal');
}

// Fonction pour ouvrir la modale d'affectation multiple
function showMultipleAssignmentModal() {
    console.log('Attempting to open multiple assignment modal');
    const modal = document.getElementById('multipleAssignmentModal');
    if (modal) {
        modal.style.display = 'flex';
        modal.classList.add('show');
        console.log('Modal should be visible now');
    } else {
        console.error('Modal element not found');
    }
}

// Fonction pour fermer la modale
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show');
    }
}

// Fermer la modale si on clique en dehors
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        event.target.classList.remove('show');
    }
}

// Gestionnaire pour "Tout sélectionner"
document.getElementById('selectAllInactiveUsers').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.inactive-user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Fonctions pour la gestion des modals
function showAddModal() {
    document.getElementById('addUserModal').style.display = 'block';
}

function deleteUser(id) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUserModal').style.display = 'block';
}

// Gestion des cases à cocher
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.getElementsByClassName('user-checkbox');
    for (let checkbox of checkboxes) {
        checkbox.checked = this.checked;
    }
    updateGeneratePasswordsButton();
});

document.querySelectorAll('.user-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateGeneratePasswordsButton);
});

function updateGeneratePasswordsButton() {
    const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
    document.getElementById('generatePasswordsBtn').style.display = checkedBoxes.length > 0 ? 'inline-block' : 'none';
}

function generatePasswords() {
    // Récupérer les utilisateurs sélectionnés
    const selectedUsers = document.querySelectorAll('.inactive-user-checkbox:checked');
    
    if (selectedUsers.length === 0) {
        alert('Veuillez sélectionner au moins un utilisateur inactif à affecter.');
        return false;
    }
    
    // Vérifier que les champs requis sont remplis
    const typeUtilisateur = document.getElementById('mass_type_utilisateur').value;
    const groupeUtilisateur = document.getElementById('mass_groupe_utilisateur').value;
    
    if (!typeUtilisateur) {
        alert('Veuillez sélectionner un type d\'utilisateur.');
        return false;
    }
    
    if (!groupeUtilisateur) {
        alert('Veuillez sélectionner un groupe d\'utilisateur.');
        return false;
    }
    
    // Confirmation pour l'attribution des accès
    if (confirm('Êtes-vous sûr de vouloir attribuer les accès et générer les mots de passe pour ' + selectedUsers.length + ' utilisateur(s) sélectionné(s) ?')) {
        // Créer un formulaire dynamique pour envoyer les données
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?liste=utilisateurs';
        
        // Ajouter l'action
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'assign_multiple';
        form.appendChild(actionInput);
        
        // Ajouter les utilisateurs sélectionnés
        selectedUsers.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_users[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });
        
        // Ajouter les paramètres de configuration
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type_utilisateur';
        typeInput.value = typeUtilisateur;
        form.appendChild(typeInput);
        
        const groupeInput = document.createElement('input');
        groupeInput.type = 'hidden';
        groupeInput.name = 'groupe_utilisateur';
        groupeInput.value = groupeUtilisateur;
        form.appendChild(groupeInput);
        
        const niveauInput = document.createElement('input');
        niveauInput.type = 'hidden';
        niveauInput.name = 'niveau_acces';
        niveauInput.value = document.getElementById('mass_niveau_acces').value;
        form.appendChild(niveauInput);
        
        // Soumettre le formulaire
        document.body.appendChild(form);
        form.submit();
    }
}

function showDesactivateModal(id) {
    document.getElementById('desactivateUserId').value = id;
    document.getElementById('desactiveUserModal').style.display = 'block';
}

function showActivateModal(id) {
    document.getElementById('activateUserId').value = id;
    document.getElementById('activateUserModal').style.display = 'block';
}

// --- JavaScript for Multiple Assignment Modal --- //

// "Tout sélectionner" functionality for inactive users
const selectAllInactiveUsersCheckbox = document.getElementById('selectAllInactiveUsers');
const inactiveUserCheckboxes = document.querySelectorAll('.inactive-user-checkbox');

if (selectAllInactiveUsersCheckbox) {
    selectAllInactiveUsersCheckbox.addEventListener('change', function() {
        inactiveUserCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });
}

// Add event listeners to individual checkboxes to update "Tout sélectionner"
inactiveUserCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        if (!this.checked && selectAllInactiveUsersCheckbox) {
            selectAllInactiveUsersCheckbox.checked = false;
        }
        // Optional: if all inactiveUserCheckboxes are checked, check selectAllInactiveUsersCheckbox
        if (selectAllInactiveUsersCheckbox && document.querySelectorAll('.inactive-user-checkbox:checked').length === inactiveUserCheckboxes.length) {
            selectAllInactiveUsersCheckbox.checked = true;
        }
    });
});

// Form validation for multiple assignment
const multipleAssignmentForm = document.getElementById('multipleAssignmentForm');
if (multipleAssignmentForm) {
    multipleAssignmentForm.addEventListener('submit', function(e) {
        const typeSelect = document.getElementById('assign_type_utilisateur');
        const selectedUsers = document.querySelectorAll('.inactive-user-checkbox:checked');

        if (typeSelect.value === "") {
            alert('Veuillez sélectionner un type d\'utilisateur à affecter.');
            e.preventDefault(); // Prevent form submission
            return false;
        }

        if (selectedUsers.length === 0) {
            alert('Veuillez sélectionner au moins un utilisateur inactif à affecter.');
            e.preventDefault(); // Prevent form submission
            return false;
        }

        // If validation passes, allow form submission
        return true;
    });
}

// Function to show multiple assignment modal
function showMultipleAssignmentModal() {
    console.log('Attempting to open multiple assignment modal'); // Added console log
    document.getElementById('multipleAssignmentModal').style.display = 'block';
}

// Fonction pour appliquer les filtres
function applyFilters() {
    const typeFilter = document.getElementById('typeFilter').value;
    const groupeFilter = document.getElementById('groupeFilter').value;
    const statutFilter = document.getElementById('statutFilter').value;
    
    // Construire l'URL avec les paramètres de filtrage
    let url = '?page=liste_utilisateurs';
    if (typeFilter) url += '&type=' + typeFilter;
    if (groupeFilter) url += '&groupe=' + groupeFilter;
    if (statutFilter) url += '&statut=' + statutFilter;
    
    // Rediriger vers la nouvelle URL
    window.location.href = url;
}

// Fonction pour réinitialiser les filtres
function resetFilters() {
    // Réinitialiser les sélecteurs
    document.getElementById('typeFilter').value = '';
    document.getElementById('groupeFilter').value = '';
    document.getElementById('statutFilter').value = '';
    
    // Rediriger vers la page sans filtres
    window.location.href = '?page=liste_utilisateurs';
}

// Fonction pour activer les utilisateurs sélectionnés dans la liste principale
function activateSelectedUsers() {
    // Récupérer les utilisateurs sélectionnés
    const selectedUsers = document.querySelectorAll('.row-checkbox:checked');
    
    if (selectedUsers.length === 0) {
        alert('Veuillez sélectionner au moins un utilisateur à activer.');
        return false;
    }
    
    // Confirmation pour l'activation
    if (confirm('Êtes-vous sûr de vouloir activer ' + selectedUsers.length + ' utilisateur(s) sélectionné(s) ?\n\nCette action va :\n- Changer le statut en "Actif"\n- Générer un nouveau mot de passe\n- Envoyer les identifiants par email')) {
        // Créer un formulaire dynamique pour envoyer les données
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../public/assets/traitements/traitements_liste_utilisateurs.php';
        
        // Ajouter l'action
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'generate_passwords';
        form.appendChild(actionInput);
        
        // Ajouter les utilisateurs sélectionnés
        selectedUsers.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected_users[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });
        
        // Soumettre le formulaire
        document.body.appendChild(form);
        form.submit();
    }
}

// Fonction pour mettre à jour l'affichage du bouton d'activation
function updateActivateButton() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    const btn = document.getElementById('generatePasswordsBtn');
    if (checked.length > 0) {
        btn.style.display = 'flex';
    } else {
        btn.style.display = 'none';
    }
}

// Initialisation des gestionnaires d'événements pour la liste principale
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaire pour "Tout sélectionner" dans la liste principale
    const selectAllCheckbox = document.getElementById('select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = this.checked);
            updateActivateButton();
        });
    }
    
    // Gestionnaire pour les checkboxes individuelles dans la liste principale
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('row-checkbox')) {
            updateActivateButton();
        }
    });
});