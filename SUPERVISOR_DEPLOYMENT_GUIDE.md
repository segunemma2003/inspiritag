# Supervisor Deployment Guide

## Overview

Your application uses **Supervisor** to manage Laravel processes:

-   ✅ Queue Workers (for OTP emails and notifications)
-   ✅ Scheduler (for OTP cleanup and other scheduled tasks)
-   ✅ Performance Monitor (optional)

---

## 📦 What's Included

### 1. **Supervisor Configuration**

-   `docker/supervisord-laravel.conf` - Optimized Supervisor config for Laravel
-   Manages 2 queue workers (for better performance)
-   Auto-restarts processes if they fail
-   Rotates logs automatically

### 2. **Docker Setup Options**

#### Option A: Current Setup (Separate Containers)

File: `docker-compose.yml`

-   Separate containers for: app, queue, scheduler, nginx, redis
-   Good for: scaling individual services

#### Option B: Supervisor Setup (Single Container)

File: `docker-compose-supervisor.yml`

-   Single app container running Supervisor
-   Supervisor manages PHP-FPM, Queue, Scheduler
-   Good for: simpler deployment, resource efficiency

---

## 🚀 Deployment Options

### Option 1: Docker with Supervisor (Recommended)

Use the new supervisor-based docker-compose:

```bash
# Use the supervisor version
docker-compose -f docker-compose-supervisor.yml up -d

# Check supervisor status
docker exec inspirtag-app supervisorctl status

# View logs
docker exec inspirtag-app supervisorctl tail -f laravel-queue
docker exec inspirtag-app supervisorctl tail -f laravel-scheduler
```

### Option 2: Keep Current Setup

Your current `docker-compose.yml` already works with separate containers:

```bash
# Current setup continues to work
docker-compose up -d

# Check queue worker
docker logs -f inspirtag-queue

# Check scheduler
docker logs -f inspirtag-scheduler
```

### Option 3: VPS/Server with Supervisor

For deployment on a VPS (DigitalOcean, Linode, AWS EC2, etc.):

#### Install Supervisor:

```bash
sudo apt update
sudo apt install supervisor
```

#### Copy Laravel Config:

```bash
sudo cp docker/supervisord-laravel.conf /etc/supervisor/conf.d/laravel-worker.conf
```

#### Update Paths in Config:

```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Change paths to match your server:

```ini
command=php /var/www/your-app/artisan queue:work ...
directory=/var/www/your-app
user=www-data
```

#### Start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

#### Check Status:

```bash
sudo supervisorctl status
```

---

## 📋 Supervisor Commands

### Check Status

```bash
# Docker
docker exec inspirtag-app supervisorctl status

# VPS
sudo supervisorctl status
```

### View Logs

```bash
# Docker
docker exec inspirtag-app supervisorctl tail -f laravel-queue
docker exec inspirtag-app supervisorctl tail -f laravel-scheduler

# VPS
sudo supervisorctl tail -f laravel-queue
sudo supervisorctl tail -f laravel-scheduler
```

### Restart Workers

```bash
# Docker
docker exec inspirtag-app supervisorctl restart laravel-queue:*
docker exec inspirtag-app supervisorctl restart laravel-scheduler

# VPS
sudo supervisorctl restart laravel-queue:*
sudo supervisorctl restart laravel-scheduler
```

### Stop/Start All

```bash
# Docker
docker exec inspirtag-app supervisorctl stop all
docker exec inspirtag-app supervisorctl start all

