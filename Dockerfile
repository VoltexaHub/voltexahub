FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git curl zip unzip bash icu-dev libzip-dev libpng-dev oniguruma-dev \
    postgresql-dev nodejs npm linux-headers $PHPIZE_DEPS \
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
