#!/bin/bash
# fix-port-80.sh
# Quick fix for "port 80 already in use" error during deployment

set -e

echo "🔍 Checking what's using port 80..."
echo ""

# Check what's using port 80
if command -v lsof &> /dev/null; then
    echo "Using lsof:"
    sudo lsof -i :80 || echo "Port 80 appears to be free (or lsof found nothing)"
elif command -v netstat &> /dev/null; then
    echo "Using netstat:"
    sudo netstat -tulpn | grep :80 || echo "Port 80 appears to be free"
elif command -v ss &> /dev/null; then
    echo "Using ss:"
    sudo ss -tulpn | grep :80 || echo "Port 80 appears to be free"
else
    echo "⚠️  No port checking tool found (lsof, netstat, or ss)"
fi

echo ""
echo "🛑 Stopping conflicting services..."
echo ""

# Stop system nginx if running
if systemctl is-active --quiet nginx 2>/dev/null; then
    echo "✅ Stopping system nginx..."
    sudo systemctl stop nginx
    sudo systemctl disable nginx
    echo "   System nginx stopped and disabled"
else
    echo "ℹ️  System nginx is not running"
fi

# Stop system apache if running
if systemctl is-active --quiet apache2 2>/dev/null; then
    echo "✅ Stopping system apache2..."
    sudo systemctl stop apache2
    sudo systemctl disable apache2
    echo "   System apache2 stopped and disabled"
else
    echo "ℹ️  System apache2 is not running"
fi

# Stop old Docker containers using port 80
echo ""
echo "🐳 Checking Docker containers using port 80..."

# Find containers using port 80
CONTAINERS_USING_PORT=$(docker ps --filter "publish=80" --format "{{.Names}}" 2>/dev/null || true)

if [ -n "$CONTAINERS_USING_PORT" ]; then
    echo "   Found containers using port 80:"
    echo "$CONTAINERS_USING_PORT" | while read -r container; do
        echo "   - Stopping: $container"
        docker stop "$container" 2>/dev/null || true
    done

    # Remove stopped containers
    docker ps -a --filter "publish=80" --format "{{.Names}}" | while read -r container; do
        echo "   - Removing: $container"
        docker rm "$container" 2>/dev/null || true
    done
else
    echo "ℹ️  No Docker containers found using port 80"
fi

# Stop inspirtag containers if project directory exists
PROJECT_DIR="/var/www/inspirtag"
if [ -d "$PROJECT_DIR" ]; then
    echo ""
    echo "📁 Found project directory: $PROJECT_DIR"
    cd "$PROJECT_DIR"

    if [ -f "docker-compose.yml" ]; then
        echo "   Stopping existing containers..."
        docker-compose down 2>/dev/null || true
        echo "   ✅ Existing containers stopped"
    fi
else
    echo ""
    echo "ℹ️  Project directory not found at $PROJECT_DIR"
    echo "   If your project is elsewhere, navigate to it and run: docker-compose down"
fi

echo ""
echo "🔍 Final verification..."
echo ""

# Final check
if command -v lsof &> /dev/null; then
    if sudo lsof -i :80 2>/dev/null | grep -q LISTEN; then
        echo "⚠️  WARNING: Port 80 is still in use!"
        echo "   Run this to see what's using it:"
        echo "   sudo lsof -i :80"
    else
        echo "✅ Port 80 is now free!"
    fi
elif command -v netstat &> /dev/null; then
    if sudo netstat -tulpn 2>/dev/null | grep -q ":80 "; then
        echo "⚠️  WARNING: Port 80 is still in use!"
        echo "   Run this to see what's using it:"
        echo "   sudo netstat -tulpn | grep :80"
    else
        echo "✅ Port 80 is now free!"
    fi
else
    echo "ℹ️  Could not verify port 80 status (no checking tool available)"
fi

echo ""
echo "🚀 Next steps:"
echo "   1. Navigate to your project directory"
echo "   2. Run: docker-compose up -d"
echo ""
echo "   Or if you're already in the project directory:"
echo "   docker-compose up -d"

