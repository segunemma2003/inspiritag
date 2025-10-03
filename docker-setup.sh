#!/bin/bash

# Inspirtag API Docker Setup Script
# This script sets up the complete Docker environment for the Inspirtag API

echo "🚀 Setting up Inspirtag API with Docker..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

print_status "Docker and Docker Compose are installed ✓"

# Create necessary directories
print_status "Creating necessary directories..."
mkdir -p docker/ssl
mkdir -p storage/app/public
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set permissions
print_status "Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Copy environment file if it doesn't exist
if [ ! -f .env ]; then
    print_status "Creating .env file from example..."
    cp docker/env.example .env
    print_warning "Please update the .env file with your actual configuration values"
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" .env; then
    print_status "Generating application key..."
    docker run --rm -v $(pwd):/app -w /app php:8.3-cli php -r "echo 'APP_KEY=base64:' . base64_encode(random_bytes(32)) . PHP_EOL;" >> .env.tmp
    if [ -f .env.tmp ]; then
        cat .env.tmp >> .env
        rm .env.tmp
    fi
fi

# Build and start containers
print_status "Building Docker containers..."
docker-compose build

if [ $? -ne 0 ]; then
    print_error "Failed to build Docker containers"
    exit 1
fi

print_status "Starting Docker containers..."
docker-compose up -d

if [ $? -ne 0 ]; then
    print_error "Failed to start Docker containers"
    exit 1
fi

# Wait for services to be ready
print_status "Waiting for services to be ready..."
sleep 30

# Run Laravel setup commands
print_status "Running Laravel setup commands..."

# Install dependencies
print_status "Installing PHP dependencies..."
docker-compose exec app composer install --no-dev --optimize-autoloader

# Generate application key
print_status "Generating application key..."
docker-compose exec app php artisan key:generate

# Run migrations
print_status "Running database migrations..."
docker-compose exec app php artisan migrate --force

# Seed database
print_status "Seeding database..."
docker-compose exec app php artisan db:seed --force

# Create storage link
print_status "Creating storage link..."
docker-compose exec app php artisan storage:link

# Warm up cache
print_status "Warming up cache..."
docker-compose exec app php artisan cache:warm-up

# Set up performance monitoring
print_status "Setting up performance monitoring..."
docker-compose exec app php artisan performance:monitor

# Check if services are running
print_status "Checking service status..."

# Check if app is responding
if curl -s -f http://localhost:8000/health > /dev/null; then
    print_success "Application is running on http://localhost:8000"
else
    print_warning "Application might not be ready yet. Please wait a moment and try again."
fi

# Check database connection
if docker-compose exec app php artisan tinker --execute="DB::connection()->getPdo();" > /dev/null 2>&1; then
    print_success "Database connection is working"
else
    print_warning "Database connection might not be ready yet"
fi

# Check Redis connection
if docker-compose exec app php artisan tinker --execute="Cache::put('test', 'value');" > /dev/null 2>&1; then
    print_success "Redis cache is working"
else
    print_warning "Redis cache might not be ready yet"
fi

# Display useful information
echo ""
print_success "🎉 Inspirtag API Docker setup completed!"
echo ""
echo "📋 Useful Commands:"
echo "  View logs:           docker-compose logs -f"
echo "  Stop services:       docker-compose down"
echo "  Restart services:    docker-compose restart"
echo "  Access app shell:    docker-compose exec app bash"
echo "  Run migrations:      docker-compose exec app php artisan migrate"
echo "  Clear cache:         docker-compose exec app php artisan cache:clear"
echo "  Performance monitor: docker-compose exec app php artisan performance:dashboard"
echo ""
echo "🌐 Services:"
echo "  API:                 http://localhost:8000"
echo "  Database:            localhost:3306"
echo "  Redis:               localhost:6379"
echo ""
echo "📊 Performance Monitoring:"
echo "  Dashboard:           docker-compose exec app php artisan performance:dashboard"
echo "  Health Check:        docker-compose exec app php artisan system:health-check"
echo "  Cache Status:        docker-compose exec app php artisan cache:manage status"
echo "  Queue Status:        docker-compose exec app php artisan queue:manage status"
echo ""
print_warning "Don't forget to:"
echo "  1. Update your .env file with actual AWS credentials for S3"
echo "  2. Configure your Firebase credentials"
echo "  3. Set up SSL certificates in docker/ssl/ for production"
echo "  4. Configure your domain and CDN settings"
echo ""

print_success "Setup complete! 🚀"
