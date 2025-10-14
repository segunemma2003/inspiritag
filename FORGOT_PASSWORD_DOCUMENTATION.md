# 🔐 Forgot Password API Documentation

Complete guide for implementing the forgot password feature in your application.

---

## 📋 Overview

The forgot password flow uses **OTP (One-Time Password)** sent via email to verify user identity before allowing password reset.

**Flow Summary:**

1. User requests password reset by providing email
2. System sends 6-digit OTP to user's email
3. User enters OTP and new password
4. Password is reset and all existing sessions are logged out

**Security Features:**

-   ✅ OTP expires in 10 minutes
-   ✅ Only verified emails can reset password
-   ✅ OTP is single-use (marked as used after successful reset)
-   ✅ All existing auth tokens are revoked after password reset
-   ✅ Password must meet security requirements (min 8 characters)

---

## 🔄 Complete Password Reset Flow

### Step 1: Request Password Reset OTP

**Endpoint:** `POST /api/forgot-password`

**Authentication:** None (Public endpoint)

**Request Body:**

```json
{
    "email": "user@example.com"
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

**Error Responses:**

**Email Not Found (422):**

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "email": ["The selected email is invalid."]
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

**Invalid Email Format (422):**

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "email": ["The email must be a valid email address."]
    }
}
```

---

### Step 2: Reset Password with OTP

**Endpoint:** `POST /api/reset-password`

**Authentication:** None (Public endpoint)

**Request Body:**

```json
{
    "email": "user@example.com",
    "otp": "123456",
    "password": "NewSecurePassword123!",
    "password_confirmation": "NewSecurePassword123!"
}
```

**Field Requirements:**

-   `email`: Valid email address that matches the OTP recipient
-   `otp`: Exactly 6 digits
-   `password`: Minimum 8 characters
-   `password_confirmation`: Must match password field

**Success Response (200):**

```json
{
    "success": true,
    "message": "Password reset successfully. Please login with your new password."
}
```

**Error Responses:**

**Invalid or Expired OTP (400):**

```json
{
    "success": false,
    "message": "Invalid or expired OTP"
}
```

**Validation Errors (422):**

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "otp": ["The otp field must be 6 characters."],
        "password": ["The password field must be at least 8 characters."],
        "password_confirmation": ["The password confirmation does not match."]
    }
}
```

---

## 💻 Implementation Examples

### Frontend - React/React Native

```javascript
// Step 1: Request Password Reset
const requestPasswordReset = async (email) => {
    try {
        const response = await fetch(
            "https://your-api.com/api/forgot-password",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ email }),
            }
        );

        const data = await response.json();

        if (response.ok) {
            // Show success message
            alert(data.message);
            // Navigate to OTP entry screen
            navigateToOTPScreen(email);
        } else {
            // Show error
            alert(data.message);
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Network error. Please try again.");
    }
};

// Step 2: Reset Password with OTP
const resetPassword = async (email, otp, password, passwordConfirmation) => {
    try {
        const response = await fetch(
            "https://your-api.com/api/reset-password",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    email,
                    otp,
                    password,
                    password_confirmation: passwordConfirmation,
                }),
            }
        );

        const data = await response.json();

        if (response.ok) {
            // Password reset successful
            alert(data.message);
            // Navigate to login screen
            navigateToLogin();
        } else {
            // Show error
            alert(data.message || "Password reset failed");
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Network error. Please try again.");
    }
};
```

---

### Flutter/Dart

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

// Step 1: Request Password Reset
Future<void> requestPasswordReset(String email) async {
  final url = Uri.parse('https://your-api.com/api/forgot-password');

  try {
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: json.encode({'email': email}),
    );

    final data = json.decode(response.body);

    if (response.statusCode == 200) {
      // Show success message
      print(data['message']);
      // Navigate to OTP screen
    } else {
      // Show error
      print(data['message']);
    }
  } catch (e) {
    print('Error: $e');
  }
}

// Step 2: Reset Password with OTP
Future<void> resetPassword(
  String email,
  String otp,
  String password,
  String passwordConfirmation,
) async {
  final url = Uri.parse('https://your-api.com/api/reset-password');

  try {
    final response = await http.post(
      url,
      headers: {'Content-Type': 'application/json'},
      body: json.encode({
        'email': email,
        'otp': otp,
        'password': password,
        'password_confirmation': passwordConfirmation,
      }),
    );

    final data = json.decode(response.body);

    if (response.statusCode == 200) {
      // Password reset successful
      print(data['message']);
      // Navigate to login screen
    } else {
      // Show error
      print(data['message']);
    }
  } catch (e) {
    print('Error: $e');
  }
}
```

---

### iOS - Swift

