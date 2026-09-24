#!/bin/sh
set -eu

APP_PORT="${PORT:-8080}"

php artisan config:clear
php artisan migrate --force
php artisan db:seed --force
php artisan optimize

exec php artisan serve --host=0.0.0.0 --port="$APP_PORT"
