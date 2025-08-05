// Script pour gérer l'inscription à cheval avec calcul des prix
document.addEventListener('DOMContentLoaded', function() {
    let selectedMatieres = [];
    let totalPrixMatieres = 0;
    let fraisBase = 0;

    // Fonction pour mettre à jour l'affichage des matières avec prix
    function displayMatieres(matieres) {
        const matieresContainer = document.getElementById('matieres-container');
        if (!matieresContainer) return;

        let html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">';
        
        matieres.forEach(matiere => {
            const prix = parseFloat(matiere.prix_matiere_cheval || 25000);
            html += `
                <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <input type="checkbox" name="selected_matieres[]" 
                           value="${matiere.id_ecue}" 
                           data-prix="${prix}"
                           class="matiere-checkbox w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2"
                           onchange="updatePrixTotal()">
                    <div class="ml-3 flex-1">
                        <div class="font-medium text-gray-900">${matiere.lib_ecue}</div>
                        <div class="text-sm text-gray-500">
                            Crédits: ${matiere.credit_ecue} | Niveau: ${matiere.lib_niv_etd}
                        </div>
                        <div class="text-sm font-semibold text-green-600">
                            Prix: ${prix.toLocaleString('fr-FR')} FCFA
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        matieresContainer.innerHTML = html;
    }

    // Fonction pour mettre à jour le total des prix
    window.updatePrixTotal = function() {
        const checkboxes = document.querySelectorAll('.matiere-checkbox:checked');
        selectedMatieres = Array.from(checkboxes).map(cb => cb.value);
        totalPrixMatieres = Array.from(checkboxes).reduce((total, cb) => {
            return total + parseFloat(cb.dataset.prix || 25000);
        }, 0);

        updateFraisDisplay();
    };

    // Fonction pour mettre à jour l'affichage des frais
    function updateFraisDisplay() {
        const fraisBaseElement = document.getElementById('frais-base');
        const prixMatieresElement = document.getElementById('prix-matieres');
        const totalFraisElement = document.getElementById('total-frais');

        if (fraisBaseElement) {
            fraisBaseElement.textContent = fraisBase.toLocaleString('fr-FR') + ' FCFA';
        }
        if (prixMatieresElement) {
            prixMatieresElement.textContent = totalPrixMatieres.toLocaleString('fr-FR') + ' FCFA';
        }
        if (totalFraisElement) {
            const total = fraisBase + totalPrixMatieres;
            totalFraisElement.textContent = total.toLocaleString('fr-FR') + ' FCFA';
        }
    }

    // Fonction pour charger les matières selon les étudiants sélectionnés
    window.loadMatieres = function() {
        const selectedEtudiants = getSelectedEtudiants();
        const anneeId = document.getElementById('annee_academique')?.value;
        const promotionId = document.getElementById('promotion_principale')?.value;

        if (selectedEtudiants.length === 0) {
            alert('Veuillez sélectionner au moins un étudiant');
            return;
        }

        // Afficher un indicateur de chargement
        const matieresContainer = document.getElementById('matieres-container');
        if (matieresContainer) {
            matieresContainer.innerHTML = '<div class="text-center py-4"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto"></div><p class="mt-2 text-gray-600">Chargement des matières...</p></div>';
        }

        // Appel AJAX pour récupérer les matières
        fetch('ajax_inscription_cheval.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'get-matieres-rattrapage',
                annee_id: anneeId,
                promotion_id: promotionId,
                etudiants_ids: JSON.stringify(selectedEtudiants)
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayMatieres(data.matieres);
                // Calculer les frais de base pour le premier étudiant sélectionné
                if (selectedEtudiants.length > 0) {
                    calculerFraisBase(selectedEtudiants[0], anneeId);
                }
            } else {
                alert('Erreur: ' + (data.message || 'Impossible de charger les matières'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des matières');
        });
    };

    // Fonction pour calculer les frais de base
    function calculerFraisBase(etudiantId, anneeId) {
        fetch('ajax_inscription_cheval.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'calculer-frais-base',
                etudiant_id: etudiantId,
                annee_id: anneeId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fraisBase = parseFloat(data.frais_base || 0);
                updateFraisDisplay();
            }
        })
        .catch(error => {
            console.error('Erreur calcul frais base:', error);
        });
    }

    // Fonction pour obtenir les étudiants sélectionnés
    function getSelectedEtudiants() {
        const checkboxes = document.querySelectorAll('.etudiant-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    // Fonction pour valider le formulaire avant soumission
    window.validateForm = function() {
        const selectedEtudiants = getSelectedEtudiants();
        const selectedMatieres = document.querySelectorAll('.matiere-checkbox:checked');

        if (selectedEtudiants.length === 0) {
            alert('Veuillez sélectionner au moins un étudiant');
            return false;
        }

        if (selectedMatieres.length === 0) {
            alert('Veuillez sélectionner au moins une matière');
            return false;
        }

        // Mettre à jour le champ caché avec le total des frais
        const totalFrais = fraisBase + totalPrixMatieres;
        const totalFraisInput = document.getElementById('total_frais');
        if (totalFraisInput) {
            totalFraisInput.value = totalFrais;
        }

        return true;
    };

    // Initialisation
    console.log('Script inscription à cheval chargé');
}); 