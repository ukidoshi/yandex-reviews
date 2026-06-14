#!/usr/bin/env bash
# Ручной деплой на сервере (код уже должен быть на сервере).
# Обычный деплой — push в main, GitHub Actions всё сделает сам.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
exec bash "$SCRIPT_DIR/deploy-remote.sh"
