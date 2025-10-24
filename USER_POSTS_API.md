# 📱 User Posts API Documentation

## Overview

The User Posts API allows you to retrieve all posts created by a specific user. This endpoint provides comprehensive post information including user details, categories, tags, and interaction status for authenticated users.

## Base URL

```
https://your-domain.com/api
```

## Authentication

This endpoint supports both authenticated and anonymous access:

-   **Authenticated users**: Get interaction status (is_liked, is_saved) for each post
-   **Anonymous users**: Get basic post information without interaction status

```
Authorization: Bearer your_token_here
```

---

## 📋 API Endpoint

### **Get Posts by Specific User**

**GET** `/users/{user_id}/posts`

Retrieve all public posts created by a specific user with pagination and interaction status.

**Path Parameters:**

-   `user_id` (required): The ID of the user whose posts you want to retrieve

**Query Parameters:**

-   `per_page` (optional): Number of posts per page (max 50, default 20)
-   `page` (optional): Page number (default 1)

---

## 📝 Request Examples

### **Basic Request**

```http
GET /api/users/2/posts
Authorization: Bearer 1|abc123def456...
```

### **With Pagination**

```http
GET /api/users/2/posts?per_page=10&page=2
Authorization: Bearer 1|abc123def456...
```

### **Anonymous Request**

```http
GET /api/users/2/posts?per_page=20
```

---

## 📊 Response Examples

### **Success Response (Authenticated User)**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 2,
                "caption": "New makeup look! Check out this amazing tutorial #makeup #beauty",
                "media_url": "https://s3.amazonaws.com/bucket/post1.jpg",
                "media_type": "image",
                "thumbnail_url": "https://s3.amazonaws.com/bucket/thumb1.jpg",
                "likes_count": 25,
                "saves_count": 5,
                "shares_count": 2,
                "comments_count": 3,
                "created_at": "2024-01-01T12:00:00.000000Z",
                "updated_at": "2024-01-01T12:00:00.000000Z",
                "is_liked": false,
                "is_saved": true,
                "user": {
                    "id": 2,
                    "name": "Jane Smith",
                    "full_name": "Jane Smith",
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
                        "name": "makeup",
                        "slug": "makeup"
                    },
                    {
                        "id": 2,
                        "name": "beauty",
                        "slug": "beauty"
                    }
                ],
                "tagged_users": [
                    {
                        "id": 3,
                        "name": "Alice Brown",
                        "username": "alicebrown"
                    }
                ]
            },
            {
                "id": 2,
                "user_id": 2,
                "caption": "Behind the scenes of my latest photoshoot!",
                "media_url": "https://s3.amazonaws.com/bucket/post2.jpg",
                "media_type": "image",
                "thumbnail_url": "https://s3.amazonaws.com/bucket/thumb2.jpg",
                "likes_count": 50,
                "saves_count": 12,
                "shares_count": 8,
                "comments_count": 15,
                "created_at": "2024-01-02T10:00:00.000000Z",
                "updated_at": "2024-01-02T10:00:00.000000Z",
                "is_liked": true,
                "is_saved": false,
                "user": {
                    "id": 2,
                    "name": "Jane Smith",
                    "full_name": "Jane Smith",
                    "username": "janesmith",
                    "profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg"
                },
                "category": {
                    "id": 2,
                    "name": "Photography",
                    "color": "#45B7D1",
                    "icon": "📸"
                },
                "tags": [
                    {
                        "id": 3,
                        "name": "photography",
                        "slug": "photography"
                    },
                    {
                        "id": 4,
                        "name": "behind-the-scenes",
                        "slug": "behind-the-scenes"
                    }
                ],
                "tagged_users": []
            }
        ],
        "first_page_url": "http://localhost/api/users/2/posts?page=1",
        "from": 1,
        "last_page": 3,
        "last_page_url": "http://localhost/api/users/2/posts?page=3",
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "http://localhost/api/users/2/posts?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": "http://localhost/api/users/2/posts?page=2",
                "label": "2",
                "active": false
            },
            {
                "url": "http://localhost/api/users/2/posts?page=3",
                "label": "3",
                "active": false
            },
            {
                "url": "http://localhost/api/users/2/posts?page=2",
                "label": "Next &raquo;",
                "active": false
            }
        ],
        "next_page_url": "http://localhost/api/users/2/posts?page=2",
        "path": "http://localhost/api/users/2/posts",
        "per_page": 20,
        "prev_page_url": null,
        "to": 20,
        "total": 45
    }
}
```

### **Success Response (Anonymous User)**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 2,
                "caption": "New makeup look! Check out this amazing tutorial #makeup #beauty",
                "media_url": "https://s3.amazonaws.com/bucket/post1.jpg",
                "media_type": "image",
                "thumbnail_url": "https://s3.amazonaws.com/bucket/thumb1.jpg",
                "likes_count": 25,
                "saves_count": 5,
                "shares_count": 2,
                "comments_count": 3,
                "created_at": "2024-01-01T12:00:00.000000Z",
                "updated_at": "2024-01-01T12:00:00.000000Z",
                "user": {
                    "id": 2,
                    "name": "Jane Smith",
                    "full_name": "Jane Smith",
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
                        "name": "makeup",
                        "slug": "makeup"
                    },
                    {
                        "id": 2,
                        "name": "beauty",
                        "slug": "beauty"
                    }
                ],
                "tagged_users": [
                    {
                        "id": 3,
                        "name": "Alice Brown",
                        "username": "alicebrown"
                    }
                ]
            }
        ],
        "per_page": 20,
        "total": 45
    }
}
```

