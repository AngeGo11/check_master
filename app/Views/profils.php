<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../Controllers/ProfilController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialiser le contrôleur
$profilController = new ProfilController($pdo);

// Récupérer les données via le contrôleur
$data = $profilController->index($_SESSION['user_id']);

if (isset($data['error'])) {
    die($data['error']);
}

$recupUserData = $data['studentData'];
$notes = $data['grades'];
$stages = $data['internships'];
$rapports = $data['reports'];

// Organiser les notes par semestre
$notesParSemestre = $profilController->organizeGradesBySemester($notes);

if (!$recupUserData) {
    die("Aucune donnée trouvée pour cet utilisateur");
}

$num_etd = $recupUserData['num_etd'];
$photo_actuelle = $recupUserData['photo_etd'];
$_SESSION['photo_etd'] = $photo_actuelle;

// ====== TRAITEMENT DU FORMULAIRE ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $updateData = [
        'nom' => $_POST['nom'] ?? '',
        'prenom' => $_POST['prenom'] ?? '',
        'date_naissance' => $_POST['date_naissance'] ?? '',
        'email' => $_POST['email'] ?? '',
        'sexe' => $_POST['sexe'] ?? '',
        'telephone' => $_POST['telephone'] ?? '',
        'adresse' => $_POST['adresse'] ?? '',
        'ville' => $_POST['ville'] ?? '',
        'pays' => $_POST['pays'] ?? '',
        'nouveau_mdp' => $_POST['nouveau_mdp'] ?? ''
    ];

    if ($profilController->updateProfile($num_etd, $_SESSION['user_id'], $updateData)) {
        $_SESSION['success_message'] = "Profil mis à jour avec succès !";
    } else {
        $_SESSION['error_message'] = "Erreur lors de la mise à jour du profil.";
    }

    //  GESTION PHOTO 
    $uploadDir = __DIR__ . '/assets/uploads/profiles/';
    $photoSupprime = isset($_POST['delete_photo']) && $_POST['delete_photo'] === '1';

    // Suppression
    if ($photoSupprime && $photo_actuelle) {
        $oldPath = $uploadDir . $photo_actuelle;
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
        $profilController->deletePhoto($num_etd);
        $_SESSION['photo_etd'] = null;
    }

    // Upload d'une nouvelle photo
    if (isset($_FILES['photo_etd']) && $_FILES['photo_etd']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['photo_etd']['tmp_name'];
        $fileSize = $_FILES['photo_etd']['size'];
        $fileInfo = getimagesize($tmpName);

        if ($fileInfo && $fileSize <= 2 * 1024 * 1024) { // max 2 Mo
            $ext = pathinfo($_FILES['photo_etd']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('photo_') . '.' . strtolower($ext);

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            move_uploaded_file($tmpName, $uploadDir . $filename);

            // Supprimer l'ancienne photo si existante
            if ($photo_actuelle && file_exists($uploadDir . $photo_actuelle)) {
                unlink($uploadDir . $photo_actuelle);
            }

            $profilController->updatePhoto($num_etd, $filename);
            $_SESSION['photo_etd'] = $filename;
        } else {
            $_SESSION['error_message'] = "La photo doit être une image valide de moins de 2 Mo.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'student-primary': '#154360',
                    'student-secondary': '#1a5276',
                    'student-light': '#2563eb',
                    'student-dark': '#0f2f3f',
                }
            }
        }
    }
</script>

