# Create Post from S3 API Documentation

_Last updated: 2025-01-20_

## Overview

This endpoint allows you to create posts using files that have already been uploaded to S3. It supports both single and multiple media files (images and/or videos) with pre-generated S3 links.

**Endpoint**: `POST /api/posts/create-from-s3`  
**Authentication**: Required (Bearer token)  
**Response Format**: JSON

---

## Table of Contents

1. [Endpoint Details](#endpoint-details)
2. [Request Parameters](#request-parameters)
3. [Response Format](#response-format)
4. [Error Responses](#error-responses)
5. [Examples](#examples)
6. [Workflow Guide](#workflow-guide)

---

## Endpoint Details

**URL**: `/api/posts/create-from-s3`  
**Method**: `POST`  
**Authentication**: Bearer token required

**Headers**:

```
Authorization: Bearer {your_access_token}
Content-Type: application/json
```

---

## Request Parameters

### Single Media (Backward Compatible)

| Parameter        | Type    | Required | Description                                                          |
| ---------------- | ------- | -------- | -------------------------------------------------------------------- |
| `file_path`      | string  | Yes\*    | S3 path to the uploaded file (e.g., `posts/1234567890_1_abc123.jpg`) |
| `thumbnail_path` | string  | No       | S3 path to thumbnail (for videos)                                    |
| `caption`        | string  | No       | Post caption (max 2000 characters)                                   |
| `category_id`    | integer | Yes      | Category ID (must exist in categories table)                         |
| `tags`           | array   | No       | Array of tag names (max 50 chars each)                               |
| `tagged_users`   | array   | No       | Array of user IDs to tag in the post                                 |
| `location`       | string  | No       | Location string (max 255 characters)                                 |
| `media_metadata` | object  | No       | Additional metadata about the media file                             |
| `is_ads`         | boolean | No       | Whether this is an ads post (requires professional subscription)     |

\*Required if `file_paths` is not provided

### Multiple Media (New Feature)

| Parameter         | Type    | Required | Description                                                              |
| ----------------- | ------- | -------- | ------------------------------------------------------------------------ |
| `file_paths`      | array   | Yes\*    | Array of S3 paths (1-10 files, all paths must be unique)                 |
| `thumbnail_paths` | array   | No       | Array of S3 paths to thumbnails (use `null` for images, path for videos) |
| `caption`         | string  | No       | Post caption (max 2000 characters)                                       |
| `category_id`     | integer | Yes      | Category ID (must exist in categories table)                             |
| `tags`            | array   | No       | Array of tag names (max 50 chars each)                                   |
| `tagged_users`    | array   | No       | Array of user IDs to tag in the post                                     |
| `location`        | string  | No       | Location string (max 255 characters)                                     |
| `media_metadata`  | object  | No       | Additional metadata about media files                                    |
| `is_ads`          | boolean | No       | Whether this is an ads post (requires professional subscription)         |

\*Required if `file_path` is not provided

**Constraints**:

-   Maximum 10 media files per post
-   Minimum 1 media file per post
-   All file paths must be unique (no duplicates)
-   All files must exist on S3 before creating the post
-   Supported image formats: JPEG, PNG, GIF
-   Supported video formats: MP4, MOV, AVI, MKV, WebM

---

## Response Format

### Success Response (200 OK)

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
                }
            ]
        },
        "is_liked": false,
        "is_saved": false
    }
}
```

**Response Fields**:

-   `media_url`: Always an array, even for single media posts
-   `media_type`: Can be `"image"`, `"video"`, or `"mixed"`
-   `thumbnail_url`: Thumbnail for the first video (if any), null for images
-   `media_metadata.files`: Array with details for each media file
-   `media_metadata.count`: Total number of media files

---

## Error Responses

### 422 Unprocessable Entity - Validation Errors

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "file_paths": [
            "The file paths field is required when file path is not present."
        ],
        "category_id": ["The category id field is required."],
        "file_paths.0": ["The file paths.0 field is required."]
    }
}
```

### 404 Not Found - Files Not Found on S3

```json
{
    "success": false,
    "message": "Some files not found on S3. Please upload first.",
    "missing_files": ["File at index 1: posts/1234567890_1_image2.jpg"]
}
```

### 403 Forbidden - Professional Subscription Required

```json
{
    "success": false,
    "message": "Professional subscription required to create ads posts"
}
```

### 401 Unauthorized - Missing Authentication

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

---

## Examples

### Example 1: Single Image Post

**Request**:

```bash
POST /api/posts/create-from-s3
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_path": "posts/1234567890_1_abc123.jpg",
  "caption": "Beautiful sunset at the beach",
  "category_id": 1,
  "tags": ["photography", "nature", "sunset"],
  "location": "Maldives"
}
```

**Response**:

```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "post": {
      "id": 123,
      "media_url": ["https://cdn.example.com/posts/1234567890_1_abc123.jpg"],
      "media_type": "image",
      "caption": "Beautiful sunset at the beach",
      ...
    }
  }
}
```

### Example 2: Multiple Images Post

**Request**:

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
  "caption": "Photo gallery from my trip",
  "category_id": 1,
  "tags": ["photography", "travel", "gallery"],
  "location": "Tokyo, Japan"
}
```

**Response**:

```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "post": {
      "id": 124,
      "media_url": [
        "https://cdn.example.com/posts/1234567890_1_img1.jpg",
        "https://cdn.example.com/posts/1234567890_1_img2.jpg",
        "https://cdn.example.com/posts/1234567890_1_img3.jpg"
      ],
      "media_type": "image",
      "media_metadata": {
        "count": 3,
        "files": [...]
      },
      ...
    }
  }
}
```

### Example 3: Mixed Media Post (Images + Video)

**Request**:

```bash
POST /api/posts/create-from-s3
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_paths": [
    "posts/1234567890_1_image1.jpg",
    "posts/1234567890_1_video1.mp4",
    "posts/1234567890_1_image2.jpg"
  ],
  "thumbnail_paths": [
    null,
    "posts/1234567890_1_video1_thumb.jpg",
    null
  ],
  "caption": "Check out this amazing video!",
  "category_id": 1,
  "tags": ["video", "photography"],
  "tagged_users": [5, 10]
}
```

**Response**:

```json
{
  "success": true,
  "message": "Post created successfully",
  "data": {
    "post": {
      "id": 125,
      "media_url": [
        "https://cdn.example.com/posts/1234567890_1_image1.jpg",
        "https://cdn.example.com/posts/1234567890_1_video1.mp4",
        "https://cdn.example.com/posts/1234567890_1_image2.jpg"
      ],
      "media_type": "mixed",
      "thumbnail_url": "https://cdn.example.com/posts/1234567890_1_video1_thumb.jpg",
      ...
    }
  }
}
```

### Example 4: Video Post with Thumbnail

**Request**:

```bash
POST /api/posts/create-from-s3
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_path": "posts/1234567890_1_tutorial.mp4",
  "thumbnail_path": "posts/1234567890_1_tutorial_thumb.jpg",
  "caption": "Tutorial video on photography tips",
  "category_id": 2,
  "tags": ["tutorial", "photography", "video"],
  "is_ads": false
}
```

---

## Workflow Guide

### Step 1: Get Presigned Upload URLs

Before creating a post, you need to upload files to S3. Get presigned URLs for each file:

```bash
POST /api/posts/upload-url
Authorization: Bearer {token}
Content-Type: application/json

