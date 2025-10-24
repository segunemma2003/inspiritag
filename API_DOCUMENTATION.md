# Social Media API Documentation

## User Profile & Social Features

### Base URL

```
https://your-domain.com/api
```

### Authentication

All protected endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer your_token_here
```

---

## 1. User Profile Details

### Get Current User Profile

**GET** `/me`

Get comprehensive information about the authenticated user including statistics, recent posts, and account details.

**Response:**

```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "full_name": "John Doe",
            "username": "johndoe",
            "email": "john@example.com",
            "bio": "Beauty enthusiast and makeup artist",
            "profile_picture": "https://s3.amazonaws.com/bucket/profile.jpg",
            "profession": "Makeup Artist",
            "is_business": false,
            "is_admin": false,
            "interests": ["Makeup", "Fashion", "Beauty"],
            "location": "New York, NY",
            "website": "https://johndoe.com",
            "phone": "+1234567890",
            "date_of_birth": "1990-01-01",
            "gender": "male",
            "notifications_enabled": true,
            "email_verified_at": "2024-01-01T00:00:00.000000Z",
            "created_at": "2024-01-01T00:00:00.000000Z",
            "updated_at": "2024-01-05T10:00:00.000000Z"
        },
        "statistics": {
            "posts_count": 45,
            "followers_count": 1200,
            "following_count": 300,
            "likes_received": 5000,
            "saves_received": 800,
            "shares_received": 250,
            "comments_received": 1200
        },
        "recent_posts": [
            {
                "id": 1,
                "caption": "New makeup look!",
                "media_url": "https://s3.amazonaws.com/bucket/post1.jpg",
                "media_type": "image",
                "likes_count": 25,
                "saves_count": 5,
                "shares_count": 2,
                "comments_count": 3,
                "created_at": "2024-01-05T10:00:00.000000Z"
            }
        ],
        "devices": [
            {
                "id": 1,
                "device_type": "android",
                "device_name": "Samsung Galaxy S21",
                "app_version": "1.0.0",
                "os_version": "Android 12",
                "last_used_at": "2024-01-05T10:00:00.000000Z"
            }
        ],
        "unread_notifications_count": 5,
        "business_info": null
    }
}
```

### Get User Profile by ID

**GET** `/users/{user_id}`

Get detailed profile information for a specific user.

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
        "bio": "Beauty enthusiast and makeup artist",
        "profile_picture": "https://s3.amazonaws.com/bucket/profile.jpg",
        "profession": "Makeup Artist",
        "is_business": false,
        "is_admin": false,
        "interests": ["Makeup", "Fashion", "Beauty"],
        "is_followed": true,
        "created_at": "2024-01-01T00:00:00.000000Z",
        "posts": [
            {
                "id": 1,
                "caption": "New makeup look!",
                "media_url": "https://s3.amazonaws.com/bucket/post1.jpg",
                "media_type": "image",
                "likes_count": 25,
                "saves_count": 5,
                "comments_count": 3,
                "created_at": "2024-01-01T12:00:00.000000Z"
            }
        ]
    }
}
```

---

## 2. User Statistics

### Get User Stats (Followers, Following, Posts Count)

**GET** `/users/{user_id}/stats`

Get user statistics including followers count, following count, and posts count.

**Response:**

```json
{
    "success": true,
    "data": {
        "posts_count": 45,
        "followers_count": 1200,
        "following_count": 300,
        "likes_received": 5000,
        "saves_received": 800
    }
}
```

---

## 3. User Posts

### Get All Posts by User

**GET** `/users/{user_id}/posts`

Get all public posts by a specific user.

**Query Parameters:**

