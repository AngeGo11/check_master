#!/bin/sh
set -eu
mkdir -p storage/app/private storage/app/public \
  storage/framework/cache/data storage/framework/cache/locks \
  storage/framework/sessions storage/framework/views storage/framework/testing \
  storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
if [ -f .env ]; then
  chgrp www-data .env
  chmod 640 .env
fi
exec "$@"
