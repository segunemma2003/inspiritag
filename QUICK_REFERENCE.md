# 🚀 Quick Reference Guide

Quick links to all documentation and common commands.

---

## 📚 Documentation Files

### Authentication & Security

-   **[OTP Authentication](OTP_AUTHENTICATION_DOCUMENTATION.md)** - Complete OTP system (registration & verification)
-   **[Forgot Password](FORGOT_PASSWORD_DOCUMENTATION.md)** - Password reset flow with OTP
-   **[User API](USER_API_DOCUMENTATION.md)** - All user-related endpoints
-   **[API Documentation](API_DOCUMENTATION.md)** - Complete API reference

### Setup & Deployment

-   **[Email Setup](EMAIL_SETUP_GUIDE.md)** - Configure production email (SMTP)
-   **[Deployment Summary](DEPLOYMENT_SUMMARY.md)** - Docker deployment guide
-   **[Supervisor Guide](SUPERVISOR_DEPLOYMENT_GUIDE.md)** - Queue & scheduler setup
-   **[Docker Environment](DOCKER_ENV_CONFIGURATION.md)** - Environment configuration

### Troubleshooting

-   **[Fixing 502 Error](FIXING_502_MYSQL_ERROR.md)** - MySQL connection issues
-   **[Troubleshooting 502](TROUBLESHOOTING_502_ERROR.md)** - General 502 debugging
-   **[Server Login Guide](SERVER_LOGIN_GUIDE.md)** - SSH and server access
-   **[Correct Server Fix](CORRECT_SERVER_FIX.md)** - Server configuration fixes

---

## 🧪 Test Scripts

### Authentication Tests

```bash
# Test OTP registration flow
./test_otp_registration.sh

# Test forgot password flow
./test_forgot_password.sh

# Test file upload
./upload_real.sh
```

### Server Diagnostics

```bash
# Diagnose and fix MySQL connection
./fix_mysql_connection.sh

# Complete fix for 502 errors
./emergency_fix_502.sh

# Complete fix with code updates
./COMPLETE_FIX_NOW.sh
```

---

## 🔑 Common API Endpoints

### Authentication

```bash
# Register user
POST /api/register

# Verify email with OTP
POST /api/verify-otp

# Login
POST /api/login

# Logout
POST /api/logout

# Forgot password (request OTP)
POST /api/forgot-password

# Reset password with OTP
POST /api/reset-password

# Resend OTP
POST /api/resend-otp
```

### Health Check

```bash
curl http://localhost/api/health
```

---

## 🐳 Docker Commands

### Container Management

```bash
# Start all containers
docker-compose up -d

# Stop all containers
docker-compose down

# Restart specific container
docker-compose restart app

# View container status
docker-compose ps

# View logs
docker-compose logs app --tail 50
docker-compose logs -f app  # Follow logs
```

### Laravel Commands

```bash
# Run artisan commands
docker-compose exec app php artisan [command]

# Examples:
docker-compose exec app php artisan migrate
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan tinker

# Check database
docker-compose exec app php artisan db:show
```

### Troubleshooting

```bash
# Check if PHP-FPM is running
docker-compose exec app ps aux | grep php

# Check MySQL connection
docker-compose exec app php artisan tinker
# Then: DB::connection()->getPdo();

# Access container shell
docker-compose exec app bash

# View environment variables
docker-compose exec app env | grep DB_
```

---

## 🗄️ Database Commands

### MySQL

```bash
# Access MySQL from host
mysql -u root -p

# Access MySQL from container
docker-compose exec app mysql -h172.17.0.1 -uroot -p

# Common queries
SHOW DATABASES;
USE your_database;
SHOW TABLES;
SELECT * FROM users LIMIT 5;
```

---

## 📧 Email & OTP

### Check OTP in Logs

```bash
# For registration OTP
docker-compose logs app | grep "Registration OTP" | tail -1

# For password reset OTP
docker-compose logs app | grep "Password reset OTP" | tail -1

# Check Laravel log file
docker-compose exec app tail -50 /var/www/html/storage/logs/laravel.log | grep OTP
```

### Email Configuration

```bash
# Current email settings
grep "^MAIL_" .env

# For development (logs email)
MAIL_MAILER=log

# For production (real SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
```

---

## 🔧 Quick Fixes

