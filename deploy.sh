#!/bin/bash

# Manual Deployment Script for Inspirtag
# Run this script on your VPS for manual deployments

echo "🚀 Starting manual deployment..."

# Navigate to project directory
cd /var/www/your-project

# Pull latest changes
echo "🔄 Pulling latest changes from Git..."
git pull origin main

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "🐳 Starting Docker..."
    sudo systemctl start docker
    sleep 5
fi

# Build and start containers
echo "🔨 Building and starting containers..."
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Wait for services to be ready
echo "⏳ Waiting for services to start..."
sleep 15

# Run database migrations
echo "🗄️ Running database migrations..."
docker-compose exec -T app php artisan migrate --force

# Run seeders
echo "🌱 Running database seeders..."
docker-compose exec -T app php artisan db:seed --force

# Clear caches
echo "🧹 Clearing caches..."
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan route:clear
docker-compose exec -T app php artisan view:clear

# Warm up caches
echo "🔥 Warming up caches..."
docker-compose exec -T app php artisan cache:warm-up

# Restart queue workers
echo "🔄 Restarting queue workers..."
docker-compose exec -T app php artisan queue:restart

# Health check
echo "🏥 Performing health check..."
sleep 5

# Check if API is responding
if curl -f http://localhost:8000/api/categories > /dev/null 2>&1; then
    echo "✅ API is responding successfully"
else
    echo "❌ API health check failed"
    echo "📊 Container status:"
    docker-compose ps
    exit 1
fi

# Show container status
echo "📊 Container status:"
docker-compose ps

echo "🎉 Deployment completed successfully!"
echo "🌐 Your API is available at: http://[SERVER_IP]:8000"
