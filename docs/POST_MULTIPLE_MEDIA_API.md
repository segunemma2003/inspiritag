# Multiple Media Support for Posts API Documentation

_Last updated: {{DATE}}_

## Overview

This document describes the API endpoints for creating posts with multiple images or videos using pre-generated S3 links. Posts can now contain up to 10 media files (images and/or videos).

**Base URL**: `/api/posts`  
**Authentication**: Bearer token (required for creation)  
**Response Format**: JSON with standard wrapper structure

---

## Table of Contents

1. [Create Post with Multiple Media from S3](#1-create-post-with-multiple-media-from-s3)
2. [Get Posts (Updated Response Format)](#2-get-posts-updated-response-format)
3. [Get Single Post (Updated Response Format)](#3-get-single-post-updated-response-format)

---

## 1. Create Post with Multiple Media from S3

Create a post using pre-generated S3 URLs. Supports multiple images and/or videos.

**Endpoint**: `POST /api/posts/create-from-s3`

**Authentication**: Required

**Request Body**:

### Single Media (Backward Compatible)

```json
{
  "file_path": "posts/1234567890_1_abc123.jpg",
  "thumbnail_path": null,
  "caption": "My single image post",
  "category_id": 1,
  "tags": ["photography", "nature"],
  "tagged_users": [5, 10],
  "location": "London, UK",
  "media_metadata": {
    "width": 1920,
    "height": 1080
  },
  "is_ads": false
}
```

### Multiple Media (New Feature)

```json
{
  "file_paths": [
    "posts/1234567890_1_image1.jpg",
    "posts/1234567890_1_image2.jpg",
    "posts/1234567890_1_video1.mp4"
  ],
  "thumbnail_paths": [
    null,
    null,
    "posts/1234567890_1_video1_thumb.jpg"
  ],
  "caption": "My post with multiple media files",
  "category_id": 1,
  "tags": ["photography", "travel", "video"],
  "tagged_users": [5, 10, 15],
  "location": "Paris, France",
  "media_metadata": {
    "files": [
      {
        "width": 1920,
        "height": 1080,
        "format": "JPEG"
      },
      {
        "width": 1920,
        "height": 1080,
        "format": "JPEG"
      },
      {
        "width": 1920,
        "height": 1080,
        "duration": 30,
        "format": "MP4"
      }
    ]
  },
  "is_ads": false
}
```

**Request Parameters**:

#### For Single Media (Backward Compatible):
- `file_path` (required if `file_paths` not provided, string): S3 path to the uploaded file
- `thumbnail_path` (optional, string): S3 path to thumbnail (for videos)

#### For Multiple Media:
- `file_paths` (required if `file_path` not provided, array, min:1, max:10): Array of S3 paths to uploaded files
  - `file_paths.*` (required, string, distinct): S3 path to each file
- `thumbnail_paths` (optional, array): Array of S3 paths to thumbnails
  - `thumbnail_paths.*` (optional, nullable, string): S3 path to thumbnail for corresponding video (use `null` for images)

#### Common Parameters:
- `caption` (optional, string, max:2000): Post caption
- `category_id` (required, integer, exists:categories,id): Category ID
- `tags` (optional, array): Array of tag names
  - `tags.*` (string, max:50): Tag name
- `tagged_users` (optional, array): Array of user IDs to tag
  - `tagged_users.*` (integer, exists:users,id): User ID
- `location` (optional, string, max:255): Location string
- `media_metadata` (optional, array): Additional metadata about media files
- `is_ads` (optional, boolean): Whether this is an ads post (requires professional subscription)

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "post": {
      "id": 123,
      "user_id": 1,
      "category_id": 1,
      "caption": "My post with multiple media files",
      "media_url": [
        "https://cdn.example.com/posts/1234567890_1_image1.jpg",
        "https://cdn.example.com/posts/1234567890_1_image2.jpg",
        "https://cdn.example.com/posts/1234567890_1_video1.mp4"
      ],
      "media_type": "mixed",
      "thumbnail_url": "https://cdn.example.com/posts/1234567890_1_video1_thumb.jpg",
      "media_metadata": {
        "files": [
          {
            "file_path": "posts/1234567890_1_image1.jpg",
            "media_url": "https://cdn.example.com/posts/1234567890_1_image1.jpg",
            "media_type": "image",
            "thumbnail_url": null
          },
          {
            "file_path": "posts/1234567890_1_image2.jpg",
            "media_url": "https://cdn.example.com/posts/1234567890_1_image2.jpg",
            "media_type": "image",
            "thumbnail_url": null
          },
          {
            "file_path": "posts/1234567890_1_video1.mp4",
            "media_url": "https://cdn.example.com/posts/1234567890_1_video1.mp4",
            "media_type": "video",
            "thumbnail_url": "https://cdn.example.com/posts/1234567890_1_video1_thumb.jpg"
          }
        ],
        "count": 3
      },
      "location": "Paris, France",
      "is_public": true,
      "is_ads": false,
      "likes_count": 0,
      "saves_count": 0,
      "comments_count": 0,
      "created_at": "2025-01-20T14:00:00.000000Z",
      "updated_at": "2025-01-20T14:00:00.000000Z",
      "user": {
        "id": 1,
        "name": "John Doe",
        "full_name": "John Doe",
        "username": "johndoe",
        "profile_picture": "https://cdn.example.com/profiles/1.jpg"
      },
      "category": {
        "id": 1,
        "name": "Photography",
        "color": "#FF5733",
        "icon": "camera"
      },
      "tags": [
        {
          "id": 5,
          "name": "photography",
          "slug": "photography"
        },
        {
          "id": 12,
          "name": "travel",
          "slug": "travel"
        }
      ]
    },
    "is_liked": false,
    "is_saved": false
  }
}
```

**Response Fields Description**:
- `media_url`: Array of media URLs (always an array, even for single media posts)
- `media_type`: Overall media type (`"image"`, `"video"`, or `"mixed"`)
- `thumbnail_url`: Thumbnail URL for the first video (if any)
- `media_metadata.files`: Array containing details for each media file
- `media_metadata.count`: Total number of media files

**Error Responses**:

**422 Unprocessable Entity** - Validation errors:
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "file_paths": ["The file paths field is required when file path is not present."],
    "category_id": ["The category id field is required."]
  }
}
```

**404 Not Found** - Files not found on S3:
```json
{
  "success": false,
  "message": "Some files not found on S3. Please upload first.",
  "missing_files": [
    "File at index 1: posts/1234567890_1_image2.jpg"
  ]
}
```

**403 Forbidden** - Professional subscription required for ads:
```json
{
  "success": false,
  "message": "Professional subscription required to create ads posts"
}
```

**Example Requests**:

1. **Single Image**:
```bash
POST /api/posts/create-from-s3
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_path": "posts/1234567890_1_abc123.jpg",
  "caption": "Beautiful sunset",
  "category_id": 1
}
```

2. **Multiple Images**:
```bash
POST /api/posts/create-from-s3
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_paths": [
    "posts/1234567890_1_img1.jpg",
    "posts/1234567890_1_img2.jpg",
    "posts/1234567890_1_img3.jpg"
  ],
  "caption": "Photo gallery",
  "category_id": 1,
  "tags": ["photography", "gallery"]
}
```

3. **Mixed Media (Images + Video)**:
```bash
POST /api/posts/create-from-s3
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_paths": [
    "posts/1234567890_1_img1.jpg",
    "posts/1234567890_1_video.mp4",
    "posts/1234567890_1_img2.jpg"
  ],
  "thumbnail_paths": [
    null,
    "posts/1234567890_1_video_thumb.jpg",
    null
  ],
  "caption": "Mixed media post",
  "category_id": 1
}
```

---

## 2. Get Posts (Updated Response Format)

Retrieve a list of posts. The `media_url` field is now always returned as an array.

**Endpoint**: `GET /api/posts`

**Authentication**: Required

**Query Parameters**:
- `per_page` (optional, integer, default: 20, max: 50): Number of posts per page
- `tags` (optional, array): Filter by tags
- `creators` (optional, array): Filter by creators
- `categories` (optional, array): Filter by categories
- `search` (optional, string): Search in captions
- `media_type` (optional, string): Filter by media type (`image`, `video`, `mixed`)
- `sort_by` (optional, string): Sort field (`created_at`, `likes_count`, `saves_count`, `comments_count`)
- `sort_order` (optional, string): Sort order (`asc`, `desc`)

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 123,
        "user_id": 1,
        "category_id": 1,
        "caption": "My post with multiple media",
        "media_url": [
          "https://cdn.example.com/posts/image1.jpg",
          "https://cdn.example.com/posts/image2.jpg"
        ],
        "media_type": "image",
        "thumbnail_url": null,
        "likes_count": 45,
        "saves_count": 12,
        "comments_count": 8,
        "created_at": "2025-01-20T14:00:00.000000Z",
        "is_liked": false,
        "is_saved": true,
        "user": {
          "id": 1,
          "name": "John Doe",
          "full_name": "John Doe",
          "username": "johndoe",
          "profile_picture": "https://cdn.example.com/profiles/1.jpg"
        },
        "category": {
          "id": 1,
          "name": "Photography",
          "color": "#FF5733",
          "icon": "camera"
        },
        "tags": [
          {
            "id": 5,
            "name": "photography",
            "slug": "photography"
          }
        ]
      },
      {
        "id": 124,
        "user_id": 2,
        "category_id": 2,
        "caption": "Single image post",
        "media_url": [
          "https://cdn.example.com/posts/single.jpg"
        ],
        "media_type": "image",
        "thumbnail_url": null,
        "likes_count": 120,
        "saves_count": 30,
        "comments_count": 15,
        "created_at": "2025-01-19T10:00:00.000000Z",
        "is_liked": true,
        "is_saved": false,
        "user": { ... },
        "category": { ... },
        "tags": []
      }
    ],
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  },
  "filters_applied": {
    "tags": [],
    "creators": [],
    "categories": [],
    "search": "",
    "media_type": "",
    "sort_by": "created_at",
    "sort_order": "desc"
  }
}
```

**Important Notes**:
- `media_url` is **always an array**, even for posts with a single media file
- For backward compatibility, old posts with single media will have their URL wrapped in an array
- Use `media_url[0]` to get the first media URL, or iterate through the array for multiple media

---

## 3. Get Single Post (Updated Response Format)

Retrieve a single post by ID. The `media_url` field is returned as an array.

**Endpoint**: `GET /api/posts/{post}`

**Authentication**: Required

**URL Parameters**:
- `post` (required, integer): Post ID

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "id": 123,
    "user_id": 1,
    "category_id": 1,
    "caption": "My post with multiple media files",
    "media_url": [
      "https://cdn.example.com/posts/image1.jpg",
      "https://cdn.example.com/posts/image2.jpg",
      "https://cdn.example.com/posts/video1.mp4"
    ],
    "media_type": "mixed",
    "thumbnail_url": "https://cdn.example.com/posts/video1_thumb.jpg",
    "media_metadata": {
      "files": [
        {
          "file_path": "posts/image1.jpg",
          "media_url": "https://cdn.example.com/posts/image1.jpg",
          "media_type": "image",
          "thumbnail_url": null
        },
        {
          "file_path": "posts/image2.jpg",
          "media_url": "https://cdn.example.com/posts/image2.jpg",
          "media_type": "image",
          "thumbnail_url": null
        },
        {
          "file_path": "posts/video1.mp4",
          "media_url": "https://cdn.example.com/posts/video1.mp4",
          "media_type": "video",
          "thumbnail_url": "https://cdn.example.com/posts/video1_thumb.jpg"
        }
      ],
      "count": 3
    },
    "location": "Paris, France",
    "is_public": true,
    "is_ads": false,
    "likes_count": 45,
    "saves_count": 12,
    "comments_count": 8,
    "created_at": "2025-01-20T14:00:00.000000Z",
    "updated_at": "2025-01-20T14:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "John Doe",
      "full_name": "John Doe",
      "username": "johndoe",
      "profile_picture": "https://cdn.example.com/profiles/1.jpg"
    },
    "category": {
      "id": 1,
      "name": "Photography",
      "color": "#FF5733",
      "icon": "camera"
    },
    "tags": [
      {
        "id": 5,
        "name": "photography",
        "slug": "photography"
      }
    ],
    "taggedUsers": [
      {
        "id": 5,
        "username": "janedoe",
        "full_name": "Jane Doe"
      }
    ],
    "likes": [...],
    "saves": [...],
    "shares": [...]
  }
}
```