-   `per_page` (optional): Number of posts per page (max 50, default 20)
-   `page` (optional): Page number (default 1)

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
                "caption": "New makeup look!",
                "media_url": "https://s3.amazonaws.com/bucket/post1.jpg",
                "media_type": "image",
                "thumbnail_url": "https://s3.amazonaws.com/bucket/thumb1.jpg",
                "likes_count": 25,
                "saves_count": 5,
                "comments_count": 3,
                "created_at": "2024-01-01T12:00:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "John Doe",
                    "full_name": "John Doe",
                    "username": "johndoe",
                    "profile_picture": "https://s3.amazonaws.com/bucket/profile.jpg"
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
    }
}
```

---

## 4. Saved Posts

### Get User's Saved Posts

**GET** `/saved-posts`

Get all posts saved by the authenticated user.

**Query Parameters:**

-   `per_page` (optional): Number of posts per page (max 50, default 20)

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 2,
                "user_id": 3,
                "caption": "Amazing skincare routine",
                "media_url": "https://s3.amazonaws.com/bucket/skincare.jpg",
                "media_type": "image",
                "likes_count": 50,
                "saves_count": 15,
                "comments_count": 8,
                "created_at": "2024-01-02T10:00:00.000000Z",
                "is_liked": false,
                "is_saved": true,
                "user": {
                    "id": 3,
                    "name": "Jane Smith",
                    "username": "janesmith",
                    "profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg"
                }
            }
        ],
        "per_page": 20,
        "total": 12
    },
    "message": "Saved posts retrieved successfully"
}
```

---

## 5. Liked Posts

### Get User's Liked Posts

**GET** `/liked-posts`

Get all posts liked by the authenticated user.

**Query Parameters:**

-   `per_page` (optional): Number of posts per page (max 50, default 20)

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 4,
                "user_id": 5,
                "caption": "Beautiful sunset",
                "media_url": "https://s3.amazonaws.com/bucket/sunset.jpg",
                "media_type": "image",
                "likes_count": 100,
                "saves_count": 25,
                "comments_count": 12,
                "created_at": "2024-01-03T18:00:00.000000Z",
                "is_liked": true,
                "is_saved": false,
                "user": {
                    "id": 5,
                    "name": "Mike Johnson",
                    "username": "mikej",
                    "profile_picture": "https://s3.amazonaws.com/bucket/mike.jpg"
                }
            }
        ],
        "per_page": 20,
        "total": 8
    },
    "message": "Liked posts retrieved successfully"
}
```

---

## 6. Followers & Following

### Get User's Followers

**GET** `/users/{user_id}/followers`

Get list of users who follow the specified user.

**Query Parameters:**

-   `per_page` (optional): Number of followers per page (max 50, default 20)

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 2,
                "name": "Alice Brown",
                "full_name": "Alice Brown",
                "username": "alicebrown",
                "profile_picture": "https://s3.amazonaws.com/bucket/alice.jpg",
                "bio": "Fashion lover",
                "profession": "Fashion Designer",
                "is_business": false,
                "created_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 1200
    }
}
```

### Get User's Following

**GET** `/users/{user_id}/following`

Get list of users that the specified user follows.

**Query Parameters:**

-   `per_page` (optional): Number of following per page (max 50, default 20)

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 3,
                "name": "Bob Wilson",
                "full_name": "Bob Wilson",
                "username": "bobwilson",
                "profile_picture": "https://s3.amazonaws.com/bucket/bob.jpg",
                "bio": "Photography enthusiast",
                "profession": "Photographer",
                "is_business": true,
                "created_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 300
    }
}
```

---

## 7. Follow/Unfollow Actions

### Follow a User

**POST** `/users/{user_id}/follow`

Follow a specific user.

**Response (Success):**

```json
{
    "success": true,
    "message": "Successfully followed user"
}
```

**Response (Error - Already Following):**

```json
{
    "success": false,
    "message": "Already following this user"
}
```

**Response (Error - Cannot Follow Self):**

```json
{
    "success": false,
    "message": "Cannot follow yourself"
}
```

### Unfollow a User

**DELETE** `/users/{user_id}/unfollow`

Unfollow a specific user.

**Response (Success):**

```json
{
    "success": true,
    "message": "Successfully unfollowed user"
}
```

**Response (Error - Not Following):**

```json
{
    "success": false,
    "message": "Not following this user"
}
```

---

## 8. Tagged Posts

### Get Posts by Tags

**POST** `/posts/search/tags`

Search for posts by specific tags.

**Request Body:**

```json
{
    "tags": ["makeup", "beauty", "fashion"],
    "per_page": 20
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
                "id": 5,
                "user_id": 2,
                "caption": "New makeup tutorial",
                "media_url": "https://s3.amazonaws.com/bucket/tutorial.jpg",
                "media_type": "image",
                "likes_count": 75,
                "saves_count": 20,
                "comments_count": 15,
                "created_at": "2024-01-04T14:00:00.000000Z",
                "user": {
                    "id": 2,
                    "name": "Sarah Davis",
                    "username": "sarahdavis",
                    "profile_picture": "https://s3.amazonaws.com/bucket/sarah.jpg"
                },
                "tags": [
                    {
                        "id": 1,
                        "name": "Makeup",
                        "slug": "makeup"
                    },
                    {
                        "id": 2,
                        "name": "Beauty",
                        "slug": "beauty"
                    }
                ]
            }
        ],
        "per_page": 20,
        "total": 45
    }
}
```

---

## 9. User Search

### Search Users

**GET** `/users?q={search_term}`

Search for users by username or full name.

**Query Parameters:**

-   `q` (required): Search term
-   `per_page` (optional): Number of results per page (max 50, default 20)

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 4,
                "name": "Emma Wilson",
                "full_name": "Emma Wilson",
                "username": "emmawilson",
                "profile_picture": "https://s3.amazonaws.com/bucket/emma.jpg",
                "bio": "Beauty blogger",
                "profession": "Content Creator",
                "is_business": false,
                "created_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 5
    }
}
```

