<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/includes/audit_utils.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/app/Models/AnneeAcademique.php';

// Vérifier si l'utilisateur est connecté et est un personnel administratif
if (!isset($_SESSION['user_id']) || !isset($_SESSION['id_personnel_adm'])) {
    header('Location: ../../authentication.php');
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Récupération des données
    $numero = $_POST['numero'] ?? '';
    $semestre = $_POST['semestre'] ?? '';
    $notes_json = $_POST['notes'] ?? '[]';
    $credits_json = $_POST['credits'] ?? '[]';
    $edit_mode = isset($_POST['edit_mode']);

    if (empty($numero) || empty($semestre)) {
        throw new Exception('Numéro d\'étudiant et semestre requis');
    }

    // Décoder les données JSON
    $notes = json_decode($notes_json, true);
    $credits = json_decode($credits_json, true);

    if (!$notes || !$credits) {
        throw new Exception('Données de notes invalides');
    }

    // Récupérer l'ID de l'étudiant
    $stmt = $pdo->prepare("SELECT num_etd FROM etudiants WHERE num_carte_etd = ?");
    $stmt->execute([$numero]);
    $num_etd = $stmt->fetchColumn();

    if (!$num_etd) {
        throw new Exception('Étudiant non trouvé');
    }

    // Récupérer l'année académique en cours
    $anneeModel = new App\Models\AnneeAcademique($pdo);
    $annee_en_cours = explode("-", $anneeModel->getCurrentAcademicYear());

    $stmt = $pdo->prepare("SELECT id_ac FROM annee_academique WHERE statut_annee = ?");
    $stmt->execute(['En cours']);
    $id_ac = $stmt->fetchColumn();

    // Récupérer l'ID du personnel administratif
    $id_personnel_adm = $_SESSION['id_personnel_adm'] ?? null;
    if (!$id_personnel_adm) {
        throw new Exception('ID du personnel administratif non défini');
    }

    // Vérifier et créer la contrainte d'unicité pour evaluer_ecue si elle n'existe pas
    try {
        // Vérifier si la contrainte existe déjà
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM information_schema.table_constraints 
            WHERE table_schema = DATABASE() 
            AND table_name = 'evaluer_ecue' 
            AND constraint_name = 'unique_evaluation_ecue'
        ");
        $stmt->execute();
        $constraint_exists = $stmt->fetchColumn() > 0;
        
        if (!$constraint_exists) {
            $pdo->exec("
                ALTER TABLE evaluer_ecue 
                ADD CONSTRAINT unique_evaluation_ecue 
                UNIQUE (num_etd, id_ecue, id_semestre, id_ac, id_personnel_adm)
            ");
        }
    } catch (PDOException $e) {
        // Ignorer les erreurs de contrainte existante
        if (strpos($e->getMessage(), 'Duplicate key name') === false && 
            strpos($e->getMessage(), 'Duplicate entry') === false) {
            // Log l'erreur mais continuer
            error_log("Erreur lors de la création de la contrainte: " . $e->getMessage());
        }
    }

    // Démarrer une transaction
    $pdo->beginTransaction();

    $notes_enregistrees = 0;

    // Traiter chaque note
    foreach ($notes as $id_ue => $ecues) {
        foreach ($ecues as $id_ecue => $note) {
            $note = floatval($note);
            $credit = intval($credits[$id_ue][$id_ecue] ?? 0);

            // Validation
            if ($note < 0 || $note > 20) {
                throw new Exception("La note doit être comprise entre 0 et 20");
            }

            if ($credit <= 0) {
                throw new Exception("Le crédit doit être supérieur à 0");
            }

            // Déterminer si c'est une UE évaluée directement ou un ECUE
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ecue WHERE id_ecue = ? AND id_ue = ?");
            $stmt->execute([$id_ecue, $id_ue]);
            $is_ecue = $stmt->fetchColumn() > 0;

            if ($is_ecue) {
                // C'est un ECUE - utiliser INSERT ... ON DUPLICATE KEY UPDATE
                $stmt = $pdo->prepare("
                    INSERT INTO evaluer_ecue (num_etd, id_ecue, id_semestre, id_ac, id_personnel_adm, note, credit, date_eval)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    note = VALUES(note),
                    credit = VALUES(credit),
                    date_eval = NOW()
                ");
                $stmt->execute([$num_etd, $id_ecue, $semestre, $id_ac, $id_personnel_adm, $note, $credit]);
            } else {
                // C'est une UE évaluée directement - utiliser INSERT ... ON DUPLICATE KEY UPDATE
                $stmt = $pdo->prepare("
                    INSERT INTO evaluer_ue (num_etd, id_ue, id_semestre, id_ac, id_personnel_adm, note, credit, date_eval)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    note = VALUES(note),
                    credit = VALUES(credit),
                    date_eval = NOW()
                ");
                $stmt->execute([$num_etd, $id_ue, $semestre, $id_ac, $id_personnel_adm, $note, $credit]);
            }

            $notes_enregistrees++;
        }
    }

    // Valider la transaction
    $pdo->commit();

    // Message de succès
    $action = $edit_mode ? 'mise à jour' : 'enregistrée';
    $message = "Évaluation $action avec succès ($notes_enregistrees matière(s) traitée(s))";
    
    // Log pour débogage
    error_log("Évaluation enregistrée - Étudiant: $numero, Semestre: $semestre, Notes: $notes_enregistrees");
    
    // Définir le message de succès dans la session
    $_SESSION['success_message'] = $message;
    
    // Rediriger vers la page précédente
    header('Location: ../../index_personnel_administratif.php?page=evaluations_etudiants');
    exit;

} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Définir le message d'erreur dans la session
    $_SESSION['error_message'] = $e->getMessage();
    
    // Rediriger vers la page précédente
    header('Location: ../../index_personnel_administratif.php?page=evaluations_etudiants');
    exit;
}
?>