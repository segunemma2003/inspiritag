#!/bin/bash

# Server Check and Fix Script
# Run this on your server: ssh Root@38.180.244.178

echo "=========================================="
echo "🔍 Checking Server Status"
echo "=========================================="
echo ""

# Check if we're on the server
if [ ! -f /root/.ssh/authorized_keys ] && [ "$(hostname)" != "inspirtag" ]; then
    echo "⚠️  This script should be run ON the server"
    echo ""
    echo "Run this command first:"
    echo "  ssh Root@38.180.244.178"
    echo "  Password: PGU8aPqTm2"
    echo ""
    echo "Then run:"
    echo "  bash check_and_fix_server.sh"
    exit 1
fi

echo "✅ Connected to server"
echo ""

# Find project directory
echo "📁 Finding project directory..."
if [ -d "/var/www/html/social-media" ]; then
    PROJECT_DIR="/var/www/html/social-media"
elif [ -d "/var/www/social-media" ]; then
    PROJECT_DIR="/var/www/social-media"
elif [ -d "/root/social-media" ]; then
    PROJECT_DIR="/root/social-media"
elif [ -d "$HOME/social-media" ]; then
    PROJECT_DIR="$HOME/social-media"
else
    echo "❌ Cannot find project directory"
    echo "Looking in common locations..."
    find /var/www /root $HOME -maxdepth 2 -name "social-media" -type d 2>/dev/null
    echo ""
    echo "Please cd to your project directory and run this script again"
    exit 1
fi

echo "✅ Found project: $PROJECT_DIR"
cd "$PROJECT_DIR"
echo ""

# Check Docker
echo "🐳 Checking Docker..."
if ! command -v docker &> /dev/null; then
    echo "❌ Docker not installed"
    exit 1
fi
echo "✅ Docker is installed: $(docker --version)"
echo ""

# Check if containers are running
echo "📊 Container Status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""

RUNNING=$(docker ps | grep -c "inspirtag" || echo "0")

if [ "$RUNNING" -eq "0" ]; then
    echo "❌ No containers running! Starting now..."
    echo ""

    # Stop any existing containers
    echo "🛑 Stopping any existing containers..."
    docker-compose down 2>/dev/null || true
    echo ""

    # Start containers
    echo "🚀 Starting containers..."
    docker-compose up -d

    # Wait for containers to start
    echo "⏳ Waiting for containers to start..."
    sleep 10

    # Show status
    echo ""
    echo "📊 New Container Status:"
    docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
    echo ""
else
    echo "✅ $RUNNING container(s) running"
    echo ""
fi

# Check if app container exists
if docker ps | grep -q "inspirtag-app\|inspirtag-api"; then
    APP_CONTAINER=$(docker ps --format "{{.Names}}" | grep "inspirtag-app\|inspirtag-api" | head -1)
    echo "✅ App container: $APP_CONTAINER"

    # Check Laravel
    echo ""
    echo "🔍 Checking Laravel..."

    # Check .env exists
    if docker exec "$APP_CONTAINER" test -f /var/www/html/.env; then
        echo "✅ .env file exists"
    else
        echo "❌ .env file missing!"
    fi

    # Check APP_KEY
    APP_KEY=$(docker exec "$APP_CONTAINER" php artisan key:generate --show 2>&1)
    if [ $? -eq 0 ]; then
        echo "✅ APP_KEY is set"
    else
        echo "⚠️  Generating APP_KEY..."
        docker exec "$APP_CONTAINER" php artisan key:generate
    fi

    # Run migrations
    echo ""
    echo "🗄️  Running migrations..."
    docker exec "$APP_CONTAINER" php artisan migrate --force

    # Clear caches
    echo ""
    echo "🧹 Clearing caches..."
    docker exec "$APP_CONTAINER" php artisan config:clear
    docker exec "$APP_CONTAINER" php artisan cache:clear
    docker exec "$APP_CONTAINER" php artisan route:clear

    # Fix permissions
    echo ""
    echo "🔧 Fixing permissions..."
    docker exec "$APP_CONTAINER" chown -R www-data:www-data /var/www/html/storage
    docker exec "$APP_CONTAINER" chmod -R 775 /var/www/html/storage
    docker exec "$APP_CONTAINER" chmod -R 775 /var/www/html/bootstrap/cache

    echo ""
    echo "✅ Laravel setup complete"
else
    echo "❌ App container not found!"
    echo "Container names found:"
    docker ps --format "{{.Names}}"
fi

# Test health endpoint
echo ""
echo "🏥 Testing API health..."
HEALTH=$(curl -s http://localhost/api/health 2>&1)
if echo "$HEALTH" | grep -q "healthy"; then
    echo "✅ API is healthy!"
    echo "$HEALTH"
else
    echo "❌ API not responding correctly"
    echo "Response: $HEALTH"
    echo ""
    echo "Checking logs..."
    docker logs "$APP_CONTAINER" --tail 30
fi

echo ""
echo "=========================================="
echo "📊 Summary"
echo "=========================================="
echo ""
echo "Server: 38.180.244.178"
echo "Project: $PROJECT_DIR"
echo "Containers: $RUNNING running"
echo ""

# Test from external
echo "🌐 Testing external access..."
EXTERNAL=$(curl -s http://38.180.244.178/api/health 2>&1)
if echo "$EXTERNAL" | grep -q "healthy"; then
    echo "✅ Server is accessible from outside!"
else
    echo "⚠️  External access may have issues"
fi

echo ""
echo "=========================================="
echo "✅ Setup Complete!"
echo "=========================================="
echo ""
echo "Test OTP registration:"
echo "curl -X POST http://38.180.244.178/api/register \\"
echo "  -H 'Content-Type: application/json' \\"
echo "  -d '{"
echo "    \"full_name\": \"Test User\","
echo "    \"email\": \"test@example.com\","
echo "    \"username\": \"testuser123\","
echo "    \"password\": \"Password123!\","
echo "    \"password_confirmation\": \"Password123!\","
echo "    \"terms_accepted\": true"
echo "  }'"
echo ""

