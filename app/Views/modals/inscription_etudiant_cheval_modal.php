<!-- Modal d'inscription étudiant à cheval -->
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-warning/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-horse text-warning text-lg"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Inscription Multiple Étudiants à Cheval</h3>
                </div>
                <a href="?page=etudiants" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </a>
            </div>
        </div>

        <form method="post" action="?page=etudiants&action=inscrire-etudiants-cheval" class="p-6" id="inscriptionChevalForm">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Section 1: Sélection des étudiants -->
                <div class="space-y-6">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-users text-blue-600 mr-3"></i>Sélection des étudiants
                        </h4>
                        
                        <!-- Filtres pour les étudiants -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="filter_niveau" class="block text-sm font-medium text-gray-700 mb-2">
                                    Filtrer par niveau
                                </label>
                                <select id="filter_niveau" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Tous les niveaux</option>
                                    <?php foreach ($inscription_data['niveaux'] ?? [] as $niveau): ?>
                                        <option value="<?php echo $niveau['id_niv_etd']; ?>">
                                            <?php echo htmlspecialchars($niveau['lib_niv_etd']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="filter_promotion" class="block text-sm font-medium text-gray-700 mb-2">
                                    Filtrer par promotion
                                </label>
                                <select id="filter_promotion" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Toutes les promotions</option>
                                    <?php foreach ($inscription_data['promotions'] ?? [] as $promotion): ?>
                                        <option value="<?php echo $promotion['id_promotion']; ?>">
                                            <?php echo htmlspecialchars($promotion['lib_promotion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Sélection multiple des étudiants -->
                        <div class="bg-white rounded-lg border border-gray-200 max-h-64 overflow-y-auto">
                            <div class="p-3 border-b border-gray-200 bg-gray-50">
                                <div class="flex items-center">
                                    <input type="checkbox" id="select-all-etudiants" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                    <label for="select-all-etudiants" class="ml-2 text-sm font-medium text-gray-700">
                                        Sélectionner tous les étudiants visibles
                                    </label>
                                </div>
                            </div>
                            <div id="etudiants-list" class="p-3 space-y-2">
                                <?php foreach ($inscription_data['etudiants'] ?? [] as $etudiant): ?>
                                    <div class="etudiant-item flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors" 
                                         data-niveau="<?php echo $etudiant['id_niv_etd']; ?>" 
                                         data-promotion="<?php echo $etudiant['id_promotion']; ?>">
                                        <input type="checkbox" name="selected_etudiants[]" 
                                               value="<?php echo $etudiant['num_etd']; ?>" 
                                               class="etudiant-checkbox w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                        <div class="ml-3 flex-1">
                                            <div class="font-medium text-gray-900">
                                                <?php echo htmlspecialchars($etudiant['nom_etd'] . ' ' . $etudiant['prenom_etd']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($etudiant['email_etd']); ?>
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-400">
                                            <?php echo htmlspecialchars($etudiant['lib_niv_etd'] ?? ''); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mt-3 text-sm text-gray-600">
                            <span id="selected-count">0</span> étudiant(s) sélectionné(s)
                        </div>
                    </div>
                </div>

                <!-- Section 2: Configuration et matières -->
                <div class="space-y-6">
                    <!-- Configuration générale -->
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-cog text-green-600 mr-3"></i>Configuration générale
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Année académique -->
                            <div>
                                <label for="id_ac" class="block text-sm font-medium text-gray-700 mb-2">
                                    Année académique <span class="text-red-500">*</span>
                                </label>
                                <select name="id_ac" id="id_ac" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-warning focus:border-transparent">
                                    <option value="">Sélectionner une année</option>
                                    <?php foreach ($inscription_data['annees'] ?? [] as $annee): ?>
                                        <option value="<?php echo $annee['id_ac']; ?>">
                                            <?php echo htmlspecialchars($annee['annee_ac']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Promotion principale -->
                            <div>
                                <label for="promotion_principale" class="block text-sm font-medium text-gray-700 mb-2">
                                    Promotion d'origine <span class="text-red-500">*</span> 
                                </label>
                                <select name="promotion_principale" id="promotion_principale" required 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-warning focus:border-transparent">
                                    <option value="">Sélectionner une promotion</option>
                                    <?php foreach ($inscription_data['promotions'] ?? [] as $promotion): ?>
                                        <option value="<?php echo $promotion['id_promotion']; ?>">
                                            <?php echo htmlspecialchars($promotion['lib_promotion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Montant d'inscription -->
                            <div>
                                <label for="montant_inscription" class="block text-sm font-medium text-gray-700 mb-2">
                                    Montant d'inscription (FCFA)
                                </label>
                                <input type="number" name="montant_inscription" id="montant_inscription" 
                                       min="0" value="0"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-warning focus:border-transparent">
                            </div>

                            <!-- Commentaire -->
                            <div>
                                <label for="commentaire" class="block text-sm font-medium text-gray-700 mb-2">
                                    Commentaire
                                </label>
                                <textarea name="commentaire" id="commentaire" rows="3"
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-warning focus:border-transparent"
                                          placeholder="Commentaires additionnels..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Sélection des matières -->
                    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl p-6 border border-purple-200">
                        <h4 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-book text-purple-600 mr-3"></i>Sélection des matières de rattrapage
                        </h4>
                        
                        <div id="matieres-section" class="space-y-4">
                            <div class="text-center text-gray-500 py-8">
                                <i class="fas fa-info-circle text-2xl mb-2"></i>
                                <p>Sélectionnez d'abord des étudiants et une année académique pour voir les matières disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 justify-end mt-6 pt-6 border-t border-gray-200">
                <a href="?page=etudiants" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Annuler
                </a>
                <button type="submit" id="submit-btn" disabled
                        class="px-6 py-3 bg-warning text-white rounded-lg hover:bg-orange-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-save mr-2"></i>
                    Inscrire les étudiants sélectionnés
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('select-all-etudiants');
    const etudiantCheckboxes = document.querySelectorAll('.etudiant-checkbox');
    const selectedCountSpan = document.getElementById('selected-count');
    const submitBtn = document.getElementById('submit-btn');
    const filterNiveau = document.getElementById('filter_niveau');
    const filterPromotion = document.getElementById('filter_promotion');
    const matieresSection = document.getElementById('matieres-section');

    // Gestion de la sélection multiple
    selectAllCheckbox.addEventListener('change', function() {
        const visibleCheckboxes = document.querySelectorAll('.etudiant-item:not(.hidden) .etudiant-checkbox');
        visibleCheckboxes.forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
        updateSubmitButton();
    });

    etudiantCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateSelectedCount();
            updateSubmitButton();
            updateSelectAllState();
            // Charger les matières si une année et promotion sont sélectionnées
            const anneeSelected = document.getElementById('id_ac').value;
            const promotionSelected = document.getElementById('promotion_principale').value;
            if (anneeSelected && promotionSelected) {
                loadMatieres();
            }
        });
    });

    // Filtres
    filterNiveau.addEventListener('change', filterEtudiants);
    filterPromotion.addEventListener('change', filterEtudiants);

    function filterEtudiants() {
        const niveauFilter = filterNiveau.value;
        const promotionFilter = filterPromotion.value;
        const etudiantItems = document.querySelectorAll('.etudiant-item');

        etudiantItems.forEach(item => {
            const niveau = item.dataset.niveau;
            const promotion = item.dataset.promotion;
            const shouldShow = (!niveauFilter || niveau === niveauFilter) && 
                             (!promotionFilter || promotion === promotionFilter);
            
            item.classList.toggle('hidden', !shouldShow);
        });

        updateSelectAllState();
    }

    function updateSelectAllState() {
        const visibleCheckboxes = document.querySelectorAll('.etudiant-item:not(.hidden) .etudiant-checkbox');
        const checkedVisibleCheckboxes = document.querySelectorAll('.etudiant-item:not(.hidden) .etudiant-checkbox:checked');
        
        if (visibleCheckboxes.length > 0) {
            selectAllCheckbox.checked = checkedVisibleCheckboxes.length === visibleCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedVisibleCheckboxes.length > 0 && checkedVisibleCheckboxes.length < visibleCheckboxes.length;
        }
    }

    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.etudiant-checkbox:checked').length;
        selectedCountSpan.textContent = selectedCount;
    }

    function updateSubmitButton() {
        const selectedCount = document.querySelectorAll('.etudiant-checkbox:checked').length;
        
        submitBtn.disabled = selectedCount === 0;
    }

    // Chargement automatique des matières au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        loadMatieres();
    });

    // Chargement dynamique des matières (se déclenche quand des étudiants sont sélectionnés)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('etudiant-checkbox')) {
            loadMatieres();
        }
    });

    function loadMatieres() {
        const selectedEtudiants = Array.from(document.querySelectorAll('.etudiant-checkbox:checked')).map(cb => cb.value);

        // Afficher toutes les UE de l'année courante, même sans étudiants sélectionnés
        if (selectedEtudiants.length === 0) {
            matieresSection.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                    <p>Chargement de toutes les matières de l'année courante...</p>
                </div>
            `;
        }

        // Récupérer d'abord l'ID de l'année courante
        fetch('/assets/traitements/get_current_year_id.php', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(yearData => {
            if (!yearData.success) {
                throw new Error('Impossible de récupérer l\'année courante: ' + yearData.message);
            }
            
            // Charger les matières via AJAX avec l'ID de l'année
            console.log('=== DEBUG AJAX ===');
            const ajaxUrl = '/assets/traitements/get_matieres_rattrapage.php?annee_id=' + encodeURIComponent(yearData.id_ac) + '&etudiants=' + encodeURIComponent(JSON.stringify(selectedEtudiants));
            console.log('URL:', ajaxUrl);
            console.log('Annee ID:', yearData.id_ac);
            console.log('Etudiants:', selectedEtudiants);
            
            return fetch(ajaxUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                displayMatieres(data.matieres);
            } else {
                console.error('Server error:', data.message);
                matieresSection.innerHTML = `
                    <div class="text-center text-red-500 py-8">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Erreur lors du chargement des matières: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            matieresSection.innerHTML = `
                <div class="text-center text-red-500 py-8">
                    <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                    <p>Erreur lors du chargement des matières: ${error.message}</p>
                </div>
            `;
        });
    }

    function displayMatieres(matieres) {
        if (matieres.length === 0) {
            matieresSection.innerHTML = `
                <div class="text-center text-gray-500 py-8">
                    <i class="fas fa-book-open text-2xl mb-2"></i>
                    <p>Aucune matière disponible pour la sélection actuelle</p>
                </div>
            `;
            return;
        }

        let html = `
            <div class="bg-white rounded-lg border border-gray-200 max-h-96 overflow-y-auto">
                <div class="p-3 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center">
                        <input type="checkbox" id="select-all-matieres" class="w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2">
                        <label for="select-all-matieres" class="ml-2 text-sm font-medium text-gray-700">
                            Sélectionner toutes les matières
                        </label>
                    </div>
                </div>
                <div class="p-3 space-y-4">
        `;

        // Parcourir chaque UE et ses ECUE
        matieres.forEach(ue => {
            // En-tête de l'UE
            html += `
                <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-book text-blue-600 mr-2"></i>
                        <h4 class="font-semibold text-blue-900">${ue.lib_ue}</h4>
                        <span class="ml-auto text-sm text-blue-700 bg-blue-100 px-2 py-1 rounded">${ue.niveau}</span>
                    </div>
                    <div class="space-y-2">
            `;

            // Parcourir les ECUE de cette UE
            ue.ecues.forEach(ecue => {
                html += `
                    <div class="flex items-center p-2 bg-white rounded border border-gray-200 hover:bg-gray-50 transition-colors">
                        <input type="checkbox" name="selected_matieres[]" 
                               value="${ecue.id_ecue}" 
                               class="matiere-checkbox w-4 h-4 text-purple-600 bg-gray-100 border-gray-300 rounded focus:ring-purple-500 focus:ring-2">
                        <div class="ml-3 flex-1">
                            <div class="font-medium text-gray-900">${ecue.lib_ecue}</div>
                            <div class="text-sm text-gray-500">
                                Crédits: ${ecue.credit_ecue || 0} | Prix: ${ecue.prix_matiere_cheval || 25000} FCFA
                                ${ecue.credit_ecue === 0 ? '<span class="text-red-500 text-xs">(Crédit non défini)</span>' : ''}
                                ${!ecue.prix_matiere_cheval ? '<span class="text-orange-500 text-xs">(Prix par défaut)</span>' : ''}
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;
        });

        html += `
                </div>
            </div>
            <div class="mt-3 text-sm text-gray-600">
                <span id="matieres-count">0</span> matière(s) sélectionnée(s)
            </div>
        `;

        matieresSection.innerHTML = html;

        // Ajouter les événements pour les nouvelles checkboxes
        const selectAllMatieres = document.getElementById('select-all-matieres');
        const matiereCheckboxes = document.querySelectorAll('.matiere-checkbox');

        selectAllMatieres.addEventListener('change', function() {
            matiereCheckboxes.forEach(cb => cb.checked = this.checked);
            updateMatieresCount();
        });

        matiereCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateMatieresCount();
                updateSelectAllMatieresState();
            });
        });
    }

    function updateMatieresCount() {
        const selectedCount = document.querySelectorAll('.matiere-checkbox:checked').length;
        const countSpan = document.getElementById('matieres-count');
        if (countSpan) {
            countSpan.textContent = selectedCount;
        }
    }

    function updateSelectAllMatieresState() {
        const selectAllMatieres = document.getElementById('select-all-matieres');
        const matiereCheckboxes = document.querySelectorAll('.matiere-checkbox');
        const checkedMatieres = document.querySelectorAll('.matiere-checkbox:checked');
        
        if (matiereCheckboxes.length > 0) {
            selectAllMatieres.checked = checkedMatieres.length === matiereCheckboxes.length;
            selectAllMatieres.indeterminate = checkedMatieres.length > 0 && checkedMatieres.length < matiereCheckboxes.length;
        }
    }

    // Validation du formulaire
    document.getElementById('inscriptionChevalForm').addEventListener('submit', function(e) {
        const selectedEtudiants = document.querySelectorAll('.etudiant-checkbox:checked');
        const selectedMatieres = document.querySelectorAll('.matiere-checkbox:checked');
        
        if (selectedEtudiants.length === 0) {
            e.preventDefault();
            alert('Veuillez sélectionner au moins un étudiant.');
            return false;
        }
        
        if (selectedMatieres.length === 0) {
            e.preventDefault();
            alert('Veuillez sélectionner au moins une matière de rattrapage.');
            return false;
        }
    });
});
</script> 