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
FROM php-base AS runtime

# Copy application source
COPY . ./

# Copy in PHP dependencies and compiled assets from previous stages
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=frontend /var/www/html/public/build ./public/build

# Ensure storage directories exist with proper permissions
RUN set -eux; \
    mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 80

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
