#!/bin/bash
# Emergency fix for 502 error caused by MySQL connection failure
# Run this on your VPS: bash emergency_fix_502.sh

set -e

echo "🚨 EMERGENCY 502 FIX - Starting..."
echo "=================================="

cd /var/www/inspirtag

# Solution 1: Try to find Docker bridge IP and use it
echo ""
echo "🔍 Finding Docker bridge IP..."
DOCKER_BRIDGE_IP=$(ip addr show docker0 2>/dev/null | grep 'inet ' | awk '{print $2}' | cut -d/ -f1 || echo "172.17.0.1")
echo "Docker bridge IP: $DOCKER_BRIDGE_IP"

# Update .env to use Docker bridge IP instead of host.docker.internal
echo ""
echo "🔧 Updating .env to use Docker bridge IP..."
cp .env .env.backup-$(date +%Y%m%d-%H%M%S)

# Check if DB_HOST exists, if yes replace it, if not add it
if grep -q "^DB_HOST=" .env; then
    sed -i "s|^DB_HOST=.*|DB_HOST=$DOCKER_BRIDGE_IP|" .env
else
    echo "DB_HOST=$DOCKER_BRIDGE_IP" >> .env
fi

echo "Updated DB_HOST to $DOCKER_BRIDGE_IP"

# Solution 2: Reduce entrypoint.sh wait time from 30 attempts to 3
echo ""
echo "🔧 Reducing MySQL wait time in entrypoint..."
if [ -f docker/entrypoint.sh ]; then
    sed -i 's/max_attempts=30/max_attempts=3/' docker/entrypoint.sh
    echo "Reduced wait attempts from 30 to 3"
fi

# Solution 3: Make sure MySQL is running and accessible
echo ""
echo "🔍 Checking MySQL service..."
if systemctl is-active --quiet mysql 2>/dev/null; then
    echo "✅ MySQL service is running"
elif systemctl is-active --quiet mariadb 2>/dev/null; then
    echo "✅ MariaDB service is running"
else
    echo "⚠️ MySQL is not running. Attempting to start..."
    sudo systemctl start mysql 2>/dev/null || sudo systemctl start mariadb 2>/dev/null || echo "Could not start MySQL"
fi

# Check MySQL is listening
if netstat -tulpn 2>/dev/null | grep -q ':3306'; then
    echo "✅ MySQL is listening on port 3306"
else
    echo "❌ MySQL is NOT listening on port 3306"
    echo "⚠️ You may need to configure MySQL to bind to 0.0.0.0"
fi

# Solution 4: Stop and restart all containers
echo ""
echo "🛑 Stopping all containers..."
docker-compose down

echo ""
echo "🚀 Starting containers..."
docker-compose up -d

echo ""
echo "⏳ Waiting for containers to be ready (30 seconds)..."
sleep 30

# Check container status
echo ""
echo "📊 Container status:"
docker-compose ps

# Check app logs
echo ""
echo "📋 Recent app logs:"
docker-compose logs app --tail 30

# Test the API
echo ""
echo "🧪 Testing API health endpoint..."
if curl -f http://localhost/api/health 2>/dev/null; then
    echo ""
    echo "✅ SUCCESS! API is responding!"
else
    echo ""
    echo "⚠️ API health check failed"
    echo ""
    echo "Let's check if PHP-FPM is running..."
    docker-compose exec app ps aux | grep php-fpm | head -5
    
    echo ""
    echo "Checking nginx status..."
    docker-compose ps nginx
fi

echo ""
echo "=================================="
echo "🎯 NEXT STEPS:"
echo "=================================="
echo ""
echo "1. Check if the site is working:"
echo "   curl http://localhost/api/health"
echo ""
echo "2. If still not working, check logs:"
echo "   docker-compose logs app --tail 100"
echo ""
echo "3. If you see MySQL connection errors still:"
echo "   - Run: bash fix_mysql_connection.sh"
echo "   - Follow Solution 1 to configure MySQL bind-address"
echo ""
echo "4. Test from outside:"
echo "   curl http://YOUR_SERVER_IP/api/health"
echo ""
echo "Your .env has been backed up to .env.backup-$(date +%Y%m%d-%H%M%S)"
echo ""

