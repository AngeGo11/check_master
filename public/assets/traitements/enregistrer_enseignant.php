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
        $msg = $result ? 'Modification réussie.' : 'Erreur lors de la modification.';
    } else {
        // Ajout
        $result = $controller->store($data);
        $msg = $result ? 'Ajout réussi.' : 'Erreur lors de l\'ajout.';
    }
    header('Location: ../../ressources_humaines.php?message=' . urlencode($msg));
    exit;
}
// Si accès direct, rediriger
header('Location: ../../ressources_humaines.php');
exit;
