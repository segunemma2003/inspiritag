# 🚀 Efficient Upload System for Large Files

## 🎯 **Problem Solved**

The original upload system had limitations:

-   ❌ **50MB file size limit**
-   ❌ **API timeouts for large files (1GB+ videos)**
-   ❌ **Server memory issues with large uploads**
-   ❌ **Poor user experience for large files**

## ✅ **New Efficient Upload System**

### **Three Upload Methods:**

1. **📁 Regular Upload** - For small files (< 50MB)
2. **🔗 Direct S3 Upload** - For medium files (50MB - 500MB)
3. **📦 Chunked Upload** - For large files (500MB - 2GB)

---

## 🔧 **API Endpoints**

### **1. Regular Upload (Existing)**

```bash
POST /api/posts
Content-Type: multipart/form-data

# For small files < 50MB
{
  "media": file,
  "caption": "My post",
  "category_id": 1,
  "tags": ["hair", "beauty"]
}
```

### **2. Direct S3 Upload (New)**

```bash
# Step 1: Get presigned URL
POST /api/posts/upload-url
Authorization: Bearer {token}
Content-Type: application/json

{
  "filename": "video.mp4",
  "content_type": "video/mp4",
  "file_size": 104857600
}

# Response:
{
  "success": true,
  "data": {
    "upload_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4?X-Amz-Signature=...",
    "file_path": "posts/1234567890_1_abc123.mp4",
    "file_url": "https://cdn.example.com/posts/1234567890_1_abc123.mp4",
    "expires_in": 3600,
    "max_file_size": 2147483648
  }
}

# Step 2: Upload directly to S3
PUT {upload_url}
Content-Type: video/mp4
Content-Length: 104857600

[Binary file data]

# Step 3: Create post
POST /api/posts/create-from-s3
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_path": "posts/1234567890_1_abc123.mp4",
  "caption": "My video post",
  "category_id": 1,
  "tags": ["video", "transformation"],
  "media_metadata": {
    "duration": 120,
    "resolution": "1920x1080"
  }
}
```

### **3. Chunked Upload (New)**

```bash
# Step 1: Get chunked upload URLs
POST /api/posts/chunked-upload-url
Authorization: Bearer {token}
Content-Type: application/json

{
  "filename": "large_video.mp4",
  "content_type": "video/mp4",
  "total_size": 1073741824,
  "chunk_size": 52428800
}

# Response:
{
  "success": true,
  "data": {
    "file_path": "posts/1234567890_1_abc123.mp4",
    "file_url": "https://cdn.example.com/posts/1234567890_1_abc123.mp4",
    "total_chunks": 20,
    "chunk_size": 52428800,
    "chunk_urls": [
      {
        "chunk_number": 0,
        "upload_url": "https://s3.amazonaws.com/bucket/posts/1234567890_1_abc123.mp4.part0?X-Amz-Signature=...",
        "chunk_path": "posts/1234567890_1_abc123.mp4.part0"
      },
      // ... more chunks
    ],
    "expires_in": 3600
  }
}

# Step 2: Upload each chunk
PUT {chunk_urls[0].upload_url}
Content-Type: video/mp4

[Chunk 0 binary data]

PUT {chunk_urls[1].upload_url}
Content-Type: video/mp4

[Chunk 1 binary data]

# ... continue for all chunks

# Step 3: Complete chunked upload
POST /api/posts/complete-chunked-upload
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_path": "posts/1234567890_1_abc123.mp4",
  "total_chunks": 20
}

# Step 4: Create post (same as Step 3 in Direct S3 Upload)
POST /api/posts/create-from-s3
Authorization: Bearer {token}
Content-Type: application/json

{
  "file_path": "posts/1234567890_1_abc123.mp4",
  "caption": "My large video post",
  "category_id": 1,
  "tags": ["video", "transformation"]
}
```

---

## 📱 **Frontend Implementation**

