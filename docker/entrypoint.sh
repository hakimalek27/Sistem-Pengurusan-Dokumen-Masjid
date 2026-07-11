#!/usr/bin/env sh
set -eu

mkdir -p storage/app/tmp storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ "${DIWAN_CACHE_CONFIG:-true}" = "true" ]; then
    php artisan config:cache --no-interaction
    php artisan route:cache --no-interaction
    php artisan view:cache --no-interaction
fi

exec "$@"
