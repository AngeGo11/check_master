// Gestion de la piste d'audit avec AJAX
$(document).ready(function() {
    
    // Initialisation des filtres
    initializeFilters();
    
    // Gestion des filtres
    $('.filter-control').change(function() {
        applyFilters();
    });
    
    // Recherche en temps réel
    $('#search-audit').on('keyup', function() {
        const search = $(this).val();
        if (search.length >= 2) {
            searchAuditRecords(search);
        } else {
            loadAuditRecords();
        }
    });
    
    // Export des données
    $('#btn-export').click(function() {
        exportAuditData();
    });
    
    // Nettoyage des anciens logs
    $('#btn-clear-logs').click(function() {
        if (confirm('Êtes-vous sûr de vouloir supprimer les anciens logs ? Cette action est irréversible.')) {
            clearOldLogs();
        }
    });
    
    // Chargement initial
    loadAuditRecords();
    loadStatistics();
});

function initializeFilters() {
    // Charger les options des filtres
    loadFilterOptions('actions', 'get_available_actions');
    loadFilterOptions('modules', 'get_available_modules');
    loadFilterOptions('user_types', 'get_user_types');
}

function loadFilterOptions(filterType, action) {
    $.ajax({
        url: 'assets/traitements/traitements_piste_audit.php',
        method: 'GET',
        data: { action: action },
        success: function(response) {
            if (response.success) {
                populateFilterOptions(filterType, response.data);
            }
        }
    });
}

function populateFilterOptions(filterType, data) {
    const select = $(`#filter-${filterType}`);
    select.empty();
    select.append('<option value="">Tous</option>');
    
    if (Array.isArray(data)) {
        data.forEach(function(item) {
            if (typeof item === 'string') {
                select.append(`<option value="${item}">${item}</option>`);
            } else if (item.lib_traitement) {
                select.append(`<option value="${item.lib_traitement}">${item.nom_traitement}</option>`);
            }
        });
    }
}

function applyFilters() {
    const filters = {
        date_debut: $('#filter-date-debut').val(),
        date_fin: $('#filter-date-fin').val(),
        type_action: $('#filter-actions').val(),
        type_utilisateur: $('#filter-user-types').val(),
        module: $('#filter-modules').val(),
        search: $('#search-audit').val()
    };
    
    loadAuditRecords(filters);
}

function loadAuditRecords(filters = {}) {
    const params = {
        action: 'get_audit_records',
        page: getCurrentPage(),
        limit: $('#limit-select').val(),
        ...filters
    };
    
    $.ajax({
        url: 'assets/traitements/traitements_piste_audit.php',
        method: 'GET',
        data: params,
        success: function(response) {
            if (response.success) {
                displayAuditRecords(response.data);
                updatePagination(response.total_pages);
            } else {
                showError('Erreur lors du chargement des données');
            }
        },
        error: function() {
            showError('Erreur de connexion');
        }
    });
}

function searchAuditRecords(search) {
    const filters = {
        search: search,
        date_debut: $('#filter-date-debut').val(),
        date_fin: $('#filter-date-fin').val(),
        type_action: $('#filter-actions').val(),
        type_utilisateur: $('#filter-user-types').val(),
        module: $('#filter-modules').val()
    };
    
    loadAuditRecords(filters);
}

