# 📱 Post Interactions API Documentation

## Overview

The Post Interactions API allows you to retrieve users who have liked or saved a specific post. These endpoints provide detailed information about user interactions with posts, including user profiles and interaction timestamps.

## Base URL

```
https://your-domain.com/api
```

## Authentication

These endpoints support both authenticated and anonymous access:
- **Authenticated users**: Full access to all interaction data
- **Anonymous users**: Can view public interaction data

```
Authorization: Bearer your_token_here
```

---

## 📋 API Endpoints

### **1. Get Users Who Liked a Post**

**GET** `/posts/{post_id}/likes`

Retrieve all users who have liked a specific post with pagination.

**Path Parameters:**
- `post_id` (required): The ID of the post

**Query Parameters:**
- `per_page` (optional): Number of users per page (max 50, default 20)
- `page` (optional): Page number (default 1)

---

### **2. Get Users Who Saved a Post**

**GET** `/posts/{post_id}/saves`

Retrieve all users who have saved a specific post with pagination.

**Path Parameters:**
- `post_id` (required): The ID of the post

**Query Parameters:**
- `per_page` (optional): Number of users per page (max 50, default 20)
- `page` (optional): Page number (default 1)

---

## 📝 Request Examples

### **Get Post Likes**
```http
GET /api/posts/1/likes?per_page=20&page=1
Authorization: Bearer 1|abc123def456...
```

### **Get Post Saves**
```http
GET /api/posts/1/saves?per_page=20&page=1
Authorization: Bearer 1|abc123def456...
```

### **Anonymous Request**
```http
GET /api/posts/1/likes?per_page=20
```

---

## 📊 Response Examples

### **Get Post Likes - Success Response**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 2,
                "post_id": 1,
                "created_at": "2024-01-01T12:00:00.000000Z",
                "user": {
                    "id": 2,
                    "name": "Jane Smith",
                    "full_name": "Jane Smith",
                    "username": "janesmith",
                    "profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg",
                    "bio": "Fashion blogger and designer",
                    "profession": "Fashion Designer",
                    "is_business": true
                }
            },
            {
                "id": 2,
                "user_id": 3,
                "post_id": 1,
                "created_at": "2024-01-01T11:30:00.000000Z",
                "user": {
                    "id": 3,
                    "name": "Alice Brown",
                    "full_name": "Alice Brown",
                    "username": "alicebrown",
                    "profile_picture": "https://s3.amazonaws.com/bucket/alice.jpg",
                    "bio": "Photography enthusiast",
                    "profession": "Photographer",
                    "is_business": false
                }
            },
            {
                "id": 3,
                "user_id": 4,
                "post_id": 1,
                "created_at": "2024-01-01T10:15:00.000000Z",
                "user": {
                    "id": 4,
                    "name": "Bob Wilson",
                    "full_name": "Bob Wilson",
                    "username": "bobwilson",
                    "profile_picture": "https://s3.amazonaws.com/bucket/bob.jpg",
                    "bio": "Makeup artist and beauty expert",
                    "profession": "Makeup Artist",
                    "is_business": true
                }
            }
        ],
        "first_page_url": "http://localhost/api/posts/1/likes?page=1",
        "from": 1,
        "last_page": 2,
        "last_page_url": "http://localhost/api/posts/1/likes?page=2",
        "links": [
            {
                "url": null,
                "label": "&laquo; Previous",
                "active": false
            },
            {
                "url": "http://localhost/api/posts/1/likes?page=1",
                "label": "1",
                "active": true
            },
            {
                "url": "http://localhost/api/posts/1/likes?page=2",
                "label": "2",
                "active": false
            },
            {
                "url": "http://localhost/api/posts/1/likes?page=2",
                "label": "Next &raquo;",
                "active": false
            }
        ],
        "next_page_url": "http://localhost/api/posts/1/likes?page=2",
        "path": "http://localhost/api/posts/1/likes",
        "per_page": 20,
        "prev_page_url": null,
        "to": 20,
        "total": 25
    }
}
```

### **Get Post Saves - Success Response**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 2,
                "post_id": 1,
                "created_at": "2024-01-01T14:30:00.000000Z",
                "user": {
                    "id": 2,
                    "name": "Jane Smith",
                    "full_name": "Jane Smith",
                    "username": "janesmith",
                    "profile_picture": "https://s3.amazonaws.com/bucket/jane.jpg",
                    "bio": "Fashion blogger and designer",
                    "profession": "Fashion Designer",
                    "is_business": true
                }
            },
            {
                "id": 2,
                "user_id": 5,
                "post_id": 1,
                "created_at": "2024-01-01T13:45:00.000000Z",
                "user": {
                    "id": 5,
                    "name": "Emma Davis",
                    "full_name": "Emma Davis",
                    "username": "emmadavis",
                    "profile_picture": "https://s3.amazonaws.com/bucket/emma.jpg",
                    "bio": "Beauty enthusiast",
                    "profession": "Beauty Blogger",
                    "is_business": false
                }
            }
        ],
        "per_page": 20,
        "total": 8
    }
}
```

