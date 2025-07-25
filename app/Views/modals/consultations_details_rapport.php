<div class="modal" id="rapport-modal" style="display: block;">
    <div class="modal-content" style="height: 100%;">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-file-signature"></i> Détails du rapport</h2>
            <button class="close-btn" id="close-view-modal"><i class="fas fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="rapport-card">
                <div class="rapport-header">
                    <div class="rapport-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="rapport-title">
                        <h3><?php echo htmlspecialchars($rapport['nom_rapport']); ?></h3>
                        <p class="rapport-author">Par <?php echo htmlspecialchars($rapport['nom_etd'] . ' ' . $rapport['prenom_etd']); ?></p>
                    </div>
                    <div class="rapport-status">
                        <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $rapport['statut_rapport'])); ?>">
                            <?php echo htmlspecialchars($rapport['statut_rapport']); ?>
                        </span>
                    </div>
                </div>

                <div class="rapport-details">
                    <div class="detail-group">
                        <div class="detail-item">
                            <span class="detail-label"><i class="fas fa-hashtag"></i> Numéro du rapport</span>
                            <span class="detail-value" id="rap-numero"><?php echo htmlspecialchars($rapport['id_rapport_etd']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label"><i class="fas fa-calendar-plus"></i> Soumission</span>
                            <span class="detail-value" id="rap-date"><?php echo $rapport['date_depot'] ? date('d/m/Y', strtotime($rapport['date_depot'])) : '-'; ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label"><i class="fas fa-calendar-check"></i> Validation</span>
                            <span class="detail-value" id="rap-date-validation"><?php echo $rapport['date_validation'] ? date('d/m/Y', strtotime($rapport['date_validation'])) : '-'; ?></span>
                        </div>
                    </div>

                    <?php if ($rapport['theme_memoire']) : ?>
                        <div class="rapport-section">
                            <h4><i class="fas fa-bookmark"></i> Thème du mémoire</h4>
                            <div class="rapport-text">
                                <p><?php echo htmlspecialchars($rapport['theme_memoire']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($rapport['com_appr'] || $rapport['com_validation']) : ?>
                        <div class="rapport-section">
                            <h4><i class="fas fa-comments"></i> Commentaires du personnel administratif</h4>

                            <?php if ($rapport['com_appr']) : ?>
                                <div class="comment-box">
                                    <div class="comment-content">
                                        <p><?php echo htmlspecialchars($rapport['com_appr']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>


                        </div>
                    <?php endif; ?>

                    <?php
                    $stmtEvaluateurs = $pdo->prepare("
                                    SELECT ens.nom_ens, ens.prenoms_ens, v.com_validation, v.decision
                                    FROM valider v
                                    JOIN enseignants ens ON ens.id_ens = v.id_ens
                                    WHERE v.id_rapport_etd = ?");
                    $stmtEvaluateurs->execute([$rapport['id_rapport_etd']]);
                    $evaluateurs = $stmtEvaluateurs->fetchAll(PDO::FETCH_ASSOC);

                    if ($evaluateurs):
                    ?>
                        <div class="rapport-section">
                            <h4><i class="fas fa-user-check"></i> Évaluateurs</h4>
                            <div class="evaluateurs-list">
                                <?php foreach ($evaluateurs as $eval):
                                    $status_class = '';
                                    switch ($eval['decision']) {
                                        case 'Validé':
                                            $status_class = 'status-validated';
                                            break;
                                        case 'Rejeté':
                                            $status_class = 'status-rejected';
                                            break;
                                        case 'En attente de validation':
                                            $status_class = 'status-pending';
                                            break;
                                    }
                                ?>
                                    <div class="evaluateur-card">
                                        <div class="evaluateur-info">
                                            <span class="evaluateur-name"><?php echo htmlspecialchars($eval['nom_ens'] . ' ' . $eval['prenoms_ens']); ?></span>
                                            <span class="<?php echo $status_class; ?>  evaluateur-decision decision-<?php echo strtolower(str_replace(' ', '-', $eval['decision'] ?: 'en-attente')); ?>">
                                                <?php echo htmlspecialchars($eval['decision'] ?: 'En attente'); ?>
                                            </span>
                                        </div>
                                        <?php if ($eval['com_validation']): ?>
                                            <div class="evaluateur-comment">
                                                <p><?php echo htmlspecialchars($eval['com_validation']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>