<!-- Modal de détails étudiant à cheval -->
<div class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-warning/10 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-horse text-warning text-lg"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900">Détails Étudiant à Cheval</h3>
                </div>
                <a href="?page=etudiants" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </a>
            </div>
        </div>

        <div class="p-6">
            <?php if ($detail_data['etudiant']): ?>
            <!-- Informations de l'étudiant -->
            <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-6 rounded-xl mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Informations de l'étudiant</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <span class="text-sm font-medium text-gray-600">Nom complet:</span>
                        <p class="text-gray-900"><?php echo htmlspecialchars($detail_data['etudiant']['nom_etd'] . ' ' . $detail_data['etudiant']['prenom_etd']); ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Email:</span>
                        <p class="text-gray-900"><?php echo htmlspecialchars($detail_data['etudiant']['email_etd']); ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Niveau:</span>
                        <p class="text-gray-900"><?php echo htmlspecialchars($detail_data['etudiant']['lib_niv_etd']); ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Promotion:</span>
                        <p class="text-gray-900"><?php echo htmlspecialchars($detail_data['etudiant']['lib_promotion'] ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Année académique:</span>
                        <p class="text-gray-900"><?php echo htmlspecialchars($detail_data['inscription']['annee_ac'] ?? ''); ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Statut passage:</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $detail_data['peut_passer'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <i class="<?php echo $detail_data['peut_passer'] ? 'fas fa-check-circle' : 'fas fa-times-circle'; ?> mr-1"></i>
                            <?php echo $detail_data['peut_passer'] ? 'Peut passer' : 'Ne peut pas passer'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($detail_data['inscription']): ?>
            <!-- Informations d'inscription -->
            <div class="bg-gradient-to-r from-orange-50 to-orange-100 p-6 rounded-xl mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Informations d'inscription</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <span class="text-sm font-medium text-gray-600">Promotion principale:</span>
                        <p class="text-gray-900"><?php echo htmlspecialchars($detail_data['inscription']['lib_promotion']); ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Nombre de matières:</span>
                        <p class="text-gray-900"><?php echo $detail_data['inscription']['nombre_matieres_rattrapage']; ?></p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Montant d'inscription:</span>
                        <p class="text-gray-900 font-semibold"><?php echo number_format($detail_data['inscription']['montant_inscription'], 0, ',', ' '); ?> FCFA</p>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-600">Statut paiement:</span>
                        <?php 
                        $statut_class = '';
                        $statut_icon = '';
                        switch ($detail_data['inscription']['statut_paiement']) {
                            case 'Complet':
                                $statut_class = 'bg-green-100 text-green-800';
                                $statut_icon = 'fas fa-check-circle';
                                break;
                            case 'Partiel':
                                $statut_class = 'bg-yellow-100 text-yellow-800';
                                $statut_icon = 'fas fa-clock';
                                break;
                            case 'En attente':
                                $statut_class = 'bg-red-100 text-red-800';
                                $statut_icon = 'fas fa-exclamation-circle';
                                break;
                        }
                        ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statut_class; ?>">
                            <i class="<?php echo $statut_icon; ?> mr-1"></i>
                            <?php echo $detail_data['inscription']['statut_paiement']; ?>
                        </span>
                    </div>
                </div>
                <?php if (!empty($detail_data['inscription']['commentaire'])): ?>
                <div class="mt-4">
                    <span class="text-sm font-medium text-gray-600">Commentaire:</span>
                    <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($detail_data['inscription']['commentaire']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Matières à rattraper -->
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">Matières à rattraper</h4>
                    <a href="?page=etudiants&action=ajouter-matiere&num_etd=<?php echo $detail_data['etudiant']['num_etd']; ?>&id_ac=<?php echo $detail_data['id_ac']; ?>" 
                       class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                        <i class="fas fa-plus mr-2"></i>
                        Ajouter une matière
                    </a>
                </div>

                <?php if ($detail_data['matieres_rattrapage']): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matière</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion d'origine</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion actuelle</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($detail_data['matieres_rattrapage'] as $matiere): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($matiere['nom_ecue']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($matiere['code_ecue']); ?> (<?php echo $matiere['credit_ecue']; ?> crédits)</div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($matiere['promotion_origine']); ?></td>
                                <td class="px-4 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($matiere['promotion_actuelle']); ?></td>
                                <td class="px-4 py-4">
                                    <?php 
                                    $statut_class = '';
                                    $statut_icon = '';
                                    switch ($matiere['statut']) {
                                        case 'Validée':
                                            $statut_class = 'bg-green-100 text-green-800';
                                            $statut_icon = 'fas fa-check-circle';
                                            break;
                                        case 'En cours':
                                            $statut_class = 'bg-yellow-100 text-yellow-800';
                                            $statut_icon = 'fas fa-clock';
                                            break;
                                        case 'Échouée':
                                            $statut_class = 'bg-red-100 text-red-800';
                                            $statut_icon = 'fas fa-times-circle';
                                            break;
                                    }
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statut_class; ?>">
                                        <i class="<?php echo $statut_icon; ?> mr-1"></i>
                                        <?php echo $matiere['statut']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="updateStatutMatiere(<?php echo $matiere['id_rattrapage']; ?>, 'Validée')" 
                                                class="text-green-600 hover:text-green-900 p-1 rounded hover:bg-green-50 transition-colors"
                                                title="Marquer comme validée">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button onclick="updateStatutMatiere(<?php echo $matiere['id_rattrapage']; ?>, 'Échouée')" 
                                                class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors"
                                                title="Marquer comme échouée">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button onclick="supprimerMatiere(<?php echo $matiere['id_rattrapage']; ?>)" 
                                                class="text-gray-600 hover:text-gray-900 p-1 rounded hover:bg-gray-50 transition-colors"
                                                title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-book text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune matière à rattraper</h3>
                    <p class="text-gray-500">Aucune matière n'a été ajoutée pour cet étudiant.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Historique des inscriptions -->
            <?php if ($detail_data['historique']): ?>
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-4">Historique des inscriptions</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matières</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($detail_data['historique'] as $inscription): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($inscription['annee_ac']); ?></td>
                                <td class="px-4 py-4 text-sm text-gray-900"><?php echo htmlspecialchars($inscription['lib_promotion']); ?></td>
                                <td class="px-4 py-4 text-sm text-gray-900"><?php echo $inscription['nombre_matieres_rattrapage']; ?></td>
                                <td class="px-4 py-4 text-sm text-gray-900"><?php echo number_format($inscription['montant_inscription'], 0, ',', ' '); ?> FCFA</td>
                                <td class="px-4 py-4 text-sm text-gray-500"><?php echo date('d/m/Y', strtotime($inscription['date_inscription'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <div class="flex gap-3 justify-end pt-6 border-t border-gray-200">
                <a href="?page=etudiants" 
                   class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Retour à la liste
                </a>
                <a href="?page=etudiants&action=modifier-inscription&num_etd=<?php echo $detail_data['etudiant']['num_etd']; ?>&id_ac=<?php echo $detail_data['id_ac']; ?>" 
                   class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-edit mr-2"></i>
                    Modifier l'inscription
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Fonction pour mettre à jour le statut d'une matière
function updateStatutMatiere(id_rattrapage, statut) {
    if (!confirm('Êtes-vous sûr de vouloir changer le statut de cette matière ?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id_rattrapage', id_rattrapage);
    formData.append('statut', statut);

    fetch('?page=etudiants&action=update-statut-matiere', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la mise à jour', 'error');
    });
}

// Fonction pour supprimer une matière
function supprimerMatiere(id_rattrapage) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette matière ?')) {
        return;
    }

    const formData = new FormData();
    formData.append('id_rattrapage', id_rattrapage);

    fetch('?page=etudiants&action=supprimer-matiere', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification(data.error, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Erreur lors de la suppression', 'error');
    });
}
</script> 