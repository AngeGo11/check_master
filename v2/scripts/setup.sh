#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

if ! command -v docker >/dev/null 2>&1; then
  echo "Installez puis ouvrez Docker Desktop avant de relancer ce script." >&2
  exit 1
fi
docker info >/dev/null
docker compose version >/dev/null

docker run --rm --user "$(id -u):$(id -g)" -v "$PWD:/app" -w /app php:8.4-cli-alpine php scripts/init-env.php
docker compose config --quiet
docker compose build app

docker compose run --rm --no-deps app composer install --no-interaction --prefer-dist
docker compose run --rm --no-deps node sh -c 'if [ -f package-lock.json ]; then npm ci; else npm install; fi'
docker compose run --rm --no-deps node npm run build

docker compose up -d --wait db
docker compose run --rm app php artisan migrate --force
docker compose up -d app web

docker compose exec -T app php scripts/wait-http.php
echo "Application prête. Port par défaut : http://localhost:8085"
echo "Créer votre compte : docker compose exec app php artisan checkmaster:create-user"
echo "Vérifier le socle : bash scripts/check.sh"
