# 🚀 Social Media API Documentation

## Base

```

## Authentication

All protected endpoints require Bearer token authentication:

```

Authorization: Bearer {your_token}

````

---

## 🔐 Authentication Endpoints

### Register User

**POST** `/register`

Register a new user account.

**Request Body:**

```json
{
    "full_name": "John Doe",
    "email": "john@example.com",
    "username": "johndoe",
    "password": "password123",
    "password_confirmation": "password123",
    "terms_accepted": true,
    "device_token": "firebase_token_here", // Optional
    "device_type": "android", // Optional: android, ios, web
    "device_name": "Samsung Galaxy S21", // Optional
    "app_version": "1.0.0", // Optional
    "os_version": "Android 12" // Optional
}
````

**Response:**

```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### Login User

**POST** `/login`

Authenticate user and return access token.

**Request Body:**

```json
{
    "email": "john@example.com",
    "password": "password123",
    "device_token": "firebase_token_here", // Optional
    "device_type": "android", // Optional
    "device_name": "Samsung Galaxy S21", // Optional
    "app_version": "1.0.0", // Optional
    "os_version": "Android 12" // Optional
}
```

**Response:**

```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe"
        },
        "token": "1|abc123...",
        "token_type": "Bearer"
    }
}
```

### Logout

**POST** `/logout`

Revoke current access token.

**Response:**

```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### Get Current User

**GET** `/me`

Get current authenticated user information.

**Response:**

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe",
            "profile_picture": "https://example.com/avatar.jpg",
            "bio": "Photography enthusiast",
            "followers_count": 150,
            "following_count": 75
        }
    }
}
```

### Forgot Password

**POST** `/forgot-password`

Send password reset email.

**Request Body:**

```json
{
    "email": "john@example.com"
}
```

### Reset Password

**POST** `/reset-password`

Reset password using token.

**Request Body:**

```json
{
    "token": "reset_token_here",
    "email": "john@example.com",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
}
```

### Delete Account

**DELETE** `/delete-account`

Permanently delete user account.

**Response:**

```json
{
    "success": true,
    "message": "Account deleted successfully"
}
```

---

## 👥 User Endpoints

### Get All Users

**GET** `/users`

Get paginated list of users.

**Query Parameters:**

-   `page` (optional): Page number
-   `per_page` (optional): Items per page (max 50)
-   `search` (optional): Search by name or username

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "profile_picture": "https://example.com/avatar.jpg",
                "bio": "Photography enthusiast",
                "followers_count": 150,
                "following_count": 75,
                "is_following": false
            }
        ],
        "total": 100
    }
}
```

### Get User Profile

**GET** `/users/{user}`

Get specific user profile.

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "username": "johndoe",
        "profile_picture": "https://example.com/avatar.jpg",
        "bio": "Photography enthusiast",
        "followers_count": 150,
        "following_count": 75,
        "posts_count": 25,
        "is_following": false,
        "is_followed_by": false
    }
}
```

### Update Profile

**PUT** `/users/profile`

Update current user's profile.

**Request Body:**

```json
{
    "name": "John Doe",
    "bio": "Updated bio",
    "profile_picture": "https://example.com/new-avatar.jpg",
    "profession": "Photographer"
}
```

### Follow User

**POST** `/users/{user}/follow`

Follow a user.

**Response:**

```json
{
    "success": true,
    "message": "User followed successfully",
    "data": {
        "is_following": true,
        "followers_count": 151
    }
}
```

### Unfollow User

**DELETE** `/users/{user}/unfollow`

Unfollow a user.

**Response:**

```json
{
    "success": true,
    "message": "User unfollowed successfully",
    "data": {
        "is_following": false,
        "followers_count": 150
    }
}
```

### Get User Followers

**GET** `/users/{user}/followers`

