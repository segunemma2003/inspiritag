# 🔐 Forgot Password Flow Documentation

## 📋 **Complete Forgot Password Flow**

### **Step 1: Request Password Reset**
**Endpoint:** `POST /api/forgot-password`

**Request Body:**
```json
{
    "email": "user@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password reset link sent to your email",
    "data": {
        "reset_link": "http://localhost:3000/reset-password?token=abc123&email=user@example.com",
        "expires_in": "1 hour"
    }
}
```

**What happens:**
1. ✅ Validates email exists in database
2. ✅ Generates secure 64-character token
3. ✅ Stores hashed token in `password_reset_tokens` table
4. ✅ Logs reset link (for testing)
5. ✅ Returns success response

---

### **Step 2: Reset Password**
**Endpoint:** `POST /api/reset-password`

**Request Body:**
```json
{
    "email": "user@example.com",
    "token": "abc123",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Password reset successfully. Please login with your new password."
}
```

**What happens:**
1. ✅ Validates all required fields
2. ✅ Checks token exists and is not expired (1 hour limit)
3. ✅ Verifies token matches stored hash
4. ✅ Updates user password
5. ✅ Deletes reset token
6. ✅ Revokes all existing user tokens for security

---

## 🔄 **Complete Flow Diagram**

```
User forgets password
        ↓
POST /api/forgot-password
        ↓
Email validation
        ↓
Generate secure token
        ↓
Store hashed token in DB
        ↓
Send email with reset link
        ↓
User clicks link
        ↓
POST /api/reset-password
        ↓
Token validation
        ↓
Password update
        ↓
Success - Login required
```

---

## 🛡️ **Security Features**

### **Token Security:**
- ✅ 64-character random token
- ✅ Token is hashed before storage
- ✅ 1-hour expiration time
- ✅ Single-use tokens (deleted after use)
- ✅ All existing tokens revoked on reset

### **Validation:**
- ✅ Email must exist in database
- ✅ Token must be valid and not expired
- ✅ Password confirmation required
- ✅ Minimum 8 characters password

### **Database Schema:**
```sql
CREATE TABLE password_reset_tokens (
    email VARCHAR(255) PRIMARY KEY,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL
);
```

---

## 🧪 **Testing the Flow**

### **Test 1: Request Reset**
```bash
curl -X POST http://[SERVER_IP]/api/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com"}'
```

### **Test 2: Reset Password**
```bash
curl -X POST http://[SERVER_IP]/api/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email":"test@example.com",
    "token":"your_token_here",
    "password":"newpassword123",
    "password_confirmation":"newpassword123"
  }'
```

---

## 📧 **Email Integration (Future)**

To implement actual email sending, you can:

1. **Configure Mail Driver** in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

2. **Create Email Template**:
```php
// app/Mail/PasswordResetMail.php
class PasswordResetMail extends Mailable
{
    public function build()
    {
        return $this->view('emails.password-reset')
                   ->with(['resetLink' => $this->resetLink]);
    }
}
```

3. **Send Email in Controller**:
```php
Mail::to($user->email)->send(new PasswordResetMail($resetLink));
```

---

## ⚠️ **Important Notes**

- **Development Mode:** Reset links are logged for testing
- **Production Mode:** Remove `reset_link` from response
- **Token Expiry:** 1 hour (configurable)
- **Security:** All existing tokens are revoked on password reset
- **Rate Limiting:** Apply rate limiting to prevent abuse

---

## 🚀 **Frontend Integration**

### **Step 1: Forgot Password Form**
```javascript
const requestReset = async (email) => {
  const response = await fetch('/api/forgot-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email })
  });
  return response.json();
};
```

### **Step 2: Reset Password Form**
```javascript
const resetPassword = async (email, token, password) => {
  const response = await fetch('/api/reset-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, token, password, password_confirmation: password })
  });
  return response.json();
};
```

---

## ✅ **Status: Complete**

The forgot password flow is now fully implemented with:
- ✅ Secure token generation
- ✅ Token validation and expiry
- ✅ Password reset endpoint
- ✅ Security measures
- ✅ Complete documentation