---

## 10. Error Responses

All endpoints may return these error responses:

### 400 Bad Request

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "field_name": ["The field is required."]
    }
}
```

### 401 Unauthorized

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

### 404 Not Found

```json
{
    "success": false,
    "message": "User not found"
}
```

### 500 Internal Server Error

```json
{
    "success": false,
    "message": "Failed to fetch data",
    "error": "Error details"
}
```

---

## 11. Rate Limiting

All protected endpoints are rate limited to 60 requests per minute per user.

---

---

## 12. Enhanced Search APIs

### Search Posts

**POST** `/search/posts`

Advanced search for posts with multiple filters.

**Request Body:**

```json
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

### Search Users

**POST** `/search/users`

Advanced search for users with multiple filters.

**Request Body:**

```json
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

### Search Followers

**POST** `/search/users/{user_id}/followers`

Search within a user's followers.

**Request Body:**

```json
{
    "q": "makeup",
    "per_page": 20,
    "profession": "Artist",
    "is_business": false,
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
                "id": 4,
                "name": "Lisa Brown",
                "full_name": "Lisa Brown",
                "username": "lisabrown",
                "profile_picture": "https://s3.amazonaws.com/bucket/lisa.jpg",
                "bio": "Makeup enthusiast",
                "profession": "Makeup Artist",
                "is_business": false,
                "created_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 8
    },
    "user_id": 1,
    "search_query": "makeup",
    "filters_applied": {
        "profession": "Artist",
        "is_business": false,
        "sort_by": "created_at",
        "sort_order": "desc"
    }
}
```

### Search Following

**POST** `/search/users/{user_id}/following`

Search within users that a specific user follows.

**Request Body:**

```json
{
    "q": "photographer",
    "per_page": 20,
    "profession": "Photographer",
    "is_business": true,
    "sort_by": "name",
    "sort_order": "asc"
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
                "id": 5,
                "name": "Mike Johnson",
                "full_name": "Mike Johnson",
                "username": "mikej",
                "profile_picture": "https://s3.amazonaws.com/bucket/mike.jpg",
                "bio": "Professional photographer",
                "profession": "Photographer",
                "is_business": true,
                "created_at": "2024-01-01T00:00:00.000000Z"
            }
        ],
        "per_page": 20,
        "total": 3
    },
    "user_id": 1,
    "search_query": "photographer",
    "filters_applied": {
        "profession": "Photographer",
        "is_business": true,
        "sort_by": "name",
        "sort_order": "asc"
    }
}
```

### Global Search

**POST** `/search/global`

Search across posts, users, and tags simultaneously.

**Request Body:**

```json
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

### Get Trending Searches

**GET** `/search/trending`

Get trending tags and popular users.

**Query Parameters:**

-   `limit` (optional): Number of results (max 20, default 10)

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

## 13. User Tagging System

### Get Tagged Posts

**GET** `/tagged-posts`

Get all posts where the authenticated user is tagged.

**Query Parameters:**

-   `per_page` (optional): Number of posts per page (max 50, default 20)

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

### Get Tag Suggestions

**GET** `/tag-suggestions`

Get user suggestions for tagging (autocomplete functionality).