**Error Response** (404 Not Found):
```json
{
  "success": false,
  "message": "Resource not found."
}
```

---

## Media Type Values

| Value | Description |
|-------|-------------|
| `"image"` | Post contains only images |
| `"video"` | Post contains only videos |
| `"mixed"` | Post contains both images and videos |

---

## Supported File Formats

### Images:
- JPEG (`.jpg`, `.jpeg`)
- PNG (`.png`)
- GIF (`.gif`)

### Videos:
- MP4 (`.mp4`)
- MOV (`.mov`)
- AVI (`.avi`)
- MKV (`.mkv`)
- WebM (`.webm`)

---

## Important Notes

### 1. Backward Compatibility
- Single media posts created with `file_path` parameter continue to work
- All posts return `media_url` as an array for consistency
- Old posts with single media are automatically converted to array format in responses

### 2. Media URL Format
- `media_url` is **always an array** in API responses
- For single media: `["https://cdn.example.com/post.jpg"]`
- For multiple media: `["https://cdn.example.com/img1.jpg", "https://cdn.example.com/img2.jpg", "https://cdn.example.com/video.mp4"]`

### 3. Thumbnails
- Thumbnails are required/recommended for videos
- If a video doesn't have a thumbnail, a placeholder will be used
- Thumbnails are not required for images (will be `null`)

