#!/bin/sh
set -e

echo "==> Preparing HRMS Backend Application..."

# Ensure storage and bootstrap/cache permissions
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# If sqlite database file doesn't exist, create it
if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "==> Creating SQLite database file..."
    touch /var/www/html/database/database.sqlite
fi

# Ensure storage symlink
php artisan storage:link --force || true

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "==> Generating Application Key..."
    php artisan key:generate --force
fi

# Run database migrations and seed default configuration & superadmin
echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding initial data..."
php artisan db:seed --force || true

# Optimize Laravel configuration and views in production
if [ "$APP_ENV" = "production" ]; then
    echo "==> Caching configuration and routes for production..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Fix ownership
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

echo "==> Starting PHP-FPM & Nginx..."
php-fpm -D
exec nginx -g "daemon off;"