{
  "filename": "image1.jpg",
  "content_type": "image/jpeg",
  "file_size": 2048000
}
```

**Response**:

```json
{
    "success": true,
    "data": {
        "upload_url": "https://s3.amazonaws.com/...",
        "file_path": "posts/1234567890_1_image1.jpg",
        "file_url": "https://cdn.example.com/posts/1234567890_1_image1.jpg",
        "expires_in": 900
    }
}
```

### Step 2: Upload Files to S3

Use the presigned URLs to upload files directly to S3:

```bash
PUT {upload_url}
Content-Type: image/jpeg

[binary file data]
```

Repeat for each file you want to include in the post.

### Step 3: Create Post with S3 File Paths

After all files are uploaded, create the post using the `file_path` values from Step 1:

```bash
POST /api/posts/create-from-s3
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_paths": [
    "posts/1234567890_1_image1.jpg",
    "posts/1234567890_1_image2.jpg"
  ],
  "caption": "My post",
  "category_id": 1
}
```

### Complete Workflow Example (JavaScript)

```javascript
async function createPostWithMultipleMedia(files) {
    const token = "your_access_token";

    // Step 1: Get presigned URLs for all files
    const uploadPromises = files.map(async (file) => {
        const response = await fetch("/api/posts/upload-url", {
            method: "POST",
            headers: {
                Authorization: `Bearer ${token}`,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                filename: file.name,
                content_type: file.type,
                file_size: file.size,
            }),
        });

        const data = await response.json();
        return {
            file,
            uploadUrl: data.data.upload_url,
            filePath: data.data.file_path,
        };
    });

    const uploadData = await Promise.all(uploadPromises);

    // Step 2: Upload files to S3
    await Promise.all(
        uploadData.map(({ file, uploadUrl }) =>
            fetch(uploadUrl, {
                method: "PUT",
                headers: {
                    "Content-Type": file.type,
                },
                body: file,
            })
        )
    );

    // Step 3: Create post
    const filePaths = uploadData.map(({ filePath }) => filePath);
    const response = await fetch("/api/posts/create-from-s3", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            file_paths: filePaths,
            caption: "My amazing post!",
            category_id: 1,
            tags: ["photography", "travel"],
        }),
    });

    return response.json();
}

// Usage
const files = [...document.querySelector("#file-input").files];
const result = await createPostWithMultipleMedia(files);
console.log("Post created:", result);
```

---

## Important Notes

1. **File Path Format**: Use relative S3 paths (e.g., `posts/filename.jpg`), not full URLs or bucket names

2. **File Existence**: All files must exist on S3 before creating the post. The endpoint validates file existence and returns an error if any file is missing.

3. **Thumbnails**:

    - Required/recommended for videos
    - Not required for images (use `null` in `thumbnail_paths` array)
    - If a video doesn't have a thumbnail, a placeholder will be used

4. **Media Type Detection**:

    - Automatically detected from file extension
    - Images: `.jpg`, `.jpeg`, `.png`, `.gif`
    - Videos: `.mp4`, `.mov`, `.avi`, `.mkv`, `.webm`

5. **Mixed Media**:

    - Can combine images and videos in the same post
    - Media type will be set to `"mixed"` automatically

6. **Backward Compatibility**:

    - Single media posts can still use `file_path` parameter
    - All posts return `media_url` as an array for consistency

7. **Ads Posts**:
    - Requires professional subscription
    - Set `is_ads: true` in the request

---

## Related Endpoints

-   `POST /api/posts/upload-url` - Get presigned URL for file upload
-   `POST /api/posts/chunked-upload-url` - Get presigned URLs for chunked uploads (large files)
-   `GET /api/posts/{post}` - Get single post details
-   `GET /api/posts` - Get list of posts

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20
