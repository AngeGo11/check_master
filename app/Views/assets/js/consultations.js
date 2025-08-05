document.addEventListener('DOMContentLoaded', function() {
    // ==============================================
    // Variables globales
    // ==============================================
    let quill;
    let currentReportData = null;

    // ==============================================
    // Fonctions utilitaires
    // ==============================================

    // Mise à jour du statut avec message
    function setStatus(message, isSuccess = true) {
        const statusBox = document.getElementById('status');
        if (statusBox) {
            statusBox.textContent = message;
            statusBox.style.color = isSuccess ? 'green' : 'red';
        }
    }

    // Fonction pour fermer une modal spécifique
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('open');
        }
    }

    // Fonction pour ouvrir une modal spécifique
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('open');
        }
    }

    // Fonction pour télécharger un fichier PDF
    function downloadFile(type, id) {
        if (!type || !id) {
            alert('Informations manquantes pour le téléchargement');
            return;
        }

        // Créer un formulaire caché pour envoyer la requête
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'assets/download/download_file.php';

        // Ajouter les champs cachés
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = type;

        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;

        // Ajouter les champs au formulaire
        form.appendChild(typeInput);
        form.appendChild(idInput);

        // Ajouter le formulaire au document et le soumettre
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // ==============================================
    // Gestion de l'éditeur Quill
    // ==============================================

    // Initialisation de l'éditeur Quill
    function initializeQuillEditor() {
        if (typeof Quill === 'undefined') {
            console.error("Quill n'est pas chargé");
            return;
        }

        const editorElement = document.getElementById('editor');
        if (!editorElement) return;

        // Vérifier si Quill est déjà initialisé
        if (editorElement.querySelector('.ql-editor')) {
            return;
        }

        try {
            quill = new Quill('#editor', {
                theme: 'snow',
                placeholder: 'Chargez le modèle pour commencer la rédaction...',
                modules: {
                    toolbar: '#toolbar'
                }
            });

            window.quill = quill; // Rendre accessible globalement

        } catch (error) {
            console.error("Erreur lors de l'initialisation de Quill:", error);
        }
    }

    // Personnalisation du modèle avec les données du rapport
    function personalizeTemplate() {
        const reportData = currentReportData || JSON.parse(sessionStorage.getItem('current_report') || '{}');

        if (quill && reportData.id_rapport) {
            const content = quill.root.innerHTML;
            const personalizedContent = content
                .replace(/\[ID_RAPPORT\]/g, reportData.id_rapport || '')
                .replace(/\[NOM_ETUDIANT\]/g, (reportData.nom_etd || '') + ' ' + (reportData.prenom_etd || ''))
                .replace(/\[TITRE_RAPPORT\]/g, reportData.nom_rapport || '')
                .replace(/\[DATE_SOUMISSION\]/g, reportData.date_depot || '')
                .replace(/\[VALIDATEUR\]/g, fullUserName || 'Validateur')
                .replace(/\[DATE_VALIDATION\]/g, new Date().toLocaleDateString('fr-FR'));

            quill.root.innerHTML = personalizedContent;
        }
    }

    // ==============================================
    // Gestion des modales et des comptes rendus
    // ==============================================

    // Ouvrir la modal de création avec les données du rapport
    function openCreateBilanModal(reportData) {
        currentReportData = reportData;

        // Stocker les données dans la session
        sessionStorage.setItem('current_report', JSON.stringify(reportData));

        // Ouvrir la modal
        const modal = document.getElementById('create-bilan-modal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    // Configuration des gestionnaires d'événements pour les boutons modaux
    function setupModalEventHandlers() {
        // === Modal des rapports ===
        const viewButtons = document.querySelectorAll('.view-rapport-button');
        const rapportModal = document.getElementById('rapport-modal');
        const closeViewModal = document.getElementById('close-view-modal');

        // Gestion des boutons de vue
        viewButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                // Ne pas empêcher le comportement par défaut pour permettre la mise à jour de l'URL
                if (rapportModal) {
                    rapportModal.style.display = 'block';
                }
            });
        });

        // Gestion de la fermeture
        if (closeViewModal) {
            closeViewModal.addEventListener('click', function() {
                if (rapportModal) {
                    rapportModal.style.display = 'none';
                    // Mettre à jour l'URL pour enlever les paramètres
                    window.history.pushState({}, '', '?page=consultations');
                }
            });
        }

        // Fermer la modal en cliquant en dehors
        window.addEventListener('click', function(event) {
            if (event.target === rapportModal) {
                rapportModal.style.display = 'none';
                // Mettre à jour l'URL pour enlever les paramètres
                window.history.pushState({}, '', '?page=consultations');
            }
        });

        // === Modal de création de compte rendu ===

        // Gestion des boutons de création de compte rendu
        document.querySelectorAll('.create-cr-button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const reportData = {
                    id_rapport: this.dataset.rapportId,
                    num_etd: this.dataset.numEtd,
                    nom_etd: this.dataset.nomEtd,
                    prenom_etd: this.dataset.prenomEtd,
                    nom_rapport: this.dataset.nomRapport,
                    theme_memoire: this.dataset.themeMemoire || '',
                    date_depot: this.dataset.dateDepot
                };
                openCreateBilanModal(reportData);
            });
        });

        // Bouton pour fermer la modal de création
        const closeModalBtn = document.getElementById('close-modal-create-report-btn');
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal('create-bilan-modal');
            });
        }

        // === Gestion des clics en dehors des modales ===
        window.addEventListener('click', function(event) {
            const rapportModal = document.getElementById('rapport-modal');
            const createBilanModal = document.getElementById('create-bilan-modal');
            const previewBilanModal = document.getElementById('preview-bilan-modal');
            const detailsBilanModal = document.getElementById('details-bilan-modal');

            if (event.target === rapportModal) {
                closeModal('rapport-modal');
            }
            if (event.target === createBilanModal) {
                closeModal('create-bilan-modal');
            }
            if (event.target === previewBilanModal) {
                closeModal('preview-bilan-modal');
            }
            if (event.target === detailsBilanModal) {
                closeModal('details-bilan-modal');
            }
        });
    }

    // Mise en place des fonctionnalités de l'éditeur
    function setupEditorFunctions() {
        const btnValidBilan = document.getElementById('valid-bilan');
        const btnChargeModel = document.getElementById('charge-model');
        const btnDownloadPdf = document.getElementById('btn-download-pdf');
        const btnPrintPdf = document.getElementById('print-pdf');
        const btnPreviewPdf = document.getElementById('preview-pdf');
        const btnSendEmail = document.getElementById('send-email');

        // === Chargement du modèle ===
        if (btnChargeModel) {
            btnChargeModel.onclick = async () => {
                try {
                    btnChargeModel.disabled = true;
                    btnChargeModel.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Chargement...';
                    setStatus('Chargement du modèle...');

                    const response = await fetch('assets/templates/modele_compte_rendu.docx');
                    if (!response.ok) {
                        throw new Error(`Erreur HTTP: ${response.status}`);
                    }

                    const arrayBuffer = await response.arrayBuffer();
                    const result = await mammoth.convertToHtml({
                        arrayBuffer
                    });

                    if (quill && quill.root) {
                        quill.root.innerHTML = result.value;
                        setStatus('Modèle chargé avec succès ✓');

                        // Personnaliser le template après chargement
                        personalizeTemplate();

                        // Activer les boutons
                        if (btnDownloadPdf) btnDownloadPdf.disabled = false;
                        if (btnPrintPdf) btnPrintPdf.disabled = false;
                        if (btnPreviewPdf) btnPreviewPdf.disabled = false;
                        if (btnSendEmail) btnSendEmail.disabled = false;
                        if (btnValidBilan) btnValidBilan.disabled = false;
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    setStatus(`Erreur: ${error.message}`, false);
                } finally {
                    btnChargeModel.disabled = false;
                    btnChargeModel.innerHTML = '<i class="fa-solid fa-spinner"></i> Charger le modèle';
                }
            };
        }

        // === Validation et envoi du compte rendu ===
        if (btnValidBilan) {
            btnValidBilan.onclick = async () => {
                try {
                    btnValidBilan.disabled = true;
                    setStatus('Validation et envoi en cours...');

                    // Récupérer les données du rapport actuel
                    const reportData = currentReportData || JSON.parse(sessionStorage.getItem('current_report') || '{}');

                    // Vérifier qu'on a les données nécessaires
                    if (!reportData.num_etd || !reportData.id_rapport) {
                        throw new Error('Données du rapport manquantes. Sélectionnez un rapport avant de créer un compte rendu.');
                    }

                    // Récupérer le contenu HTML de l'éditeur
                    const html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' +
                        quill.root.innerHTML + '</body></html>';

                    // Créer un élément temporaire pour la conversion
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    document.body.appendChild(tempDiv);

                    // Options pour la conversion en PDF
                    const opt = {
                        margin: 1,
                        filename: `compte_rendu_${reportData.nom_etd}_${reportData.prenom_etd}_${Date.now()}.pdf`,
                        image: {
                            type: 'jpeg',
                            quality: 0.98
                        },
                        html2canvas: {
                            scale: 2
                        },
                        jsPDF: {
                            unit: 'in',
                            format: 'a4',
                            orientation: 'portrait'
                        }
                    };

                    // Convertir en PDF
                    const pdfBlob = await html2pdf().set(opt).from(tempDiv).output('blob');
                    document.body.removeChild(tempDiv);

                    const fileName = `compte_rendu_${reportData.nom_etd}_${reportData.prenom_etd}_${Date.now()}.pdf`;

                    // Créer le FormData
                    const formData = new FormData();
                    formData.append('bilan-file', new File([pdfBlob], fileName, {
                        type: 'application/pdf'
                    }));
                    formData.append('student_id', reportData.num_etd);
                    formData.append('report_id', reportData.id_rapport);
                    formData.append('theme_memoire', reportData.theme_memoire || '');
                    formData.append('file_path', 'uploads/bilan/' + fileName);

                    // Envoyer au serveur
                    const response = await fetch('assets/uploads/upload_bilan.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'include'
                    });

                    // Lire la réponse
                    const responseText = await response.text();

                    // Parser en JSON
                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('Erreur de parsing JSON:', parseError);
                        throw new Error('Réponse invalide du serveur');
                    }

                    if (result.success) {
                        setStatus('Compte rendu créé avec succès 🎉');
                        quill.disable();

                        // Désactiver les boutons
                        if (btnDownloadPdf) btnDownloadPdf.disabled = true;
                        if (btnPrintPdf) btnPrintPdf.disabled = true;
                        if (btnPreviewPdf) btnPreviewPdf.disabled = true;
                        if (btnSendEmail) btnSendEmail.disabled = true;
                        btnValidBilan.disabled = true;

                        // Afficher un message de succès
                        setTimeout(() => {
                            // Fermer la modal
                            closeModal('create-bilan-modal');
                            // Rafraîchir la page pour voir le nouveau compte rendu
                            window.location.reload();
                        }, 1000);

                    } else {
                        throw new Error(result.message || 'Erreur lors de l\'envoi');
                    }

                } catch (error) {
                    console.error('Erreur complète:', error);
                    setStatus('Erreur : ' + error.message, false);
                    btnValidBilan.disabled = false;

                    alert('Erreur : ' + error.message);
                }
            };
        }

        // === Télécharger le document en PDF ===
        if (btnDownloadPdf) {
            btnDownloadPdf.onclick = async () => {
                try {
                    setStatus('Génération du PDF en cours...');

                    // Récupérer le contenu HTML de l'éditeur
                    const content = quill.root.innerHTML;
                    const reportData = currentReportData || JSON.parse(sessionStorage.getItem('current_report') || '{}');

                    // Créer un template HTML amélioré pour le PDF
                    const formattedContent = `
                    <div class="pdf-content">
                        <div class="pdf-header">
                            <div class="pdf-title">Compte Rendu</div>
                            <div class="pdf-subtitle">Université Félix Houphouët-Boigny</div>
                        </div>
                        
                        <div class="pdf-section">
                            <div class="pdf-section-title">Informations du rapport</div>
                            <table class="pdf-table">
                                <tr>
                                    <th>Étudiant</th>
                                    <td>${reportData.nom_etd} ${reportData.prenom_etd}</td>
                                </tr>
                                <tr>
                                    <th>Titre du rapport</th>
                                    <td>${reportData.nom_rapport}</td>
                                </tr>
                                <tr>
                                    <th>Date de soumission</th>
                                    <td>${reportData.date_depot}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="pdf-section">
                            <div class="pdf-section-title">Contenu du compte rendu</div>
                            ${content}
                        </div>
                        
                        <div class="pdf-footer">
                            <p>Document généré le ${new Date().toLocaleDateString('fr-FR')}</p>
                            <p>Université Félix Houphouët-Boigny - Tous droits réservés</p>
                        </div>
                    </div>`;

                    // Créer un élément temporaire pour la conversion
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = formattedContent;
                    document.body.appendChild(tempDiv);

                    // Options pour la conversion en PDF
                    const opt = {
                        margin: 1,
                        filename: `compte_rendu_${reportData.nom_etd}_${reportData.prenom_etd}_${Date.now()}.pdf`,
                        image: {
                            type: 'jpeg',
                            quality: 0.98
                        },
                        html2canvas: {
                            scale: 2
                        },
                        jsPDF: {
                            unit: 'in',
                            format: 'a4',
                            orientation: 'portrait'
                        }
                    };

                    // Générer et télécharger le PDF
                    await html2pdf().set(opt).from(tempDiv).save();
                    document.body.removeChild(tempDiv);

                    setStatus('PDF généré avec succès ✓');
                } catch (error) {
                    console.error('Erreur lors de la génération du PDF:', error);
                    setStatus('Erreur lors de la génération du PDF', false);
                    alert('Erreur lors de la génération du PDF: ' + error.message);
                }
            };
        }

        // === Aperçu du PDF ===
        if (btnPreviewPdf) {
            btnPreviewPdf.onclick = async () => {
                try {
                    setStatus('Génération de l\'aperçu en cours...');

                    // Récupérer le contenu HTML de l'éditeur
                    const content = quill.root.innerHTML;
                    const reportData = currentReportData || JSON.parse(sessionStorage.getItem('current_report') || '{}');

                    // Créer un template HTML amélioré pour l'aperçu
                    const formattedContent = `
                        <div class="pdf-content">
                            <div class="pdf-header">
                                <div class="pdf-title">Compte Rendu</div>
                                <div class="pdf-subtitle">Université Félix Houphouët-Boigny</div>
                            </div>
                            
                            <div class="pdf-section">
                                <div class="pdf-section-title">Informations du rapport</div>
                                <table class="pdf-table">
                                    <tr>
                                        <th>Étudiant</th>
                                        <td>${reportData.nom_etd} ${reportData.prenom_etd}</td>
                                    </tr>
                                    <tr>
                                        <th>Titre du rapport</th>
                                        <td>${reportData.nom_rapport}</td>
                                    </tr>
                                    <tr>
                                        <th>Date de soumission</th>
                                        <td>${reportData.date_depot}</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="pdf-section">
                                <div class="pdf-section-title">Contenu du compte rendu</div>
                                ${content}
                            </div>
                            
                            <div class="pdf-footer">
                                <p>Document généré le ${new Date().toLocaleDateString('fr-FR')}</p>
                                <p>Université Félix Houphouët-Boigny - Tous droits réservés</p>
                            </div>
                        </div>`;

                    // Afficher l'aperçu dans la modale
                    const previewContent = document.getElementById('preview-content');
                    if (previewContent) {
                        previewContent.innerHTML = formattedContent;
                        openModal('preview-bilan-modal');
                    }

                    setStatus('Aperçu généré avec succès ✓');
                } catch (error) {
                    console.error('Erreur lors de la génération de l\'aperçu:', error);
                    setStatus('Erreur lors de la génération de l\'aperçu', false);
                    alert('Erreur lors de la génération de l\'aperçu: ' + error.message);
                }
            };
        }

        // === Envoi par email ===
        if (btnSendEmail) {
            btnSendEmail.onclick = function() {
                // Afficher la modale d'email
                const emailModal = document.getElementById('email-modal');
                if (emailModal) {
                    emailModal.style.display = 'flex';

                    // Pré-remplir le sujet avec le nom du rapport
                    const reportData = currentReportData || JSON.parse(sessionStorage.getItem('current_report') || '{}');
                    const subjectInput = document.getElementById('email-subject');
                    if (subjectInput) {
                        subjectInput.value = `Compte rendu - ${reportData.nom_rapport || 'Rapport'}`;
                    }
                }
            };
        }
    }

    // ==============================================
    // Fonctions pour gérer les tableaux et filtres
    // ==============================================

    function setupTableFunctions() {
        // Implémentation future:
        // - Filtrage des tableaux
        // - Recherche
        // - Exportation de données
        // - Pagination

        // Exemple de mise en place de la recherche
        const searchRapport = document.getElementById('search-rapport');
        const searchCR = document.getElementById('search-cr');

        if (searchRapport) {
            searchRapport.addEventListener('input', function() {
                // Logique de recherche dans le tableau des rapports
            });
        }

        if (searchCR) {
            searchCR.addEventListener('input', function() {
                // Logique de recherche dans le tableau des comptes rendus
            });
        }

        // Boutons d'exportation
        const exportButton = document.getElementById('export-button');
        const exportCRButton = document.getElementById('export-button-export-cr');

        if (exportButton) {
            exportButton.addEventListener('click', function() {
                // Logique d'exportation des rapports
            });
        }

        if (exportCRButton) {
            exportCRButton.addEventListener('click', function() {
                // Logique d'exportation des comptes rendus
            });
        }
    }

    // ==============================================
    // Définition éventuelle du polyfill htmlDocx
    // ==============================================

    // Polyfill pour htmlDocx si nécessaire
    if (typeof htmlDocx === 'undefined' && typeof JSZip !== 'undefined') {
        window.htmlDocx = {
            asBlob: function(html) {
                const zip = new JSZip();
                const documentXml = `
    <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <w:body>
            <w:p>
                <w:r>
                    <w:t>${html.replace(/<[^>]+>/g, '')}</w:t>
                </w:r>
            </w:p>
        </w:body>
    </w:document>`;

                zip.file("word/document.xml", documentXml);
                zip.file("_rels/.rels", `
    <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
        <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
    </Relationships>`);
                zip.file("[Content_Types].xml", `
    <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
        <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
        <Default Extension="xml" ContentType="application/xml"/>
        <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    </Types>`);

                return zip.generateAsync({
                    type: "blob",
                    mimeType: "application/pdf"
                });
            }
        };
    }

    // ==============================================
    // Initialisation de l'application
    // ==============================================

    // Récupérer le nom complet de l'utilisateur pour les templates
    const fullUserName = document.querySelector('.user-name') ?
        document.querySelector('.user-name').textContent.trim() : '';

    // Initialiser l'éditeur
    initializeQuillEditor();

    // Configurer les gestionnaires d'événements modaux
    setupModalEventHandlers();

    // Configurer les fonctions de l'éditeur
    setupEditorFunctions();

    // Configurer les fonctions des tableaux et filtres
    setupTableFunctions();

    // Gestionnaire pour le nouveau bouton de fermeture de la modale d'aperçu
    const closePreviewBilanModalBtn = document.getElementById('close-preview-bilan-modal-btn');
    if (closePreviewBilanModalBtn) {
        closePreviewBilanModalBtn.addEventListener('click', function(e) {
            closeModal('preview-bilan-modal');
        });
    }

    // Gestion de la modale d'email
    const emailModal = document.getElementById('email-modal');
    const closeEmailModalBtn = document.getElementById('close-email-modal-btn');
    const cancelEmailBtn = document.getElementById('cancel-email-btn');
    const emailForm = document.getElementById('email-form');

    // Fonction pour fermer la modale d'email
    function closeEmailModal() {
        if (emailModal) {
            emailModal.style.display = 'none';
            if (emailForm) {
                emailForm.reset();
            }
        }
    }

    // Gestionnaires d'événements pour la modale d'email
    if (closeEmailModalBtn) {
        closeEmailModalBtn.addEventListener('click', closeEmailModal);
    }

    if (cancelEmailBtn) {
        cancelEmailBtn.addEventListener('click', closeEmailModal);
    }

    // Fermer la modale en cliquant en dehors
    window.addEventListener('click', function(event) {
        if (event.target === emailModal) {
            closeEmailModal();
        }
    });

    // Gestion de l'envoi du formulaire d'email
    if (emailForm) {
        emailForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            try {
                // Récupérer les valeurs des champs
                const emailAddress = document.getElementById('email-address').value;
                const emailSubject = document.getElementById('email-subject').value;
                const emailMessage = document.getElementById('email-message').value;
                const crId = document.querySelector('input[name="cr_id"]').value;

                // Vérifier que les champs requis sont remplis
                if (!emailAddress || !emailSubject || !crId) {
                    throw new Error('Veuillez remplir tous les champs obligatoires');
                }

                // Créer le FormData
                const formData = new FormData();
                formData.append('email', emailAddress);
                formData.append('subject', emailSubject);
                formData.append('message', emailMessage);
                formData.append('cr_id', crId);

                // Afficher un message de chargement
                setStatus('Envoi de l\'email en cours...');

                // Envoyer au serveur
                const response = await fetch('assets/email/send_bilan.php', {
                    method: 'POST',
                    body: formData
                });

                // Vérifier si la réponse est OK
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }

                // Lire la réponse
                const result = await response.json();

                if (result.success) {
                    // Fermer la modale d'email
                    closeEmailModal();

                    // Afficher la modale de confirmation
                    const confirmationModal = document.getElementById('email-confirmation-modal');
                    const confirmationEmail = document.getElementById('confirmation-email');
                    const confirmationSubject = document.getElementById('confirmation-subject');

                    if (confirmationEmail) confirmationEmail.textContent = emailAddress;
                    if (confirmationSubject) confirmationSubject.textContent = emailSubject;
                    if (confirmationModal) confirmationModal.style.display = 'flex';

                    // Gestionnaire pour fermer la modale de confirmation
                    const closeConfirmationBtn = document.getElementById('close-confirmation-btn');
                    const closeConfirmationModalBtn = document.getElementById('close-confirmation-modal-btn');

                    if (closeConfirmationBtn) {
                        closeConfirmationBtn.onclick = function() {
                            confirmationModal.style.display = 'none';
                        };
                    }

                    if (closeConfirmationModalBtn) {
                        closeConfirmationModalBtn.onclick = function() {
                            confirmationModal.style.display = 'none';
                        };
                    }

                    // Fermer la modale en cliquant en dehors
                    window.onclick = function(event) {
                        if (event.target === confirmationModal) {
                            confirmationModal.style.display = 'none';
                        }
                    };

                } else {
                    throw new Error(result.message || 'Erreur lors de l\'envoi');
                }

            } catch (error) {
                console.error('Erreur lors de l\'envoi par email:', error);
                setStatus('Erreur : ' + error.message, false);
                alert('Erreur : ' + error.message);
            }
        });
    }

    // Ajouter les gestionnaires d'événements pour les boutons de téléchargement
    document.querySelectorAll('.download-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const filePath = this.dataset.file;
            const type = this.dataset.type;
            const id = this.dataset.id;
            downloadFile(filePath, type, id);
        });
    });

    // Fonction pour afficher l'aperçu d'un compte rendu
    function previewCompteRendu(id) {
        if (!id) {
            alert('ID du compte rendu manquant');
            return;
        }

        console.log('Tentative de prévisualisation du compte rendu ID:', id);

        // Afficher un indicateur de chargement
        const button = event.target.closest('.preview-bilan-button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement...';
        button.disabled = true;

        // Créer une modal pour l'aperçu
        const modal = document.createElement('div');
        modal.className = 'modal';
        modal.id = 'preview-modal';
        modal.style.display = 'flex';
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        modal.style.zIndex = '9999';

        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        modalContent.style.maxWidth = '90%';
        modalContent.style.maxHeight = '90%';
        modalContent.style.width = '100%';
        modalContent.style.backgroundColor = 'white';
        modalContent.style.borderRadius = '8px';
        modalContent.style.position = 'relative';
        modalContent.style.margin = 'auto';

        const modalHeader = document.createElement('div');
        modalHeader.style.padding = '20px';
        modalHeader.style.borderBottom = '1px solid #eee';
        modalHeader.style.display = 'flex';
        modalHeader.style.justifyContent = 'space-between';
        modalHeader.style.alignItems = 'center';

        const modalTitle = document.createElement('h2');
        modalTitle.innerHTML = '<i class="fas fa-file-pdf"></i> Aperçu du compte rendu';
        modalTitle.style.margin = '0';
        modalTitle.style.color = '#333';

        const closeButton = document.createElement('button');
        closeButton.innerHTML = '×';
        closeButton.style.background = 'none';
        closeButton.style.border = 'none';
        closeButton.style.fontSize = '24px';
        closeButton.style.cursor = 'pointer';
        closeButton.style.color = '#666';
        closeButton.onclick = () => {
            document.body.removeChild(modal);
            button.innerHTML = originalContent;
            button.disabled = false;
        };

        const modalBody = document.createElement('div');
        modalBody.style.padding = '20px';
        modalBody.style.height = '70vh';
        modalBody.style.overflow = 'auto';
        modalBody.innerHTML = '<div style="text-align: center; padding: 50px;"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Chargement du PDF...</p></div>';

        modalHeader.appendChild(modalTitle);
        modalHeader.appendChild(closeButton);
        modalContent.appendChild(modalHeader);
        modalContent.appendChild(modalBody);
        modal.appendChild(modalContent);
        document.body.appendChild(modal);

        // Fermer la modal en cliquant en dehors
        modal.onclick = (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
                button.innerHTML = originalContent;
                button.disabled = false;
            }
        };

        // Fermer la modal avec la touche Échap
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                document.body.removeChild(modal);
                button.innerHTML = originalContent;
                button.disabled = false;
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);

        // Charger le PDF via AJAX
        fetch(`C:/wamp64/www/GSCV+/public/assets/traitements/preview_cr.php?id=${encodeURIComponent(id)}`, {
            method: 'GET'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur HTTP: ' + response.status);
            }
            return response.blob();
        })
        .then(blob => {
            // Créer un objet URL pour le blob
            const url = URL.createObjectURL(blob);
            
            // Créer un iframe pour afficher le PDF
            modalBody.innerHTML = `
                <iframe 
                    src="${url}" 
                    style="width: 100%; height: 100%; border: none;"
                    title="Aperçu du compte rendu">
                </iframe>
            `;
            
            console.log('PDF chargé avec succès');
        })
        .catch(error => {
            console.error('Erreur lors du chargement du PDF:', error);
            modalBody.innerHTML = `
                <div style="text-align: center; padding: 50px; color: red;">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                    <p>Erreur lors du chargement du PDF</p>
                    <p>${error.message}</p>
                </div>
            `;
        });
    }

    // Ajouter les gestionnaires d'événements pour les boutons d'aperçu
    document.querySelectorAll('.preview-bilan-button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            
            if (!id) {
                alert('ID du compte rendu manquant');
                return;
            }
            
            previewCompteRendu(id);
        });
    });

    // Ajouter les gestionnaires d'événements pour le bouton de partage de compte rendu
    document.querySelectorAll('.share-cr-button').forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            const crId = this.dataset.id;
            const crType = this.dataset.type;

            // Afficher la modale d'email
            const emailModal = document.getElementById('email-modal');
            if (emailModal) {
                emailModal.style.display = 'flex';

                // Pré-remplir le sujet avec le nom du compte rendu
                const subjectInput = document.getElementById('email-subject');
                if (subjectInput) {
                    // Récupérer le nom du compte rendu et la date depuis la ligne du tableau
                    const row = this.closest('tr');
                    const crName = row.querySelector('td:nth-child(4)').textContent;
                    const crDate = row.querySelector('td:nth-child(2)').textContent;

                    // Formater la date (supprimer les slashes)
                    const formattedDate = crDate.replace(/\//g, '_');

                    // Créer le nom formaté
                    const formattedName = `Compte rendu - ${crName}_${formattedDate}`;
                    subjectInput.value = formattedName;
                }

                // Récupérer le chemin du fichier du compte rendu
                try {
                    const response = await fetch(`./assets/traitements/fetch_cr_file.php?id=${crId}`);
                    const result = await response.json();

                    const attachmentInput = document.getElementById('email-attachment');
                    if (attachmentInput) {
                        if (result.success) {
                            // Afficher le nom du fichier (basename)
                            const fileName = result.filePath.split('/').pop();
                            attachmentInput.value = fileName;
                        } else {
                            attachmentInput.value = result.message;
                            attachmentInput.style.color = 'red';
                        }
                    }
                } catch (error) {
                    console.error('Erreur lors de la récupération du fichier:', error);
                    const attachmentInput = document.getElementById('email-attachment');
                    if (attachmentInput) {
                        attachmentInput.value = 'Erreur de chargement du fichier';
                        attachmentInput.style.color = 'red';
                    }
                }

                // Stocker l'ID du compte rendu dans un champ caché
                const crIdInput = document.createElement('input');
                crIdInput.type = 'hidden';
                crIdInput.name = 'cr_id';
                crIdInput.value = crId;
                emailForm.appendChild(crIdInput);
            }
        });
    });

    // Gestion de la fermeture des modales
    function setupModalCloseHandlers() {
        // Boutons de fermeture avec la classe 'close' ou 'close-btn'
        document.querySelectorAll('.close, .close-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Trouver la modale parente
                let modal = btn.closest('.modal');
                if (!modal) {
                    // Cas particulier : modale de détails (structure différente)
                    modal = btn.closest('.modal-content')?.parentElement;
                }
                if (modal) {
                    modal.style.display = 'none';
                    // Réinitialiser l'URL si besoin
                    if (modal.id === 'rapport-modal' || modal.id === 'details-bilan-modal') {
                        window.history.pushState({}, '', window.AppConfig.baseUrl);
                    }
                }
            });
        });
    }

    // ... existing code ...
    setupEventHandlers();
    setupModalCloseOnOutsideClick();
    setupDownloadHandlers();
    setupSearchAndFilters();
    setupModalCloseHandlers();
    // ... existing code ...

});

// Gestion universelle de la fermeture des modales
(function() {
    function closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
            // Réinitialiser l'URL si besoin (pour les modales de détails)
            if (modal.id === 'rapport-modal' || modal.id === 'details-bilan-modal') {
                window.history.pushState({}, '', window.AppConfig.baseUrl);
            }
        }
    }

    // Boutons de fermeture (croix, boutons dédiés)
    document.querySelectorAll('.close, .close-btn, .close-modal-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            let modal = btn.closest('.modal');
            if (!modal) {
                modal = btn.closest('.modal-content')?.parentElement;
            }
            closeModal(modal);
        });
    });

    // Fermeture en cliquant à l'extérieur de la modale
    window.addEventListener('click', function(event) {
        document.querySelectorAll('.modal').forEach(modal => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    // Optionnel : fermeture avec la touche Echap
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(modal => {
                if (modal.style.display === 'flex' || modal.style.display === 'block') {
                    closeModal(modal);
                }
            });
        }
    });
})();