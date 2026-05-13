#!/usr/bin/env bash
set -euo pipefail

DB_PATH="${DB_DATABASE:-/data/database.sqlite}"
DB_DIR="$(dirname "$DB_PATH")"

mkdir -p "$DB_DIR"
if [ ! -f "$DB_PATH" ]; then
    touch "$DB_PATH"
fi

chown -R www-data:www-data "$DB_DIR" \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

php artisan storage:link --force || true
php artisan config:cache
php artisan event:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    php artisan migrate --force
fi

exec "$@"
