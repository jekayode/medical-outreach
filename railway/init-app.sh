#!/bin/bash
# Make sure this file has executable permissions, run `chmod +x railway/init-app.sh`
# Exit the script if any command fails
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> init-app: $(pwd)"
echo "==> $(php -v | head -n1)"

php artisan migrate --force --no-interaction
php artisan optimize:clear --no-interaction
php artisan config:cache --no-interaction
php artisan event:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

echo "==> init-app: done"