### **React/JavaScript Example:**

```javascript
class EfficientUploader {
    constructor(apiBaseUrl, authToken) {
        this.apiBaseUrl = apiBaseUrl;
        this.authToken = authToken;
    }

    // Method 1: Regular upload for small files
    async uploadSmallFile(file, postData) {
        const formData = new FormData();
        formData.append("media", file);
        formData.append("caption", postData.caption);
        formData.append("category_id", postData.categoryId);
        if (postData.tags) {
            postData.tags.forEach((tag) => formData.append("tags[]", tag));
        }

        const response = await fetch(`${this.apiBaseUrl}/posts`, {
            method: "POST",
            headers: {
                Authorization: `Bearer ${this.authToken}`,
            },
            body: formData,
        });

        return response.json();
    }

    // Method 2: Direct S3 upload for medium files
    async uploadMediumFile(file, postData) {
        // Step 1: Get presigned URL
        const urlResponse = await fetch(`${this.apiBaseUrl}/posts/upload-url`, {
            method: "POST",
            headers: {
                Authorization: `Bearer ${this.authToken}`,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                filename: file.name,
                content_type: file.type,
                file_size: file.size,
            }),
        });

        const { data } = await urlResponse.json();

        // Step 2: Upload directly to S3
        const uploadResponse = await fetch(data.upload_url, {
            method: "PUT",
            headers: {
                "Content-Type": file.type,
                "Content-Length": file.size,
            },
            body: file,
        });

        if (!uploadResponse.ok) {
            throw new Error("S3 upload failed");
        }

        // Step 3: Create post
        const postResponse = await fetch(
            `${this.apiBaseUrl}/posts/create-from-s3`,
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${this.authToken}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    file_path: data.file_path,
                    caption: postData.caption,
                    category_id: postData.categoryId,
                    tags: postData.tags,
                    media_metadata: {
                        duration: postData.duration,
                        resolution: postData.resolution,
                    },
                }),
            }
        );

        return postResponse.json();
    }

    // Method 3: Chunked upload for large files
    async uploadLargeFile(file, postData, chunkSize = 50 * 1024 * 1024) {
        // 50MB chunks
        // Step 1: Get chunked upload URLs
        const urlResponse = await fetch(
            `${this.apiBaseUrl}/posts/chunked-upload-url`,
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${this.authToken}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    filename: file.name,
                    content_type: file.type,
                    total_size: file.size,
                    chunk_size: chunkSize,
                }),
            }
        );

        const { data } = await urlResponse.json();

        // Step 2: Upload chunks
        const uploadPromises = [];
        for (let i = 0; i < data.total_chunks; i++) {
            const start = i * data.chunk_size;
            const end = Math.min(start + data.chunk_size, file.size);
            const chunk = file.slice(start, end);

            const chunkData = data.chunk_urls[i];
            const uploadPromise = fetch(chunkData.upload_url, {
                method: "PUT",
                headers: {
                    "Content-Type": file.type,
                },
                body: chunk,
            });

            uploadPromises.push(uploadPromise);
        }

        // Wait for all chunks to upload
        const uploadResults = await Promise.all(uploadPromises);
        const failedUploads = uploadResults.filter((result) => !result.ok);

        if (failedUploads.length > 0) {
            throw new Error(`${failedUploads.length} chunks failed to upload`);
        }

        // Step 3: Complete chunked upload
        const completeResponse = await fetch(
            `${this.apiBaseUrl}/posts/complete-chunked-upload`,
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${this.authToken}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    file_path: data.file_path,
                    total_chunks: data.total_chunks,
                }),
            }
        );

        if (!completeResponse.ok) {
            throw new Error("Failed to complete chunked upload");
        }

        // Step 4: Create post
        const postResponse = await fetch(
            `${this.apiBaseUrl}/posts/create-from-s3`,
            {
                method: "POST",
                headers: {
                    Authorization: `Bearer ${this.authToken}`,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({
                    file_path: data.file_path,
                    caption: postData.caption,
                    category_id: postData.categoryId,
                    tags: postData.tags,
                    media_metadata: {
                        duration: postData.duration,
                        resolution: postData.resolution,
                    },
                }),
            }
        );

        return postResponse.json();
    }

    // Smart upload method that chooses the best approach
    async uploadFile(file, postData) {
        const fileSize = file.size;
        const maxRegularSize = 50 * 1024 * 1024; // 50MB
        const maxDirectSize = 500 * 1024 * 1024; // 500MB

        if (fileSize <= maxRegularSize) {
            return this.uploadSmallFile(file, postData);
        } else if (fileSize <= maxDirectSize) {
            return this.uploadMediumFile(file, postData);
        } else {
            return this.uploadLargeFile(file, postData);
        }
    }
}

// Usage
const uploader = new EfficientUploader(
    "http://[SERVER_IP]/api",
    "your-token"
);

const file = document.getElementById("fileInput").files[0];
const postData = {
    caption: "My amazing transformation!",
    categoryId: 1,
    tags: ["hair", "beauty"],
    duration: 120,
    resolution: "1920x1080",
};

try {
    const result = await uploader.uploadFile(file, postData);
    console.log("Upload successful:", result);
} catch (error) {
    console.error("Upload failed:", error);
}
```

