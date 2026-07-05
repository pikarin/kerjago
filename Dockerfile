# syntax=docker/dockerfile:1

###############################################################################
# Base — FrankenPHP + PHP 8.5 with the extensions the app needs everywhere.
###############################################################################
FROM dunglas/frankenphp:1-php8.5 AS base

RUN install-php-extensions \
    pdo_pgsql \
    redis \
    pcntl \
    bcmath \
    intl \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

###############################################################################
# Local — dev image. The codebase is NOT copied; docker-compose bind-mounts it
# for instant reflection. Node is included so Vite (and the Wayfinder plugin,
# which shells out to `php artisan`) can run inside this container.
###############################################################################
FROM base AS local

RUN install-php-extensions xdebug

COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

# Off by default; flip to "debug" via docker-compose env when needed.
ENV XDEBUG_MODE=off
ENV SERVER_NAME=:8000

###############################################################################
# Production — self-contained image: code, prod composer deps, built assets,
# cached routes/views.
###############################################################################
FROM base AS production

COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

COPY . /app

RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts \
    && php artisan package:discover --ansi

# Temporary env so the app can boot during the asset build (the Wayfinder Vite
# plugin introspects routes) and for route/view caching. Removed afterwards —
# real config comes from runtime environment variables.
RUN cp .env.example .env \
    && php artisan key:generate --force \
    && npm ci \
    && npm run build \
    && php artisan route:cache \
    && php artisan view:cache \
    && rm .env \
    && rm -rf node_modules /root/.npm

ENV SERVER_NAME=:80
ENV APP_ENV=production
ENV APP_DEBUG=false
