# API Documentation (Index)

This is the master index of all API endpoints. See linked detailed docs for specific domains.

-   User APIs: see `USER_API_DOCUMENTATION.md`
-   Media Upload APIs: see `UPLOAD_DOCUMENTATION.md`

All protected endpoints require:

```
Authorization: Bearer <TOKEN>
Accept: application/json
```

---

## Conventions

-   Success response envelope:

```json
{
    "success": true,
    "message": "optional",
    "data": {
        /* resource or paginator */
    }
}
```

-   Error response envelope (422, 401, 400, etc.):

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": { "field": ["message"] }
}
```

-   Pagination shape (Laravel paginator):

```json
{
    "current_page": 1,
    "data": [
        /* items */
    ],
    "from": 1,
    "last_page": 10,
    "per_page": 20,
    "to": 20,
    "total": 200
}
```

---

## Auth

### POST `/api/register`

-   Body (JSON):
    -   `full_name` string required
    -   `email` string email required unique
    -   `username` string required unique
    -   `password` string min:8 required
    -   `password_confirmation` string required
    -   `terms_accepted` boolean required accepted
    -   `device_*` optional: `device_token`, `device_type` (android|ios|web), `device_name`, `app_version`, `os_version`
-   201 Response (data excerpt):

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "full_name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe"
        },
        "token": "<SANCTUM_TOKEN>",
        "token_type": "Bearer"
    }
}
```

### POST `/api/login`

-   Body (JSON): `email`, `password`, optional `device_*`
-   200 Response: same shape as register
-   401 Invalid credentials

### POST `/api/logout` (auth)

-   200: `{ "success": true, "message": "Logged out successfully" }`

### GET `/api/me` (auth)

-   200: `{ "success": true, "data": { "user": { /* user */ } } }`

### DELETE `/api/delete-account` (auth)

-   200: `{ "success": true, "message": "Account deleted successfully" }`

### POST `/api/forgot-password` (public)

-   Body: `email`
-   200: `{ "success": true, "data": { "otp": "123456", "expires_in": "10 minutes" } }`

### POST `/api/reset-password` (public)

-   Body: `email`, `token` (OTP), `password`, `password_confirmation`
-   200: message-only success

### POST `/api/verify-firebase-token` (public)

-   Body: `firebase_token`, `provider` (google|apple), optional `email`, `name`
-   200: `{ success, data: { user, token, token_type } }`

Details: `USER_API_DOCUMENTATION.md`

---

## Users

### GET `/api/users` (auth)

-   Query: `q` (username/full_name), `per_page` (≤50)
-   200: `{ success, data: <paginator<UserSummary>> }` where `UserSummary` includes `id, name, full_name, username, profile_picture, bio, profession, is_business, created_at`

### GET `/api/users/{user}` (auth)

-   Path: `{user}` numeric id (route-model-bound)
-   200: `{ success, data: <User> }` with `posts` preloaded (only `is_public=true`), newest first

### PUT `/api/users/profile` (auth, multipart)

-   Form fields: `full_name?`, `username?`, `bio?`, `profession?`, `interests[]?`, `profile_picture?` (jpeg/png/jpg/gif ≤2MB)
-   200: `{ success, message, data: <User> }`

### POST `/api/users/{user}/follow` (auth)

-   200: `{ success: true, message: "Successfully followed user" }`
-   400: self-follow or already following

### DELETE `/api/users/{user}/unfollow` (auth)

-   200: `{ success: true, message: "Successfully unfollowed user" }`
-   400: not following

### GET `/api/users/{user}/followers` (auth)

-   200: `{ success, data: <paginator<UserSummary>> }`

### GET `/api/users/{user}/following` (auth)

-   200: `{ success, data: <paginator<UserSummary>> }`

Search:

-   POST `/api/users/search/interests` — Body: `interests` array<string> required, `per_page?`; 200 paginator of users
-   POST `/api/users/search/profession` — Body: `profession` string required, `username?`, `per_page?`; 200 paginator of users

Public:

-   GET `/api/interests` — 200: `{ success, data: string[] }`

Details: `USER_API_DOCUMENTATION.md`

---

## Posts

### GET `/api/posts` (auth)

-   Query (from controller behavior): `category_id?`, `user_id?`, paging via default paginator
-   200: `{ success, data: <paginator<Post>> }` — feed filtered by following + public posts

`Post` fields (representative): `id, user_id, category_id, caption, media_url, media_type (image|video), location, is_public, created_at, user{...}, category{...}, tags[]`

### POST `/api/posts` (auth, multipart; ≤50MB)

-   Form: `media` (file), `caption?`, `category_id`, `tags[]?`, `location?`
-   200: `{ success, message: "Post created successfully", data: <PostWithRelations> }`
-   413 if body too large (use presigned uploads)