**Query Parameters:**

-   `q` (required): Search query (min 1, max 50 characters)
-   `limit` (optional): Number of suggestions (max 20, default 10)

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

### Tag Users in Post

**POST** `/posts/{post_id}/tag-users`

Tag users in an existing post.

**Request Body:**

```json
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

### Remove User Tags from Post

**DELETE** `/posts/{post_id}/untag-users`

Remove user tags from a post.

**Request Body:**

```json
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

### Create Post with User Tags

**POST** `/posts`

Create a new post and tag users.

**Request Body:**

```json
{
    "caption": "Amazing collaboration with @emmawilson and @mikej!",
    "media": "file_upload",
    "category_id": 1,
    "tags": ["collaboration", "makeup"],
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
        "caption": "Amazing collaboration with @emmawilson and @mikej!",
        "media_url": "https://s3.amazonaws.com/bucket/post.jpg",
        "media_type": "image",
        "likes_count": 0,
        "saves_count": 0,
        "comments_count": 0,
        "created_at": "2024-01-04T14:00:00.000000Z",
        "user": {
            "id": 1,
            "name": "John Doe",
            "username": "johndoe",
            "profile_picture": "https://s3.amazonaws.com/bucket/john.jpg"
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
                "name": "collaboration",
                "slug": "collaboration"
            }
        ],
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
        ]
    }
}
```

### User Tagging Features

#### **Automatic @username Parsing**

-   When creating posts, the system automatically parses `@username` mentions in captions
-   Users mentioned with `@username` are automatically tagged
-   Notifications are sent to all tagged users

#### **Notification System**

-   Tagged users receive push notifications
-   In-app notifications are created
-   Notification includes post details and tagged by user info

#### **Tag Management**

-   Tag users in existing posts
-   Remove user tags from posts
-   Get posts where you're tagged
-   User tag suggestions for autocomplete

#### **Validation Rules**

-   Maximum 10 users can be tagged per post
-   Users cannot tag themselves
-   Tagged users must exist in the system
-   Duplicate tags are automatically handled

---

## 14. Notes

-   All timestamps are in UTC format
-   Image URLs are served from AWS S3
-   Pagination is available for all list endpoints
-   User interaction data (is_liked, is_saved) is included in post responses
-   Firebase notifications are sent for follow actions
-   All endpoints support caching for better performance
-   Search results are cached for 2 minutes for better performance
-   All search endpoints support advanced filtering and sorting
-   Global search allows searching across multiple content types simultaneously

---

## 14. Share Functionality

### Share Post

**POST** `/posts/{post}/share`

Share a post on different platforms.

**Request Body:**

```json
{
    "platform": "instagram"
}
```

**Platform Options:**

-   `instagram` - Instagram
-   `facebook` - Facebook
-   `twitter` - Twitter
-   `copy_link` - Copy link (default)
-   `whatsapp` - WhatsApp
-   `telegram` - Telegram

**Response:**

```json
{
    "success": true,
    "message": "Post shared successfully",
    "data": {
        "shared": true,
        "shares_count": 15,
        "platform": "instagram"
    }
}
```

### Delete Post

**DELETE** `/posts/{post}`

Delete a post (only by post owner).

**Response:**

```json
{
    "success": true,
    "message": "Post deleted successfully"
}
```

### Updated User Statistics

**GET** `/users/{user_id}/stats`

User statistics now include shares count.

**Response:**

```json
{
    "success": true,
    "data": {
        "posts_count": 45,
        "followers_count": 1200,
        "following_count": 300,
        "likes_received": 5000,
        "saves_received": 800,
        "shares_received": 250
    }
}
```

---

## 15. Haircare Categories

### Available Haircare Categories

The following haircare categories have been added to the system:

1. **Hair Care** - Hair care tips, products, and tutorials
2. **Hair Styling** - Hair styling techniques and tutorials
3. **Hair Color** - Hair coloring techniques and trends
4. **Hair Treatments** - Hair treatment and repair solutions
5. **Hair Extensions** - Hair extensions and wigs
6. **Hair Tools** - Hair styling tools and equipment

### Run Haircare Category Seeder

To add these categories to your database, run:

```bash
php artisan db:seed --class=HaircareCategorySeeder
```

This will create all haircare-related categories with appropriate colors and icons.
