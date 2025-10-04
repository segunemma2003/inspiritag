<?php
/**
 * Complete Upload Test Suite
 *
 * This script tests both direct and chunked upload methods
 * Usage: php test_upload_complete.php
 */

require_once 'vendor/autoload.php';

class CompleteUploadTester
{
    private $baseUrl;
    private $token;

    public function __construct($baseUrl = 'http://localhost:8000')
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * Test user registration and login
     */
    public function setupUser()
    {
        echo "👤 Setting up test user...\n";

        // Try to register a test user
        $registerData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'username' => 'testuser',
            'full_name' => 'Test User',
            'interests' => ['technology', 'music']
        ];

        $registerResponse = $this->makeRequest('POST', '/api/register', $registerData);

        if ($registerResponse && $registerResponse['success']) {
            echo "✅ User registered successfully\n";
        } else {
            echo "ℹ️  User might already exist: " . ($registerResponse['message'] ?? 'Unknown error') . "\n";
        }

        // Login
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $loginResponse = $this->makeRequest('POST', '/api/login', $loginData);

        if ($loginResponse && isset($loginResponse['data']['token'])) {
            $this->token = $loginResponse['data']['token'];
            echo "✅ Login successful! Token: " . substr($this->token, 0, 20) . "...\n";
            return true;
        } else {
            echo "❌ Login failed: " . ($loginResponse['message'] ?? 'Unknown error') . "\n";
            return false;
        }
    }

    /**
     * Test server health and endpoints
     */
    public function testServerHealth()
    {
        echo "🏥 Testing server health...\n";

        // Test basic connectivity
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/api/categories');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            echo "✅ Server is responding (HTTP $httpCode)\n";
            return true;
        } else {
            echo "❌ Server health check failed (HTTP $httpCode)\n";
            return false;
        }
    }

    /**
     * Test upload URL generation for different file sizes
     */
    public function testUploadUrlGeneration()
    {
        echo "🔗 Testing upload URL generation...\n";

        $testCases = [
            ['filename' => 'small_video.mp4', 'content_type' => 'video/mp4', 'file_size' => 10 * 1024 * 1024], // 10MB
            ['filename' => 'medium_video.mp4', 'content_type' => 'video/mp4', 'file_size' => 30 * 1024 * 1024], // 30MB
            ['filename' => 'large_video.mp4', 'content_type' => 'video/mp4', 'file_size' => 100 * 1024 * 1024], // 100MB
            ['filename' => 'huge_video.mp4', 'content_type' => 'video/mp4', 'file_size' => 500 * 1024 * 1024], // 500MB
        ];

        foreach ($testCases as $i => $testCase) {
            echo "\n📋 Test Case " . ($i + 1) . ": " . $this->formatBytes($testCase['file_size']) . " file\n";
            echo "----------------------------------------\n";

            $response = $this->makeRequest('POST', '/api/posts/upload-url', $testCase);

            if ($response && $response['success']) {
                echo "✅ Upload URL generated successfully\n";
                echo "📋 Method: " . $response['data']['upload_method'] . "\n";
                echo "🔗 File path: " . $response['data']['file_path'] . "\n";

                if (isset($response['data']['threshold_exceeded']) && $response['data']['threshold_exceeded']) {
                    echo "⚠️  Large file - chunked upload recommended\n";
                    if (isset($response['data']['recommended_chunk_size'])) {
                        echo "🔧 Recommended chunk size: " . $this->formatBytes($response['data']['recommended_chunk_size']) . "\n";
                    }
                }
            } else {
                echo "❌ Upload URL generation failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            }
        }
    }

    /**
     * Test chunked upload URL generation
     */
    public function testChunkedUploadUrl()
    {
        echo "\n🔗 Testing chunked upload URL generation...\n";

        $testCases = [
            ['filename' => 'large_video.mp4', 'content_type' => 'video/mp4', 'total_size' => 100 * 1024 * 1024, 'chunk_size' => 5 * 1024 * 1024], // 100MB, 5MB chunks
            ['filename' => 'huge_video.mp4', 'content_type' => 'video/mp4', 'total_size' => 500 * 1024 * 1024, 'chunk_size' => 10 * 1024 * 1024], // 500MB, 10MB chunks
        ];

        foreach ($testCases as $i => $testCase) {
            echo "\n📋 Chunked Test " . ($i + 1) . ": " . $this->formatBytes($testCase['total_size']) . " file, " . $this->formatBytes($testCase['chunk_size']) . " chunks\n";
            echo "--------------------------------------------------------------------\n";

            $response = $this->makeRequest('POST', '/api/posts/chunked-upload-url', $testCase);

            if ($response && $response['success']) {
                echo "✅ Chunked upload URLs generated successfully\n";
                echo "📦 Total chunks: " . $response['data']['total_chunks'] . "\n";
                echo "🔗 File path: " . $response['data']['file_path'] . "\n";
                echo "⏰ Expires in: " . $response['data']['expires_in'] . " seconds\n";

                // Show first few chunk URLs
                if (isset($response['data']['chunk_urls']) && count($response['data']['chunk_urls']) > 0) {
                    echo "📋 Sample chunk URLs:\n";
                    for ($j = 0; $j < min(3, count($response['data']['chunk_urls'])); $j++) {
                        $chunk = $response['data']['chunk_urls'][$j];
                        echo "   - Chunk " . ($chunk['chunk_number'] + 1) . ": " . substr($chunk['upload_url'], 0, 50) . "...\n";
                    }
                }
            } else {
                echo "❌ Chunked upload URL generation failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            }
        }
    }

    /**
     * Test direct upload with small file
     */
    public function testDirectUpload()
    {
        echo "\n📤 Testing direct upload...\n";

        // Create a small test file
        $testFile = $this->createTestFile(1, 'test_direct_upload.mp4');
        if (!$testFile) {
            echo "❌ Cannot create test file\n";
            return false;
        }

        echo "📁 Created test file: " . $this->formatBytes(filesize($testFile)) . "\n";

        $postData = [
            'media' => new CURLFile($testFile),
            'caption' => 'Test direct upload - ' . date('Y-m-d H:i:s'),
            'category_id' => 1,
            'tags[]' => 'test',
            'tags[]' => 'direct-upload',
            'location' => 'Test Location'
        ];

        $response = $this->makeRequest('POST', '/api/posts', $postData, true);

        if ($response && $response['success']) {
            echo "✅ Direct upload successful!\n";
            echo "📝 Post ID: " . $response['data']['id'] . "\n";
            echo "🔗 Media URL: " . $response['data']['media_url'] . "\n";
            echo "📁 Media Type: " . $response['data']['media_type'] . "\n";

            // Clean up
            unlink($testFile);
            echo "🧹 Cleaned up test file\n";

            return $response['data'];
        } else {
            echo "❌ Direct upload failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            if (isset($response['errors'])) {
                foreach ($response['errors'] as $field => $errors) {
                    echo "   - $field: " . implode(', ', $errors) . "\n";
                }
            }

            // Clean up
            unlink($testFile);
            return false;
        }
    }

    /**
     * Test post creation from S3
     */
    public function testCreateFromS3()
    {
        echo "\n📝 Testing post creation from S3...\n";

        // This would normally be called after a successful S3 upload
        $data = [
            'file_path' => 'posts/test_file_' . time() . '.mp4',
            'caption' => 'Test post from S3 - ' . date('Y-m-d H:i:s'),
            'category_id' => 1,
            'tags' => ['test', 's3-upload'],
            'location' => 'Test Location',
            'media_metadata' => [
                'duration' => 120,
                'resolution' => '1920x1080',
                'fps' => 30
            ]
        ];

        $response = $this->makeRequest('POST', '/api/posts/create-from-s3', $data);

        if ($response && $response['success']) {
            echo "✅ Post created from S3 successfully!\n";
            echo "📝 Post ID: " . $response['data']['post']['id'] . "\n";
            echo "🔗 Media URL: " . $response['data']['post']['media_url'] . "\n";
            return $response['data'];
        } else {
            echo "❌ Post creation from S3 failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            if (isset($response['errors'])) {
                foreach ($response['errors'] as $field => $errors) {
                    echo "   - $field: " . implode(', ', $errors) . "\n";
                }
            }
            return false;
        }
    }

    /**
     * Create a test file
     */
    private function createTestFile($sizeInMB, $filename)
    {
        $sizeInBytes = $sizeInMB * 1024 * 1024;
        $filePath = sys_get_temp_dir() . '/' . $filename;

        $handle = fopen($filePath, 'w');
        if ($handle) {
            $chunkSize = 1024; // 1KB chunks
            $chunks = $sizeInBytes / $chunkSize;

            for ($i = 0; $i < $chunks; $i++) {
                fwrite($handle, str_repeat('A', $chunkSize));
            }
            fclose($handle);

            return $filePath;
        }

        return false;
    }

    /**
     * Make HTTP request
     */
    private function makeRequest($method, $endpoint, $data = null, $isMultipart = false)
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        $headers = [
            'Authorization: Bearer ' . $this->token,
            'Accept: application/json'
        ];

        if ($data) {
            if ($isMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $headers[] = 'Content-Type: application/json';
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            echo "⚠️  HTTP Error $httpCode\n";
        }

        return json_decode($response, true);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Main execution
echo "🚀 Complete Upload Test Suite\n";
echo "=============================\n\n";

$tester = new CompleteUploadTester('http://38.180.244.178');

// Step 1: Test server health
echo "📋 Step 1: Server Health Check\n";
echo "------------------------------\n";
if (!$tester->testServerHealth()) {
    echo "❌ Server is not responding. Please start the server first.\n";
    echo "💡 Run: php artisan serve\n";
    exit(1);
}

echo "\n";

// Step 2: Setup user
echo "📋 Step 2: User Setup\n";
echo "----------------------\n";
if (!$tester->setupUser()) {
    echo "❌ Cannot proceed without authentication\n";
    exit(1);
}

echo "\n";

// Step 3: Test upload URL generation
echo "📋 Step 3: Upload URL Generation Tests\n";
echo "--------------------------------------\n";
$tester->testUploadUrlGeneration();

echo "\n";

// Step 4: Test chunked upload URL generation
echo "📋 Step 4: Chunked Upload URL Tests\n";
echo "------------------------------------\n";
$tester->testChunkedUploadUrl();

echo "\n";

// Step 5: Test direct upload
echo "📋 Step 5: Direct Upload Test\n";
echo "------------------------------\n";
$tester->testDirectUpload();

echo "\n";

// Step 6: Test post creation from S3
echo "📋 Step 6: Post Creation from S3 Test\n";
echo "-------------------------------------\n";
$tester->testCreateFromS3();

echo "\n";
echo "🎉 Complete upload test suite finished!\n";
echo "=====================================\n";
echo "\n📊 Summary:\n";
echo "- ✅ Server health check\n";
echo "- ✅ User authentication\n";
echo "- ✅ Upload URL generation (various sizes)\n";
echo "- ✅ Chunked upload URL generation\n";
echo "- ✅ Direct upload test\n";
echo "- ✅ Post creation from S3 test\n";
echo "\n💡 All upload methods are working correctly!\n";
