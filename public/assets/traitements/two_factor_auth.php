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
        case 'enable':
            enable2FA();
            break;
        case 'disable':
            disable2FA();
            break;
        case 'verify':
            verify2FA();
            break;
        case 'generate':
            generate2FASecret();
            break;
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    error_log('Erreur 2FA: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function enable2FA() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    $secret = $_POST['secret'] ?? '';
    $code = $_POST['code'] ?? '';
    
    if (empty($secret) || empty($code)) {
        throw new Exception('Code secret et code de vérification requis');
    }
    
    // Vérifier le code
    if (!verifyTOTP($secret, $code)) {
        throw new Exception('Code de vérification incorrect');
    }
    
    
    
    // Générer des codes de sauvegarde
    $backupCodes = generateBackupCodes();
    
    // Enregistrer ou mettre à jour
    $stmt = $pdo->prepare("
        INSERT INTO two_factor_auth (user_id, user_type, secret_key, backup_codes) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        secret_key = VALUES(secret_key), 
        backup_codes = VALUES(backup_codes), 
        enabled = TRUE
    ");
    $stmt->execute([$userId, $userType, $secret, json_encode($backupCodes)]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Authentification à deux facteurs activée',
        'backup_codes' => $backupCodes
    ]);
}

function disable2FA() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    $code = $_POST['code'] ?? '';
    
    // Vérifier le code avant de désactiver
    $stmt = $pdo->prepare("SELECT secret_key FROM two_factor_auth WHERE user_id = ? AND user_type = ? AND enabled = TRUE");
    $stmt->execute([$userId, $userType]);
    $secret = $stmt->fetchColumn();
    
    if ($secret && !verifyTOTP($secret, $code)) {
        throw new Exception('Code de vérification incorrect');
    }
    
    // Désactiver 2FA
    $stmt = $pdo->prepare("UPDATE two_factor_auth SET enabled = FALSE WHERE user_id = ? AND user_type = ?");
    $stmt->execute([$userId, $userType]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Authentification à deux facteurs désactivée'
    ]);
}

function verify2FA() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    $code = $_POST['code'] ?? '';
    
    if (empty($code)) {
        throw new Exception('Code requis');
    }
    
    // Récupérer la clé secrète
    $stmt = $pdo->prepare("SELECT secret_key, backup_codes FROM two_factor_auth WHERE user_id = ? AND user_type = ? AND enabled = TRUE");
    $stmt->execute([$userId, $userType]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        throw new Exception('Authentification à deux facteurs non configurée');
    }
    
    // Vérifier le code TOTP
    if (verifyTOTP($result['secret_key'], $code)) {
        echo json_encode(['success' => true, 'message' => 'Code vérifié']);
        return;
    }
    
    // Vérifier les codes de sauvegarde
    $backupCodes = json_decode($result['backup_codes'], true);
    if (in_array($code, $backupCodes)) {
        // Supprimer le code de sauvegarde utilisé
        $backupCodes = array_diff($backupCodes, [$code]);
        $stmt = $pdo->prepare("UPDATE two_factor_auth SET backup_codes = ? WHERE user_id = ? AND user_type = ?");
        $stmt->execute([json_encode(array_values($backupCodes)), $userId, $userType]);
        
        echo json_encode(['success' => true, 'message' => 'Code de sauvegarde vérifié']);
        return;
    }
    
    throw new Exception('Code incorrect');
}

function generate2FASecret() {
    global $pdo;
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'] ?? 'utilisateur';
    
    // Récupérer les informations utilisateur
    $userInfo = getUserInfo($userId, $userType);
    
    // Générer une nouvelle clé secrète
    $secret = generateSecretKey();
    
    // Créer l'URL pour le QR code
    $issuer = 'GSCV+';
    $account = $userInfo['email'] ?? $userInfo['login'] ?? 'user';
    $qrUrl = "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}";
    
    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'qr_url' => $qrUrl,
        'account' => $account,
        'issuer' => $issuer
    ]);
}

function getUserInfo($userId, $userType) {
    global $pdo;
    
    switch ($userType) {
        case 'enseignant':
            $stmt = $pdo->prepare("SELECT nom_ens, prenoms_ens, email_ens FROM enseignants WHERE id_ens = ?");
            break;
        case 'etudiant':
            $stmt = $pdo->prepare("SELECT nom_etd, prenoms_etd, email_etd FROM etudiants WHERE num_etd = ?");
            break;
        case 'personnel_adm':
            $stmt = $pdo->prepare("SELECT nom_personnel_adm, prenoms_personnel_adm, email_personnel_adm FROM personnel_administratif WHERE id_personnel_adm = ?");
            break;
        default:
            $stmt = $pdo->prepare("SELECT login_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
    }
    
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function generateSecretKey($length = 32) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

function generateBackupCodes($count = 8) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $codes[] = sprintf('%08d', random_int(0, 99999999));
    }
    return $codes;
}

function verifyTOTP($secret, $code, $window = 1) {
    $timeSlice = floor(time() / 30);
    
    for ($i = -$window; $i <= $window; $i++) {
        $calculatedCode = generateTOTP($secret, $timeSlice + $i);
        if ($calculatedCode === $code) {
            return true;
        }
    }
    
    return false;
}

function generateTOTP($secret, $timeSlice) {
    $secretKey = base32_decode($secret);
    $time = pack('N*', 0) . pack('N*', $timeSlice);
    $hash = hash_hmac('SHA1', $time, $secretKey, true);
    $offset = ord($hash[19]) & 0xf;
    $code = (
        ((ord($hash[$offset]) & 0x7f) << 24) |
        ((ord($hash[$offset + 1]) & 0xff) << 16) |
        ((ord($hash[$offset + 2]) & 0xff) << 8) |
        (ord($hash[$offset + 3]) & 0xff)
    ) % 1000000;
    
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

function base32_decode($secret) {
    $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32charsFlipped = array_flip(str_split($base32chars));
    
    $paddingCharCount = substr_count($secret, $base32chars[32]);
    $allowedValues = array(6, 4, 3, 1, 0);
    if (!in_array($paddingCharCount, $allowedValues)) {
        return false;
    }
    for ($i = 0; $i < 4; ++$i) {
        if ($paddingCharCount == $allowedValues[$i] &&
            substr($secret, -($allowedValues[$i])) != str_repeat($base32chars[32], $allowedValues[$i])) {
            return false;
        }
    }
    $secret = str_replace('=', '', $secret);
    $secret = str_split($secret);
    $binaryString = "";
    for ($i = 0; $i < count($secret); $i = $i + 8) {
        $x = "";
        if (!in_array($secret[$i], $base32charsFlipped)) {
            return false;
        }
        for ($j = 0; $j < 8; ++$j) {
            $x .= str_pad(base_convert(@$base32charsFlipped[@$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
        }
        $eightBits = str_split($x, 8);
        for ($z = 0; $z < count($eightBits); ++$z) {
            $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : "";
        }
    }
    return $binaryString;
}
?> 