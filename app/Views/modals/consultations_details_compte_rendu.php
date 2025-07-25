<!-- Modal pour les fiches de validation  -->
<div class="modal" id="details-bilan-modal" style="display: none;">
            <div class="modal-content">
                <div class="top-text">
                    <h2 class="modal-title"><i class="fas fa-file-alt"></i> Fiche de validation</h2>
                    <button class="close-modal-btn close" onclick="closeModal('details-bilan-modal')">×</button>
                </div>

                <div class="step-action" style="flex: 100%;">
                    <div class="doc-preview">
                        <div class="doc-content">
                            <div class="doc-section">
                                <h3>Informations générales</h3>
                                <table class="info-table">
                                    <tr>
                                        <td><strong>Rapport N°:</strong></td>
                                        <td id="cr-rapport-id"> <?php echo $rapport['id_rapport_etd']; ?></td>
                                        <td><strong>Titre:</strong></td>
                                        <td id="cr-titre"><?php echo $rapport['theme_memoire']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Nom de l'étudiant:</strong></td>
                                        <td id="cr-etudiant-nom"> <?php echo $rapport['nom_etd']; ?></td>
                                        <td><strong>Prénoms de l'étudiant:</strong></td>
                                        <td id="cr-etudiant-prenom"> <?php echo  $rapport['prenom_etd']; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Date de soumission:</strong></td>
                                        <td id="cr-date-soumission"> <?php echo $rapport['date_depot']; ?></td>
                                        <td><strong>Date de validation:</strong></td>
                                        <td id="cr-date-validation"> <?php echo $rapport['date_validation']; ?></td>
                                    </tr>

                                </table>
                            </div>

                            <div class="doc-section">
                                <h3>Évaluation</h3>
                                <p><strong>Décision:</strong> <span class="decision-highlight" id="cr-decision"><?php echo $rapport['statut_rapport']; ?></span></p>
                                <p><strong>Commentaire d'approbation initial:</strong></p>
                                <div class="comment-box" id="cr-commentaire-initial"><?php echo $rapport['com_appr']; ?></div>

                                <p><strong>Commentaire final:</strong></p>
                                <div class="comment-box" id="cr-commentaire-final">
                                    <?php
                                    $id_rapport = $_GET['id'];
                                    $rapport = $pdo->prepare('SELECT v.id_ens, v.com_validation, ens.id_ens, ens.nom_ens, ens.prenoms_ens FROM valider v 
                                                                    JOIN enseignants ens ON ens.id_ens = v.id_ens
                                                                    WHERE id_rapport_etd = :id_rapport');
                                    $rapport->execute(['id_rapport' => $id_rapport]);
                                    $com_validation = $rapport->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($com_validation as $com) : ?>
                                        <?php echo '<strong>' . $com['nom_ens'] . ' ' . $com['prenoms_ens'] . '</strong>' . ' a dit :' . ' ' . $com['com_validation'] . '<br>'; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="doc-footer">
                                <p>Ce document certifie que le rapport mentionné ci-dessus a été examiné conformément aux procédures d'évaluation en vigueur à l'UFHB.</p>
                                <div class="signature-line">
                                    <?php
                                    $recupRespoCr = $pdo->prepare('SELECT ens.id_ens, ens.nom_ens, ens.prenoms_ens FROM enseignants ens
                                                                        JOIN responsable_compte_rendu rcr ON rcr.id_ens = ens.id_ens 
                                                                        WHERE actif = 1');
                                    $recupRespoCr->execute();
                                    $respo = $recupRespoCr->fetch();
                                    ?>
                                    <div class="signature-name" id="cr-signature-name"><strong>Édité par: </strong><?php echo $respo['nom_ens'] . ' ' . $respo['prenoms_ens'] ?></div>
                                    <div class="signature-date" id="cr-signature-date">Date: <?php echo date('Y-m-d'); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>