#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DEPLOY_PATH="${DEPLOY_PATH:-$(cd "$SCRIPT_DIR/.." && pwd)}"
NODE_BIN="${NODE_BIN:-node}"
NPM_BIN="${NPM_BIN:-npm}"

cd "$DEPLOY_PATH"

echo "==> Обновление кода..."
if [ -n "${GITHUB_DEPLOY_TOKEN:-}" ]; then
  git -c "http.extraHeader=Authorization: Bearer ${GITHUB_DEPLOY_TOKEN}" fetch origin main
else
  git fetch origin main
fi
git reset --hard origin/main

# shellcheck source=lib/php-bin.sh
source "$SCRIPT_DIR/lib/php-bin.sh"

if ! PHP_BIN="$(find_php_bin "$DEPLOY_PATH/deploy.env")"; then
  php_bin_hint
  exit 1
fi
export PHP_BIN

echo "==> PHP: $PHP_BIN ($($PHP_BIN -v | head -1))"
echo "==> Node: $($NODE_BIN -v)"

echo "==> Composer (backend)..."
cd backend
bash ../scripts/composer-install.sh

echo "==> Сборка фронтенда..."
cd ../frontend
$NPM_BIN ci
$NPM_BIN run build

echo "==> Копирование SPA в backend/public..."
cd ..
cp frontend/dist/index.html backend/public/index.html
rm -rf backend/public/assets
cp -r frontend/dist/assets backend/public/assets
if [ -f frontend/dist/favicon.svg ]; then
  cp frontend/dist/favicon.svg backend/public/favicon.svg
fi

echo "==> Права на storage и БД..."
chmod -R u+rwX backend/storage backend/bootstrap/cache 2>/dev/null || true
touch backend/database/database.sqlite
chmod u+rw backend/database/database.sqlite 2>/dev/null || true

echo "==> Миграции и кэш..."
cd backend
$PHP_BIN artisan migrate --force --no-interaction
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache

echo "==> Готово."
