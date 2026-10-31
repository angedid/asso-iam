#!/bin/sh
set -e

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Ensure Passport keys exist
if [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force
fi

# Run migrations if configured
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

exec "$@"
