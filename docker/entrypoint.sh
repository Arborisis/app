#!/usr/bin/env sh
set -eu

echo "[entrypoint] Starting Arborisis Railway container..."

# Storage directories
mkdir -p \
    storage/app \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    public

# Restore public assets from dist if needed (volume mount scenario)
if [ ! -d public/build ] && [ -d public.dist ]; then
    echo "[entrypoint] Restoring public assets from public.dist..."
    cp -a public.dist/. public/
fi

# Permissions
chown -R www-data:www-data storage bootstrap/cache public 2>/dev/null || true
chmod -R 775 storage bootstrap/cache

# Laravel optimizations (skip during build, run at start)
if [ "${CONTAINER_SKIP_OPTIMIZE:-false}" != "true" ] && [ -n "${APP_KEY:-}" ]; then
    echo "[entrypoint] Running Laravel optimizations..."
    php artisan optimize:clear >/dev/null 2>&1 || true
    php artisan package:discover --ansi >/dev/null 2>&1 || true
    php artisan optimize >/dev/null 2>&1 || true
fi

# Storage symlink
if [ "${CONTAINER_STORAGE_LINK:-true}" = "true" ] && [ ! -L public/storage ]; then
    echo "[entrypoint] Creating storage symlink..."
    php artisan storage:link >/dev/null 2>&1 || true
fi

# DB migrations (opt-in via env var)
if [ "${CONTAINER_RUN_MIGRATIONS:-false}" = "true" ] && [ -n "${APP_KEY:-}" ]; then
    echo "[entrypoint] Running database migrations..."
    php artisan migrate --force --ansi || true
fi

echo "[entrypoint] Ready. Executing: $@"
exec "$@"
