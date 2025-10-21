# syntax=docker/dockerfile:1.7

###############################
# Base PHP image with extensions
###############################
FROM php:8.2-cli AS php-base

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html

# Install system dependencies required by Laravel and Composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install \
        zip \
        pdo_mysql \
        bcmath \
        pcntl \
    && rm -rf /var/lib/apt/lists/*

# Provide the Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

###############################
# Install PHP dependencies
###############################
FROM php-base AS vendor

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-ansi \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

###############################
# Build frontend assets
###############################
FROM node:20-slim AS frontend

WORKDIR /var/www/html

# Sensible npm defaults for CI/builds (documented config keys)
ENV npm_config_loglevel=warn \
    npm_config_progress=false \
    npm_config_fund=false \
    npm_config_fetch_retries=5 \
    npm_config_fetch_retry_mintimeout=20000 \
    npm_config_fetch_retry_maxtimeout=120000 \
    npm_config_fetch_timeout=600000

COPY package.json package-lock.json ./

# Use BuildKit cache so npm can reuse tarballs between builds
# (First build will fill the cache; subsequent ones are much faster)
RUN --mount=type=cache,target=/root/.npm npm ci --no-audit

COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
COPY postcss.config.js tailwind.config.js ./

RUN --mount=type=secret,id=app_env \
    if [ ! -f /run/secrets/app_env ]; then \
      echo "Missing BuildKit secret 'app_env' (pass --secret id=app_env,src=.env)" >&2; \
      exit 1; \
    fi; \
    set -a; \
    . /run/secrets/app_env; \
    set +a; \
    npm run build

###############################
# Final runtime image
###############################
FROM php:8.2-fpm AS runtime
ENV DEBIAN_FRONTEND=noninteractive

# 1) Instala nginx + supervisor y libs requeridas por extensiones PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
      nginx supervisor curl ca-certificates git unzip \
      libicu-dev libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones PHP necesarias en ESTA MISMA ETAPA
RUN docker-php-ext-install intl pdo_mysql zip

WORKDIR /var/www/html

# Copy application source
COPY . ./

# Copy in PHP dependencies and compiled assets from previous stages
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=frontend /var/www/html/public/build ./public/build

# Ensure storage directories exist with proper permissions
RUN set -eux; \
    mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache

# Healthcheck simple (estático) — si no tienes ruta /healthz en Laravel
RUN printf "%s\n" "<?php http_response_code(200); echo 'ok';" > public/healthz.php

# Nginx + Supervisor
# Nginx + Supervisor (rutas consistentes)
#RUN rm -rf /etc/nginx/sites-enabled/* /etc/nginx/sites-available/* \
#    && mkdir -p /etc/nginx/conf.d

RUN mkdir -p /etc/nginx/conf.d

# 🔧 Colócala como archivo principal, NO en conf.d
COPY deploy/nginx.main.conf /etc/nginx/nginx.conf
COPY deploy/nginx.conf /etc/nginx/conf.d/app.conf
COPY deploy/supervisord.conf /etc/supervisor/supervisord.conf

# ✅ Verificaciones (fallan el build si falta algo)
RUN command -v supervisord && supervisord -v \
 && nginx -v

EXPOSE 80

# Healthcheck (ajusta si usas otra ruta)
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=20s \
  CMD curl -fsS http://localhost/healthz || exit 1

USER root
CMD ["supervisord","-n","-c","/etc/supervisor/supervisord.conf"]