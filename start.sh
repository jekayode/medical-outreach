#!/bin/bash
# Container start (Railway). Migrations run once in railway/init-app.sh (pre-deploy)—not here.
# Make sure this file has executable permissions: chmod +x start.sh
set -euo pipefail

node /assets/scripts/prestart.mjs /assets/nginx.template.conf /nginx.conf
php-fpm -y /assets/php-fpm.conf &

php artisan migrate --force --no-interaction
php artisan optimize:clear --no-interaction
php artisan config:cache --no-interaction
php artisan event:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

exec nginx -c /nginx.conf