<style>
    body {
        font-family: 'Poppins', sans-serif;
    }

    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-top: 2rem;
    }

    .action-card {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(26, 82, 118, 0.15);
        border: 2px solid #1a5276;
        position: relative;
        overflow: hidden;
        max-height: 375px;
    }

    .action-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, rgba(26, 82, 118, 0.05) 0%, transparent 70%);
        animation: float 6s ease-in-out infinite;
    }

    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(26, 82, 118, 0.25);
        border-color: #154360;
    }

    .action-card i {
        font-size: 3rem;
        color: #1a5276;
        margin-bottom: 1rem;
    }

    .action-card h3 {
        color: #1a5276;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .action-card p {
        color: #1a5276;
        font-size: 1rem;
        margin-bottom: 1.5rem;
        opacity: 0.8;
    }

    .action-card button {
        background: #1a5276;
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 0.95rem;
    }

    .action-card button:hover {
        background: #154360;
        transform: scale(1.05);
        box-shadow: 0 4px 15px rgba(26, 82, 118, 0.3);
    }

    /* Modal styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        backdrop-filter: blur(5px);
    }

    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .modal {
        background: white;
        border-radius: 20px;
        max-width: 95vw;
        max-height: 95vh;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }

    .modal-overlay.active .modal {
        transform: scale(1);
    }

    .modal-header {
        background: linear-gradient(135deg, #154360 0%, #1a5276 100%);
        color: white;
        padding: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .modal-header h2 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 600;
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .button {
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        border: none;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .button:not(.btn-cancel) {
        background: rgba(255, 255, 255, 0.9);
        color: #154360;
    }

    .button:not(.btn-cancel):hover {
        background: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(255, 255, 255, 0.3);
    }

    .btn-cancel {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .modal-body {
        max-height: calc(95vh - 120px);
        overflow-y: auto;
        padding: 2rem;
    }

    .modal-content-wrapper {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 2rem;
        align-items: start;
    }

    .profile-sidebar {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        position: sticky;
        top: 0;
    }

    .profile-photo-wrapper {
        width: 120px;
        height: 120px;
        margin: 0 auto 1rem;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid #154360;
        box-shadow: 0 8px 25px rgba(21, 67, 96, 0.2);
    }

    .profile-photo-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-photo-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .profile-photo-actions .button {
        font-size: 0.8rem;
        padding: 0.5rem 1rem;
    }

    .profile-name {
        font-size: 1.3rem;
        font-weight: 600;
        color: #154360;
        margin-bottom: 0.5rem;
    }

    .profile-role {
        color: #64748b;
        font-size: 0.9rem;
        margin-bottom: 1.5rem;
    }

    .profile-stats {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .stat-item {
        background: white;
        padding: 1rem;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .stat-value {
        display: block;
        font-weight: 600;
        color: #154360;
        font-size: 1.1rem;
    }

    .stat-label {
        font-size: 0.8rem;
        color: #64748b;
        margin-top: 0.25rem;
    }

    .form-container {
        background: white;
    }

    .form-section {
        margin-bottom: 2rem;
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
    }

    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #154360;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #154360;
    }

    .form-grid {
        display: grid;
        gap: 1.5rem;
    }

    .name-group, .password-group, .top-group, .address-group {
        display: grid;
        gap: 1rem;
    }

    .name-group {
        grid-template-columns: 1fr 1fr;
    }

    .address-group {
        grid-template-columns: 2fr 1fr 1fr;
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .form-input {
        padding: 0.75rem 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        background-color: white;
        color: #374151;
        font-family: inherit;
    }

    .form-input:focus {
        outline: none;
        border-color: #154360;
        box-shadow: 0 0 0 3px rgba(21, 67, 96, 0.1);
    }

    .form-input.readonly {
        background-color: #f8fafc;
        color: #64748b;
        border-color: #e2e8f0;
    }

    /* S'assurer que les valeurs des inputs sont visibles */
    input[type="text"],
    input[type="email"], 
    input[type="tel"],
    input[type="date"],
    select {
        background-color: white !important;
        color: #374151 !important;
        font-size: 0.9rem !important;
    }

    /* Style spécifique pour les selects */
    select.form-input {
        background-color: white;
        color: #374151;
        cursor: pointer;
    }

    select.form-input option {
        background-color: white;
        color: #374151;
    }

    .contact-grid {
        display: grid;
        gap: 1rem;
    }

    .contact-input-group {
        display: flex;
        align-items: center;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.75rem;
        transition: all 0.3s ease;
    }

    .contact-input-group:focus-within {
        border-color: #154360;
        box-shadow: 0 0 0 3px rgba(21, 67, 96, 0.1);
    }

    .contact-input-icon {
        color: #154360;
        margin-right: 1rem;
        font-size: 1.1rem;
    }

    .contact-input-content {
        flex: 1;
    }

    .contact-input-label {
        font-size: 0.8rem;
        color: #64748b;
        margin-bottom: 0.25rem;
        display: block;
    }

    .contact-input {
        border: none;
        outline: none;
        width: 100%;
        font-size: 0.9rem;
        color: #374151;
        background: transparent;
        font-family: inherit;
    }

    /* Debug: s'assurer que la photo s'affiche */
    .profile-photo-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* S'assurer que les labels et textes sont visibles */
    .form-label {
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        display: block;
    }

    .profile-name, .stat-value {
        color: #154360 !important;
    }

    .profile-role, .stat-label {
        color: #64748b !important;
    }

    /* Tables - Améliorations pour l'affichage des données */
    .stages-table, .reports-table, .grades-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .stages-table th, .reports-table th, .grades-table th {
        background: linear-gradient(135deg, #154360 0%, #1a5276 100%);
        color: white !important;
        padding: 1rem;
        font-weight: 600;
        text-align: left;
        font-size: 0.9rem;
        border: none;
    }

    .stages-table td, .reports-table td, .grades-table td {
        padding: 1rem;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.9rem;
        color: #374151 !important;
        background: white;
        vertical-align: top;
    }

    .stages-table tr:last-child td, .reports-table tr:last-child td, .grades-table tr:last-child td {
        border-bottom: none;
    }

    .stages-table tr:hover, .reports-table tr:hover, .grades-table tr:hover {
        background-color: #f8fafc !important;
    }

    .stages-table tbody tr, .reports-table tbody tr {
        display: table-row !important;
        visibility: visible !important;
    }

    .stages-table tbody, .reports-table tbody {
        display: table-row-group !important;
    }

    /* S'assurer que le contenu des cellules est visible */
    .stages-table td, .reports-table td {
        opacity: 1 !important;
        visibility: visible !important;
        display: table-cell !important;
    }

    /* Spécifique pour les colonnes de données */
    .stages-table td:nth-child(1),
    .stages-table td:nth-child(2),
    .stages-table td:nth-child(3),
    .stages-table td:nth-child(4),
    .stages-table td:nth-child(5),
    .stages-table td:nth-child(6) {
        color: #374151 !important;
        font-weight: 400;
    }

    .reports-table td:nth-child(1),
    .reports-table td:nth-child(2),
    .reports-table td:nth-child(3),
    .reports-table td:nth-child(4),
    .reports-table td:nth-child(5) {
        color: #374151 !important;
        font-weight: 400;
    }

    /* Messages quand aucune donnée */
    .stages-table tbody tr td[colspan="6"],
    .reports-table tbody tr td[colspan="5"] {
        text-align: center !important;
        font-style: italic;
        color: #6b7280 !important;
        padding: 2rem !important;
        background: #f9fafb !important;
    }

    /* Badges - S'assurer qu'ils sont visibles */
    .badge {
        padding: 0.25rem 0.75rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block !important;
        visibility: visible !important;
    }

    .badge-success {
        background: #dcfce7 !important;
        color: #166534 !important;
    }

    .badge-warning {
        background: #fef3c7 !important;
        color: #92400e !important;
    }

    .badge-info {
        background: #dbeafe !important;
        color: #1e40af !important;
    }

    .badge-primary {
        background: #e0e7ff !important;
        color: #3730a3 !important;
    }

    .badge-secondary {
        background: #f1f5f9 !important;
        color: #475569 !important;
    }

    /* Liens dans les tableaux */
    .btn-view {
        color: #154360 !important;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        transition: background-color 0.2s;
    }

    .btn-view:hover {
        background-color: #f0f9ff;
        color: #0c4a6e !important;
    }

    .text-muted {
        color: #9ca3af !important;
        font-style: italic;
    }

    /* Spécifique pour le modal des notes */
    #modalGrades .modal-body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #374151 !important;
    }

    #modalGrades .semester {
        margin: 20px 0;
        display: block !important;
        visibility: visible !important;
    }

    #modalGrades .semester-title {
        background: #f0f0f0;
        padding: 8px;
        margin: 0 0 10px 0;
        font-size: 14px;
        border-left: 4px solid #333;
        font-weight: bold;
        color: #374151 !important;
    }

    #modalGrades .grades-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-bottom: 20px;
        display: table !important;
    }

    #modalGrades .grades-table th,
    #modalGrades .grades-table td {
        border: 1px solid #ccc;
        padding: 4px 6px;
        text-align: left;
        color: #374151 !important;
        background: white;
    }

    #modalGrades .grades-table th {
        background: #f8f8f8 !important;
        color: #000 !important;
        font-weight: bold;
        text-align: center;
    }

    #modalGrades .grades-table .numeric {
        text-align: center;
    }

    #modalGrades .section-header {
        background: #e8e8e8 !important;
        font-weight: bold;
    }

    #modalGrades .total-row {
        background: #f0f0f0 !important;
        font-weight: bold;
    }

    #modalGrades .total-row td,
    #modalGrades .semester-result-row td {
        border-top: 2px solid #999;
    }

    /* S'assurer que tous les paragraphes sont visibles */
    p {
        color: #374151 !important;
        opacity: 1 !important;
        visibility: visible !important;
    }

    /* Debug pour voir si les éléments sont présents */
    .form-section table tbody {
        display: table-row-group !important;
    }

    .form-section table tbody tr {
        display: table-row !important;
    }

    .form-section table tbody tr td {
        display: table-cell !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modal-content-wrapper {
            grid-template-columns: 1fr;
        }
        
        .profile-sidebar {
            position: static;
        }
        
        .name-group, .address-group {
            grid-template-columns: 1fr;
        }
        
        .modal-header {
            flex-direction: column;
            text-align: center;
        }
        
        .modal-actions {
            justify-content: center;
        }
    }

    /* Animations */
    @keyframes float {
        0%, 100% { transform: translateY(0px) rotate(0deg); }
        50% { transform: translateY(-5px) rotate(2deg); }
    }

    /* Section principale */
    #profil {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 20px;
        padding: 2rem;
        margin: 1rem 0;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
    }

    #profil h2 {
        color: #154360;
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 1rem;
        text-align: center;
    }

    /* Styles pour les alertes */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 12px;
        margin: 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #f59e0b;
    }

    .alert-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #3b82f6;
    }

    /* Améliorer la visibilité des placeholders */
    .form-input::placeholder,
    .contact-input::placeholder {
        color: #9ca3af;
        opacity: 0.8;
    }

    /* S'assurer que les valeurs des champs sont visibles */
    .form-input:not(:placeholder-shown),
    .contact-input:not(:placeholder-shown) {
        color: #374151 !important;
        font-weight: 500;
    }

    /* Debug styles temporaires */
    .form-input[value]:not([value=""]) {
        background-color: #f0f9ff !important;
        border-color: #0ea5e9 !important;
    }

    /* Améliorer l'affichage des selects */
    select.form-input {
        appearance: menulist;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
        padding-right: 2.5rem;
    }
