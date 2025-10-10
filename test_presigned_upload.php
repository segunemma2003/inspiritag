<?php
// test_presigned_upload.php

class PresignedUploadTest {
    private $baseUrl;
    private $token;

    public function __construct($baseUrl) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function login($email, $password) {
        $response = $this->makeRequest('POST', '/api/login', [
            'email' => $email,
            'password' => $password
        ]);

        if ($response['success']) {
            $this->token = $response['data']['token'];
            echo "✅ Login successful\n";
            return true;
        } else {
            echo "❌ Login failed: " . $response['message'] . "\n";
            return false;
        }
    }

    public function testPresignedUpload($filePath, $contentType = 'image/jpeg') {
        if (!$this->token) {
            echo "❌ Please login first\n";
            return false;
        }

        if (!file_exists($filePath)) {
            echo "❌ Test file not found: $filePath\n";
            return false;
        }

        $filename = basename($filePath);
        $fileSize = filesize($filePath);

        echo "📤 Testing presigned upload for: $filename ($fileSize bytes)\n";

        // Step 1: Get presigned URL
        echo "Step 1: Getting presigned URL...\n";
        $uploadResponse = $this->makeRequest('POST', '/api/posts/upload-url', [
            'filename' => $filename,
            'content_type' => $contentType,
            'file_size' => $fileSize
        ]);

        if (!$uploadResponse['success']) {
            echo "❌ Failed to get upload URL: " . $uploadResponse['message'] . "\n";
            if (isset($uploadResponse['errors'])) {
                echo "Validation errors: " . json_encode($uploadResponse['errors']) . "\n";
            }
            return false;
        }

        $uploadData = $uploadResponse['data'];
        echo "✅ Got presigned URL: " . substr($uploadData['upload_url'], 0, 100) . "...\n";
        echo "   File path: " . $uploadData['file_path'] . "\n";
        echo "   Upload method: " . $uploadData['upload_method'] . "\n";
        echo "   Expires in: " . $uploadData['expires_in'] . " seconds\n";

        // Step 2: Upload to S3
        echo "Step 2: Uploading file to S3...\n";
        $uploadResult = $this->uploadToS3($uploadData['upload_url'], $filePath, $contentType);

        if (!$uploadResult) {
            echo "❌ Failed to upload to S3\n";
            return false;
        }

        echo "✅ File uploaded to S3 successfully\n";

        // Step 3: Create post
        echo "Step 3: Creating post...\n";
        $postResponse = $this->makeRequest('POST', '/api/posts/create-from-s3', [
            'file_path' => $uploadData['file_path'],
            'caption' => 'Test post uploaded via presigned URL - ' . date('Y-m-d H:i:s'),
            'category_id' => 1, // Assuming category 1 exists
            'tags' => ['test', 'upload', 'presigned'],
            'location' => 'Test Location'
        ]);

        if ($postResponse['success']) {
            echo "✅ Post created successfully!\n";
            echo "   Post ID: " . $postResponse['data']['post']['id'] . "\n";
            echo "   Media URL: " . $postResponse['data']['post']['media_url'] . "\n";
            echo "   Caption: " . $postResponse['data']['post']['caption'] . "\n";
            return true;
        } else {
            echo "❌ Failed to create post: " . $postResponse['message'] . "\n";
            if (isset($postResponse['errors'])) {
                echo "Validation errors: " . json_encode($postResponse['errors']) . "\n";
            }
            return false;
        }
    }

    public function testVideoUpload($filePath) {
        return $this->testPresignedUpload($filePath, 'video/mp4');
    }