# VPS
sudo supervisorctl stop all
sudo supervisorctl start all
```

---

## 🔧 Configuration Details

### Queue Worker Configuration

```ini
[program:laravel-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
numprocs=2          ← Runs 2 workers for better performance
autostart=true      ← Starts automatically
autorestart=true    ← Restarts if it crashes
stopwaitsecs=3600   ← Waits 1 hour before force-stopping
```

**Why 2 workers?**

-   Handle OTP emails quickly
-   Process notifications in parallel
-   Prevent backlog during peak times

### Scheduler Configuration

```ini
[program:laravel-scheduler]
command=php /var/www/html/artisan schedule:work
autostart=true
autorestart=true
```

**What does it run?**

-   `users:delete-unverified` (every 5 minutes)
-   `cache:warm-up` (every 5 minutes)
-   `performance:monitor` (every 10 minutes)
-   Other scheduled tasks

---

## 🧪 Testing Your Setup

### 1. Check Supervisor is Running

```bash
# Docker
docker exec inspirtag-app supervisorctl status

# Should show:
# laravel-queue:laravel-queue_00    RUNNING
# laravel-queue:laravel-queue_01    RUNNING
# laravel-scheduler                 RUNNING
# php-fpm                          RUNNING
```

### 2. Test Queue Worker

```bash
# Send a test OTP
docker exec -it inspirtag-app php artisan tinker
```

Then in tinker:

```php
use App\Models\Otp;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Notification;

$otp = Otp::createOTP('test@example.com', 'registration');
Notification::route('mail', 'test@example.com')
    ->notify(new SendOtpNotification($otp->otp, 'registration'));
```

Check queue logs:

```bash
docker exec inspirtag-app supervisorctl tail -f laravel-queue
```

### 3. Test Scheduler

```bash
# Manually trigger the cleanup command
docker exec inspirtag-app php artisan users:delete-unverified

# Check scheduler logs
docker exec inspirtag-app supervisorctl tail -f laravel-scheduler
```

---

## 📊 Monitoring

### View All Logs

```bash
# Docker
docker exec inspirtag-app supervisorctl tail -f laravel-queue stderr
docker exec inspirtag-app supervisorctl tail -f laravel-scheduler stdout

# VPS
sudo tail -f /var/www/html/storage/logs/queue-worker.log
sudo tail -f /var/www/html/storage/logs/scheduler.log
```

### Check Process Health

```bash
# Docker
docker exec inspirtag-app supervisorctl status

# VPS
sudo supervisorctl status
```

### Restart After Code Changes

```bash
# Docker
docker exec inspirtag-app supervisorctl restart laravel-queue:*

# VPS
sudo supervisorctl restart laravel-queue:*
```

---

## 🔄 Deployment Workflow

### When You Deploy New Code:

1. **Pull Latest Code**

```bash
git pull origin main
```

2. **Update Dependencies**

```bash
docker exec inspirtag-app composer install --no-dev --optimize-autoloader
```

3. **Run Migrations**

```bash
docker exec inspirtag-app php artisan migrate --force
```

4. **Clear Caches**

```bash
docker exec inspirtag-app php artisan config:cache
docker exec inspirtag-app php artisan route:cache
docker exec inspirtag-app php artisan view:cache
```

5. **Restart Workers**

```bash
docker exec inspirtag-app supervisorctl restart laravel-queue:*
```

---

## 🚨 Troubleshooting

### "Queue not processing"

```bash
# Check status
docker exec inspirtag-app supervisorctl status laravel-queue:*

# Restart
docker exec inspirtag-app supervisorctl restart laravel-queue:*

# Check logs
docker exec inspirtag-app supervisorctl tail -f laravel-queue stderr
```

### "Scheduler not running"

```bash
# Check status
docker exec inspirtag-app supervisorctl status laravel-scheduler

# Restart
docker exec inspirtag-app supervisorctl restart laravel-scheduler

# Check logs
docker exec inspirtag-app supervisorctl tail -f laravel-scheduler stderr
```

### "Supervisor not starting"

```bash
# Check configuration
docker exec inspirtag-app supervisorctl reread

# Reload configuration
docker exec inspirtag-app supervisorctl update

# Check main supervisor log
docker exec inspirtag-app cat /var/www/html/storage/logs/supervisord.log
```

### "Workers dying/crashing"

```bash
# Check error logs
docker exec inspirtag-app tail -100 /var/www/html/storage/logs/queue-worker-error.log

# Check Laravel logs
docker exec inspirtag-app tail -100 /var/www/html/storage/logs/laravel.log
```

---

## 🎯 Production Checklist

-   [ ] Supervisor installed and configured
-   [ ] Queue workers running (check `supervisorctl status`)
-   [ ] Scheduler running (check `supervisorctl status`)
-   [ ] Email configured (SMTP credentials in `.env`)
-   [ ] Test OTP registration flow
-   [ ] Test password reset flow
-   [ ] Monitor logs for errors
-   [ ] Setup log rotation (already configured)
-   [ ] Setup monitoring/alerting for supervisor processes

---

## 📈 Performance Tuning

### Increase Queue Workers

If you have high email volume, increase workers:

Edit `docker/supervisord-laravel.conf`:

```ini
[program:laravel-queue]
numprocs=4  ← Change from 2 to 4 (or more)
```

Restart:

```bash
docker-compose restart app
```

### Memory Limits

If workers are dying due to memory:

```ini
[program:laravel-queue]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=512
```

---

## 🔗 Next Steps

1. Choose your deployment option (Docker with Supervisor recommended)
2. Deploy and test
3. Monitor logs for the first few days
4. Setup alerting (optional - use tools like Sentry or New Relic)

Your OTP system is now production-ready with Supervisor! 🚀
