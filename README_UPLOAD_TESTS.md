# Presigned Upload Test Suite

This directory contains comprehensive test scripts for testing the presigned URL upload functionality of the social media API.

## Files Created

### 1. `test_presigned_upload.php`

A comprehensive PHP test script that tests:

-   Basic presigned upload for images
-   Video upload via presigned URLs
-   Chunked upload for large files
-   Authentication flow
-   Post creation from S3

### 2. `test_upload_curl.sh`

A bash script using curl that tests:

-   Image upload via presigned URLs
-   Video upload via presigned URLs
-   Complete upload workflow
-   Colored output for better readability

### 3. `create_test_image.php`

A utility script for creating test files:

-   Test images in various sizes
-   Fake video files for testing
-   Multiple test files generation

## Quick Start

### Prerequisites

1. Valid test user credentials
2. At least one category in your database
3. S3 configuration properly set up
4. PHP with cURL extension
5. For bash script: `jq` command-line JSON processor

### Running the Tests

#### Option 1: PHP Test Script

```bash
# Update credentials in the script first
php test_presigned_upload.php
```

#### Option 2: Bash Test Script

```bash
# Update credentials in the script first
./test_upload_curl.sh
```

#### Option 3: Create Test Files Only

```bash
# Create a single test image
php create_test_image.php image 800 600

# Create a test video file
php create_test_image.php video /tmp/test.mp4 5

# Create multiple test files
php create_test_image.php multiple
```

## Configuration

Before running the tests, update the following in the scripts:

### In `test_presigned_upload.php`:

```php
$baseUrl = 'http://38.180.244.178';
$email = 'test@example.com';        // Your test email
$password = 'password';             // Your test password
```

### In `test_upload_curl.sh`:

```bash
BASE_URL="http://38.180.244.178"
EMAIL="test@example.com"            # Your test email
PASSWORD="password"                 # Your test password
```

## Test Flow

Each test follows this flow:

1. **Authentication**: Login to get bearer token
2. **Get Presigned URL**: Request upload URL from API
3. **Upload to S3**: Upload file directly to S3 using presigned URL
4. **Create Post**: Create post record using uploaded file
5. **Verification**: Check if post was created successfully

## API Endpoints Tested

-   `POST /api/login` - Authentication
-   `GET /api/categories` - Get available categories
-   `POST /api/posts/upload-url` - Get presigned URL
-   `POST /api/posts/create-from-s3` - Create post from S3
-   `POST /api/posts/chunked-upload-url` - Get chunked upload URLs
-   `POST /api/posts/complete-chunked-upload` - Complete chunked upload

## Expected Results

### Successful Test Output

```
✅ Login successful
✅ Got presigned URL
✅ File uploaded to S3 successfully
✅ Post created successfully!
   Post ID: 123
   Media URL: https://your-bucket.s3.amazonaws.com/posts/filename.jpg
   Caption: Test post uploaded via presigned URL
```

### Test Results Summary

```
Basic Image Upload: ✅ PASSED
Video Upload: ✅ PASSED
Chunked Upload: ✅ PASSED

Overall: 3/3 tests passed
🎉 All tests passed! Presigned upload is working correctly.
```

## Troubleshooting

### Common Issues

1. **Login Failed**

    - Check credentials in the script
    - Ensure user exists in database
    - Verify API endpoint is accessible

2. **Upload URL Failed**

    - Check S3 configuration
    - Verify AWS credentials
    - Check file size limits

3. **S3 Upload Failed**

    - Verify presigned URL is valid
    - Check network connectivity
    - Verify S3 bucket permissions

4. **Post Creation Failed**
    - Check if category_id exists
    - Verify file exists on S3
    - Check validation rules

### Debug Mode

To get more detailed output, you can modify the scripts to include debug information:

```php
// In test_presigned_upload.php
curl_setopt($ch, CURLOPT_VERBOSE, true);
```

```bash
# In test_upload_curl.sh
curl -v ...  # Add -v flag for verbose output
```

## File Cleanup

Both test scripts automatically clean up temporary files after testing. Test files are created in the system temp directory and removed after completion.

## Security Notes

-   Test scripts use temporary files that are automatically cleaned up
-   No sensitive data is stored in test files
-   Presigned URLs expire after 1 hour
-   Test credentials should be for development/testing only

## Extending the Tests

You can extend the tests by:

1. Adding new file types
2. Testing different file sizes
3. Adding error condition tests
4. Testing with different user permissions
5. Adding performance benchmarks

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Verify your API configuration
3. Check server logs for detailed error messages
4. Ensure all dependencies are installed
