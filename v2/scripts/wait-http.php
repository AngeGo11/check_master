<?php

declare(strict_types=1);

$context = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
for ($attempt = 0; $attempt < 30; $attempt++) {
    $body = @file_get_contents('http://web/login', false, $context);
    $status = $http_response_header[0] ?? '';
    if (str_contains($status, '200') && is_string($body) && str_contains($body, 'data-page=')) {
        fwrite(STDOUT, "Nginx, PHP, la session et la page de connexion répondent.\n");
        exit(0);
    }
    usleep(1_000_000);
}
fwrite(STDERR, "La page de connexion ne répond pas correctement. Consulter docker compose logs app web.\n");
exit(1);
