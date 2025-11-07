#!/bin/bash
# quick-fix-api.sh
# Quick fix to get API working immediately

set -e

PROJECT_DIR="/var/www/inspirtag"
cd "$PROJECT_DIR" || { echo "❌ Project directory not found: $PROJECT_DIR"; exit 1; }

echo "🚀 Quick Fix - Getting API Online"
echo "=================================="
echo ""

# Check Docker
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running"
    echo "   Starting Docker..."
    sudo systemctl start docker
    sleep 5
fi

echo "1️⃣ Checking Docker containers..."
docker-compose ps

echo ""
echo "2️⃣ Starting all containers..."
docker-compose up -d --force-recreate

echo ""
echo "3️⃣ Waiting for containers to start..."
sleep 15

echo ""
echo "4️⃣ Checking container status..."
docker-compose ps

echo ""
echo "5️⃣ Testing API..."

# Check which port Docker nginx is using
if docker-compose ps nginx | grep -q "0.0.0.0:80->80"; then
    TEST_PORT=80
elif docker-compose ps nginx | grep -q "0.0.0.0:8080->80"; then
    TEST_PORT=8080
else
    TEST_PORT=8080
fi

echo "   Testing port $TEST_PORT..."
if curl -f http://localhost:$TEST_PORT/api/health > /dev/null 2>&1; then
    echo "   ✅ API is working on port $TEST_PORT"
    curl -s http://localhost:$TEST_PORT/api/health
else
    echo "   ❌ API test failed"
    echo "   Container logs:"
    docker-compose logs --tail=20 nginx
    docker-compose logs --tail=20 app
fi

echo ""
echo "6️⃣ Checking external access..."
if curl -f http://api.inspirtag.com/api/health > /dev/null 2>&1; then
    echo "   ✅ API is accessible externally"
else
    echo "   ⚠️ API not accessible externally"
    echo "   This might be because:"
    echo "   - System nginx is not running/configured"
    echo "   - Firewall is blocking ports"
    echo "   - DNS is not fully propagated"
fi

echo ""
echo "📋 Summary:"
echo "   Docker containers: $(docker-compose ps | grep -q 'Up' && echo '✅ Running' || echo '❌ Not running')"
echo "   Port $TEST_PORT: $(netstat -tuln 2>/dev/null | grep -q ":$TEST_PORT " && echo '✅ Listening' || echo '❌ Not listening')"
echo "   Local API: $(curl -f http://localhost:$TEST_PORT/api/health > /dev/null 2>&1 && echo '✅ Working' || echo '❌ Not working')"
echo "   External API: $(curl -f http://api.inspirtag.com/api/health > /dev/null 2>&1 && echo '✅ Working' || echo '❌ Not working')"

