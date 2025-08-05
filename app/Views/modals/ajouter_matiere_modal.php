<!-- Modal d'ajout de matière à rattraper -->
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-plus text-green-600 text-lg"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Ajouter une matière à rattraper</h3>
                </div>
                <a href="?page=etudiants&action=detail-cheval&num_etd=<?php echo $ajouter_matiere_data['num_etd']; ?>&id_ac=<?php echo $ajouter_matiere_data['id_ac']; ?>" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </a>
            </div>
        </div>

        <form method="post" action="?page=etudiants&action=ajouter-matiere-rattrapage" class="p-6">
            <input type="hidden" name="num_etd" value="<?php echo $ajouter_matiere_data['num_etd']; ?>">
            <input type="hidden" name="id_ac" value="<?php echo $ajouter_matiere_data['id_ac']; ?>">

            <!-- Informations de l'étudiant -->
            <?php if ($ajouter_matiere_data['etudiant']): ?>
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <h4 class="font-medium text-gray-900 mb-2">Informations de l'étudiant</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium">Nom:</span> 
                        <?php echo htmlspecialchars($ajouter_matiere_data['etudiant']['nom_etd'] . ' ' . $ajouter_matiere_data['etudiant']['prenom_etd']); ?>
                    </div>
                    <div>
                        <span class="font-medium">Email:</span> 
                        <?php echo htmlspecialchars($ajouter_matiere_data['etudiant']['email_etd']); ?>
                    </div>
                    <div>
                        <span class="font-medium">Niveau:</span> 
                        <?php echo htmlspecialchars($ajouter_matiere_data['etudiant']['lib_niv_etd']); ?>
                    </div>
                    <div>
                        <span class="font-medium">Promotion:</span> 
                        <?php echo htmlspecialchars($ajouter_matiere_data['etudiant']['lib_promotion'] ?? 'N/A'); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Matière -->
                <div>
                    <label for="id_ecue" class="block text-sm font-medium text-gray-700 mb-2">
                        Matière à rattraper <span class="text-red-500">*</span>
                    </label>
                    <select name="id_ecue" id="id_ecue" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Sélectionner une matière</option>
                        <?php foreach ($ajouter_matiere_data['ecues'] as $ecue): ?>
                            <option value="<?php echo $ecue['id_ecue']; ?>">
                                <?php echo htmlspecialchars($ecue['code_ecue'] . ' - ' . $ecue['nom_ecue'] . ' (' . $ecue['credit_ecue'] . ' crédits)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Promotion d'origine -->
                <div>
                    <label for="promotion_origine" class="block text-sm font-medium text-gray-700 mb-2">
                        Promotion d'origine <span class="text-red-500">*</span>
                    </label>
                    <select name="promotion_origine" id="promotion_origine" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Sélectionner une promotion</option>
                        <?php foreach ($ajouter_matiere_data['promotions'] as $promotion): ?>
                            <option value="<?php echo $promotion['id_promotion']; ?>">
                                <?php echo htmlspecialchars($promotion['lib_promotion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Promotion actuelle -->
                <div>
                    <label for="promotion_actuelle" class="block text-sm font-medium text-gray-700 mb-2">
                        Promotion actuelle <span class="text-red-500">*</span>
                    </label>
                    <select name="promotion_actuelle" id="promotion_actuelle" required 
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">Sélectionner une promotion</option>
                        <?php foreach ($ajouter_matiere_data['promotions'] as $promotion): ?>
                            <option value="<?php echo $promotion['id_promotion']; ?>">
                                <?php echo htmlspecialchars($promotion['lib_promotion']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="flex gap-3 justify-end mt-6 pt-6 border-t border-gray-200">
                <a href="?page=etudiants&action=detail-cheval&num_etd=<?php echo $ajouter_matiere_data['num_etd']; ?>&id_ac=<?php echo $ajouter_matiere_data['id_ac']; ?>" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Annuler
                </a>
                <button type="submit" 
                        class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter la matière
                </button>
            </div>
        </form>
    </div>
</div> 