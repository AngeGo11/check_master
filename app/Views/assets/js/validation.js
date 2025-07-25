// Gestion des étapes
function initializeButtonHandlers() {
    const steps = document.querySelectorAll('.step');
    const stepDetails = document.querySelectorAll('.step-details');
    const nextButtons = document.querySelectorAll('.nav-button.next');
    const prevButtons = document.querySelectorAll('.nav-button:not(.next)');
    const progressBar = document.querySelector('.progress');

    let currentStepIndex = 0;

    // Fonction pour mettre à jour les étapes et la progression
    function updateSteps(index) {
        // Réinitialiser tous les styles
        steps.forEach(step => {
            step.querySelector('.step-icon').classList.remove('active', 'completed');
        });

        // Mettre à jour les icônes des étapes
        for (let i = 0; i < steps.length; i++) {
            const stepIcon = steps[i].querySelector('.step-icon');
            if (i < index) {
                stepIcon.classList.add('completed');
            } else if (i === index) {
                stepIcon.classList.add('active');
            }
        }

        // Masquer tous les détails des étapes
        stepDetails.forEach(detail => {
            detail.style.display = 'none';
        });

        // Afficher l'étape courante
        stepDetails[index].style.display = 'block';

        // Mettre à jour la barre de progression
        progressBar.style.width = `${(index + 1) * 25}%`;
    }

    // Gestionnaire pour les boutons "Étape suivante"
    nextButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            if (currentStepIndex < steps.length - 1) {
                currentStepIndex++;
                updateSteps(currentStepIndex);
            }
        });
    });

    // Gestionnaire pour les boutons "Étape précédente"
    prevButtons.forEach((button, index) => {
        button.addEventListener('click', () => {
            if (currentStepIndex > 0) {
                currentStepIndex--;
                updateSteps(currentStepIndex);
            }
        });
    });

    // Gestionnaire pour le bouton Valider
    const btnExcellent = document.getElementById('btnExcellent');
    if(btnExcellent) {
        btnExcellent.addEventListener('click', () => {
            const formData = new FormData();
            formData.append('action', 'valider');
            formData.append('commentaire_validation', document.getElementById('commentaire_validation')?.value || '');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                updateValidationStatus('Validé', 'green');
            });
        });
    }

    // Gestionnaire pour le bouton Rejeter
    const btnRejeter = document.getElementById('btnRejeter');
    if(btnRejeter) {
        btnRejeter.addEventListener('click', () => {
            const formData = new FormData();
            formData.append('action', 'rejeter');
            formData.append('commentaire_validation', document.getElementById('commentaire_validation')?.value || '');

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                updateValidationStatus('Rejeté', 'red');
            });
        });
    }
}

// Mise à jour du statut de validation
function updateValidationStatus(status, color) {
    const statusBadge = document.querySelector('.status-badge');
    const statutDecision = document.getElementById('statut-decision');
    if(statusBadge) statusBadge.textContent = status;
    if(statutDecision) {
        statutDecision.textContent = status.toUpperCase();
        statutDecision.style.color = color;
    }
}

