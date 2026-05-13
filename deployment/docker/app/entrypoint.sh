#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="/var/www/html"
STORAGE_DIR="${APP_ROOT}/storage"
SKELETON_DIR="${APP_ROOT}/storage.skeleton"
RUNTIME_USER="www"
RUNTIME_GROUP="www"

# -----------------------------------------------------------------------------
# Seed the shared storage volume the first time the container starts. Docker
# named volumes start empty when first mounted, so we copy the directory tree
# that was baked into the image (storage.skeleton) into the volume only when
# the standard Laravel subdirectories are missing.
# -----------------------------------------------------------------------------
if [ -d "${SKELETON_DIR}" ]; then
    needs_seed=0
    for dir in framework framework/cache framework/sessions framework/views logs app; do
        if [ ! -d "${STORAGE_DIR}/${dir}" ]; then
            needs_seed=1
            break
        fi
    done
    if [ "${needs_seed}" = "1" ]; then
        echo "[entrypoint] seeding storage volume from image skeleton..."
        cp -an "${SKELETON_DIR}/." "${STORAGE_DIR}/"
    fi
fi

# Make sure storage and bootstrap/cache are writable by the runtime user.
chown -R "${RUNTIME_USER}:${RUNTIME_GROUP}" "${STORAGE_DIR}" "${APP_ROOT}/bootstrap/cache"
chmod -R ug+rwX "${STORAGE_DIR}" "${APP_ROOT}/bootstrap/cache"

# -----------------------------------------------------------------------------
# Build framework caches on every boot. The user explicitly opted out of
# automatic migrations, so we only cache configuration / routes / views and
# leave database state untouched.
# -----------------------------------------------------------------------------
cache_app() {
    echo "[entrypoint] caching config, routes, views and events..."
    gosu "${RUNTIME_USER}" php artisan config:clear --no-interaction || true
    gosu "${RUNTIME_USER}" php artisan route:clear  --no-interaction || true
    gosu "${RUNTIME_USER}" php artisan view:clear   --no-interaction || true
    gosu "${RUNTIME_USER}" php artisan event:clear  --no-interaction || true

    gosu "${RUNTIME_USER}" php artisan config:cache --no-interaction
    gosu "${RUNTIME_USER}" php artisan route:cache  --no-interaction
    gosu "${RUNTIME_USER}" php artisan view:cache   --no-interaction
    gosu "${RUNTIME_USER}" php artisan event:cache  --no-interaction || true
}

# Storage symlink for public/storage -> storage/app/public. Failing here must
# not block startup (e.g. when the symlink already exists from a previous run).
gosu "${RUNTIME_USER}" php artisan storage:link --no-interaction || true

cache_app

# -----------------------------------------------------------------------------
# Dispatch based on the requested role.
# -----------------------------------------------------------------------------
role="${1:-web}"
shift || true

case "${role}" in
    web)
        echo "[entrypoint] starting nginx + php-fpm via supervisord"
        exec /usr/bin/supervisord -c /etc/supervisor/conf.d/web.conf
        ;;
    queue)
        echo "[entrypoint] starting queue worker"
        exec gosu "${RUNTIME_USER}" php artisan queue:work \
            --tries=3 \
            --timeout=3600 \
            --sleep=3 \
            --max-time=3600 \
            "$@"
        ;;
    reverb)
        echo "[entrypoint] starting reverb websocket server"
        exec gosu "${RUNTIME_USER}" php artisan reverb:start \
            --host=0.0.0.0 \
            --port=8080 \
            "$@"
        ;;
    scheduler)
        echo "[entrypoint] starting laravel scheduler"
        exec gosu "${RUNTIME_USER}" php artisan schedule:work "$@"
        ;;
    artisan)
        exec gosu "${RUNTIME_USER}" php artisan "$@"
        ;;
    *)
        # Allow arbitrary commands, e.g. `docker compose run app bash`.
        exec "${role}" "$@"
        ;;
esac
