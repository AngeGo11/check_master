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
        $msg = $result ? 'Membre du personnel modifié avec succès.' : 'Erreur lors de la modification.';
        $msgType = $result ? 'success' : 'error';
    } else {
        // Ajout
        $result = $controller->store($data);
        $msg = $result ? 'Membre du personnel ajouté avec succès.' : 'Erreur lors de l\'ajout.';
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