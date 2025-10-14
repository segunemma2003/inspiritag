# ✅ Correct Server Fix (Based on Your docker-compose.yml)

## Your Actual Setup

**Container names:**

-   `inspirtag-api` - Main application (PHP-FPM)
-   `inspirtag-nginx` - Web server
-   `inspirtag-redis` - Redis cache
-   `inspirtag-queue` - Queue worker (OTP emails)
-   `inspirtag-scheduler` - Scheduler (cleanup)

**Auto-setup via entrypoint.sh:**

-   Waits for MySQL
-   Runs `composer install`
-   Clears caches
-   Generates APP_KEY
-   Runs migrations
-   Sets permissions

## 🚀 The Fix (Run on Server)

### Step 1: SSH to Server

```bash
ssh Root@38.180.244.178
```

Password: `PGU8aPqTm2`

### Step 2: Find Project Directory

```bash
# Try common locations
cd /var/www/html || cd /var/www || cd /root
ls -la
# Find your project folder with docker-compose.yml
cd your-project-folder
```

### Step 3: Check Current Status

```bash
docker ps -a
```

### Step 4: Restart Everything (entrypoint.sh will do the setup)

```bash
# Stop all containers
docker-compose down

# Start fresh (entrypoint.sh runs automatically)
docker-compose up -d

# Wait for startup
sleep 20

# Check status
docker ps
```

You should see 5 containers:

-   inspirtag-api (healthy)
-   inspirtag-nginx (healthy)
-   inspirtag-redis (healthy)
-   inspirtag-queue (running)
-   inspirtag-scheduler (running)

### Step 5: Check Logs

```bash
# Check API logs (should show "Laravel application setup completed!")
docker logs inspirtag-api --tail 50

# Check for errors
docker logs inspirtag-api | grep -i error
docker logs inspirtag-nginx | grep -i error
```

### Step 6: Test

```bash
# Test on server
curl http://localhost/api/health

# Should return:
# {"status":"healthy","timestamp":"..."}
```

### Step 7: Test from Outside

On your Mac:

```bash
curl http://38.180.244.178/api/health
```

## 🔧 If Still Getting 502

### Check Database Connection

Your app connects to MySQL on the HOST (not in Docker), check `.env`:

```bash
# View database config
cat .env | grep DB_

# Should have:
# DB_HOST=host.docker.internal (or actual MySQL IP)
# DB_PORT=3306
# DB_DATABASE=your_database
# DB_USERNAME=your_user
# DB_PASSWORD=your_password
```

If wrong, fix `.env` and restart:

```bash
nano .env  # or vi .env
# Fix DB_ settings
docker-compose restart
```

### Check Container Health

```bash
# Detailed status
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Check if API container is crashing
docker logs inspirtag-api --tail 100
```

### Manual Commands (if needed)

```bash
# If migrations didn't run
docker exec inspirtag-api php artisan migrate --force

# If caches need clearing
docker exec inspirtag-api php artisan config:clear
docker exec inspirtag-api php artisan cache:clear

# If permissions are wrong
docker exec inspirtag-api chown -R www-data:www-data /var/www/html/storage
docker exec inspirtag-api chmod -R 775 /var/www/html/storage
```

## ✅ ONE-LINE FIX

Copy this ENTIRE command (it's one line):

```bash
cd /var/www/html 2>/dev/null || cd /var/www 2>/dev/null || cd /root 2>/dev/null; pwd; echo "Stopping containers..."; docker-compose down; echo "Starting containers..."; docker-compose up -d; echo "Waiting 20 seconds..."; sleep 20; echo "Container status:"; docker ps --format "table {{.Names}}\t{{.Status}}"; echo ""; echo "Checking API logs:"; docker logs inspirtag-api --tail 20; echo ""; echo "Testing API:"; curl -s http://localhost/api/health; echo ""; echo "DONE! Test from outside: curl http://38.180.244.178/api/health"
```

## 🧪 Test OTP After Fix

Once API is healthy, test registration:

```bash
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
```

Should return:

```json
{
    "success": true,
    "message": "Registration successful. Please check your email for OTP to verify your account."
}
```

Check OTP in logs:

```bash
docker logs inspirtag-api | grep -B5 -A5 "**"
# or
docker exec inspirtag-api tail -100 /var/www/html/storage/logs/laravel.log | grep "**"
```

## 📊 Check OTP System is Working

```bash
# Check queue worker
docker logs inspirtag-queue --tail 20

# Check scheduler (OTP cleanup)
docker logs inspirtag-scheduler --tail 20

# Manually trigger cleanup test
docker exec inspirtag-api php artisan users:delete-unverified
```

## 🎯 Summary

Your setup has:
✅ Auto-setup via `entrypoint.sh`
✅ Separate queue worker container
✅ Separate scheduler container  
✅ All .env variables loaded automatically

Just restart with `docker-compose down && docker-compose up -d` and it should work! 🚀
