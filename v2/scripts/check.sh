#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

docker compose config --quiet
docker compose exec -T app composer validate --strict
docker compose exec -T app php artisan test
docker compose exec -T app php scripts/check-audit-query.php
docker compose exec -T app php scripts/check-progression-query.php
docker compose exec -T app php scripts/wait-http.php
