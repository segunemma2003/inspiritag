# 🏷️ User Mentioning/Tagging API Documentation

## Overview

The user tagging system allows users to mention other users in posts using `@username` syntax or by explicitly tagging users. When users are tagged, they receive notifications and can view all posts where they're mentioned.

## Base URL

```
https://your-domain.com/api
```

## Authentication

All endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer your_token_here
```

---

## 📋 API Endpoints

### 1. Get Tagged Posts

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
                "id": 5,
                "user_id": 2,
                "caption": "Check out this amazing work by @johndoe!",
                "media_url": "https://s3.amazonaws.com/bucket/tagged_post.jpg",
                "media_type": "image",
                "likes_count": 25,
                "saves_count": 5,
                "comments_count": 3,
                "shares_count": 2,
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
                    "name": "Makeup"
                },
                "tagged_users": [
                    {
                        "id": 1,
                        "name": "John Doe",
                        "username": "johndoe"
                    }
                ]
            }
        ],
        "first_page_url": "http://localhost/api/tagged-posts?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost/api/tagged-posts?page=1",
        "links": [...],
        "next_page_url": null,
        "path": "http://localhost/api/tagged-posts",
        "per_page": 20,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

**Error Responses:**

```json
{
    "success": false,
    "message": "Unauthorized",
    "error": "Invalid token"
}
```

---

### 2. Get Tag Suggestions

**GET** `/tag-suggestions`

Get user suggestions for tagging (autocomplete functionality).

**Query Parameters:**

-   `q` (required): Search query (min 1, max 50 characters)
-   `limit` (optional): Number of suggestions (max 20, default 10)

**Example Request:**

```
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
            "username": "emmawilson",
            "profile_picture": "https://s3.amazonaws.com/bucket/emma.jpg"
        },
        {
            "id": 4,
            "name": "John Smith",
            "username": "johnsmith",
            "profile_picture": "https://s3.amazonaws.com/bucket/john.jpg"
        }
    ]
}
```

**Error Responses:**

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "q": ["The q field is required."]
    }
}
```

---

### 3. Tag Users in Post

**POST** `/posts/{post_id}/tag-users`

Tag users in an existing post.

**Request Body:**

```json
{
    "user_ids": [3, 4, 5]
}
```

**Example Request:**

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
                "name": "John Smith",
                "username": "johnsmith"
            }
        ]
    }
}
```

**Error Responses:**

```json
{
    "success": false,
    "message": "Post not found"
}
```

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "user_ids": ["The user ids field is required."]
    }
}
```

---

### 4. Untag Users from Post

**DELETE** `/posts/{post_id}/untag-users`

Remove user tags from a post.

**Request Body:**

```json
{
    "user_ids": [3, 4]
}
```

**Example Request:**

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

**Error Responses:**

```json
{
    "success": false,
    "message": "Post not found"
}
```

---

### 5. Create Post with User Tags

**POST** `/posts`

Create a new post with user tagging.

**Request Body:**

```json
{
    "caption": "Amazing collaboration with @emmawilson and @johnsmith! #collaboration #makeup",
    "media": "file_upload",
    "category_id": 1,
    "tags": ["collaboration", "makeup"],
    "tagged_users": [3, 4],
    "location": "New York"
}
```

**Example Request:**

```http
POST /api/posts
Authorization: Bearer 1|abc123def456...
Content-Type: multipart/form-data

{
    "caption": "Amazing collaboration with @emmawilson and @johnsmith! #collaboration #makeup",
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
        "id": 6,
        "user_id": 1,
        "caption": "Amazing collaboration with @emmawilson and @johnsmith! #collaboration #makeup",
        "media_url": "https://s3.amazonaws.com/bucket/new_post.jpg",
        "media_type": "image",
        "likes_count": 0,
        "saves_count": 0,
        "comments_count": 0,
        "shares_count": 0,
        "created_at": "2024-01-05T10:00:00.000000Z",
        "user": {
            "id": 1,
            "name": "John Doe",
            "username": "johndoe"
        },
        "category": {
            "id": 1,
            "name": "Makeup"
        },
        "tags": [
            {
                "id": 1,
                "name": "collaboration"
            },
            {
                "id": 2,
                "name": "makeup"
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
                "name": "John Smith",
                "username": "johnsmith"
            }
        ]
    }
}
```

---

## 🔧 Key Features

### **Automatic @username Parsing**

-   When creating posts, the system automatically parses `@username` mentions in captions
-   Users mentioned with `@username` are automatically tagged
-   Notifications are sent to all tagged users

### **Notification System**

-   Tagged users receive push notifications
-   In-app notifications are created
-   Notification includes post details and tagged by user info

### **Tag Management**

-   Tag users in existing posts
-   Remove user tags from posts
-   Get posts where you're tagged
-   User tag suggestions for autocomplete

