<?php
/**
 * Configuration OnlyOffice pour GSCV+
 */

return [
    // URL du serveur OnlyOffice
    'server_url' => $_ENV['ONLYOFFICE_SERVER_URL'] ?? 'https://onlyoffice.github.io/sdkjs-plugins/example/',
    
    // Configuration de l'éditeur
    'editor_config' => [
        'mode' => 'edit',
        'lang' => 'fr',
        'callback_url' => '/GSCV+/public/assets/traitements/onlyoffice_callback.php',
        'customization' => [
            'chat' => false,
            'comments' => true,
            'compactHeader' => false,
            'feedback' => false,
            'forcesave' => true,
            'submitForm' => false,
            'toolbar' => true,
            'zoom' => 100
        ]
    ],
    
    // Types de documents supportés
    'supported_formats' => [
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'rtf' => 'application/rtf',
        'txt' => 'text/plain'
    ],
    
    // Dossiers de stockage
    'storage' => [
        'templates' => __DIR__ . '/../storage/templates/',
        'uploads' => __DIR__ . '/../storage/uploads/rapports/',
        'temp' => __DIR__ . '/../storage/temp/'
    ],
    
    // Configuration de sécurité
    'security' => [
        'allowed_domains' => ['localhost', '127.0.0.1'],
        'max_file_size' => 50 * 1024 * 1024, // 50MB
        'allowed_extensions' => ['docx', 'doc', 'odt', 'rtf', 'txt']
    ],
    
    // Configuration des logs
    'logging' => [
        'enabled' => true,
        'file' => __DIR__ . '/../storage/logs/onlyoffice.log',
        'level' => 'info'
    ]
]; 