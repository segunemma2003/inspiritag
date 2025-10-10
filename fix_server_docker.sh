#!/bin/bash
# fix_server_docker.sh - Script to fix Docker setup on server

echo "🔧 Fixing Docker Setup on Server"
echo "================================="

# Navigate to project directory
cd /var/www/inspirtag

# Stop all containers
echo "🛑 Stopping all containers..."
docker-compose down --remove-orphans

# Clean up Docker system
echo "🧹 Cleaning up Docker system..."
docker system prune -f
docker volume prune -f

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "❌ .env file not found!"
    echo "Please create .env file with proper configuration"
    exit 1
fi

# Rebuild containers
echo "🔨 Rebuilding containers..."
docker-compose build --no-cache

# Start services in correct order
echo "🚀 Starting services..."
docker-compose up -d mysql redis

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
sleep 30

# Start app container
echo "🚀 Starting app container..."
docker-compose up -d app

# Wait for app to be ready
echo "⏳ Waiting for app to be ready..."
sleep 20

# Start nginx
echo "🚀 Starting nginx..."
docker-compose up -d nginx

# Check container status
echo "📊 Container status:"
docker-compose ps

# Test health endpoint
echo "🏥 Testing health endpoint..."
for i in {1..10}; do
    if curl -f http://localhost/health > /dev/null 2>&1; then
        echo "✅ Health endpoint is responding"
        break
    else
        echo "⏳ Attempt $i/10: Health endpoint not ready yet..."
        sleep 5
    fi
done

# Test API endpoint
echo "🧪 Testing API endpoint..."
if curl -f http://localhost/api/categories > /dev/null 2>&1; then
    echo "✅ API endpoint is responding"
else
    echo "❌ API endpoint not responding"
    echo "📋 Container logs:"
    docker-compose logs app
fi

echo "🎉 Docker setup fix complete!"
