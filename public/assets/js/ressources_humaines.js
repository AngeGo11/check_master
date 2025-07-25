// Gestion des ressources humaines avec AJAX
$(document).ready(function() {
    
    // Gestion des onglets
    $('.tab-button').click(function() {
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-content').removeClass('active');
        $('#' + $(this).data('tab')).addClass('active');
    });

    // Recherche en temps réel
    $('#search-enseignants').on('keyup', function() {
        const search = $(this).val();
        if (search.length >= 2) {
            searchEnseignants(search);
        } else {
            loadEnseignants();
        }
    });

    $('#search-personnel').on('keyup', function() {
        const search = $(this).val();
        if (search.length >= 2) {
            searchPersonnel(search);
        } else {
            loadPersonnel();
        }
    });

    // Filtres
    $('.filter-select').change(function() {
        applyFilters();
    });

    // Formulaires d'ajout/modification
    $('#form-enseignant').submit(function(e) {
        e.preventDefault();
        submitEnseignantForm();
    });

    $('#form-personnel').submit(function(e) {
        e.preventDefault();
        submitPersonnelForm();
    });

    // Boutons de suppression
    $('.btn-delete').click(function() {
        const id = $(this).data('id');
        const type = $(this).data('type');
        if (confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
            deleteItem(id, type);
        }
    });

    // Boutons de modification
    $('.btn-edit').click(function() {
        const id = $(this).data('id');
        const type = $(this).data('type');
        loadItemForEdit(id, type);
    });
});

// Fonctions AJAX
function searchEnseignants(search) {
    $.ajax({
        url: 'assets/traitements/traitements_ressources_humaines.php',
        method: 'GET',
        data: {
            action: 'search_enseignants',
            search: search
        },
        success: function(response) {
            if (response.success) {
                displayEnseignants(response.data);
            } else {
                showError('Erreur lors de la recherche');
            }
        },
        error: function() {
            showError('Erreur de connexion');
        }
    });
}

function searchPersonnel(search) {
    $.ajax({
        url: 'assets/traitements/traitements_ressources_humaines.php',
        method: 'GET',
        data: {
            action: 'search_personnel',
            search: search
        },
        success: function(response) {
            if (response.success) {
                displayPersonnel(response.data);
            } else {
                showError('Erreur lors de la recherche');
            }
        },
        error: function() {
            showError('Erreur de connexion');
        }
    });
}

function submitEnseignantForm() {
    const formData = new FormData($('#form-enseignant')[0]);
    formData.append('action', $('#form-enseignant').data('action'));

    $.ajax({
        url: 'assets/traitements/traitements_ressources_humaines.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showSuccess('Enseignant enregistré avec succès');
                $('#modal-enseignant').modal('hide');
                loadEnseignants();
            } else {
                showError(response.message || 'Erreur lors de l\'enregistrement');
            }
        },
        error: function() {
            showError('Erreur de connexion');
        }
    });
}

function submitPersonnelForm() {
    const formData = new FormData($('#form-personnel')[0]);
    formData.append('action', $('#form-personnel').data('action'));

    $.ajax({
        url: 'assets/traitements/traitements_ressources_humaines.php',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showSuccess('Personnel enregistré avec succès');
                $('#modal-personnel').modal('hide');
                loadPersonnel();
            } else {
                showError(response.message || 'Erreur lors de l\'enregistrement');
            }
        },
        error: function() {
            showError('Erreur de connexion');
        }
    });
}

function deleteItem(id, type) {
    $.ajax({
        url: 'assets/traitements/traitements_ressources_humaines.php',
        method: 'POST',
        data: {
            action: 'delete_' + type,
            id: id
        },
        success: function(response) {
            if (response.success) {
                showSuccess('Élément supprimé avec succès');
                if (type === 'enseignant') {
                    loadEnseignants();
                } else {
                    loadPersonnel();
                }
            } else {
                showError(response.message || 'Erreur lors de la suppression');
            }
        },
        error: function() {
            showError('Erreur de connexion');
        }
    });
}

function loadItemForEdit(id, type) {
    $.ajax({
        url: 'assets/traitements/traitements_ressources_humaines.php',
        method: 'GET',
        data: {
            action: 'get_' + type,
            id: id
        },
        success: function(response) {
            if (response.success) {
                populateForm(response.data, type);
                $('#modal-' + type).modal('show');
            } else {
                showError('Erreur lors du chargement des données');
            }
        },
        error: function() {
            showError('Erreur de connexion');
        }
    });
}

function populateForm(data, type) {
    const form = $('#form-' + type);
    form.data('action', 'update_' + type);
    form.find('input[name="id"]').val(data.id);
    form.find('input[name="nom"]').val(data.nom);
    form.find('input[name="prenoms"]').val(data.prenoms);
    form.find('input[name="email"]').val(data.email);
    form.find('input[name="telephone"]').val(data.telephone || '');
    form.find('textarea[name="adresse"]').val(data.adresse || '');
    
    if (type === 'enseignant') {
        form.find('select[name="grade_id"]').val(data.grade_id || '');
        form.find('select[name="fonction_id"]').val(data.fonction_id || '');
        form.find('select[name="specialite_id"]').val(data.specialite_id || '');
    } else {
        form.find('select[name="groupe_id"]').val(data.groupe_id || '');
    }
}

function applyFilters() {
    const filters = {
        grade: $('#filter-grade').val(),
        fonction: $('#filter-fonction').val(),
        specialite: $('#filter-specialite').val(),
        groupe: $('#filter-groupe').val()
    };

    $.ajax({
        url: 'assets/traitements/traitements_ressources_humaines.php',
        method: 'GET',
        data: {
            action: 'search_enseignants',
            ...filters
        },
        success: function(response) {
            if (response.success) {
                displayEnseignants(response.data);
            }
        }
    });
}

function displayEnseignants(data) {
    const container = $('#enseignants-list');
    container.empty();
    
    data.forEach(function(enseignant) {
        const row = `
            <tr>
                <td>${enseignant.nom} ${enseignant.prenoms}</td>
                <td>${enseignant.email}</td>
                <td>${enseignant.nom_grd || '-'}</td>
                <td>${enseignant.nom_fonction || '-'}</td>
                <td>${enseignant.lib_spe || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-primary btn-edit" data-id="${enseignant.id_ens}" data-type="enseignant">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="${enseignant.id_ens}" data-type="enseignant">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        container.append(row);
    });
}

function displayPersonnel(data) {
    const container = $('#personnel-list');
    container.empty();
    
    data.forEach(function(personnel) {
        const row = `
            <tr>
                <td>${personnel.nom_personnel_adm} ${personnel.prenoms_personnel_adm}</td>
                <td>${personnel.email_personnel_adm}</td>
                <td>${personnel.poste || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-primary btn-edit" data-id="${personnel.id_personnel_adm}" data-type="personnel">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-delete" data-id="${personnel.id_personnel_adm}" data-type="personnel">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        container.append(row);
    });
}

function loadEnseignants() {
    // Recharger la liste des enseignants
    location.reload();
}

function loadPersonnel() {
    // Recharger la liste du personnel
    location.reload();
}

function showSuccess(message) {
    $('.alert-success').remove();
    $('body').prepend(`
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);
    setTimeout(function() {
        $('.alert-success').fadeOut();
    }, 3000);
}

function showError(message) {
    $('.alert-danger').remove();
    $('body').prepend(`
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `);
    setTimeout(function() {
        $('.alert-danger').fadeOut();
    }, 5000);
} 