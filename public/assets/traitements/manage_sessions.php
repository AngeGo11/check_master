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
            listSessions();
            break;
        case 'terminate':
            terminateSession();
            break;
        case 'terminate_all':
            terminateAllSessions();
            break;
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    error_log('Erreur gestion sessions: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function listSessions() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    
    // Créer la table sessions si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(50) NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        device_info VARCHAR(255),
        location VARCHAR(255),
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        INDEX idx_user (user_id, user_type),
        INDEX idx_session (session_id)
    )";
    $pdo->exec($sql);
    
    // Récupérer toutes les sessions actives de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT * FROM user_sessions 
        WHERE user_id = ? AND user_type = ? AND is_active = TRUE 
        ORDER BY last_activity DESC
    ");
    $stmt->execute([$userId, $userType]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajouter des informations supplémentaires
    foreach ($sessions as &$session) {
        $session['is_current'] = ($session['session_id'] === session_id());
        $session['device_type'] = getDeviceType($session['user_agent']);
        $session['location_info'] = getLocationInfo($session['ip_address']);
    }
    
    echo json_encode([
        'success' => true,
        'sessions' => $sessions
    ]);
}

function terminateSession() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    $sessionId = $_POST['session_id'] ?? '';
    
    if (empty($sessionId)) {
        throw new Exception('ID de session requis');
    }
    
    // Vérifier que la session appartient à l'utilisateur
    $stmt = $pdo->prepare("
        SELECT id FROM user_sessions 
        WHERE user_id = ? AND user_type = ? AND session_id = ? AND is_active = TRUE
    ");
    $stmt->execute([$userId, $userType, $sessionId]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Session non trouvée ou non autorisée');
    }
    
    // Terminer la session
    $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = FALSE WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Session terminée avec succès'
    ]);
}

function terminateAllSessions() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    $currentSessionId = session_id();
    
    // Terminer toutes les sessions sauf la session actuelle
    $stmt = $pdo->prepare("
        UPDATE user_sessions 
        SET is_active = FALSE 
        WHERE user_id = ? AND user_type = ? AND session_id != ?
    ");
    $stmt->execute([$userId, $userType, $currentSessionId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Toutes les autres sessions ont été terminées'
    ]);
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

// Fonction pour enregistrer une nouvelle session
function recordSession($userId, $userType, $sessionId, $ipAddress, $userAgent) {
    global $pdo;
    
    // Créer la table si elle n'existe pas
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type VARCHAR(50) NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        device_info VARCHAR(255),
        location VARCHAR(255),
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        INDEX idx_user (user_id, user_type),
        INDEX idx_session (session_id)
    )";
    $pdo->exec($sql);
    
    // Enregistrer la nouvelle session
    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, user_type, session_id, ip_address, user_agent, device_info) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId, 
        $userType, 
        $sessionId, 
        $ipAddress, 
        $userAgent,
        getDeviceType($userAgent)
    ]);
}

// Fonction pour mettre à jour l'activité de la session
function updateSessionActivity($sessionId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE user_sessions 
        SET last_activity = CURRENT_TIMESTAMP 
        WHERE session_id = ? AND is_active = TRUE
    ");
    $stmt->execute([$sessionId]);
}
?> 