    public function testChunkedUpload($filePath, $contentType = 'video/mp4') {
        if (!$this->token) {
            echo "❌ Please login first\n";
            return false;
        }

        if (!file_exists($filePath)) {
            echo "❌ Test file not found: $filePath\n";
            return false;
        }

        $filename = basename($filePath);
        $fileSize = filesize($filePath);
        $chunkSize = 10 * 1024 * 1024; // 10MB chunks

        echo "📤 Testing chunked upload for: $filename ($fileSize bytes)\n";

        // Step 1: Get chunked upload URLs
        echo "Step 1: Getting chunked upload URLs...\n";
        $uploadResponse = $this->makeRequest('POST', '/api/posts/chunked-upload-url', [
            'filename' => $filename,
            'content_type' => $contentType,
            'total_size' => $fileSize,
            'chunk_size' => $chunkSize
        ]);

        if (!$uploadResponse['success']) {
            echo "❌ Failed to get chunked upload URLs: " . $uploadResponse['message'] . "\n";
            return false;
        }

        $uploadData = $uploadResponse['data'];
        $totalChunks = $uploadData['total_chunks'];

        echo "✅ Got chunked upload URLs for $totalChunks chunks\n";

        // Step 2: Upload chunks
        echo "Step 2: Uploading chunks...\n";
        $chunkUrls = $uploadData['chunk_urls'];

        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkStart = $i * $chunkSize;
            $chunkEnd = min($chunkStart + $chunkSize, $fileSize) - 1;
            $chunkLength = $chunkEnd - $chunkStart + 1;

            echo "   Uploading chunk " . ($i + 1) . "/$totalChunks...\n";

            $chunkData = file_get_contents($filePath, false, null, $chunkStart, $chunkLength);
            $tempFile = tempnam(sys_get_temp_dir(), 'chunk_');
            file_put_contents($tempFile, $chunkData);

            $chunkUploadResult = $this->uploadToS3($chunkUrls[$i]['upload_url'], $tempFile, $contentType);
            unlink($tempFile);

            if (!$chunkUploadResult) {
                echo "❌ Failed to upload chunk " . ($i + 1) . "\n";
                return false;
            }
        }

        echo "✅ All chunks uploaded successfully\n";

        // Step 3: Complete chunked upload
        echo "Step 3: Completing chunked upload...\n";
        $completeResponse = $this->makeRequest('POST', '/api/posts/complete-chunked-upload', [
            'file_path' => $uploadData['file_path'],
            'total_chunks' => $totalChunks
        ]);

        if (!$completeResponse['success']) {
            echo "❌ Failed to complete chunked upload: " . $completeResponse['message'] . "\n";
            return false;
        }

        echo "✅ Chunked upload completed\n";
        return true;
    }

    private function uploadToS3($presignedUrl, $filePath, $contentType) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $presignedUrl,
            CURLOPT_PUT => true,
            CURLOPT_INFILE => fopen($filePath, 'r'),
            CURLOPT_INFILESIZE => filesize($filePath),
            CURLOPT_HTTPHEADER => [
                // Don't send any headers - let the presigned URL handle everything
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => false, // For testing only
            CURLOPT_SSL_VERIFYHOST => false, // For testing only
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            echo "❌ cURL error: $error\n";
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            echo "❌ Upload failed with HTTP code: $httpCode\n";
            echo "Response: " . substr($response, 0, 500) . "\n";
            return false;
        }
    }

    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        switch ($method) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if ($data) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            throw new Exception("cURL error: $error");
        }

        return json_decode($response, true) ?: ['success' => false, 'message' => 'Invalid JSON response'];
    }

    public function getCategories() {
        $response = $this->makeRequest('GET', '/api/categories');

        if ($response && isset($response['data'])) {
            echo "📋 Available categories:\n";
            foreach ($response['data'] as $category) {
                echo "   ID: {$category['id']} - {$category['name']}\n";
            }
            return $response['data'];
        }

        return [];
    }
}

// Usage
$baseUrl = 'http://38.180.244.178';
$test = new PresignedUploadTest($baseUrl);

// Configuration - Update these with your test credentials
$email = 'testuser1760092397@example.com';
$password = 'password123';

echo "🚀 Presigned Upload Test Suite\n";
echo "Base URL: $baseUrl\n";
echo "=====================================\n\n";

