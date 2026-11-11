#!/bin/sh
# Sobe o Mater nativamente no macOS (sem Docker): PHP 8.4 do Homebrew + Postgres local.
# Uso: ./dev/start.sh   (porta padrão 8000; outra: PORT=8080 ./dev/start.sh)
cd "$(dirname "$0")/.."

PHP=/opt/homebrew/opt/php@8.4/bin/php
[ -x "$PHP" ] || { echo "PHP 8.4 não encontrado. Rode: brew install php@8.4"; exit 1; }
[ -f .env ] || { echo "Falta o .env. Rode: cp .env.example .env (e ajuste DB_HOST=127.0.0.1)"; exit 1; }

brew services list | grep -q '^postgresql@16 *started' || brew services start postgresql@16

PORT=${PORT:-8000}
echo "Mater em http://localhost:$PORT  (Ctrl+C para parar)"
# Vários workers para não travar com requisições simultâneas (CSS/JS/AJAX)
PHP_CLI_SERVER_WORKERS=${PHP_CLI_SERVER_WORKERS:-8} exec "$PHP" \
  -d display_errors=On \
  -d "error_reporting=E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED" \
  -d upload_max_filesize=10M \
  -d opcache.enable=1 -d opcache.enable_cli=1 -d opcache.revalidate_freq=0 \
  -d realpath_cache_size=4096K -d realpath_cache_ttl=600 \
  -S "localhost:$PORT" -t public dev/router.php
