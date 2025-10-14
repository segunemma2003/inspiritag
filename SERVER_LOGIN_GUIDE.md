# 🔐 Server Access & Setup Guide

## Server Credentials

**Server:** 38.180.244.178  
**Username:** Root  
**Password:** PGU8aPqTm2

---

## 🚀 Quick Fix for 502 Error

### Step 1: SSH to Server

Open your terminal and run:

```bash
ssh Root@38.180.244.178
```

When prompted for password, enter: `PGU8aPqTm2`

### Step 2: Find Your Project

Once logged in, find where your project is:

```bash
# Common locations
cd /var/www/html/social-media
# or
cd /var/www/social-media
# or
cd /root/social-media
# or
ls -la /var/www/
```

### Step 3: Check Docker Status

```bash
# Check if containers are running
docker ps

# If nothing is running, you'll see empty list
```

### Step 4: Start Containers

```bash
# Make sure you're in project directory
cd /path/to/social-media  # use the path you found in Step 2

# Start all containers
docker-compose up -d

# Wait a few seconds, then check
docker ps
```

You should see containers like:

-   `inspirtag-app` or `inspirtag-api`
-   `inspirtag-nginx`
-   `inspirtag-redis`
-   `inspirtag-queue`
-   `inspirtag-scheduler`

### Step 5: Run Setup Commands

```bash
# Get the app container name (usually inspirtag-app)
APP_CONTAINER=$(docker ps --format "{{.Names}}" | grep "inspirtag-app\|inspirtag-api" | head -1)

# Run migrations
docker exec $APP_CONTAINER php artisan migrate --force

# Clear caches
docker exec $APP_CONTAINER php artisan config:clear
docker exec $APP_CONTAINER php artisan cache:clear

# Fix permissions
docker exec $APP_CONTAINER chown -R www-data:www-data /var/www/html/storage
docker exec $APP_CONTAINER chmod -R 775 /var/www/html/storage
```

### Step 6: Test

```bash
# Test on server
curl http://localhost/api/health

# Should return:
# {"status":"healthy","timestamp":"..."}
```

### Step 7: Test from Your Mac

Open a new terminal on your Mac:

```bash
curl http://38.180.244.178/api/health
```

If you see `{"status":"healthy"...}`, you're good! 🎉

---

## 🤖 Automated Fix Script

I've created an automated script for you. Here's how to use it:

### Option A: Run Directly on Server

```bash
# SSH to server
ssh Root@38.180.244.178

# Navigate to project
cd /var/www/html/social-media  # or wherever your project is

# Download and run the fix script
curl -o check_and_fix_server.sh https://raw.githubusercontent.com/your-repo/main/check_and_fix_server.sh
bash check_and_fix_server.sh
```

### Option B: Copy Script Manually

1. SSH to server:

```bash
ssh Root@38.180.244.178
```

2. Create the script:

```bash
cd /root
nano fix_server.sh
```

3. Paste this content:

```bash
#!/bin/bash

# Find project
cd /var/www/html/social-media || cd /var/www/social-media || cd /root/social-media

# Stop existing containers
docker-compose down

# Start fresh
docker-compose up -d

# Wait for startup
sleep 10

# Get app container
APP=$(docker ps --format "{{.Names}}" | grep "inspirtag" | head -1)

# Setup
docker exec $APP php artisan migrate --force
docker exec $APP php artisan config:clear
docker exec $APP php artisan cache:clear
docker exec $APP chown -R www-data:www-data /var/www/html/storage
docker exec $APP chmod -R 775 /var/www/html/storage

# Test
echo "Testing..."
curl http://localhost/api/health

echo ""
echo "Done! Test from outside: curl http://38.180.244.178/api/health"
```

4. Make it executable and run:

```bash
chmod +x fix_server.sh
./fix_server.sh
```

---

## 🔍 Troubleshooting

### Issue: "docker: command not found"

Install Docker:

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo systemctl start docker
sudo systemctl enable docker
```

### Issue: "docker-compose: command not found"

Install Docker Compose:

```bash
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
docker-compose --version
```

### Issue: Containers keep restarting

Check logs:

```bash
docker logs inspirtag-app --tail 100
docker logs inspirtag-nginx --tail 50
```

Common causes:

-   Database connection failed
-   Missing .env file
-   Permission issues

### Issue: Can't find project directory

Search for it:

```bash
find /var /root /home -name "social-media" -type d 2>/dev/null
```

Or check where code is deployed:

```bash
ls -la /var/www/
ls -la /var/www/html/
ls -la /root/
```

---

## 📋 Complete Setup Checklist

Run these in order on your server:

```bash
# 1. SSH
ssh Root@38.180.244.178

# 2. Find project
cd /var/www/html/social-media  # adjust path as needed

# 3. Check .env exists
ls -la .env

# 4. Stop containers
docker-compose down

# 5. Start containers
docker-compose up -d

# 6. Wait a bit
sleep 10

# 7. Check containers
docker ps

# 8. Get app container name
docker ps | grep inspirtag

# 9. Run migrations (replace $APP with actual container name)
docker exec inspirtag-app php artisan migrate --force

# 10. Clear caches
docker exec inspirtag-app php artisan config:clear
docker exec inspirtag-app php artisan cache:clear

# 11. Fix permissions
docker exec inspirtag-app chown -R www-data:www-data /var/www/html/storage
docker exec inspirtag-app chmod -R 775 /var/www/html/storage

# 12. Test
curl http://localhost/api/health

# 13. Exit and test from Mac
exit
curl http://38.180.244.178/api/health
```

---

## ✅ Success Indicators

You'll know it's working when:

1. `docker ps` shows 5+ containers running
2. `curl http://localhost/api/health` returns `{"status":"healthy"}`
3. `curl http://38.180.244.178/api/health` works from your Mac
4. No errors in `docker logs inspirtag-app`

---

## 🎯 After Server is Running

Test OTP registration from your Mac:

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

---

## 🔐 Security Note

⚠️ **Important:** Change your root password after setup:

```bash
# On server
passwd
```

Also consider:

-   Setting up SSH key authentication
-   Disabling password login
-   Setting up a firewall (UFW)
-   Creating a non-root user for deployments

---

## 📞 Need Help?

If you're still getting 502 errors after these steps, check:

1. `docker logs inspirtag-app` for application errors
2. `docker logs inspirtag-nginx` for nginx errors
3. `.env` file has correct database credentials
4. Storage directory has correct permissions

Good luck! 🚀
