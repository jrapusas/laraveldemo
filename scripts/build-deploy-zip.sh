#!/usr/bin/env bash
# Build a production zip for FTP / cPanel upload (includes vendor + Vite build).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

STAMP="$(date +%Y%m%d-%H%M)"
OUT_DIR="${ROOT}/storage/app/deploy"
ZIP_NAME="uc-service-desk-${STAMP}.zip"
STAGE="$(mktemp -d)"
APP_DIR="${STAGE}/uc-service-desk"

echo "==> Composer (no dev)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Front-end build"
if [[ -f package-lock.json ]]; then
  npm ci
else
  npm install
fi
npm run build

echo "==> Stage files"
mkdir -p "$APP_DIR"
rsync -a \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude 'bootstrap/cache/*.php' \
  --exclude '.git' \
  --exclude '.github' \
  --exclude 'node_modules' \
  --exclude 'tests' \
  --exclude 'storage/app/deploy' \
  --exclude 'storage/logs/*.log' \
  --exclude 'storage/framework/cache/data/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*.php' \
  --exclude '.phpunit.result.cache' \
  --exclude 'compose.yaml' \
  --exclude '.DS_Store' \
  --exclude '_notes' \
  ./ "$APP_DIR/"

mkdir -p "$OUT_DIR"
(
  cd "$STAGE"
  zip -rq "${OUT_DIR}/${ZIP_NAME}" uc-service-desk
)

rm -rf "$STAGE"

echo ""
echo "Done: ${OUT_DIR}/${ZIP_NAME}"
echo "Upload with FTP, extract, point docroot to uc-service-desk/public — see docs/DEPLOY.md"
