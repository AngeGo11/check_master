<?php
session_start();
require_once 'C:/wamp64/www/GSCV/config/db_connect.php';
require_once 'C:/wamp64/www/GSCV/includes/audit_utils.php';

// Vérification si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $id_frais = isset($_POST['id_frais']) ? intval($_POST['id_frais']) : 0;
    $id_niv_etd = isset($_POST['id_niv_etd']) ? intval($_POST['id_niv_etd']) : 0;
    $tarifs = isset($_POST['tarifs']) ? floatval($_POST['tarifs']) : 0;

    // Validation des données
    if ($id_frais > 0 && $id_niv_etd > 0 && $tarifs > 0) {
        try {
            // Vérifier si le niveau d'étude existe
            $check_niveau = $pdo->prepare("SELECT COUNT(*) FROM niveau_etude WHERE id_niv_etd = ?");
            $check_niveau->execute([$id_niv_etd]);
            
            if ($check_niveau->fetchColumn() > 0) {
                // Récupérer l'année académique en cours
                $get_annee = $pdo->prepare("SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours'");
                $get_annee->execute();
                $annee = $get_annee->fetch(PDO::FETCH_ASSOC);
                $id_ac = $annee['id_ac'];

                // Vérifier si les frais existent déjà pour ce niveau et cette année (sauf pour l'enregistrement en cours de modification)
                $check_frais = $pdo->prepare("
                    SELECT COUNT(*) FROM frais_inscription 
                    WHERE id_niv_etd = ? AND id_ac = ? AND id_frais != ?
                ");
                $check_frais->execute([$id_niv_etd, $id_ac, $id_frais]);

                if ($check_frais->fetchColumn() > 0) {
                    $_SESSION['messages'] = "Des frais d'inscription existent déjà pour ce niveau d'étude pour l'année en cours";
                    if (isset($_SESSION['user_id'])) {
                        enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'frais_inscription', 'Modification frais inscription - échec (doublon)', 0);
                    }
                } else {
                    // Mise à jour des frais
                    $update = $pdo->prepare("
                        UPDATE frais_inscription 
                        SET id_niv_etd = ?, montant = ? 
                        WHERE id_frais = ?
                    ");
                    $update->execute([$id_niv_etd, $tarifs, $id_frais]);

                    if ($update->rowCount() > 0) {
                        $_SESSION['messages'] = "Les frais d'inscription ont été modifiés avec succès";
                        if (isset($_SESSION['user_id'])) {
                            enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'frais_inscription', 'Modification frais inscription', 1);
                        }
                    } else {
                        $_SESSION['messages'] = "Aucune modification n'a été effectuée";
                        if (isset($_SESSION['user_id'])) {
                            enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'frais_inscription', 'Modification frais inscription - aucune modification', 0);
                        }
                    }
                }
            } else {
                $_SESSION['messages'] = "Le niveau d'étude sélectionné n'existe pas";
                if (isset($_SESSION['user_id'])) {
                    enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'frais_inscription', 'Modification frais inscription - niveau inexistant', 0);
                }
            }
        } catch (PDOException $e) {
            $_SESSION['messages'] = "Une erreur est survenue lors de la modification : " . $e->getMessage();
            if (isset($_SESSION['user_id'])) {
                enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'frais_inscription', 'Modification frais inscription - erreur DB', 0);
            }
        }
    } else {
        $_SESSION['messages'] = "Veuillez remplir tous les champs correctement";
        if (isset($_SESSION['user_id'])) {
            enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'frais_inscription', 'Modification frais inscription - données invalides', 0);
        }
    }

    // Redirection vers la liste des frais d'inscription
    header("Location: ../liste_frais_inscriptions.php");
    exit();
} else {
    // Si quelqu'un accède directement à ce fichier sans soumettre le formulaire
    header("Location: ../liste_frais_inscriptions.php");
    exit();
}
?> 