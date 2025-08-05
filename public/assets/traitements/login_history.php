<?php
// Démarrer la session
session_start();

require_once __DIR__ . '/../../../config/config.php';

// Établir la connexion à la base de données
$pdo = DataBase::getConnection();

// Vérification de sécurité
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'list':
            listLoginHistory();
            break;
        case 'clear':
            clearLoginHistory();
            break;
        case 'export':
            exportLoginHistory();
            break;
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    error_log('Erreur historique connexion: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function listLoginHistory() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    $page = intval($_POST['page'] ?? 1);
    $limit = intval($_POST['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    // Créer la table historique si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS login_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(50) NOT NULL,
        session_id VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        device_info VARCHAR(255),
        location VARCHAR(255),
        login_status ENUM('success', 'failed', 'logout') DEFAULT 'success',
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        logout_time TIMESTAMP NULL,
        duration_seconds INT NULL,
        INDEX idx_user (user_id, user_type),
        INDEX idx_login_time (login_time)
    )";
    $pdo->exec($sql);
    
    // Récupérer l'historique avec pagination
    $stmt = $pdo->prepare("
        SELECT * FROM login_history 
        WHERE user_id = ? AND user_type = ? 
        ORDER BY login_time DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$userId, $userType, $limit, $offset]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter le total
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_history 
        WHERE user_id = ? AND user_type = ?
    ");
    $stmt->execute([$userId, $userType]);
    $total = $stmt->fetchColumn();
    
    // Ajouter des informations supplémentaires
    foreach ($history as &$entry) {
        $entry['device_type'] = getDeviceType($entry['user_agent']);
        $entry['location_info'] = getLocationInfo($entry['ip_address']);
        $entry['duration_formatted'] = formatDuration($entry['duration_seconds']);
        $entry['login_time_formatted'] = date('d/m/Y H:i:s', strtotime($entry['login_time']));
        $entry['logout_time_formatted'] = $entry['logout_time'] ? date('d/m/Y H:i:s', strtotime($entry['logout_time'])) : 'En cours';
    }
    
    echo json_encode([
        'success' => true,
        'history' => $history,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_records' => $total,
            'per_page' => $limit
        ]
    ]);
}

function clearLoginHistory() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    $days = intval($_POST['days'] ?? 30);
    
    // Supprimer l'historique plus ancien que X jours
    $stmt = $pdo->prepare("
        DELETE FROM login_history 
        WHERE user_id = ? AND user_type = ? AND login_time < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$userId, $userType, $days]);
    
    $deletedCount = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => "$deletedCount entrées supprimées",
        'deleted_count' => $deletedCount
    ]);
}

function exportLoginHistory() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    $format = $_POST['format'] ?? 'csv';
    
    // Récupérer tout l'historique
    $stmt = $pdo->prepare("
        SELECT * FROM login_history 
        WHERE user_id = ? AND user_type = ? 
        ORDER BY login_time DESC
    ");
    $stmt->execute([$userId, $userType]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($format === 'csv') {
        exportAsCSV($history);
    } else {
        exportAsJSON($history);
    }
}

function exportAsCSV($history) {
    $filename = 'login_history_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes
    fputcsv($output, [
        'Date/Heure de connexion',
        'Date/Heure de déconnexion',
        'Durée',
        'Adresse IP',
        'Pays',
        'Ville',
        'Appareil',
        'Statut'
    ]);
    
    // Données
    foreach ($history as $entry) {
        $locationInfo = getLocationInfo($entry['ip_address']);
        fputcsv($output, [
            date('d/m/Y H:i:s', strtotime($entry['login_time'])),
            $entry['logout_time'] ? date('d/m/Y H:i:s', strtotime($entry['logout_time'])) : 'En cours',
            formatDuration($entry['duration_seconds']),
            $entry['ip_address'],
            $locationInfo['country'],
            $locationInfo['city'],
            getDeviceType($entry['user_agent']),
            $entry['login_status']
        ]);
    }
    
    fclose($output);
    exit;
}

function exportAsJSON($history) {
    $filename = 'login_history_' . date('Y-m-d_H-i-s') . '.json';
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $exportData = [];
    foreach ($history as $entry) {
        $locationInfo = getLocationInfo($entry['ip_address']);
        $exportData[] = [
            'login_time' => date('d/m/Y H:i:s', strtotime($entry['login_time'])),
            'logout_time' => $entry['logout_time'] ? date('d/m/Y H:i:s', strtotime($entry['logout_time'])) : 'En cours',
            'duration' => formatDuration($entry['duration_seconds']),
            'ip_address' => $entry['ip_address'],
            'country' => $locationInfo['country'],
            'city' => $locationInfo['city'],
            'device' => getDeviceType($entry['user_agent']),
            'status' => $entry['login_status']
        ];
    }
    
    echo json_encode($exportData, JSON_PRETTY_PRINT);
    exit;
}

function getDeviceType($userAgent) {
    if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
        return 'Mobile';
    } elseif (preg_match('/Tablet|iPad/', $userAgent)) {
        return 'Tablet';
    } else {
        return 'Desktop';
    }
}

function getLocationInfo($ipAddress) {
    // Service gratuit pour obtenir les informations de localisation
    $url = "http://ip-api.com/json/{$ipAddress}";
    
    try {
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data && $data['status'] === 'success') {
            return [
                'country' => $data['country'] ?? '',
                'region' => $data['regionName'] ?? '',
                'city' => $data['city'] ?? '',
                'isp' => $data['isp'] ?? ''
            ];
        }
    } catch (Exception $e) {
        // En cas d'erreur, retourner des informations de base
    }
    
    return [
        'country' => 'Inconnu',
        'region' => 'Inconnu',
        'city' => 'Inconnu',
        'isp' => 'Inconnu'
    ];
}

function formatDuration($seconds) {
    if (!$seconds) return 'N/A';
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    if ($hours > 0) {
        return sprintf('%dh %dm %ds', $hours, $minutes, $secs);
    } elseif ($minutes > 0) {
        return sprintf('%dm %ds', $minutes, $secs);
    } else {
        return sprintf('%ds', $secs);
    }
}

// Fonction pour enregistrer une connexion
function recordLogin($userId, $userType, $sessionId, $ipAddress, $userAgent, $status = 'success') {
    global $pdo;
    
    // Créer la table si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS login_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(50) NOT NULL,
        session_id VARCHAR(255),
        ip_address VARCHAR(45),
        user_agent TEXT,
        device_info VARCHAR(255),
        location VARCHAR(255),
        login_status ENUM('success', 'failed', 'logout') DEFAULT 'success',
        login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        logout_time TIMESTAMP NULL,
        duration_seconds INT NULL,
        INDEX idx_user (user_id, user_type),
        INDEX idx_login_time (login_time)
    )";
    $pdo->exec($sql);
    
    // Enregistrer la connexion
    $stmt = $pdo->prepare("
        INSERT INTO login_history (user_id, user_type, session_id, ip_address, user_agent, device_info, login_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId, 
        $userType, 
        $sessionId, 
        $ipAddress, 
        $userAgent,
        getDeviceType($userAgent),
        $status
    ]);
    
    return $pdo->lastInsertId();
}

// Fonction pour enregistrer une déconnexion
function recordLogout($sessionId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE login_history 
        SET logout_time = CURRENT_TIMESTAMP,
            duration_seconds = TIMESTAMPDIFF(SECOND, login_time, CURRENT_TIMESTAMP),
            login_status = 'logout'
        WHERE session_id = ? AND logout_time IS NULL
    ");
    $stmt->execute([$sessionId]);
}
?> 