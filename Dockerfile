# --- Stage 1: build the React frontend (matches `npm run build:prod`) ---
FROM node:20-bookworm AS frontend-build
WORKDIR /app/frontend
COPY frontend/package.json frontend/package-lock.json ./
RUN npm install --legacy-peer-deps
COPY frontend/ ./
RUN npm run build:prod

# --- Stage 2: PHP backend + node proxy, single runtime image ---
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libsqlite3-dev libpng-dev libonig-dev libxml2-dev nodejs npm \
    && docker-php-ext-install pdo pdo_sqlite mbstring bcmath zip gd exif pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/backend
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
COPY backend/ ./
RUN composer dump-autoload --optimize \
    && mkdir -p database storage/app/public storage/framework/{cache,sessions,testing,views} storage/logs \
    && touch database/database.sqlite

COPY --from=frontend-build /app/frontend/build /app/frontend/build
COPY scripts/wafir-proxy.js /app/scripts/wafir-proxy.js
COPY docker-entrypoint.sh /app/docker-entrypoint.sh
RUN chmod +x /app/docker-entrypoint.sh

ENV WAFIR_PROXY_PORT=3014 \
    WAFIR_BACKEND_PORT=8010 \
    WAFIR_STATIC_ROOT=/app/frontend/build

EXPOSE 3014
WORKDIR /app
ENTRYPOINT ["/app/docker-entrypoint.sh"]