function displayAuditRecords(data) {
    const container = $('#audit-records');
    container.empty();
    
    if (data.length === 0) {
        container.append('<tr><td colspan="7" class="text-center">Aucun enregistrement trouvé</td></tr>');
        return;
    }
    
    data.forEach(function(record) {
        const row = `
            <tr>
                <td>${formatDateTime(record.date_piste, record.heure_piste)}</td>
                <td>${record.nom_utilisateur} ${record.prenoms_utilisateur}</td>
                <td><span class="badge badge-${record.type_utilisateur === 'Enseignant' ? 'primary' : record.type_utilisateur === 'Personnel administratif' ? 'success' : 'info'}">${record.type_utilisateur}</span></td>
                <td>${record.lib_traitement || '-'}</td>
                <td>${record.lib_action || '-'}</td>
                <td>
                    <span class="badge badge-${record.acceder ? 'success' : 'danger'}">
                        ${record.acceder ? 'Succès' : 'Échec'}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-info btn-details" data-id="${record.id_piste}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        container.append(row);
    });
}

function loadStatistics() {
    $.ajax({
        url: 'assets/traitements/traitements_piste_audit.php',
        method: 'GET',
        data: { action: 'get_statistics' },
        success: function(response) {
            if (response.success) {
                updateStatistics(response.data);
            }
        }
    });
}

function updateStatistics(stats) {
    $('#total-actions').text(stats.total_actions || 0);
    $('#unique-users').text(stats.utilisateurs_actifs || 0);
    $('#successful-actions').text(stats.actions_reussies || 0);
    $('#failed-actions').text(stats.actions_echouees || 0);
    $('#today-actions').text(stats.actions_aujourdhui || 0);
    $('#yesterday-actions').text(stats.actions_hier || 0);
    
    // Calculer les pourcentages d'évolution
    const evolutionActions = calculateEvolution(stats.actions_aujourdhui, stats.actions_hier);
    const evolutionConnexions = calculateEvolution(stats.connexions_aujourdhui, stats.connexions_hier);
    const evolutionEchecs = calculateEvolution(stats.echecs_aujourdhui, stats.echecs_hier);
    
    updateEvolutionIndicator('#evolution-actions', evolutionActions);
    updateEvolutionIndicator('#evolution-connexions', evolutionConnexions);
    updateEvolutionIndicator('#evolution-echecs', evolutionEchecs);
}

function calculateEvolution(current, previous) {
    if (!previous || previous === 0) return 0;
    return Math.round(((current - previous) / previous) * 100);
}

function updateEvolutionIndicator(selector, value) {
    const element = $(selector);
    element.text(value > 0 ? `+${value}%` : `${value}%`);
    element.removeClass('text-success text-danger');
    element.addClass(value >= 0 ? 'text-success' : 'text-danger');
}

function exportAuditData() {
    const filters = {
        date_debut: $('#filter-date-debut').val(),
        date_fin: $('#filter-date-fin').val(),
        type_action: $('#filter-actions').val(),
        type_utilisateur: $('#filter-user-types').val(),
        module: $('#filter-modules').val(),
        search: $('#search-audit').val(),
        format: $('#export-format').val()
    };
    
    $.ajax({
        url: 'assets/traitements/traitements_piste_audit.php',
        method: 'GET',
        data: {
            action: 'export_audit',
            ...filters
        },
        success: function(response) {
            if (response.success) {
                // Télécharger le fichier
                window.location.href = response.file;
                showSuccess('Export terminé avec succès');
            } else {
                showError(response.message || 'Erreur lors de l\'export');
            }
        },
        error: function() {
            showError('Erreur de connexion');
        }
    });
}

function clearOldLogs() {
    const days = $('#clear-days').val() || 90;
    
    $.ajax({
        url: 'assets/traitements/traitements_piste_audit.php',
        method: 'POST',
        data: {
            action: 'clear_old_logs',
            days: days
        },
        success: function(response) {
            if (response.success) {
                showSuccess(`Anciens logs supprimés avec succès (plus de ${days} jours)`);
                loadAuditRecords();
                loadStatistics();
            } else {
                showError(response.message || 'Erreur lors du nettoyage');
            }
        },
        error: function() {
            showError('Erreur de connexion');
        }
    });
}

function getCurrentPage() {
    const urlParams = new URLSearchParams(window.location.search);
    return parseInt(urlParams.get('num')) || 1;
}

function updatePagination(totalPages) {
    const currentPage = getCurrentPage();
    const pagination = $('#pagination');
    pagination.empty();
    
    if (totalPages <= 1) return;
    
    // Bouton précédent
    if (currentPage > 1) {
        pagination.append(`<li class="page-item"><a class="page-link" href="${generatePageUrl(currentPage - 1)}">Précédent</a></li>`);
    }
    
    // Pages
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            pagination.append(`<li class="page-item active"><span class="page-link">${i}</span></li>`);
        } else {
            pagination.append(`<li class="page-item"><a class="page-link" href="${generatePageUrl(i)}">${i}</a></li>`);
        }
    }
    
    // Bouton suivant
    if (currentPage < totalPages) {
        pagination.append(`<li class="page-item"><a class="page-link" href="${generatePageUrl(currentPage + 1)}">Suivant</a></li>`);
    }
}

function generatePageUrl(page) {
    const url = new URL(window.location);
    url.searchParams.set('num', page);
    return url.toString();
}

function formatDateTime(date, time) {
    const dateObj = new Date(date + ' ' + time);
    return dateObj.toLocaleString('fr-FR');
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