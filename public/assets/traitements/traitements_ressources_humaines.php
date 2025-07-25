<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../Controllers/EnseignantController.php';
require_once __DIR__ . '/../../../Controllers/PersonnelAdministratifController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Initialisation des contrôleurs
$enseignantController = new EnseignantController($pdo);
$personnelController = new PersonnelAdministratifController($pdo);

// Récupération de l'action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create_enseignant':
            $data = [
                'nom' => $_POST['nom'] ?? '',
                'prenoms' => $_POST['prenoms'] ?? '',
                'email' => $_POST['email'] ?? '',
                'grade_id' => $_POST['grade_id'] ?? null,
                'fonction_id' => $_POST['fonction_id'] ?? null,
                'specialite_id' => $_POST['specialite_id'] ?? null,
                'telephone' => $_POST['telephone'] ?? '',
                'adresse' => $_POST['adresse'] ?? ''
            ];
            
            $result = $enseignantController->store($data);
            if ($result) {
                $_SESSION['success_message'] = 'Enseignant ajouté avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de l\'ajout de l\'enseignant';
            }
            break;

        case 'update_enseignant':
            $id = $_POST['id'] ?? 0;
            $data = [
                'nom' => $_POST['nom'] ?? '',
                'prenoms' => $_POST['prenoms'] ?? '',
                'email' => $_POST['email'] ?? '',
                'grade_id' => $_POST['grade_id'] ?? null,
                'fonction_id' => $_POST['fonction_id'] ?? null,
                'specialite_id' => $_POST['specialite_id'] ?? null,
                'telephone' => $_POST['telephone'] ?? '',
                'adresse' => $_POST['adresse'] ?? ''
            ];
            
            $result = $enseignantController->update($id, $data);
            if ($result) {
                $_SESSION['success_message'] = 'Enseignant modifié avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la modification de l\'enseignant';
            }
            break;

        case 'delete_enseignant':
            $id = $_POST['id'] ?? 0;
            $result = $enseignantController->delete($id);
            if ($result) {
                $_SESSION['success_message'] = 'Enseignant supprimé avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la suppression de l\'enseignant';
            }
            break;

        case 'create_personnel':
            $data = [
                'nom' => $_POST['nom'] ?? '',
                'prenoms' => $_POST['prenoms'] ?? '',
                'email' => $_POST['email'] ?? '',
                'groupe_id' => $_POST['groupe_id'] ?? null,
                'telephone' => $_POST['telephone'] ?? '',
                'adresse' => $_POST['adresse'] ?? ''
            ];
            
            $result = $personnelController->store($data);
            if ($result) {
                $_SESSION['success_message'] = 'Personnel administratif ajouté avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de l\'ajout du personnel';
            }
            break;

        case 'update_personnel':
            $id = $_POST['id'] ?? 0;
            $data = [
                'nom' => $_POST['nom'] ?? '',
                'prenoms' => $_POST['prenoms'] ?? '',
                'email' => $_POST['email'] ?? '',
                'groupe_id' => $_POST['groupe_id'] ?? null,
                'telephone' => $_POST['telephone'] ?? '',
                'adresse' => $_POST['adresse'] ?? ''
            ];
            
            $result = $personnelController->update($id, $data);
            if ($result) {
                $_SESSION['success_message'] = 'Personnel administratif modifié avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la modification du personnel';
            }
            break;

        case 'delete_personnel':
            $id = $_POST['id'] ?? 0;
            $result = $personnelController->delete($id);
            if ($result) {
                $_SESSION['success_message'] = 'Personnel administratif supprimé avec succès';
            } else {
                $_SESSION['error_message'] = 'Erreur lors de la suppression du personnel';
            }
            break;

        case 'search_enseignants':
            $search = $_GET['search'] ?? '';
            $filters = [
                'grade' => $_GET['grade'] ?? '',
                'fonction' => $_GET['fonction'] ?? '',
                'specialite' => $_GET['specialite'] ?? ''
            ];
            
            $result = $enseignantController->searchEnseignants($search, $filters);
            echo json_encode(['success' => true, 'data' => $result]);
            exit;

        case 'search_personnel':
            $search = $_GET['search'] ?? '';
            $filters = [
                'groupe' => $_GET['groupe'] ?? ''
            ];
            
            $result = $personnelController->searchPersonnel($search, $filters);
            echo json_encode(['success' => true, 'data' => $result]);
            exit;

        case 'get_enseignant':
            $id = $_GET['id'] ?? 0;
            $enseignant = $enseignantController->show($id);
            echo json_encode(['success' => true, 'data' => $enseignant]);
            exit;

        case 'get_personnel':
            $id = $_GET['id'] ?? 0;
            $personnel = $personnelController->show($id);
            echo json_encode(['success' => true, 'data' => $personnel]);
            exit;

        case 'get_statistics':
            $stats_enseignants = $enseignantController->getStatistics();
            $stats_personnel = $personnelController->getStatistics();
            
            echo json_encode([
                'success' => true, 
                'enseignants' => $stats_enseignants,
                'personnel' => $stats_personnel
            ]);
            exit;

        default:
            $_SESSION['error_message'] = 'Action non reconnue';
            break;
    }

    // Redirection vers la page des ressources humaines
    header('Location: ../../index_commission.php?page=ressources_humaines');
    exit;

} catch (Exception $e) {
    error_log("Erreur dans traitements_ressources_humaines.php: " . $e->getMessage());
    $_SESSION['error_message'] = 'Une erreur est survenue: ' . $e->getMessage();
    header('Location: ../../index_commission.php?page=ressources_humaines');
    exit;
}
?> 