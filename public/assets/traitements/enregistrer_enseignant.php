<?php
require_once __DIR__ . '/../../../app/config/config.php';
require_once __DIR__ . '/../../../app/Controllers/EnseignantController.php';

$controller = new EnseignantController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom_ens' => $_POST['nom_ens'] ?? '',
        'prenoms_ens' => $_POST['prenoms_ens'] ?? '',
        'email_ens' => $_POST['email_ens'] ?? '',
        'sexe_ens' => $_POST['sexe_ens'] ?? '',
        'date_entree_fonction' => $_POST['date_entree'] ?? '',
        'id_grd' => $_POST['grade'] ?? '',
        'id_fonction' => $_POST['fonction'] ?? '',
        'id_spe' => $_POST['specialite'] ?? '',
        'num_tel_ens' => $_POST['num_tel_ens'] ?? '',
        'date_naissance_ens' => $_POST['date_naissance_ens'] ?? '',
        'photo_ens' => $_POST['photo_ens'] ?? '',
        'mdp_ens' => $_POST['mdp_ens'] ?? '',
        'date_grd' => $_POST['date_grd'] ?? $_POST['date_entree'] ?? '',
        'date_occup' => $_POST['date_occup'] ?? $_POST['date_entree'] ?? '',
    ];
    
    if (!empty($_POST['id_enseignant'])) {
        // Modification
        $id = $_POST['id_enseignant'];
        $result = $controller->update($id, $data);
        $msg = $result ? 'Enseignant modifié avec succès.' : 'Erreur lors de la modification.';
        $msgType = $result ? 'success' : 'error';
    } else {
        // Ajout
        $result = $controller->store($data);
        $msg = $result ? 'Enseignant ajouté avec succès.' : 'Erreur lors de l\'ajout.';
        $msgType = $result ? 'success' : 'error';
    }
    
    // Redirection avec les paramètres corrects
    $redirectUrl = '../../../app.php?page=ressources_humaines#tab_current';
    if (!empty($msg)) {
        $redirectUrl .= '&message=' . urlencode($msg) . '&type=' . urlencode($msgType);
    }
    
    header('Location: ' . $redirectUrl);
    exit;
}

// Si accès direct, rediriger
header('Location: ../../../app.php?page=ressources_humaines#tab_current');
exit;
