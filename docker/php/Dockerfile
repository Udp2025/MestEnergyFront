# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.3

# ---------- Base PHP-FPM image with ONLY what MySQL needs ----------
FROM php:${PHP_VERSION}-fpm-alpine AS php-base
WORKDIR /var/www

# System deps for common Laravel extensions
RUN apk add --no-cache \
    bash curl git tzdata icu-dev libzip-dev oniguruma-dev \
    freetype-dev libjpeg-turbo-dev libpng-dev

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
      pdo pdo_mysql mbstring bcmath intl gd zip opcache exif

# Custom php.ini with OPcache for prod
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# ---------- DEV target (code is bind-mounted in compose) ----------
FROM php-base AS php-dev
WORKDIR /var/www
CMD ["php-fpm"]

# ---------- Build vendor (Composer) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./

# If using BuildKit, you can cache Composer downloads to speed up subsequent builds:
# RUN --mount=type=cache,target=/tmp/composer \
#     composer install --no-dev --prefer-dist --no-interaction --no-progress \
#       --optimize-autoloader --classmap-authoritative --no-scripts
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress \
      --optimize-autoloader --no-scripts
# --no-scripts avoids running Laravel's post-autoload script that calls `artisan`
#   (we will run the needed artisan commands later in the final stage).

# ---------- Build frontend (Vite) ----------
FROM node:20-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

# ---------- PROD PHP-FPM (immutable runtime) ----------
FROM php-base AS php-prod
WORKDIR /var/www

# Copy your app code
COPY . .

# Copy optimized vendor and built assets from previous stages
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Ensure Laravel writable dirs
RUN set -eux; \
    mkdir -p storage bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache; \
    chmod -R 775 storage bootstrap/cache

# Run Laravel discovery & build caches only now that 'artisan' exists
# Clean any stale cache files that may reference dev-only packages (e.g., Breeze)
RUN rm -f bootstrap/cache/*.php
RUN php artisan package:discover --ansi
RUN php artisan config:cache
RUN php artisan view:cache

CMD ["php-fpm"]

# Nginx for prod (serves only /public) 
FROM nginx:1.27-alpine AS nginx-prod
WORKDIR /var/www
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=php-prod /var/www/public ./public
