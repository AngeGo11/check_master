  // Sélectionner les éléments nécessaires pour les ouvertures/fermetures modales si JS est activé
  document.addEventListener('DOMContentLoaded', function() {
    // Fermer les modales quand on clique sur la croix
    document.querySelectorAll('.close').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            // Trouver la modale parente
            const modal = this.closest('.modal');
            if (modal) {
                // Fermer la modale en enlevant la classe 'open'
                modal.classList.remove('open');
            }
        });
    });

    // Fermer les modales quand on clique en dehors du contenu
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('click', function(event) {
            // Si on clique directement sur la modale (et pas son contenu)
            if (event.target === modal) {
                modal.classList.remove('open');
            }
        });
    });

    // Ouvrir la modale d'ajout d'étudiant
    const addStudentBtn = document.getElementById('add_student');
    if (addStudentBtn) {
        addStudentBtn.addEventListener('click', function(event) {
            if (!event.target.href) {
                event.preventDefault();
                document.querySelector('#add-student-modal').classList.add('open');
            }
        });
    }

    // Logique pour la modale d'aperçu du rapport
    const previewBtn = document.querySelector('#rapports-student-modal .preview-btn');
    const previewModal = document.getElementById('preview-rapport-modal');

    if (previewBtn && previewModal) {
        const closePreviewBtn = document.getElementById('close-modal-preview-btn');
        const iframe = previewModal.querySelector('iframe');

        previewBtn.addEventListener('click', function(event) {
            event.preventDefault();
            const path = this.dataset.path;
            if (path && iframe) {
                iframe.setAttribute('src', path);
                previewModal.classList.add('open');
            }
        });

        closePreviewBtn.addEventListener('click', function() {
            previewModal.classList.remove('open');
            iframe.setAttribute('src', ''); // Vider le src pour arrêter la lecture
        });
    }

    // Ajouter les événements d'exportation aux boutons existants
    const exportBtns = document.querySelectorAll('.bulk-export-btn');
    exportBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            exportData();
        });
    });

    // Gestion de la sélection en masse
    const selectAllEtudiants = document.getElementById('select-all-etudiants');
    const etudiantCheckboxes = document.querySelectorAll('.etudiant-checkbox');
    if (selectAllEtudiants) {
        selectAllEtudiants.addEventListener('change', function() {
            etudiantCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    const selectAllRapports = document.getElementById('select-all-rapports');
    const rapportCheckboxes = document.querySelectorAll('.rapport-checkbox');
    if (selectAllRapports) {
        selectAllRapports.addEventListener('change', function() {
            rapportCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Modale de confirmation générique
    let confirmCallback = null;

    function openConfirmationModal(message, onConfirm) {
        document.getElementById('confirmation-text').textContent = message;
        document.getElementById('confirmation-modal').style.display = 'flex';
        confirmCallback = onConfirm;
    }

    function closeConfirmationModal() {
        document.getElementById('confirmation-modal').style.display = 'none';
        confirmCallback = null;
    }
    document.getElementById('confirm-modal-btn').onclick = function() {
        if (typeof confirmCallback === 'function') confirmCallback();
        closeConfirmationModal();
    };
    document.getElementById('cancel-modal-btn').onclick = closeConfirmationModal;
    document.getElementById('close-confirmation-modal-btn').onclick = closeConfirmationModal;
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('confirmation-modal');
        if (event.target === modal) closeConfirmationModal();
    });

    // Remplacement suppression multiple étudiants
    const bulkDeleteBtn = document.getElementById('bulk-delete-btn');
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const checkedBoxes = document.querySelectorAll('.etudiant-checkbox:checked');
            const etudiantIds = Array.from(checkedBoxes).map(cb => cb.value);
            if (etudiantIds.length === 0) {
                openConfirmationModal('Veuillez sélectionner au moins un étudiant à supprimer.', null);
                return;
            }
            openConfirmationModal(
                `ATTENTION : Vous allez supprimer ${etudiantIds.length} étudiant(s) ainsi que toutes leurs données associées (rapports, inscriptions, etc.). Cette action est irréversible. Confirmez-vous ?`,
                function() {
                    fetch('./assets/traitements/supprimer_etudiants.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'etudiant_ids=' + JSON.stringify(etudiantIds)
                        })
                        .then(res => res.json())
                        .then(data => {
                            openConfirmationModal(data.message || (data.success ? 'Étudiants supprimés avec succès.' : data.error), function() {
                                if (data.success) location.reload();
                            });
                        });
                }
            );
        });
    }

    // Remplacement suppression multiple rapports
    const bulkDeleteRapportsBtn = document.getElementById('bulk-delete-rapports-btn');
    if (bulkDeleteRapportsBtn) {
        bulkDeleteRapportsBtn.addEventListener('click', function() {
            const checkedBoxes = document.querySelectorAll('.rapport-checkbox:checked');
            const rapportIds = Array.from(checkedBoxes).map(cb => cb.value);
            if (rapportIds.length === 0) {
                openConfirmationModal('Veuillez sélectionner au moins un rapport à supprimer.', null);
                return;
            }
            openConfirmationModal(
                `Voulez-vous vraiment supprimer les ${rapportIds.length} rapports sélectionnés ?`,
                function() {
                    fetch('./assets/traitements/supprimer_rapports_etudiants.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'rapport_ids=' + JSON.stringify(rapportIds)
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                openConfirmationModal(data.message || 'Rapports supprimés avec succès.', function() {
                                    location.reload();
                                });
                            } else {
                                openConfirmationModal(data.message || data.error, null);
                            }
                        });
                }
            );
        });
    }
});

// === FONCTIONS D'EXPORTATION ===

