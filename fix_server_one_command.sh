#!/bin/bash

# One-command server fix
# Copy this entire command and paste it after SSH'ing to your server

cd /var/www/html/social-media 2>/dev/null || cd /var/www/social-media 2>/dev/null || cd /root/social-media 2>/dev/null || cd ~/social-media 2>/dev/null; pwd; echo ""; echo "Stopping containers..."; docker-compose down 2>&1 | head -5; echo ""; echo "Starting containers..."; docker-compose up -d; echo ""; echo "Waiting 15 seconds..."; sleep 15; echo ""; echo "Container status:"; docker ps --format "table {{.Names}}\t{{.Status}}"; echo ""; APP=$(docker ps --format "{{.Names}}" | grep -i "inspirtag" | head -1); echo "Using container: $APP"; echo ""; echo "Running migrations..."; docker exec $APP php artisan migrate --force 2>&1 | tail -10; echo ""; echo "Clearing caches..."; docker exec $APP php artisan config:clear; docker exec $APP php artisan cache:clear; echo ""; echo "Fixing permissions..."; docker exec $APP chown -R www-data:www-data /var/www/html/storage 2>/dev/null; docker exec $APP chmod -R 775 /var/www/html/storage 2>/dev/null; echo ""; echo "Testing API..."; curl -s http://localhost/api/health; echo ""; echo ""; echo "DONE! Test from outside: curl http://38.180.244.178/api/health"

