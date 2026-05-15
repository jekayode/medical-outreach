#!/bin/bash
# Make sure this file has executable permissions, run `chmod +x railway/run-worker.sh`
# Use the database queue (matches .env.example). Override Railway's redis default if present.
set -euo pipefail
cd "$(dirname "$0")/.."
php artisan queue:work database --sleep=3 --tries=3 --no-interaction
