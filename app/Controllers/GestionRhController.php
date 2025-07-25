<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../Models/Enseignant.php';
require_once __DIR__ . '/../Models/PersonnelAdministratif.php';
require_once __DIR__ . '/../Models/Grade.php';
require_once __DIR__ . '/../Models/Fonction.php';
require_once __DIR__ . '/../Models/Specialite.php';

class GestionRhController
{
    private $enseignantModel;
    private $persAdminModel;
    private $gradeModel;
    private $fonctionModel;
    private $specialiteModel;

    public function __construct($pdo)
    {
        $this->enseignantModel = new App\Models\Enseignant($pdo);
        $this->persAdminModel = new App\Models\PersonnelAdministratif($pdo);
        $this->gradeModel = new App\Models\Grade($pdo);
        $this->fonctionModel = new App\Models\Fonction($pdo);
        $this->specialiteModel = new App\Models\Specialite($pdo);
    }

    public function handleRequest()
    {
        $messageErreur = '';
        $messageSuccess = '';
        $enseignant_a_modifier = null;
        $pers_admin_a_modifier = null;

        // Gestion des enseignants
        if (isset($_GET['tab']) && $_GET['tab'] === 'enseignant') {
            // Ajout ou modification d'un enseignant
            if (isset($_POST['btn_add_enseignant']) || isset($_POST['btn_modifier_enseignant'])) {
                $nom = $_POST['nom_ens'] ?? '';
                $prenom = $_POST['prenom_ens'] ?? '';
                $email = $_POST['email_ens'] ?? '';
                $id_grade = $_POST['id_grade'] ?? null;
                $id_specialite = $_POST['id_specialite'] ?? null;
                $id_fonction = $_POST['id_fonction'] ?? null;
                $date_grade = $_POST['date_grade'] ?? null;
                $date_fonction = $_POST['date_fonction'] ?? null;
                $type_enseignant = $_POST['type_enseignant'] ?? null;

                if (!empty($_POST['id_enseignant'])) {
                    // Modification
                    if ($this->enseignantModel->update($_POST['id_enseignant'], [
                        'nom_ens' => $nom,
                        'prenoms_ens' => $prenom,
                        'email_ens' => $email,
                        'id_grd' => $id_grade,
                        'id_spe' => $id_specialite,
                        'id_fonction' => $id_fonction,
                        'date_grd' => $date_grade,
                        'date_occup' => $date_fonction,
                        'type_enseignant' => $type_enseignant
                    ])) {
                        $messageSuccess = "Enseignant modifié avec succès.";
                    } else {
                        $messageErreur = "Erreur lors de la modification de l'enseignant.";
                    }
                } else {
                    // Ajout
                    if ($this->enseignantModel->create([
                        'nom_ens' => $nom,
                        'prenoms_ens' => $prenom,
                        'email_ens' => $email,
                        'id_grd' => $id_grade,
                        'id_spe' => $id_specialite,
                        'id_fonction' => $id_fonction,
                        'date_grd' => $date_grade,
                        'date_occup' => $date_fonction,
                        'type_enseignant' => $type_enseignant
                    ])) {
                        $messageSuccess = "Enseignant ajouté avec succès.";
                    } else {
                        $messageErreur = "Erreur lors de l'ajout de l'enseignant.";
                    }
                }
            }

            // Suppression multiple ou individuelle
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    if (!$this->enseignantModel->delete($id)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $messageSuccess = "Enseignant(s) supprimé(s) avec succès.";
                } else {
                    $messageErreur = "Erreur lors de la suppression de l'enseignant.";
                }
            }

            // Récupération de l'enseignant à modifier
            $enseignant_a_modifier = null;
            if (isset($_GET['id_enseignant'])) {
                $enseignant_a_modifier = $this->enseignantModel->find($_GET['id_enseignant']);
            }
        }
        // Gestion du personnel administratif
        else if (isset($_GET['tab']) && $_GET['tab'] === 'pers_admin') {
            // Ajout ou modification d'un membre du personnel
            if (isset($_POST['btn_add_pers_admin']) || isset($_POST['btn_modifier_pers_admin'])) {
                $nom = $_POST['nom'] ?? '';
                $prenom = $_POST['prenom'] ?? '';
                $email = $_POST['email'] ?? '';
                $telephone = $_POST['telephone'] ?? '';
                $poste = $_POST['poste'] ?? '';
                $date_embauche = $_POST['date_embauche'] ?? '';

                if (!empty($_POST['id_pers_admin'])) {
                    // Modification
                    if ($this->persAdminModel->modifierPersonnelAdministratif(
                        $_POST['id_pers_admin'],
                        $nom,
                        $prenom,
                        $email,
                        [
                            'telephone' => $telephone,
                            'poste' => $poste,
                            'date_embauche' => $date_embauche
                        ]
                    )) {
                        $messageSuccess = "Personnel administratif modifié avec succès.";
                    } else {
                        $messageErreur = "Erreur lors de la modification du personnel administratif.";
                    }
                } else {
                    // Ajout
                    if ($this->persAdminModel->ajouterPersonnelAdministratif($nom, $prenom, $email, [
                        'telephone' => $telephone,
                        'poste' => $poste,
                        'date_embauche' => $date_embauche
                    ])) {
                        $messageSuccess = "Personnel administratif ajouté avec succès.";
                    } else {
                        $messageErreur = "Erreur lors de l'ajout du personnel administratif.";
                    }
                }
            }

            // Suppression multiple
            if (isset($_POST['submit_delete_multiple']) && isset($_POST['selected_ids'])) {
                $success = true;
                foreach ($_POST['selected_ids'] as $id) {
                    if (!$this->persAdminModel->supprimerPersonnelAdministratif($id)) {
                        $success = false;
                        break;
                    }
                }

                if ($success) {
                    $messageSuccess = "Personnel administratif supprimé avec succès.";
                } else {
                    $messageErreur = "Erreur lors de la suppression du personnel administratif.";
                }
            }

            // Récupération du membre à modifier
            if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id_pers_admin'])) {
                $pers_admin_a_modifier = $this->persAdminModel->getPersonnelAdministratifById($_GET['id_pers_admin']);
            }
        }

        // Variables communes pour toutes les vues
        $GLOBALS['messageErreur'] = $messageErreur;
        $GLOBALS['messageSuccess'] = $messageSuccess;
        $GLOBALS['pers_admin_a_modifier'] = $pers_admin_a_modifier;
        $GLOBALS['enseignant_a_modifier'] = $enseignant_a_modifier;
        $GLOBALS['listeEnseignants'] = $this->enseignantModel->getAllEnseignants();
        $GLOBALS['listePersAdmin'] = $this->persAdminModel->getAllPersonnelAdministratif();
        $GLOBALS['listeGrades'] = $this->gradeModel->getAllGrades();
        $GLOBALS['listeFonctions'] = $this->fonctionModel->getAllFonctions();
        $GLOBALS['listeSpecialites'] = $this->specialiteModel->getAllSpecialites();
    }
} 