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

## 12. Notes

-   All timestamps are in UTC format
-   Image URLs are served from AWS S3
-   Pagination is available for all list endpoints
-   User interaction data (is_liked, is_saved) is included in post responses
-   Firebase notifications are sent for follow actions
-   All endpoints support caching for better performance
