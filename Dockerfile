# syntax=docker/dockerfile:1.7

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --prefer-dist \
        --no-progress \
        --optimize-autoloader

FROM node:22-alpine AS assets
RUN apk add --no-cache \
        php84 \
        php84-cli \
        php84-phar \
        php84-openssl \
        php84-mbstring \
        php84-tokenizer \
        php84-xml \
        php84-xmlreader \
        php84-xmlwriter \
        php84-dom \
        php84-simplexml \
        php84-fileinfo \
        php84-curl \
        php84-session \
        php84-ctype \
        php84-iconv \
        php84-pdo \
        php84-pdo_sqlite \
        php84-bcmath \
        php84-intl \
    && ln -sf /usr/bin/php84 /usr/local/bin/php
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY --from=vendor /app/vendor ./vendor
COPY . .
RUN cp .env.example .env \
    && php artisan key:generate --no-interaction --force \
    && php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form
RUN npm run build

FROM php:8.4-apache AS runtime

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install --no-install-recommends -y \
        ca-certificates \
        curl \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libsqlite3-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        intl \
        opcache \
        pcntl \
        pdo_sqlite \
        zip \
    && a2enmod rewrite headers \
    && a2dissite 000-default \
    && echo "Listen 8080" > /etc/apache2/ports.conf \
    && rm -rf /var/lib/apt/lists/*

COPY .fly/apache-laravel.conf /etc/apache2/sites-available/laravel.conf
RUN a2ensite laravel

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/testing storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache

COPY .fly/php.ini /usr/local/etc/php/conf.d/zz-fly.ini
COPY .fly/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["apache2-foreground"]