Get user's followers list.

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 2,
            "name": "Jane Smith",
            "username": "janesmith",
            "profile_picture": "https://example.com/avatar2.jpg",
            "is_following": true
        }
    ]
}
```

### Get User Following

**GET** `/users/{user}/following`

Get users that this user is following.

### Search Users by Interests

**POST** `/users/search/interests`

**Request Body:**

```json
{
    "interests": ["photography", "travel", "food"]
}
```

### Search Users by Profession

**POST** `/users/search/profession`

**Request Body:**

```json
{
    "profession": "photographer",
    "username": "john" // Optional
}
```

---

## 📱 Post Endpoints

### Get Posts

**GET** `/posts`

Get paginated list of posts with filtering.

**Query Parameters:**

-   `page` (optional): Page number
-   `per_page` (optional): Items per page (max 50)
-   `tags` (optional): Filter by tags (comma-separated)
-   `creators` (optional): Filter by creator IDs (comma-separated)
-   `categories` (optional): Filter by category IDs (comma-separated)
-   `search` (optional): Search in captions
-   `media_type` (optional): Filter by media type (image, video)
-   `sort_by` (optional): Sort by field (created_at, likes_count, saves_count)
-   `sort_order` (optional): Sort order (asc, desc)

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "caption": "Beautiful sunset!",
                "media_url": "https://example.com/image.jpg",
                "media_type": "image",
                "thumbnail_url": "https://example.com/thumb.jpg",
                "likes_count": 25,
                "saves_count": 10,
                "comments_count": 5,
                "is_liked": true,
                "is_saved": false,
                "user": {
                    "id": 1,
                    "name": "John Doe",
                    "username": "johndoe",
                    "profile_picture": "https://example.com/avatar.jpg"
                },
                "category": {
                    "id": 1,
                    "name": "Photography",
                    "color": "#FF6B6B"
                },
                "tags": [
                    { "id": 1, "name": "sunset", "slug": "sunset" },
                    { "id": 2, "name": "nature", "slug": "nature" }
                ],
                "created_at": "2025-01-01T12:00:00Z"
            }
        ],
        "total": 100
    }
}
```

### Create Post

**POST** `/posts`

Create a new post.

**Request Body:**

```json
{
    "caption": "Beautiful sunset!",
    "media_url": "https://example.com/image.jpg",
    "media_type": "image",
    "thumbnail_url": "https://example.com/thumb.jpg",
    "category_id": 1,
    "tags": ["sunset", "nature"],
    "location": "Beach, California",
    "media_metadata": {
        "width": 1920,
        "height": 1080,
        "size": 2048000
    }
}
```

### Get Post

**GET** `/posts/{post}`

Get specific post details.

### Delete Post

**DELETE** `/posts/{post}`

Delete a post (only by owner).

### Like Post

**POST** `/posts/{post}/like`

Like or unlike a post.

**Response:**

```json
{
    "success": true,
    "message": "Post liked",
    "data": {
        "liked": true,
        "likes_count": 26
    }
}
```

### Save Post

**POST** `/posts/{post}/save`

Save or unsave a post.

**Response:**

```json
{
    "success": true,
    "message": "Post saved",
    "data": {
        "saved": true,
        "saves_count": 11
    }
}
```

### Get Saved Posts

**GET** `/posts/saved`

Get current user's saved posts.

### Get Liked Posts

**GET** `/posts/liked`

Get current user's liked posts.

### Search Posts by Tags

**POST** `/posts/search/tags`

**Request Body:**

```json
{
    "tags": ["sunset", "nature", "photography"]
}
```

### Search Posts

**GET** `/search`

General search endpoint for posts and users.

---

## 📤 File Upload Endpoints

### Get Upload URL (Direct S3 Upload)

**POST** `/posts/upload-url`

Get presigned URL for direct S3 upload.

**Request Body:**

