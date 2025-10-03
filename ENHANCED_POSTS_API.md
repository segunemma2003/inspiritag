# 📱 Enhanced Posts API Documentation

## 🚀 **GET /api/posts - Enhanced with Advanced Filtering**

### **Query Parameters:**

| Parameter    | Type    | Description                     | Example                                      |
| ------------ | ------- | ------------------------------- | -------------------------------------------- |
| `tags`       | array   | Filter by post tags             | `?tags[]=hair&tags[]=beauty`                 |
| `creators`   | array   | Filter by creator usernames/IDs | `?creators[]=john_doe&creators[]=jane_smith` |
| `categories` | array   | Filter by category IDs          | `?categories[]=1&categories[]=2`             |
| `search`     | string  | Search in post captions         | `?search=transformation`                     |
| `media_type` | string  | Filter by media type            | `?media_type=image` or `?media_type=video`   |
| `sort_by`    | string  | Sort field                      | `?sort_by=likes_count`                       |
| `sort_order` | string  | Sort direction                  | `?sort_order=desc`                           |
| `per_page`   | integer | Posts per page (max 50)         | `?per_page=20`                               |
| `page`       | integer | Page number                     | `?page=2`                                    |

### **Sort Options:**

-   `created_at` - Sort by creation date
-   `likes_count` - Sort by number of likes
-   `saves_count` - Sort by number of saves
-   `comments_count` - Sort by number of comments

### **Sort Order:**

-   `asc` - Ascending order
-   `desc` - Descending order (default)

---

## 🧪 **API Examples:**

### **1. Basic Feed (Default)**

```bash
GET /api/posts
Authorization: Bearer {token}
```

### **2. Filter by Tags**

```bash
GET /api/posts?tags[]=hair&tags[]=transformation&tags[]=beauty
Authorization: Bearer {token}
```

### **3. Filter by Creators**

```bash
GET /api/posts?creators[]=john_doe&creators[]=jane_smith
Authorization: Bearer {token}
```

### **4. Filter by Categories**

```bash
GET /api/posts?categories[]=1&categories[]=3
Authorization: Bearer {token}
```

### **5. Search in Captions**

```bash
GET /api/posts?search=hair+transformation
Authorization: Bearer {token}
```

### **6. Filter by Media Type**

```bash
# Images only
GET /api/posts?media_type=image
Authorization: Bearer {token}

# Videos only
GET /api/posts?media_type=video
Authorization: Bearer {token}
```

### **7. Sort by Popularity**

```bash
GET /api/posts?sort_by=likes_count&sort_order=desc
Authorization: Bearer {token}
```

### **8. Combined Filters**

```bash
GET /api/posts?tags[]=hair&categories[]=1&media_type=image&sort_by=likes_count&per_page=10
Authorization: Bearer {token}
```

---

## 📋 **Response Format:**

```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "category_id": 1,
        "caption": "Amazing hair transformation! #hair #beauty",
        "media_url": "https://cdn.example.com/posts/image1.jpg",
        "media_type": "image",
        "thumbnail_url": null,
        "likes_count": 25,
        "saves_count": 8,
        "comments_count": 3,
        "created_at": "2025-10-01T10:30:00.000000Z",
        "is_liked": true,
        "is_saved": false,
        "user": {
          "id": 1,
          "name": "John Doe",
          "full_name": "John Doe",
          "username": "john_doe",
          "profile_picture": "https://cdn.example.com/profiles/avatar1.jpg"
        },
        "category": {
          "id": 1,
          "name": "Hair Styling",
          "color": "#FF6B6B",
          "icon": "scissors"
        },
        "tags": [
          {
            "id": 1,
            "name": "hair",
            "slug": "hair"
          },
          {
            "id": 2,
            "name": "beauty",
            "slug": "beauty"
          }
        ]
      }
    ],
    "first_page_url": "http://api.example.com/api/posts?page=1",
    "from": 1,
    "last_page": 5,
    "last_page_url": "http://api.example.com/api/posts?page=5",
    "links": [...],
    "next_page_url": "http://api.example.com/api/posts?page=2",
    "path": "http://api.example.com/api/posts",
    "per_page": 20,
    "prev_page_url": null,
    "to": 20,
    "total": 100
  },
  "filters_applied": {
    "tags": ["hair", "beauty"],
    "creators": [],
    "categories": [1],
    "search": "",
    "media_type": "image",
    "sort_by": "likes_count",
    "sort_order": "desc"
  }
}
```

