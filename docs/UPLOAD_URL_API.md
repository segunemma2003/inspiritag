# Upload URL API Documentation

_Last updated: 2025-01-20_

## Overview

This endpoint generates a **presigned URL** that allows you to upload files directly to S3 without going through your server. This is useful for large files and provides better performance by bypassing your application server.

**Endpoint**: `POST /api/posts/upload-url`  
**Authentication**: Required (Bearer token)  
**Response Format**: JSON

---

## What is a Presigned URL?

A presigned URL is a temporary, secure URL that grants permission to upload (or download) a file directly to/from S3. It includes authentication credentials in the URL itself, so you don't need to expose your AWS credentials to the client.

**Benefits**:

-   ✅ Upload files directly to S3 (bypasses your server)
-   ✅ Better performance for large files
-   ✅ Reduces server load
-   ✅ Secure (temporary, expires after 15 minutes)
-   ✅ Works with any HTTP client

---

## Endpoint Details

**URL**: `/api/posts/upload-url`  
**Method**: `POST`  
**Authentication**: Bearer token required

**Headers**:

```
Authorization: Bearer {your_access_token}
Content-Type: application/json
```

---

## Request Parameters

| Parameter      | Type    | Required | Description                                             |
| -------------- | ------- | -------- | ------------------------------------------------------- |
| `filename`     | string  | Yes      | Original filename (e.g., `photo.jpg`, `video.mp4`)      |
| `content_type` | string  | Yes      | MIME type of the file (e.g., `image/jpeg`, `video/mp4`) |
| `file_size`    | integer | Yes      | File size in bytes (min: 1, max: 2GB)                   |

**Request Example**:

```json
{
    "filename": "my-photo.jpg",
    "content_type": "image/jpeg",
    "file_size": 2048000
}
```

---

## Response Format

### Success Response (200 OK)

```json
{
    "success": true,
    "data": {
        "upload_method": "direct",
        "upload_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.jpg?X-Amz-Algorithm=...",
        "file_path": "posts/1234567890_1_abc123.jpg",
        "file_url": "https://bucket.s3.region.amazonaws.com/posts/1234567890_1_abc123.jpg",
        "expires_in": 900,
        "file_size": 2048000,
        "content_type": "image/jpeg"
    }
}
```

**Response Fields**:

-   `upload_url`: Presigned URL for uploading the file (PUT request)
-   `file_path`: S3 path to use when creating the post (save this!)
-   `file_url`: Public URL to access the file after upload
-   `expires_in`: Time in seconds until the presigned URL expires (900 = 15 minutes)
-   `upload_method`: Always `"direct"` for this endpoint
-   `file_size`: Echo of the file size you provided
-   `content_type`: Echo of the content type you provided

---

## Error Responses

### 422 Unprocessable Entity - Validation Errors

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "filename": ["The filename field is required."],
        "content_type": ["The content type field is required."],
        "file_size": ["The file size must be at least 1."]
    }
}
```

### 401 Unauthorized - Missing Authentication

```json
{
    "success": false,
    "message": "Authentication required",
    "error": "User not authenticated"
}
```

### 500 Internal Server Error

```json
{
    "success": false,
    "message": "Failed to generate upload URL",
    "error": "Error message details"
}
```

---

## How It Works

### Step-by-Step Process

1. **Request Presigned URL**: Call this endpoint with file details
2. **Upload to S3**: Use the `upload_url` to PUT the file directly to S3
3. **Create Post**: Use the `file_path` to create a post via `/api/posts/create-from-s3`

### Example Workflow

```javascript
// Step 1: Get presigned URL
const response = await fetch("/api/posts/upload-url", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
    body: JSON.stringify({
        filename: "photo.jpg",
        content_type: "image/jpeg",
        file_size: 2048000,
    }),
});

const { data } = await response.json();
// data.upload_url = presigned URL
// data.file_path = "posts/1234567890_1_abc123.jpg"

// Step 2: Upload file directly to S3
const uploadResponse = await fetch(data.upload_url, {
    method: "PUT",
    headers: {
        "Content-Type": data.content_type,
    },
    body: file, // Your file object
});

