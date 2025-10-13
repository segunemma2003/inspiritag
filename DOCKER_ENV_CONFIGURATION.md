# Docker Environment Configuration

## ✅ Email Configuration is Already Set Up!

Your Docker setup automatically reads **all** environment variables from your `.env` file, including email settings.

---

## 📋 How It Works

### 1. Docker Compose Configuration

Your `docker-compose.yml` includes:

```yaml
services:
    app:
        env_file:
            - .env  ← This loads ALL .env variables into the container

    queue:
        env_file:
            - .env  ← Queue worker also has access to .env

    scheduler:
        env_file:
            - .env  ← Scheduler also has access to .env
```

This means **every** variable in your `.env` file is automatically available inside your Docker containers.

---

## 📧 Current Email Configuration

Your `.env` already has:

```env
MAIL_MAILER=log
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

✅ **This works!** Laravel inside Docker will read these values automatically.

---

## 🔄 Switching to Production Email

When you're ready to use real SMTP, just update your `.env` file:

### For SendGrid (Recommended):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your-sendgrid-api-key-here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

### For Gmail (Testing):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-gmail-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### For Mailtrap (Development):

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 🚀 After Changing .env

After updating your `.env` file, you need to:

### 1. Clear Laravel Config Cache

```bash
docker exec inspirtag-app php artisan config:clear
docker exec inspirtag-app php artisan config:cache
```

### 2. Restart Queue Workers (Important!)

```bash
# If using current setup (separate containers)
docker restart inspirtag-queue

# If using supervisor setup
docker exec inspirtag-app supervisorctl restart laravel-queue:*
```

### 3. Restart Scheduler (if needed)

```bash
# If using current setup
docker restart inspirtag-scheduler

# If using supervisor setup
docker exec inspirtag-app supervisorctl restart laravel-scheduler
```

**OR** just restart everything:

```bash
docker-compose restart
```

---

## ✅ Verify Email Configuration

Test that Docker is reading your .env email settings:

```bash
# Check mail configuration inside container
docker exec inspirtag-app php artisan tinker
```

Then in tinker:

```php
config('mail.mailers.smtp');
config('mail.from');
```

You should see your email configuration!

---

## 🧪 Test Email Sending

### Quick Test:

```bash
docker exec inspirtag-app php artisan tinker
```

Then:

```php
use App\Models\Otp;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Notification;

$otp = Otp::generateOTP();
Notification::route('mail', 'test@example.com')
    ->notify(new SendOtpNotification($otp, 'registration'));

echo "OTP: " . $otp . "\n";
echo "Check your logs or email!";
```

### Check Logs:

```bash
# For log driver (current setup)
docker exec inspirtag-app tail -50 /var/www/html/storage/logs/laravel.log | grep -A5 -B5 "SendOtpNotification"

# Check queue is processing
docker logs -f inspirtag-queue
```

---

## 🔐 Environment Variables Available to Docker

All these are automatically loaded from your `.env`:

```env
# Application
APP_NAME=
APP_ENV=
APP_KEY=
APP_DEBUG=
APP_URL=

# Database
DB_CONNECTION=
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

# Queue
QUEUE_CONNECTION=

# Mail (the ones we need for OTP!)
MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=

# AWS S3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

# Firebase
FIREBASE_PROJECT_ID=
FIREBASE_SERVER_KEY=

# Redis
REDIS_HOST=
REDIS_PASSWORD=
REDIS_PORT=

# ... and all other variables in your .env file
```

---

## 📝 Example: Full Production Email Setup

### Step 1: Sign up for SendGrid

1. Go to https://sendgrid.com
2. Create account (free tier: 100 emails/day)
3. Create API key

### Step 2: Update .env

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.xxxxxxxxxxxxx
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="Social Media App"
```

### Step 3: Apply Changes

```bash
# Clear config
docker exec inspirtag-app php artisan config:clear

# Restart queue workers
docker restart inspirtag-queue

# Or restart everything
docker-compose restart
```

### Step 4: Test

```bash
./test_otp_registration.sh
```

Check your email inbox for the OTP!

---

## 🎯 Important Notes

### ✅ What Works Automatically:

-   All `.env` variables are loaded into Docker containers
-   No need to rebuild containers when changing `.env`
-   Laravel automatically reads mail config from environment
-   Queue workers process OTP emails using your settings

### ⚠️ What You Need to Do:

-   Clear config cache after changing `.env`
-   Restart queue workers to pick up new config
-   Verify your SMTP credentials are correct
-   Test email sending before production

### 🔄 When to Restart:

You need to restart containers/workers when:

-   You change email settings in `.env`
-   You switch from `log` to `smtp` driver
-   You update SMTP credentials
-   Workers are stuck/not processing

### 💡 Pro Tip:

For development, keep `MAIL_MAILER=log` so you don't need real SMTP.
OTPs will be in `storage/logs/laravel.log` - easy to test!

---

## 🚨 Troubleshooting

### "Mail not sending"

```bash
# 1. Check .env has correct values
cat .env | grep MAIL_

# 2. Verify Docker container can see them
docker exec inspirtag-app env | grep MAIL_

# 3. Clear config cache
docker exec inspirtag-app php artisan config:clear

# 4. Restart queue workers
docker restart inspirtag-queue
```

### "Authentication failed"

```bash
# Check your SMTP credentials are correct
docker exec inspirtag-app php artisan tinker
```

Then:

```php
config('mail.mailers.smtp');
// Verify username and password are correct
```

### "Connection refused"

```bash
# Check if Docker can reach SMTP host
docker exec inspirtag-app ping smtp.sendgrid.net

# Check port is accessible
docker exec inspirtag-app telnet smtp.sendgrid.net 587
```

---

## 📚 Summary

✅ **Yes!** Your Docker setup already grabs email configuration from `.env`

✅ **How:** The `env_file: - .env` in `docker-compose.yml` does this automatically

✅ **Works for:** All containers (app, queue, scheduler, nginx)

✅ **To update:** Just edit `.env` and restart containers/workers

✅ **Current setup:** Using `log` driver (perfect for development)

✅ **Production:** Just update `.env` with real SMTP credentials

**No Docker configuration needed - it's already done!** 🎉
