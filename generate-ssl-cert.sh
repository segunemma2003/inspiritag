#!/bin/bash
# generate-ssl-cert.sh
# Generate SSL certificate for api.inspirtag.com

set -e

DOMAIN="api.inspirtag.com"
EMAIL="admin@inspirtag.com"
PROJECT_DIR="/var/www/inspirtag"

echo "🔐 Generating SSL certificate for $DOMAIN"
echo ""

# Check if certificate already exists
if [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    echo "✅ SSL certificate already exists"
    exit 0
fi

# Install certbot if not installed
if ! command -v certbot &> /dev/null; then
    echo "📦 Installing certbot..."
    sudo apt update -qq
    sudo apt install -y certbot
fi

# Navigate to project directory
cd "$PROJECT_DIR" || exit 1

echo "🛑 Stopping services on port 80..."

# Stop Docker nginx
echo "   Stopping Docker nginx..."
docker-compose stop nginx 2>/dev/null || true

# Stop system nginx if running
if systemctl is-active --quiet nginx 2>/dev/null; then
    echo "   Stopping system nginx..."
    sudo systemctl stop nginx
fi

# Find and stop any process using port 80
echo "   Checking for other processes on port 80..."
PORT_80_PID=$(sudo lsof -ti:80 2>/dev/null || echo "")
if [ -n "$PORT_80_PID" ]; then
    echo "   Found process $PORT_80_PID using port 80"
    echo "   Stopping process..."
    sudo kill -9 $PORT_80_PID 2>/dev/null || true
    sleep 2
fi

# Double check port 80 is free
if sudo lsof -ti:80 > /dev/null 2>&1; then
    echo "   ⚠️ Port 80 is still in use:"
    sudo lsof -i:80 || sudo netstat -tulpn | grep :80
    echo ""
    read -p "   Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "   Aborted"
        exit 1
    fi
else
    echo "   ✅ Port 80 is free"
fi

echo ""
echo "🔐 Generating SSL certificate..."
sudo certbot certonly --standalone \
    -d "$DOMAIN" \
    --email "$EMAIL" \
    --agree-tos \
    --non-interactive

if [ $? -eq 0 ]; then
    echo "✅ SSL certificate generated successfully"
    echo ""
    echo "📍 Certificate location:"
    echo "   /etc/letsencrypt/live/$DOMAIN/fullchain.pem"
    echo "   /etc/letsencrypt/live/$DOMAIN/privkey.pem"
else
    echo "❌ SSL certificate generation failed"
    exit 1
fi

echo ""
echo "🔄 Restarting Docker nginx..."
docker-compose start nginx 2>/dev/null || docker-compose up -d nginx 2>/dev/null || true

# Start system nginx if it was running
if systemctl list-units --type=service | grep -q nginx.service; then
    echo "🔄 Starting system nginx..."
    sudo systemctl start nginx 2>/dev/null || true
fi

echo ""
echo "✅ SSL certificate setup complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Ensure system nginx is configured to use the certificate"
echo "   2. Test: curl -k https://$DOMAIN/api/health"
echo "   3. Update App Store Connect webhook URL"

