# syntax=docker/dockerfile:1.7

# -------- base: PHP 8.4 + required extensions --------
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    git curl zip unzip bash icu-dev libzip-dev libpng-dev oniguruma-dev \
    postgresql-dev linux-headers $PHPIZE_DEPS \
 && docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip exif pcntl bcmath intl gd \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} app && adduser -D -u ${UID} -G app app

WORKDIR /var/www/html
USER app

EXPOSE 9000
CMD ["php-fpm"]

# -------- dev: adds node + npm for running Vite/artisan/composer live --------
FROM base AS dev

USER root
RUN apk add --no-cache nodejs npm
USER app

# -------- composer-deps: production PHP dependencies only --------
FROM composer:2 AS composer-deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader --no-progress
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# -------- asset-build: Vite build --------
FROM node:20-alpine AS asset-build
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
# Vite imports from vendor/tightenco/ziggy at build time, so we need composer
# deps in place before `npm run build` runs.
COPY --from=composer-deps /app/vendor /app/vendor
RUN npm run build

# -------- prod: final lean runtime image --------
FROM base AS prod

USER root
WORKDIR /var/www/html

COPY --chown=app:app . /var/www/html
COPY --from=composer-deps --chown=app:app /app/vendor /var/www/html/vendor
COPY --from=asset-build --chown=app:app /app/public/build /var/www/html/public/build

RUN chown -R app:app storage bootstrap/cache \
 && chmod -R ug+w storage bootstrap/cache

USER app
EXPOSE 9000
CMD ["php-fpm"]
