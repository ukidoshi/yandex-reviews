#!/usr/bin/env bash
# Запускается на сервере после выгрузки файлов из GitHub Actions.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DEPLOY_PATH="${DEPLOY_PATH:-$(cd "$SCRIPT_DIR/.." && pwd)}"

cd "$DEPLOY_PATH"

# shellcheck source=lib/php-bin.sh
source "$SCRIPT_DIR/lib/php-bin.sh"

if ! PHP_BIN="$(find_php_bin "$DEPLOY_PATH/deploy.env")"; then
  php_bin_hint
  exit 1
fi
export PHP_BIN

echo "==> PHP: $PHP_BIN ($($PHP_BIN -v | head -1))"

echo "==> Composer (backend)..."
cd backend
bash ../scripts/composer-install.sh

echo "==> Права на storage и БД..."
chmod -R u+rwX storage bootstrap/cache 2>/dev/null || true
touch database/database.sqlite
chmod u+rw database/database.sqlite 2>/dev/null || true

echo "==> Миграции и кэш..."
$PHP_BIN artisan migrate --force --no-interaction
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache

echo "==> Готово."
