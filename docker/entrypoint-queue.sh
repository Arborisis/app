#!/usr/bin/env sh
# Queue Worker entrypoint — called via startCommand
set -eu

echo "[queue] Starting Laravel Queue Worker..."

mkdir -p storage/framework/cache storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache

# Optimizations
if [ -n "${APP_KEY:-}" ]; then
    php artisan optimize:clear >/dev/null 2>&1 || true
    php artisan optimize >/dev/null 2>&1 || true
fi

exec php artisan queue:work \
    --queue="${QUEUE_QUEUE:-default}" \
    --sleep=3 \
    --tries=3 \
    --max-time=3600 \
    --ansi