---

## 🔍 **User Interaction Data:**

Each post includes the authenticated user's interaction status:

-   **`is_liked`** - Boolean indicating if the user liked this post
-   **`is_saved`** - Boolean indicating if the user saved this post

---

## 🚀 **Performance Features:**

### **Caching:**

-   ✅ **Smart Cache Keys:** Based on user ID and filter combinations
-   ✅ **2-Minute Cache:** Optimized for real-time updates
-   ✅ **Filter-Based Caching:** Different cache for different filter combinations

### **Efficient Queries:**

-   ✅ **Eager Loading:** User, category, and tags loaded in single query
-   ✅ **Batch User Interactions:** Likes and saves fetched in batch
-   ✅ **Optimized Pagination:** Limited to 50 posts per page

### **Database Optimization:**

-   ✅ **Indexed Queries:** Uses database indexes for fast filtering
-   ✅ **Relationship Queries:** Efficient `whereHas` for tags and creators
-   ✅ **Selective Fields:** Only loads necessary post data

---

## 🧪 **Testing Examples:**

### **Test 1: Filter by Tags**

```bash
curl -H "Authorization: Bearer {token}" \
  "http://[SERVER_IP]/api/posts?tags[]=hair&tags[]=beauty"
```

### **Test 2: Filter by Creators**

```bash
curl -H "Authorization: Bearer {token}" \
  "http://[SERVER_IP]/api/posts?creators[]=john_doe"
```

### **Test 3: Search and Sort**

```bash
curl -H "Authorization: Bearer {token}" \
  "http://[SERVER_IP]/api/posts?search=transformation&sort_by=likes_count&sort_order=desc"
```

### **Test 4: Combined Filters**

```bash
curl -H "Authorization: Bearer {token}" \
  "http://[SERVER_IP]/api/posts?tags[]=hair&categories[]=1&media_type=image&per_page=10"
```

---

## 📱 **Frontend Integration:**

### **React Example:**

```javascript
const fetchPosts = async (filters = {}) => {
    const params = new URLSearchParams();

    if (filters.tags)
        filters.tags.forEach((tag) => params.append("tags[]", tag));
    if (filters.creators)
        filters.creators.forEach((creator) =>
            params.append("creators[]", creator)
        );
    if (filters.categories)
        filters.categories.forEach((category) =>
            params.append("categories[]", category)
        );
    if (filters.search) params.append("search", filters.search);
    if (filters.mediaType) params.append("media_type", filters.mediaType);
    if (filters.sortBy) params.append("sort_by", filters.sortBy);
    if (filters.sortOrder) params.append("sort_order", filters.sortOrder);
    if (filters.perPage) params.append("per_page", filters.perPage);

    const response = await fetch(`/api/posts?${params}`, {
        headers: { Authorization: `Bearer ${token}` },
    });

    return response.json();
};

// Usage
const posts = await fetchPosts({
    tags: ["hair", "beauty"],
    categories: [1, 2],
    sortBy: "likes_count",
    sortOrder: "desc",
    perPage: 20,
});
```

---

## ✅ **Features Implemented:**

-   ✅ **Tag Filtering:** Filter posts by multiple tags
-   ✅ **Creator Filtering:** Filter by creator usernames or IDs
-   ✅ **Category Filtering:** Filter by category IDs
-   ✅ **Text Search:** Search in post captions
-   ✅ **Media Type Filtering:** Filter by image/video
-   ✅ **Advanced Sorting:** Sort by likes, saves, comments, date
-   ✅ **User Interactions:** Shows if user liked/saved each post
-   ✅ **Smart Caching:** Filter-based caching for performance
-   ✅ **Pagination:** Efficient pagination with metadata
-   ✅ **Performance Optimized:** Batch queries and eager loading

---

## 🎯 **Use Cases:**

1. **Personalized Feed:** Show posts from followed users with user interactions
2. **Tag-Based Discovery:** Find posts by specific tags
3. **Creator Following:** See posts from specific creators
4. **Category Browsing:** Browse posts by categories
5. **Search Functionality:** Find posts by caption content
6. **Media Type Filtering:** Show only images or videos
7. **Trending Posts:** Sort by popularity metrics
8. **Recent Posts:** Sort by creation date

**Your enhanced posts API is now ready for advanced filtering and user interaction tracking!** 🚀✨
