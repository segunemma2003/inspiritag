#!/bin/bash

# Subdomain Setup Script
# Usage: bash setup_subdomain.sh api.yourdomain.com your-email@example.com

set -e

if [ -z "$1" ] || [ -z "$2" ]; then
    echo "Usage: bash setup_subdomain.sh <subdomain> <email>"
    echo "Example: bash setup_subdomain.sh api.inspirtag.com admin@inspirtag.com"
    exit 1
fi

DOMAIN=$1
EMAIL=$2
PROJECT_DIR="/var/www/inspirtag"

echo "🚀 Setting up subdomain: $DOMAIN"
echo "📧 Email: $EMAIL"
echo ""

# Step 1: Install Certbot
echo "📦 Installing Certbot..."
if ! command -v certbot &> /dev/null; then
    sudo apt update
    sudo apt install certbot -y
else
    echo "✅ Certbot already installed"
fi

# Step 2: Check if domain resolves
echo ""
echo "🔍 Checking DNS resolution..."
if nslookup $DOMAIN | grep -q "38.180.244.178"; then
    echo "✅ DNS is configured correctly"
else
    echo "⚠️  WARNING: DNS may not be configured yet"
    echo "   Please add A record: $DOMAIN → 38.180.244.178"
    echo "   Waiting 30 seconds for DNS propagation..."
    sleep 30
fi

# Step 3: Generate SSL Certificate
echo ""
echo "🔐 Generating SSL certificate..."
sudo certbot certonly --standalone -d $DOMAIN --email $EMAIL --agree-tos --non-interactive || {
    echo "❌ Failed to generate certificate. Please check:"
    echo "   - DNS is pointing to this server"
    echo "   - Port 80 is open"
    echo "   - Domain is accessible"
    exit 1
}

echo "✅ SSL certificate generated"

# Step 4: Update Nginx config
echo ""
echo "📝 Updating Nginx configuration..."
cd $PROJECT_DIR

# Backup existing config
cp docker/nginx.conf docker/nginx.conf.backup

# Update server_name in nginx.conf
sed -i "s/server_name.*/server_name $DOMAIN 38.180.244.178;/" docker/nginx.conf

# Check if SSL config exists
if [ ! -f "docker/nginx-ssl.conf" ]; then
    echo "⚠️  SSL config not found. Please create docker/nginx-ssl.conf"
else
    # Update SSL config
    sed -i "s/server_name api.yourdomain.com/$DOMAIN/g" docker/nginx-ssl.conf
    sed -i "s/api.yourdomain.com/$DOMAIN/g" docker/nginx-ssl.conf
fi

# Step 5: Update .env
echo ""
echo "📝 Updating .env file..."
if grep -q "^APP_URL=" .env; then
    sed -i "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" .env
else
    echo "APP_URL=https://$DOMAIN" >> .env
fi
echo "✅ Updated APP_URL=https://$DOMAIN"

# Step 6: Update docker-compose.yml to mount SSL
echo ""
echo "📝 Checking docker-compose.yml..."
if ! grep -q "/etc/letsencrypt" docker-compose.yml; then
    echo "⚠️  Please update docker-compose.yml to mount SSL certificates:"
    echo "   Add to nginx volumes:"
    echo "   - /etc/letsencrypt:/etc/letsencrypt:ro"
fi

# Step 7: Restart services
echo ""
echo "🔄 Restarting services..."
docker-compose restart nginx || {
    echo "⚠️  Docker-compose restart failed. Try: docker-compose down && docker-compose up -d"
}

echo ""
echo "✅ Setup complete!"
echo ""
echo "📋 Next steps:"
echo "1. Update App Store Connect webhook URL to:"
echo "   https://$DOMAIN/api/webhooks/apple/subscription"
echo ""
echo "2. Test your API:"
echo "   curl https://$DOMAIN/health"
echo ""
echo "3. Set up auto-renewal for SSL:"
echo "   sudo crontab -e"
echo "   Add: 0 0,12 * * * certbot renew --quiet && cd $PROJECT_DIR && docker-compose restart nginx"
echo ""

