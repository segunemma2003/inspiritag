#!/bin/bash

echo "🔧 Fixing Deployment Issues"
echo "==========================="

# Navigate to project directory
cd /var/www/your-project

echo "1. Stopping all containers..."
docker-compose down --remove-orphans

echo "2. Removing orphaned containers..."
docker rm your-project-nginx 2>/dev/null || echo "No orphaned nginx container found"

echo "3. Rebuilding containers with new nginx config..."
docker-compose build --no-cache

echo "4. Starting containers..."
docker-compose up -d

echo "5. Waiting for services to start..."
sleep 15

echo "6. Checking container status..."
docker-compose ps

echo "7. Testing health endpoint..."
curl -f http://localhost/health && echo "✅ Health endpoint working" || echo "❌ Health endpoint failed"

echo "8. Testing API endpoint..."
curl -f http://localhost/api/categories && echo "✅ API endpoint working" || echo "❌ API endpoint failed"

echo "9. Checking app container logs..."
docker-compose logs app --tail=10

echo "🎉 Fix completed!"
