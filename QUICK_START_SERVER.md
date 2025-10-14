# 🚀 Quick Start: Deploy OTP System to Server

## Current Status

Your server: **38.180.244.178**
Status: **502 Bad Gateway** (Application not running)

---

## 📋 Step-by-Step Deployment

### 1. SSH to Your Server

```bash
ssh user@38.180.244.178
```

### 2. Navigate to Project

```bash
cd /var/www/html  # or wherever your project is
# or
cd ~/social-media
```

### 3. Pull Latest Code

```bash
git pull origin main
```

### 4. Start Docker Containers

```bash
# Stop any running containers
docker-compose down

# Start all services
docker-compose up -d

# Verify containers are running
docker ps
```

You should see:

-   `inspirtag-app`
-   `inspirtag-nginx`
-   `inspirtag-redis`
-   `inspirtag-queue`
-   `inspirtag-scheduler`

### 5. Run Database Migrations

```bash
docker exec inspirtag-app php artisan migrate --force
```

### 6. Clear Caches

```bash
docker exec inspirtag-app php artisan config:clear
docker exec inspirtag-app php artisan cache:clear
```

### 7. Check Application is Running

```bash
# Test health endpoint on server
curl http://localhost/api/health

# Should return:
# {"status":"healthy","timestamp":"..."}
```

### 8. Test from Your Local Machine

```bash
# Test from your Mac
curl http://38.180.244.178/api/health
```

---

## ✅ Verify Everything Works

### Check Containers

```bash
docker ps

# Should show:
# CONTAINER ID   IMAGE          STATUS
# xxxxxxxxx      inspirtag-app  Up X minutes
# xxxxxxxxx      nginx          Up X minutes
# xxxxxxxxx      redis          Up X minutes
```

### Check Logs

```bash
# Application logs
docker logs inspirtag-app --tail 50

# Nginx logs
docker logs inspirtag-nginx --tail 20

# Queue worker logs
docker logs inspirtag-queue --tail 20
```

### Test Registration Endpoint

```bash
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Test User",
    "email": "test@example.com",
    "username": "testuser_'$(date +%s)'",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "terms_accepted": true
  }'
```

Should return:

```json
{
    "success": true,
    "message": "Registration successful. Please check your email for OTP to verify your account.",
    "data": {
        "email": "test@example.com",
        "otp_expires_in": "10 minutes",
        "account_expires_in": "30 minutes if not verified"
    }
}
```

---

## 🔧 If You Get Errors

### Error: "Cannot connect to database"

```bash
# Check .env file
docker exec inspirtag-app cat /var/www/html/.env | grep DB_

# Update database connection in .env
docker exec inspirtag-app nano /var/www/html/.env

# Then restart
docker-compose restart app
```

### Error: "Storage not writable"

```bash
# Fix permissions
docker exec inspirtag-app chown -R www-data:www-data /var/www/html/storage
docker exec inspirtag-app chmod -R 775 /var/www/html/storage
docker exec inspirtag-app chmod -R 775 /var/www/html/bootstrap/cache
```

### Error: "No application encryption key"

```bash
# Generate APP_KEY
docker exec inspirtag-app php artisan key:generate
docker-compose restart app
```

---

## 📧 Configure Production Email

Once the app is running, configure email:

### Edit .env on Server

```bash
docker exec inspirtag-app nano /var/www/html/.env
```

### Update Mail Settings

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="Social Media App"
```

### Apply Changes

```bash
docker exec inspirtag-app php artisan config:clear
docker restart inspirtag-queue  # Important: restart queue workers!
```

---

## 🧪 Test OTP Flow

### From Your Local Machine

```bash
# 1. Register
curl -X POST http://38.180.244.178/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "John Doe",
    "email": "your-real-email@example.com",
    "username": "johndoe123",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "terms_accepted": true
  }'

# 2. Check your email for OTP (or check server logs)
ssh user@38.180.244.178
docker exec inspirtag-app tail -100 /var/www/html/storage/logs/laravel.log | grep -B5 -A5 "**"

# 3. Verify OTP
curl -X POST http://38.180.244.178/api/verify-otp \
  -H "Content-Type: application/json" \
  -d '{
    "email": "your-real-email@example.com",
    "otp": "123456"
  }'

# 4. Login
curl -X POST http://38.180.244.178/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "your-real-email@example.com",
    "password": "Password123!"
  }'
```

---

## 📊 Monitor Your Application

### Watch Logs in Real-Time

```bash
# Application logs
docker logs -f inspirtag-app

# Queue worker (OTP emails)
docker logs -f inspirtag-queue

# Scheduler (cleanup unverified users)
docker logs -f inspirtag-scheduler
```

### Check Queue Status

```bash
# See if jobs are processing
docker exec inspirtag-app php artisan queue:work --once
```

### Check Scheduled Tasks

```bash
# List scheduled tasks
docker exec inspirtag-app php artisan schedule:list

# Run scheduler manually
docker exec inspirtag-app php artisan schedule:run
```

---

## 🎯 Production Checklist

-   [ ] Docker containers running on server
-   [ ] Health endpoint responding
-   [ ] Database migrations run
-   [ ] Storage permissions fixed
-   [ ] Email configured (SMTP)
-   [ ] Queue workers running
-   [ ] Scheduler running
-   [ ] Test registration with OTP
-   [ ] Test password reset with OTP
-   [ ] Monitor logs for errors

---

## 🔄 Update/Deploy New Code

When you make changes:

```bash
# On server
cd /var/www/html  # or your project path

# Pull latest code
git pull origin main

# Install dependencies (if changed)
docker exec inspirtag-app composer install --no-dev --optimize-autoloader

# Run migrations (if any)
docker exec inspirtag-app php artisan migrate --force

# Clear caches
docker exec inspirtag-app php artisan config:clear
docker exec inspirtag-app php artisan route:clear
docker exec inspirtag-app php artisan view:clear

# Restart queue workers (important!)
docker restart inspirtag-queue

# Or if using supervisor
docker exec inspirtag-app supervisorctl restart laravel-queue:*
```

---

## ✅ Success!

Once you see this, you're good:

```bash
$ curl http://38.180.244.178/api/health
{"status":"healthy","timestamp":"2025-10-13T20:45:00.000000Z"}

$ docker ps
CONTAINER ID   IMAGE                 STATUS
abc123...      inspirtag-app         Up 5 minutes (healthy)
def456...      nginx:alpine          Up 5 minutes (healthy)
ghi789...      redis:7-alpine        Up 5 minutes (healthy)
```

Your OTP system is now live! 🎉

---

## 📞 Need Help?

See `TROUBLESHOOTING_502_ERROR.md` for detailed troubleshooting steps.