if (uploadResponse.ok) {
    // Step 3: Create post using file_path
    const postResponse = await fetch("/api/posts/create-from-s3", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            file_path: data.file_path, // Use the file_path from step 1
            caption: "My photo",
            category_id: 1,
        }),
    });
}
```

---

## Complete Example

### JavaScript/React Example

```javascript
async function uploadFileAndCreatePost(file, caption, categoryId) {
    const token = "your_access_token";

    // Step 1: Get presigned URL
    const urlResponse = await fetch("/api/posts/upload-url", {
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

    if (!urlResponse.ok) {
        throw new Error("Failed to get upload URL");
    }

    const { data } = await urlResponse.json();

    // Step 2: Upload file to S3 using presigned URL
    const uploadResponse = await fetch(data.upload_url, {
        method: "PUT",
        headers: {
            "Content-Type": data.content_type,
        },
        body: file,
    });

    if (!uploadResponse.ok) {
        throw new Error("Failed to upload file to S3");
    }

    // Step 3: Create post using the file_path
    const postResponse = await fetch("/api/posts/create-from-s3", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            file_path: data.file_path,
            caption: caption,
            category_id: categoryId,
        }),
    });

    return postResponse.json();
}

// Usage
const fileInput = document.querySelector("#file-input");
const file = fileInput.files[0];

uploadFileAndCreatePost(file, "My amazing photo!", 1)
    .then((result) => console.log("Post created:", result))
    .catch((error) => console.error("Error:", error));
```

### Multiple Files Example

```javascript
async function uploadMultipleFilesAndCreatePost(files, caption, categoryId) {
    const token = "your_access_token";

    // Step 1: Get presigned URLs for all files
    const urlPromises = Array.from(files).map((file) =>
        fetch("/api/posts/upload-url", {
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
        }).then((res) => res.json())
    );

    const urlResults = await Promise.all(urlPromises);

    // Step 2: Upload all files to S3
    const uploadPromises = urlResults.map((result, index) =>
        fetch(result.data.upload_url, {
            method: "PUT",
            headers: {
                "Content-Type": result.data.content_type,
            },
            body: files[index],
        })
    );

    await Promise.all(uploadPromises);

    // Step 3: Create post with multiple file paths
    const filePaths = urlResults.map((result) => result.data.file_path);

    const postResponse = await fetch("/api/posts/create-from-s3", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            file_paths: filePaths,
            caption: caption,
            category_id: categoryId,
        }),
    });

    return postResponse.json();
}
```

---

## Important Notes

### 1. URL Expiration

-   Presigned URLs expire after **15 minutes** (900 seconds)
-   Upload the file immediately after receiving the URL
-   If expired, request a new URL

### 2. File Path

-   **Save the `file_path`** from the response
-   Use this `file_path` when creating the post via `/api/posts/create-from-s3`
-   Don't use the `upload_url` for creating posts

### 3. Upload Method

-   Use **PUT** method to upload to the presigned URL
-   Include the `Content-Type` header matching the `content_type` you provided
-   The file should be in the request body

### 4. File Naming

-   The server generates a unique filename automatically
-   Format: `{timestamp}_{user_id}_{random}.{extension}`
-   Example: `1234567890_1_abc123def.jpg`
-   This prevents filename conflicts

### 5. File Size Limits

-   Minimum: 1 byte
-   Maximum: 2GB (2,147,483,648 bytes)
-   For larger files, use `/api/posts/chunked-upload-url`

### 6. Supported File Types

-   **Images**: `image/jpeg`, `image/png`, `image/gif`
-   **Videos**: `video/mp4`, `video/mov`, `video/avi`, `video/mkv`, `video/webm`

### 7. Error Handling

-   Always check if the upload to S3 was successful (HTTP 200)
-   If upload fails, don't create the post
-   Handle expired URLs by requesting a new one

---

## Related Endpoints

-   `POST /api/posts/create-from-s3` - Create post using uploaded file path
-   `POST /api/posts/chunked-upload-url` - Get presigned URLs for chunked uploads (large files)
-   `POST /api/posts/complete-chunked-upload` - Complete chunked upload

---

## Troubleshooting

### Issue: Upload URL expires before upload completes

**Solution**: Upload the file immediately after receiving the URL. For large files, use chunked upload.

### Issue: 403 Forbidden when uploading to presigned URL

**Solution**:

-   Check that you're using PUT method
-   Verify Content-Type header matches the one you provided
-   Ensure the URL hasn't expired

### Issue: File not found when creating post

**Solution**:

-   Verify the file was successfully uploaded (check HTTP status)
-   Wait a few seconds for S3 to process the upload
-   Double-check you're using `file_path` (not `upload_url`) when creating the post

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20
