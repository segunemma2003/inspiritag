#!/bin/bash

echo "🔍 VPS Diagnostic Script"
echo "========================"

echo "1. Checking Docker status..."
docker --version
docker info

echo ""
echo "2. Checking running containers..."
docker ps -a

echo ""
echo "3. Checking docker-compose status..."
cd /var/www/your-project
docker-compose ps

echo ""
echo "4. Checking container logs..."
echo "App container logs:"
docker-compose logs app --tail=20

echo ""
echo "5. Checking if ports are listening..."
netstat -tlnp | grep :80
netstat -tlnp | grep :8000

echo ""
echo "6. Checking firewall status..."
ufw status || iptables -L || echo "No firewall found"

echo ""
echo "7. Testing local connectivity..."
curl -f http://localhost/health || echo "Health endpoint failed"
curl -f http://localhost/api/categories || echo "API endpoint failed"

echo ""
echo "8. Checking if containers are healthy..."
docker-compose exec -T app php artisan --version || echo "Laravel not accessible"

echo ""
echo "9. Checking disk space..."
df -h

echo ""
echo "10. Checking memory usage..."
free -h