---

## ⚡ **Performance Benefits**

### **Before (Regular Upload):**

-   ❌ **50MB limit**
-   ❌ **Server processes entire file**
-   ❌ **Memory usage = file size**
-   ❌ **Single point of failure**
-   ❌ **API timeouts**

### **After (Efficient Upload):**

-   ✅ **2GB file size limit**
-   ✅ **Direct S3 upload (no server processing)**
-   ✅ **Minimal server memory usage**
-   ✅ **Resumable uploads (chunked)**
-   ✅ **No API timeouts**
-   ✅ **Better user experience**
-   ✅ **Progress tracking**

---

## 🛡️ **Security Features**

-   ✅ **Presigned URLs with expiration (1 hour)**
-   ✅ **Content-Type validation**
-   ✅ **File size limits**
-   ✅ **User authentication required**
-   ✅ **Unique file naming**
-   ✅ **S3 bucket policies**

---

## 📊 **File Size Recommendations**

| File Size    | Method         | Chunk Size | Benefits             |
| ------------ | -------------- | ---------- | -------------------- |
| < 50MB       | Regular Upload | N/A        | Simple, fast         |
| 50MB - 500MB | Direct S3      | N/A        | No server processing |
| 500MB - 2GB  | Chunked Upload | 50MB       | Resumable, reliable  |

---

## 🧪 **Testing Examples**

### **Test Direct S3 Upload:**

```bash
# Get presigned URL
curl -X POST http://[SERVER_IP]/api/posts/upload-url \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "filename": "test.mp4",
    "content_type": "video/mp4",
    "file_size": 104857600
  }'

# Upload to S3 (use the upload_url from response)
curl -X PUT {upload_url} \
  -H "Content-Type: video/mp4" \
  -H "Content-Length: 104857600" \
  --data-binary @test.mp4

# Create post
curl -X POST http://[SERVER_IP]/api/posts/create-from-s3 \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "file_path": "posts/1234567890_1_abc123.mp4",
    "caption": "Test video",
    "category_id": 1,
    "tags": ["test"]
  }'
```

---

## ✅ **Implementation Status**

-   ✅ **Direct S3 Upload** - Implemented
-   ✅ **Chunked Upload** - Implemented
-   ✅ **Presigned URLs** - Implemented
-   ✅ **File Validation** - Implemented
-   ✅ **Security Measures** - Implemented
-   ✅ **API Documentation** - Complete
-   ✅ **Frontend Examples** - Provided

**Your efficient upload system is now ready for large files up to 2GB!** 🚀✨
