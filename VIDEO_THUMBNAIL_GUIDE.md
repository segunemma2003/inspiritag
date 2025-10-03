# Video Thumbnail Generation Guide

## Current Issue
Videos uploaded via presigned URLs don't automatically get thumbnails generated.

## Solutions

### Option 1: Frontend-Generated Thumbnails (Recommended)

#### Frontend Implementation (JavaScript)
```javascript
// Generate thumbnail from video
function generateVideoThumbnail(videoFile, timeInSeconds = 1) {
    return new Promise((resolve, reject) => {
        const video = document.createElement('video');
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        video.addEventListener('loadeddata', () => {
            video.currentTime = timeInSeconds;
        });
        
        video.addEventListener('seeked', () => {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            canvas.toBlob((blob) => {
                resolve(blob);
            }, 'image/jpeg', 0.8);
        });
        
        video.addEventListener('error', reject);
        video.src = URL.createObjectURL(videoFile);
        video.load();
    });
}

// Upload process
async function uploadVideoWithThumbnail(videoFile) {
    try {
        // 1. Get presigned URL for video
        const videoResponse = await fetch('/api/posts/upload-url', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                filename: videoFile.name,
                content_type: videoFile.type,
                file_size: videoFile.size
            })
        });
        
        const videoData = await videoResponse.json();
        
        // 2. Upload video to S3
        await fetch(videoData.data.upload_url, {
            method: 'PUT',
            body: videoFile,
            headers: {
                'Content-Type': videoFile.type
            }
        });
        
        // 3. Generate thumbnail
        const thumbnailBlob = await generateVideoThumbnail(videoFile);
        
        // 4. Get presigned URL for thumbnail
        const thumbnailResponse = await fetch('/api/posts/upload-url', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                filename: `thumb_${videoFile.name}.jpg`,
                content_type: 'image/jpeg',
                file_size: thumbnailBlob.size
            })
        });
        
        const thumbnailData = await thumbnailResponse.json();
        
        // 5. Upload thumbnail to S3
        await fetch(thumbnailData.data.upload_url, {
            method: 'PUT',
            body: thumbnailBlob,
            headers: {
                'Content-Type': 'image/jpeg'
            }
        });
        
        // 6. Create post with both video and thumbnail URLs
        const postResponse = await fetch('/api/posts/create-from-s3', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                file_path: videoData.data.file_path,
                thumbnail_path: thumbnailData.data.file_path,
                caption: 'My awesome video!',
                category_id: 1,
                tags: ['video', 'awesome'],
                media_metadata: {
                    duration: video.duration,
                    width: video.videoWidth,
                    height: video.videoHeight
                }
            })
        });
        
        return await postResponse.json();
        
    } catch (error) {
        console.error('Upload failed:', error);
        throw error;
    }
}
```

### Option 2: Backend Thumbnail Generation

#### Update the createFromS3 method to handle thumbnails:

```php
public function createFromS3(Request $request)
{
    $validator = Validator::make($request->all(), [
        'file_path' => 'required|string',
        'thumbnail_path' => 'nullable|string', // New field for thumbnail
        'caption' => 'nullable|string|max:2000',
        'category_id' => 'required|exists:categories,id',
        'tags' => 'nullable|array',
        'tags.*' => 'string|max:50',
        'location' => 'nullable|string|max:255',
        'media_metadata' => 'nullable|array',
    ]);

    // ... validation code ...

    $user = $request->user();
    $filePath = $request->file_path;
    $thumbnailPath = $request->thumbnail_path;

    // Verify file exists on S3
    if (!S3Service::exists($filePath)) {
        return response()->json([
            'success' => false,
            'message' => 'File not found on S3. Please upload first.'
        ], 404);
    }

    // Determine media type
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $isVideo = in_array($extension, ['mp4', 'mov', 'avi', 'mkv', 'webm']);
    $mediaType = $isVideo ? 'video' : 'image';

    // Get URLs
    $mediaUrl = S3Service::getUrl($filePath);
    $thumbnailUrl = $thumbnailPath ? S3Service::getUrl($thumbnailPath) : null;

    // For videos without thumbnails, generate a placeholder or use a default
    if ($isVideo && !$thumbnailUrl) {
        $thumbnailUrl = 'https://via.placeholder.com/800x600/cccccc/666666?text=Video+Thumbnail';
    }

    $post = Post::create([
        'user_id' => $user->id,
        'category_id' => $request->category_id,
        'caption' => $request->caption,
        'media_url' => $mediaUrl,
        'media_type' => $mediaType,
        'thumbnail_url' => $thumbnailUrl, // Set thumbnail URL
        'media_metadata' => $request->media_metadata,
        'location' => $request->location,
        'is_public' => true,
    ]);

    // ... rest of the method ...
}
```

### Option 3: Server-Side Video Processing (Advanced)

For automatic server-side thumbnail generation, you'd need:

1. **FFmpeg installed on server**
2. **Background job processing**
3. **Video processing service**

#### Example Job for Server-Side Thumbnail Generation:

```php
// app/Jobs/GenerateVideoThumbnail.php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Post;
use App\Services\S3Service;

class GenerateVideoThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function handle()
    {
        if ($this->post->media_type !== 'video') {
            return;
        }

        // Download video from S3
        $videoPath = storage_path('app/temp/' . $this->post->id . '.mp4');
        $thumbnailPath = storage_path('app/temp/' . $this->post->id . '_thumb.jpg');
        
        // Download video
        $videoContent = file_get_contents($this->post->media_url);
        file_put_contents($videoPath, $videoContent);

        // Generate thumbnail using FFmpeg
        $command = "ffmpeg -i {$videoPath} -ss 00:00:01.000 -vframes 1 {$thumbnailPath}";
        exec($command);

        if (file_exists($thumbnailPath)) {
            // Upload thumbnail to S3
            $thumbnailS3Path = 'thumbnails/' . $this->post->id . '_thumb.jpg';
            S3Service::uploadFile($thumbnailPath, $thumbnailS3Path);
            
            // Update post with thumbnail URL
            $this->post->update([
                'thumbnail_url' => S3Service::getUrl($thumbnailS3Path)
            ]);

            // Clean up temp files
            unlink($videoPath);
            unlink($thumbnailPath);
        }
    }
}
```

## Recommendation

**Use Option 1 (Frontend-generated thumbnails)** because:
- ✅ Faster processing
- ✅ No server resources needed
- ✅ Better user experience
- ✅ Works with presigned URLs
- ✅ No additional infrastructure required

The frontend can generate thumbnails immediately when the user selects a video file, providing instant feedback.
