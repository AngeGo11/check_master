<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

// Fonction pour récupérer les statistiques
function getStatistics()
{
    global $pdo;

    $stats = [
        'rapports_valides' => 0,
        'rapports_en_attente' => 0,
        'rapports_rejetes' => 0,
        'comptes_rendus' => 0
    ];

    // Rapports validés
    $query = "SELECT COUNT(*) as count FROM rapport_etudiant WHERE statut_rapport = 'Validé'";
    $stmt = $pdo->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['rapports_valides'] = $row['count'];

    // Rapports en attente
    $query = "SELECT COUNT(*) as count FROM rapport_etudiant WHERE statut_rapport = 'En attente de validation'";
    $stmt = $pdo->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['rapports_en_attente'] = $row['count'];

    // Rapports rejetés
    $query = "SELECT COUNT(*) as count FROM rapport_etudiant WHERE statut_rapport = 'Rejeté'";
    $stmt = $pdo->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['rapports_rejetes'] = $row['count'];

    // Comptes rendus disponibles
    $query = "SELECT COUNT(*) as count FROM compte_rendu";
    $stmt = $pdo->query($query);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $stats['comptes_rendus'] = $row['count'];

    return $stats;
}

// Fonction pour récupérer les rapports en attente
function getPendingReports($search = '', $date_filter = '')
{
    global $pdo;

    $query = "SELECT r.*, e.nom_etd, e.prenom_etd, e.email_etd 
              FROM rapport_etudiant r 
              JOIN etudiants e ON r.num_etd = e.num_etd 
              WHERE r.statut_rapport = 'En attente de validation'";
    $params = [];

    if (!empty($search)) {
        $query .= " AND (e.nom_etd LIKE ? OR e.prenom_etd LIKE ? OR r.nom_rapport LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }

    if (!empty($date_filter)) {
        switch ($date_filter) {
            case 'today':
                $query .= " AND DATE(r.date_rapport) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(r.date_rapport) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $query .= " AND MONTH(r.date_rapport) = MONTH(CURDATE()) AND YEAR(r.date_rapport) = YEAR(CURDATE())";
                break;
        }
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour récupérer les décisions finales
function getFinalDecisions($search = '', $date_submission = '', $date_decision = '', $decision = '')
{
    global $pdo;

    $query = "WITH LastDecisions AS (
        SELECT re.id_rapport_etd, re.num_etd, re.nom_rapport, re.date_rapport,
               e.nom_etd, e.prenom_etd, e.email_etd,
               v.date_validation, v.decision, v.com_validation,
               ROW_NUMBER() OVER (PARTITION BY re.num_etd ORDER BY v.date_validation DESC) as rn
        FROM rapport_etudiant re
        LEFT JOIN etudiants e ON re.num_etd = e.num_etd
        LEFT JOIN valider v ON v.id_rapport_etd = re.id_rapport_etd
        WHERE re.statut_rapport IN ('Validé', 'Rejeté')
    )
    SELECT * FROM LastDecisions WHERE rn = 1";
    
    $params = [];

    if (!empty($search)) {
        $query .= " AND (nom_etd LIKE ? OR prenom_etd LIKE ? OR nom_rapport LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    }

    if (!empty($date_submission)) {
        switch ($date_submission) {
            case 'today':
                $query .= " AND DATE(date_rapport) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(date_rapport) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $query .= " AND MONTH(date_rapport) = MONTH(CURDATE()) AND YEAR(date_rapport) = YEAR(CURDATE())";
                break;
        }
    }

    if (!empty($date_decision)) {
        switch ($date_decision) {
            case 'today':
                $query .= " AND DATE(date_validation) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(date_validation) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $query .= " AND MONTH(date_validation) = MONTH(CURDATE()) AND YEAR(date_validation) = YEAR(CURDATE())";
                break;
        }
    }

    if (!empty($decision)) {
        $query .= " AND decision = ?";
        $params[] = $decision;
    }

    $query .= " ORDER BY date_rapport DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour récupérer les détails d'une décision


// Fonction pour récupérer les détails d'un rapport
function getReportDetails($reportId) {
    global $pdo;
    
    $query = "SELECT r.*, 
                     e.nom_etd, e.prenom_etd, e.num_etd,
                     en.nom_ens as nom_encadrant, en.prenoms_ens as prenom_encadrant,
                     r.statut_rapport
              FROM rapport_etudiant r
              JOIN etudiants e ON r.num_etd = e.num_etd
              JOIN valider v ON v.id_rapport_etd = r.id_rapport_etd
              LEFT JOIN enseignants en ON en.id_ens = v.id_ens
              WHERE r.id_rapport_etd = ?";
              
    $stmt = $pdo->prepare($query);
    $stmt->execute([$reportId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fonction pour récupérer les membres de la commission
function getCommissionMembers()
{
    global $pdo;

    $query = "SELECT e.*, 
                     (SELECT COUNT(*) FROM rapport_etudiant r 
                      WHERE r.statut_rapport = 'En attente de validation') as pending_reports
              FROM enseignants e
              JOIN posseder p ON e.id_ens = p.id_util
              JOIN groupe_utilisateur g ON p.id_gu = g.id_gu
              WHERE g.lib_gu = 'Commission de validation'";

    $stmt = $pdo->query($query);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




// Fonction pour envoyer des rappels aux membres de la commission
function sendReminders($expediteur_id, $destinataire_id, $objet, $contenu, $type_message = 'rappel', $categorie = 'evaluation', $priorite = 'urgente') {
    global $pdo;
    
    try {
        // Debug : Log des paramètres
        error_log("Fonction sendReminders appelée avec:");
        error_log("Expediteur: $expediteur_id, Destinataire: $destinataire_id");
        error_log("Objet: $objet");
        error_log("Type: $type_message, Catégorie: $categorie, Priorité: $priorite");

        // Vérifier si l'expéditeur et le destinataire existent
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE id_utilisateur = ?");
        $stmt->execute([$expediteur_id]);
        $expediteur_exists = $stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE id_utilisateur = ?");
        $stmt->execute([$destinataire_id]);
        $destinataire_exists = $stmt->fetchColumn();
        
        error_log("Expéditeur existe: $expediteur_exists, Destinataire existe: $destinataire_exists");

        if (!$expediteur_exists || !$destinataire_exists) {
            return [
                'success' => false,
                'message' => "L'expéditeur (ID: $expediteur_id) ou le destinataire (ID: $destinataire_id) n'existe pas."
            ];
        }

        // Requête d'insertion simplifiée
        $sql = "INSERT INTO messages (
            expediteur_id, 
            destinataire_id, 
            destinataire_type,
            objet, 
            contenu, 
            type_message,
            categorie,
            priorite,
            statut,
            date_envoi,
            date_creation
        ) VALUES (?, ?, 'individuel', ?, ?, ?, ?, ?, 'envoyé', NOW(), NOW())";

        error_log("Requête SQL: $sql");
        
        // Préparer et exécuter la requête
        $stmt = $pdo->prepare($sql);
        
        // Exécuter avec les paramètres
        $result = $stmt->execute([
            $expediteur_id,
            $destinataire_id,
            $objet,
            $contenu,
            $type_message,
            $categorie,
            $priorite
        ]);

        error_log("Résultat de l'exécution: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            $message_id = $pdo->lastInsertId();
            error_log("Message inséré avec l'ID: $message_id");
            
            return [
                'success' => true,
                'message' => 'Le message a été envoyé avec succès.',
                'message_id' => $message_id
            ];
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Erreur PDO: " . print_r($errorInfo, true));
            return [
                'success' => false,
                'message' => "Erreur lors de l'insertion: " . $errorInfo[2]
            ];
        }

    } catch (PDOException $e) {
        error_log("Exception PDO dans sendReminders: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return [
            'success' => false,
            'message' => "Erreur lors de l'envoi du message : " . $e->getMessage()
        ];
    } catch (Exception $e) {
        error_log("Exception générale dans sendReminders: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "Erreur générale : " . $e->getMessage()
        ];
    }
}