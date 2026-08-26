FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.4-cli
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libonig-dev libcurl4-openssl-dev libxml2-dev git unzip ca-certificates \
    && docker-php-ext-install pdo_pgsql pcntl bcmath mbstring curl dom \
    && rm -rf /var/lib/apt/lists/*
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan package:discover --ansi
EXPOSE 10000
CMD ["sh","-lc","php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
