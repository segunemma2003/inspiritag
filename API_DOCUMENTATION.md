# 🚀 Complete Social Media API Documentation

## 📋 **API Overview**

This is a comprehensive social media API built with Laravel 12, featuring:

-   🔐 **Authentication & Authorization** (Sanctum)
-   📱 **Firebase Push Notifications**
-   📁 **S3 File Uploads** with presigned URLs
-   🎥 **Large File Support** (500MB+ videos)
-   🔍 **Advanced Search & Filtering**
-   📊 **Performance Optimized** for 100k+ users
-   🐳 **Docker Ready** with multi-service setup

## 🌐 **Base URL**

```
Production: https://your-domain.com/api
Local: http://localhost/api
```

## 📑 **Table of Contents**

1. [🔐 Authentication](#-authentication-endpoints)
2. [👤 User Management](#-user-management)
3. [📝 Post Management](#-post-management)
4. [📁 File Uploads](#-file-uploads)
5. [🔔 Notifications](#-notifications)
6. [📱 Device Management](#-device-management)
7. [🏢 Business Features](#-business-features)
8. [🏷️ Categories](#-category-endpoints)
9. [🔍 Search & Discovery](#-search--discovery)
10. [📊 Performance Features](#-performance-features)
11. [🛠️ System Endpoints](#️-system-endpoints)

---

## 🔐 **Authentication**

All protected endpoints require Bearer token authentication:

```

Authorization: Bearer {your_token}

```

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
```

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

### Google Sign-In

**POST** `/auth/google`

Authenticate with Google OAuth token.

**Request Body:**

```json
{
    "id_token": "google_id_token_here",
    "device_token": "firebase_token_here", // Optional
    "device_type": "android", // Optional: android, ios, web
    "device_name": "Samsung Galaxy S21", // Optional
    "app_version": "1.0.0", // Optional
    "os_version": "Android 12" // Optional
}
```

**Response:**

```json
{
    "success": true,
    "message": "Google authentication successful",
    "data": {
        "user": {
            "id": 123,
            "full_name": "John Doe",
            "email": "john@gmail.com",
            "username": "johndoe",
            "profile_picture": "https://lh3.googleusercontent.com/...",
            "is_google_user": true,
            "created_at": "2024-01-15T10:30:00Z"
        },
        "token": "1|abc123def456...",
        "device": {
            "id": 456,
            "device_token": "firebase_token_here",
            "device_type": "android",
            "device_name": "Samsung Galaxy S21",
            "is_active": true
        }
    }
}
```

### Apple Sign-In

**POST** `/auth/apple`

Authenticate with Apple Sign-In.

**Request Body:**

```json
{
    "identity_token": "apple_identity_token_here",
    "authorization_code": "apple_authorization_code_here", // Optional
    "device_token": "firebase_token_here", // Optional
    "device_type": "ios", // Optional: android, ios, web
    "device_name": "iPhone 15 Pro", // Optional
    "app_version": "1.0.0", // Optional
    "os_version": "iOS 17" // Optional
}
```

**Response:**

```json
{
    "success": true,
    "message": "Apple authentication successful",
    "data": {
        "user": {
            "id": 123,
            "full_name": "John Doe",
            "email": "john@privaterelay.appleid.com",
            "username": "johndoe",
            "profile_picture": null,
            "is_apple_user": true,
            "created_at": "2024-01-15T10:30:00Z"
        },
        "token": "1|abc123def456...",
        "device": {
            "id": 456,
            "device_token": "firebase_token_here",
            "device_type": "ios",
            "device_name": "iPhone 15 Pro",
            "is_active": true
        }
    }
}
```

### Firebase Custom Token

**POST** `/auth/firebase-token`

Get Firebase custom token for frontend authentication.

**Request Body:**

```json
{
    "uid": "firebase_user_uid",
    "email": "user@example.com",
    "display_name": "John Doe",
    "photo_url": "https://example.com/photo.jpg" // Optional
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "custom_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
        "expires_in": 3600,
        "user": {
            "id": 123,
            "full_name": "John Doe",
            "email": "user@example.com",
            "username": "johndoe",
            "profile_picture": "https://example.com/photo.jpg",
            "created_at": "2024-01-15T10:30:00Z"
        }
    }
}
```

### Verify Firebase Token

**POST** `/verify-firebase-token`

Verify Firebase ID token and authenticate user.

**Request Body:**

```json
{
    "id_token": "firebase_id_token_here",
    "device_token": "firebase_device_token_here", // Optional
    "device_type": "android", // Optional: android, ios, web
    "device_name": "Samsung Galaxy S21", // Optional
    "app_version": "1.0.0", // Optional
    "os_version": "Android 12" // Optional
}
```

**Response:**

```json
{
    "success": true,
    "message": "Firebase token verified successfully",
    "data": {
        "user": {
            "id": 123,
            "full_name": "John Doe",
            "email": "john@example.com",
            "username": "johndoe",
            "profile_picture": "https://example.com/photo.jpg",
            "created_at": "2024-01-15T10:30:00Z"
        },
        "token": "1|abc123def456...",
        "device": {
            "id": 456,
            "device_token": "firebase_device_token_here",
            "device_type": "android",
            "device_name": "Samsung Galaxy S21",
            "is_active": true
        }
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

## 📁 **File Uploads**

### 🎯 **Smart Upload System**

The API automatically detects file size and chooses the optimal upload method:

-   **< 500MB**: Direct S3 upload with presigned URL
-   **≥ 500MB**: Chunked upload for better performance

### **Get Upload URL**

**POST** `/posts/upload-url`

Get presigned URL for file upload.

**Request:**

```json
{
    "filename": "video.mp4",
    "content_type": "video/mp4",
    "file_size": 104857600
}
```

**Response (Small File < 500MB):**

```json
{
    "success": true,
    "data": {
        "upload_method": "direct",
        "upload_url": "https://s3.amazonaws.com/bucket/presigned-url",
        "file_path": "posts/1234567890_1_abc123.mp4",
        "file_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4",
        "expires_in": 3600,
        "file_size": 104857600,
        "threshold_exceeded": false
    }
}
```

**Response (Large File ≥ 500MB):**

```json
{
    "success": true,
    "message": "Large file detected. Use chunked upload for better performance.",
    "data": {
        "upload_method": "chunked",
        "file_path": "posts/1234567890_1_abc123.mp4",
        "file_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4",
        "file_size": 1073741824,
        "threshold_exceeded": true,
        "recommended_chunk_size": 52428800,
        "chunked_upload_endpoint": "/api/posts/chunked-upload-url"
    }
}
```

### **Get Chunked Upload URLs**

**POST** `/posts/chunked-upload-url`

Get multiple presigned URLs for chunked upload.

**Request:**

```json
{
    "filename": "large_video.mp4",
    "content_type": "video/mp4",
    "total_size": 1073741824,
    "chunk_size": 52428800
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "file_path": "posts/1234567890_1_abc123.mp4",
        "file_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4",
        "total_chunks": 21,
        "chunk_size": 52428800,
        "chunk_urls": [
            {
                "chunk_number": 0,
                "upload_url": "https://s3.amazonaws.com/bucket/presigned-url-0",
                "chunk_path": "posts/1234567890_1_abc123.mp4.part0"
            }
        ],
        "expires_in": 3600
    }
}
```

### **Complete Chunked Upload**

**POST** `/posts/complete-chunked-upload`

Complete the chunked upload process.

**Request:**

```json
{
    "file_path": "posts/1234567890_1_abc123.mp4",
    "total_chunks": 21
}
```

### **Create Post from S3**

**POST** `/posts/create-from-s3`

Create a post after successful S3 upload.

**Request:**

```json
{
    "file_path": "posts/1234567890_1_abc123.mp4",
    "title": "My Amazing Video",
    "description": "Check out this awesome video!",
    "category_id": 1,
    "tags": ["video", "amazing"],
    "thumbnail_path": "posts/thumbnails/1234567890_1_abc123.jpg" // Optional for videos
}
```

**Response:**

```json
{
    "success": true,
    "message": "Post created successfully",
    "data": {
        "id": 123,
        "title": "My Amazing Video",
        "description": "Check out this awesome video!",
        "media_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4",
        "thumbnail_url": "https://s3.amazonaws.com/bucket/posts/thumbnails/1234567890_1_abc123.jpg",
        "media_type": "video",
        "file_size": 1073741824,
        "created_at": "2024-01-15T10:30:00Z"
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

---

## 📊 **Performance Features**

### **Caching Strategy**

-   **Redis Cache**: User sessions, API responses
-   **Database Indexing**: Optimized queries for 100k+ users
-   **CDN Integration**: S3 + CloudFront for media delivery
-   **Query Optimization**: Eager loading, selective fields

### **Rate Limiting**

-   **General API**: 60 requests/minute
-   **Authentication**: 5 requests/minute
-   **File Uploads**: 10 requests/minute
-   **Search**: 30 requests/minute

### **Performance Monitoring**

**GET** `/system/performance`

Get system performance metrics.

**Response:**

```json
{
    "success": true,
    "data": {
        "cache_hit_rate": 85.5,
        "avg_response_time": 120,
        "active_users": 1250,
        "database_connections": 45,
        "memory_usage": "512MB",
        "disk_usage": "2.1GB"
    }
}
```

---

## 🛠️ **System Endpoints**

### **Health Check**

**GET** `/health`

Check API health status.

**Response:**

```json
{
    "status": "healthy",
    "timestamp": "2024-01-15T10:30:00Z",
    "services": {
        "database": "connected",
        "redis": "connected",
        "s3": "connected",
        "firebase": "connected"
    }
}
```

### **System Status**

**GET** `/system/status`

Get detailed system status.

**Response:**

```json
{
    "success": true,
    "data": {
        "api_version": "1.0.0",
        "laravel_version": "12.0",
        "php_version": "8.2",
        "database_status": "connected",
        "cache_status": "connected",
        "queue_status": "running",
        "uptime": "7 days, 12 hours"
    }
}
```

### **Database Health**

**GET** `/system/database-health`

Check database performance and health.

**Response:**

```json
{
    "success": true,
    "data": {
        "connection_count": 12,
        "slow_queries": 0,
        "table_sizes": {
            "users": "2.5MB",
            "posts": "15.2MB",
            "notifications": "1.8MB"
        },
        "index_usage": "optimal"
    }
}
```

---

## 🚀 **Quick Start Guide**

### **1. Authentication**

```bash
# Register
curl -X POST /api/register \
  -H "Content-Type: application/json" \
  -d '{"full_name":"John Doe","email":"john@example.com","username":"johndoe","password":"password123","password_confirmation":"password123","terms_accepted":true}'

# Login
curl -X POST /api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password123"}'
```

### **2. File Upload**

```bash
# Get upload URL
curl -X POST /api/posts/upload-url \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"filename":"video.mp4","content_type":"video/mp4","file_size":104857600}'

# Upload to S3 (use the presigned URL)
curl -X PUT "PRESIGNED_URL" \
  -H "Content-Type: video/mp4" \
  --data-binary @video.mp4

# Create post
curl -X POST /api/posts/create-from-s3 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"file_path":"posts/1234567890_1_abc123.mp4","title":"My Video","description":"Awesome video!"}'
```

### **3. Firebase Authentication**

```bash
# Google Sign-In
curl -X POST /api/auth/google \
  -H "Content-Type: application/json" \
  -d '{"id_token":"google_id_token","device_token":"firebase_token","device_type":"android"}'

# Apple Sign-In
curl -X POST /api/auth/apple \
  -H "Content-Type: application/json" \
  -d '{"identity_token":"apple_identity_token","device_token":"firebase_token","device_type":"ios"}'

# Verify Firebase Token
curl -X POST /api/verify-firebase-token \
  -H "Content-Type: application/json" \
  -d '{"id_token":"firebase_id_token","device_token":"firebase_device_token","device_type":"android"}'

# Get Firebase Custom Token
curl -X POST /api/auth/firebase-token \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"uid":"firebase_uid","email":"user@example.com","display_name":"John Doe"}'
```

### **4. Firebase Notifications**

```bash
# Register device
curl -X POST /api/devices/register \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"device_token":"firebase_token","device_type":"android","device_name":"Samsung Galaxy"}'

# Get notifications
curl -X GET /api/notifications \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📋 **Error Handling**

### **Common Error Responses**

```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

### **HTTP Status Codes**

-   `200` - Success
-   `201` - Created
-   `400` - Bad Request
-   `401` - Unauthorized
-   `403` - Forbidden
-   `404` - Not Found
-   `422` - Validation Error
-   `429` - Too Many Requests
-   `500` - Internal Server Error

---

## 🔧 **Configuration**

### **Environment Variables**

```bash
# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# AWS S3
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_BUCKET=your_bucket_name
AWS_DEFAULT_REGION=us-east-1

# Firebase
FIREBASE_CREDENTIALS_FILE=/path/to/firebase.json
FIREBASE_PROJECT_ID=your_project_id
FIREBASE_SERVER_KEY=your_server_key

# Cache
CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

---

## 📚 **Additional Resources**

-   [Docker Setup Guide](DEPLOYMENT_GUIDE.md)
-   [Firebase Notifications Guide](FIREBASE_NOTIFICATIONS_GUIDE.md)
-   [Large File Upload Guide](LARGE_FILE_UPLOAD_GUIDE.md)
-   [Performance Optimization](PERFORMANCE_OPTIMIZATION.md)
-   [Security Summary](SECURITY_SUMMARY.md)

---

**🎉 Your Social Media API is ready for production with 100k+ users!**

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
