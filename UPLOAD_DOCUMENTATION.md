# Media Upload API Documentation

## Overview

The social media application supports two different upload methods based on file size:

- **Direct Upload** (≤ 50MB): Traditional Laravel file upload
- **Presigned URL Upload** (> 50MB): Direct S3 upload with chunked support

## Authentication

All endpoints require authentication via Laravel Sanctum. Include the bearer token in the Authorization header:

```
Authorization: Bearer {your_token}
```

## Method 1: Direct Upload (≤ 50MB)

### Endpoint
```
POST /api/posts
```

### Request Format
- **Content-Type**: `multipart/form-data`
- **Method**: POST

### Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `media` | File | Yes | Video/image file (max 50MB) |
| `caption` | String | No | Post caption (max 2000 chars) |
| `category_id` | Integer | Yes | Category ID |
| `tags` | Array | No | Array of tag strings |
| `location` | String | No | Location string (max 255 chars) |

### Supported File Types
- **Images**: jpeg, png, jpg, gif
- **Videos**: mp4, mov, avi

### Example Request (cURL)
```bash
curl -X POST http://localhost:8000/api/posts \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "media=@/path/to/video.mp4" \
  -F "caption=My awesome video!" \
  -F "category_id=1" \
  -F "tags[]=funny" \
  -F "tags[]=viral" \
  -F "location=New York"
```

