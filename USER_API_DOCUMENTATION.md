# User API Documentation

## Overview

This document covers all user-related endpoints:

-   Authentication (register, login, logout, me, delete account, password reset, Firebase verification)
-   User profile (list, view, update)
-   Social graph (follow, unfollow, followers, following)
-   User search (by interests, by profession)
-   Device management (during auth and via `/api/devices` endpoints)

Unless noted as Public, endpoints require Sanctum auth with:

```
Authorization: Bearer <TOKEN>
Accept: application/json
```

---

## Authentication

### Register

-   Method: POST
-   Path: `/api/register`
-   Public

Request (JSON):

```json
{
    "full_name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "password": "Secret123!",
    "password_confirmation": "Secret123!",
    "terms_accepted": true,
    "device_token": "optional-device-token",
    "device_type": "android",
    "device_name": "Pixel 8",
    "app_version": "1.0.0",
    "os_version": "14"
}
```

Response 201:

```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            /* user object */
        },
        "token": "<SANCTUM_TOKEN>",
        "token_type": "Bearer"
    }
}
```

Validation: unique `email`, `username`; `password` min 8; `terms_accepted` required.

---

### Login

-   Method: POST
-   Path: `/api/login`
-   Public

Request (JSON):

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

Response 200:

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            /* user object */
        },
        "token": "<SANCTUM_TOKEN>",
        "token_type": "Bearer"
    }
}
```

Errors: 401 Invalid credentials; 422 Validation errors

---

### Logout

-   Method: POST
-   Path: `/api/logout`
-   Auth

Response 200:

```json
{ "success": true, "message": "Logged out successfully" }
```

---

### Me (Current user)

-   Method: GET
-   Path: `/api/me`
-   Auth

Response 200:

```json
{
    "success": true,
    "data": {
        "user": {
            /* user */
        }
    }
}
```

---

### Delete Account

-   Method: DELETE
-   Path: `/api/delete-account`
-   Auth

Behavior: Deletes user plus related `posts`, `likes`, `saves`, `notifications`, `bookings`, `businessAccount`.

Response 200:

```json
{ "success": true, "message": "Account deleted successfully" }
```

---

### Forgot Password (OTP)

-   Method: POST
-   Path: `/api/forgot-password`
-   Public

Request (JSON):

```json
{ "email": "john@example.com" }
```

Response 200:

```json
{
    "success": true,
    "message": "OTP sent to your email address",
    "data": { "otp": "123456", "expires_in": "10 minutes" }
}
```

Note: For dev/testing, OTP is logged; replace with email/SMS in prod.

---

### Reset Password

-   Method: POST
-   Path: `/api/reset-password`
-   Public

Request (JSON):

```json
{
    "email": "john@example.com",
    "token": "123456",
    "password": "NewPass123!",
    "password_confirmation": "NewPass123!"
}
```

Response 200:

```json
{
    "success": true,
    "message": "Password reset successfully. Please login with your new password."
}
```

Errors: 400 Invalid/expired token; 422 Validation

---

### Verify Firebase Token

-   Method: POST
-   Path: `/api/verify-firebase-token`
-   Public

Request (JSON):

```json
{
    "firebase_token": "<FIREBASE_ID_TOKEN>",
    "provider": "google",
    "email": "john@example.com",
    "name": "John Doe"
}
```

Response 200:

```json
{
    "success": true,
    "message": "Firebase authentication successful",
    "data": {
        "user": {
            /* user */
        },
        "token": "<SANCTUM_TOKEN>",
        "token_type": "Bearer"
    }
}
```

Note: Token verification is stubbed; integrate Firebase Admin in production.

---

## User Profiles

### List Users

-   Method: GET
-   Path: `/api/users`
-   Auth

Query params:

-   `q`: filter by `username` or `full_name`
-   `per_page` (≤ 50)

Response 200 (paginated):

```json
{
    "success": true,
    "data": {
        /* Laravel paginator */
    }
}
```

---

### View User

-   Method: GET
-   Path: `/api/users/{user}`
-   Auth

Response 200:

```json
{
    "success": true,
    "data": {
        /* user with public posts */
    }
}
```

---

### Update Profile

-   Method: PUT
-   Path: `/api/users/profile`
-   Auth
-   Content-Type: `multipart/form-data`

Fields:

-   `full_name` (string)
-   `username` (string, unique)
-   `bio` (string ≤ 500)
-   `profession` (string)
-   `profile_picture` (file: jpeg/png/jpg/gif, ≤ 2MB)
-   `interests` (array<string>)

Response 200:

```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        /* user */
    }
}
```

Notes: Existing S3 profile picture is deleted before saving a new one.

---

## Social Graph

### Follow User

-   Method: POST
-   Path: `/api/users/{user}/follow`
-   Auth

Response 200:

```json
{ "success": true, "message": "Successfully followed user" }
```

Errors: 400 Cannot follow yourself / Already following

---

### Unfollow User

-   Method: DELETE
-   Path: `/api/users/{user}/unfollow`
-   Auth

Response 200:

```json
{ "success": true, "message": "Successfully unfollowed user" }
```

Errors: 400 Not following this user

---

### Followers

-   Method: GET
-   Path: `/api/users/{user}/followers`
-   Auth

Response 200 (paginated):

```json
{
    "success": true,
    "data": {
        /* followers paginator */
    }
}
```

---

### Following

-   Method: GET
-   Path: `/api/users/{user}/following`
-   Auth

Response 200 (paginated):

```json
{
    "success": true,
    "data": {
        /* following paginator */
    }
}
```

---

## User Search

### Search by Interests

-   Method: POST
-   Path: `/api/users/search/interests`
-   Auth

Request (JSON):

```json
{ "interests": ["Makeup", "Fashion"], "per_page": 20 }
```

Response 200 (paginated):

```json
{
    "success": true,
    "data": {
        /* users */
    }
}
```

Validation: `interests` must be a non-empty array of strings

---

### Search by Profession

-   Method: POST
-   Path: `/api/users/search/profession`
-   Auth

Request (JSON):

```json
{ "profession": "Stylist", "username": "mike" }
```

Response 200 (paginated):

```json
{
    "success": true,
    "data": {
        /* users */
    }
}
```

---

## Interests (Public)

### Get Interests

-   Method: GET
-   Path: `/api/interests`
-   Public

Response 200:

```json
{ "success": true, "data": ["Hair Styling", "Makeup", "Fashion", ...] }
```

---

## Device Management (Related)

Alongside register/login, device information can be registered. Dedicated endpoints (all Auth) under `/api/devices`:

-   `POST /api/devices/register` — register a device
-   `GET /api/devices` — list devices
-   `PUT /api/devices/{device}` — update device
-   `PUT /api/devices/{device}/deactivate` — deactivate device
-   `DELETE /api/devices/{device}` — delete device

---

## Common Responses

### Validation Errors (422)

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": { "field": ["message"] }
}
```

