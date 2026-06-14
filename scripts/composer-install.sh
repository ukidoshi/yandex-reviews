#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DEPLOY_PATH="$(cd "$SCRIPT_DIR/.." && pwd)"

# shellcheck source=lib/php-bin.sh
source "$SCRIPT_DIR/lib/php-bin.sh"

if ! PHP_BIN="$(find_php_bin "$DEPLOY_PATH/deploy.env")"; then
  php_bin_hint
  exit 1
fi
export PHP_BIN

BACKEND_DIR="$(cd "$SCRIPT_DIR/../backend" && pwd)"
cd "$BACKEND_DIR"

if command -v composer >/dev/null 2>&1; then
  echo "==> composer install (глобальный composer)"
  composer install --no-dev --optimize-autoloader --no-interaction
  exit 0
fi

if [ ! -f composer.phar ]; then
  echo "==> Скачиваем composer.phar (PHP: $PHP_BIN)..."
  curl -sS https://getcomposer.org/installer | "$PHP_BIN"
fi

echo "==> composer.phar install"
"$PHP_BIN" composer.phar install --no-dev --optimize-autoloader --no-interaction