### Example Request (JavaScript)
```javascript
const formData = new FormData();
formData.append('media', fileInput.files[0]);
formData.append('caption', 'My awesome video!');
formData.append('category_id', '1');
formData.append('tags[]', 'funny');
formData.append('tags[]', 'viral');
formData.append('location', 'New York');

fetch('/api/posts', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  },
  body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

### Success Response
```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "id": 123,
    "user_id": 1,
    "category_id": 1,
    "caption": "My awesome video!",
    "media_url": "https://bucket.s3.amazonaws.com/posts/1234567890_1_abc123.mp4",
    "media_type": "video",
    "location": "New York",
    "is_public": true,
    "created_at": "2024-01-01T12:00:00.000000Z",
    "updated_at": "2024-01-01T12:00:00.000000Z",
    "user": {
      "id": 1,
      "name": "John Doe",
      "username": "johndoe",
      "profile_picture": "https://..."
    },
    "category": {
      "id": 1,
      "name": "Entertainment",
      "color": "#FF5733",
      "icon": "🎬"
    },
    "tags": [
      {"id": 1, "name": "funny", "slug": "funny"},
      {"id": 2, "name": "viral", "slug": "viral"}
    ]
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "media": ["The media field is required."],
    "category_id": ["The category id field is required."]
  }
}
```

## Method 2: Presigned URL Upload (> 50MB)

This method uses a 3-step process for large files:

### Step 1: Get Upload URL

#### Endpoint
```
POST /api/posts/upload-url
```

#### Request Body
```json
{
  "filename": "large_video.mp4",
  "content_type": "video/mp4",
  "file_size": 104857600
}
```

#### Response (File < 50MB)
```json
{
  "success": true,
  "data": {
    "upload_method": "direct",
    "upload_url": "https://bucket.s3.amazonaws.com/posts/1234567890_1_abc123.mp4?X-Amz-Algorithm=...",
    "file_path": "posts/1234567890_1_abc123.mp4",
    "file_url": "https://bucket.s3.amazonaws.com/posts/1234567890_1_abc123.mp4",
    "expires_in": 3600,
    "file_size": 104857600,
    "threshold_exceeded": false
  }
}
```

#### Response (File ≥ 50MB)
```json
{
  "success": true,
  "message": "Large file detected. Use chunked upload for better performance.",
  "data": {
    "upload_method": "chunked",
    "file_path": "posts/1234567890_1_abc123.mp4",
    "file_url": "https://bucket.s3.amazonaws.com/posts/1234567890_1_abc123.mp4",
    "file_size": 104857600,
    "threshold_exceeded": true,
    "recommended_chunk_size": 5242880,
    "chunked_upload_endpoint": "/api/posts/chunked-upload-url"
  }
}
```

### Step 2: Get Chunked Upload URLs (for large files)

#### Endpoint
```
POST /api/posts/chunked-upload-url
```

#### Request Body
```json
{
  "filename": "large_video.mp4",
  "content_type": "video/mp4",
  "total_size": 104857600,
  "chunk_size": 5242880
}
```

#### Response
```json
{
  "success": true,
  "data": {
    "file_path": "posts/1234567890_1_abc123.mp4",
    "file_url": "https://bucket.s3.amazonaws.com/posts/1234567890_1_abc123.mp4",
    "total_chunks": 20,
    "chunk_size": 5242880,
    "chunk_urls": [
      {
        "chunk_number": 0,
        "upload_url": "https://bucket.s3.amazonaws.com/posts/1234567890_1_abc123.mp4.part0?X-Amz-Algorithm=...",
        "chunk_path": "posts/1234567890_1_abc123.mp4.part0"
      },
      {
        "chunk_number": 1,
        "upload_url": "https://bucket.s3.amazonaws.com/posts/1234567890_1_abc123.mp4.part1?X-Amz-Algorithm=...",
        "chunk_path": "posts/1234567890_1_abc123.mp4.part1"
      }
    ],
    "expires_in": 3600
  }
}
```

### Step 3: Upload Chunks to S3

Upload each chunk directly to the provided presigned URLs:

```javascript
// Example: Upload chunk 0
fetch(chunkUrls[0].upload_url, {
  method: 'PUT',
  body: chunkData,
  headers: {
    'Content-Type': 'video/mp4'
  }
});
```

### Step 4: Complete Chunked Upload

#### Endpoint
```
POST /api/posts/complete-chunked-upload
```

#### Request Body
```json
{
  "file_path": "posts/1234567890_1_abc123.mp4",
  "total_chunks": 20
}
```

#### Response
```json
{
  "success": true,
  "message": "Chunked upload completed. File is ready for use.",
  "data": {
    "file_path": "posts/1234567890_1_abc123.mp4",
    "file_url": "https://bucket.s3.amazonaws.com/posts/1234567890_1_abc123.mp4",
    "total_chunks": 20
  }
}
```

### Step 5: Create Post from S3

#### Endpoint
```
POST /api/posts/create-from-s3
```

#### Request Body
```json
{
  "file_path": "posts/1234567890_1_abc123.mp4",
  "thumbnail_path": "posts/thumbnails/1234567890_1_abc123.jpg",
  "caption": "My large video!",
  "category_id": 1,
  "tags": ["funny", "viral"],
  "location": "New York",
  "media_metadata": {
    "duration": 120,
    "resolution": "1920x1080",
    "fps": 30
  }
}
```

#### Response
```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "post": {
      "id": 124,
      "user_id": 1,
      "category_id": 1,
      "caption": "My large video!",
      "media_url": "https://bucket.s3.amazonaws.com/posts/1234567890_1_abc123.mp4",
      "media_type": "video",
      "location": "New York",
      "media_metadata": {
        "duration": 120,
        "resolution": "1920x1080",
        "fps": 30
      },
      "created_at": "2024-01-01T12:00:00.000000Z",
      "updated_at": "2024-01-01T12:00:00.000000Z"
    },
    "is_liked": false,
    "is_saved": false
  }
}
```

## File Size Limits

| Method | Maximum Size | Recommended Use |
|--------|-------------|-----------------|
| Direct Upload | 50MB | Small to medium files |
| Presigned URL | 2GB | Large files |

## Error Handling

### Common Error Responses

#### Validation Errors (422)
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "media": ["The media field is required."],
    "file_size": ["The file size must be at least 1."]
  }
}
```

#### File Not Found (404)
```json
{
  "success": false,
  "message": "File not found on S3. Please upload first."
}
```

#### Unauthorized (401)
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

## Best Practices

1. **File Size Check**: Always check file size before choosing upload method
2. **Chunk Size**: Use 5-10MB chunks for optimal performance
3. **Error Handling**: Implement retry logic for failed chunk uploads
4. **Progress Tracking**: Show upload progress for large files
5. **Thumbnails**: Generate thumbnails for videos before upload
6. **Metadata**: Include video metadata for better user experience

## Performance Considerations

- **Direct Upload**: Simpler but uses server bandwidth
- **Presigned URL**: More complex but better for large files
- **Chunked Upload**: Allows parallel uploads and resume capability
- **CDN**: Files are served through CDN for better performance

## Security Notes

- All uploads require authentication
- File types are strictly validated
- Presigned URLs expire in 1 hour
- Files are stored with unique names to prevent conflicts
