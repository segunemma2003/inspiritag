#!/bin/bash
set -e

echo "🚀 Starting Laravel application setup..."

# Wait for MySQL on HOST (not Docker container)
echo "⏳ Waiting for MySQL on host..."
max_attempts=30
attempt=0

# Use host MySQL connection
DB_HOST=${DB_HOST:-host.docker.internal}
DB_USER=${DB_USERNAME:-root}
DB_PASS=${DB_PASSWORD:-}

while ! mysqladmin ping -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" --silent 2>/dev/null; do
    attempt=$((attempt + 1))
    if [ $attempt -eq $max_attempts ]; then
        echo "⚠️ MySQL connection failed after $max_attempts attempts"
        echo "Continuing anyway - application will start but DB operations may fail"
        break
    fi
    echo "Waiting for MySQL on ${DB_HOST}... (attempt $attempt/$max_attempts)"
    sleep 2
done

if [ $attempt -lt $max_attempts ]; then
    echo "✅ MySQL is ready!"
fi

# Install/update composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# CRITICAL: Clear all cached configs to read .env file
echo "🧹 Clearing all caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
rm -rf bootstrap/cache/*.php

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:your-app-key-here" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
fi

# Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force || echo "⚠️ Migration failed, continuing..."

# Cache config only AFTER migrations
echo "📦 Caching configuration..."
php artisan config:cache

# Set proper permissions
echo "🔐 Setting proper permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "✅ Laravel application setup completed!"

# Start PHP-FPM (supervisor is not needed for separate queue/scheduler containers)
exec php-fpm