```swift
import Foundation

// Step 1: Request Password Reset
func requestPasswordReset(email: String, completion: @escaping (Bool, String) -> Void) {
    let url = URL(string: "https://your-api.com/api/forgot-password")!
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")

    let body: [String: Any] = ["email": email]
    request.httpBody = try? JSONSerialization.data(withJSONObject: body)

    URLSession.shared.dataTask(with: request) { data, response, error in
        guard let data = data,
              let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
              let success = json["success"] as? Bool,
              let message = json["message"] as? String else {
            completion(false, "Network error")
            return
        }

        completion(success, message)
    }.resume()
}

// Step 2: Reset Password with OTP
func resetPassword(email: String, otp: String, password: String, passwordConfirmation: String, completion: @escaping (Bool, String) -> Void) {
    let url = URL(string: "https://your-api.com/api/reset-password")!
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")

    let body: [String: Any] = [
        "email": email,
        "otp": otp,
        "password": password,
        "password_confirmation": passwordConfirmation
    ]
    request.httpBody = try? JSONSerialization.data(withJSONObject: body)

    URLSession.shared.dataTask(with: request) { data, response, error in
        guard let data = data,
              let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
              let success = json["success"] as? Bool,
              let message = json["message"] as? String else {
            completion(false, "Network error")
            return
        }

        completion(success, message)
    }.resume()
}

// Usage
requestPasswordReset(email: "user@example.com") { success, message in
    if success {
        print("OTP sent: \(message)")
        // Show OTP input screen
    } else {
        print("Error: \(message)")
    }
}

resetPassword(email: "user@example.com", otp: "123456", password: "NewPass123!", passwordConfirmation: "NewPass123!") { success, message in
    if success {
        print("Password reset: \(message)")
        // Navigate to login
    } else {
        print("Error: \(message)")
    }
}
```

---

### Android - Kotlin

```kotlin
import okhttp3.*
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.io.IOException

// Step 1: Request Password Reset
fun requestPasswordReset(email: String, callback: (Boolean, String) -> Unit) {
    val client = OkHttpClient()
    val mediaType = "application/json; charset=utf-8".toMediaType()

    val json = JSONObject().apply {
        put("email", email)
    }

    val body = json.toString().toRequestBody(mediaType)
    val request = Request.Builder()
        .url("https://your-api.com/api/forgot-password")
        .post(body)
        .build()

    client.newCall(request).enqueue(object : Callback {
        override fun onFailure(call: Call, e: IOException) {
            callback(false, "Network error")
        }

        override fun onResponse(call: Call, response: Response) {
            val responseData = response.body?.string()
            val jsonResponse = JSONObject(responseData ?: "{}")
            val success = jsonResponse.optBoolean("success", false)
            val message = jsonResponse.optString("message", "Unknown error")

            callback(success, message)
        }
    })
}

// Step 2: Reset Password with OTP
fun resetPassword(
    email: String,
    otp: String,
    password: String,
    passwordConfirmation: String,
    callback: (Boolean, String) -> Unit
) {
    val client = OkHttpClient()
    val mediaType = "application/json; charset=utf-8".toMediaType()

    val json = JSONObject().apply {
        put("email", email)
        put("otp", otp)
        put("password", password)
        put("password_confirmation", passwordConfirmation)
    }

    val body = json.toString().toRequestBody(mediaType)
    val request = Request.Builder()
        .url("https://your-api.com/api/reset-password")
        .post(body)
        .build()

    client.newCall(request).enqueue(object : Callback {
        override fun onFailure(call: Call, e: IOException) {
            callback(false, "Network error")
        }

        override fun onResponse(call: Call, response: Response) {
            val responseData = response.body?.string()
            val jsonResponse = JSONObject(responseData ?: "{}")
            val success = jsonResponse.optBoolean("success", false)
            val message = jsonResponse.optString("message", "Unknown error")

            callback(success, message)
        }
    })
}

// Usage
requestPasswordReset("user@example.com") { success, message ->
    if (success) {
        println("OTP sent: $message")
        // Show OTP input screen
    } else {
        println("Error: $message")
    }
}
```

---

## 🧪 Testing with cURL

### Step 1: Request Password Reset OTP

```bash
curl -X POST https://your-api.com/api/forgot-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com"
  }'
```

**Expected Response:**

```json
{
    "success": true,
    "message": "OTP sent to your email address",
    "data": {
        "otp_expires_in": "10 minutes"
    }
}
```

---

### Step 2: Reset Password with OTP

```bash
curl -X POST https://your-api.com/api/reset-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "otp": "123456",
    "password": "NewSecurePassword123!",
    "password_confirmation": "NewSecurePassword123!"
  }'
```

**Expected Response:**

```json
{
    "success": true,
    "message": "Password reset successfully. Please login with your new password."
}
```

---

## 📧 Email Templates

### OTP Email for Password Reset

**Subject:** Reset Your Password - OTP Inside

**Body:**

```
Hello,

You requested to reset your password. Use the following OTP to complete the process:

OTP: 123456

This OTP will expire in 10 minutes.

If you didn't request this, please ignore this email.

Thanks,
Your App Team
```

---

## 🔒 Security Best Practices

### Backend Security

