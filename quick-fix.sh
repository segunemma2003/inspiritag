#!/bin/bash

echo "🚀 Quick Fix for Inspirtag API"
echo "=============================="

# Navigate to project directory
cd /var/www/your-project

echo "1. Stopping all containers..."
docker-compose down --remove-orphans

echo "2. Removing any orphaned containers..."
docker rm your-project-nginx 2>/dev/null || echo "No orphaned nginx container"

echo "3. Rebuilding containers..."
docker-compose build --no-cache

echo "4. Starting containers..."
docker-compose up -d

echo "5. Waiting for services..."
sleep 20

echo "6. Checking container status..."
docker-compose ps

echo "7. Testing health endpoint..."
curl -f http://localhost/health && echo "✅ Health endpoint working" || echo "❌ Health endpoint failed"

echo "8. Testing API endpoint..."
curl -f http://localhost/api/categories && echo "✅ API endpoint working" || echo "❌ API endpoint failed"

echo "9. If still failing, checking logs..."
if ! curl -f http://localhost/health > /dev/null 2>&1; then
  echo "📊 App container logs:"
  docker-compose logs app --tail=20
fi

echo "🎉 Quick fix completed!"
echo "🌐 Test your API at: http://[SERVER_IP]/api/categories"
