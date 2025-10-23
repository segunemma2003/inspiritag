# Complete API Reference Guide

## 📋 Table of Contents

1. [Authentication APIs](#authentication-apis)
2. [User Management APIs](#user-management-apis)
3. [Post Management APIs](#post-management-apis)
4. [Social Features APIs](#social-features-apis)
5. [Search & Discovery APIs](#search--discovery-apis)
6. [Business Account APIs](#business-account-apis)
7. [Notification APIs](#notification-apis)
8. [File Upload APIs](#file-upload-apis)
9. [User Tagging APIs](#user-tagging-apis)
10. [Error Handling](#error-handling)

---

## 🔐 Authentication APIs

### **User Registration**

```http
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
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
            "created_at": "2024-01-01T00:00:00.000000Z"
        },
        "token": "1|abc123def456..."
    }
}
```

### **User Login**

```http
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
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
            "profile_picture": "https://s3.amazonaws.com/bucket/profile.jpg"
        },
        "token": "1|abc123def456..."
    }
}
```

### **Get Current User**

```http
GET /api/me
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "full_name": "John Doe",
        "username": "johndoe",
        "email": "john@example.com",
        "bio": "Beauty enthusiast",
        "profile_picture": "https://s3.amazonaws.com/bucket/profile.jpg",
        "profession": "Makeup Artist",
        "is_business": false,
        "interests": ["Makeup", "Fashion"],
        "followers_count": 150,
        "following_count": 75,
        "posts_count": 25,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

### **User Logout**

```http
POST /api/logout
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

---

## 👤 User Management APIs

### **Get All Users**

```http
GET /api/users?q=john&per_page=20
Authorization: Bearer 1|abc123def456...
```

**Query Parameters:**

-   `q` (optional): Search query
-   `per_page` (optional): Number of users per page (max 50)

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 2,
                "name": "Jane Smith",
                "full_name": "Jane Smith",
                "username": "janesmith",
                "profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg",
                "bio": "Fashion blogger",
                "profession": "Fashion Designer",
                "is_business": true,
                "created_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 150
    }
}
```

### **Get User Profile**

```http
GET /api/users/2
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Jane Smith",
        "full_name": "Jane Smith",
        "username": "janesmith",
        "bio": "Fashion blogger and designer",
        "profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg",
        "profession": "Fashion Designer",
        "is_business": true,
        "interests": ["Fashion", "Design", "Beauty"],
        "followers_count": 500,
        "following_count": 200,
        "posts_count": 45,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "posts": [
            {
                "id": 1,
                "caption": "New fashion collection!",
                "media_url": "https://s3.amazonaws.com/bucket/post1.jpg",
                "media_type": "image",
                "likes_count": 25,
                "saves_count": 5,
                "created_at": "2024-01-01T12:00:00.000000Z"
            }
        ]
    }
}
```

### **Update User Profile**

```http
POST /api/users/profile
Authorization: Bearer 1|abc123def456...
Content-Type: multipart/form-data

{
  "full_name": "John Doe Updated",
  "username": "johndoe",
  "bio": "Professional makeup artist",
  "profession": "Makeup Artist",
  "interests": ["Makeup", "Beauty", "Fashion"],
  "profile_picture": "file_upload"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "id": 1,
        "name": "John Doe Updated",
        "full_name": "John Doe Updated",
        "username": "johndoe",
        "bio": "Professional makeup artist",
        "profession": "Makeup Artist",
        "interests": ["Makeup", "Beauty", "Fashion"],
        "profile_picture": "https://s3.amazonaws.com/bucket/new_profile.jpg"
    }
}
```

### **Get User Statistics**

```http
GET /api/users/2/stats
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "posts_count": 45,
        "followers_count": 500,
        "following_count": 200,
        "likes_received": 2500,
        "saves_received": 400
    }
}
```

---

## 📝 Post Management APIs

### **Get Posts Feed**

```http
GET /api/posts?per_page=20&page=1
Authorization: Bearer 1|abc123def456...
```

**Query Parameters:**

-   `per_page` (optional): Posts per page (max 50, default 20)
-   `page` (optional): Page number (default 1)
-   `category_id` (optional): Filter by category
-   `media_type` (optional): Filter by media type (image, video)
-   `tags` (optional): Filter by tags array
-   `search` (optional): Search in captions

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 2,
                "caption": "New makeup tutorial!",
                "media_url": "https://s3.amazonaws.com/bucket/post1.jpg",
                "media_type": "image",
                "thumbnail_url": "https://s3.amazonaws.com/bucket/thumb1.jpg",
                "likes_count": 25,
                "saves_count": 5,
                "comments_count": 3,
                "created_at": "2024-01-01T12:00:00.000000Z",
                "is_liked": false,
                "is_saved": true,
                "user": {
                    "id": 2,
                    "name": "Jane Smith",
                    "username": "janesmith",
                    "profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg"
                },
                "category": {
                    "id": 1,
                    "name": "Makeup",
                    "color": "#FF6B6B",
                    "icon": "💄"
                },
                "tags": [
                    {
                        "id": 1,
                        "name": "Makeup",
                        "slug": "makeup"
                    }
                ],
                "tagged_users": [
                    {
                        "id": 3,
                        "name": "Emma Wilson",
                        "username": "emmawilson"
                    }
                ]
            }
        ],
        "per_page": 20,
        "total": 150
    }
}
```

### **Create Post**

```http
POST /api/posts
Authorization: Bearer 1|abc123def456...
Content-Type: multipart/form-data

{
  "caption": "Amazing makeup look! @emmawilson",
  "media": "file_upload",
  "category_id": 1,
  "tags": ["makeup", "beauty"],
  "tagged_users": [3, 4],
  "location": "New York"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Post created successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "caption": "Amazing makeup look! @emmawilson",
        "media_url": "https://s3.amazonaws.com/bucket/post1.jpg",
        "media_type": "image",
        "likes_count": 0,
        "saves_count": 0,
        "comments_count": 0,
        "created_at": "2024-01-01T12:00:00.000000Z",
        "user": {
            "id": 1,
            "name": "John Doe",
            "username": "johndoe"
        },
        "category": {
            "id": 1,
            "name": "Makeup",
            "color": "#FF6B6B",
            "icon": "💄"
        },
        "tags": [
            {
                "id": 1,
                "name": "makeup",
                "slug": "makeup"
            }
        ],
        "tagged_users": [
            {
                "id": 3,
                "name": "Emma Wilson",
                "username": "emmawilson"
            }
        ]
    }
}
```

### **Get Specific Post**

```http
GET /api/posts/1
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "id": 1,
        "user_id": 2,
        "caption": "New makeup tutorial!",
        "media_url": "https://s3.amazonaws.com/bucket/post1.jpg",
        "media_type": "image",
        "likes_count": 25,
        "saves_count": 5,
        "comments_count": 3,
        "created_at": "2024-01-01T12:00:00.000000Z",
        "user": {
            "id": 2,
            "name": "Jane Smith",
            "username": "janesmith",
            "profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg"
        },
        "category": {
            "id": 1,
            "name": "Makeup",
            "color": "#FF6B6B",
            "icon": "💄"
        },
        "tags": [
            {
                "id": 1,
                "name": "Makeup",
                "slug": "makeup"
            }
        ],
        "tagged_users": [
            {
                "id": 3,
                "name": "Emma Wilson",
                "username": "emmawilson"
            }
        ],
        "likes": [
            {
                "id": 1,
                "user_id": 3,
                "created_at": "2024-01-01T12:30:00.000000Z"
            }
        ],
        "saves": [
            {
                "id": 1,
                "user_id": 4,
                "created_at": "2024-01-01T13:00:00.000000Z"
            }
        ]
    }
}
```

### **Like/Unlike Post**

```http
POST /api/posts/1/like
Authorization: Bearer 1|abc123def456...
```

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

### **Save/Unsave Post**

```http
POST /api/posts/1/save
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "message": "Post saved",
    "data": {
        "saved": true,
        "saves_count": 6
    }
}
```

---

## 👥 Social Features APIs

### **Follow User**

```http
POST /api/users/2/follow
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "message": "Successfully followed user"
}
```

### **Unfollow User**

```http
DELETE /api/users/2/unfollow
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "message": "Successfully unfollowed user"
}
```

### **Get User Followers**

```http
GET /api/users/2/followers?per_page=20
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 3,
                "name": "Emma Wilson",
                "full_name": "Emma Wilson",
                "username": "emmawilson",
                "profile_picture": "https://s3.amazonaws.com/bucket/emma.jpg",
                "bio": "Beauty enthusiast",
                "profession": "Makeup Artist",
                "is_business": false,
                "created_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 500
    }
}
```

### **Get User Following**

```http
GET /api/users/2/following?per_page=20
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 4,
                "name": "Mike Johnson",
                "full_name": "Mike Johnson",
                "username": "mikej",
                "profile_picture": "https://s3.amazonaws.com/bucket/mike.jpg",
                "bio": "Photographer",
                "profession": "Photographer",
                "is_business": true,
                "created_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 200
    }
}
```

---

## 🔍 Search & Discovery APIs

### **Search Posts**

```http
POST /api/search/posts
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "q": "makeup tutorial",
  "per_page": 20,
  "category_id": 1,
  "media_type": "image",
  "tags": ["makeup", "beauty"],
  "sort_by": "likes_count",
  "sort_order": "desc",
  "date_from": "2024-01-01",
  "date_to": "2024-12-31"
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 2,
                "caption": "New makeup tutorial",
                "media_url": "https://s3.amazonaws.com/bucket/tutorial.jpg",
                "media_type": "image",
                "likes_count": 75,
                "saves_count": 20,
                "comments_count": 15,
                "created_at": "2024-01-04T14:00:00.000000Z",
                "is_liked": false,
                "is_saved": true,
                "user": {
                    "id": 2,
                    "name": "Sarah Davis",
                    "username": "sarahdavis",
                    "profile_picture": "https://s3.amazonaws.com/bucket/sarah.jpg"
                },
                "category": {
                    "id": 1,
                    "name": "Makeup",
                    "color": "#FF6B6B",
                    "icon": "💄"
                },
                "tags": [
                    {
                        "id": 1,
                        "name": "Makeup",
                        "slug": "makeup"
                    }
                ]
            }
        ],
        "per_page": 20,
        "total": 45
    },
    "search_query": "makeup tutorial",
    "filters_applied": {
        "category_id": 1,
        "media_type": "image",
        "tags": ["makeup", "beauty"],
        "date_from": "2024-01-01",
        "date_to": "2024-12-31",
        "sort_by": "likes_count",
        "sort_order": "desc"
    }
}
```

### **Search Users**

```http
POST /api/search/users
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "q": "makeup artist",
  "per_page": 20,
  "profession": "Makeup Artist",
  "is_business": true,
  "interests": ["Makeup", "Beauty"],
  "sort_by": "created_at",
  "sort_order": "desc"
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 3,
                "name": "Emma Wilson",
                "full_name": "Emma Wilson",
                "username": "emmawilson",
                "profile_picture": "https://s3.amazonaws.com/bucket/emma.jpg",
                "bio": "Professional makeup artist",
                "profession": "Makeup Artist",
                "is_business": true,
                "interests": ["Makeup", "Beauty", "Fashion"],
                "created_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 12
    },
    "search_query": "makeup artist",
    "filters_applied": {
        "profession": "Makeup Artist",
        "is_business": true,
        "interests": ["Makeup", "Beauty"],
        "sort_by": "created_at",
        "sort_order": "desc"
    }
}
```

### **Global Search**

```http
POST /api/search/global
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "q": "beauty",
  "types": ["posts", "users", "tags"],
  "per_page": 10
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "posts": {
            "data": [
                {
                    "id": 1,
                    "caption": "Beauty tips",
                    "media_url": "https://s3.amazonaws.com/bucket/beauty.jpg",
                    "user": {
                        "id": 2,
                        "name": "Sarah Davis",
                        "username": "sarahdavis"
                    }
                }
            ],
            "total": 1
        },
        "users": {
            "data": [
                {
                    "id": 3,
                    "name": "Emma Wilson",
                    "username": "emmawilson",
                    "profession": "Beauty Blogger"
                }
            ],
            "total": 1
        },
        "tags": {
            "data": [
                {
                    "id": 1,
                    "name": "Beauty",
                    "slug": "beauty",
                    "usage_count": 150
                }
            ],
            "total": 1
        }
    },
    "search_query": "beauty",
    "search_types": ["posts", "users", "tags"]
}
```

### **Get Trending Searches**

```http
GET /api/search/trending?limit=10
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "trending_tags": [
            {
                "id": 1,
                "name": "Makeup",
                "slug": "makeup",
                "usage_count": 500
            },
            {
                "id": 2,
                "name": "Beauty",
                "slug": "beauty",
                "usage_count": 450
            }
        ],
        "popular_users": [
            {
                "id": 1,
                "name": "John Doe",
                "username": "johndoe",
                "profile_picture": "https://s3.amazonaws.com/bucket/john.jpg",
                "followers_count": 5000
            }
        ]
    }
}
```

---

## 💼 Business Account APIs

### **Get Business Accounts**

```http
GET /api/business-accounts?type=makeup&search=artist
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 2,
                "business_name": "Emma's Beauty Studio",
                "business_description": "Professional makeup services",
                "business_type": "Makeup Artist",
                "website": "https://emmasbeauty.com",
                "phone": "+1234567890",
                "email": "emma@emmasbeauty.com",
                "address": "123 Beauty St, New York",
                "rating": 4.8,
                "reviews_count": 150,
                "is_verified": true,
                "accepts_bookings": true,
                "user": {
                    "id": 2,
                    "name": "Emma Wilson",
                    "username": "emmawilson",
                    "profile_picture": "https://s3.amazonaws.com/bucket/emma.jpg"
                }
            }
        ],
        "per_page": 20,
        "total": 25
    }
}
```

### **Create Business Account**

```http
POST /api/business-accounts
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "business_name": "John's Makeup Studio",
  "business_description": "Professional makeup services for all occasions",
  "business_type": "Makeup Artist",
  "website": "https://johnsmakeup.com",
  "phone": "+1234567890",
  "email": "john@johnsmakeup.com",
  "address": "456 Studio Ave, New York",
  "city": "New York",
  "state": "NY",
  "country": "USA",
  "postal_code": "10001",
  "instagram_handle": "@johnsmakeup",
  "business_hours": {
    "monday": "9:00-18:00",
    "tuesday": "9:00-18:00",
    "wednesday": "9:00-18:00",
    "thursday": "9:00-18:00",
    "friday": "9:00-18:00",
    "saturday": "10:00-16:00",
    "sunday": "closed"
  },
  "services": ["Bridal Makeup", "Event Makeup", "Photoshoot Makeup"],
  "accepts_bookings": true
}
```

**Response:**

```json
{
    "success": true,
    "message": "Business account created successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "business_name": "John's Makeup Studio",
        "business_description": "Professional makeup services for all occasions",
        "business_type": "Makeup Artist",
        "website": "https://johnsmakeup.com",
        "phone": "+1234567890",
        "email": "john@johnsmakeup.com",
        "address": "456 Studio Ave, New York",
        "city": "New York",
        "state": "NY",
        "country": "USA",
        "postal_code": "10001",
        "instagram_handle": "@johnsmakeup",
        "business_hours": {
            "monday": "9:00-18:00",
            "tuesday": "9:00-18:00",
            "wednesday": "9:00-18:00",
            "thursday": "9:00-18:00",
            "friday": "9:00-18:00",
            "saturday": "10:00-16:00",
            "sunday": "closed"
        },
        "services": ["Bridal Makeup", "Event Makeup", "Photoshoot Makeup"],
        "rating": 0,
        "reviews_count": 0,
        "is_verified": false,
        "accepts_bookings": true,
        "created_at": "2024-01-01T00:00:00.000000Z"
    }
}
```

---

## 🔔 Notification APIs

### **Get Notifications**

```http
GET /api/notifications?per_page=20&type=user_tagged&is_read=false
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 1,
                "from_user_id": 2,
                "post_id": 3,
                "type": "user_tagged",
                "title": "You were tagged in a post",
                "message": "Jane Smith tagged you in a post",
                "data": {
                    "post_id": 3,
                    "post_caption": "Amazing collaboration!",
                    "post_media_url": "https://s3.amazonaws.com/bucket/collab.jpg",
                    "post_media_type": "image",
                    "tagged_by_name": "Jane Smith",
                    "tagged_by_username": "janesmith",
                    "tagged_by_profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg"
                },
                "is_read": false,
                "read_at": null,
                "created_at": "2024-01-01T12:00:00.000000Z",
                "from_user": {
                    "id": 2,
                    "name": "Jane Smith",
                    "username": "janesmith",
                    "profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg"
                },
                "post": {
                    "id": 3,
                    "caption": "Amazing collaboration!",
                    "media_url": "https://s3.amazonaws.com/bucket/collab.jpg",
                    "media_type": "image"
                }
            }
        ],
        "per_page": 20,
        "total": 5
    },
    "unread_count": 5
}
```

### **Mark Notification as Read**

```http
POST /api/notifications/1/read
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "message": "Notification marked as read"
}
```

### **Mark All Notifications as Read**

```http
POST /api/notifications/read-all
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "message": "All notifications marked as read"
}
```

### **Get Unread Count**

```http
GET /api/notifications/unread-count
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "unread_count": 5
    }
}
```

---

## 🏷️ User Tagging APIs

### **Get Tagged Posts**

```http
GET /api/tagged-posts?per_page=20
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 2,
                "caption": "Check out this amazing work by @johndoe!",
                "media_url": "https://s3.amazonaws.com/bucket/tagged_post.jpg",
                "media_type": "image",
                "likes_count": 25,
                "saves_count": 5,
                "comments_count": 3,
                "created_at": "2024-01-04T14:00:00.000000Z",
                "is_liked": false,
                "is_saved": true,
                "is_tagged": true,
                "user": {
                    "id": 2,
                    "name": "Sarah Davis",
                    "username": "sarahdavis",
                    "profile_picture": "https://s3.amazonaws.com/bucket/sarah.jpg"
                },
                "category": {
                    "id": 1,
                    "name": "Makeup",
                    "color": "#FF6B6B",
                    "icon": "💄"
                },
                "tags": [
                    {
                        "id": 1,
                        "name": "Makeup",
                        "slug": "makeup"
                    }
                ]
            }
        ],
        "per_page": 20,
        "total": 5
    },
    "message": "Tagged posts retrieved successfully"
}
```

### **Get Tag Suggestions**

```http
GET /api/tag-suggestions?q=john&limit=10
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 3,
            "name": "Emma Wilson",
            "full_name": "Emma Wilson",
            "username": "emmawilson",
            "profile_picture": "https://s3.amazonaws.com/bucket/emma.jpg",
            "display_name": "Emma Wilson"
        },
        {
            "id": 4,
            "name": "Mike Johnson",
            "full_name": "Mike Johnson",
            "username": "mikej",
            "profile_picture": "https://s3.amazonaws.com/bucket/mike.jpg",
            "display_name": "Mike Johnson"
        }
    ]
}
```

### **Tag Users in Post**

```http
POST /api/posts/1/tag-users
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "user_ids": [3, 4, 5]
}
```

**Response:**

```json
{
    "success": true,
    "message": "Users tagged successfully",
    "data": {
        "tagged_users": [
            {
                "id": 3,
                "name": "Emma Wilson",
                "username": "emmawilson"
            },
            {
                "id": 4,
                "name": "Mike Johnson",
                "username": "mikej"
            }
        ],
        "notifications_sent": 2
    }
}
```

### **Remove User Tags**

```http
DELETE /api/posts/1/untag-users
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "user_ids": [3, 4]
}
```

**Response:**

```json
{
    "success": true,
    "message": "Users untagged successfully",
    "data": {
        "untagged_users": [3, 4]
    }
}
```

---

## 📁 File Upload APIs

### **Get Upload URL**

```http
POST /api/posts/upload-url
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "filename": "image.jpg",
  "content_type": "image/jpeg",
  "file_size": 2048000
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "upload_url": "https://s3.amazonaws.com/bucket/presigned-url",
        "file_url": "https://s3.amazonaws.com/bucket/final-url",
        "expires_in": 3600
    }
}
```

### **Create Post from S3**

```http
POST /api/posts/create-from-s3
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
  "s3_key": "posts/image_123456.jpg",
  "caption": "Amazing photo!",
  "category_id": 1,
  "tags": ["photography", "beauty"],
  "tagged_users": [2, 3]
}
```

**Response:**

```json
{
    "success": true,
    "message": "Post created successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "caption": "Amazing photo!",
        "media_url": "https://s3.amazonaws.com/bucket/image_123456.jpg",
        "media_type": "image",
        "created_at": "2024-01-01T12:00:00.000000Z"
    }
}
```

---

## ❌ Error Handling

### **Common Error Responses**

#### **400 Bad Request**

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

#### **401 Unauthorized**

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

#### **403 Forbidden**

```json
{
    "success": false,
    "message": "This action is unauthorized."
}
```

#### **404 Not Found**

```json
{
    "success": false,
    "message": "User not found"
}
```

#### **422 Unprocessable Entity**

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "username": ["The username has already been taken."]
    }
}
```

#### **429 Too Many Requests**

```json
{
    "success": false,
    "message": "Too many requests. Please try again later."
}
```

#### **500 Internal Server Error**

```json
{
    "success": false,
    "message": "Internal server error",
    "error": "Error details for debugging"
}
```

### **Error Codes Reference**

-   **400** - Bad Request (invalid input)
-   **401** - Unauthorized (missing/invalid token)
-   **403** - Forbidden (insufficient permissions)
-   **404** - Not Found (resource doesn't exist)
-   **422** - Unprocessable Entity (validation errors)
-   **429** - Too Many Requests (rate limit exceeded)
-   **500** - Internal Server Error (server error)

---

## 🔧 Rate Limiting

### **Rate Limits**

-   **Authentication endpoints**: 5 requests per minute
-   **General API endpoints**: 60 requests per minute per user
-   **File upload endpoints**: 10 requests per minute
-   **Search endpoints**: 30 requests per minute

### **Rate Limit Headers**

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1640995200
```

---

## 📱 Mobile App Integration

### **Authentication Flow**

1. **Register/Login** to get access token
2. **Store token** securely in app
3. **Include token** in all API requests
4. **Handle token refresh** when expired

### **File Upload Flow**

1. **Get presigned URL** from API
2. **Upload file** directly to S3
3. **Create post** with S3 file reference
4. **Handle upload progress** and errors

### **Real-time Features**

1. **WebSocket connection** for live updates
2. **Push notifications** for important events
3. **Background sync** for offline support
4. **Cache management** for performance

---

This comprehensive API reference provides all the information needed to integrate with the social media platform. Each endpoint includes detailed request/response examples, error handling, and best practices for implementation.
