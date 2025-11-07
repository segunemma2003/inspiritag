#!/bin/bash
# diagnose-api.sh
# Diagnose why api.inspirtag.com is not working

echo "🔍 Diagnosing API Issues for api.inspirtag.com"
echo "=============================================="
echo ""

# Check DNS
echo "1️⃣ Checking DNS..."
DNS_IP=$(dig +short api.inspirtag.com)
if [ "$DNS_IP" = "38.180.244.178" ]; then
    echo "   ✅ DNS is correct: api.inspirtag.com → $DNS_IP"
else
    echo "   ❌ DNS issue: api.inspirtag.com → $DNS_IP (expected: 38.180.244.178)"
fi
echo ""

# Check Docker containers
echo "2️⃣ Checking Docker containers..."
cd /var/www/inspirtag 2>/dev/null || { echo "   ❌ Project directory not found"; exit 1; }

echo "   Container status:"
docker-compose ps

echo ""
echo "   Checking if containers are running..."
if docker-compose ps | grep -q "Up"; then
    echo "   ✅ Some containers are running"
else
    echo "   ❌ No containers are running!"
    echo "   Attempting to start..."
    docker-compose up -d
    sleep 10
fi
echo ""

# Check port 8080
echo "3️⃣ Checking port 8080..."
if netstat -tuln 2>/dev/null | grep -q ":8080 " || ss -tuln 2>/dev/null | grep -q ":8080 "; then
    echo "   ✅ Port 8080 is listening"
    netstat -tuln 2>/dev/null | grep ":8080 " || ss -tuln 2>/dev/null | grep ":8080 "
else
    echo "   ❌ Port 8080 is NOT listening"
    echo "   Docker nginx may not be running"
fi
echo ""

# Test Docker nginx directly
echo "4️⃣ Testing Docker nginx on port 8080..."
if curl -f http://localhost:8080/health > /dev/null 2>&1; then
    echo "   ✅ Docker nginx /health working"
    curl -s http://localhost:8080/health
else
    echo "   ❌ Docker nginx /health failed"
    echo "   Testing /api/health..."
    if curl -f http://localhost:8080/api/health > /dev/null 2>&1; then
        echo "   ✅ Docker nginx /api/health working"
        curl -s http://localhost:8080/api/health
    else
        echo "   ❌ Docker nginx /api/health also failed"
        echo "   Nginx logs:"
        docker-compose logs --tail=20 nginx
    fi
fi
echo ""

# Check system nginx
echo "5️⃣ Checking system nginx..."
if command -v nginx &> /dev/null; then
    echo "   ✅ Nginx is installed"
    if systemctl is-active --quiet nginx 2>/dev/null; then
        echo "   ✅ System nginx is running"
        sudo nginx -t 2>&1 | head -3
    else
        echo "   ⚠️ System nginx is NOT running"
        if systemctl list-units --type=service | grep -q nginx.service; then
            echo "   Attempting to start system nginx..."
            sudo systemctl start nginx 2>/dev/null || echo "   Failed to start"
        else
            echo "   System nginx service not found (may not be installed as service)"
        fi
    fi
else
    echo "   ⚠️ Nginx command not found"
fi
echo ""

# Check nginx config
echo "6️⃣ Checking nginx configuration..."
if [ -f "/etc/nginx/conf.d/api.inspirtag.com.conf" ]; then
    echo "   ✅ Found: /etc/nginx/conf.d/api.inspirtag.com.conf"
elif [ -f "/etc/nginx/sites-available/api.inspirtag.com.conf" ]; then
    echo "   ✅ Found: /etc/nginx/sites-available/api.inspirtag.com.conf"
    if [ -L "/etc/nginx/sites-enabled/api.inspirtag.com.conf" ]; then
        echo "   ✅ Symlink exists"
    else
        echo "   ⚠️ Symlink missing"
    fi
else
    echo "   ❌ Nginx config file not found"
fi
echo ""

# Check SSL
echo "7️⃣ Checking SSL certificate..."
if [ -f "/etc/letsencrypt/live/api.inspirtag.com/fullchain.pem" ]; then
    echo "   ✅ SSL certificate exists"
else
    echo "   ❌ SSL certificate NOT found"
    echo "   Run: ./generate-ssl-cert.sh"
fi
echo ""

# Test external access
echo "8️⃣ Testing external access..."
echo "   Testing http://api.inspirtag.com/health..."
HTTP_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://api.inspirtag.com/health 2>/dev/null)
if [ "$HTTP_RESPONSE" = "200" ] || [ "$HTTP_RESPONSE" = "301" ] || [ "$HTTP_RESPONSE" = "302" ]; then
    echo "   ✅ HTTP is accessible (status: $HTTP_RESPONSE)"
else
    echo "   ❌ HTTP not accessible (status: $HTTP_RESPONSE)"
fi

echo "   Testing http://api.inspirtag.com/api/health..."
API_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://api.inspirtag.com/api/health 2>/dev/null)
if [ "$API_RESPONSE" = "200" ]; then
    echo "   ✅ API endpoint is accessible (status: $API_RESPONSE)"
    curl -s http://api.inspirtag.com/api/health | head -1
else
    echo "   ❌ API endpoint not accessible (status: $API_RESPONSE)"
fi

echo ""
echo "   Testing https://api.inspirtag.com/api/health..."
HTTPS_RESPONSE=$(curl -s -k -o /dev/null -w "%{http_code}" https://api.inspirtag.com/api/health 2>/dev/null)
if [ "$HTTPS_RESPONSE" = "200" ]; then
    echo "   ✅ HTTPS is accessible (status: $HTTPS_RESPONSE)"
elif [ "$HTTPS_RESPONSE" = "000" ]; then
    echo "   ❌ HTTPS connection failed (SSL not configured or port 443 not open)"
else
    echo "   ⚠️ HTTPS returned status: $HTTPS_RESPONSE"
fi
echo ""

# Summary
echo "📋 Summary:"
echo "   DNS: $([ "$DNS_IP" = "38.180.244.178" ] && echo "✅ Correct" || echo "❌ Wrong")"
echo "   Docker containers: $(docker-compose ps | grep -q 'Up' && echo "✅ Running" || echo "❌ Not running")"
echo "   Port 8080: $(netstat -tuln 2>/dev/null | grep -q ':8080 ' && echo "✅ Listening" || echo "❌ Not listening")"
echo "   System nginx: $(systemctl is-active --quiet nginx 2>/dev/null && echo "✅ Running" || echo "⚠️ Not running")"
echo "   SSL certificate: $([ -f "/etc/letsencrypt/live/api.inspirtag.com/fullchain.pem" ] && echo "✅ Exists" || echo "❌ Missing")"
echo "   HTTP access: $([ "$HTTP_RESPONSE" = "200" ] || [ "$HTTP_RESPONSE" = "301" ] || [ "$HTTP_RESPONSE" = "302" ] && echo "✅ Working" || echo "❌ Not working")"
echo "   HTTPS access: $([ "$HTTPS_RESPONSE" = "200" ] && echo "✅ Working" || echo "❌ Not working")"
echo ""
echo "🔧 Quick fixes:"
echo "   1. If containers not running: docker-compose up -d"
echo "   2. If SSL missing: ./generate-ssl-cert.sh"
echo "   3. If system nginx not running: sudo systemctl start nginx"
echo "   4. Check logs: docker-compose logs"

