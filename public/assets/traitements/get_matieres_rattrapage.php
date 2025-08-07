<?php
// Désactiver l'affichage des erreurs pour éviter le HTML
error_reporting(0);
ini_set('display_errors', 0);

// Démarrer la session
session_start();

// Log pour debug
error_log("=== AJAX get_matieres_rattrapage.php démarré ===");

try {
    // Inclure les fichiers nécessaires avec des chemins absolus
    $config_path = __DIR__ . '/../../../config/config.php';
    error_log("Chemin config: " . $config_path);
    if (!file_exists($config_path)) {
        throw new Exception("Fichier config.php introuvable: " . $config_path);
    }
    require_once $config_path;
    error_log("Config chargée");

    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        error_log("Utilisateur non connecté - user_id manquant");
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Non autorisé - user_id manquant']);
        exit;
    }

    // Récupérer l'ID du personnel administratif si pas déjà défini
    if (!isset($_SESSION['id_personnel_adm'])) {
        $stmt = $pdo->prepare("SELECT id_personnel_adm FROM personnel_administratif WHERE email_personnel_adm = (
            SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?
        )");
        $stmt->execute([$_SESSION['user_id']]);
        $personnel_id = $stmt->fetchColumn();

        if (!$personnel_id) {
            error_log("Personnel administratif non trouvé pour user_id: " . $_SESSION['user_id']);
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Non autorisé - personnel non trouvé']);
            exit;
        }

        $_SESSION['id_personnel_adm'] = $personnel_id;
        error_log("ID personnel administratif défini: " . $personnel_id);
    }

    // Vérifier que c'est une requête AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        error_log("Requête non AJAX");
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Requête invalide']);
        exit;
    }

    // Récupérer les paramètres
    $annee_id = $_GET['annee_id'] ?? '';
    $etudiants = json_decode($_GET['etudiants'] ?? '[]', true);

    error_log("Paramètres reçus - annee_id: $annee_id, etudiants: " . print_r($etudiants, true));

    // Validation des paramètres
    if (empty($annee_id)) {
        error_log("Année académique manquante");
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Année académique requise']);
        exit;
    }

    // Récupérer toutes les UE de l'année académique (sans filtrer par niveau d'étudiant)
    $sql = "SELECT DISTINCT 
                ue.id_ue,
                ue.credit_ue,
                ue.lib_ue,
                ue.prix_matiere_cheval_ue,
                ne.lib_niv_etd,
                ne.id_niv_etd
            FROM ue
            JOIN niveau_etude ne ON ue.id_niv_etd = ne.id_niv_etd
            WHERE ue.id_annee_academique = ?
            ORDER BY ne.lib_niv_etd, ue.lib_ue";
    
    error_log("SQL UE: " . $sql);
    error_log("Params UE: " . $annee_id);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$annee_id]);
    $ues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("UE trouvées: " . print_r($ues, true));

    if (empty($ues)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Aucune UE trouvée pour cette année académique']);
        exit;
    }

    // Récupérer les ECUE pour chaque UE
    $matieres_organisees = [];
    foreach ($ues as $ue) {
        // Récupérer les ECUE de cette UE
        $sql_ecue = "SELECT 
                        ec.id_ecue,
                        ec.lib_ecue,
                        ec.credit_ecue,
                        ec.prix_matiere_cheval_ecue as prix_matiere_cheval
                    FROM ecue ec
                    WHERE ec.id_ue = ?
                    ORDER BY ec.lib_ecue";
        
        $stmt_ecue = $pdo->prepare($sql_ecue);
        $stmt_ecue->execute([$ue['id_ue']]);
        $ecues = $stmt_ecue->fetchAll(PDO::FETCH_ASSOC);
        
        // Log pour déboguer les crédits et prix
        error_log("ECUE pour UE " . $ue['id_ue'] . " (" . $ue['lib_ue'] . "): " . print_r($ecues, true));
        error_log("Prix UE " . $ue['id_ue'] . ": " . ($ue['prix_matiere_cheval_ue'] ?? 'Non défini'));
        
        // Si pas d'ECUE, créer une entrée pour l'UE elle-même
        if (empty($ecues)) {
            $matieres_organisees[] = [
                'id_ue' => $ue['id_ue'],
                'lib_ue' => $ue['lib_ue'],
                'niveau' => $ue['lib_niv_etd'],
                'ecues' => [
                    [
                        'id_ecue' => $ue['id_ue'], // Utiliser l'ID de l'UE comme ECUE
                        'lib_ecue' => $ue['lib_ue'],
                        'credit_ecue' => $ue['credit_ue'] ?? 0, // Utiliser le crédit de l'UE
                        'prix_matiere_cheval' => $ue['prix_matiere_cheval_ue'] ?? 25000.00
                    ]
                ]
            ];
        } else {
            // Ajouter l'UE avec ses ECUE
            $matieres_organisees[] = [
                'id_ue' => $ue['id_ue'],
                'lib_ue' => $ue['lib_ue'],
                'niveau' => $ue['lib_niv_etd'],
                'ecues' => $ecues
            ];
        }
    }

    // Retourner le résultat
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'matieres' => $matieres_organisees,
        'total_ues' => count($ues),
        'annee_id' => $annee_id
    ]);
    error_log("JSON envoyé");
    
} catch (Exception $e) {
    error_log("Erreur AJAX get_matieres_rattrapage: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
} catch (Error $e) {
    error_log("Erreur fatale AJAX get_matieres_rattrapage: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur fatale: ' . $e->getMessage()]);
}
?> 