```json
{
    "filename": "video.mp4",
    "content_type": "video/mp4",
    "file_size": 104857600
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "upload_url": "https://s3.amazonaws.com/bucket/presigned-url",
        "file_path": "posts/1234567890_1_abc123.mp4",
        "file_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4",
        "expires_in": 3600,
        "max_file_size": 2147483648
    }
}
```

### Create Post from S3

**POST** `/posts/create-from-s3`

Create post after successful S3 upload.

**Request Body:**

```json
{
    "file_path": "posts/1234567890_1_abc123.mp4",
    "thumbnail_path": "thumbnails/1234567890_1_abc123.jpg", // Optional
    "caption": "My awesome video!",
    "category_id": 1,
    "tags": ["video", "awesome"],
    "location": "My Studio",
    "media_metadata": {
        "duration": 120,
        "width": 1920,
        "height": 1080,
        "size": 104857600
    }
}
```

### Get Chunked Upload URL

**POST** `/posts/chunked-upload-url`

Get presigned URLs for chunked upload.

**Request Body:**

```json
{
    "filename": "large_video.mp4",
    "content_type": "video/mp4",
    "total_size": 1073741824,
    "chunk_size": 52428800
}
```

### Complete Chunked Upload

**POST** `/posts/complete-chunked-upload`

Complete chunked upload process.

---

## 🏢 Business Account Endpoints

### Get Business Accounts

**GET** `/business-accounts`

Get list of business accounts.

### Create Business Account

**POST** `/business-accounts`

**Request Body:**

```json
{
    "business_name": "Photography Studio",
    "description": "Professional photography services",
    "category": "Photography",
    "location": "New York, NY",
    "contact_email": "contact@studio.com",
    "contact_phone": "+1234567890",
    "website": "https://studio.com",
    "services": ["Portrait Photography", "Event Photography"],
    "pricing": {
        "portrait": 150,
        "event": 300
    }
}
```

### Get Business Account

**GET** `/business-accounts/{businessAccount}`

### Update Business Account

**PUT** `/business-accounts/{businessAccount}`

### Delete Business Account

**DELETE** `/business-accounts/{businessAccount}`

### Create Booking

**POST** `/business-accounts/{businessAccount}/bookings`

**Request Body:**

```json
{
    "service": "Portrait Photography",
    "date": "2025-01-15",
    "time": "14:00",
    "duration": 120,
    "message": "Looking for a professional photo session"
}
```

### Get Bookings

**GET** `/business-accounts/{businessAccount}/bookings`

---

## 🔔 Notification Endpoints

### Get Notifications

**GET** `/notifications`

Get user's notifications with filtering.

**Query Parameters:**

-   `page` (optional): Page number
-   `per_page` (optional): Items per page (max 50)
-   `type` (optional): Filter by notification type
-   `is_read` (optional): Filter by read status (true/false)

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "type": "post_liked",
                "title": "John Doe liked your post",
                "body": "Your photo got a new like!",
                "data": {
                    "post_id": 1,
                    "liker_id": 2,
                    "liker_name": "John Doe"
                },
                "is_read": false,
                "read_at": null,
                "created_at": "2025-01-01T12:00:00Z"
            }
        ],
        "total": 25
    },
    "unread_count": 8
}
```

### Get Unread Count

**GET** `/notifications/unread-count`

**Response:**

```json
{
    "success": true,
    "data": {
        "unread_count": 8
    }
}
```

### Mark Notification as Read

**PUT** `/notifications/{notification}/read`

### Mark Notification as Unread

**PUT** `/notifications/{notification}/unread`

### Mark All as Read

**PUT** `/notifications/mark-all-read`

### Mark Multiple as Read

**PUT** `/notifications/mark-multiple-read`

**Request Body:**

```json
{
    "notification_ids": [1, 2, 3, 4]
}
```

### Delete Notification

**DELETE** `/notifications/{notification}`

### Delete All Notifications

**DELETE** `/notifications`

### Get Notification Statistics

**GET** `/notifications/statistics`

**Response:**

```json
{
    "success": true,
    "data": {
        "total": 25,
        "unread": 8,
        "read": 17,
        "by_type": {
            "post_liked": 10,
            "new_follower": 5,
            "post_saved": 3,
            "profile_visit": 7
        }
    }
}
```

### Send Test Notification

**POST** `/notifications/test`

**Request Body:**

```json
{
    "message": "This is a test notification"
}
```

---

## 📱 Device Management Endpoints

### Register Device

**POST** `/devices/register`

Register a new device for push notifications.

**Request Body:**

```json
{
    "device_token": "firebase_device_token_here",
    "device_type": "android", // android, ios, web
    "device_name": "Samsung Galaxy S21",
    "app_version": "1.0.0",
    "os_version": "Android 12"
}
```

### Get User's Devices

**GET** `/devices`

Get all devices registered for current user.

### Update Device

**PUT** `/devices/{device}`

**Request Body:**

```json
{
    "device_name": "Updated Device Name",
    "app_version": "1.1.0",
    "os_version": "Android 13",
    "is_active": true
}
```

### Deactivate Device

**PUT** `/devices/{device}/deactivate`

### Delete Device

**DELETE** `/devices/{device}`

---

## 🏷️ Category Endpoints

### Get Categories

**GET** `/categories`

Get all available categories.

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Photography",
            "slug": "photography",
            "color": "#FF6B6B",
            "icon": "camera"
        }
    ]
}
```