### **Error Responses**

#### **Post Not Found**
```json
{
    "success": false,
    "message": "Post not found"
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
    "message": "Failed to get post likes",
    "error": "Database connection failed"
}
```

---

## 🔧 Key Features

### **Pagination Support**
- **Configurable page size**: 1-50 users per page (default: 20)
- **Page navigation**: Easy navigation with first, last, next, previous links
- **Total count**: Know exactly how many users interacted
- **Efficient queries**: Only loads necessary data

### **Rich User Information**
- **Complete user profiles**: Name, username, profile picture, bio
- **Professional details**: Profession and business status
- **Interaction timestamps**: When the like/save occurred
- **User verification**: Business account verification status

### **Chronological Ordering**
- **Latest first**: Most recent interactions appear first
- **Timestamp tracking**: Exact time of interaction
- **Activity timeline**: Track when users engaged with content

---

## 📱 Frontend Implementation Examples

### **React/JavaScript Implementation**

```javascript
// Get users who liked a post
const getPostLikes = async (postId, page = 1, perPage = 20) => {
    try {
        const response = await fetch(`/api/posts/${postId}/likes?page=${page}&per_page=${perPage}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Failed to get post likes:', error);
        return { 
            success: false, 
            message: 'Failed to fetch likes',
            error: error.message 
        };
    }
};

