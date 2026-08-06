#!/bin/bash
export TUNNEL_TRANSPORT_PROTOCOL=http2
# Isolate from /etc/cloudflared
exec /usr/local/bin/cloudflared tunnel --config /dev/null --url http://127.0.0.1:3014 --no-autoupdate --protocol http2
