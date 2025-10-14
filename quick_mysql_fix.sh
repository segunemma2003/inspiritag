#!/bin/bash
# Quick MySQL connection fix for Docker containers
# Run this on your VPS

echo "🔍 Checking MySQL configuration..."

# Check what MySQL is binding to
echo ""
echo "1. Checking MySQL bind address:"
if grep -r "bind-address" /etc/mysql/ 2>/dev/null; then
    echo "Found bind-address configuration above ↑"
else
    echo "No bind-address found in config"
fi

echo ""
echo "2. Checking what port 3306 is listening on:"
netstat -tulpn | grep 3306

echo ""
echo "3. Finding Docker bridge IP:"
DOCKER_IP=$(ip addr show docker0 2>/dev/null | grep 'inet ' | awk '{print $2}' | cut -d/ -f1)
if [ -n "$DOCKER_IP" ]; then
    echo "Docker bridge IP: $DOCKER_IP"
else
    DOCKER_IP="172.17.0.1"
    echo "Using default Docker IP: $DOCKER_IP"
fi

echo ""
echo "4. Testing MySQL connection from container:"
if docker exec inspirtag-api mysqladmin ping -h"$DOCKER_IP" -u"root" -p"${DB_PASSWORD}" --silent 2>/dev/null; then
    echo "✅ Connection to $DOCKER_IP works!"
elif docker exec inspirtag-api mysqladmin ping -h"host.docker.internal" -u"root" -p"${DB_PASSWORD}" --silent 2>/dev/null; then
    echo "✅ Connection to host.docker.internal works!"
else
    echo "❌ Cannot connect to MySQL from container"
fi

echo ""
echo "================================"
echo "🔧 APPLYING FIX..."
echo "================================"

# Fix 1: Update MySQL to bind to all interfaces
echo ""
echo "Updating MySQL bind-address..."
MYSQL_CONF="/etc/mysql/mysql.conf.d/mysqld.cnf"

if [ -f "$MYSQL_CONF" ]; then
    # Backup original
    cp "$MYSQL_CONF" "$MYSQL_CONF.backup-$(date +%Y%m%d-%H%M%S)"

    # Update bind-address
    if grep -q "^bind-address" "$MYSQL_CONF"; then
        sed -i 's/^bind-address.*/bind-address = 0.0.0.0/' "$MYSQL_CONF"
        echo "✅ Updated bind-address to 0.0.0.0"
    else
        echo "bind-address = 0.0.0.0" >> "$MYSQL_CONF"
        echo "✅ Added bind-address = 0.0.0.0"
    fi

    echo "Restarting MySQL..."
    systemctl restart mysql
    sleep 3
    echo "✅ MySQL restarted"
else
    echo "⚠️ Could not find $MYSQL_CONF"
fi

# Fix 2: Update .env to use Docker bridge IP
echo ""
echo "Updating .env file..."
cd /var/www/inspirtag

if [ -f .env ]; then
    cp .env .env.backup-$(date +%Y%m%d-%H%M%S)

    # Update DB_HOST
    if grep -q "^DB_HOST=" .env; then
        sed -i "s|^DB_HOST=.*|DB_HOST=$DOCKER_IP|" .env
        echo "✅ Updated DB_HOST to $DOCKER_IP"
    else
        echo "DB_HOST=$DOCKER_IP" >> .env
        echo "✅ Added DB_HOST=$DOCKER_IP"
    fi
fi

# Fix 3: Grant permissions to Docker network
echo ""
echo "Granting MySQL permissions to Docker network..."
DB_NAME=$(grep "^DB_DATABASE=" .env | cut -d= -f2)
DB_USER=$(grep "^DB_USERNAME=" .env | cut -d= -f2)
DB_PASS=$(grep "^DB_PASSWORD=" .env | cut -d= -f2)

mysql -u root -p"$DB_PASS" <<EOF
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'172.%' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
FLUSH PRIVILEGES;
EOF

if [ $? -eq 0 ]; then
    echo "✅ MySQL permissions granted"
else
    echo "⚠️ Could not grant permissions (may need manual setup)"
fi

# Fix 4: Restart containers
echo ""
echo "Restarting Docker containers..."
docker-compose down
sleep 2
docker-compose up -d

echo ""
echo "Waiting for containers to start (30 seconds)..."
sleep 30

echo ""
echo "================================"
echo "🧪 TESTING..."
echo "================================"

echo ""
echo "Container status:"
docker-compose ps

echo ""
echo "Testing API health:"
if curl -f http://localhost/api/health 2>/dev/null; then
    echo ""
    echo "✅✅✅ SUCCESS! Server is back online! ✅✅✅"
else
    echo ""
    echo "⚠️ Health check failed. Checking logs..."
    docker-compose logs app --tail 30
fi

echo ""
echo "================================"
echo "Done!"

