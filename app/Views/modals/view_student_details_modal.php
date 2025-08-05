<?php
// Calcul de l'âge
$age = null;
if ($etudiant_details['etudiant']['date_naissance_etd']) {
    $dateNaissance = new DateTime($etudiant_details['etudiant']['date_naissance_etd']);
    $aujourdhui = new DateTime();
    $age = $aujourdhui->diff($dateNaissance)->y;
}

// Détermination du niveau d'étude
$niveauEtude = $etudiant_details['etudiant']['lib_niv_etd'] ?? "Non renseigné";

// Statut du stage
$statutStage = $etudiant_details['stage'] ? "En stage" : "Pas de stage déclaré";

// Statut de la scolarité
if (isset($etudiant_details['scolarite']['reste_a_payer'])) {
    if ($etudiant_details['scolarite']['reste_a_payer'] > 0) {
        $scolariteAJour = "Paiement partiel (Reste à payer: " . $etudiant_details['scolarite']['reste_a_payer'] . " FCFA)";
    } elseif ($etudiant_details['scolarite']['reste_a_payer'] == 0) {
        $scolariteAJour = "Soldé";
    }
} else {
    $scolariteAJour = "Non payé";
}
?>

<!-- Modal de détails de l'étudiant -->
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50" id="view-student-details-modal">
    <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto animate-bounce-in">
        <!-- En-tête de la modale -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 bg-gradient-to-r from-primary to-primary-light text-white rounded-t-xl">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-user-check text-xl"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold">
                        Détails de l'Étudiant
                    </h2>
                    <p class="text-white/80 text-sm">
                        <?php echo $etudiant_details['etudiant']['prenom_etd'] . " " . $etudiant_details['etudiant']['nom_etd']; ?>
                    </p>
                </div>
            </div>
            <button class="text-white/80 hover:text-white transition-colors duration-200" id="close-modal-details-btn">
                <i class="fas fa-times text-2xl"></i>
            </button>
        </div>

        <!-- Contenu de la modale -->
        <div class="p-6">
            <!-- Section Informations Générales -->
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-info-circle text-primary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Informations Générales</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Nom complet</label>
                        <span class="text-lg font-semibold text-gray-900">
                            <?php echo $etudiant_details['etudiant']['prenom_etd'] . " " . $etudiant_details['etudiant']['nom_etd']; ?>
                        </span>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">N° Carte Étudiant</label>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-id-card mr-2"></i>
                            <?php echo $etudiant_details['etudiant']['num_carte_etd']; ?>
                        </span>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                        <span class="text-lg text-gray-900 flex items-center">
                            <i class="fas fa-envelope mr-2 text-gray-400"></i>
                            <?php echo $etudiant_details['etudiant']['email_etd']; ?>
                        </span>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Téléphone</label>
                        <span class="text-lg text-gray-900 flex items-center">
                            <i class="fas fa-phone mr-2 text-gray-400"></i>
                            <?php echo $etudiant_details['etudiant']['num_tel_etd'] ? $etudiant_details['etudiant']['num_tel_etd'] : "Non renseigné"; ?>
                        </span>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Sexe</label>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $etudiant_details['etudiant']['sexe_etd'] === 'Homme' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'; ?>">
                            <i class="fas <?php echo $etudiant_details['etudiant']['sexe_etd'] === 'Homme' ? 'fa-mars' : 'fa-venus'; ?> mr-2"></i>
                            <?php echo $etudiant_details['etudiant']['sexe_etd'] ? $etudiant_details['etudiant']['sexe_etd'] : "Non renseigné"; ?>
                        </span>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Date de naissance</label>
                        <span class="text-lg text-gray-900 flex items-center">
                            <i class="fas fa-calendar mr-2 text-gray-400"></i>
                            <?php echo $etudiant_details['etudiant']['date_naissance_etd'] ? date('d/m/Y', strtotime($etudiant_details['etudiant']['date_naissance_etd'])) : "Non renseigné"; ?>
                        </span>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Âge</label>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-birthday-cake mr-2"></i>
                            <?php echo $age ? $age . " ans" : "Non renseigné"; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Section Scolarité -->
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-secondary/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-graduation-cap text-secondary"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Scolarité</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border-l-4 border-blue-500">
                        <label class="block text-sm font-medium text-blue-700 mb-1">Promotion</label>
                        <span class="text-lg font-semibold text-blue-900">
                            <?php echo $etudiant_details['promotion'] ? htmlspecialchars($etudiant_details['promotion']) : 'Non renseignée'; ?>
                        </span>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border-l-4 border-green-500">
                        <label class="block text-sm font-medium text-green-700 mb-1">Niveau d'étude</label>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-200 text-green-800">
                            <i class="fas fa-layer-group mr-2"></i>
                            <?php echo $niveauEtude; ?>
                        </span>
                    </div>
                    
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 border-l-4 border-orange-500">
                        <label class="block text-sm font-medium text-orange-700 mb-1">Statut scolarité</label>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                            <?php 
                            if (strpos($scolariteAJour, 'Soldé') !== false) {
                                echo 'bg-green-200 text-green-800';
                            } elseif (strpos($scolariteAJour, 'partiel') !== false) {
                                echo 'bg-yellow-200 text-yellow-800';
                            } else {
                                echo 'bg-red-200 text-red-800';
                            }
                            ?>">
                            <i class="fas fa-credit-card mr-2"></i>
                            <?php echo $scolariteAJour; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Section Stage (si disponible) -->
            <?php if (isset($etudiant_details['stage'])): ?>
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-accent/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-briefcase text-accent"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Stage</h3>
                </div>
                
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border-l-4 border-purple-500">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                        <?php echo $etudiant_details['stage'] ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-800'; ?>">
                        <i class="fas <?php echo $etudiant_details['stage'] ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-2"></i>
                        <?php echo $statutStage; ?>
                    </span>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pied de la modale -->
        <div class="flex justify-end p-6 border-t border-gray-200 bg-gray-50 rounded-b-xl">
            <button type="button" 
                    class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors duration-200 flex items-center"
                    id="close-modal-details-btn-2">
                <i class="fas fa-times mr-2"></i>
                Fermer
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestionnaires pour fermer la modale
    const closeButtons = document.querySelectorAll('#close-modal-details-btn, #close-modal-details-btn-2');
    const modal = document.getElementById('view-student-details-modal');
    
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