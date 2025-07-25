<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/config/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/app/Models/AnneeAcademique.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV+/includes/audit_utils.php';

// Récupération de l'année académique en cours
$anneeModel = new App\Models\AnneeAcademique($pdo);
$_SESSION['current_year'] = $anneeModel->getCurrentAcademicYear();

// Fonction pour récupérer les statistiques
function getStatistics($pdo)
{
    $current_year = $_SESSION['current_year'];

    //Récupération de l'année academique (id)
    $query = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_ac = $result['id_ac'];

    $stats = [
        'total' => 0,
        'en_cours' => 0,
        'resolues' => 0
    ];

    try {
        // Total des réclamations
        $sql_total = "SELECT COUNT(*) as total FROM reclamations WHERE id_ac = ?";
        $stmt_total = $pdo->prepare($sql_total);
        $stmt_total->execute([$id_ac]);
        $stats['total'] = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

        // Réclamations en cours - CORRECTION: utiliser $id_ac au lieu de $current_year
        $sql_en_cours = "SELECT COUNT(*) as en_cours FROM reclamations WHERE id_ac = ? AND statut_reclamation = 'En attente'";
        $stmt_en_cours = $pdo->prepare($sql_en_cours);
        $stmt_en_cours->execute([$id_ac]); // CORRIGÉ
        $stats['en_cours'] = $stmt_en_cours->fetch(PDO::FETCH_ASSOC)['en_cours'];

        // Réclamations résolues - CORRECTION: utiliser $id_ac au lieu de $current_year
        $sql_resolues = "SELECT COUNT(*) as resolues FROM reclamations WHERE id_ac = ? AND statut_reclamation = 'Traitée'";
        $stmt_resolues = $pdo->prepare($sql_resolues);
        $stmt_resolues->execute([$id_ac]); // CORRIGÉ
        $stats['resolues'] = $stmt_resolues->fetch(PDO::FETCH_ASSOC)['resolues'];

        // Log des statistiques
        error_log("Statistiques récupérées : " . print_r($stats, true));
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques : " . $e->getMessage());
    }

    return $stats;
}

// Fonction pour récupérer les réclamations avec filtres
function getReclamations($pdo, $search = '', $date_filter = '', $status_filter = '')
{
    // CORRECTION: récupérer l'id_ac correctement
    $query = "SELECT id_ac FROM annee_academique WHERE statut_annee = 'En cours' ";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $id_ac = $result['id_ac'];
    
    $params = [$id_ac]; // CORRIGÉ

    $sql = "SELECT r.*,
                        e.nom_etd,
                        e.prenom_etd,
                        e.num_carte_etd
                        FROM reclamations r
                        JOIN etudiants e ON e.num_etd = r.num_etd
                        WHERE r.id_ac = ?";

    if (!empty($search)) {
        $sql .= " AND (e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR r.motif_reclamation LIKE ?)";
        $search_param = "%$search%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
    }

    if (!empty($date_filter)) {
        switch ($date_filter) {
            case 'today':
                $sql .= " AND DATE(r.date_reclamation) = CURDATE()";
                break;
            case 'week':
                $sql .= " AND YEARWEEK(r.date_reclamation) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $sql .= " AND MONTH(r.date_reclamation) = MONTH(CURDATE()) AND YEAR(r.date_reclamation) = YEAR(CURDATE())";
                break;
        }
    }

    if (!empty($status_filter)) {
        $sql .= " AND r.statut_reclamation = ?";
        $params[] = $status_filter;
    }

    $sql .= " ORDER BY r.date_reclamation DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Log du résultat
        error_log("Nombre de réclamations trouvées : " . count($result));

        return $result;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des réclamations : " . $e->getMessage());
        return [];
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'changer_statut':
            try {
                $reclamation_id = $_POST['reclamation_id'];
                $commentaire = $_POST['retour_traitement'] ?? null; // CORRECTION: utiliser 'retour_traitement'

                // CORRECTION: Ajout de logs pour debug
                error_log("Tentative de changement de statut:");
                error_log("ID: " . $reclamation_id);
                error_log("Nouveau statut: " . $nouveau_statut);
                error_log("Commentaire: " . $commentaire);

                $sql = "UPDATE reclamations SET 
                        statut_reclamation = 'Traitée',
                        retour_traitement = ?,
                        date_traitement = NOW()
                        WHERE id_reclamation = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$commentaire, $reclamation_id]);

                // CORRECTION: Vérifier si la mise à jour a réussi
                if ($result) {
                    $_SESSION['success_message'] = "La réclamation a été traitée avec succès.";
                    error_log("Mise à jour réussie - Lignes affectées: " . $stmt->rowCount());
                  //  enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'reclamations_etudiants', 'Traitement réclamation', 1);
                } else {
                    $_SESSION['error_message'] = "Aucune réclamation n'a été mise à jour. Vérifiez l'ID.";
                    error_log("Aucune ligne affectée lors de la mise à jour");
                  //  enregistrer_piste_audit($pdo, $_SESSION['user_id'], 'reclamations_etudiants', 'Traitement réclamation', 0);
                }

            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Une erreur est survenue lors de la mise à jour du statut.";
                error_log("Erreur lors du changement de statut : " . $e->getMessage());
            }
            break;

        case 'supprimer':
            try {
                $reclamation_id = $_POST['reclamation_id'];

                $sql = "DELETE FROM reclamations WHERE id_reclamation = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$reclamation_id]);

                if ($result) {
                    $_SESSION['success_message'] = "La réclamation a été supprimée avec succès.";
                } else {
                    $_SESSION['error_message'] = "Aucune réclamation n'a été supprimée.";
                }
            } catch (PDOException $e) {
                $_SESSION['error_message'] = "Une erreur est survenue lors de la suppression.";
                error_log("Erreur lors de la suppression : " . $e->getMessage());
            }
            break;
    }

    // CORRECTION: Redirection absolue pour éviter les problèmes
    header('Location: ../../index_personnel_administratif.php?page=reclamations_etudiants');
    exit();
}

// Traitement des requêtes GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_details':
            try {
                $id = $_GET['id'];
                
                if (!$id) {
                    throw new Exception('ID de réclamation manquant');
                }

                $sql = "SELECT r.*, e.nom_etd, e.prenom_etd, e.num_carte_etd, n.lib_niv_etd,
                        DATE_FORMAT(r.date_reclamation, '%d/%m/%Y') as date_reclamation
                        FROM reclamations r
                        JOIN etudiants e ON e.num_etd = r.num_etd
                        JOIN niveau_etude n ON n.id_niv_etd = e.id_niv_etd
                        WHERE r.id_reclamation = ?";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $details = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$details) {
                    throw new Exception('Réclamation non trouvée');
                }

                header('Content-Type: application/json');
                echo json_encode($details);
                exit(); // AJOUT: important pour éviter la suite du script
            } catch (Exception $e) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode(['error' => $e->getMessage()]);
                exit(); // AJOUT: important pour éviter la suite du script
            }
            break;
    }
}

// Récupération des données pour l'affichage
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$stats = getStatistics($pdo);
$reclamations = getReclamations($pdo, $search, $date_filter, $status_filter);
?>