#!/bin/bash
set -e

cd /app/backend

# database/, storage/ are expected to be bind-mounted from the host so real
# data (the sqlite db, uploaded files) survives redeploys. Make sure the
# paths exist even on a first run against an empty mount.
mkdir -p database storage/app/public storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views storage/logs
touch -a database/database.sqlite

php artisan config:clear
php artisan migrate --force || true
php artisan storage:link || true

php artisan serve --host=127.0.0.1 --port="${WAFIR_BACKEND_PORT:-8010}" &
BACKEND_PID=$!

node /app/scripts/wafir-proxy.js &
PROXY_PID=$!

term() {
  kill -TERM "$BACKEND_PID" "$PROXY_PID" 2>/dev/null
}
trap term SIGTERM SIGINT

wait -n "$BACKEND_PID" "$PROXY_PID"
exit_code=$?
term
exit "$exit_code"