1. **Rate Limiting**

    - Limit password reset requests per email (e.g., 3 per hour)
    - Prevents OTP spam attacks

2. **OTP Security**

    - ✅ 6-digit random OTP
    - ✅ Expires in 10 minutes
    - ✅ Single-use only
    - ✅ Invalidated when new OTP is requested

3. **Token Revocation**

    - ✅ All existing auth tokens are deleted after password reset
    - Forces re-login on all devices

4. **Email Verification Required**
    - Only verified email addresses can reset password
    - Prevents abuse of unverified accounts

### Frontend Security

1. **Input Validation**

    - Validate email format before sending
    - Ensure password meets requirements
    - Check password confirmation matches

2. **Error Handling**

    - Don't reveal if email exists or not (for security)
    - Show generic errors to prevent enumeration

3. **OTP Handling**
    - Never log or store OTP in frontend
    - Clear OTP from memory after use
    - Implement auto-submit when 6 digits entered

---

## 🎨 UX Best Practices

### User Experience Flow

1. **Forgot Password Screen**

    - Clear "Forgot Password?" link on login screen
    - Simple email input form
    - Clear error messages

2. **OTP Entry Screen**

    - Show email address for confirmation
    - 6 separate input boxes or single input with mask
    - Countdown timer showing expiration
    - "Resend OTP" button (enabled after 30 seconds)
    - "Change email" option

3. **New Password Screen**

    - Password strength indicator
    - Show/hide password toggle
    - Clear password requirements
    - Confirmation field

4. **Success Screen**
    - Clear success message
    - Automatic redirect to login (3-5 seconds)
    - Or manual "Go to Login" button

### Error Messages

**User-Friendly Messages:**

-   ❌ "Invalid or expired OTP" → ✅ "The code you entered is incorrect or has expired. Please try again or request a new code."
-   ❌ "Email not found" → ✅ "If this email exists, we've sent a password reset code to it."
-   ❌ "Validation failed" → ✅ "Please check your entries and try again."

---

## 🐛 Troubleshooting

### Common Issues

**Issue: OTP not received**

-   Check spam/junk folder
-   Verify email is spelled correctly
-   Wait 2-3 minutes for delivery
-   Check server email logs: `docker-compose logs app | grep OTP`

**Issue: OTP expired**

-   OTPs expire after 10 minutes
-   Request a new OTP using forgot-password endpoint again
-   Previous OTPs are automatically invalidated

**Issue: "Email not verified" error**

-   User must complete email verification first
-   Direct user to check registration email
-   Option to resend verification OTP

**Issue: Password reset successful but can't login**

-   Ensure user is using the NEW password
-   Old password won't work after reset
-   All previous sessions are logged out

---

## 📊 Monitoring & Logs

### Server Logs

```bash
# Check OTP generation logs
docker-compose logs app | grep "Password reset OTP"

# Check email sending
docker-compose logs app | grep "SendOtpNotification"

# Check password reset attempts
docker-compose logs app | grep "Password reset"
```

### Metrics to Monitor

-   Password reset request rate
-   OTP verification success rate
-   Time between OTP request and verification
-   Failed OTP attempts per email

---

## 🔄 Resend OTP

Users can request a new OTP by calling the forgot-password endpoint again:

```bash
curl -X POST https://your-api.com/api/forgot-password \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com"
  }'
```

**Behavior:**

-   Previous unused OTPs are invalidated
-   New OTP is generated and sent
-   10-minute expiration timer resets

---

## 📝 Summary

**Forgot Password Flow:**

1. User enters email → System sends OTP
2. User receives OTP via email (valid for 10 minutes)
3. User enters OTP + new password → Password is reset
4. All existing sessions are logged out
5. User logs in with new password

**Key Features:**

-   ✅ OTP-based verification
-   ✅ Email-only delivery
-   ✅ 10-minute expiration
-   ✅ Single-use OTPs
-   ✅ Automatic session logout
-   ✅ Requires verified email

**Endpoints:**

-   `POST /api/forgot-password` - Request OTP
-   `POST /api/reset-password` - Reset password with OTP

---

## 🚀 Production Checklist

Before going live:

-   [ ] Configure production SMTP server (see `EMAIL_SETUP_GUIDE.md`)
-   [ ] Test email delivery in production environment
-   [ ] Set up rate limiting for password reset endpoint
-   [ ] Implement monitoring/alerting for failed attempts
-   [ ] Create branded email template for OTP
-   [ ] Test full flow on all platforms (iOS, Android, Web)
-   [ ] Set up proper error tracking (Sentry, etc.)
-   [ ] Document internal troubleshooting procedures

---

## 📞 Support

For issues or questions:

-   Check `OTP_AUTHENTICATION_DOCUMENTATION.md` for general OTP info
-   Check `EMAIL_SETUP_GUIDE.md` for email configuration
-   Check `TROUBLESHOOTING_502_ERROR.md` for server issues

---

**Last Updated:** October 2025
**API Version:** 1.0
