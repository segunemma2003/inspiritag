#!/bin/bash
# fix-https-now.sh
# Quick fix to enable HTTPS - run this on the server

set -e

echo "🔧 Quick HTTPS Fix for api.inspirtag.com"
echo "========================================"
echo ""

# Install nginx if not installed
if ! command -v nginx &> /dev/null; then
    echo "1️⃣ Installing system nginx..."
    sudo apt update -qq
    sudo apt install -y nginx
    echo "✅ Nginx installed"
else
    echo "✅ Nginx is already installed"
fi
echo ""

# Ensure Docker nginx is on port 8080
echo "2️⃣ Checking Docker nginx port..."
cd /var/www/inspirtag 2>/dev/null || cd ~/inspirtag 2>/dev/null || { echo "❌ Cannot find project directory"; exit 1; }

if grep -q '"80:80"' docker-compose.yml; then
    echo "   Updating docker-compose.yml to use port 8080..."
    sed -i 's/"80:80"/"8080:80"/' docker-compose.yml
    docker-compose -p inspirtag restart nginx 2>/dev/null || docker-compose restart nginx 2>/dev/null
    sleep 5
    echo "✅ Docker nginx now on port 8080"
else
    echo "✅ Docker nginx already on port 8080"
fi
echo ""

# Create nginx config
echo "3️⃣ Creating nginx configuration..."
NGINX_CONFIG="/etc/nginx/conf.d/api.inspirtag.com.conf"

sudo tee "$NGINX_CONFIG" > /dev/null << 'EOF'
# HTTP - redirect to HTTPS
server {
    listen 80;
    server_name api.inspirtag.com;

    location /.well-known/acme-challenge/ {
        root /var/www/html;
    }

    location / {
        return 301 https://$server_name$request_uri;
    }
}

# HTTPS - proxy to Docker nginx
server {
    listen 443 ssl http2;
    server_name api.inspirtag.com;

    ssl_certificate /etc/letsencrypt/live/api.inspirtag.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.inspirtag.com/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;

    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_redirect off;
    }
}
EOF

echo "✅ Nginx config created"
echo ""

# Generate SSL certificate if it doesn't exist
if [ ! -f "/etc/letsencrypt/live/api.inspirtag.com/fullchain.pem" ]; then
    echo "4️⃣ Generating SSL certificate..."

    # Install certbot if needed
    if ! command -v certbot &> /dev/null; then
        sudo apt install -y certbot
    fi

    # Stop services on port 80
    echo "   Stopping services on port 80..."
    docker-compose -p inspirtag stop nginx 2>/dev/null || docker-compose stop nginx 2>/dev/null || true
    sudo systemctl stop nginx 2>/dev/null || true
    sudo pkill -f certbot 2>/dev/null || true
    sleep 2

    # Generate certificate
    sudo certbot certonly --standalone \
        -d api.inspirtag.com \
        --email admin@inspirtag.com \
        --agree-tos \
        --non-interactive || {
        echo "⚠️ SSL certificate generation failed"
        echo "   You may need to run this manually"
    }

    # Restart services
    docker-compose -p inspirtag start nginx 2>/dev/null || docker-compose start nginx 2>/dev/null || true
else
    echo "✅ SSL certificate already exists"
fi
echo ""

# Test nginx config
echo "5️⃣ Testing nginx configuration..."
if sudo nginx -t; then
    echo "✅ Nginx configuration is valid"
else
    echo "❌ Nginx configuration has errors"
    exit 1
fi
echo ""

# Start/restart nginx
echo "6️⃣ Starting system nginx..."
sudo systemctl enable nginx
sudo systemctl restart nginx
sleep 3

if systemctl is-active --quiet nginx; then
    echo "✅ System nginx is running"
else
    echo "❌ System nginx failed to start"
    sudo systemctl status nginx --no-pager -l | head -10
    exit 1
fi
echo ""

# Test connectivity
echo "7️⃣ Testing connectivity..."
echo "   HTTP (should redirect):"
curl -s -o /dev/null -w "   Status: %{http_code}\n" http://api.inspirtag.com/health 2>&1 || echo "   ⚠️ HTTP test failed"

echo "   HTTPS:"
HTTPS_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 https://api.inspirtag.com/health 2>&1)
if [ "$HTTPS_STATUS" = "200" ] || [ "$HTTPS_STATUS" = "301" ] || [ "$HTTPS_STATUS" = "302" ]; then
    echo "   ✅ HTTPS is working! Status: $HTTPS_STATUS"
else
    echo "   ⚠️ HTTPS test returned: $HTTPS_STATUS"
    echo "   Checking ports..."
    sudo netstat -tuln | grep -E ":80|:443" || sudo ss -tuln | grep -E ":80|:443"
fi
echo ""

echo "✅ HTTPS fix completed!"
echo ""
echo "📋 Summary:"
echo "   System nginx: $(systemctl is-active --quiet nginx && echo '✅ Running' || echo '❌ Not running')"
echo "   SSL certificate: $([ -f /etc/letsencrypt/live/api.inspirtag.com/fullchain.pem ] && echo '✅ Exists' || echo '❌ Missing')"
echo "   Port 80: $(sudo netstat -tuln 2>/dev/null | grep -q ':80 ' && echo '✅ Listening' || echo '❌ Not listening')"
echo "   Port 443: $(sudo netstat -tuln 2>/dev/null | grep -q ':443 ' && echo '✅ Listening' || echo '❌ Not listening')"
echo "   Port 8080: $(sudo netstat -tuln 2>/dev/null | grep -q ':8080 ' && echo '✅ Listening' || echo '❌ Not listening')"

