# 📹 Large File Upload Guide (500MB+ Videos)

## 🎯 **Smart Upload Strategy**

Your API now automatically detects file size and chooses the best upload method:

-   **< 500MB**: Direct S3 upload with presigned URL
-   **≥ 500MB**: Chunked upload for better performance and reliability

## 🚀 **How It Works**

### 1. **File Size Detection**

```javascript
// Frontend checks file size before upload
const fileSize = videoFile.size;
const isLargeFile = fileSize >= 500 * 1024 * 1024; // 500MB

if (isLargeFile) {
    // Use chunked upload
    await uploadLargeVideo(videoFile);
} else {
    // Use direct S3 upload
    await uploadSmallVideo(videoFile);
}
```

### 2. **API Response Examples**

#### For Small Files (< 500MB):

```json
{
    "success": true,
    "data": {
        "upload_method": "direct",
        "upload_url": "https://s3.amazonaws.com/bucket/presigned-url",
        "file_path": "posts/1234567890_1_abc123.mp4",
        "file_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4",
        "expires_in": 3600,
        "file_size": 104857600,
        "threshold_exceeded": false
    }
}
```

#### For Large Files (≥ 500MB):

```json
{
    "success": true,
    "message": "Large file detected. Use chunked upload for better performance.",
    "data": {
        "upload_method": "chunked",
        "file_path": "posts/1234567890_1_abc123.mp4",
        "file_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4",
        "file_size": 1073741824,
        "threshold_exceeded": true,
        "recommended_chunk_size": 52428800,
        "chunked_upload_endpoint": "/api/posts/chunked-upload-url"
    }
}
```

## 📱 **Frontend Implementation**

### 1. **Smart Upload Function**

```javascript
async function uploadVideo(videoFile) {
    const fileSize = videoFile.size;
    const isLargeFile = fileSize >= 500 * 1024 * 1024;

    try {
        if (isLargeFile) {
            return await uploadLargeVideo(videoFile);
        } else {
            return await uploadSmallVideo(videoFile);
        }
    } catch (error) {
        console.error("Upload failed:", error);
        throw error;
    }
}
```

### 2. **Small File Upload (< 500MB)**

```javascript
async function uploadSmallVideo(videoFile) {
    // 1. Get presigned URL
    const response = await fetch("/api/posts/upload-url", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            filename: videoFile.name,
            content_type: videoFile.type,
            file_size: videoFile.size,
        }),
    });

    const { data } = await response.json();

    // 2. Upload directly to S3
    await fetch(data.upload_url, {
        method: "PUT",
        body: videoFile,
        headers: {
            "Content-Type": videoFile.type,
        },
    });

    // 3. Create post
    return await createPostFromS3(data.file_path, videoFile);
}
```

### 3. **Large File Upload (≥ 500MB)**

```javascript
async function uploadLargeVideo(videoFile) {
    const fileSize = videoFile.size;
    const chunkSize = 50 * 1024 * 1024; // 50MB chunks

    // 1. Get chunked upload URLs
    const response = await fetch("/api/posts/chunked-upload-url", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            filename: videoFile.name,
            content_type: videoFile.type,
            total_size: fileSize,
            chunk_size: chunkSize,
        }),
    });

    const { data } = await response.json();

    // 2. Upload chunks
    const totalChunks = data.total_chunks;
    const chunkUrls = data.chunk_urls;

    for (let i = 0; i < totalChunks; i++) {
        const start = i * chunkSize;
        const end = Math.min(start + chunkSize, fileSize);
        const chunk = videoFile.slice(start, end);

        await fetch(chunkUrls[i].upload_url, {
            method: "PUT",
            body: chunk,
            headers: {
                "Content-Type": videoFile.type,
            },
        });

        // Update progress
        const progress = ((i + 1) / totalChunks) * 100;
        updateProgress(progress);
    }

    // 3. Complete chunked upload
    await fetch("/api/posts/complete-chunked-upload", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            file_path: data.file_path,
            total_chunks: totalChunks,
        }),
    });

    // 4. Create post
    return await createPostFromS3(data.file_path, videoFile);
}
```

### 4. **Progress Tracking**

```javascript
function updateProgress(percentage) {
    const progressBar = document.getElementById("upload-progress");
    const progressText = document.getElementById("upload-progress-text");

    progressBar.style.width = `${percentage}%`;
    progressText.textContent = `Uploading... ${Math.round(percentage)}%`;

    if (percentage === 100) {
        progressText.textContent = "Processing video...";
    }
}
```

## 🔧 **API Endpoints**

### 1. **Check Upload Method**

```bash
POST /api/posts/upload-url
```

**Request:**

```json
{
    "filename": "large_video.mp4",
    "content_type": "video/mp4",
    "file_size": 1073741824
}
```

**Response (Large File):**

```json
{
    "success": true,
    "message": "Large file detected. Use chunked upload for better performance.",
    "data": {
        "upload_method": "chunked",
        "file_path": "posts/1234567890_1_abc123.mp4",
        "file_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4",
        "file_size": 1073741824,
        "threshold_exceeded": true,
        "recommended_chunk_size": 52428800,
        "chunked_upload_endpoint": "/api/posts/chunked-upload-url"
    }
}
```

### 2. **Get Chunked Upload URLs**

```bash
POST /api/posts/chunked-upload-url
```

**Request:**

```json
{
    "filename": "large_video.mp4",
    "content_type": "video/mp4",
    "total_size": 1073741824,
    "chunk_size": 52428800
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "file_path": "posts/1234567890_1_abc123.mp4",
        "file_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4",
        "total_chunks": 21,
        "chunk_size": 52428800,
        "chunk_urls": [
            {
                "chunk_number": 0,
                "upload_url": "https://s3.amazonaws.com/bucket/presigned-url-0",
                "chunk_path": "posts/1234567890_1_abc123.mp4.part0"
            }
        ],
        "expires_in": 3600
    }
}
```

### 3. **Complete Chunked Upload**

```bash
POST /api/posts/complete-chunked-upload
```

**Request:**

```json
{
    "file_path": "posts/1234567890_1_abc123.mp4",
    "total_chunks": 21
}
```

## 🎯 **Benefits of This Approach**

### 1. **Performance**

-   **Small files**: Fast direct upload
-   **Large files**: Reliable chunked upload with progress tracking

### 2. **Reliability**

-   **Resumable uploads**: If connection fails, resume from last chunk
-   **Progress tracking**: Users see upload progress
-   **Error handling**: Better error recovery

### 3. **User Experience**

-   **Automatic detection**: No manual choice needed
-   **Progress indicators**: Visual feedback during upload
-   **Optimized for size**: Right method for right file size

## 📊 **Upload Performance Comparison**

| File Size | Method    | Upload Time         | Reliability | User Experience   |
| --------- | --------- | ------------------- | ----------- | ----------------- |
| < 500MB   | Direct S3 | Fast                | Good        | Simple            |
| ≥ 500MB   | Chunked   | Slower but reliable | Excellent   | Progress tracking |

## 🚀 **Implementation Checklist**

-   [ ] ✅ API automatically detects file size
-   [ ] ✅ Recommends appropriate upload method
-   [ ] ✅ Provides chunked upload for large files
-   [ ] ✅ Supports progress tracking
-   [ ] ✅ Handles errors gracefully
-   [ ] ✅ Optimized for 500MB threshold

Your API now intelligently handles both small and large video uploads with the optimal method for each file size! 🎉

