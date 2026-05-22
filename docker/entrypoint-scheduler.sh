#!/usr/bin/env sh
# Scheduler entrypoint — called via startCommand
set -eu

echo "[scheduler] Starting Laravel Scheduler..."

mkdir -p storage/framework/cache storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache

if [ -n "${APP_KEY:-}" ]; then
    php artisan optimize:clear >/dev/null 2>&1 || true
    php artisan optimize >/dev/null 2>&1 || true
fi

while true; do
    php artisan schedule:run --ansi --no-interaction >> /var/log/scheduler.log 2>&1 || true
    sleep 60
done
