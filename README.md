# Wafir

Sales performance platform for Trusttech: dashboards, hierarchy (sales managers → area managers → supervisors → reps), targets, forecasts, and commission schemes.

**Live:** [https://wafir.trusttechlimited.com](https://wafir.trusttechlimited.com)

## Stack

| Layer | Tech |
| --- | --- |
| Frontend | React 19, Tailwind CSS, React Router, i18next (EN / AR), Chart.js |
| Backend | Laravel 12, PHP 8.2+, JWT auth, Sanctum |
| Process manager | PM2 (`wafir-backend`, `wafir-proxy`) |
| Production edge | Cloudflare Tunnel → local proxy |

```
Browser → Cloudflare → wafir-proxy :3014
                           ├─ static: frontend/build
                           └─ /api  → Laravel :8010
```

## Repository layout

```
wafir/
├── frontend/          # React admin UI
├── backend/           # Laravel API
├── scripts/           # wafir-proxy.js (static + API gateway)
├── deploy/            # Cloudflare + Nginx notes
└── ecosystem.config.js
```

## Local development

**Requirements:** PHP 8.2+, Composer, Node.js, npm or Yarn, a database (SQLite by default).

### Backend

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Dev frontend runs on **http://localhost:9000** and talks to **http://127.0.0.1:8000/api/admin**.

| Script | Purpose |
| --- | --- |
| `npm run dev` | Development server |
| `npm run build:prod` | Production build |
| `npm run build:staging` | Staging build |
| `npm run format` | Prettier |

## Production (PM2)

```bash
cd frontend && npm run build:prod
pm2 start ecosystem.config.js
pm2 save
pm2 status
```

| Process | Port |
| --- | --- |
| `wafir-backend` | `127.0.0.1:8010` |
| `wafir-proxy` | `127.0.0.1:3014` (public entry) |

Public traffic must hit the **proxy**, not Laravel directly.

## Deploy / Cloudflare

See [deploy/CLOUDFLARE.md](deploy/CLOUDFLARE.md) for tunnel and Nginx options.

## App areas

- Login (JWT)
- Dashboard & working days
- Sales managers, area managers, supervisors, sales reps
- Admin panel: targets & forecasts, reps, supervisors, commission schemes
- ERP user sync

