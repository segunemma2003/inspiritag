# OTP Authentication Documentation

## Overview

This social media platform uses OTP (One-Time Password) based email verification for **custom email/password registration only**. Social authentication (Google, Apple) automatically verifies emails.

### Key Features:
- ✅ 6-digit OTP sent via email
- ⏱️ OTP expires after **10 minutes**
- 🗑️ Unverified accounts are deleted after **30 minutes**
- 🔄 OTP can be resent
- 🔐 Used for both registration verification and password reset
- 🌐 Social auth (Firebase/Google/Apple) skips OTP - emails are auto-verified

---

## Registration Flow (Custom Email/Password)

### 1. Register User
**Endpoint:** `POST /api/register`

**Request:**
```json
{
    "full_name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "password": "Secret123!",
    "password_confirmation": "Secret123!",
    "terms_accepted": true
}
```

**Response (201):**
```json
{
    "success": true,
    "message": "Registration successful. Please check your email for OTP to verify your account.",
    "data": {
        "email": "john@example.com",
        "otp_expires_in": "10 minutes",
        "account_expires_in": "30 minutes if not verified"
    }
}
```

**Notes:**
- User account is created but **cannot login** until email is verified
- OTP is sent to the provided email address
- User has 10 minutes to use the OTP
- Account will be **automatically deleted** if not verified within 30 minutes

---

### 2. Verify OTP
**Endpoint:** `POST /api/verify-otp`

**Request:**
```json
{
    "email": "john@example.com",
    "otp": "123456",
    "device_token": "optional-device-token",
    "device_type": "android",
    "device_name": "Pixel 8",
    "app_version": "1.0.0",
    "os_version": "14"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Email verified successfully. You can now login.",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe",
            "email_verified_at": "2025-10-13T18:35:45.000000Z"
        },
        "token": "<SANCTUM_TOKEN>",
        "token_type": "Bearer"
    }
}
```

**Error Responses:**

Invalid/Expired OTP (400):
```json
{
    "success": false,
    "message": "Invalid or expired OTP"
}
```

---

### 3. Resend OTP
**Endpoint:** `POST /api/resend-otp`

**Request:**
```json
{
    "email": "john@example.com",
    "type": "registration"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "OTP sent successfully",
    "data": {
        "otp_expires_in": "10 minutes"
    }
}
```

**Notes:**
- Type can be: `registration` or `password_reset`
- Previous unused OTPs are invalidated when a new one is generated
- Cannot resend if email is already verified

---

## Login Flow

### Login (Email/Password)
**Endpoint:** `POST /api/login`

**Request:**
```json
{
    "email": "john@example.com",
    "password": "Secret123!",
    "device_token": "optional-device-token",
    "device_type": "android",
    "device_name": "Pixel 8",
    "app_version": "1.0.0",
    "os_version": "14"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": { /* user object */ },
        "token": "<SANCTUM_TOKEN>",
        "token_type": "Bearer"
    }
}
```

**Email Not Verified (403):**
```json
{
    "success": false,
    "message": "Please verify your email address before logging in. Check your email for OTP.",
    "error_code": "EMAIL_NOT_VERIFIED"
}
```

**Invalid Credentials (401):**
```json
{
    "success": false,
    "message": "Invalid credentials"
}
```

---

## Password Reset Flow

### 1. Request Password Reset OTP
**Endpoint:** `POST /api/forgot-password`

**Request:**
```json
{
    "email": "john@example.com"
}
```

**Success Response (200):**
```json
{
    "success": true,
    "message": "OTP sent to your email address",
    "data": {
        "otp_expires_in": "10 minutes"
    }
}
```

**Email Not Verified (403):**
```json
{
    "success": false,
    "message": "Please verify your email address first"
}
```

**Notes:**
- Only works for verified accounts
- OTP expires in 10 minutes

---

### 2. Reset Password with OTP
**Endpoint:** `POST /api/reset-password`

**Request:**
```json
{
    "email": "john@example.com",
    "otp": "123456",
    "password": "NewSecret123!",
    "password_confirmation": "NewSecret123!"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Password reset successfully. Please login with your new password."
}
```

**Invalid/Expired OTP (400):**
```json
{
    "success": false,
    "message": "Invalid or expired OTP"
}
```

**Notes:**
- All existing auth tokens are revoked after password reset for security
- User must login again with new password

---

## Social Authentication (Firebase/Google/Apple)

### Firebase Token Verification
**Endpoint:** `POST /api/verify-firebase-token`

**Request:**
```json
{
    "firebase_token": "<FIREBASE_ID_TOKEN>",
    "provider": "google",
    "email": "john@example.com",
    "name": "John Doe"
}
```

**Response (200):**
```json
{
    "success": true,
    "message": "Firebase authentication successful",
    "data": {
        "user": { /* user object */ },
        "token": "<SANCTUM_TOKEN>",
        "token_type": "Bearer"
    }
}
```

**Notes:**
- ✅ **No OTP required** - emails are automatically verified for social auth
- If user doesn't exist, a new account is created with email pre-verified
- If user exists but email not verified, it gets verified automatically
- Supported providers: `google`, `apple`

---

## Account Cleanup

### Automatic Deletion
- Unverified accounts are **automatically deleted** 30 minutes after registration
- Runs every 5 minutes via scheduled command: `users:delete-unverified`
- Expired OTPs are also cleaned up periodically

### Manual Testing
You can manually run the cleanup command:
```bash
php artisan users:delete-unverified
```

---

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `EMAIL_NOT_VERIFIED` | 403 | User attempted login without verifying email |
| Validation errors | 422 | Invalid request data |
| Invalid credentials | 401 | Wrong email/password combination |
| Invalid OTP | 400 | OTP is incorrect or has expired |

---

## Security Features

1. **OTP Expiration:** OTPs expire after 10 minutes
2. **Account Cleanup:** Unverified accounts deleted after 30 minutes
3. **Single Use:** OTPs can only be used once
4. **Token Invalidation:** Old OTPs are invalidated when new ones are generated
5. **Session Revocation:** All tokens revoked on password reset
6. **Verified Email Requirement:** Users must verify email before login (custom auth only)

---

## Email Configuration

The system sends OTP emails using Laravel's notification system. Configure your mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="Your App Name"
```

For development, you can use `MAIL_MAILER=log` to log emails instead of sending them.

---

## Testing Workflow

### Test Registration Flow:
1. POST `/api/register` → Get "check your email" message
2. Check email for 6-digit OTP
3. POST `/api/verify-otp` with OTP → Get auth token
4. User can now login

### Test Password Reset Flow:
1. POST `/api/forgot-password` → OTP sent to email
2. POST `/api/reset-password` with OTP → Password updated
3. POST `/api/login` with new password → Success

### Test Social Auth Flow:
1. POST `/api/verify-firebase-token` → Immediate access, no OTP needed
2. Email automatically verified

---

## Queue Configuration

OTP emails are queued for better performance. Make sure your queue worker is running:

```bash
php artisan queue:work
```

For production, use Supervisor or similar process manager to keep queue workers running.

---

## Scheduler Configuration

The cleanup command runs automatically via Laravel's scheduler. Ensure the scheduler is running:

Add to crontab:
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or use Laravel Forge/Vapor for automatic scheduling.

