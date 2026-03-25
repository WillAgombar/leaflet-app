FROM composer:2 AS composer_deps
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts

FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./vite.config.js
RUN npm run build

FROM php:8.5-cli-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache icu-dev oniguruma-dev libzip-dev zlib-dev postgresql-dev \
    && docker-php-ext-install bcmath intl pdo_mysql pdo_pgsql pgsql zip

COPY --from=composer_deps /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build

RUN rm -f public/hot \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["sh", "-c", "until php artisan migrate --force; do echo 'Waiting for database...'; sleep 3; done; php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