### 502 Bad Gateway

```bash
# Quick fix
docker-compose restart app nginx

# If still fails, clear caches
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose restart app

# Complete fix
./emergency_fix_502.sh
```

### MySQL Connection Issues

```bash
# Update DB_HOST in .env
sed -i 's|^DB_HOST=.*|DB_HOST=172.17.0.1|' .env
docker-compose restart app

# Or run diagnostic
./fix_mysql_connection.sh
```

### Queue Not Processing

```bash
# Restart queue worker
docker-compose restart queue

# Check queue status
docker-compose logs queue --tail 50

# Process one job manually
docker-compose exec app php artisan queue:work --once
```

---

## 🚀 Deployment

### Quick Deploy

```bash
# On server
cd /var/www/inspirtag
git pull origin main
docker-compose down
docker-compose build
docker-compose up -d
```

### GitHub Actions Deploy

```bash
# Push to main branch triggers automatic deployment
git push origin main

# Manual trigger
# Go to GitHub Actions → Deploy to VPS → Run workflow
```

---

## 🔍 Monitoring

### Health Check

```bash
# Local
curl http://localhost/api/health

# Production
curl http://YOUR_SERVER_IP/api/health
```

### Container Health

```bash
# Check all containers
docker-compose ps

# Container resource usage
docker stats

# Supervisor status (if using)
docker-compose exec app supervisorctl status
```

### Logs

```bash
# Application logs
docker-compose logs app --tail 100

# Nginx logs
docker-compose logs nginx --tail 50

# Queue logs
docker-compose logs queue --tail 50

# All logs
docker-compose logs --tail 50
```

---

## 🔐 Security

### Production Checklist

-   [ ] Configure real SMTP (not log driver)
-   [ ] Use strong database passwords
-   [ ] Enable HTTPS with SSL certificate
-   [ ] Set proper file permissions
-   [ ] Configure rate limiting
-   [ ] Enable firewall
-   [ ] Keep Docker images updated
-   [ ] Regular backups
-   [ ] Monitor error logs

### Update Passwords

```bash
# Database password
nano .env
# Update DB_PASSWORD
docker-compose restart app

# MySQL root password
mysql -u root -p
# Then: ALTER USER 'root'@'localhost' IDENTIFIED BY 'new_password';
```

---

## 📊 Performance

### Clear All Caches

```bash
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan route:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose restart app
```

### Optimize for Production

```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
docker-compose exec app php artisan optimize
```

---

## 🆘 Emergency Recovery

### Complete Reset

```bash
# Stop everything
docker-compose down -v

# Clean Docker
docker system prune -f

# Fresh start
docker-compose build --no-cache
docker-compose up -d

# Wait for startup
sleep 30

# Test
curl http://localhost/api/health
```

### Restore Backup

```bash
# Database
mysql -u root -p database_name < backup.sql

# Files
tar -xzf backup.tar.gz -C /var/www/inspirtag
```

---

## 📞 Quick Support Commands

### Get System Info

```bash
# Server info
uname -a
cat /etc/os-release

# Docker version
docker --version
docker-compose --version

# PHP version
docker-compose exec app php -v

# Laravel version
docker-compose exec app php artisan --version

# MySQL version
mysql --version
```

### Export Logs

```bash
# Export all logs to file
docker-compose logs > logs_$(date +%Y%m%d_%H%M%S).txt

# Export specific container
docker-compose logs app > app_logs.txt
```

---

## 🎯 Quick Tests

### Test API Endpoints

```bash
# Health
curl http://localhost/api/health

# Categories
curl http://localhost/api/categories

# Register (change email)
curl -X POST http://localhost/api/register \
  -H "Content-Type: application/json" \
  -d '{"full_name":"Test","email":"test@test.com","username":"testuser","password":"Pass123!","password_confirmation":"Pass123!","terms_accepted":true}'
```

---

## 📱 Mobile App Integration

### Base URL

```
Production: https://your-domain.com/api
Development: http://YOUR_SERVER_IP/api
```

### Required Headers

```
Content-Type: application/json
Accept: application/json
Authorization: Bearer YOUR_TOKEN  (for authenticated endpoints)
```

---

**Last Updated:** October 2025

For detailed information, refer to the specific documentation files listed above.
