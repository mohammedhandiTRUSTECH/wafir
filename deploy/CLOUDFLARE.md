# Wafir — Cloudflare production README

How to expose the local Wafir stack on **https://wafir.trusttechlimited.com** via Cloudflare.

## Local services

The app must already be running on the host before you publish it:

| Service | Process (PM2) | Bind |
| --- | --- | --- |
| Proxy (frontend + `/api`) | `wafir-proxy` | http://127.0.0.1:3014 |
| Laravel backend | `wafir-backend` | http://127.0.0.1:8010 |

Useful checks:

```bash
pm2 status
curl -I http://127.0.0.1:3014
```

Public traffic should hit the **proxy on 3014**, not Laravel on 8010. The proxy serves the React build and forwards `/api` to the backend.

## Option A (recommended): Cloudflare Zero Trust public hostname

Point a Cloudflare Tunnel hostname straight at the local proxy. Same pattern as other Trusttech apps on `trusttech-tunnel`.

1. Open [Cloudflare Zero Trust](https://one.dash.cloudflare.com) → **Networks** → **Tunnels** → `trusttech-tunnel`.
2. **Add a public hostname**:
   - **Subdomain:** `wafir`
   - **Domain:** `trusttechlimited.com`
   - **Type:** HTTP
   - **URL:** `localhost:3014`
3. Save.

Site: **https://wafir.trusttechlimited.com**

TLS is handled by Cloudflare. No local Nginx or certs required for this option.

## Option B: Nginx + Cloudflare

Use this if other apps already go through Nginx on port 80 (same pattern as market / sabina).

1. Append [`nginx-wafir.conf`](./nginx-wafir.conf) into `/etc/nginx/sites-available/trusttech-apps`.
2. Test and reload:

   ```bash
   sudo nginx -t && sudo systemctl reload nginx
   ```

3. In Cloudflare Zero Trust, route `wafir.trusttechlimited.com` → `http://localhost:80`  
   (or rely on an existing catch-all that already sends that hostname to Nginx).

Nginx listens on port 80 for `wafir.trusttechlimited.com` and proxies to `127.0.0.1:3014`.

## After go-live

- Confirm HTTPS loads the frontend and `/api` requests succeed.
- If the tunnel hostname was just added, DNS may take a minute to propagate.
- Keep PM2 processes up: `pm2 save` after a working `pm2 start ecosystem.config.js`.
