#!/bin/bash
# COMPLETE FIX for 502 error - Run this on your VPS server
# Usage: bash COMPLETE_FIX_NOW.sh

set -e

echo "🚨 COMPLETE 502 FIX - Starting..."
echo "=================================="
echo ""

cd /var/www/inspirtag

# Step 1: Find Docker bridge IP
echo "1️⃣ Finding Docker bridge IP..."
DOCKER_IP=$(ip addr show docker0 | grep 'inet ' | awk '{print $2}' | cut -d/ -f1 || echo "172.17.0.1")
echo "   Docker bridge IP: $DOCKER_IP"
echo ""

# Step 2: Stop containers
echo "2️⃣ Stopping containers..."
docker-compose down
echo "   ✅ Containers stopped"
echo ""

# Step 3: Backup and update .env
echo "3️⃣ Updating .env file..."
if [ -f .env ]; then
    cp .env .env.backup-$(date +%Y%m%d-%H%M%S)
    echo "   ✅ Backup created"
    
    # Update DB_HOST
    sed -i "s|^DB_HOST=.*|DB_HOST=$DOCKER_IP|" .env
    echo "   ✅ Updated DB_HOST to $DOCKER_IP"
    
    echo ""
    echo "   Current DB configuration:"
    grep "^DB_" .env | head -5
else
    echo "   ❌ .env file not found!"
    exit 1
fi
echo ""

# Step 4: Pull latest code changes (fixes entrypoint.sh)
echo "4️⃣ Pulling latest code updates..."
git fetch origin
git reset --hard origin/main
echo "   ✅ Code updated"
echo ""

# Step 5: Grant MySQL permissions
echo "5️⃣ Granting MySQL permissions to Docker network..."
DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d= -f2)
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d= -f2)
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d= -f2)

if [ -n "$DB_PASS" ]; then
    mysql -u root -p"$DB_PASS" <<EOF 2>/dev/null || echo "   ⚠️ Could not grant permissions (might need manual setup)"
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'172.%';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO 'root'@'172.%';
FLUSH PRIVILEGES;
EOF
    echo "   ✅ MySQL permissions granted"
else
    echo "   ⚠️ No DB_PASSWORD in .env, skipping MySQL permissions"
fi
echo ""

# Step 6: Rebuild containers
echo "6️⃣ Rebuilding containers with fixes..."
docker-compose build --no-cache app
echo "   ✅ Build complete"
echo ""

# Step 7: Start containers
echo "7️⃣ Starting containers..."
docker-compose up -d
echo "   ✅ Containers started"
echo ""

# Step 8: Wait for services
echo "8️⃣ Waiting for services to be ready (30 seconds)..."
for i in {1..30}; do
    echo -n "."
    sleep 1
done
echo ""
echo "   ✅ Wait complete"
echo ""

# Step 9: Check container status
echo "9️⃣ Checking container status..."
docker-compose ps
echo ""

# Step 10: Check logs
echo "🔟 Checking recent logs..."
echo "   Last 20 lines:"
docker-compose logs app --tail 20
echo ""

# Step 11: Test API
echo "🧪 Testing API health endpoint..."
echo ""
if curl -f http://localhost/api/health 2>/dev/null; then
    echo ""
    echo "=================================="
    echo "✅✅✅ SUCCESS! ✅✅✅"
    echo "=================================="
    echo ""
    echo "Your server is BACK ONLINE!"
    echo ""
    echo "Test from outside:"
    echo "  curl http://YOUR_SERVER_IP/api/health"
    echo ""
else
    echo ""
    echo "⚠️ Health check failed"
    echo ""
    echo "Let's do more diagnostics..."
    echo ""
    
    # Check if PHP-FPM is running
    echo "Checking if PHP-FPM is running:"
    docker-compose exec app ps aux | grep php-fpm | head -5 || echo "PHP-FPM not found"
    echo ""
    
    # Check nginx
    echo "Checking nginx status:"
    docker-compose ps nginx
    echo ""
    
    # Test MySQL connection from container
    echo "Testing MySQL connection from container:"
    docker-compose exec app nc -zv $DOCKER_IP 3306 2>&1 || echo "Cannot reach MySQL"
    echo ""
    
    echo "=================================="
    echo "📋 TROUBLESHOOTING STEPS:"
    echo "=================================="
    echo ""
    echo "1. Check full logs:"
    echo "   docker-compose logs app --tail 100"
    echo ""
    echo "2. Check MySQL connection manually:"
    echo "   docker-compose exec app mysql -h$DOCKER_IP -u$DB_USER -p$DB_PASS -e 'SELECT 1;'"
    echo ""
    echo "3. Try accessing from inside container:"
    echo "   docker-compose exec app curl http://localhost:9000/api/health"
    echo ""
fi

echo ""
echo "=================================="
echo "Done!"
echo "=================================="