</style>

<div id="profil" class="section">
    <h2>Profil et Informations Personnelles</h2>
    <div class="action-grid">
        <div class="action-card">
            <i class="fas fa-user-edit"></i>
            <h3>Informations Personnelles</h3>
            <p>Mettre à jour vos coordonnées</p>
            <button onclick="openModal('modalProfile')">Modifier</button>
        </div>
        <div class="action-card">
            <i class="fas fa-graduation-cap"></i>
            <h3>Dossier Académique</h3>
            <p>Consulter votre parcours</p>
            <button onclick="openModal('modalAcademic')">Consulter</button>
        </div>
        <div class="action-card">
            <i class="fas fa-chart-line"></i>
            <h3>Notes et UE/ECUE</h3>
            <p>Vérifier vos notes</p>
            <button onclick="openModal('modalGrades')">Voir Détails</button>
        </div>
    </div>
</div>

<!-- Modal Profil -->
<!-- Modal Profil -->
<div class="modal-overlay" id="modalProfile">
    <div class="modal">
        <div class="modal-header">
            <h2>Modifiez votre profil</h2>
            <div class="modal-header-actions">
                <div class="modal-actions">
                    <button class="button btn-cancel" onclick="closeModal('modalProfile')">Retour</button>
                    <button type="button" class="button" onclick="saveProfile()">Sauvegarder les modifications</button>
                </div>
            </div>
        </div>

        <div class="modal-body">
            <div class="modal-content-wrapper">

                <!-- Sidebar avec photo -->
                <div class="profile-sidebar">
                    <div class="profile-photo">
                        <div class="profile-photo-wrapper">
                            <img id="previewPhoto" src="<?= "assets/uploads/profiles/" . $_SESSION['photo_etd'] ?>" alt="Photo de profil">
                        </div>

                        <div class="profile-photo-actions">
                            <button type="button" class="button" onclick="document.getElementById('photoInput').click()">Modifier la photo</button>
                            <button type="button" class="button delete-photo" onclick="handlePhotoDelete()">Supprimer la photo</button>
                        </div>
                    </div>

                    <?php if ($recupUserData): ?>
                        <div class="profile-info">
                            <h3 class="profile-name"><?= htmlspecialchars($recupUserData['nom_etd'] . ' ' . $recupUserData['prenom_etd']); ?></h3>
                            <p class="profile-role"><?= htmlspecialchars($_SESSION['lib_user_type']); ?></p>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="stat-value"><?= htmlspecialchars($recupUserData['num_carte_etd']); ?></span>
                                    <span class="stat-label">N° Carte Étudiant</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-value"><?= htmlspecialchars($recupUserData['lib_promotion']); ?></span>
                                    <span class="stat-label">Promotion</span>
                                </div>
                            </div>
                        </div>
                </div>

                <!-- Formulaire -->
                <div class="form-container">
                    <form id="profileForm" method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="file" name="photo_etd" id="photoInput" style="display: none;" accept="image/*">
                        <input type="hidden" name="delete_photo" id="deletePhotoFlag" value="0">

                        <!-- Informations Générales -->
                        <div class="form-section">
                            <h4 class="form-section-title">GENERAL</h4>
                            <div class="form-grid" id="form-grid-generals">
                                <div class="name-group">
                                    <div class="form-group">
                                        <label class="form-label">Nom</label>
                                        <input type="text" name="nom" class="form-input" value="<?= htmlspecialchars($recupUserData['nom_etd'] ?? ''); ?>" placeholder="Votre nom">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Prénoms</label>
                                        <input type="text" name="prenom" class="form-input" value="<?= htmlspecialchars($recupUserData['prenom_etd'] ?? ''); ?>" placeholder="Vos prénoms">
                                    </div>
                                </div>

                                <div class="password-group">
                                    <div class="form-group">
                                        <label class="form-label">Votre mot de passe actuel <span style="color: red;">*</span></label>
                                        <div id="password-placeholder">
                                            <input type="password" name="motdepasse_actuel" class="form-input" placeholder="Mot de passe actuel" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Nouveau mot de passe</label>
                                        <input type="password" name="nouveau_mdp" class="form-input" placeholder="Nouveau mot de passe (optionnel)">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Confirmation du mot de passe</label>
                                        <input type="password" name="confirmation_mdp" class="form-input" placeholder="Confirmez le nouveau mot de passe">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div class="form-section">
                            <h4 class="form-section-title">CONTACT</h4>
                            <div class="contact-grid">
                                <div class="contact-input-group">
                                    <div class="contact-input-icon"><i class="fas fa-phone"></i></div>
                                    <div class="contact-input-content">
                                        <div class="contact-input-label">N° téléphone</div>
                                        <input type="tel" name="telephone" class="contact-input" value="<?= htmlspecialchars($recupUserData['num_tel_etd'] ?? ''); ?>" placeholder="Votre numéro de téléphone">
                                    </div>
                                </div>

                                <div class="contact-input-group">
                                    <div class="contact-input-icon"><i class="fas fa-envelope"></i></div>
                                    <div class="contact-input-content">
                                        <div class="contact-input-label">Email</div>
                                        <input type="email" name="email" class="contact-input" value="<?= htmlspecialchars($recupUserData['email_etd'] ?? ''); ?>" placeholder="Votre adresse email">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Autres informations -->
                        <div class="form-section">
                            <h4 class="form-section-title">AUTRES INFORMATIONS</h4>
                            <div class="form-grid" id="other-infos">
                                <div class="top-group">
                                    <div class="form-group">
                                        <label class="form-label">Date de naissance</label>
                                        <input type="date" name="date_naissance" class="form-input" value="<?= htmlspecialchars($recupUserData['date_naissance_etd'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Sexe</label>
                                        <select class="form-input" name="sexe">
                                            <option value="">Sélectionnez un genre</option>
                                            <option value="Homme" <?= ($recupUserData['sexe_etd'] ?? '') == 'Homme' ? 'selected' : ''; ?>>Homme</option>
                                            <option value="Femme" <?= ($recupUserData['sexe_etd'] ?? '') == 'Femme' ? 'selected' : ''; ?>>Femme</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="address-group">
                                    <div class="form-group">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" name="adresse" class="form-input" value="<?= htmlspecialchars($recupUserData['adresse_etd'] ?? ''); ?>" placeholder="Votre adresse">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Ville</label>
                                        <input type="text" name="ville" class="form-input" value="<?= htmlspecialchars($recupUserData['ville_etd'] ?? ''); ?>" placeholder="Votre ville">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Pays</label>
                                        <input type="text" name="pays" class="form-input" value="<?= htmlspecialchars($recupUserData['pays_etd'] ?? ''); ?>" placeholder="Votre pays">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            <?php else: ?>
                <div class="alert alert-warning">
                    <p>Aucune donnée utilisateur trouvée. Veuillez contacter l'administration.</p>
                </div>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- Modal Dossier Académique -->