### **Error Responses**

#### **User Not Found**

```json
{
    "success": false,
    "message": "User not found"
}
```

#### **Invalid Pagination**

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "per_page": ["The per page field must not be greater than 50."]
    }
}
```

#### **Server Error**

```json
{
    "success": false,
    "message": "Internal server error",
    "error": "Database connection failed"
}
```

---

## 🔧 Key Features

### **Pagination Support**

-   **Configurable page size**: 1-50 posts per page (default: 20)
-   **Page navigation**: Easy navigation with first, last, next, previous links
-   **Total count**: Know exactly how many posts exist
-   **Efficient queries**: Only loads necessary data

### **Interaction Status (Authenticated Users)**

-   **`is_liked`**: Whether the authenticated user has liked the post
-   **`is_saved`**: Whether the authenticated user has saved the post
-   **Real-time status**: Always reflects current user interactions

### **Rich Post Information**

-   **User details**: Complete user information for each post
-   **Category data**: Category name, color, and icon
-   **Tag information**: All tags associated with the post
-   **Tagged users**: Users mentioned/tagged in the post
-   **Media information**: URLs for both full-size and thumbnail images

### **Public Posts Only**

-   **Privacy respect**: Only returns public posts
-   **Content filtering**: Automatically filters out private content
-   **User control**: Users can control their post visibility

---

## 📱 Frontend Implementation Examples

### **React/JavaScript Implementation**

```javascript
// Get user posts with pagination
const getUserPosts = async (userId, page = 1, perPage = 20) => {
    try {
        const response = await fetch(
            `/api/users/${userId}/posts?page=${page}&per_page=${perPage}`,
            {
                headers: {
                    Authorization: `Bearer ${token}`,
                    "Content-Type": "application/json",
                },
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        return data;
    } catch (error) {
        console.error("Failed to get user posts:", error);
        return {
            success: false,
            message: "Failed to fetch posts",
            error: error.message,
        };
    }
};

// Usage example
const loadUserPosts = async (userId) => {
    const result = await getUserPosts(userId, 1, 20);

    if (result.success) {
        const posts = result.data.data;
        const pagination = {
            currentPage: result.data.current_page,
            lastPage: result.data.last_page,
            total: result.data.total,
            perPage: result.data.per_page,
        };

        console.log("Posts:", posts);
        console.log("Pagination:", pagination);
    } else {
        console.error("Error:", result.message);
    }
};
```

### **React Component Example**

```jsx
import React, { useState, useEffect } from "react";

const UserPostsComponent = ({ userId }) => {
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(false);
    const [pagination, setPagination] = useState({});
    const [error, setError] = useState(null);

    const loadPosts = async (page = 1) => {
        setLoading(true);
        setError(null);

        try {
            const result = await getUserPosts(userId, page, 20);

            if (result.success) {
                setPosts(result.data.data);
                setPagination({
                    currentPage: result.data.current_page,
                    lastPage: result.data.last_page,
                    total: result.data.total,
                    perPage: result.data.per_page,
                });
            } else {
                setError(result.message);
            }
        } catch (err) {
            setError("Failed to load posts");
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadPosts();
    }, [userId]);

    const handlePageChange = (page) => {
        loadPosts(page);
    };

    if (loading) return <div>Loading posts...</div>;
    if (error) return <div>Error: {error}</div>;

    return (
        <div className="user-posts">
            <h2>User Posts ({pagination.total})</h2>

            <div className="posts-grid">
                {posts.map((post) => (
                    <div key={post.id} className="post-card">
                        <img src={post.media_url} alt={post.caption} />
                        <div className="post-info">
                            <p>{post.caption}</p>
                            <div className="post-stats">
                                <span>❤️ {post.likes_count}</span>
                                <span>💾 {post.saves_count}</span>
                                <span>💬 {post.comments_count}</span>
                                <span>📤 {post.shares_count}</span>
                            </div>
                            {post.is_liked && (
                                <span className="liked">Liked</span>
                            )}
                            {post.is_saved && (
                                <span className="saved">Saved</span>
                            )}
                        </div>
                    </div>
                ))}
            </div>

            {/* Pagination */}
            <div className="pagination">
                {pagination.currentPage > 1 && (
                    <button
                        onClick={() =>
                            handlePageChange(pagination.currentPage - 1)
                        }
                    >
                        Previous
                    </button>
                )}

                <span>
                    Page {pagination.currentPage} of {pagination.lastPage}
                </span>

                {pagination.currentPage < pagination.lastPage && (
                    <button
                        onClick={() =>
                            handlePageChange(pagination.currentPage + 1)
                        }
                    >
                        Next
                    </button>
                )}
            </div>
        </div>
    );
};

export default UserPostsComponent;
```

### **Vue.js Implementation**

```vue
<template>
    <div class="user-posts">
        <h2>User Posts ({{ pagination.total }})</h2>

        <div v-if="loading" class="loading">Loading posts...</div>
        <div v-else-if="error" class="error">Error: {{ error }}</div>
        <div v-else>
            <div class="posts-grid">
                <div v-for="post in posts" :key="post.id" class="post-card">
                    <img :src="post.media_url" :alt="post.caption" />
                    <div class="post-info">
                        <p>{{ post.caption }}</p>
                        <div class="post-stats">
                            <span>❤️ {{ post.likes_count }}</span>
                            <span>💾 {{ post.saves_count }}</span>
                            <span>💬 {{ post.comments_count }}</span>
                            <span>📤 {{ post.shares_count }}</span>
                        </div>
                        <span v-if="post.is_liked" class="liked">Liked</span>
                        <span v-if="post.is_saved" class="saved">Saved</span>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination">
                <button
                    v-if="pagination.currentPage > 1"
                    @click="loadPosts(pagination.currentPage - 1)"
                >
                    Previous
                </button>

                <span>
                    Page {{ pagination.currentPage }} of
                    {{ pagination.lastPage }}
                </span>

                <button
                    v-if="pagination.currentPage < pagination.lastPage"
                    @click="loadPosts(pagination.currentPage + 1)"
                >
                    Next
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: "UserPosts",
    props: {
        userId: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            posts: [],
            loading: false,
            pagination: {},
            error: null,
        };
    },
    methods: {
        async loadPosts(page = 1) {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(
                    `/api/users/${this.userId}/posts?page=${page}&per_page=20`,
                    {
                        headers: {
                            Authorization: `Bearer ${this.$store.state.authToken}`,
                            "Content-Type": "application/json",
                        },
                    }
                );

                const data = await response.json();

                if (data.success) {
                    this.posts = data.data.data;
                    this.pagination = {
                        currentPage: data.data.current_page,
                        lastPage: data.data.last_page,
                        total: data.data.total,
                        perPage: data.data.per_page,
                    };
                } else {
                    this.error = data.message;
                }
            } catch (err) {
                this.error = "Failed to load posts";
            } finally {
                this.loading = false;
            }
        },
    },
    mounted() {
        this.loadPosts();
    },
};
</script>
```

---

## 🚨 Error Handling

### **Common Error Scenarios**

1. **User Not Found (404)**

    - User ID doesn't exist
    - User account has been deleted

2. **Invalid Pagination (422)**

    - `per_page` exceeds maximum limit (50)
    - Invalid page number

3. **Authentication Issues (401)**

    - Invalid or expired token
    - Missing authentication header

4. **Server Errors (500)**
    - Database connection issues
    - Internal server errors

### **Error Response Format**

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

-   **Authenticated users**: 100 requests per minute
-   **Anonymous users**: 60 requests per minute
-   **Burst limit**: 20 requests per 10 seconds

---

## 🔒 Security Notes

-   Only public posts are returned
-   User privacy is respected
-   No sensitive user information is exposed
-   All inputs are validated and sanitized
-   SQL injection protection through Eloquent ORM

---

## 📝 Best Practices

### **Performance Optimization**

-   Use pagination to limit data transfer
-   Implement caching for frequently accessed data
-   Load only necessary post fields
-   Use thumbnail URLs for image previews

### **User Experience**

-   Show loading states during API calls
-   Implement error handling with user-friendly messages
-   Provide pagination controls
-   Display interaction status clearly

### **Error Handling**

-   Always check response status
-   Implement retry logic for network errors
-   Provide fallback content for failed requests
-   Log errors for debugging

---

## 🧪 Testing

### **Test with cURL**

```bash
# Basic request
curl -X GET "https://your-domain.com/api/users/2/posts" \
  -H "Authorization: Bearer your_token_here"

# With pagination
curl -X GET "https://your-domain.com/api/users/2/posts?per_page=10&page=2" \
  -H "Authorization: Bearer your_token_here"

# Anonymous request
curl -X GET "https://your-domain.com/api/users/2/posts?per_page=20"
```

### **Test with JavaScript**

```javascript
// Test function
const testUserPostsAPI = async () => {
    try {
        const response = await fetch("/api/users/2/posts?per_page=5", {
            headers: {
                Authorization: "Bearer test_token",
            },
        });

        const data = await response.json();
        console.log("API Response:", data);

        if (data.success) {
            console.log("Posts count:", data.data.data.length);
            console.log("Total posts:", data.data.total);
            console.log("Current page:", data.data.current_page);
        }
    } catch (error) {
        console.error("Test failed:", error);
    }
};

testUserPostsAPI();
```

This comprehensive API provides everything you need to display user posts with full functionality! 🚀
