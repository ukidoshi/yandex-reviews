#!/usr/bin/env bash
# Находит рабочий PHP CLI на shared hosting (REG.RU и др.).
# В bash-скриптах alias php83 не работает — нужен полный путь.

find_php_bin() {
  local candidate
  local deploy_env="${1:-}"

  # 1. Переменная окружения
  if [ -n "${PHP_BIN:-}" ] && [ -x "$PHP_BIN" ]; then
    echo "$PHP_BIN"
    return 0
  fi

  # 2. Файл deploy.env в корне проекта
  if [ -n "$deploy_env" ] && [ -f "$deploy_env" ]; then
    # shellcheck disable=SC1090
    source "$deploy_env"
    if [ -n "${PHP_BIN:-}" ] && [ -x "$PHP_BIN" ]; then
      echo "$PHP_BIN"
      return 0
    fi
  fi

  # 3. Команды в PATH
  for candidate in php83 php8.3 php; do
    if command -v "$candidate" >/dev/null 2>&1; then
      command -v "$candidate"
      return 0
    fi
  done

  # 4. Типичные пути REG.RU / ISPmanager
  for candidate in \
    /opt/php/8.3/bin/php \
    /opt/php/83/bin/php \
    /opt/alt/php83/usr/bin/php \
    /opt/alt/php83/usr/local/bin/php \
    /usr/bin/php8.3 \
    /usr/local/bin/php83
  do
    if [ -x "$candidate" ]; then
      echo "$candidate"
      return 0
    fi
  done

  # 5. Любой Alt-PHP 8.3
  for candidate in /opt/alt/php83*/usr/bin/php; do
    if [ -x "$candidate" ]; then
      echo "$candidate"
      return 0
    fi
  done

  return 1
}

php_bin_hint() {
  cat <<'EOF'
PHP не найден. Выполните на сервере:

  type php83
  which php php8.3
  ls /opt/php/*/bin/php 2>/dev/null
  ls /opt/alt/php*/usr/bin/php 2>/dev/null

Создайте deploy.env в корне проекта:

  PHP_BIN=/opt/php/8.3/bin/php

Или запустите:

  PHP_BIN=/путь/к/php bash scripts/deploy.sh
EOF
}