// Get users who saved a post
const getPostSaves = async (postId, page = 1, perPage = 20) => {
    try {
        const response = await fetch(`/api/posts/${postId}/saves?page=${page}&per_page=${perPage}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Failed to get post saves:', error);
        return { 
            success: false, 
            message: 'Failed to fetch saves',
            error: error.message 
        };
    }
};

// Usage example
const loadPostInteractions = async (postId) => {
    const [likesResult, savesResult] = await Promise.all([
        getPostLikes(postId, 1, 20),
        getPostSaves(postId, 1, 20)
    ]);
    
    if (likesResult.success) {
        console.log('Likes:', likesResult.data.data);
        console.log('Total likes:', likesResult.data.total);
    }
    
    if (savesResult.success) {
        console.log('Saves:', savesResult.data.data);
        console.log('Total saves:', savesResult.data.total);
    }
};
```

### **React Component Example**

```jsx
import React, { useState, useEffect } from 'react';

const PostInteractionsComponent = ({ postId }) => {
    const [likes, setLikes] = useState([]);
    const [saves, setSaves] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [activeTab, setActiveTab] = useState('likes');

    const loadInteractions = async (type) => {
        setLoading(true);
        setError(null);
        
        try {
            const result = type === 'likes' 
                ? await getPostLikes(postId, 1, 20)
                : await getPostSaves(postId, 1, 20);
            
            if (result.success) {
                if (type === 'likes') {
                    setLikes(result.data.data);
                } else {
                    setSaves(result.data.data);
                }
            } else {
                setError(result.message);
            }
        } catch (err) {
            setError('Failed to load interactions');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        loadInteractions('likes');
    }, [postId]);

    const handleTabChange = (tab) => {
        setActiveTab(tab);
        if (tab === 'saves' && saves.length === 0) {
            loadInteractions('saves');
        }
    };

    if (loading) return <div>Loading interactions...</div>;
    if (error) return <div>Error: {error}</div>;

    const currentData = activeTab === 'likes' ? likes : saves;

    return (
        <div className="post-interactions">
            <div className="tabs">
                <button 
                    className={activeTab === 'likes' ? 'active' : ''}
                    onClick={() => handleTabChange('likes')}
                >
                    Likes ({likes.length})
                </button>
                <button 
                    className={activeTab === 'saves' ? 'active' : ''}
                    onClick={() => handleTabChange('saves')}
                >
                    Saves ({saves.length})
                </button>
            </div>
            
            <div className="interactions-list">
                {currentData.map(interaction => (
                    <div key={interaction.id} className="interaction-item">
                        <img 
                            src={interaction.user.profile_picture} 
                            alt={interaction.user.name}
                            className="user-avatar"
                        />
                        <div className="user-info">
                            <h4>{interaction.user.full_name || interaction.user.name}</h4>
                            <p>@{interaction.user.username}</p>
                            {interaction.user.bio && <p className="bio">{interaction.user.bio}</p>}
                            {interaction.user.profession && (
                                <p className="profession">{interaction.user.profession}</p>
                            )}
                            {interaction.user.is_business && (
                                <span className="business-badge">Business</span>
                            )}
                        </div>
                        <div className="interaction-time">
                            {new Date(interaction.created_at).toLocaleDateString()}
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default PostInteractionsComponent;
```

### **Vue.js Implementation**

```vue
<template>
  <div class="post-interactions">
    <div class="tabs">
      <button 
        :class="{ active: activeTab === 'likes' }"
        @click="setActiveTab('likes')"
      >
        Likes ({{ likes.length }})
      </button>
      <button 
        :class="{ active: activeTab === 'saves' }"
        @click="setActiveTab('saves')"
      >
        Saves ({{ saves.length }})
      </button>
    </div>
    
    <div v-if="loading" class="loading">Loading interactions...</div>
    <div v-else-if="error" class="error">Error: {{ error }}</div>
    <div v-else class="interactions-list">
      <div 
        v-for="interaction in currentData" 
        :key="interaction.id" 
        class="interaction-item"
      >
        <img 
          :src="interaction.user.profile_picture" 
          :alt="interaction.user.name"
          class="user-avatar"
        />
        <div class="user-info">
          <h4>{{ interaction.user.full_name || interaction.user.name }}</h4>
          <p>@{{ interaction.user.username }}</p>
          <p v-if="interaction.user.bio" class="bio">{{ interaction.user.bio }}</p>
          <p v-if="interaction.user.profession" class="profession">
            {{ interaction.user.profession }}
          </p>
          <span v-if="interaction.user.is_business" class="business-badge">
            Business
          </span>
        </div>
        <div class="interaction-time">
          {{ formatDate(interaction.created_at) }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PostInteractions',
  props: {
    postId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      likes: [],
      saves: [],
      loading: false,
      error: null,
      activeTab: 'likes'
    };
  },
  computed: {
    currentData() {
      return this.activeTab === 'likes' ? this.likes : this.saves;
    }
  },
  methods: {
    async loadInteractions(type) {
      this.loading = true;
      this.error = null;
      
      try {
        const endpoint = type === 'likes' ? 'likes' : 'saves';
        const response = await fetch(`/api/posts/${this.postId}/${endpoint}?per_page=20`, {
          headers: {
            'Authorization': `Bearer ${this.$store.state.authToken}`,
            'Content-Type': 'application/json'
          }
        });
        
        const data = await response.json();
        
        if (data.success) {
          if (type === 'likes') {
            this.likes = data.data.data;
          } else {
            this.saves = data.data.data;
          }
        } else {
          this.error = data.message;
        }
      } catch (err) {
        this.error = 'Failed to load interactions';
      } finally {
        this.loading = false;
      }
    },
    
    setActiveTab(tab) {
      this.activeTab = tab;
      if (tab === 'saves' && this.saves.length === 0) {
        this.loadInteractions('saves');
      }
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString();
    }
  },
  
  mounted() {
    this.loadInteractions('likes');
  }
};
</script>
```

---

## 🚨 Error Handling

### **Common Error Scenarios**

1. **Post Not Found (404)**
   - Post ID doesn't exist
   - Post has been deleted

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

- **Authenticated users**: 100 requests per minute
- **Anonymous users**: 60 requests per minute
- **Burst limit**: 20 requests per 10 seconds

---

## 🔒 Security Notes

- Only public post interactions are returned
- User privacy is respected
- No sensitive user information is exposed
- All inputs are validated and sanitized
- SQL injection protection through Eloquent ORM

---

## 📝 Best Practices

### **Performance Optimization**
- Use pagination to limit data transfer
- Implement caching for frequently accessed data
- Load only necessary user fields
- Use efficient database queries

### **User Experience**
- Show loading states during API calls
- Implement error handling with user-friendly messages
- Provide pagination controls
- Display user information clearly

### **Error Handling**
- Always check response status
- Implement retry logic for network errors
- Provide fallback content for failed requests
- Log errors for debugging

---

## 🧪 Testing

### **Test with cURL**

```bash
# Get post likes
curl -X GET "https://your-domain.com/api/posts/1/likes?per_page=20" \
  -H "Authorization: Bearer your_token_here"

# Get post saves
curl -X GET "https://your-domain.com/api/posts/1/saves?per_page=20" \
  -H "Authorization: Bearer your_token_here"

# Anonymous request
curl -X GET "https://your-domain.com/api/posts/1/likes?per_page=20"
```

### **Test with JavaScript**

```javascript
// Test function
const testPostInteractionsAPI = async () => {
    try {
        // Test likes
        const likesResponse = await fetch('/api/posts/1/likes?per_page=5', {
            headers: {
                'Authorization': 'Bearer test_token'
            }
        });
        
        const likesData = await likesResponse.json();
        console.log('Likes API Response:', likesData);
        
        // Test saves
        const savesResponse = await fetch('/api/posts/1/saves?per_page=5', {
            headers: {
                'Authorization': 'Bearer test_token'
            }
        });
        
        const savesData = await savesResponse.json();
        console.log('Saves API Response:', savesData);
        
    } catch (error) {
        console.error('Test failed:', error);
    }
};

testPostInteractionsAPI();
```

This comprehensive API provides everything you need to display post interactions with full user information and pagination! 🚀
