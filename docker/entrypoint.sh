#!/bin/bash
set -e

echo "🚀 Starting Laravel application setup..."

# Wait for MySQL on HOST (not Docker container) - but don't block PHP-FPM startup
echo "⏳ Checking MySQL connection..."
max_attempts=15  # Reduced to 30 seconds max
attempt=0

# Use host MySQL connection
DB_HOST=${DB_HOST:-host.docker.internal}
DB_USER=${DB_USERNAME:-root}
DB_PASS=${DB_PASSWORD:-}

# Check if mysqladmin is available
if command -v mysqladmin >/dev/null 2>&1; then
    while ! mysqladmin ping -h"${DB_HOST}" -u"${DB_USER}" -p"${DB_PASS}" --silent 2>/dev/null; do
        attempt=$((attempt + 1))
        if [ $attempt -eq $max_attempts ]; then
            echo "⚠️ MySQL connection failed after $max_attempts attempts"
            echo "Continuing anyway - PHP-FPM will start and MySQL will be checked later"
            break
        fi
        echo "Waiting for MySQL on ${DB_HOST}... (attempt $attempt/$max_attempts)"
        sleep 2
    done

    if [ $attempt -lt $max_attempts ]; then
        echo "✅ MySQL is ready!"
    fi
else
    echo "⚠️ mysqladmin not found - skipping MySQL check"
    echo "PHP-FPM will start and MySQL connection will be tested by Laravel"
fi

# Do setup tasks quickly (non-blocking)
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction || echo "⚠️ Composer install failed, continuing..."

# CRITICAL: Clear all cached configs to read .env file
echo "🧹 Clearing all caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true
rm -rf bootstrap/cache/*.php || true

# Generate application key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:your-app-key-here" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force || echo "⚠️ Key generation failed"
fi

# Set proper permissions
echo "🔐 Setting proper permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Run migrations in background (non-blocking)
(
    echo "🗄️ Running database migrations in background..."
    sleep 5  # Give MySQL a bit more time
    php artisan migrate --force || echo "⚠️ Migration failed"

    # Cache config only AFTER migrations
    echo "📦 Caching configuration..."
    php artisan config:cache || echo "⚠️ Config cache failed"
    php artisan route:cache || echo "⚠️ Route cache failed"
    php artisan view:cache || echo "⚠️ View cache failed"
) &

echo "✅ Laravel application setup completed!"
echo "🚀 Starting PHP-FPM..."

# Start PHP-FPM as main process (this keeps container alive)
exec php-fpm