<div class="modal-overlay" id="modalAcademic">
    <div class="modal">
        <!----
        <button class="close-modal" onclick="closeModal('modalAcademic')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        --->

        <div class="modal-header">
            <h2>Dossier Académique - MASTER 2</h2>
            <div class="modal-actions">
                <button class="button btn-cancel" onclick="closeModal('modalAcademic')">Fermer</button>
                <button class="button" onclick="printAcademicRecord()">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>
        </div>

        <div class="modal-body">
            <!-- Informations Personnelles -->
            <div class="form-section">
                <h4 class="form-section-title">INFORMATIONS PERSONNELLES</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Numéro Carte Étudiant</label>
                        <p class="form-input readonly"><?= htmlspecialchars($recupUserData['num_carte_etd']) ?></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nom et Prénoms</label>
                        <p class="form-input readonly"><?= htmlspecialchars($recupUserData['nom_etd'] . ' ' . $recupUserData['prenom_etd']) ?></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date de Naissance</label>
                        <p class="form-input readonly"><?= date('d/m/Y', strtotime($recupUserData['date_naissance_etd'])) ?></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Universitaire</label>
                        <p class="form-input readonly"><?= htmlspecialchars($recupUserData['email_etd']) ?></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Statut d'Éligibilité</label>
                        <p class="form-input readonly">
                            <span class="badge <?= $recupUserData['statut_eligibilite'] === 'Éligible' ? 'badge-success' : 'badge-warning' ?>">
                                <?= htmlspecialchars($recupUserData['statut_eligibilite']) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>


            <!-- Parcours Académique -->
            <div class="form-section">
                <h4 class="form-section-title">PARCOURS ACADÉMIQUE</h4>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">UFR</label>
                        <p class="form-input readonly">MATHÉMATIQUES ET INFORMATIQUES</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Filière</label>
                        <p class="form-input readonly">MIAGE</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Spécialité</label>
                        <p class="form-input readonly">Étudiant</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Niveau Actuel</label>
                        <p class="form-input readonly"><?= htmlspecialchars($recupUserData['lib_niv_etd']) ?></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Année Académique</label>
                        <p class="form-input readonly"><?= htmlspecialchars($recupUserData['lib_promotion']) ?></p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date d'Inscription</label>
                        <p class="form-input readonly"></p>
                    </div>
                </div>
            </div>



            <!-- Stages et Expériences -->
            <div class="form-section">
                <h4 class="form-section-title">STAGES ET EXPÉRIENCES ACADÉMIQUES</h4>
                <table class="stages-table">
                    <thead>
                        <tr>
                            <th>Période</th>
                            <th>Entreprise</th>
                            <th>Type de Stage</th>
                            <th>Durée</th>
                            <th>Tuteur</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stages)): ?>
                            <?php foreach ($stages as $stage): ?>
                                <tr>
                                    <td>
                                        <?= date('d/m/Y', strtotime($stage['date_debut'])) . ' - ' . date('d/m/Y', strtotime($stage['date_fin'])) ?>
                                    </td>
                                    <td><?= htmlspecialchars($stage['lib_entr']) ?></td>
                                    <td>
                                        <?php
                                        $type = strtolower($stage['type_stage']);
                                        $badgeClass = 'badge-secondary';

                                        if (str_contains($type, 'immersion')) {
                                            $badgeClass = 'badge-warning';
                                        } elseif (str_contains($type, 'fin') || str_contains($type, "d'étude")) {
                                            $badgeClass = 'badge-success';
                                        } elseif (str_contains($type, 'observation')) {
                                            $badgeClass = 'badge-info';
                                        } elseif (str_contains($type, 'apprentissage')) {
                                            $badgeClass = 'badge-primary';
                                        }
                                        ?>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($stage['type_stage']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                        $start = new DateTime($stage['date_debut']);
                                        $end = new DateTime($stage['date_fin']);
                                        $interval = $start->diff($end);
                                        echo ($interval->y * 12 + $interval->m) . ' mois';
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($stage['nom_tuteur']) ?></td>
                                    <td><?= htmlspecialchars($stage['telephone_tuteur']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6">Aucun stage enregistré pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mémoires et Rapports -->
            <div class="form-section">
                <h4 class="form-section-title">MÉMOIRES ET RAPPORTS</h4>
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Nom</th>
                            <th>Thème</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rapports)): ?>
                            <?php foreach ($rapports as $rapport): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($rapport['date_rapport'])) ?></td>
                                    <td><?= htmlspecialchars($rapport['nom_rapport']) ?></td>
                                    <td><?= htmlspecialchars($rapport['theme_memoire']) ?></td>
                                    <td>
                                        <span class="badge <?=
                                                            $rapport['statut_rapport'] === 'Validé' ? 'badge-success' : ($rapport['statut_rapport'] === 'En attente' ? 'badge-warning' : 'badge-secondary')
                                                            ?>">
                                            <?= htmlspecialchars($rapport['statut_rapport']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($rapport['fichier_rapport'])): ?>
                                            <a href="<?= htmlspecialchars($rapport['fichier_rapport']) ?>" class="btn-view" target="_blank">
                                                <i class="fas fa-eye"></i> Voir
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Aucun fichier</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">Aucun rapport trouvé pour cet étudiant.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<!-- Styles pour la modale des notes -->
<style>
    #modalGrades .modal-body {
        font-family: Arial, sans-serif;
        font-size: 12px;
    }

    #modalGrades .semester {
        margin: 20px 0;
    }

    #modalGrades .semester-title {
        background: #f0f0f0;
        padding: 8px;
        margin: 0 0 10px 0;
        font-size: 14px;
        border-left: 4px solid #333;
        font-weight: bold;
    }

    #modalGrades .grades-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-bottom: 20px;
    }

    #modalGrades .grades-table th,
    #modalGrades .grades-table td {
        border: 1px solid #ccc;
        padding: 4px 6px;
        text-align: left;
    }

    #modalGrades .grades-table th {
        background: #f8f8f8;
        color: #000;
        font-weight: bold;
        text-align: center;
    }

    #modalGrades .grades-table .numeric {
        text-align: center;
    }

    #modalGrades .section-header {
        background: #e8e8e8;
        font-weight: bold;
    }

    #modalGrades .total-row {
        background: #f0f0f0;
        font-weight: bold;
    }

    #modalGrades .total-row td,
    #modalGrades .semester-result-row td {
        border-top: 2px solid #999;
    }