### GET `/api/posts/{post}` (auth)

-   200: `{ success, data: <PostWithRelations> }`

### DELETE `/api/posts/{post}` (auth, owner)

-   200: `{ success, message: "Post deleted successfully" }`

### POST `/api/posts/{post}/like` (auth)

-   200: `{ success: true, message: "Post liked" }`

### POST `/api/posts/{post}/save` (auth)

-   200: `{ success: true, message: "Post saved" }`

### Saved/Liked collections (auth)

-   GET `/api/user-saved-posts` (aliases: `/api/saved-posts`, `/api/my-saved-posts`)
-   GET `/api/user-liked-posts` (aliases: `/api/liked-posts`, `/api/my-liked-posts`)
-   200: `{ success, data: <paginator<Post>> }`

Search across posts:

-   POST `/api/posts/search/tags` — Body: `{ tags: string[] }`; 200: `{ success, data: <paginator<Post>> }`
-   GET `/api/search` — 200 search results (mixed), shape defined in `PostController@search`

Uploads (S3 presigned & chunked): see `UPLOAD_DOCUMENTATION.md`

-   POST `/api/posts/upload-url` — Body: `filename`, `content_type`, `file_size`(bytes); 200: direct or chunked payload with `upload_url` or chunk plan
-   POST `/api/posts/create-from-s3` — Body: `file_path`, optional `thumbnail_path`, `caption`, `category_id`, `tags[]`, `location`, `media_metadata{}`; 200: created `post`
-   POST `/api/posts/chunked-upload-url` — Body: `filename`, `content_type`, `total_size`, `chunk_size`; 200: chunk URLs
-   POST `/api/posts/complete-chunked-upload` — Body: `file_path`, `total_chunks`; 200: assembled file info

---

## Categories

-   GET `/api/categories` (public) — 200: `{ success, data: Category[] }`
-   POST `/api/categories` (admin) — Body: category fields; 200 created
-   PUT `/api/categories/{category}` (admin) — update
-   DELETE `/api/categories/{category}` (admin) — delete

`Category` fields: `id, name, color, icon, created_at`

---

## Business Accounts

-   GET `/api/business-accounts` (auth) — paginator of accounts
-   POST `/api/business-accounts` (auth) — create
-   GET `/api/business-accounts/{businessAccount}` (auth) — view
-   PUT `/api/business-accounts/{businessAccount}` (auth) — update
-   DELETE `/api/business-accounts/{businessAccount}` (auth) — delete
-   POST `/api/business-accounts/{businessAccount}/bookings` (auth) — create booking
-   GET `/api/business-accounts/{businessAccount}/bookings` (auth) — list bookings

`BusinessAccount` fields: `id, user_id, name, description, contact_info, ...`
`Booking` fields: `id, business_account_id, user_id, date_time, notes, status, ...`

---

## Notifications

Simple routes:

-   GET `/api/notifications` — list
-   POST `/api/notifications/{notification}/read` — mark one read
-   POST `/api/notifications/read-all` — mark all read
-   GET `/api/notifications/unread-count` — unread count

Extended routes (prefixed group):

-   GET `/api/notifications/` — list
-   GET `/api/notifications/unread-count` — unread count
-   GET `/api/notifications/statistics` — statistics
-   PUT `/api/notifications/{notification}/read` — read
-   PUT `/api/notifications/{notification}/unread` — unread
-   PUT `/api/notifications/mark-all-read` — mark all read
-   PUT `/api/notifications/mark-multiple-read` — mark multiple read
-   DELETE `/api/notifications/{notification}` — delete one
-   DELETE `/api/notifications/` — delete all
-   POST `/api/notifications/test` — send test

`Notification` fields: `id, user_id, type, title, body, data (json), read_at, created_at`

---

## Devices

-   POST `/api/devices/register` — Body: `device_token`, `device_type`, `device_name`, `app_version`, `os_version`
-   GET `/api/devices` — list devices
-   PUT `/api/devices/{device}` — update fields above
-   PUT `/api/devices/{device}/deactivate` — set `is_active=false`
-   DELETE `/api/devices/{device}` — remove device

`Device` fields: `id, user_id, device_token, device_type, device_name, app_version, os_version, is_active, last_used_at`

---

## Health & Ops

-   GET `/health` — simple healthcheck (deployment)

---

## Notes

-   Rate limiting on protected routes: `throttle:60,1`
-   Always include `Accept: application/json` and `Authorization` where required
-   Upload request/response bodies are fully documented in `UPLOAD_DOCUMENTATION.md`
-   User flows are fully documented in `USER_API_DOCUMENTATION.md`
