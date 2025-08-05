<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Configuration des modals et de l'UI
        initializeModals();
        initializeStatusButtons();
        initializeEditor();

        const hasExistingReport = window.hasExistingReport !== undefined ? window.hasExistingReport : false;
        const newReportBtn = document.getElementById('new-report');
        const forbiddenModal = document.getElementById('forbidden-modal');
        const infoReportModal = document.getElementById('info-report-modal');
        const closeForbiddenBtn = document.getElementById('close-forbidden-btn');

        if (newReportBtn && forbiddenModal) {
            newReportBtn.addEventListener('click', function(e) {
                if (hasExistingReport) {
                    e.preventDefault();
                    // Ouvre SEULEMENT la modale d'interdiction
                    forbiddenModal.style.display = 'flex';
                    setTimeout(() => forbiddenModal.classList.add('open'), 10);
                    // S'assure que la modale de création est bien fermée
                    if (infoReportModal) {
                        infoReportModal.classList.remove('open');
                        infoReportModal.style.display = 'none';
                    }
                } else {
                    // Ouvre SEULEMENT la modale de création
                    e.preventDefault();
                    if (infoReportModal) {
                        infoReportModal.style.display = 'flex';
                        setTimeout(() => infoReportModal.classList.add('open'), 10);
                    }
                }
            });
        }
        if (closeForbiddenBtn && forbiddenModal) {
            closeForbiddenBtn.addEventListener('click', function(e) {
                e.preventDefault();
                forbiddenModal.classList.remove('open');
                setTimeout(() => forbiddenModal.style.display = 'none', 300);
            });
        }
    });



    function initializeModals() {
        // Fonctions globales pour gérer les modals
        window.openModal = function(modal) {
            if (modal) {
                modal.style.display = 'flex';
                setTimeout(() => {
                    modal.classList.add('open');
                }, 10);
            }
        };

        window.closeModal = function(modal) {
            if (modal) {
                modal.classList.remove('open');
                setTimeout(() => {
                    modal.style.display = 'none';
                }, 300);
            }
        };

        // Modal des commentaires
        const commentsModal = document.getElementById('comments-modal');
        const viewCommentsBtn = document.getElementById('view-comments');
        const closeCommentsBtn = document.getElementById('close-modal-comments-btn');

        if (viewCommentsBtn) {
            viewCommentsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                openModal(commentsModal);
            });
        }

        if (closeCommentsBtn) {
            closeCommentsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal(commentsModal);
            });
        }

        // Modal d'informations du rapport
        const infoReportModal = document.getElementById('info-report-modal');
        const newReportBtn = document.getElementById('new-report');
        const closeInfoReportBtn = document.getElementById('close-modal-info-report-btn');
        const createReportBtn = document.getElementById('create-report-btn');

        if (newReportBtn) {
            newReportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (!this.classList.contains('btn-desactive')) {
                    openModal(infoReportModal);
                }
            });
        }

        if (closeInfoReportBtn) {
            closeInfoReportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal(infoReportModal);
            });
        }

        // Modal de création de rapport
        const createReportModal = document.getElementById('create-report-modal');
        const closeCreateReportBtn = document.getElementById('close-modal-create-report-btn');

        if (createReportBtn) {
            createReportBtn.addEventListener('click', function(e) {
                e.preventDefault();

                // Récupérer le thème du rapport
                const themeReportInput = document.getElementById('theme_report');
                if (themeReportInput) {
                    const themeValue = themeReportInput.value.trim();
                    if (!themeValue) {
                        alert('Veuillez renseigner le thème du mémoire avant de continuer.');
                        themeReportInput.focus();
                        return;
                    }
                    window.themeReport = themeValue;
                }

                closeModal(infoReportModal);
                setTimeout(() => {
                    openModal(createReportModal);
                    // Initialiser l'éditeur immédiatement quand le modal s'ouvre
                    console.log('Ouverture du modal, initialisation de l\'éditeur...');
                    initializeOnlyOfficeEditor();
                    setupEditorButtons();
                }, 300);
            });
        }

        if (closeCreateReportBtn) {
            closeCreateReportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal(createReportModal);
            });
        }

        // Modal d'interdiction
        const forbiddenModal = document.getElementById('forbidden-modal');
        const closeForbiddenBtn = document.getElementById('close-modal-forbidden-btn');
        const closeForbiddenBtn2 = document.getElementById('close-forbidden-btn');
        const checkCurrentStatusBtn = document.getElementById('check-current-status-btn');

        if (closeForbiddenBtn) {
            closeForbiddenBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal(forbiddenModal);
            });
        }

        if (closeForbiddenBtn2) {
            closeForbiddenBtn2.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal(forbiddenModal);
            });
        }

        if (checkCurrentStatusBtn) {
            checkCurrentStatusBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeModal(forbiddenModal);
                // Afficher le statut du rapport actuel
                const reportStatus = <?php echo json_encode($rapport_status); ?>;
                showReportModal(reportStatus);
            });
        }

        // Fermer les modals en cliquant en dehors
        window.addEventListener('click', function(e) {
            if (e.target === commentsModal) closeModal(commentsModal);
            if (e.target === infoReportModal) closeModal(infoReportModal);
            if (e.target === createReportModal) closeModal(createReportModal);
            if (e.target === forbiddenModal) closeModal(forbiddenModal);
        });
    }

    function initializeStatusButtons() {
        const newReportBtn = document.getElementById('new-report');
        const checkStatusReport = document.getElementById('check-status-report');
        const modalOverlay = document.querySelector('.modal-overlay');
        const continueBtns = document.querySelectorAll('.continue-btn');

        // Récupération du statut et de l'éligibilité
        const reportStatus = <?php echo json_encode($rapport_status); ?>;
        const eligibilityStatus = <?php echo json_encode($eligibility_status); ?>;
        const hasExistingReport = <?php echo $hasExistingReport ? 'true' : 'false'; ?>;

        // Mise à jour de l'UI selon l'éligibilité et l'existence d'un rapport
        function updateEligibilityUI() {
            // Le bouton "Nouveau rapport" est activé si l'étudiant est éligible ET n'a pas de rapport existant
            if (eligibilityStatus === 'Éligible' && !hasExistingReport) {
                if (newReportBtn) newReportBtn.classList.remove('btn-desactive');
            } else {
                if (newReportBtn) newReportBtn.classList.add('btn-desactive');
            }

            // Le bouton "Vérifier" est activé si l'étudiant a un rapport existant
            if (hasExistingReport) {
                if (checkStatusReport) checkStatusReport.classList.remove('btn-desactive');
            } else {
                if (checkStatusReport) checkStatusReport.classList.add('btn-desactive');
            }
        }

        updateEligibilityUI();

        // Fonctions pour les modals de statut
        function hideAllStatusModals() {
            document.querySelectorAll('.alert').forEach(modal => {
                modal.style.display = 'none';
            });
            modalOverlay.style.display = 'none';
        }

        function showReportModal(status) {
            hideAllStatusModals();
            modalOverlay.style.display = 'block';

            // Si le statut est vide ou null, afficher le modal "Non soumis"
            if (!status || status === '' || status === null) {
                status = "Non soumis";
            }

            let modalToShow;
            switch (status) {
                case "Non soumis":
                    modalToShow = document.querySelector('.alert.not_submitted');
                    break;
                case "En attente d'approbation":
                    modalToShow = document.querySelector('.alert.wait_approbation');
                    break;
                case "Désapprouvé":
                    modalToShow = document.querySelector('.alert.refuse_approbation');
                    break;
                case 'Approuvé':
                    modalToShow = document.querySelector('.alert.success_approbation');
                    break;
                case "En attente de validation":
                    modalToShow = document.querySelector('.alert.wait_validation');
                    break;
                case "Validé":
                    modalToShow = document.querySelector('.alert.success_validation');
                    break;
                case 'Rejeté':
                    modalToShow = document.querySelector('.alert.refuse_validation');
                    break;
                default:
                    console.warn("Statut inconnu :", status);
                    // Par défaut, afficher le modal "Non soumis"
                    modalToShow = document.querySelector('.alert.not_submitted');
                    break;
            }

            if (modalToShow) {
                modalToShow.classList.add('fade-in');
                modalToShow.style.display = 'block';
            } else {
                console.error('Modal non trouvé pour le statut:', status);
            }
        }

        // Événement pour vérifier le statut
        if (checkStatusReport) {
            checkStatusReport.addEventListener('click', function() {
                if (!this.classList.contains('btn-desactive')) {
                    showReportModal(reportStatus);
                }
            });
        }

        // Gestion des boutons CONTINUE
        continueBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = this.closest('.alert');
                modal.classList.add('fade-out');
                modal.classList.remove('fade-in');

                setTimeout(() => {
                    hideAllStatusModals();
                    modal.classList.remove('fade-out');
                }, 300);
            });
        });

        // Fermeture via overlay
        if (modalOverlay) {
            modalOverlay.addEventListener('click', function() {
                const visibleModal = document.querySelector('.alert[style*="display: block"]');
                if (visibleModal) {
                    visibleModal.classList.add('fade-out');
                    visibleModal.classList.remove('fade-in');
                    setTimeout(() => {
                        hideAllStatusModals();
                        visibleModal.classList.remove('fade-out');
                    }, 300);
                } else {
                    hideAllStatusModals();
                }
            });
        }
    }

    function initializeEditor() {
        // Attendre le chargement complet
        window.addEventListener('load', function() {
            console.log("Vérification des bibliothèques:");
            console.log("- OnlyOffice: Vérification de la disponibilité...");

            // Initialiser l'éditeur si le modal est ouvert
            const createReportModal = document.getElementById('create-report-modal');
            if (createReportModal && createReportModal.classList.contains('open')) {
                initializeOnlyOfficeEditor();
                setupEditorButtons();
            }
        });
    }

    // Initialiser l'éditeur OnlyOffice
    function initializeOnlyOfficeEditor() {
        const onlyofficeContainer = document.getElementById('onlyoffice-container');
        const placeholder = document.getElementById('placeholder');
        const editorWrapper = document.getElementById('local-editor-wrapper');

        if (!onlyofficeContainer) {
            console.error('Conteneur onlyoffice-container non trouvé');
            return;
        }

        console.log('Initialisation de l\'éditeur...');

        // Afficher le conteneur
        onlyofficeContainer.style.display = 'block';

        // Masquer le placeholder et afficher l'éditeur
        if (placeholder) {
            placeholder.style.display = 'none';
        }

        if (editorWrapper) {
            editorWrapper.style.display = 'flex';
        }

        // Assigner l'éditeur globalement
        window.localEditor = document.getElementById('local-editor');
        
        if (window.localEditor) {
            console.log('Éditeur initialisé avec succès');
            // S'assurer que l'éditeur est focusable
            window.localEditor.focus();
        } else {
            console.error('Éditeur local non trouvé');
        }
    }

    // Créer un éditeur local avec conversion DOCX
    function createLocalEditor() {
        const onlyofficeContainer = document.getElementById('onlyoffice-container');
        if (!onlyofficeContainer) {
            console.error('Conteneur onlyoffice-container non trouvé');
            return;
        }

        console.log('Création de l\'éditeur local...');

        // Afficher le conteneur
        onlyofficeContainer.style.display = 'block';
        console.log('Conteneur affiché');

        // Créer l'interface de l'éditeur
        const editorWrapper = document.createElement('div');
        editorWrapper.id = 'local-editor-wrapper';
        editorWrapper.style.cssText = `
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        background: white;
        border-radius: 8px;
        overflow: hidden;
    `;

        // Barre d'outils
        const toolbar = document.createElement('div');
        toolbar.id = 'editor-toolbar';
        toolbar.style.cssText = `
        padding: 10px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        align-items: center;
    `;

        // Boutons de la barre d'outils
        const toolbarButtons = [{
                text: 'Gras',
                icon: 'bold',
                action: 'bold'
            },
            {
                text: 'Italique',
                icon: 'italic',
                action: 'italic'
            },
            {
                text: 'Souligné',
                icon: 'underline',
                action: 'underline'
            },
            {
                text: '|',
                separator: true
            },
            {
                text: 'Titre 1',
                icon: 'heading',
                action: 'formatBlock',
                value: 'h1'
            },
            {
                text: 'Titre 2',
                icon: 'heading',
                action: 'formatBlock',
                value: 'h2'
            },
            {
                text: 'Titre 3',
                icon: 'heading',
                action: 'formatBlock',
                value: 'h3'
            },
            {
                text: '|',
                separator: true
            },
            {
                text: 'Liste à puces',
                icon: 'list-ul',
                action: 'insertUnorderedList'
            },
            {
                text: 'Liste numérotée',
                icon: 'list-ol',
                action: 'insertOrderedList'
            },
            {
                text: '|',
                separator: true
            },
            {
                text: 'Align. Gauche',
                icon: 'align-left',
                action: 'justifyLeft'
            },
            {
                text: 'Align. Centre',
                icon: 'align-center',
                action: 'justifyCenter'
            },
            {
                text: 'Align. Droite',
                icon: 'align-right',
                action: 'justifyRight'
            },
            {
                text: '|',
                separator: true
            },
            {
                text: 'Couleur',
                icon: 'palette',
                action: 'foreColor',
                value: '#000000'
            }
        ];

        toolbarButtons.forEach(btn => {
            if (btn.separator) {
                const separator = document.createElement('span');
                separator.textContent = '|';
                separator.style.cssText = 'color: #ccc; margin: 0 5px;';
                toolbar.appendChild(separator);
            } else {
                const button = document.createElement('button');
                button.innerHTML = `<i class="fas fa-${btn.icon}"></i> ${btn.text}`;
                button.style.cssText = `
                padding: 6px 12px;
                border: 1px solid #dee2e6;
                background: white;
                cursor: pointer;
                border-radius: 4px;
                font-size: 12px;
                transition: all 0.2s;
            `;
                button.onmouseover = () => button.style.background = '#e9ecef';
                button.onmouseout = () => button.style.background = 'white';
                button.onclick = () => {
                    if (btn.value) {
                        document.execCommand(btn.action, false, btn.value);
                    } else {
                        document.execCommand(btn.action, false, null);
                    }
                };
                toolbar.appendChild(button);
            }
        });

        // Zone d'édition
        const editor = document.createElement('div');
        editor.id = 'local-editor';
        editor.contentEditable = true;
        editor.style.cssText = `
        flex: 1;
        padding: 20px;
        border: none;
        outline: none;
        font-family: 'Times New Roman', serif;
        font-size: 14px;
        line-height: 1.6;
        overflow-y: auto;
        background: white;
    `;
        editor.innerHTML = '<p>Commencez à rédiger votre rapport...</p>';

        // Assembler l'éditeur
        editorWrapper.appendChild(toolbar);
        editorWrapper.appendChild(editor);
        onlyofficeContainer.appendChild(editorWrapper);

        // Sauvegarder l'éditeur globalement
        window.localEditor = editor;
        console.log('Éditeur local créé et assigné à window.localEditor');
    }

    // Gérer la sauvegarde du document
    function handleDocumentSave(data) {
        const statusDiv = document.getElementById('status');
        if (statusDiv) {
            statusDiv.textContent = 'Document sauvegardé avec succès ✓';
            statusDiv.style.color = 'green';
        }

        // Envoyer les données au serveur
        if (data.url) {
            saveDocumentToServer(data.url);
        }
    }

    // Sauvegarder le document sur le serveur
    async function saveDocumentToServer(documentUrl) {
        try {
            const response = await fetch('/GSCV+/public/assets/traitements/save_document.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    documentUrl: documentUrl,
                    studentId: <?php echo json_encode($student_id); ?>,
                    themeReport: window.themeReport || ''
                })
            });

            const result = await response.json();
            if (result.success) {
                console.log('Document sauvegardé avec succès');
            } else {
                console.error('Erreur lors de la sauvegarde:', result.error);
            }
        } catch (error) {
            console.error('Erreur lors de la sauvegarde:', error);
        }
    }

    // Créer un éditeur de fallback si OnlyOffice n'est pas disponible
    function createFallbackEditor() {
        const onlyofficeContainer = document.getElementById('onlyoffice-container');
        if (!onlyofficeContainer) return;

        // Créer un éditeur simple avec contenteditable
        const editorDiv = document.createElement('div');
        editorDiv.id = 'fallback-editor';
        editorDiv.contentEditable = true;
        editorDiv.style.cssText = `
        width: 100%;
        height: 100%;
        padding: 20px;
        border: 1px solid #ccc;
        font-family: 'Times New Roman', serif;
        font-size: 14px;
        line-height: 1.6;
        overflow-y: auto;
    `;
        editorDiv.innerHTML = '<p>Commencez à rédiger votre rapport...</p>';

        onlyofficeContainer.appendChild(editorDiv);

        // Ajouter une barre d'outils simple
        const toolbar = document.createElement('div');
        toolbar.style.cssText = `
        padding: 10px;
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        gap: 10px;
    `;

        const buttons = [{
                text: 'Gras',
                action: 'bold'
            },
            {
                text: 'Italique',
                action: 'italic'
            },
            {
                text: 'Souligné',
                action: 'underline'
            },
            {
                text: 'Titre 1',
                action: 'formatBlock',
                value: 'h1'
            },
            {
                text: 'Titre 2',
                action: 'formatBlock',
                value: 'h2'
            },
            {
                text: 'Titre 3',
                action: 'formatBlock',
                value: 'h3'
            }
        ];

        buttons.forEach(btn => {
            const button = document.createElement('button');
            button.textContent = btn.text;
            button.style.cssText = `
            padding: 5px 10px;
            border: 1px solid #ccc;
            background: white;
            cursor: pointer;
            border-radius: 3px;
        `;
            button.onclick = () => {
                document.execCommand(btn.action, false, btn.value);
            };
            toolbar.appendChild(button);
        });

        onlyofficeContainer.insertBefore(toolbar, editorDiv);
    }

    // Configurer les boutons de l'éditeur
    function setupEditorButtons() {
        console.log('Configuration des boutons de l\'éditeur...');
        const loadTemplateBtn = document.getElementById('load-template');
        const saveReportBtn = document.getElementById('save-report-onlyoffice');
        const downloadPdfBtn = document.getElementById('download-pdf');
        const statusDiv = document.getElementById('status');
        
        console.log('Boutons trouvés:', {
            loadTemplate: !!loadTemplateBtn,
            saveReport: !!saveReportBtn,
            downloadPdf: !!downloadPdfBtn,
            status: !!statusDiv
        });

        // Fonction pour afficher le statut
        function setStatus(message, isSuccess = true) {
            if (statusDiv) {
                statusDiv.textContent = message;
                statusDiv.style.color = isSuccess ? 'green' : 'red';
            }
        }

        // Charger le modèle
        if (loadTemplateBtn) {
            console.log('Bouton de chargement trouvé, ajout de l\'événement click');
            loadTemplateBtn.addEventListener('click', async function() {
                console.log('Bouton de chargement cliqué !');
                try {
                    // Récupérer le modèle sélectionné
                    const modelSelect = document.getElementById('model-select');
                    const selectedModel = modelSelect.value;

                    if (!selectedModel) {
                        alert('Veuillez sélectionner un modèle avant de continuer.');
                        modelSelect.focus();
                        return;
                    }

                    this.disabled = true;
                    this.textContent = 'Chargement...';
                    setStatus('Chargement du modèle...');

                    console.log('Début du chargement du modèle:', selectedModel);

                    // S'assurer que l'éditeur est initialisé
                    if (!window.localEditor) {
                        console.log('Initialisation de l\'éditeur...');
                        initializeOnlyOfficeEditor();
                        
                        // Attendre un peu pour que l'éditeur soit prêt
                        await new Promise(resolve => setTimeout(resolve, 200));
                    }

                    console.log('Éditeur disponible:', !!window.localEditor);

                    if (!window.localEditor) {
                        throw new Error('Éditeur non initialisé - veuillez réessayer');
                    }

                    let content = '';

                    // Charger selon le type de modèle
                    if (selectedModel.endsWith('.docx')) {
                        // Charger le modèle DOCX
                        const modelUrl = '/GSCV+/storage/templates/' + selectedModel;
                        console.log('Tentative de chargement DOCX depuis:', modelUrl);

                        const response = await fetch(modelUrl);
                        console.log('Réponse du serveur:', response.status, response.ok);

                        if (!response.ok) {
                            throw new Error(`Impossible de charger le modèle (${response.status})`);
                        }

                        const arrayBuffer = await response.arrayBuffer();
                        console.log('Fichier DOCX chargé, taille:', arrayBuffer.byteLength);

                        console.log('Conversion avec mammoth...');
                        const result = await mammoth.convertToHtml({
                            arrayBuffer
                        });
                        console.log('Conversion terminée, contenu:', result.value.substring(0, 100) + '...');

                        content = result.value;
                    } else if (selectedModel.endsWith('.pdf')) {
                        // Charger le modèle HTML
                        const modelUrl = '/GSCV+/storage/templates/' + selectedModel;
                        console.log('Tentative de chargement HTML depuis:', modelUrl);

                        const response = await fetch(modelUrl);
                        console.log('Réponse du serveur:', response.status, response.ok);

                        if (!response.ok) {
                            throw new Error(`Impossible de charger le modèle (${response.status})`);
                        }

                        const htmlContent = await response.text();
                        console.log('Fichier HTML chargé, taille:', htmlContent.length);

                        // Extraire le contenu du body du HTML
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlContent, 'text/html');
                        const bodyContent = doc.body.innerHTML;

                        content = bodyContent;
                    } else {
                        throw new Error('Type de modèle non supporté');
                    }

                    // Personnaliser le contenu avec le thème
                    if (window.themeReport) {
                        content = content.replace(/Thème du mémoire/g, window.themeReport);
                        content = content.replace(/MISE EN PLACE D'UN MODULE D'INTÉGRATION ENTRE ATLANTIS CRM ET ATLANTIS SGO :<br>CAS DE LA BOA CAPITAL ASSET MANAGEMENT/g, window.themeReport);
                    }

                    console.log('Mise à jour de l\'éditeur avec le contenu...');
                    window.localEditor.innerHTML = content;
                    setStatus('Modèle chargé avec succès ✓');

                    // Activer les autres boutons
                    if (saveReportBtn) saveReportBtn.disabled = false;
                    if (downloadPdfBtn) downloadPdfBtn.disabled = false;

                    console.log('Modèle chargé avec succès');

                } catch (error) {
                    console.error('Erreur détaillée:', error);
                    setStatus(`Erreur: ${error.message}`, false);

                    // En cas d'erreur, créer un modèle de base
                    if (window.localEditor) {
                        const fallbackContent = `
                        <h1>RAPPORT DE STAGE</h1>
                        <h2>Thème du mémoire</h2>
                        <p>Veuillez remplacer ce texte par le thème de votre mémoire.</p>
                        
                        <h2>Introduction</h2>
                        <p>Votre introduction ici...</p>
                        
                        <h2>1. Présentation de l'entreprise</h2>
                        <p>Description de l'entreprise d'accueil...</p>
                        
                        <h2>2. Missions réalisées</h2>
                        <p>Description des missions et tâches réalisées...</p>
                        
                        <h2>3. Analyse et résultats</h2>
                        <p>Analyse des résultats obtenus...</p>
                        
                        <h2>Conclusion</h2>
                        <p>Bilan de votre stage...</p>
                        
                        <h2>Bibliographie</h2>
                        <p>Liste des références utilisées...</p>
                    `;

                        window.localEditor.innerHTML = fallbackContent;
                        setStatus('Modèle de base chargé ✓');

                        // Activer les autres boutons
                        if (saveReportBtn) saveReportBtn.disabled = false;
                        if (downloadPdfBtn) downloadPdfBtn.disabled = false;
                    }
                } finally {
                    this.disabled = false;
                    this.textContent = 'Charger le modèle';
                }
            });
        }

        // Sauvegarder le rapport
        if (saveReportBtn) {
            saveReportBtn.addEventListener('click', async function() {
                try {
                    this.disabled = true;
                    this.textContent = 'Sauvegarde...';
                    setStatus('Sauvegarde du rapport...');

                    if (!window.localEditor) {
                        throw new Error('Éditeur non initialisé');
                    }

                    const content = window.localEditor.innerHTML;
                    const studentId = <?php echo json_encode($student_id); ?>;
                    const themeReport = window.themeReport || '';

                    // Créer un FormData pour l'envoi
                    const formData = new FormData();
                    formData.append('action', 'create_report');
                    formData.append('student_id', studentId);
                    formData.append('theme_memoire', themeReport);
                    formData.append('content', content);
                    formData.append('file_path', '<?php echo $_SESSION['name_report']; ?>.pdf');

                    // Envoyer au serveur
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        setStatus('Rapport sauvegardé avec succès ✓');
                        // Fermer le modal après sauvegarde
                        setTimeout(() => {
                            const modal = document.getElementById('create-report-modal');
                            if (modal) closeModal(modal);
                        }, 2000);
                    } else {
                        throw new Error('Erreur lors de la sauvegarde');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    setStatus(`Erreur: ${error.message}`, false);
                } finally {
                    this.disabled = false;
                    this.textContent = 'Déposer le rapport';
                }
            });
        }

        // Télécharger en PDF
        if (downloadPdfBtn) {
            downloadPdfBtn.addEventListener('click', async function() {
                try {
                    this.disabled = true;
                    setStatus('Génération du fichier PDF...');

                    if (!window.localEditor) {
                        throw new Error('Éditeur non initialisé');
                    }

                    const content = window.localEditor.innerHTML;

                    // Utiliser html2pdf pour convertir en PDF
                    if (typeof html2pdf !== 'undefined') {
                        const element = document.createElement('div');
                        element.innerHTML = content;
                        element.style.cssText = `
                        font-family: 'Times New Roman', serif;
                        font-size: 12pt;
                        line-height: 1.6;
                        color: #333;
                        padding: 20px;
                        background: white;
                    `;

                        const opt = {
                            margin: [10, 10, 10, 10],
                            filename: `rapport_${Date.now()}.pdf`,
                            image: {
                                type: 'jpeg',
                                quality: 0.98
                            },
                            html2canvas: {
                                scale: 2,
                                useCORS: true,
                                letterRendering: true
                            },
                            jsPDF: {
                                unit: 'mm',
                                format: 'a4',
                                orientation: 'portrait'
                            }
                        };

                        await html2pdf().set(opt).from(element).save();
                        setStatus('Fichier PDF téléchargé ✓');
                    } else {
                        throw new Error('Bibliothèque html2pdf non disponible');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    setStatus(`Erreur: ${error.message}`, false);
                } finally {
                    this.disabled = false;
                }
            });
        }

        // Désactiver les boutons au début (sauf le bouton de chargement)
        if (saveReportBtn) saveReportBtn.disabled = true;
        if (downloadPdfBtn) downloadPdfBtn.disabled = true;
        
        // S'assurer que le bouton de chargement est activé
        if (loadTemplateBtn) {
            loadTemplateBtn.disabled = false;
            console.log('Bouton de chargement activé');
        }
    }

    // Fonction pour créer un modèle de base
    function createFallbackTemplate() {
        if (window.localEditor) {
            const fallbackContent = `
            <h1>RAPPORT DE STAGE</h1>
            <h2>Thème du mémoire</h2>
            <p>Veuillez remplacer ce texte par le thème de votre mémoire.</p>
            
            <h2>Introduction</h2>
            <p>Votre introduction ici...</p>
            
            <h2>1. Présentation de l'entreprise</h2>
            <p>Description de l'entreprise d'accueil...</p>
            
            <h2>2. Missions réalisées</h2>
            <p>Description des missions et tâches réalisées...</p>
            
            <h2>3. Analyse et résultats</h2>
            <p>Analyse des résultats obtenus...</p>
            
            <h2>Conclusion</h2>
            <p>Bilan de votre stage...</p>
            
            <h2>Bibliographie</h2>
            <p>Liste des références utilisées...</p>
        `;

            window.localEditor.innerHTML = fallbackContent;

            // Activer les boutons
            const saveReportBtn = document.getElementById('save-report-onlyoffice');
            const downloadPdfBtn = document.getElementById('download-pdf');
            if (saveReportBtn) saveReportBtn.disabled = false;
            if (downloadPdfBtn) downloadPdfBtn.disabled = false;

            // Afficher le statut
            const statusDiv = document.getElementById('status');
            if (statusDiv) {
                statusDiv.textContent = 'Modèle de base chargé ✓';
                statusDiv.style.color = 'green';
            }

            // Sauvegarder le contenu
            localStorage.setItem('rapport_content', fallbackContent);
        }
    }
</script>