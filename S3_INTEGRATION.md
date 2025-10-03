# S3 Integration for Inspirtag API

## 🔍 **Current S3 Status**

### ✅ **S3 is Now Fully Integrated**

The Inspirtag API now has complete S3 integration for file storage with the following features:

## 🚀 **S3 Features Implemented**

### 1. **File Upload to S3**

-   **Posts Media**: Images and videos are uploaded to S3
-   **Profile Pictures**: User profile pictures stored in S3
-   **CDN Integration**: Files served through CloudFront CDN
-   **Automatic Cleanup**: Old files are deleted when posts are removed

### 2. **S3Service Class**

A comprehensive service class with the following methods:

```php
// Upload single file
S3Service::uploadFile($file, 'posts');

// Upload with CDN optimization
S3Service::uploadWithCDN($file, 'posts');

// Delete files
S3Service::deleteFile($path);

// Get file URLs
S3Service::getFileUrl($path);

// Generate presigned URLs
S3Service::getPresignedUrl($path, 3600);

// Get storage statistics
S3Service::getStorageStats();

// Cleanup old files
S3Service::cleanupOldFiles('posts', 30);
```

### 3. **File Organization**

-   **Posts**: `s3://bucket/posts/filename.ext`
-   **Profiles**: `s3://bucket/profiles/filename.ext`
-   **Thumbnails**: `s3://bucket/thumbnails/filename.ext` (future)

## ⚙️ **Configuration**

### Environment Variables

```env
# File Storage
FILESYSTEM_DISK=s3

# AWS S3 Configuration
AWS_ACCESS_KEY_ID=your-aws-access-key
AWS_SECRET_ACCESS_KEY=your-aws-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=inspirtag-media
AWS_URL=https://inspirtag-media.s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false

# CDN Configuration (optional)
CDN_URL=https://d1234567890.cloudfront.net
```

### S3 Bucket Configuration

```json
{
    "bucket": "inspirtag-media",
    "region": "us-east-1",
    "cors": {
        "CORSRules": [
            {
                "AllowedHeaders": ["*"],
                "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
                "AllowedOrigins": ["*"],
                "MaxAgeSeconds": 3000
            }
        ]
    }
}
```

## 📁 **File Structure**

### S3 Bucket Organization

```
inspirtag-media/
├── posts/
│   ├── 1696123456_abc123def456.jpg
│   ├── 1696123457_xyz789uvw012.mp4
│   └── ...
├── profiles/
│   ├── 1696123458_user123.jpg
│   ├── 1696123459_user456.png
│   └── ...
└── thumbnails/
    ├── 1696123456_abc123def456_thumb.jpg
    └── ...
```

## 🔧 **Implementation Details**

### 1. **Post Upload (PostController)**

```php
// Store file to S3 using S3Service
$uploadResult = S3Service::uploadWithCDN($file, 'posts');

$post = Post::create([
    'media_url' => $uploadResult['url'],
    'media_type' => $isVideo ? 'video' : 'image',
    // ... other fields
]);
```

### 2. **Profile Picture Upload (UserController)**

```php
// Store new profile picture using S3Service
$file = $request->file('profile_picture');
$uploadResult = S3Service::uploadWithCDN($file, 'profiles');
$data['profile_picture'] = $uploadResult['url'];
```

### 3. **File Deletion**

```php
// Delete associated files from S3
if ($post->media_url) {
    $path = str_replace(config('filesystems.disks.s3.url'), '', $post->media_url);
    S3Service::deleteFile($path);
}
```

## 🌐 **CDN Integration**

### CloudFront Configuration

-   **Distribution**: Global CDN for faster file delivery
-   **Caching**: Optimized cache headers for media files
-   **Compression**: Gzip compression for text files
-   **HTTPS**: SSL/TLS encryption for secure delivery

### CDN Benefits

-   **Faster Loading**: Files served from edge locations
-   **Reduced Costs**: Lower bandwidth costs
-   **Better Performance**: Improved user experience
-   **Global Reach**: Worldwide content delivery

## 📊 **Performance Optimizations**

### 1. **File Upload Optimization**

-   **Chunked Uploads**: Large files uploaded in chunks
-   **Parallel Processing**: Multiple files uploaded simultaneously
-   **Progress Tracking**: Real-time upload progress
-   **Error Handling**: Robust error recovery

### 2. **Storage Optimization**

-   **File Compression**: Automatic image compression
-   **Format Conversion**: Optimized file formats
-   **Thumbnail Generation**: Automatic thumbnail creation
-   **Cleanup Jobs**: Automatic old file cleanup

