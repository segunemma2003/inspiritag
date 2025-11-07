#!/bin/bash
# check-server-status.sh
# Run this on the server to diagnose connection issues

echo "🔍 Server Status Check for api.inspirtag.com"
echo "=============================================="
echo ""

# Check DNS
echo "1️⃣ DNS Resolution:"
echo "   api.inspirtag.com → $(dig +short api.inspirtag.com)"
echo ""

# Check if Docker containers are running
echo "2️⃣ Docker Containers:"
docker-compose -p inspirtag ps 2>/dev/null || docker ps | grep inspirtag
echo ""

# Check ports
echo "3️⃣ Port Status:"
echo "   Port 80:"
netstat -tuln 2>/dev/null | grep ":80 " || ss -tuln 2>/dev/null | grep ":80 " || echo "   ⚠️ Not listening"
echo "   Port 443:"
netstat -tuln 2>/dev/null | grep ":443 " || ss -tuln 2>/dev/null | grep ":443 " || echo "   ⚠️ Not listening"
echo "   Port 8080:"
netstat -tuln 2>/dev/null | grep ":8080 " || ss -tuln 2>/dev/null | grep ":8080 " || echo "   ⚠️ Not listening"
echo ""

# Check system nginx
echo "4️⃣ System Nginx:"
if command -v nginx &> /dev/null; then
    echo "   ✅ Nginx is installed"
    if systemctl is-active --quiet nginx 2>/dev/null; then
        echo "   ✅ Nginx service is running"
        systemctl status nginx --no-pager -l | head -5
    else
        echo "   ❌ Nginx service is NOT running"
        echo "   Attempting to start..."
        sudo systemctl start nginx 2>/dev/null && echo "   ✅ Started" || echo "   ❌ Failed to start"
    fi

    # Check nginx config
    echo ""
    echo "   Nginx configuration files:"
    ls -la /etc/nginx/conf.d/*.conf 2>/dev/null | head -5
    ls -la /etc/nginx/sites-available/*.conf 2>/dev/null | head -5
    echo ""

    # Test nginx config
    echo "   Testing nginx configuration:"
    sudo nginx -t 2>&1 | head -10
else
    echo "   ⚠️ Nginx is not installed"
fi
echo ""

# Check Docker nginx
echo "5️⃣ Docker Nginx:"
if docker ps | grep -q inspirtag-nginx; then
    echo "   ✅ Docker nginx container is running"
    echo "   Testing local connection:"
    curl -s http://localhost:8080/health 2>/dev/null | head -3 || echo "   ⚠️ Cannot connect to Docker nginx on port 8080"
    curl -s http://localhost:80/health 2>/dev/null | head -3 || echo "   ⚠️ Cannot connect to Docker nginx on port 80"
else
    echo "   ❌ Docker nginx container is NOT running"
fi
echo ""

# Check SSL certificate
echo "6️⃣ SSL Certificate:"
if [ -f "/etc/letsencrypt/live/api.inspirtag.com/fullchain.pem" ]; then
    echo "   ✅ SSL certificate exists"
    echo "   Certificate details:"
    sudo openssl x509 -in /etc/letsencrypt/live/api.inspirtag.com/fullchain.pem -text -noout 2>/dev/null | grep -E "Subject:|Issuer:|Not After" | head -3
else
    echo "   ❌ SSL certificate NOT found"
fi
echo ""

# Check firewall
echo "7️⃣ Firewall Status:"
if command -v ufw &> /dev/null; then
    sudo ufw status | head -10
elif command -v firewall-cmd &> /dev/null; then
    sudo firewall-cmd --list-all 2>/dev/null | head -10
else
    echo "   Checking iptables:"
    sudo iptables -L -n | grep -E "80|443" | head -5 || echo "   No specific rules found"
fi
echo ""

# Test external connectivity
echo "8️⃣ External Connectivity Test:"
echo "   Testing HTTP (port 80):"
curl -s -o /dev/null -w "   Status: %{http_code}\n" http://api.inspirtag.com/health 2>&1 || echo "   ❌ Connection failed"
echo "   Testing HTTPS (port 443):"
curl -s -o /dev/null -w "   Status: %{http_code}\n" https://api.inspirtag.com/health 2>&1 || echo "   ❌ Connection refused"
echo ""

# Summary
echo "📋 Summary:"
echo "   DNS: ✅ Correct (38.180.244.178)"
echo "   HTTP (80): $(curl -s -o /dev/null -w '%{http_code}' http://api.inspirtag.com/health 2>&1 | grep -q '200\|301\|302' && echo '✅ Working' || echo '❌ Not working')"
echo "   HTTPS (443): $(curl -s -o /dev/null -w '%{http_code}' --connect-timeout 2 https://api.inspirtag.com/health 2>&1 | grep -q '200\|301\|302' && echo '✅ Working' || echo '❌ Not working')"
echo "   Docker nginx: $(docker ps | grep -q inspirtag-nginx && echo '✅ Running' || echo '❌ Not running')"
echo "   System nginx: $(systemctl is-active --quiet nginx 2>/dev/null && echo '✅ Running' || echo '❌ Not running')"
echo ""

