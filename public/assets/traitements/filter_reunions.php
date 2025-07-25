<?php
// Activation du logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/../../../logs/php_errors.log');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Définir le header JSON
header('Content-Type: application/json');

try {
    // Initialisation session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Vérification méthode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }

    // Vérification utilisateur
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentification requise']);
        exit;
    }

    // Chargement config DB
    require_once $_SERVER['DOCUMENT_ROOT'] . '/GSCV/config/db_connect.php';

    // Récupération des paramètres
    $search = trim($_POST['search'] ?? '');
    $statut = trim($_POST['statut'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $date = trim($_POST['date'] ?? '');

    // Construction de la requête SQL
    $sql = "SELECT * FROM reunions WHERE 1=1";
    $params = [];

    // Filtre de recherche
    if (!empty($search)) {
        $sql .= " AND (titre LIKE ? OR lieu LIKE ? OR description LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    // Filtre par statut
    if (!empty($statut)) {
        $sql .= " AND status = ?";
        $params[] = $statut;
    }

    // Filtre par type
    if (!empty($type)) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }

    // Filtre par date
    if (!empty($date)) {
        switch ($date) {
            case 'today':
                $sql .= " AND date_reunion = CURDATE()";
                break;
            case 'week':
                $sql .= " AND date_reunion BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $sql .= " AND date_reunion BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 MONTH)";
                break;
            case 'past':
                $sql .= " AND date_reunion < CURDATE()";
                break;
            case 'future':
                $sql .= " AND date_reunion >= CURDATE()";
                break;
        }
    }

    // Tri par date et heure
    $sql .= " ORDER BY date_reunion DESC, heure_debut DESC";

    // Exécution de la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reunions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Réponse JSON
    echo json_encode([
        'success' => true,
        'reunions' => $reunions,
        'count' => count($reunions)
    ]);

} catch (Exception $e) {
    error_log("Erreur dans filter_reunions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors du filtrage des réunions',
        'error' => $e->getMessage()
    ]);
}
?> 