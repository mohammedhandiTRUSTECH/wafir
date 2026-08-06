#!/usr/bin/env bash
#
# Rebuild frontend and restart Wafir PM2 apps.
#
# Usage:
#   ./scripts/deploy.sh          # frontend build + restart
#   ./scripts/deploy.sh --skip-build   # restart only
#
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PROXY_PORT="${WAFIR_PROXY_PORT:-3014}"
BACKEND_PORT="${WAFIR_BACKEND_PORT:-8010}"
PM2_APPS=(wafir-backend wafir-proxy)

log()  { printf '\033[36m==>\033[0m %s\n' "$*"; }
ok()   { printf '\033[32mOK:\033[0m %s\n' "$*"; }
fail() { printf '\033[31mFAIL:\033[0m %s\n' "$*" >&2; exit 1; }

SKIP_BUILD=0
for arg in "$@"; do
  case "$arg" in
    --skip-build) SKIP_BUILD=1 ;;
    -h|--help)
      sed -n '2,10p' "$0"
      exit 0
      ;;
    *)
      fail "Unknown option: $arg (use --skip-build or --help)"
      ;;
  esac
done

if [ "$SKIP_BUILD" -eq 0 ]; then
  log "Installing frontend deps (if needed)"
  if [ ! -d frontend/node_modules ]; then
    (cd frontend && npm ci)
  fi

  log "Building frontend (production)"
  (cd frontend && npm run build:prod)

  if [ ! -f frontend/build/index.html ]; then
    fail "Build did not produce frontend/build/index.html"
  fi
  ok "Frontend build ready"
else
  log "Skipping frontend build"
fi

log "Ensuring PM2 apps are running"
if ! command -v pm2 >/dev/null 2>&1; then
  fail "pm2 not found"
fi

# Start from ecosystem if either app is missing; otherwise restart.
missing=0
for app in "${PM2_APPS[@]}"; do
  if ! pm2 describe "$app" >/dev/null 2>&1; then
    missing=1
    break
  fi
done

if [ "$missing" -eq 1 ]; then
  log "Starting apps from ecosystem.config.js"
  pm2 start "$ROOT/ecosystem.config.js"
else
  log "Restarting ${PM2_APPS[*]}"
  pm2 restart "${PM2_APPS[@]}" --update-env
fi

log "Waiting for proxy on :$PROXY_PORT"
ready=0
for _ in $(seq 1 30); do
  if curl -sf -o /dev/null --max-time 3 "http://127.0.0.1:${PROXY_PORT}/"; then
    ready=1
    break
  fi
  sleep 1
done
[ "$ready" -eq 1 ] || fail "Proxy did not respond on http://127.0.0.1:${PROXY_PORT}/"

code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 "http://127.0.0.1:${PROXY_PORT}/" || echo "000")
[ "$code" = "200" ] || fail "Health check failed (HTTP $code)"

ok "Deploy complete — http://127.0.0.1:${PROXY_PORT}/ (backend :${BACKEND_PORT})"
ok "Public URL (if tunnel configured): https://wafir.trusttechlimited.com"
pm2 list | grep -E 'wafir-|name' || true
