# 🔴 Fixing 502 Error - MySQL Connection Issue

## 🔍 What Happened?

After deployment, your server started returning **502 Bad Gateway** errors. The logs show:

```
Waiting for MySQL on host.docker.internal... (attempt 29/30)
⚠️ MySQL connection failed after 30 attempts
```

**Root Cause**: Your Docker containers cannot connect to MySQL on the host machine.

---

## 🚨 **QUICK FIX (Run This First)**

SSH to your server and run:

```bash
cd /var/www/inspirtag
bash emergency_fix_502.sh
```

This script will:
1. ✅ Update DB_HOST to use Docker bridge IP instead of `host.docker.internal`
2. ✅ Reduce MySQL wait time from 30 to 3 attempts
3. ✅ Restart all containers
4. ✅ Test the API

**If this doesn't work**, continue to the solutions below.

---

## 🔧 **Permanent Solutions**

### **Solution 1: Configure MySQL to Accept Docker Connections** ⭐ Recommended

This is the most common fix and works for most setups.

#### Step 1: Edit MySQL Configuration

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Find this line:
```
bind-address = 127.0.0.1
```

Change it to:
```
bind-address = 0.0.0.0
```

#### Step 2: Restart MySQL

```bash
sudo systemctl restart mysql
```

#### Step 3: Grant Docker Network Access

```bash
mysql -u root -p
```

Then in MySQL console:
```sql
-- Replace 'your_database', 'your_user', and 'your_password' with actual values
GRANT ALL PRIVILEGES ON your_database.* TO 'your_user'@'172.%' IDENTIFIED BY 'your_password';
FLUSH PRIVILEGES;
EXIT;
```

#### Step 4: Restart Containers

```bash
cd /var/www/inspirtag
docker-compose restart app queue scheduler
```

#### Step 5: Test

```bash
curl http://localhost/api/health
```

You should see:
```json
{"status":"healthy","timestamp":"..."}
```

---

### **Solution 2: Use Docker Bridge IP**

If `host.docker.internal` doesn't work on your Linux server (it's a Mac/Windows feature), use the actual Docker bridge IP.

#### Step 1: Find Docker Bridge IP

```bash
ip addr show docker0 | grep 'inet ' | awk '{print $2}' | cut -d/ -f1
```

Usually returns: `172.17.0.1`

#### Step 2: Update .env

```bash
cd /var/www/inspirtag
nano .env
```

Change:
```env
DB_HOST=host.docker.internal
```

To:
```env
DB_HOST=172.17.0.1
```

#### Step 3: Restart Containers

```bash
docker-compose restart app queue scheduler
```

#### Step 4: Test

```bash
curl http://localhost/api/health
```

---

### **Solution 3: Add MySQL to Docker Compose** (Simplest)

Instead of connecting to host MySQL, run MySQL inside Docker.

#### Step 1: Update docker-compose.yml

Add this service:

```yaml
services:
  mysql:
    image: mysql:8.0
    container_name: inspirtag-mysql
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
    volumes:
      - mysql_data:/var/lib/mysql
    ports:
      - "3306:3306"
    networks:
      - inspirtag-network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  mysql_data:  # Add this
  redis_data:
```

#### Step 2: Update .env

```bash
nano .env
```

Change:
```env
DB_HOST=host.docker.internal
```

To:
```env
DB_HOST=mysql
```

#### Step 3: Restart Everything

```bash
docker-compose down
docker-compose up -d
```

Wait for MySQL to be ready (about 30 seconds), then:

```bash
docker-compose exec app php artisan migrate --force
```

#### Step 4: Test

```bash
curl http://localhost/api/health
```

---

### **Solution 4: Bypass MySQL Check** (Temporary Workaround)

If you need the site up IMMEDIATELY and will fix MySQL later:

#### Step 1: Edit entrypoint.sh

```bash
nano docker/entrypoint.sh
```

Change line 8 from:
```bash
max_attempts=30
```

To:
```bash
max_attempts=1
```

This makes it skip the MySQL check quickly.

#### Step 2: Rebuild and Restart

```bash
docker-compose build app
docker-compose up -d
```

⚠️ **Warning**: Your app will start but database operations will fail until you fix the MySQL connection.

---

## 🔍 **Diagnosis Tools**

### Check Container Status
```bash
docker-compose ps
```

All containers should show "Up" status.