// Gestion de l'éditeur
function setupEditorFunctions() {
    const btnDownloadPdf = document.getElementById('download-pdf');
    const btnPrintPdf = document.getElementById('print-pdf');
    const btnPreviewPdf = document.getElementById('preview-pdf');
    const statusBox = document.getElementById('status');

    const setStatus = (txt, ok = true) => {
        if (statusBox) {
            statusBox.textContent = txt;
            statusBox.style.color = ok ? 'green' : 'crimson';
        }
    };

    // Télécharger en PDF
    if (btnDownloadPdf) {
        btnDownloadPdf.onclick = async () => {
            try {
                btnDownloadPdf.disabled = true;
                setStatus('Génération du PDF...');
                
                const content = window.quill.root.innerHTML;
                
                if (typeof window.jspdf !== 'undefined') {
                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF();
                    
                    doc.html(content, {
                        callback: function (doc) {
                            doc.save(`compte_rendu_${Date.now()}.pdf`);
                            setStatus('PDF téléchargé ✓');
                            btnDownloadPdf.disabled = false;
                        },
                        x: 15,
                        y: 15,
                        width: 170,
                        windowWidth: 650
                    });
                } else {
                    handlePdfDownloadAlternative(content);
                }
            } catch (e) {
                setStatus('Erreur: ' + e.message, false);
                btnDownloadPdf.disabled = false;
            }
        };
    }

    // Imprimer le PDF
    if (btnPrintPdf) {
        btnPrintPdf.onclick = () => {
            try {
                const content = window.quill.root.innerHTML;
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Compte rendu de validation</title>
                            <style>
                                body { font-family: Arial, sans-serif; padding: 20px; }
                                h1, h2, h3 { color: #333; }
                                @media print {
                                    body { margin: 0; }
                                }
                            </style>
                        </head>
                        <body>${content}</body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
                setStatus('Document envoyé à l\'imprimante');
            } catch (e) {
                setStatus('Erreur d\'impression: ' + e.message, false);
            }
        };
    }

    // Aperçu du PDF
    if (btnPreviewPdf) {
        btnPreviewPdf.onclick = () => {
            try {
                const content = window.quill.root.innerHTML;
                const previewWindow = window.open('', '_blank');
                previewWindow.document.write(`
                    <html>
                        <head>
                            <title>Aperçu - Compte rendu de validation</title>
                            <style>
                                body { 
                                    font-family: Arial, sans-serif; 
                                    max-width: 800px; 
                                    margin: 0 auto;
                                    padding: 20px;
                                    background: #f5f5f5;
                                }
                                .content {
                                    background: white;
                                    padding: 40px;
                                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                                }
                                h1, h2, h3 { color: #333; }
                            </style>
                        </head>
                        <body>
                            <div class="content">
                                ${content}
                            </div>
                        </body>
                    </html>
                `);
                previewWindow.document.close();
                setStatus('Aperçu ouvert dans une nouvelle fenêtre');
            } catch (e) {
                setStatus('Erreur d\'aperçu: ' + e.message, false);
            }
        };
    }
}

// Méthode alternative pour le téléchargement PDF
function handlePdfDownloadAlternative(content) {
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Compte rendu de validation</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h1, h2, h3 { color: #333; }
                </style>
            </head>
            <body>${content}</body>
        </html>
    `);
    printWindow.document.close();
    
    setTimeout(() => {
        printWindow.print();
    }, 500);
}

// Initialisation de l'éditeur
function initializeEditor() {
    window.addEventListener('load', function() {
        console.log("Vérification des bibliothèques:");
        console.log("- JSZip:", typeof JSZip !== 'undefined');
        console.log("- Mammoth:", typeof mammoth !== 'undefined');
        console.log("- Quill:", typeof Quill !== 'undefined');
        console.log("- htmlDocx:", typeof htmlDocx !== 'undefined');
    });
}

// Création de htmlDocx si nécessaire
(function(global) {
    if (typeof htmlDocx === 'undefined' && typeof JSZip !== 'undefined') {
        global.htmlDocx = {
            asBlob: function(html) {
                const zip = new JSZip();
                const documentXml = `
                    <w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
                        <w:body>${this.convertHtmlToDocxXml(html)}</w:body>
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
                    </Types>`);
                
                return zip.generateAsync({
                    type: "blob",
                    mimeType: "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                });
            },
            
            convertHtmlToDocxXml: function(html) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                let result = '';
                
                this.processNode(tempDiv, function(text) {
                    if (text && text.trim()) {
                        result += '<w:p><w:r><w:t>' + 
                            text.replace(/&/g, '&amp;')
                                .replace(/</g, '&lt;')
                                .replace(/>/g, '&gt;')
                                .replace(/"/g, '&quot;') + 
                            '</w:t></w:r></w:p>';
                    }
                });
                
                return result || '<w:p><w:r><w:t></w:t></w:r></w:p>';
            },
            
            processNode: function(node, textHandler) {
                if (node.nodeType === 3) {
                    textHandler(node.textContent);
                } else if (node.nodeType === 1) {
                    if (this.isBlockElement(node)) {
                        textHandler(this.getTextContent(node));
                    } else {
                        for (let i = 0; i < node.childNodes.length; i++) {
                            this.processNode(node.childNodes[i], textHandler);
                        }
                    }
                }
            },
            
            isBlockElement: function(node) {
                const blockElements = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'table', 'tr', 'td', 'th'];
                return blockElements.indexOf(node.nodeName.toLowerCase()) !== -1;
            },
            
            getTextContent: function(node) {
                return node.textContent || '';
            }
        };
    }
})(window); 