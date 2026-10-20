<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$path = $root.'/.env';
$content = file_exists($path) ? file_get_contents($path) : file_get_contents($root.'/.env.example');
if ($content === false) {
    throw new RuntimeException('Lecture de la configuration impossible.');
}

$defaults = [
    'APP_KEY' => fn () => 'base64:'.base64_encode(random_bytes(32)),
    'DB_PASSWORD' => fn () => bin2hex(random_bytes(24)),
    'DB_ROOT_PASSWORD' => fn () => bin2hex(random_bytes(24)),
];
foreach ($defaults as $key => $generate) {
    $pattern = '/^'.preg_quote($key, '/').'=(.*)$/m';
    if (preg_match($pattern, $content, $matches)) {
        if (in_array(trim($matches[1]), ['', '""', "''"], true)) {
            $content = preg_replace($pattern, $key.'='.$generate(), $content, 1);
        }
    } else {
        $content = rtrim($content).PHP_EOL.$key.'='.$generate().PHP_EOL;
    }
}
if (file_put_contents($path, $content, LOCK_EX) === false) {
    throw new RuntimeException('Écriture de .env impossible.');
}
chmod($path, 0640);
fwrite(STDOUT, "Configuration locale prête ; les secrets restent dans .env.\n");
