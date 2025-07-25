<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/Controllers/PersonnelAdministratifController.php';

$controller = new PersonnelAdministratifController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'nom' => $_POST['nom_personnel'] ?? '',
        'prenoms' => $_POST['prenoms_personnel'] ?? '',
        'email' => $_POST['email_personnel'] ?? '',
        'autres' => [
            'sexe' => $_POST['sexe_personnel'] ?? '',
            'poste' => $_POST['poste'] ?? '',
            'date_embauche' => $_POST['date_embauche'] ?? '',
            'telephone' => $_POST['telephone'] ?? '',
        ]
    ];
    if (!empty($_POST['id_personnel'])) {
        // Modification
        $id = $_POST['id_personnel'];
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