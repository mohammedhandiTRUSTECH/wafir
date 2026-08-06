# Wafir production exposure

App is running locally:
- Proxy (frontend + /api): http://127.0.0.1:3014
- Laravel backend: http://127.0.0.1:8010
- PM2: wafir-backend, wafir-proxy

## Option A (recommended): Cloudflare Zero Trust public hostname
1. Open https://one.dash.cloudflare.com → Networks → Tunnels → trusttech-tunnel
2. Add Public Hostname:
   - Subdomain: wafir
   - Domain: trusttechlimited.com
   - Type: HTTP
   - URL: localhost:3014
3. Save. Site: https://wafir.trusttechlimited.com

## Option B: Nginx + Cloudflare (same pattern as market/sabina)
1. Append deploy/nginx-wafir.conf into /etc/nginx/sites-available/trusttech-apps
2. sudo nginx -t && sudo systemctl reload nginx
3. In Cloudflare Zero Trust, route wafir.trusttechlimited.com → http://localhost:80
   (or rely on existing catch-all to nginx if present)