</style>

<!-- Modal Notes et UE/ECUE -->
<div class="modal-overlay" id="modalGrades">
    <div class="modal">
        <div class="modal-header">
            <h2>Relevé de Notes</h2>
            <div class="modal-actions">
                <button class="button btn-cancel" onclick="closeModal('modalGrades')">Fermer</button>
                <button class="button" onclick="printGrades()">
                    <i class="fas fa-print"></i> Imprimer
                </button>
            </div>
        </div>

        <div class="modal-body">
            <?php
            if (empty($notesParSemestre)) {
                echo "<p>Aucune note enregistrée pour le moment.</p>";
            } else {
                foreach ($notesParSemestre as $semestre => $ues) {
                    // 1. Pré-traitement: séparer les UE et calculer leurs moyennes
                    $ues_majeures = [];
                    $ues_mineures = [];

                    foreach ($ues as $id_ue => $ue_data) {
                        $total_notes_ue = 0;
                        $total_credits_ue = 0;
                        foreach ($ue_data['notes'] as $note) {
                            $total_notes_ue += $note['note'] * $note['credit'];
                            $total_credits_ue += $note['credit'];
                        }
                        $moyenne_ue = ($total_credits_ue > 0) ? $total_notes_ue / $total_credits_ue : 0;

                        $ue_details = [
                            'id_ue' => $id_ue,
                            'lib_ue' => $ue_data['lib_ue'],
                            'coef' => $ue_data['credit_ue'], // Coef = crédit total de l'UE
                            'moyenne' => $moyenne_ue,
                            'credits_obtenus' => ($moyenne_ue >= 10) ? $ue_data['credit_ue'] : 0
                        ];

                        if ($ue_data['credit_ue'] >= 4) {
                            $ues_majeures[] = $ue_details;
                        } else {
                            $ues_mineures[] = $ue_details;
                        }
                    }
            ?>

                    <div class="semester">
                        <h3 class="semester-title"><?= strtoupper($semestre) ?></h3>
                        <table class="grades-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th style="width: 50%;">Unité d'Enseignement</th>
                                    <th>Coef</th>
                                    <th>Moyenne/20</th>
                                    <th>Crédits Obtenus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- UE Majeures -->
                                <?php if (!empty($ues_majeures)): ?>
                                    <tr class="section-header">
                                        <td colspan="5"><strong>UE MAJEURES</strong></td>
                                    </tr>
                                    <?php
                                    $total_coef_maj = 0;
                                    $total_moy_pond_maj = 0;
                                    $total_cred_obtenus_maj = 0;
                                    foreach ($ues_majeures as $ue) {
                                        echo "<tr>
                                        <td>" . htmlspecialchars($ue['id_ue']) . "</td>
                                        <td>" . htmlspecialchars($ue['lib_ue']) . "</td>
                                        <td class='numeric'>" . htmlspecialchars($ue['coef']) . "</td>
                                        <td class='numeric'>" . number_format($ue['moyenne'], 2) . "</td>
                                        <td class='numeric'>" . htmlspecialchars($ue['credits_obtenus']) . "</td>
                                    </tr>";
                                        $total_coef_maj += $ue['coef'];
                                        $total_moy_pond_maj += $ue['moyenne'] * $ue['coef'];
                                        $total_cred_obtenus_maj += $ue['credits_obtenus'];
                                    }
                                    $moyenne_maj = ($total_coef_maj > 0) ? $total_moy_pond_maj / $total_coef_maj : 0;
                                    ?>
                                    <tr class="total-row">
                                        <td colspan="2"><strong>Moyenne UE Majeures et crédits</strong></td>
                                        <td class="numeric"><strong><?= $total_coef_maj ?></strong></td>
                                        <td class="numeric"><strong><?= number_format($moyenne_maj, 2) ?></strong></td>
                                        <td class="numeric"><strong><?= $total_cred_obtenus_maj ?></strong></td>
                                    </tr>
                                <?php endif; ?>

                                <!-- UE Mineures -->
                                <?php if (!empty($ues_mineures)): ?>
                                    <tr class="section-header">
                                        <td colspan="5"><strong>UE MINEURES</strong></td>
                                    </tr>
                                    <?php
                                    $total_coef_min = 0;
                                    $total_moy_pond_min = 0;
                                    $total_cred_obtenus_min = 0;
                                    foreach ($ues_mineures as $ue) {
                                        echo "<tr>
                                        <td>" . htmlspecialchars($ue['id_ue']) . "</td>
                                        <td>" . htmlspecialchars($ue['lib_ue']) . "</td>
                                        <td class='numeric'>" . htmlspecialchars($ue['coef']) . "</td>
                                        <td class='numeric'>" . number_format($ue['moyenne'], 2) . "</td>
                                        <td class='numeric'>" . htmlspecialchars($ue['credits_obtenus']) . "</td>
                                    </tr>";
                                        $total_coef_min += $ue['coef'];
                                        $total_moy_pond_min += $ue['moyenne'] * $ue['coef'];
                                        $total_cred_obtenus_min += $ue['credits_obtenus'];
                                    }
                                    $moyenne_min = ($total_coef_min > 0) ? $total_moy_pond_min / $total_coef_min : 0;
                                    ?>
                                    <tr class="total-row">
                                        <td colspan="2"><strong>Moyenne UE Mineures et crédits</strong></td>
                                        <td class="numeric"><strong><?= $total_coef_min ?></strong></td>
                                        <td class="numeric"><strong><?= number_format($moyenne_min, 2) ?></strong></td>
                                        <td class="numeric"><strong><?= $total_cred_obtenus_min ?></strong></td>
                                    </tr>
                                <?php endif; ?>

                                <?php
                                // Calculs finaux pour le semestre
                                $total_coef_sem = ($total_coef_maj ?? 0) + ($total_coef_min ?? 0);
                                $total_credits_obtenus_sem = ($total_cred_obtenus_maj ?? 0) + ($total_cred_obtenus_min ?? 0);
                                $moyenne_semestre = ($total_coef_sem > 0) ? (($total_moy_pond_maj ?? 0) + ($total_moy_pond_min ?? 0)) / $total_coef_sem : 0;
                                $resultat_semestre = ($moyenne_semestre >= 10 && $total_credits_obtenus_sem == $total_coef_sem) ? "Admis" : "Non admis";
                                ?>
                                <tr class="total-row semester-result-row">
                                    <td colspan="2"><strong>Total crédits</strong></td>
                                    <td></td>
                                    <td class="numeric"><strong><?= $total_coef_sem ?></strong></td>
                                    <td class="numeric"><strong><?= $total_credits_obtenus_sem ?></strong></td>
                                </tr>
                                <tr class="semester-result-row">
                                    <td colspan="2"><strong>Résultat Semestre</strong></td>
                                    <td colspan="1"><strong><?= $resultat_semestre ?></strong></td>
                                    <td colspan="1"><strong>Moyenne semestre</strong></td>
                                    <td colspan="1" class="numeric"><strong><?= number_format($moyenne_semestre, 2) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
            <?php
                } // Fin de la boucle foreach semestre
            } // Fin du else
            ?>
        </div>
    </div>