### Unauthorized (401)

```json
{ "message": "Unauthenticated." }
```

### Rate Limiting

All protected routes use `throttle:60,1` (60 requests/minute per user).

---

## Notes & Best Practices

-   Include `Authorization: Bearer <token>` on protected endpoints
-   Use pagination (`per_page`) for lists
-   Profile updates with image must be `multipart/form-data`
-   Handle 400/401/422 errors in clients
-   Optimistically update follow/unfollow UI for better UX

---

## Posts Related to the User

### My Saved Posts

-   Method: GET
-   Paths (Auth):
    -   `/api/user-saved-posts` (primary)
    -   `/api/saved-posts` (alias)
    -   `/api/my-saved-posts` (alias)

Response 200 (paginated):

```json
{
    "success": true,
    "data": {
        /* posts paginator */
    }
}
```

---

### My Liked Posts

-   Method: GET
-   Paths (Auth):
    -   `/api/user-liked-posts` (primary)
    -   `/api/liked-posts` (alias)
    -   `/api/my-liked-posts` (alias)

Response 200 (paginated):

```json
{
    "success": true,
    "data": {
        /* posts paginator */
    }
}
```

---

### Like a Post

-   Method: POST
-   Path: `/api/posts/{post}/like`
-   Auth

Response 200:

```json
{ "success": true, "message": "Post liked" }
```

Idempotent behavior: multiple likes should not duplicate.

---

### Save a Post

-   Method: POST
-   Path: `/api/posts/{post}/save`
-   Auth

Response 200:

```json
{ "success": true, "message": "Post saved" }
```

---

### View a Post

-   Method: GET
-   Path: `/api/posts/{post}`
-   Auth

Response 200:

```json
{
    "success": true,
    "data": {
        /* post with relations */
    }
}
```

---

### Delete a Post

-   Method: DELETE
-   Path: `/api/posts/{post}`
-   Auth (owner only)

Response 200:

```json
{ "success": true, "message": "Post deleted successfully" }
```

---

### User's Uploaded Posts

There is no dedicated `GET /api/users/{user}/posts` route. Instead:

-   `GET /api/users/{user}` returns the user with their public posts (sorted latest).
-   To fetch your own public posts, call `GET /api/me` to get your `id`, then `GET /api/users/{id}`.

Example:

```bash
# 1) Who am I
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" \
  http://<HOST>/api/me

# 2) Fetch my public posts
curl -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" \
  http://<HOST>/api/users/<MY_ID>
```

Note: Private posts (if supported) are not returned by `/api/users/{user}`; only `is_public = true` are loaded.

---

## Profile Picture Upload (recap)

-   Method: PUT
-   Path: `/api/users/profile`
-   Content-Type: `multipart/form-data`
-   Field: `profile_picture` (image: jpeg/png/jpg/gif, ≤ 2MB)

Example (cURL):

```bash
curl -X PUT http://<HOST>/api/users/profile \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Accept: application/json" \
  -F "profile_picture=@/path/to/avatar.jpg" \
  -F "full_name=New Name" \
  -F "bio=Updated bio"
```

Behavior: Old profile image on S3 (if any) is deleted before saving the new one.