// Login
echo "🔐 Logging in...\n";
if (!$test->login($email, $password)) {
    echo "❌ Cannot proceed without login\n";
    echo "Please update the email and password variables in the script\n";
    exit(1);
}

// Get categories
echo "\n📋 Fetching available categories...\n";
$categories = $test->getCategories();

// Create a test image file if it doesn't exist
$testImagePath = sys_get_temp_dir() . '/test_image.jpg';
if (!file_exists($testImagePath)) {
    echo "📸 Creating test image...\n";

    // Try to create image using GD
    if (extension_loaded('gd')) {
        $image = imagecreate(800, 600);
        $bg = imagecolorallocate($image, 135, 206, 235); // Sky blue background
        $textColor = imagecolorallocate($image, 255, 255, 255); // White text
        $borderColor = imagecolorallocate($image, 0, 0, 0); // Black border

        // Draw border
        imagerectangle($image, 0, 0, 799, 599, $borderColor);

        // Add text
        imagestring($image, 5, 280, 250, 'Test Image', $textColor);
        imagestring($image, 3, 320, 280, date('Y-m-d H:i:s'), $textColor);
        imagestring($image, 2, 250, 320, 'Presigned Upload Test', $textColor);

        imagejpeg($image, $testImagePath, 90);
        imagedestroy($image);
        echo "✅ Created test image: $testImagePath\n";
    } else {
        echo "❌ GD extension not available, creating simple text file\n";
        file_put_contents($testImagePath, "This is a test file for upload testing.\nCreated at: " . date('Y-m-d H:i:s'));
    }
}

// Test 1: Basic presigned upload
echo "\n🧪 Test 1: Basic presigned upload\n";
echo "=====================================\n";
$success1 = $test->testPresignedUpload($testImagePath, 'image/jpeg');

// Test 2: Video upload (if we have a test video)
$testVideoPath = sys_get_temp_dir() . '/test_video.mp4';
if (!file_exists($testVideoPath)) {
    echo "\n📹 Creating test video file...\n";
    // Create a minimal video file (just for testing - not a real video)
    file_put_contents($testVideoPath, str_repeat("FAKE VIDEO CONTENT\n", 1000));
    echo "✅ Created test video file: $testVideoPath\n";
}

echo "\n🧪 Test 2: Video presigned upload\n";
echo "=====================================\n";
$success2 = $test->testPresignedUpload($testVideoPath, 'video/mp4');

// Test 3: Chunked upload for large files
echo "\n🧪 Test 3: Chunked upload (simulated large file)\n";
echo "=====================================\n";
$success3 = $test->testChunkedUpload($testVideoPath, 'video/mp4');

// Summary
echo "\n📊 Test Results Summary\n";
echo "=======================\n";
echo "Basic Image Upload: " . ($success1 ? "✅ PASSED" : "❌ FAILED") . "\n";
echo "Video Upload: " . ($success2 ? "✅ PASSED" : "❌ FAILED") . "\n";
echo "Chunked Upload: " . ($success3 ? "✅ PASSED" : "❌ FAILED") . "\n";

$totalTests = 3;
$passedTests = ($success1 ? 1 : 0) + ($success2 ? 1 : 0) + ($success3 ? 1 : 0);

echo "\nOverall: $passedTests/$totalTests tests passed\n";

if ($passedTests === $totalTests) {
    echo "🎉 All tests passed! Presigned upload is working correctly.\n";
} else {
    echo "⚠️  Some tests failed. Check the output above for details.\n";
}

// Clean up
echo "\n🧹 Cleaning up test files...\n";
if (file_exists($testImagePath)) {
    unlink($testImagePath);
    echo "   Removed: $testImagePath\n";
}
if (file_exists($testVideoPath)) {
    unlink($testVideoPath);
    echo "   Removed: $testVideoPath\n";
}
echo "✅ Cleanup complete\n";
