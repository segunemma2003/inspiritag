# Email Setup Guide for OTP Authentication

## Quick Setup

Your application now uses **email-based OTP** for user registration and password reset. You need to configure email sending to make this work.

---

## Option 1: SMTP (Recommended for Production)

### Popular SMTP Providers:

#### 1. **Gmail** (Free for testing)
Add to your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Note:** For Gmail, you need to use an [App Password](https://support.google.com/accounts/answer/185833), not your regular password.

---

#### 2. **Mailtrap** (Best for Development/Testing)
Free service that catches emails so you can test without sending real emails.

Add to your `.env` file:
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

Sign up at: https://mailtrap.io

---

#### 3. **SendGrid** (Production)
Add to your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

Get API key at: https://sendgrid.com

---

#### 4. **Mailgun** (Production)
Add to your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-mailgun-username
MAIL_PASSWORD=your-mailgun-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

Sign up at: https://www.mailgun.com

---

#### 5. **Amazon SES** (Production - Low Cost)
Add to your `.env` file:
```env
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
```

---

## Option 2: Log Driver (Development Only)

For local development/testing without sending real emails:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

Emails will be saved to: `storage/logs/laravel.log`

---

## Quick Start (Development)

### Step 1: Use Mailtrap or Log Driver

**For Mailtrap (Recommended):**
1. Sign up at https://mailtrap.io (free)
2. Get your SMTP credentials from the inbox
3. Add to `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@socialapp.com
MAIL_FROM_NAME="Social Media App"
```

**OR For Log Driver (Quickest):**
```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@socialapp.com
MAIL_FROM_NAME="Social Media App"
```

### Step 2: Clear Config Cache
```bash
php artisan config:clear
php artisan config:cache
```

### Step 3: Start Queue Worker
OTP emails are queued for better performance:
```bash
php artisan queue:work
```

### Step 4: Test Registration
```bash
curl -X POST http://your-app.test/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Test User",
    "email": "test@example.com",
    "username": "testuser",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "terms_accepted": true
  }'
```

Check your Mailtrap inbox or `storage/logs/laravel.log` for the OTP!

---

## Production Setup

### Step 1: Choose a Provider
For production, use a dedicated email service:
- **SendGrid**: Free tier (100 emails/day)
- **Mailgun**: Free tier (10,000 emails/month)
- **Amazon SES**: Very cheap ($0.10 per 1,000 emails)

### Step 2: Update .env
Add your chosen provider's credentials (see examples above)

### Step 3: Setup Queue Worker
Use Supervisor to keep queue workers running:

Create `/etc/supervisor/conf.d/laravel-worker.conf`:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path-to-your-app/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=your-user
numprocs=2
redirect_stderr=true
stdout_logfile=/path-to-your-app/storage/logs/worker.log
stopwaitsecs=3600
```

### Step 4: Setup Scheduler
Add to crontab:
```bash
* * * * * cd /path-to-your-app && php artisan schedule:run >> /dev/null 2>&1
```

This runs the unverified user cleanup every 5 minutes.

---

## Testing Email Configuration

### Test Email Sending:
```bash
php artisan tinker
```

Then run:
```php
use App\Models\Otp;
use App\Notifications\SendOtpNotification;
use Illuminate\Support\Facades\Notification;

$otp = Otp::generateOTP();
Notification::route('mail', 'test@example.com')
    ->notify(new SendOtpNotification($otp, 'registration'));
```

Check your email inbox or logs!

---

## Troubleshooting

### Issue: "Connection refused"
- Check MAIL_HOST and MAIL_PORT are correct
- Verify firewall allows outbound connections on port 587/465

### Issue: "Authentication failed"
- Verify MAIL_USERNAME and MAIL_PASSWORD
- For Gmail, use App Password, not regular password
- Check if 2FA is enabled (required for Gmail)

### Issue: "Emails not sending"
- Ensure queue worker is running: `php artisan queue:work`
- Check logs: `tail -f storage/logs/laravel.log`
- Verify mail config: `php artisan config:cache`

### Issue: "OTP not received"
- Check spam folder
- Verify MAIL_FROM_ADDRESS is valid
- Some providers require domain verification

---

## Current Email Features

✅ **Registration OTP** - Sent when user registers  
✅ **Password Reset OTP** - Sent when user requests password reset  
✅ **10-minute expiration** - OTPs expire after 10 minutes  
✅ **Account cleanup** - Unverified accounts deleted after 30 minutes  
✅ **Queued emails** - Better performance with background processing  
✅ **Professional templates** - Clean, readable email format  

---

## Email Template Preview

Your users will receive emails like this:

**Subject:** Verify Your Email Address

```
Welcome to Social Media App!

Thank you for registering with us. Please use the following OTP to verify your email address:

123456

This OTP will expire in 10 minutes.

If your account is not verified within 30 minutes, it will be automatically deleted.

Regards,
Social Media App Team
```

---

## Environment Variables Summary

Add these to your `.env` file:

```env
# Email Configuration (Choose one provider above)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"

# Queue Configuration (for email sending)
QUEUE_CONNECTION=database
```

After updating `.env`:
```bash
php artisan config:clear
php artisan queue:work
```

---

## Next Steps

1. ✅ Choose an email provider (Mailtrap for dev, SendGrid/Mailgun for production)
2. ✅ Add credentials to `.env`
3. ✅ Run `php artisan config:clear`
4. ✅ Start queue worker: `php artisan queue:work`
5. ✅ Test registration endpoint
6. ✅ Check email/logs for OTP

Happy coding! 🚀

