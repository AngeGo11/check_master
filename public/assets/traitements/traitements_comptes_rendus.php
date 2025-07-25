<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/includes/audit_utils.php';

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

// Fonction pour récupérer les comptes rendus
function getComptesRendus($search = '', $date_filter = '')
{
    global $pdo;

    $query = "SELECT cr.*, 
                     r.nom_rapport,
                     et.nom_etd, et.prenom_etd,
                     dm.nom_ens as nom_directeur, dm.prenoms_ens as prenom_directeur,
                     enc.nom_ens as nom_encadreur, enc.prenoms_ens as prenom_encadreur,
                     e.nom_ens, e.prenoms_ens, e.email_ens
              FROM compte_rendu cr
              JOIN rapport_etudiant r ON cr.id_rapport_etd = r.id_rapport_etd
              JOIN etudiants et ON r.num_etd = et.num_etd
              LEFT JOIN enseignants dm ON r.id_directeur = dm.id_ens
              LEFT JOIN enseignants enc ON r.id_encadreur = enc.id_ens
              LEFT JOIN enseignants e ON r.id_ens = e.id_ens
              WHERE 1=1";
    
    $params = [];

    if (!empty($search)) {
        $query .= " AND (e.nom_ens LIKE ? OR e.prenoms_ens LIKE ? 
                          OR et.nom_etd LIKE ? OR et.prenom_etd LIKE ?
                          OR r.nom_rapport LIKE ? OR cr.nom_cr LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
    }

    if (!empty($date_filter)) {
        switch ($date_filter) {
            case 'today':
                $query .= " AND DATE(cr.date_cr) = CURDATE()";
                break;
            case 'week':
                $query .= " AND YEARWEEK(cr.date_cr) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $query .= " AND MONTH(cr.date_cr) = MONTH(CURDATE()) AND YEAR(cr.date_cr) = YEAR(CURDATE())";
                break;
        }
    }

    $query .= " ORDER BY cr.date_cr DESC";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Afficher la requête et les résultats
        error_log("Requête SQL: " . $query);
        error_log("Paramètres: " . print_r($params, true));
        error_log("Nombre de résultats: " . count($results));
        
        return $results;
    } catch (PDOException $e) {
        error_log("Erreur dans getComptesRendus: " . $e->getMessage());
        return [];
    }
}

// Fonction pour récupérer les détails d'un compte rendu
function getCompteRenduDetails($id)
{
    global $pdo;

    $query = "SELECT cr.*, 
                     r.nom_rapport, r.resume_rapport,
                     et.nom_etd, et.prenom_etd, et.email_etd,
                     dm.nom_ens as nom_directeur, dm.prenoms_ens as prenom_directeur,
                     enc.nom_ens as nom_encadreur, enc.prenoms_ens as prenom_encadreur,
                     e.nom_ens, e.prenoms_ens, e.email_ens
              FROM compte_rendu cr
              JOIN rapport_etudiant r ON cr.id_rapport_etd = r.id_rapport_etd
              JOIN etudiants et ON r.num_etd = et.num_etd
              LEFT JOIN enseignants dm ON r.id_directeur = dm.id_ens
              LEFT JOIN enseignants enc ON r.id_encadreur = enc.id_ens
              LEFT JOIN enseignants e ON r.id_ens = e.id_ens
              WHERE cr.id_cr = ?";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Afficher la requête et les résultats
        error_log("Requête SQL: " . $query);
        error_log("ID: " . $id);
        error_log("Résultat: " . print_r($result, true));
        
        return $result;
    } catch (PDOException $e) {
        error_log("Erreur dans getCompteRenduDetails: " . $e->getMessage());
        return null;
    }
} 