### **Validation Rules**

-   Maximum 10 users can be tagged per post
-   Users cannot tag themselves
-   Tagged users must exist in the system
-   Duplicate tags are automatically handled

---

## 📱 Frontend Implementation Examples

### **JavaScript/React Implementation:**

```javascript
// Get tag suggestions for autocomplete
const getTagSuggestions = async (query) => {
    try {
        const response = await fetch(`/api/tag-suggestions?q=${query}`, {
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            },
        });
        const data = await response.json();
        return data.data;
    } catch (error) {
        console.error("Error fetching tag suggestions:", error);
        return [];
    }
};

// Tag users in a post
const tagUsers = async (postId, userIds) => {
    try {
        const response = await fetch(`/api/posts/${postId}/tag-users`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`,
            },
            body: JSON.stringify({ user_ids: userIds }),
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Error tagging users:", error);
        return { success: false, message: "Failed to tag users" };
    }
};

// Untag users from a post
const untagUsers = async (postId, userIds) => {
    try {
        const response = await fetch(`/api/posts/${postId}/untag-users`, {
            method: "DELETE",
            headers: {
                "Content-Type": "application/json",
                Authorization: `Bearer ${token}`,
            },
            body: JSON.stringify({ user_ids: userIds }),
        });
        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Error untagging users:", error);
        return { success: false, message: "Failed to untag users" };
    }
};

// Get tagged posts
const getTaggedPosts = async (page = 1, perPage = 20) => {
    try {
        const response = await fetch(
            `/api/tagged-posts?page=${page}&per_page=${perPage}`,
            {
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            }
        );
        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Error fetching tagged posts:", error);
        return { success: false, message: "Failed to fetch tagged posts" };
    }
};
```

### **React Component Example:**

```jsx
import React, { useState, useEffect } from "react";

const UserTaggingComponent = ({ postId }) => {
    const [suggestions, setSuggestions] = useState([]);
    const [query, setQuery] = useState("");
    const [taggedUsers, setTaggedUsers] = useState([]);

    // Get tag suggestions
    const handleSearch = async (searchQuery) => {
        if (searchQuery.length > 0) {
            const results = await getTagSuggestions(searchQuery);
            setSuggestions(results);
        } else {
            setSuggestions([]);
        }
    };

    // Tag a user
    const handleTagUser = async (userId) => {
        const result = await tagUsers(postId, [userId]);
        if (result.success) {
            setTaggedUsers([...taggedUsers, ...result.data.tagged_users]);
            setQuery("");
            setSuggestions([]);
        }
    };

    // Untag a user
    const handleUntagUser = async (userId) => {
        const result = await untagUsers(postId, [userId]);
        if (result.success) {
            setTaggedUsers(taggedUsers.filter((user) => user.id !== userId));
        }
    };

    return (
        <div className="user-tagging">
            <div className="tag-input">
                <input
                    type="text"
                    placeholder="Tag users with @username"
                    value={query}
                    onChange={(e) => {
                        setQuery(e.target.value);
                        handleSearch(e.target.value);
                    }}
                />
                {suggestions.length > 0 && (
                    <div className="suggestions">
                        {suggestions.map((user) => (
                            <div
                                key={user.id}
                                className="suggestion-item"
                                onClick={() => handleTagUser(user.id)}
                            >
                                <img
                                    src={user.profile_picture}
                                    alt={user.name}
                                />
                                <span>
                                    {user.name} (@{user.username})
                                </span>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <div className="tagged-users">
                {taggedUsers.map((user) => (
                    <div key={user.id} className="tagged-user">
                        <span>@{user.username}</span>
                        <button onClick={() => handleUntagUser(user.id)}>
                            ×
                        </button>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default UserTaggingComponent;
```

---

## 🚨 Error Handling

### **Common Error Codes:**

-   **401 Unauthorized**: Invalid or missing authentication token
-   **404 Not Found**: Post or user not found
-   **422 Validation Error**: Invalid request data
-   **400 Bad Request**: Invalid request format
-   **500 Internal Server Error**: Server error

### **Error Response Format:**

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

---

## 📊 Rate Limiting

-   **Tag Suggestions**: 60 requests per minute
-   **Tag/Untag Operations**: 30 requests per minute
-   **Tagged Posts**: 100 requests per minute

---

## 🔒 Security Notes

-   All endpoints require authentication
-   Users can only tag users who exist in the system
-   Users cannot tag themselves
-   Maximum 10 users per post
-   All user inputs are validated and sanitized

---

## 📝 Notes

-   Tagged users receive push notifications immediately
-   In-app notifications are created for tagged users
-   The system automatically parses `@username` mentions in post captions
-   Tag suggestions are based on username and name matching
-   All operations are logged for audit purposes