### 4. Media Limits
- Maximum **10 media files** per post
- Minimum **1 media file** per post
- All file paths must be unique (no duplicates)

### 5. File Validation
- All file paths must exist on S3 before creating the post
- If any file is missing, the request will fail with a list of missing files
- Files are validated to ensure they exist on S3 before post creation

### 6. S3 Path Format
- File paths should be relative to the S3 bucket
- Example: `posts/1234567890_1_filename.jpg`
- Do not include bucket name or full URL in the path

### 7. Media Metadata
- Optional metadata can be included for each file
- Stored in `media_metadata.files` array
- Useful for storing dimensions, duration, format, etc.

---

## Workflow Example

### Step 1: Generate Upload URLs for Multiple Files

```bash
# Get presigned URLs for each file
POST /api/posts/upload-url
{
  "filename": "image1.jpg",
  "content_type": "image/jpeg",
  "file_size": 2048000
}

POST /api/posts/upload-url
{
  "filename": "image2.jpg",
  "content_type": "image/jpeg",
  "file_size": 1856000
}

POST /api/posts/upload-url
{
  "filename": "video.mp4",
  "content_type": "video/mp4",
  "file_size": 15728640
}
```

### Step 2: Upload Files to S3

Use the presigned URLs returned from Step 1 to upload files directly to S3.

