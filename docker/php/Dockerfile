FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
        postgresql-dev \
        icu-dev \
        linux-headers \
        $PHPIZE_DEPS

RUN docker-php-ext-install pdo pdo_pgsql intl opcache

RUN pecl install apcu \
    && docker-php-ext-enable apcu

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
