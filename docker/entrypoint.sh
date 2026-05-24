#!/bin/sh
set -e

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public

php artisan config:cache
php artisan view:cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

exec php artisan octane:start --server="${OCTANE_SERVER:-frankenphp}" --host=0.0.0.0 --port="${OCTANE_PORT:-8000}"