</div>



<script>
    // Détecter si on est dans un iframe
    const isInIframe = window.self !== window.top;


    document.addEventListener('DOMContentLoaded', function() {
        const motdepasseActuel = document.querySelector('input[name="motdepasse_actuel"]');

        if (!motdepasseActuel) return;

        let lastValue = motdepasseActuel.value;

        // Vérification initiale après remplissage auto
        setTimeout(() => {
            if (motdepasseActuel.value && motdepasseActuel.value !== '') {
                console.log('🔐 Mot de passe auto-rempli détecté.');
                motdepasseActuel.classList.add('autofilled');
                showAlert('info', 'Le mot de passe semble avoir été rempli automatiquement.');
            }
        }, 500);

        // Surveillance de changements manuels ou auto
        setInterval(() => {
            if (motdepasseActuel.value !== lastValue) {
                lastValue = motdepasseActuel.value;

                if (motdepasseActuel.value !== '') {
                    console.log('🔄 Champ mot de passe modifié dynamiquement.');
                    motdepasseActuel.classList.add('autofilled');
                } else {
                    motdepasseActuel.classList.remove('autofilled');
                }
            }
        }, 300);
    });


    // Ajuster la fonction openModal pour éviter les conflits
    function openModal(modalId) {
        console.log('Tentative d\'ouverture de la modale:', modalId);
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('Modal non trouvée:', modalId);
            return;
        }

        // Ajouter la classe active
        modal.classList.add('active');

        // Bloquer le scroll de la page principale si pas dans un iframe
        if (!isInIframe) {
            document.body.style.overflow = 'hidden';
        }

        // Force un reflow pour s'assurer que l'animation se déclenche
        modal.offsetHeight;

        // Si c'est la modale académique, forcer l'affichage des tableaux
        if (modalId === 'modalAcademic') {
            setTimeout(() => {
                // Forcer l'affichage des tableaux de stages
                const stagesTable = modal.querySelector('.stages-table tbody');
                if (stagesTable) {
                    stagesTable.style.display = 'table-row-group !important';
                    stagesTable.style.visibility = 'visible !important';
                    
                    Array.from(stagesTable.rows).forEach((row) => {
                        row.style.display = 'table-row !important';
                        row.style.visibility = 'visible !important';
                        row.style.opacity = '1 !important';
                    });
                }
                
                // Forcer l'affichage des tableaux de rapports
                const reportsTable = modal.querySelector('.reports-table tbody');
                if (reportsTable) {
                    reportsTable.style.display = 'table-row-group !important';
                    reportsTable.style.visibility = 'visible !important';
                    
                    Array.from(reportsTable.rows).forEach((row) => {
                        row.style.display = 'table-row !important';
                        row.style.visibility = 'visible !important';
                        row.style.opacity = '1 !important';
                    });
                }
                
                // Forcer l'affichage des badges
                const badges = modal.querySelectorAll('.badge');
                badges.forEach(badge => {
                    badge.style.display = 'inline-block !important';
                    badge.style.visibility = 'visible !important';
                    badge.style.opacity = '1 !important';
                });
                
                console.log('Affichage forcé des tableaux et badges dans modalAcademic');
            }, 100);
        }

        // Si c'est la modale des notes, forcer l'affichage
        if (modalId === 'modalGrades') {
            setTimeout(() => {
                const gradesContent = modal.querySelector('.modal-body');
                if (gradesContent) {
                    // Forcer l'affichage de tout le contenu
                    gradesContent.style.display = 'block !important';
                    gradesContent.style.visibility = 'visible !important';
                    
                    // Forcer l'affichage des semestres
                    const semesters = modal.querySelectorAll('.semester');
                    semesters.forEach(semester => {
                        semester.style.display = 'block !important';
                        semester.style.visibility = 'visible !important';
                    });
                    
                    // Forcer l'affichage des tableaux de notes
                    const gradesTables = modal.querySelectorAll('.grades-table');
                    gradesTables.forEach(table => {
                        table.style.display = 'table !important';
                        table.style.visibility = 'visible !important';
                    });
                }
                console.log('Affichage forcé du contenu des notes');
            }, 100);
        }

        console.log('Modal ouverte:', modalId);
    }

    function closeModal(modalId) {
        console.log('Fermeture de la modale:', modalId);
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error('Modal non trouvée:', modalId);
            return;
        }

        modal.classList.remove('active');

        // Restaurer le scroll de la page principale si pas dans un iframe
        if (!isInIframe) {
            document.body.style.overflow = 'auto';
        }
    }

    // Vérifier que les éléments existent avant d'ajouter les écouteurs
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM chargé');

        // Vérifier la présence des modales
        const modales = ['modalProfile', 'modalAcademic', 'modalGrades'];
        modales.forEach(id => {
            const modal = document.getElementById(id);
            if (modal) {
                console.log('Modal trouvée:', id);
            } else {
                console.error('Modal manquante:', id);
            }
        });

        // Vérifier et forcer l'affichage des tableaux
        function checkAndFixTableDisplay() {
            // Vérifier les tableaux de stages
            const stagesTable = document.querySelector('.stages-table tbody');
            if (stagesTable) {
                console.log('Table stages trouvée, nombre de lignes:', stagesTable.rows.length);
                // Forcer l'affichage
                stagesTable.style.display = 'table-row-group';
                stagesTable.style.visibility = 'visible';
                
                // Vérifier chaque ligne
                Array.from(stagesTable.rows).forEach((row, index) => {
                    row.style.display = 'table-row';
                    row.style.visibility = 'visible';
                    console.log(`Ligne stage ${index + 1}:`, row.innerText);
                });
            } else {
                console.log('Table stages non trouvée');
            }

            // Vérifier les tableaux de rapports
            const reportsTable = document.querySelector('.reports-table tbody');
            if (reportsTable) {
                console.log('Table rapports trouvée, nombre de lignes:', reportsTable.rows.length);
                // Forcer l'affichage
                reportsTable.style.display = 'table-row-group';
                reportsTable.style.visibility = 'visible';
                
                // Vérifier chaque ligne
                Array.from(reportsTable.rows).forEach((row, index) => {
                    row.style.display = 'table-row';
                    row.style.visibility = 'visible';
                    console.log(`Ligne rapport ${index + 1}:`, row.innerText);
                });
            } else {
                console.log('Table rapports non trouvée');
            }

            // Vérifier les badges
            const badges = document.querySelectorAll('.badge');
            badges.forEach((badge, index) => {
                badge.style.display = 'inline-block';
                badge.style.visibility = 'visible';
                console.log(`Badge ${index + 1}:`, badge.innerText);
            });
        }

        // Exécuter la vérification immédiatement et après un délai
        checkAndFixTableDisplay();
        setTimeout(checkAndFixTableDisplay, 500);

        // Attacher les événements de clic sur l'overlay
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this.id);
                }
            });
        });

        // Gestion du ESC pour fermer les modales
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activeModal = document.querySelector('.modal-overlay.active');
                if (activeModal) {
                    closeModal(activeModal.id);
                }
            }
        });

        // Animation au chargement des cartes
        const actionCards = document.querySelectorAll('.action-card');
        actionCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';

            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // Fonction pour sauvegarder le profil
    function saveProfile() {
        console.log('Début de la sauvegarde du profil');
        const form = document.getElementById('profileForm');
        const nouveauMdp = form.querySelector('input[name="nouveau_mdp"]').value;
        const confirmationMdp = form.querySelector('input[name="confirmation_mdp"]').value;

        // Validation du mot de passe
        if (nouveauMdp || confirmationMdp) {
            if (nouveauMdp !== confirmationMdp) {
                showAlert('error', 'Les mots de passe ne correspondent pas !');
                return;
            }
            if (nouveauMdp.length < 6) {
                showAlert('error', 'Le mot de passe doit contenir au moins 6 caractères !');
                return;
            }
        }

        // Validation des champs obligatoires
        const requiredFields = ['nom', 'prenom', 'email', 'telephone', 'date_naissance', 'sexe'];
        for (const field of requiredFields) {
            const input = form.querySelector(`[name="${field}"]`);
            if (!input.value.trim()) {
                showAlert('error', `Le champ ${input.previousElementSibling.textContent} est obligatoire !`);
                input.focus();
                return;
            }
        }

        // Validation de l'email (version plus permissive)
        const email = form.querySelector('input[name="email"]').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[a-z]{2,}$/i; // permet .cor, .xyz, etc.
        if (!emailRegex.test(email)) {
            showAlert('error', 'Veuillez entrer une adresse email valide !');
            return;
        }

        // Afficher le bouton de chargement
        const saveBtn = document.querySelector('.modal-actions button:last-child');
        saveBtn.classList.add('loading');
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

        // Envoyer en AJAX
        const formData = new FormData(form);

        fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(response => {
                // Pas besoin d'alerte ici, car la page va recharger
                // et afficher le message de session.
                closeModal('modalProfile');
                window.location.reload();
            })
            .catch(error => {
                console.error('Erreur AJAX :', error);
                showAlert('error', 'Une erreur est survenue lors de la sauvegarde.');
                saveBtn.classList.remove('loading');
                saveBtn.innerHTML = 'Sauvegarder les modifications';
            });
    }


    // Fonction pour afficher les messages d'alerte
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
        <div class="alert-icon">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        </div>
        <span>${message}</span>
    `;

        // Trouver la modale active ou fallback vers body
        const modalBody = document.querySelector('.modal-overlay.active .modal-body');
        const target = modalBody || document.body;

        // Ajouter l'alerte
        target.insertBefore(alertDiv, target.firstChild);

        // Animation d'entrée
        setTimeout(() => {
            alertDiv.classList.add('show');
        }, 100);

        // Suppression après 5 secondes
        setTimeout(() => {
            alertDiv.classList.remove('show');
            setTimeout(() => {
                alertDiv.remove();
            }, 300);
        }, 5000);
    }

    // Afficher les messages de session s'ils existent
    <?php if (isset($_SESSION['success_message'])): ?>
        showAlert('success', '<?= $_SESSION['success_message'] ?>');
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        showAlert('error', '<?= $_SESSION['error_message'] ?>');
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    // Fonctions d'impression
    function printAcademicRecord() {
        window.print();
    }

    function printGrades() {
        window.print();
    }

    // Fonction pour charger les notes d'une année spécifique
    function loadGradesForYear(year) {
        // Ici, vous pourriez faire un appel AJAX pour charger les notes de l'année sélectionnée
        console.log('Chargement des notes pour l\'année:', year);
        // Simuler le chargement avec une animation
        const tables = document.querySelectorAll('.grades-table');
        tables.forEach(table => {
            table.style.opacity = '0.5';
            setTimeout(() => {
                table.style.opacity = '1';
            }, 300);
        });
    }

    // Fonction pour visualiser un rapport
    function viewReport(reportId) {
        // Ouvrir le rapport dans une nouvelle fenêtre ou un modal
        window.open(`view_report.php?id=${reportId}`, '_blank');
    }

    // Fonction d'impression du dossier académique
    function printAcademicRecord() {
        window.print();
    }

    // Fonction d'impression des notes
    function printGrades() {
        window.print();
    }

    // Gestion des modales
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Fermer la modale en cliquant sur l'overlay
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    // Gestion du ESC pour fermer les modales
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal-overlay.active');
            if (activeModal) {
                closeModal(activeModal.id);
            }
        }
    });



    document.getElementById('photoInput').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('photo_etd', file);
        formData.append('action', 'update_profile'); // même action que le formulaire
        formData.append('nom', document.querySelector('[name="nom"]').value); // envoyer les champs requis même si non changés
        formData.append('prenom', document.querySelector('[name="prenom"]').value);
        formData.append('email', document.querySelector('[name="email"]').value);
        formData.append('telephone', document.querySelector('[name="telephone"]').value);
        formData.append('date_naissance', document.querySelector('[name="date_naissance"]').value);
        formData.append('sexe', document.querySelector('[name="sexe"]').value);
        formData.append('adresse', document.querySelector('[name="adresse"]').value);
        formData.append('ville', document.querySelector('[name="ville"]').value);
        formData.append('pays', document.querySelector('[name="pays"]').value);

        fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(response => {
                // Affichage immédiat de la photo
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewPhoto').src = e.target.result;
                };
                reader.readAsDataURL(file);

            })
            .catch(err => {
                console.error(err);
                showAlert('error', 'Erreur lors du téléchargement de la photo.');
            });
    });

    // Supprimer photo
    function handlePhotoDelete() {
        if (!confirm("Voulez-vous vraiment supprimer votre photo ?")) return;

        const formData = new FormData();
        formData.append('action', 'update_profile');
        formData.append('delete_photo', '1');

        // Inclure champs requis pour éviter erreurs serveur
        formData.append('nom', document.querySelector('[name="nom"]').value);
        formData.append('prenom', document.querySelector('[name="prenom"]').value);
        formData.append('email', document.querySelector('[name="email"]').value);
        formData.append('telephone', document.querySelector('[name="telephone"]').value);
        formData.append('date_naissance', document.querySelector('[name="date_naissance"]').value);
        formData.append('sexe', document.querySelector('[name="sexe"]').value);
        formData.append('adresse', document.querySelector('[name="adresse"]').value);
        formData.append('ville', document.querySelector('[name="ville"]').value);
        formData.append('pays', document.querySelector('[name="pays"]').value);

        fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(() => {
                document.getElementById('previewPhoto').src = 'assets/images/default_profile.png';

            })
            .catch(() => {
                showAlert('error', 'Erreur lors de la suppression de la photo.');
            });
    }
</script>

<!-- Styles additionnels spécifiques à cette page -->

</style>