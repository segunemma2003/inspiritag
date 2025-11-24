# Debugging 500 Internal Server Error

_Last updated: 2025-01-20_

## Quick Checklist

If you're getting a 500 error from `api.inspirtag.com`, check these common issues:

### 1. Check Application Logs

**SSH into your server** and check the Laravel logs:

```bash
# Check Laravel logs
docker-compose -p inspirtag exec app tail -100 /var/www/html/storage/logs/laravel.log

# Or check container logs
docker-compose -p inspirtag logs app --tail=100
docker-compose -p inspirtag logs nginx --tail=100
```

### 2. Verify Route Files Exist

Make sure these files exist on the server:

```bash
# SSH into server and check
ls -la /var/www/inspirtag/routes/web.php
ls -la /var/www/inspirtag/routes/console.php
```

If they're missing, they need to be deployed. We created them locally but they may not be on the server yet.

### 3. Check Container Status

```bash
# Check if containers are running
docker-compose -p inspirtag ps

# Check container health
docker-compose -p inspirtag ps app
docker-compose -p inspirtag ps nginx
```

### 4. Test Health Endpoint

```bash
# Test localhost
curl http://localhost:8080/health

# Test through system nginx
curl http://localhost/health
curl https://api.inspirtag.com/health

# Test API endpoint
curl http://localhost:8080/api/health
curl https://api.inspirtag.com/api/health
```

### 5. Check Environment Variables

```bash
# Check if .env exists
docker-compose -p inspirtag exec app cat /var/www/html/.env | grep -E "APP_ENV|APP_DEBUG|DB_|CACHE_"
```

Ensure:

-   `APP_ENV=production`
-   `APP_DEBUG=false` (should be false in production)
-   Database credentials are correct
-   Cache/Redis credentials are correct

### 6. Clear Caches

```bash
# Clear all caches
docker-compose -p inspirtag exec app php artisan config:clear
docker-compose -p inspirtag exec app php artisan route:clear
docker-compose -p inspirtag exec app php artisan cache:clear
docker-compose -p inspirtag exec app php artisan view:clear

# Rebuild caches
docker-compose -p inspirtag exec app php artisan config:cache
docker-compose -p inspirtag exec app php artisan route:cache
docker-compose -p inspirtag exec app php artisan view:cache
```

### 7. Check Database Connection

```bash
# Test database connection
docker-compose -p inspirtag exec app php artisan tinker
# Then in tinker:
DB::connection()->getPdo();
```

### 8. Check PHP Errors

```bash
# Check PHP error log
docker-compose -p inspirtag exec app tail -50 /var/log/php-fpm/error.log

# Or check container logs for PHP errors
docker-compose -p inspirtag logs app | grep -i error
```

### 9. Verify File Permissions

```bash
# Check storage and bootstrap/cache permissions
docker-compose -p inspirtag exec app ls -la /var/www/html/storage
docker-compose -p inspirtag exec app ls -la /var/www/html/bootstrap/cache

# Fix permissions if needed
docker-compose -p inspirtag exec app chmod -R 775 /var/www/html/storage
docker-compose -p inspirtag exec app chmod -R 775 /var/www/html/bootstrap/cache
```

### 10. Check Recent Changes

Since we just added route files, verify they're on the server:

```bash
# On your server
cd /var/www/inspirtag
ls -la routes/

# Should see:
# - api.php
# - web.php
# - console.php
```

---

## Common Error Causes

### Missing Route Files

**Error**: `Failed to open stream: routes/web.php` or `routes/console.php`

**Solution**:

```bash
# Ensure files exist
cd /var/www/inspirtag
git pull origin main
# Or manually create if missing (files should be in repo)
```

### Database Connection Error

**Error**: `SQLSTATE[HY000] [2002] Connection refused` or similar

**Solution**:

1. Check `.env` file for correct `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
2. Verify database service is running
3. Test connection manually

### Missing Environment Variables

**Error**: `Undefined index` or configuration errors

**Solution**:

1. Check `.env` file has all required variables
2. Clear config cache: `php artisan config:clear`
3. Rebuild config: `php artisan config:cache`

### Permission Issues

**Error**: `Permission denied` when writing to storage or cache

**Solution**:

```bash
docker-compose -p inspirtag exec app chmod -R 775 storage bootstrap/cache
docker-compose -p inspirtag exec app chown -R www-data:www-data storage bootstrap/cache
```

### PHP Memory Limit

**Error**: `Fatal error: Allowed memory size exhausted`

**Solution**:

1. Check PHP memory limit in `docker/php.ini`
2. Increase if needed: `memory_limit = 256M`

---

## Enable Detailed Error Messages (Temporary)

For debugging, you can temporarily enable detailed errors:

**⚠️ Only do this in a development/test environment, not production!**

```bash
# Edit .env file
docker-compose -p inspirtag exec app sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' /var/www/html/.env

# Clear config cache
docker-compose -p inspirtag exec app php artisan config:clear

# Restart containers
docker-compose -p inspirtag restart app
```

This will show detailed error messages in the browser/API response.

**Remember to set it back to `false` after debugging!**

---

## Quick Diagnostic Commands

Run these to get a full diagnostic:

```bash
# Full diagnostic script
docker-compose -p inspirtag exec app php artisan route:list | head -20
docker-compose -p inspirtag exec app php artisan config:show app.env
docker-compose -p inspirtag exec app php artisan config:show app.debug
docker-compose -p inspirtag exec app php -v
docker-compose -p inspirtag exec app composer --version
docker-compose -p inspirtag exec app php artisan --version
```

---

## Get Specific Error Details

### Method 1: Check Laravel Logs

```bash
# Real-time log monitoring
docker-compose -p inspirtag exec app tail -f /var/www/html/storage/logs/laravel.log
```

Then make a request to your API and watch the logs.

### Method 2: Check Nginx Error Logs

```bash
# Nginx error logs
docker-compose -p inspirtag logs nginx | grep -i error
```

### Method 3: Check System Nginx Logs (if using)

```bash
# System nginx error log
sudo tail -50 /var/log/nginx/error.log
```

### Method 4: Enable PHP Error Display (Temporary)

Add to `docker/php.ini`:

```ini
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
```

Then restart the container.

---

## Most Likely Issues After Recent Deployment

Since we just:

1. Added `routes/web.php`
2. Added `routes/console.php`
3. Updated `bootstrap/app.php` with `withExceptions()`

**Check these first**:

```bash
# 1. Verify route files exist
ls -la /var/www/inspirtag/routes/web.php
ls -la /var/www/inspirtag/routes/console.php

# 2. Check if deployment pulled latest changes
cd /var/www/inspirtag
git log --oneline -5
git status

# 3. Check Laravel logs for specific errors
docker-compose -p inspirtag exec app tail -50 /var/www/html/storage/logs/laravel.log
```

---

## Contact Points

If the error persists:

1. **Share the exact error message** from logs
2. **Share the endpoint** that's failing (e.g., `/api/posts`, `/api/health`)
3. **Share container status**: `docker-compose -p inspirtag ps`
4. **Share recent log entries**: Last 50-100 lines of laravel.log

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20
