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
    if (confirm('Êtes-vous sûr de vouloir générer de nouveaux mots de passe pour les utilisateurs sélectionnés ?')) {
        document.getElementById('usersForm').submit();
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