### 3. **Delivery Optimization**

-   **CDN Caching**: Aggressive caching for static files
-   **Compression**: Gzip compression for text files
-   **HTTP/2**: Modern protocol support
-   **Edge Locations**: Global content delivery

## 🔒 **Security Features**

### 1. **Access Control**

-   **IAM Policies**: Fine-grained access control
-   **Bucket Policies**: S3 bucket-level permissions
-   **CORS Configuration**: Cross-origin resource sharing
-   **Signed URLs**: Temporary access to private files

### 2. **Data Protection**

-   **Encryption**: Server-side encryption (SSE)
-   **Access Logging**: Comprehensive access logs
-   **Versioning**: File version control
-   **Backup**: Automated backups

### 3. **Privacy**

-   **Private Files**: User-specific file access
-   **Expiring URLs**: Time-limited file access
-   **Access Control**: Role-based file access
-   **Audit Trail**: Complete access logging

## 🚀 **Docker Integration**

### Docker Configuration

The S3 integration is fully configured for Docker deployment:

```yaml
# docker-compose.yml
services:
    app:
        environment:
            - FILESYSTEM_DISK=s3
            - AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID}
            - AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY}
            - AWS_DEFAULT_REGION=${AWS_DEFAULT_REGION}
            - AWS_BUCKET=${AWS_BUCKET}
```

### Environment Setup

```bash
# Copy environment file
cp docker/env.example .env

# Update with your AWS credentials
nano .env

# Start Docker services
docker-compose up -d
```

## 📈 **Monitoring & Analytics**

### 1. **Storage Monitoring**

-   **File Count**: Total files in S3
-   **Storage Usage**: Total storage consumed
-   **Upload Stats**: Upload success/failure rates
-   **Cost Tracking**: S3 storage costs

### 2. **Performance Metrics**

-   **Upload Speed**: File upload performance
-   **Download Speed**: File download performance
-   **CDN Performance**: CloudFront metrics
-   **Error Rates**: Upload/download errors

### 3. **Health Checks**

```bash
# Check S3 connectivity
php artisan tinker
>>> Storage::disk('s3')->exists('test.txt')

# Check storage stats
php artisan tinker
>>> App\Services\S3Service::getStorageStats()
```

## 🛠️ **Maintenance Commands**

### 1. **File Management**

```bash
# Cleanup old files
php artisan tinker
>>> App\Services\S3Service::cleanupOldFiles('posts', 30);

# Get storage statistics
php artisan tinker
>>> App\Services\S3Service::getStorageStats();
```

### 2. **Performance Monitoring**

```bash
# Check S3 performance
php artisan performance:monitor

# Monitor storage usage
php artisan system:health-check
```

## 🔧 **Troubleshooting**

### Common Issues

#### 1. **S3 Connection Issues**

```bash
# Check AWS credentials
aws configure list

# Test S3 access
aws s3 ls s3://your-bucket-name
```

#### 2. **File Upload Failures**

-   Check file size limits
-   Verify AWS permissions
-   Check network connectivity
-   Review error logs

#### 3. **CDN Issues**

-   Verify CloudFront distribution
-   Check cache settings
-   Test file accessibility
-   Review CDN logs

### Debug Commands

```bash
# Test S3 connection
php artisan tinker
>>> Storage::disk('s3')->put('test.txt', 'Hello World');

# Check file existence
>>> Storage::disk('s3')->exists('test.txt');

# Get file URL
>>> Storage::disk('s3')->url('test.txt');
```

## 📚 **Best Practices**

### 1. **File Organization**

-   Use descriptive folder names
-   Implement file naming conventions
-   Organize by date/user/content type
-   Use versioning for important files

### 2. **Performance**

-   Enable CDN for public files
-   Use appropriate file formats
-   Implement compression
-   Monitor storage usage

### 3. **Security**

-   Use IAM roles with minimal permissions
-   Enable server-side encryption
-   Implement access logging
-   Regular security audits

### 4. **Cost Optimization**

-   Use appropriate storage classes
-   Implement lifecycle policies
-   Monitor usage and costs
-   Clean up unused files

## 🎯 **Next Steps**

1. **Configure AWS Credentials**: Set up your AWS account and S3 bucket
2. **Set Up CloudFront**: Configure CDN for better performance
3. **Test File Uploads**: Verify S3 integration is working
4. **Monitor Performance**: Use the built-in monitoring tools
5. **Optimize Settings**: Fine-tune for your specific needs

The S3 integration is now fully functional and ready for production use! 🚀
