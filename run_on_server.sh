#!/bin/bash
# This script should be run ON the server (after SSH)
# Based on your actual docker-compose.yml configuration

echo "=========================================="
echo "🔍 Inspirtag Server Fix Script"
echo "=========================================="
echo ""

# Find project directory with docker-compose.yml
echo "📁 Finding project directory..."
for dir in /var/www/html /var/www /root ~/social-media; do
    if [ -f "$dir/docker-compose.yml" ]; then
        PROJECT_DIR="$dir"
        break
    fi
done

if [ -z "$PROJECT_DIR" ]; then
    echo "❌ Cannot find docker-compose.yml"
    echo "Current directory: $(pwd)"
    echo "Please cd to your project directory first"
    exit 1
fi

cd "$PROJECT_DIR"
echo "✅ Found project: $PROJECT_DIR"
echo ""

# Check current status
echo "📊 Current container status:"
docker ps -a --format "table {{.Names}}\t{{.Status}}" | grep inspirtag || echo "No containers found"
echo ""

# Stop containers
echo "🛑 Stopping containers..."
docker-compose down
echo ""

# Start containers (entrypoint.sh will run automatically)
echo "🚀 Starting containers..."
docker-compose up -d
echo ""

# Wait for startup
echo "⏳ Waiting 20 seconds for containers to start..."
sleep 20
echo ""

# Show status
echo "📊 New container status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep inspirtag
echo ""

# Check if all 5 containers are running
RUNNING=$(docker ps | grep -c "inspirtag" || echo "0")
echo "Running containers: $RUNNING/5"
echo ""

# Check API logs
echo "📄 API Container Logs (last 30 lines):"
docker logs inspirtag-api --tail 30
echo ""

# Check for errors
ERRORS=$(docker logs inspirtag-api 2>&1 | grep -i "error\|fatal\|failed" | tail -5)
if [ ! -z "$ERRORS" ]; then
    echo "⚠️  Found errors in logs:"
    echo "$ERRORS"
    echo ""
fi

# Test API
echo "🏥 Testing API health..."
HEALTH=$(curl -s http://localhost/api/health 2>&1)
if echo "$HEALTH" | grep -q "healthy"; then
    echo "✅ API is healthy!"
    echo "$HEALTH"
else
    echo "❌ API not responding correctly"
    echo "Response: $HEALTH"
    echo ""
    echo "Trying manual fixes..."

    # Clear caches
    docker exec inspirtag-api php artisan config:clear
    docker exec inspirtag-api php artisan cache:clear

    # Run migrations
    docker exec inspirtag-api php artisan migrate --force

    # Test again
    sleep 5
    HEALTH2=$(curl -s http://localhost/api/health 2>&1)
    echo "Retry result: $HEALTH2"
fi

echo ""
echo "=========================================="
echo "✅ Setup Complete!"
echo "=========================================="
echo ""
echo "Container Status:"
docker ps --format "{{.Names}}: {{.Status}}" | grep inspirtag
echo ""
echo "Test from outside:"
echo "  curl http://38.180.244.178/api/health"
echo ""
echo "Test OTP registration:"
echo "  curl -X POST http://38.180.244.178/api/register \\"
echo "    -H 'Content-Type: application/json' \\"
echo "    -d '{\"full_name\":\"Test User\",\"email\":\"test@example.com\",\"username\":\"testuser\",\"password\":\"Password123!\",\"password_confirmation\":\"Password123!\",\"terms_accepted\":true}'"
echo ""