// Fonction principale d'exportation
function exportData() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px;">
            <div class="top-text">
                <h2 class="modal-title">Export des données Étudiants</h2>
                <button class="close-modal-btn" onclick="this.closest('.modal').remove()">×</button>
            </div>
            <div class="modal-body">
                <p>Choisissez le format d'export pour les données actuellement affichées :</p>
                <div style="display: flex; flex-direction: column; gap: 15px; margin-top: 20px;">
                    <button class="button" onclick="exportToCSV(); this.closest('.modal').remove();">
                        <i class="fas fa-file-csv"></i> Export Excel/CSV
                        <small style="display: block; font-weight: normal;">Format tableur compatible Excel</small>
                    </button>
                    <button class="button" onclick="exportToPDF(); this.closest('.modal').remove();">
                        <i class="fas fa-file-pdf"></i> Export PDF
                        <small style="display: block; font-weight: normal;">Format document imprimable</small>
                    </button>
                    <button class="button secondary" onclick="window.print(); this.closest('.modal').remove();">
                        <i class="fas fa-print"></i> Imprimer directement
                    </button>
                </div>
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <strong>Note :</strong> L'export inclura uniquement les données visibles dans la section active.
                </div>
            </div>
        </div>
    `;
    modal.classList.add('open');
    document.body.appendChild(modal);
}

// Export CSV/Excel
function exportToCSV() {
    const tables = document.querySelectorAll('.users-table');
    let csv = [];

    tables.forEach((table, index) => {
        // En-têtes du tableau
        const headers = Array.from(table.querySelectorAll('thead th')).slice(1, -1); // Exclure checkbox et actions
        csv.push(headers.map(th => th.textContent.trim().replace(/\r?\n|\r/g, ' ')).join(';'));

        // Données visibles uniquement
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cells = Array.from(row.querySelectorAll('td')).slice(1, -1); // Exclure checkbox et actions
                const rowData = cells.map(td => {
                    let text = td.textContent.trim().replace(/\s+/g, ' ');
                    text = text.replace(/\r?\n|\r/g, ' '); // Supprimer les retours à la ligne
                    text = text.replace(/"/g, '""');
                    return '"' + text + '"';
                });
                csv.push(rowData.join(';'));
            }
        });
        // Séparateur entre les deux tableaux (sauf le dernier)
        if (index < tables.length - 1) {
            csv.push('---');
        }
    });

    // Télécharger
    const csvContent = csv.join('\n');
    const blob = new Blob(['\ufeff' + csvContent], {
        type: 'text/csv;charset=utf-8;'
    });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `etudiants_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showFeedback('Export CSV terminé avec succès', 'success');
}

// Export PDF
function exportToPDF() {
    // Créer une nouvelle fenêtre pour l'impression
    const printWindow = window.open('', '_blank');
    // Préparer le tableau sans les colonnes checkbox et actions
    function getTableWithoutCheckboxAndActions(table) {
        const clone = table.cloneNode(true);
        // Supprimer la colonne checkbox et actions dans thead
        const ths = clone.querySelectorAll('thead th');
        if (ths.length > 2) {
            ths[0].remove(); // checkbox
            ths[ths.length - 1].remove(); // actions
        }
        // Supprimer la colonne checkbox et actions dans tbody
        clone.querySelectorAll('tbody tr').forEach(tr => {
            if (tr.children.length > 2) {
                tr.children[0].remove();
                tr.children[tr.children.length - 1].remove();
            }
        });
        return clone.outerHTML;
    }
    const tables = document.querySelectorAll('.users-table');
    let tableHtmls = [];
    tables.forEach((table, idx) => {
        tableHtmls.push(getTableWithoutCheckboxAndActions(table));
    });
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Export Étudiants</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                .header h1 { color: #2c3e50; margin: 0; }
                .header p { color: #7f8c8d; margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .stats { margin-bottom: 20px; }
                .stats h3 { color: #2c3e50; }
                .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
                .stat-item { background: #f8f9fa; padding: 10px; border-radius: 5px; text-align: center; }
                .stat-value { font-size: 24px; font-weight: bold; color: #3498db; }
                .stat-label { color: #7f8c8d; font-size: 14px; }
                @media print {
                    body { margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Gestion des Étudiants</h1>
                <p>Université Félix Houphouët-Boigny</p>
                <p>Date d'export: ${new Date().toLocaleDateString('fr-FR')}</p>
            </div>
            <div class="stats">
                <h3>Statistiques</h3>
                <div class="stats-grid">
                    ${document.querySelector('.dashboard-grid').innerHTML}
                </div>
            </div>
            <div class="section">
                <h2>Liste des étudiants</h2>
                ${tableHtmls[0]}
            </div>
            ${tableHtmls.length > 1 ? `
            <div class="section">
                <h2>Liste des rapports étudiants</h2>
                ${tableHtmls[1]}
            </div>
            ` : ''}
            <div style="margin-top: 30px; text-align: center; color: #7f8c8d; font-size: 12px;">
                Document généré automatiquement par le système GSCV
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.focus();
    // Attendre que le contenu soit chargé puis imprimer
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
    showFeedback('Export PDF terminé avec succès', 'success');
}

// Messages de feedback
function showFeedback(message, type = 'info') {
    const feedback = document.createElement('div');
    feedback.className = `feedback-message ${type}`;
    feedback.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${message}
    `;
    feedback.style.cssText = `
        position: fixed; top: 20px; right: 20px; z-index: 2000;
        padding: 15px 20px; border-radius: 8px; color: white;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease-out;
    `;
    document.body.appendChild(feedback);

    setTimeout(() => {
        feedback.style.animation = 'slideInRight 0.3s ease-out reverse';
        setTimeout(() => {
            if (feedback.parentNode) {
                document.body.removeChild(feedback);
            }
        }, 300);
    }, 4000);
}