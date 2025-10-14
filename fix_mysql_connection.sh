#!/bin/bash
set -e

echo "🔍 Diagnosing MySQL Connection Issue..."
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check 1: Is MySQL running on host?
echo -e "\n${YELLOW}1. Checking if MySQL is running on host...${NC}"
if systemctl is-active --quiet mysql 2>/dev/null; then
    echo -e "${GREEN}✅ MySQL service is running${NC}"
    MYSQL_RUNNING=true
elif systemctl is-active --quiet mariadb 2>/dev/null; then
    echo -e "${GREEN}✅ MariaDB service is running${NC}"
    MYSQL_RUNNING=true
else
    echo -e "${RED}❌ MySQL/MariaDB is NOT running on host${NC}"
    MYSQL_RUNNING=false
    
    echo -e "${YELLOW}Attempting to start MySQL...${NC}"
    if sudo systemctl start mysql 2>/dev/null || sudo systemctl start mariadb 2>/dev/null; then
        echo -e "${GREEN}✅ MySQL started successfully${NC}"
        MYSQL_RUNNING=true
    else
        echo -e "${RED}❌ Could not start MySQL${NC}"
    fi
fi

# Check 2: Is MySQL listening on network?
echo -e "\n${YELLOW}2. Checking MySQL network configuration...${NC}"
if netstat -tulpn 2>/dev/null | grep -q ':3306'; then
    echo -e "${GREEN}✅ MySQL is listening on port 3306${NC}"
    netstat -tulpn 2>/dev/null | grep ':3306' | head -1
else
    echo -e "${RED}❌ MySQL is NOT listening on port 3306${NC}"
fi

# Check 3: Find Docker bridge IP
echo -e "\n${YELLOW}3. Finding Docker bridge IP...${NC}"
DOCKER_BRIDGE_IP=$(ip addr show docker0 2>/dev/null | grep 'inet ' | awk '{print $2}' | cut -d/ -f1 || echo "")
if [ -n "$DOCKER_BRIDGE_IP" ]; then
    echo -e "${GREEN}✅ Docker bridge IP: $DOCKER_BRIDGE_IP${NC}"
else
    echo -e "${YELLOW}⚠️ Could not find docker0 interface (this is okay if Docker is running)${NC}"
    DOCKER_BRIDGE_IP="172.17.0.1"
    echo -e "Using default: $DOCKER_BRIDGE_IP"
fi

# Check 4: Test connection from container
echo -e "\n${YELLOW}4. Testing MySQL connection from Docker container...${NC}"
if docker ps | grep -q inspirtag-api; then
    echo "Testing connection to host.docker.internal..."
    if docker exec inspirtag-api nc -zv host.docker.internal 3306 2>&1 | grep -q succeeded; then
        echo -e "${GREEN}✅ Container CAN reach host.docker.internal:3306${NC}"
    else
        echo -e "${RED}❌ Container CANNOT reach host.docker.internal:3306${NC}"
        
        echo "Testing connection to $DOCKER_BRIDGE_IP..."
        if docker exec inspirtag-api nc -zv $DOCKER_BRIDGE_IP 3306 2>&1 | grep -q succeeded; then
            echo -e "${GREEN}✅ Container CAN reach $DOCKER_BRIDGE_IP:3306${NC}"
            echo -e "${YELLOW}💡 You should use DB_HOST=$DOCKER_BRIDGE_IP instead of host.docker.internal${NC}"
        else
            echo -e "${RED}❌ Container CANNOT reach $DOCKER_BRIDGE_IP:3306${NC}"
        fi
    fi
else
    echo -e "${YELLOW}⚠️ inspirtag-api container is not running, skipping network test${NC}"
fi

# Check 5: Read current .env DB settings
echo -e "\n${YELLOW}5. Current database configuration in .env:${NC}"
if [ -f .env ]; then
    echo "DB_HOST: $(grep '^DB_HOST=' .env | cut -d= -f2)"
    echo "DB_PORT: $(grep '^DB_PORT=' .env | cut -d= -f2)"
    echo "DB_DATABASE: $(grep '^DB_DATABASE=' .env | cut -d= -f2)"
    echo "DB_USERNAME: $(grep '^DB_USERNAME=' .env | cut -d= -f2)"
else
    echo -e "${RED}❌ .env file not found!${NC}"
fi

# Provide solutions
echo -e "\n${YELLOW}========================================${NC}"
echo -e "${YELLOW}📋 SOLUTIONS:${NC}"
echo -e "${YELLOW}========================================${NC}"

if [ "$MYSQL_RUNNING" = false ]; then
    echo -e "\n${RED}ISSUE: MySQL is not running on host${NC}"
    echo "FIX: Start MySQL service:"
    echo "  sudo systemctl start mysql"
    echo "  sudo systemctl enable mysql"
fi

echo -e "\n${GREEN}SOLUTION 1: Fix MySQL to accept Docker connections${NC}"
echo "---------------------------------------------------"
echo "1. Edit MySQL config:"
echo "   sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf"
echo "   Change 'bind-address = 127.0.0.1' to 'bind-address = 0.0.0.0'"
echo ""
echo "2. Restart MySQL:"
echo "   sudo systemctl restart mysql"
echo ""
echo "3. Grant Docker access (replace with your actual DB name and credentials):"
echo "   mysql -u root -p"
echo "   GRANT ALL PRIVILEGES ON your_database.* TO 'your_user'@'172.%' IDENTIFIED BY 'your_password';"
echo "   FLUSH PRIVILEGES;"
echo "   EXIT;"
echo ""
echo "4. Restart containers:"
echo "   docker-compose restart app queue scheduler"

echo -e "\n${GREEN}SOLUTION 2: Use Docker bridge IP instead${NC}"
echo "---------------------------------------------------"
echo "Edit .env and change DB_HOST:"
echo "   sed -i 's/^DB_HOST=.*/DB_HOST=$DOCKER_BRIDGE_IP/' .env"
echo ""
echo "Then restart containers:"
echo "   docker-compose restart app queue scheduler"

echo -e "\n${GREEN}SOLUTION 3: Skip MySQL check temporarily${NC}"
echo "---------------------------------------------------"
echo "Edit docker/entrypoint.sh and change line 8 to:"
echo "   max_attempts=1"
echo ""
echo "This will skip the MySQL wait and start PHP-FPM anyway."
echo "Then rebuild:"
echo "   docker-compose build app"
echo "   docker-compose up -d"

echo -e "\n${GREEN}SOLUTION 4: Add MySQL to Docker Compose (Easiest)${NC}"
echo "---------------------------------------------------"
echo "Instead of using host MySQL, run MySQL in Docker."
echo "This is the most reliable option for production."

echo -e "\n${YELLOW}========================================${NC}"
echo -e "${YELLOW}🧪 QUICK TEST${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""
echo "After applying a fix, test with:"
echo "  docker-compose restart app"
echo "  docker-compose logs app --tail 50"
echo "  curl http://localhost/api/health"
echo ""

echo -e "${GREEN}Diagnosis complete!${NC}"

