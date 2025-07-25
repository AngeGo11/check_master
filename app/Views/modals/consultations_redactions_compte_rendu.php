
<!-- Modal pour la rédaction du compte rendu -->
<div class="modal" id="create-bilan-modal">
    <div class="modal-content">
        <div class="top-text">
            <h2 class="modal-title">Création du compte rendu</h2>
            <a href="#" class="close" id="close-modal-create-report-btn">
                <i class="fa fa-xmark fa-2x"></i>
            </a>
        </div>

        <!-- Interface de l'éditeur -->
        <div class="container">
            <div class="action-card">
                <h3>Saisir le compte rendu</h3>
                <p>Cliquez sur « Charger le modèle » pour charger la trame Word puis rédigez !</p>

                <div class="step-action actions-panel">
                    <h4><i class="fas fa-cogs"></i> Actions disponibles</h4>
                    <div class="action-buttons">
                        <div class="top">
                            <button id="charge-model" class="button action-button">
                                <i class="fa-solid fa-spinner"></i> Charger le modèle
                            </button>

                            <button id="preview-pdf" class="button action-button">
                                <i class="fas fa-eye"></i> Aperçu du compte rendu
                            </button>
                            <button id="valid-bilan" class="button action-button">
                                <i class="fa-solid fa-circle-check"></i> Valider le compte rendu
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Toolbar personnalisée -->
                <div id="toolbar">

                    <select class="ql-header">
                        <option value="1">Titre 1</option>
                        <option value="2">Titre 2</option>
                        <option value="3">Titre 3</option>
                        <option value="4">Titre 4</option>
                        <option value="5">Titre 5</option>
                    </select>
                    <!-- Police et taille -->

                    <select class="ql-font">
                        <option selected></option> <!-- Valeur par défaut -->
                        <option value="calibri">Calibri</option>
                        <option value="arial">Arial</option>
                        <option value="times-new-roman">Times New Roman</option>
                        <option value="comic-sans-ms">Comic Sans MS</option>
                    </select>




                    <select class="ql-size"></select>

                    <button class="ql-indent" value="-1"></button>
                    <button class="ql-indent" value="+1"></button>

                    <button class="ql-direction" value="rtl"></button>

                    <button class="ql-blockquote"></button>

                    <button class="ql-code-block"></button>

                    <button class="ql-link"></button>

                    <button class="ql-image"></button>

                    <button class="ql-video"></button>

                    <button class="ql-formula"></button>

                    <!-- Mise en forme texte -->
                    <button class="ql-bold"></button>
                    <button class="ql-italic"></button>
                    <button class="ql-underline"></button>
                    <button class="ql-strike"></button>

                    <!-- Couleur et surlignage -->
                    <select class="ql-color"></select>
                    <select class="ql-background"></select>

                    <!-- Indice et exposant -->
                    <button class="ql-script" value="sub"></button>
                    <button class="ql-script" value="super"></button>

                    <!-- Efface mise en forme -->
                    <button class="ql-clean"></button>

                    <!-- Listes -->
                    <button class="ql-list" value="ordered"></button>
                    <button class="ql-list" value="bullet"></button>

                    <!-- Alignement -->
                    <select class="ql-align"></select>
                    <div id="table-des-matieres"></div>

                </div>

                <div id="editor"></div>
                <div id="status"></div>
            </div>
        </div>


    </div>
</div>