### Check App Logs
```bash
docker-compose logs app --tail 100
```

Look for MySQL connection errors.

### Test MySQL from Container
```bash
docker-compose exec app nc -zv host.docker.internal 3306
```

Should show "succeeded" if connection works.

### Check if MySQL is Running on Host
```bash
sudo systemctl status mysql
```

### Check MySQL is Listening
```bash
sudo netstat -tulpn | grep 3306
```

Should show MySQL listening.

### Run Full Diagnostic
```bash
bash fix_mysql_connection.sh
```

---

## 🎯 **Recommended Approach**

For a production server, I recommend this order:

1. **First**: Try Solution 1 (Configure MySQL) - This is most reliable
2. **If that fails**: Try Solution 2 (Docker bridge IP)
3. **If you want simplicity**: Use Solution 3 (MySQL in Docker)
4. **Emergency only**: Use Solution 4 (Skip check)

---

## ✅ **Verify Everything is Working**

After applying any solution, verify:

### 1. Containers are Running
```bash
docker-compose ps
```

Expected output:
```
NAME                  STATUS
inspirtag-api         Up (healthy)
inspirtag-nginx       Up (healthy)
inspirtag-queue       Up
inspirtag-redis       Up (healthy)
inspirtag-scheduler   Up
```

### 2. No MySQL Errors in Logs
```bash
docker-compose logs app --tail 50
```

Should NOT see "MySQL connection failed" messages.

### 3. Health Endpoint Works
```bash
curl http://localhost/api/health
```

Expected:
```json
{"status":"healthy","timestamp":"2025-10-14T12:34:56.000000Z"}
```

### 4. API Works from Outside
```bash
curl http://YOUR_SERVER_IP/api/health
```

### 5. Database Connection Works
```bash
docker-compose exec app php artisan tinker
```

Then in Tinker:
```php
DB::connection()->getPdo();
```

Should return a PDO object, not an error.

---

## 🚫 **Prevent This in Future Deployments**

Your `deploy.yml` has been updated to:

1. ✅ Check MySQL connection before deploying
2. ✅ Not override working DB_HOST configuration
3. ✅ Warn if MySQL is unreachable

To prevent this issue:

1. **Keep .env DB_HOST stable** - Don't change it in deploy scripts
2. **Test MySQL connection** before deployment
3. **Monitor health endpoint** after deployment
4. **Use Docker MySQL** for consistency across environments

---

## 🆘 **Still Having Issues?**

If none of the solutions work:

### 1. Get Full Diagnostic
```bash
cd /var/www/inspirtag
bash fix_mysql_connection.sh
```

### 2. Check Full Logs
```bash
docker-compose logs --tail 200 > logs.txt
cat logs.txt
```

### 3. Nuclear Option (Last Resort)
```bash
cd /var/www/inspirtag

# Complete teardown
docker-compose down -v
docker system prune -f

# Fresh rebuild
docker-compose build --no-cache
docker-compose up -d

# Wait for services
sleep 30

# Clear caches
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear

# Test
curl http://localhost/api/health
```

---

## 📊 **Understanding the Error**

### What is 502 Bad Gateway?

- ✅ **Nginx is running** (port 80/443 accessible)
- ❌ **PHP-FPM is not responding** (can't process requests)
- 🔄 Nginx → tries to talk to PHP-FPM → gets no response → returns 502

### Why PHP-FPM Isn't Running?

Your `entrypoint.sh` script waits for MySQL connection before starting PHP-FPM. When MySQL connection fails:

1. Script tries 30 times to connect (60 seconds)
2. After 30 failures, it continues anyway
3. Tries to run migrations (fails without DB)
4. Tries to cache config (may fail with DB errors)
5. PHP-FPM might not start if config cache has errors

### The Fix

Any of the solutions above will:
- Ensure MySQL is reachable from Docker
- Allow entrypoint.sh to complete successfully
- Start PHP-FPM properly
- Return normal responses instead of 502

---

## 📝 **Summary**

**Problem**: Docker containers can't reach MySQL → entrypoint.sh fails → PHP-FPM doesn't start → 502 error

**Quick Fix**: Run `emergency_fix_502.sh`

**Best Long-term Fix**: Configure MySQL to accept connections from Docker network (Solution 1)

**Simplest Fix**: Run MySQL in Docker (Solution 3)

---

Good luck! 🚀

