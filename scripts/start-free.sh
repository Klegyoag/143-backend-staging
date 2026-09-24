#!/bin/sh
set -eu

APP_PORT="${PORT:-8080}"

php artisan config:clear
php artisan config:cache

# The free container's health check expires while the large initial schema is
# being applied. Serve the database-independent health endpoint immediately and
# finish the idempotent database setup in the background.
(
    php artisan migrate --force && php artisan db:seed --force
) &

exec php artisan serve --host=0.0.0.0 --port="$APP_PORT"
