#!/bin/bash
# setup-nginx-reverse-proxy.sh
# Setup system nginx as reverse proxy for Docker nginx

set -e

DOMAIN="api.inspirtag.com"
EMAIL="admin@inspirtag.com"
PROJECT_DIR="/var/www/inspirtag"

echo "🔧 Setting up Nginx Reverse Proxy for $DOMAIN"
echo ""

# Step 1: Check if system nginx is installed
if ! command -v nginx &> /dev/null; then
    echo "❌ System nginx is not installed"
    echo "   Install it with: sudo apt install nginx"
    exit 1
fi

echo "✅ System nginx is installed"
echo ""

# Step 2: Update docker-compose.yml to use port 8080
echo "📝 Updating docker-compose.yml..."
cd "$PROJECT_DIR" || { echo "❌ Project directory not found: $PROJECT_DIR"; exit 1; }

# Backup docker-compose.yml
cp docker-compose.yml docker-compose.yml.backup

# Update nginx ports in docker-compose.yml
if grep -q '"80:80"' docker-compose.yml; then
    echo "   Changing nginx port from 80:80 to 8080:80"
    sed -i 's/"80:80"/"8080:80"/' docker-compose.yml
    sed -i 's/"443:443"/# "443:443"  # Removed - system nginx handles SSL/' docker-compose.yml
    echo "   ✅ docker-compose.yml updated"
else
    echo "   ℹ️  Ports may already be configured"
fi

# Update nginx config to use HTTP only
if [ -f "docker/nginx-ssl.conf" ] && [ -f "docker/nginx.conf" ]; then
    echo "   ✅ Using docker/nginx.conf (HTTP only)"
fi

echo ""

# Step 3: Generate SSL certificate (if not exists)
echo "🔐 Checking SSL certificate..."
if [ ! -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    echo "   SSL certificate not found. Generating..."

    # Stop nginx temporarily
    sudo systemctl stop nginx

    # Generate certificate
    sudo certbot certonly --standalone \
        -d "$DOMAIN" \
        --email "$EMAIL" \
        --agree-tos \
        --non-interactive || {
        echo "❌ Failed to generate certificate"
        echo "   Make sure DNS is pointing to this server"
        sudo systemctl start nginx
        exit 1
    }

    # Start nginx
    sudo systemctl start nginx
    echo "   ✅ SSL certificate generated"
else
    echo "   ✅ SSL certificate already exists"
fi

echo ""

# Step 4: Create system nginx configuration
echo "📝 Creating system nginx configuration..."
NGINX_CONFIG="/etc/nginx/sites-available/$DOMAIN"

if [ -f "$NGINX_CONFIG" ]; then
    echo "   ⚠️  Configuration already exists at $NGINX_CONFIG"
    read -p "   Overwrite? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "   Skipping configuration creation"
    else
        sudo cp system-nginx-api-inspirtag.conf "$NGINX_CONFIG"
        echo "   ✅ Configuration updated"
    fi
else
    sudo cp system-nginx-api-inspirtag.conf "$NGINX_CONFIG"
    echo "   ✅ Configuration created"
fi

echo ""

# Step 5: Enable site
echo "🔗 Enabling nginx site..."
if [ ! -L "/etc/nginx/sites-enabled/$DOMAIN" ]; then
    sudo ln -s "/etc/nginx/sites-available/$DOMAIN" "/etc/nginx/sites-enabled/"
    echo "   ✅ Site enabled"
else
    echo "   ℹ️  Site already enabled"
fi

echo ""

# Step 6: Test nginx configuration
echo "🧪 Testing nginx configuration..."
if sudo nginx -t; then
    echo "   ✅ Nginx configuration is valid"
else
    echo "   ❌ Nginx configuration has errors"
    exit 1
fi

echo ""

# Step 7: Restart services
echo "🔄 Restarting services..."

# Restart Docker containers
echo "   Restarting Docker containers..."
docker-compose down
docker-compose up -d

# Wait a moment for containers to start
sleep 5

# Reload system nginx
echo "   Reloading system nginx..."
sudo systemctl reload nginx

echo ""

# Step 8: Test
echo "🧪 Testing setup..."
sleep 2

# Test Docker nginx directly
if curl -s http://127.0.0.1:8080/health | grep -q "healthy"; then
    echo "   ✅ Docker nginx is accessible on port 8080"
else
    echo "   ⚠️  Docker nginx may not be ready yet"
fi

# Test through system nginx (if DNS is configured)
if curl -s -k https://$DOMAIN/health 2>/dev/null | grep -q "healthy"; then
    echo "   ✅ API is accessible via HTTPS"
else
    echo "   ℹ️  HTTPS test skipped (DNS may not be configured yet)"
fi

echo ""
echo "✅ Setup complete!"
echo ""
echo "📋 Summary:"
echo "   - Docker nginx: Running on port 8080"
echo "   - System nginx: Proxying $DOMAIN to Docker nginx"
echo "   - SSL: Handled by system nginx"
echo ""
echo "🔍 Next steps:"
echo "   1. Ensure DNS A record: $DOMAIN → $(hostname -I | awk '{print $1}')"
echo "   2. Test: curl https://$DOMAIN/health"
echo "   3. Update App Store Connect webhook: https://$DOMAIN/api/webhooks/apple/subscription"
echo ""
echo "📝 Configuration files:"
echo "   - System nginx: $NGINX_CONFIG"
echo "   - Docker compose: $PROJECT_DIR/docker-compose.yml"
echo "   - Backup: $PROJECT_DIR/docker-compose.yml.backup"

