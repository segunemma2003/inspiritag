# 🔧 Troubleshooting 502 Bad Gateway Error

## Problem

Your test is showing: **502 Bad Gateway**

This means:

-   ✅ Server is reachable (38.180.244.178)
-   ✅ Nginx is running
-   ❌ PHP-FPM/Laravel application is **NOT running** or not responding

---

## 🔍 Quick Diagnosis

SSH into your server and run these commands:

```bash
# Check if Docker containers are running
docker ps

# Check container logs
docker logs inspirtag-app
docker logs inspirtag-nginx
docker logs inspirtag-queue
docker logs inspirtag-scheduler
```

---

## 🚀 Solution: Start Your Application

### Step 1: SSH to Your Server

```bash
ssh user@38.180.244.178
cd /path/to/your/social-media/project
```

### Step 2: Start Docker Containers

```bash
# If using current setup
docker-compose up -d

# If using supervisor setup
docker-compose -f docker-compose-supervisor.yml up -d

# Check containers are running
docker ps
```

### Step 3: Verify Containers

You should see these containers running:

```
inspirtag-app         (or inspirtag-api)
inspirtag-nginx
inspirtag-redis
inspirtag-queue       (if not using supervisor)
inspirtag-scheduler   (if not using supervisor)
```

### Step 4: Check Application Logs

```bash
# Check app logs
docker logs inspirtag-app --tail 100

# Check nginx logs
docker logs inspirtag-nginx --tail 50

# Check if PHP-FPM is running
docker exec inspirtag-app ps aux | grep php-fpm
```

### Step 5: Test API Health

```bash
curl http://localhost/api/health
# or from your local machine
curl http://38.180.244.178/api/health
```

---

## 🔧 Common Issues & Fixes

### Issue 1: Containers Not Running

**Check:**

```bash
docker ps -a  # See all containers, including stopped ones
```

**Fix:**

```bash
# Start containers
docker-compose up -d

# Or rebuild if needed
docker-compose down
docker-compose build
docker-compose up -d
```

---

### Issue 2: PHP-FPM Not Starting

**Check:**

```bash
docker logs inspirtag-app | grep -i error
docker logs inspirtag-app | grep -i fatal
```

**Common Causes:**

-   Database connection issues
-   Permission problems
-   Missing environment variables

**Fix:**

```bash
# Check .env file exists
docker exec inspirtag-app ls -la /var/www/html/.env

# Check permissions
docker exec inspirtag-app chown -R www-data:www-data /var/www/html/storage
docker exec inspirtag-app chmod -R 775 /var/www/html/storage

# Restart container
docker restart inspirtag-app
```

---

### Issue 3: Database Connection Failed

**Check:**

```bash
docker exec inspirtag-app php artisan tinker
```

Then:

```php
DB::connection()->getPdo();
```

**Fix:**
Update your `.env` on the server:

```env
DB_HOST=host.docker.internal  # or your actual MySQL host
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Then:

```bash
docker exec inspirtag-app php artisan config:clear
docker restart inspirtag-app
```

---

### Issue 4: Nginx Can't Connect to PHP-FPM

**Check nginx config:**

```bash
docker exec inspirtag-nginx cat /etc/nginx/conf.d/default.conf | grep fastcgi_pass
```

Should show:

```nginx
fastcgi_pass app:9000;  # or inspirtag-app:9000
```

**Fix:**

```bash
# Restart both nginx and app
docker restart inspirtag-app inspirtag-nginx

# Check they're on the same network
docker network inspect inspirtag-network
```

---

### Issue 5: Missing APP_KEY

**Check:**

```bash
docker exec inspirtag-app php artisan key:generate --show
docker exec inspirtag-app env | grep APP_KEY
```

**Fix:**

```bash
# Generate key if missing
docker exec inspirtag-app php artisan key:generate
docker restart inspirtag-app
```

---

### Issue 6: Queue Workers Not Running

**Check:**

```bash
# If using separate containers
docker ps | grep queue

# If using supervisor
docker exec inspirtag-app supervisorctl status
```

**Fix:**

```bash
# Separate containers
docker restart inspirtag-queue

# Supervisor
docker exec inspirtag-app supervisorctl restart laravel-queue:*
```

---

## 📋 Complete Startup Checklist

Run these commands on your server:

```bash
# 1. Navigate to project
cd /path/to/social-media

# 2. Check .env exists
ls -la .env

# 3. Stop any running containers
docker-compose down

# 4. Start fresh
docker-compose build
docker-compose up -d

# 5. Check containers are up
docker ps

# 6. Run migrations
docker exec inspirtag-app php artisan migrate --force

# 7. Clear caches
docker exec inspirtag-app php artisan config:clear
docker exec inspirtag-app php artisan cache:clear
docker exec inspirtag-app php artisan route:clear

# 8. Check logs
docker logs inspirtag-app --tail 50

# 9. Test health endpoint
curl http://localhost/api/health

# 10. Test from outside
curl http://38.180.244.178/api/health
```

---

## 🧪 Test Once Everything is Running

From your local machine:

```bash
# Test health
curl http://38.180.244.178/api/health

# Should return:
# {"status":"healthy","timestamp":"..."}

# Test registration
curl -X POST http://38.180.244.178/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Test User",
    "email": "test@example.com",
    "username": "testuser123",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "terms_accepted": true
  }'

# Should return:
# {"success":true,"message":"Registration successful. Please check your email..."}
```

---

## 📊 Useful Monitoring Commands

```bash
# Watch container status
watch docker ps

# Follow app logs
docker logs -f inspirtag-app

# Follow nginx logs
docker logs -f inspirtag-nginx

# Check resource usage
docker stats

# Check supervisor (if using)
docker exec inspirtag-app supervisorctl status

# Check queue jobs
docker exec inspirtag-app php artisan queue:work --once
```

---

## 🚨 Emergency Recovery

If nothing works, nuclear option:

```bash
# Stop everything
docker-compose down -v

# Remove all containers
docker rm -f $(docker ps -aq)

# Clean up
docker system prune -a

# Start fresh
docker-compose build --no-cache
docker-compose up -d

# Run migrations
docker exec inspirtag-app php artisan migrate:fresh --force

# Test
curl http://localhost/api/health
```

---

## 📞 Still Not Working?

### Check These:

1. **Is Docker installed?**

    ```bash
    docker --version
    docker-compose --version
    ```

2. **Are ports available?**

    ```bash
    sudo netstat -tulpn | grep :80
    sudo netstat -tulpn | grep :443
    sudo netstat -tulpn | grep :9000
    ```

3. **Is firewall blocking?**

    ```bash
    sudo ufw status
    sudo ufw allow 80/tcp
    sudo ufw allow 443/tcp
    ```

4. **Check disk space:**

    ```bash
    df -h
    docker system df
    ```

5. **Check memory:**
    ```bash
    free -h
    ```

---

## ✅ Success Indicators

You'll know it's working when:

-   ✅ `docker ps` shows all containers running
-   ✅ `curl http://localhost/api/health` returns healthy status
-   ✅ `docker logs inspirtag-app` shows no errors
-   ✅ `curl http://38.180.244.178/api/health` works from outside
-   ✅ Registration endpoint returns JSON response

---

## 🎯 Next Steps After Fix

Once the server is running:

1. Test the OTP registration flow
2. Configure production email (SMTP)
3. Test email sending
4. Monitor logs for errors
5. Setup monitoring/alerting

Good luck! 🚀