### Create Category (Admin)

**POST** `/categories`

### Update Category (Admin)

**PUT** `/categories/{category}`

### Delete Category (Admin)

**DELETE** `/categories/{category}`

---

## 🔍 Search Endpoints

### Get Interests

**GET** `/interests`

Get all available interests.

**Response:**

```json
{
    "success": true,
    "data": ["photography", "travel", "food", "technology", "art"]
}
```

---

## 📊 Response Format

### Success Response

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // Response data
    }
}
```

### Error Response

```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": ["Validation error message"]
    }
}
```

### Pagination Response

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            // Array of items
        ],
        "first_page_url": "https://api.example.com/posts?page=1",
        "from": 1,
        "last_page": 10,
        "last_page_url": "https://api.example.com/posts?page=10",
        "links": [
            // Pagination links
        ],
        "next_page_url": "https://api.example.com/posts?page=2",
        "path": "https://api.example.com/posts",
        "per_page": 20,
        "prev_page_url": null,
        "to": 20,
        "total": 200
    }
}
```

---

## 🔒 Authentication & Authorization

### Bearer Token

Include the token in the Authorization header:

```
Authorization: Bearer 1|abc123def456...
```

### Rate Limiting

-   **General API**: 60 requests per minute
-   **Authentication**: 5 requests per minute

### Error Codes

-   `401` - Unauthorized (invalid/missing token)
-   `403` - Forbidden (insufficient permissions)
-   `404` - Not Found
-   `422` - Validation Error
-   `429` - Too Many Requests
-   `500` - Internal Server Error

---

## 🚀 Getting Started

1. **Register/Login** to get access token
2. **Register device** for push notifications
3. **Create posts** with media uploads
4. **Follow users** and interact with content
5. **Manage notifications** and device settings

## 📱 Push Notifications

The API supports Firebase push notifications for:

-   New posts from followed users
-   Post likes and saves
-   Profile visits
-   New followers
-   Booking requests
-   Custom notifications

All notifications are stored in the database with read/unread status tracking.

---

## 🔧 Environment Setup

Required environment variables:

```env
FIREBASE_SERVER_KEY=your_firebase_server_key
FIREBASE_PROJECT_ID=your_project_id
AWS_ACCESS_KEY_ID=your_aws_access_key
AWS_SECRET_ACCESS_KEY=your_aws_secret_key
AWS_BUCKET=your_s3_bucket_name
```

This API provides a complete social media platform with advanced features like efficient file uploads, real-time notifications, and comprehensive user management.
