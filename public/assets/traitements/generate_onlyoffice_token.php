<?php
/**
 * Générateur de token OnlyOffice
 */

require_once __DIR__ . '/../../../config/config.php';

// Vérifier si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Utilisateur non connecté');
}

// Configuration OnlyOffice
$onlyofficeSecret = 'your-secret-key-here'; // À remplacer par votre clé secrète OnlyOffice
$onlyofficeServer = 'http://localhost:80'; // URL de votre serveur OnlyOffice

/**
 * Générer un token JWT pour OnlyOffice
 */
function generateOnlyOfficeToken($payload) {
    global $onlyofficeSecret;
    
    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];
    
    $payload['iat'] = time();
    $payload['exp'] = time() + 3600; // Expire dans 1 heure
    
    $headerEncoded = base64url_encode(json_encode($header));
    $payloadEncoded = base64url_encode(json_encode($payload));
    
    $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $onlyofficeSecret, true);
    $signatureEncoded = base64url_encode($signature);
    
    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

/**
 * Encoder en base64url
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Générer le payload du token
$payload = [
    'document' => [
        'key' => 'rapport_' . $_SESSION['user_id'] . '_' . time(),
        'title' => 'Rapport de Stage',
        'url' => $onlyofficeServer . '/storage/templates/modele_rapport_de_stage.docx'
    ],
    'documentType' => 'word',
    'editorConfig' => [
        'mode' => 'edit',
        'callbackUrl' => $onlyofficeServer . '/GSCV+/public/assets/traitements/onlyoffice_callback.php',
        'user' => [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['nom'] ?? 'Utilisateur'
        ],
        'customization' => [
            'chat' => false,
            'comments' => true,
            'compactToolbar' => false,
            'feedback' => false,
            'forcesave' => true,
            'submitForm' => false
        ]
    ],
    'permissions' => [
        'edit' => true,
        'download' => true,
        'print' => true,
        'review' => true
    ]
];

// Générer le token
$token = generateOnlyOfficeToken($payload);

// Stocker le token dans la session
$_SESSION['onlyoffice_token'] = $token;

// Retourner le token
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'token' => $token,
    'config' => $payload
]);
?> 