### Step 3: Create Post with Multiple Media

```bash
POST /api/posts/create-from-s3
{
  "file_paths": [
    "posts/1234567890_1_image1.jpg",
    "posts/1234567890_1_image2.jpg",
    "posts/1234567890_1_video.mp4"
  ],
  "thumbnail_paths": [
    null,
    null,
    "posts/1234567890_1_video_thumb.jpg"
  ],
  "caption": "My amazing post with multiple media!",
  "category_id": 1,
  "tags": ["photography", "travel"],
  "location": "Paris, France"
}
```

### Step 4: Retrieve Post

```bash
GET /api/posts/123
```

Response will include `media_url` as an array with all media URLs.

---

## Frontend Implementation Examples

### React/JavaScript - Displaying Multiple Media

```javascript
function PostMedia({ post }) {
  const { media_url, media_type } = post;

  return (
    <div className="post-media">
      {media_url.length === 1 ? (
        // Single media - simple display
        media_type === 'video' ? (
          <video src={media_url[0]} controls />
        ) : (
          <img src={media_url[0]} alt={post.caption} />
        )
      ) : (
        // Multiple media - gallery/carousel
        <MediaCarousel mediaUrls={media_url} mediaType={media_type} />
      )}
    </div>
  );
}

function MediaCarousel({ mediaUrls, mediaType }) {
  return (
    <div className="media-carousel">
      {mediaUrls.map((url, index) => (
        <div key={index} className="media-item">
          {mediaType === 'video' || url.endsWith('.mp4') ? (
            <video src={url} controls />
          ) : (
            <img src={url} alt={`Media ${index + 1}`} />
          )}
        </div>
      ))}
    </div>
  );
}
```

### Creating Post with Multiple Media

```javascript
async function createPostWithMultipleMedia(files, caption, categoryId) {
  // Step 1: Get presigned URLs for all files
  const uploadUrls = await Promise.all(
    files.map(file => 
      fetch('/api/posts/upload-url', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          filename: file.name,
          content_type: file.type,
          file_size: file.size
        })
      }).then(res => res.json())
    )
  );

  // Step 2: Upload files to S3 using presigned URLs
  await Promise.all(
    files.map((file, index) => {
      const { upload_url, file_path } = uploadUrls[index].data;
      return fetch(upload_url, {
        method: 'PUT',
        headers: {
          'Content-Type': file.type
        },
        body: file
      });
    })
  );

  // Step 3: Create post with file paths
  const filePaths = uploadUrls.map(url => url.data.file_path);
  const thumbnailPaths = files.map((file, index) => {
    // Generate thumbnail for videos (if applicable)
    if (file.type.startsWith('video/')) {
      return generateThumbnail(file); // Your thumbnail generation logic
    }
    return null;
  });

  const response = await fetch('/api/posts/create-from-s3', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      file_paths: filePaths,
      thumbnail_paths: thumbnailPaths,
      caption: caption,
      category_id: categoryId
    })
  });

  return response.json();
}
```

---

## API Endpoint Summary

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/posts/create-from-s3` | Create post with multiple media from S3 | Yes |
| GET | `/posts` | Get list of posts (media_url as array) | Yes |
| GET | `/posts/{post}` | Get single post (media_url as array) | Yes |

---

## Migration Notes

If you're running this update on an existing system:

1. **Run the migration**:
   ```bash
   php artisan migrate
   ```
   This will:
   - Convert `media_url` column from VARCHAR to TEXT
   - Convert existing single URLs to JSON array format
   - Ensure backward compatibility

2. **Clear caches**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Test endpoints**:
   - Test creating posts with single media (backward compatibility)
   - Test creating posts with multiple media
   - Verify posts are retrieved with `media_url` as array

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20

