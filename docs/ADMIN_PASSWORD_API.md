# Admin Password Change API Documentation

_Last updated: {{DATE}}_

## Overview

This document describes the Admin API endpoint for changing passwords. The endpoint allows authenticated admin users to change their own password while maintaining their current session.

**Base URL**: `/api/admin/v1`  
**Authentication**: Bearer token (admin users only)  
**Response Format**: JSON with standard wrapper structure

---

## Table of Contents

1. [Change Password](#1-change-password)

---

## 1. Change Password

Allow an admin user to change their password while keeping their current session active.

**Endpoint**: `POST /api/admin/v1/change-password`

**Authentication**: Required (Admin)

**Request Body**:

```json
{
    "current_password": "your_current_password",
    "password": "new_secure_password",
    "password_confirmation": "new_secure_password"
}
```

**Request Parameters**:

-   `current_password` (required, string): The user's current password
-   `password` (required, string, min:8): The new password (minimum 8 characters)
-   `password_confirmation` (required, string): Confirmation of the new password (must match `password`)

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Password changed successfully"
}
```

**Example Request**:

```bash
POST /api/admin/v1/change-password
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "current_password": "admin123",
  "password": "newSecurePassword123",
  "password_confirmation": "newSecurePassword123"
}
```

---

## Error Responses

### 401 Unauthorized

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

### 403 Forbidden

```json
{
    "success": false,
    "message": "Unauthorized. Admin access required."
}
```

### 422 Unprocessable Entity - Incorrect Current Password

```json
{
    "success": false,
    "message": "Current password is incorrect"
}
```

### 422 Unprocessable Entity - Same Password

```json
{
    "success": false,
    "message": "New password must be different from current password"
}
```

### 422 Unprocessable Entity - Validation Errors

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "current_password": ["The current password field is required."],
        "password": [
            "The password must be at least 8 characters.",
            "The password confirmation does not match."
        ]
    }
}
```

---

## Notes

1. **Authentication**: The endpoint requires authentication via Bearer token and admin privileges (`is_admin = true`).

2. **Current Password Verification**: The endpoint verifies the current password before allowing the change. If the current password is incorrect, the request will be rejected.

3. **Password Requirements**:

    - Minimum 8 characters
    - Must include a confirmation field that matches the new password
    - Must be different from the current password

4. **Security Features**:

    - **Current Session Maintained**: The admin user's current session (token) remains active after password change
    - **Other Sessions Invalidated**: All other active tokens for the user are automatically deleted, forcing re-login on other devices
    - This provides a balance between convenience (staying logged in) and security (invalidating other sessions)

5. **Password Validation**:

    - The endpoint checks that the new password is different from the current password
    - Password confirmation must exactly match the new password
    - Password must meet minimum length requirements (8 characters)

6. **Session Management**:
    - After a successful password change, the current token remains valid
    - All other tokens for the user are deleted for security
    - This means if the admin is logged in on multiple devices, they will be logged out on all other devices except the current one

---

## Complete Example Workflow

### Step 1: Login as Admin

```bash
POST /api/login
{
  "email": "admin@inspirtag.com",
  "password": "admin123"
}

Response:
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { ... },
    "token": "1|abc123...",
    "token_type": "Bearer"
  }
}
```

### Step 2: Change Password

```bash
POST /api/admin/v1/change-password
Authorization: Bearer 1|abc123...
Content-Type: application/json

{
  "current_password": "admin123",
  "password": "newSecurePassword123",
  "password_confirmation": "newSecurePassword123"
}

Response:
{
  "success": true,
  "message": "Password changed successfully"
}
```

### Step 3: Continue Using Current Session

The admin can continue using the same token (`1|abc123...`) for subsequent API calls. However, any tokens issued to other devices/sessions will no longer work.

---

## Security Considerations

1. **Password Strength**: While the API enforces a minimum length of 8 characters, it's recommended that admins use strong passwords with:

    - Mix of uppercase and lowercase letters
    - Numbers and special characters
    - Avoid common words or patterns

2. **Token Invalidation**: When a password is changed, all other tokens are invalidated. This is a security measure to prevent unauthorized access if a token was compromised before the password change.

3. **Current Session**: The current session is maintained to provide a smooth user experience. However, admins should be aware that if they suspect their account has been compromised, they should change their password immediately.

4. **Password Storage**: Passwords are hashed using Laravel's bcrypt algorithm before being stored in the database.

---

## Integration Notes

### Frontend Implementation

When implementing the password change feature in the admin dashboard:

1. **Form Validation**: Validate the form on the frontend before sending the request:

    - Ensure password is at least 8 characters
    - Verify password confirmation matches
    - Check that new password differs from current password

2. **User Feedback**: Provide clear feedback:

    - Success message when password is changed
    - Error messages for validation failures
    - Specific error for incorrect current password

3. **Session Handling**: Note that after password change:
    - The current session remains active
    - The user does not need to log in again
    - Other devices will require re-authentication

### Example Frontend Code (JavaScript/Axios)

```javascript
async function changePassword(
    currentPassword,
    newPassword,
    passwordConfirmation
) {
    try {
        const response = await axios.post(
            "/api/admin/v1/change-password",
            {
                current_password: currentPassword,
                password: newPassword,
                password_confirmation: passwordConfirmation,
            },
            {
                headers: {
                    Authorization: `Bearer ${adminToken}`,
                    "Content-Type": "application/json",
                },
            }
        );

        if (response.data.success) {
            // Show success message
            alert("Password changed successfully!");
            // Optionally refresh or redirect
        }
    } catch (error) {
        if (error.response) {
            // Handle specific error messages
            const message = error.response.data.message;
            if (message === "Current password is incorrect") {
                alert("Your current password is incorrect.");
            } else if (
                message ===
                "New password must be different from current password"
            ) {
                alert(
                    "Please choose a different password from your current one."
                );
            } else {
                // Handle validation errors
                const errors = error.response.data.errors;
                // Display errors to user
            }
        }
    }
}
```

---

## API Endpoint Summary

| Method | Endpoint                    | Description           | Auth Required |
| ------ | --------------------------- | --------------------- | ------------- |
| POST   | `/admin/v1/change-password` | Change admin password | Yes (Admin)   |

---

## Related Endpoints

-   **Login**: `POST /api/login` - Login to obtain authentication token
-   **Logout**: `POST /api/logout` - Logout and invalidate current token
-   **Reset Password (Public)**: `POST /api/reset-password` - Reset password via OTP (for forgot password flow)

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20
