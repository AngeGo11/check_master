<!-- Modal d'ajout d'étudiant -->
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" id="add-student-modal">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto animate-bounce-in">
        <!-- En-tête de la modale -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-accent to-green-500 text-white rounded-t-xl">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-user-plus text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold">
                        Ajouter un Étudiant
                    </h2>
                    <p class="text-white/80 text-sm">
                        Créer un nouveau compte étudiant
                    </p>
                </div>
            </div>
            <button class="text-white/80 hover:text-white transition-colors duration-200" id="close-modal-btn">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Formulaire -->
        <form action="?page=etudiants&action=add_process" method="POST" id="std-form" enctype="multipart/form-data" class="p-6">
            <!-- Première section - Informations académiques -->
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-accent/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-graduation-cap text-accent"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Informations Académiques</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-group">
                        <label for="card" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-id-card mr-2 text-gray-500"></i>
                            N° carte étudiant
                        </label>
                        <input type="text" 
                               id="card" 
                               name="card" 
                               placeholder="Saisissez le numéro de carte étudiant ici..." 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent transition-colors duration-200"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="niveau" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-layer-group mr-2 text-gray-500"></i>
                            Niveau d'étude
                        </label>
                        <select name="id_niv_etd" 
                                id="niveau" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent transition-colors duration-200"
                                required>
                            <option value="">Sélectionnez un niveau d'étude</option>
                            <?php foreach ($lists['niveaux'] as $niv): ?>
                                <option value="<?php echo $niv['id_niv_etd']; ?>"><?php echo htmlspecialchars($niv['lib_niv_etd']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="promotion" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-users mr-2 text-gray-500"></i>
                            Promotion
                        </label>
                        <select name="id_promotion" 
                                id="promotion" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-transparent transition-colors duration-200"
                                required>
                            <option value="">Sélectionnez une promotion</option>
                            <?php foreach ($lists['promotions'] as $promo): ?>
                                <option value="<?php echo $promo['id_promotion']; ?>"><?php echo htmlspecialchars($promo['lib_promotion']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Deuxième section - Informations personnelles -->
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-primary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Informations Personnelles</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="form-group">
                        <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-gray-500"></i>
                            Nom de l'étudiant
                        </label>
                        <input type="text" 
                               id="nom" 
                               name="nom" 
                               placeholder="Saisissez le nom de l'étudiant ici..." 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-200"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenoms" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-gray-500"></i>
                            Prénoms de l'étudiant
                        </label>
                        <input type="text" 
                               id="prenoms" 
                               name="prenoms" 
                               placeholder="Saisissez le prénom de l'étudiant ici..." 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-200"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-gray-500"></i>
                            Email de l'étudiant
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               placeholder="Saisissez l'email de l'étudiant ici..." 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-colors duration-200"
                               required>
                    </div>
                </div>
            </div>

            <!-- Troisième section - Informations complémentaires -->
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-secondary/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-info-circle text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Informations Complémentaires</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-2 text-gray-500"></i>
                            Date de naissance
                        </label>
                        <input type="date" 
                               name="date" 
                               id="date"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent transition-colors duration-200">
                    </div>
                    
                    <div class="form-group">
                        <label for="sexe" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-venus-mars mr-2 text-gray-500"></i>
                            Sexe
                        </label>
                        <select name="sexe" 
                                id="sexe"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary focus:border-transparent transition-colors duration-200">
                            <option value="Homme">Homme</option>
                            <option value="Femme">Femme</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" 
                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 flex items-center"
                        id="close-modal-btn-2">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </button>
                <button type="submit" 
                        class="px-6 py-3 bg-accent text-white rounded-lg hover:bg-green-600 transition-colors duration-200 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter cet étudiant
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaires pour fermer la modale
    const closeButtons = document.querySelectorAll('#close-modal-btn, #close-modal-btn-2');
    const modal = document.getElementById('add-student-modal');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            modal.classList.add('hidden');
        });
    });
    
    // Fermer la modale en cliquant à l'extérieur
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
    
    // Fermer avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            modal.classList.add('hidden');
        }
    });
});
</script> 