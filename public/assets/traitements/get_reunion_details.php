<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de réunion invalide']);
    exit;
}

$reunion_id = (int)$_GET['id'];

try {
    // Récupérer les détails de la réunion
    $stmt_reunion = $pdo->prepare("SELECT * FROM reunions WHERE id = ?");
    $stmt_reunion->execute([$reunion_id]);
    $reunion = $stmt_reunion->fetch(PDO::FETCH_ASSOC);

    if (!$reunion) {
        throw new Exception("Réunion introuvable.");
    }

    // Formater la date et l'heure pour les champs de formulaire HTML
    if (isset($reunion['date_reunion'])) {
        $reunion['date_reunion'] = date('Y-m-d', strtotime($reunion['date_reunion']));
    }
    if (isset($reunion['heure_debut'])) {
        $reunion['heure_debut'] = date('H:i', strtotime($reunion['heure_debut']));
    }

    // Récupérer les participants
    $stmt_parts = $pdo->prepare("
        SELECT 
            CASE 
                WHEN e.id_ens IS NOT NULL THEN CONCAT(e.nom_ens, ' ', e.prenoms_ens)
                WHEN pa.id_personnel_adm IS NOT NULL THEN CONCAT(pa.nom_personnel_adm, ' ', pa.prenoms_personnel_adm)
            END as nom,
            CASE 
                WHEN e.id_ens IS NOT NULL THEN e.prenoms_ens
                WHEN pa.id_personnel_adm IS NOT NULL THEN pa.prenoms_personnel_adm
            END as prenom,
            CASE 
                WHEN f.nom_fonction IS NOT NULL THEN f.nom_fonction
                WHEN gu.lib_gu IS NOT NULL THEN gu.lib_gu
            END as fonction
        FROM participants p
        LEFT JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
        LEFT JOIN enseignants e ON u.login_utilisateur = e.email_ens
        LEFT JOIN personnel_administratif pa ON u.login_utilisateur = pa.email_personnel_adm
        LEFT JOIN occuper oc ON e.id_ens = oc.id_ens
        LEFT JOIN fonction f ON oc.id_fonction = f.id_fonction
        LEFT JOIN posseder pos ON u.id_utilisateur = pos.id_util
        LEFT JOIN groupe_utilisateur gu ON pos.id_gu = gu.id_gu
        WHERE p.reunion_id = ?
    ");
    $stmt_parts->execute([$reunion_id]);
    $participants = $stmt_parts->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les documents
    $stmt_docs = $pdo->prepare("SELECT id, nom_fichier as nom, chemin_fichier as url FROM documents WHERE reunion_id = ?");
    $stmt_docs->execute([$reunion_id]);
    $documents = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reunion' => $reunion,
        'participants' => $participants,
        'documents' => $documents
    ]);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des détails de la réunion : " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des détails']);
} 