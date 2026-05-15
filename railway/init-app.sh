#!/bin/bash
# Make sure this file has executable permissions, run `chmod +x railway/init-app.sh`
# Exit the script if any command fails
set -euo pipefail

cd "$(dirname "$0")/.."

echo "==> init-app: $(pwd)"
echo "==> $(php -v | head -n1)"

echo "==> init-app: done"
