<?php
/**
 * Test Script for Direct Upload (≤ 50MB)
 * 
 * This script tests the traditional Laravel file upload method
 * Usage: php test_upload_direct.php
 */

require_once 'vendor/autoload.php';

class UploadTester
{
    private $baseUrl;
    private $token;
    
    public function __construct($baseUrl = 'http://localhost:8000')
    {
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Test user login to get authentication token
     */
    public function login($email = 'test@example.com', $password = 'password')
    {
        echo "🔐 Logging in...\n";
        
        $data = [
            'email' => $email,
            'password' => $password
        ];
        
        $response = $this->makeRequest('POST', '/api/login', $data);
        
        if ($response && isset($response['data']['token'])) {
            $this->token = $response['data']['token'];
            echo "✅ Login successful! Token: " . substr($this->token, 0, 20) . "...\n";
            return true;
        } else {
            echo "❌ Login failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            return false;
        }
    }
    
    /**
     * Test direct upload with a small file
     */
    public function testDirectUpload($filePath)
    {
        if (!file_exists($filePath)) {
            echo "❌ Test file not found: $filePath\n";
            return false;
        }
        
        echo "📤 Testing direct upload with: $filePath\n";
        echo "📊 File size: " . $this->formatBytes(filesize($filePath)) . "\n";
        
        $postData = [
            'media' => new CURLFile($filePath),
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
            return $response['data'];
        } else {
            echo "❌ Direct upload failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            if (isset($response['errors'])) {
                foreach ($response['errors'] as $field => $errors) {
                    echo "   - $field: " . implode(', ', $errors) . "\n";
                }
            }
            return false;
        }
    }
    
    /**
     * Test upload URL generation for small file
     */
    public function testUploadUrl($filename, $contentType, $fileSize)
    {
        echo "🔗 Testing upload URL generation...\n";
        echo "📁 Filename: $filename\n";
        echo "📊 File size: " . $this->formatBytes($fileSize) . "\n";
        
        $data = [
            'filename' => $filename,
            'content_type' => $contentType,
            'file_size' => $fileSize
        ];
        
        $response = $this->makeRequest('POST', '/api/posts/upload-url', $data);
        
        if ($response && $response['success']) {
            echo "✅ Upload URL generated successfully!\n";
            echo "📋 Upload method: " . $response['data']['upload_method'] . "\n";
            echo "🔗 File path: " . $response['data']['file_path'] . "\n";
            echo "⏰ Expires in: " . $response['data']['expires_in'] . " seconds\n";
            
            if ($response['data']['threshold_exceeded']) {
                echo "⚠️  Large file detected - chunked upload recommended\n";
                echo "🔧 Recommended chunk size: " . $this->formatBytes($response['data']['recommended_chunk_size']) . "\n";
            }
            
            return $response['data'];
        } else {
            echo "❌ Upload URL generation failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            return false;
        }
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Accept: application/json'
        ]);
        
        if ($data) {
            if ($isMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
                    'Content-Type: application/json'
                ], curl_getinfo($ch, CURLINFO_HTTPHEADER)));
            }
        }
        
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
    
    /**
     * Create a test file
     */
    public function createTestFile($sizeInMB = 1, $filename = 'test_video.mp4')
    {
        $sizeInBytes = $sizeInMB * 1024 * 1024;
        $filePath = sys_get_temp_dir() . '/' . $filename;
        
        echo "📁 Creating test file: $filePath (" . $this->formatBytes($sizeInBytes) . ")\n";
        
        // Create a simple test file with random data
        $handle = fopen($filePath, 'w');
        if ($handle) {
            $chunkSize = 1024; // 1KB chunks
            $chunks = $sizeInBytes / $chunkSize;
            
            for ($i = 0; $i < $chunks; $i++) {
                fwrite($handle, str_repeat('A', $chunkSize));
            }
            fclose($handle);
            
            echo "✅ Test file created successfully\n";
            return $filePath;
        } else {
            echo "❌ Failed to create test file\n";
            return false;
        }
    }
}

// Main execution
echo "🚀 Starting Direct Upload Tests\n";
echo "==============================\n\n";

$tester = new UploadTester();

// Step 1: Login
if (!$tester->login()) {
    echo "❌ Cannot proceed without authentication\n";
    exit(1);
}

echo "\n";

// Step 2: Test upload URL generation for small file
echo "📋 Test 1: Upload URL for small file (10MB)\n";
echo "--------------------------------------------\n";
$tester->testUploadUrl('small_video.mp4', 'video/mp4', 10 * 1024 * 1024);

echo "\n";

// Step 3: Test upload URL generation for large file
echo "📋 Test 2: Upload URL for large file (100MB)\n";
echo "--------------------------------------------\n";
$tester->testUploadUrl('large_video.mp4', 'video/mp4', 100 * 1024 * 1024);

echo "\n";

// Step 4: Test direct upload with small file
echo "📋 Test 3: Direct upload with small file (1MB)\n";
echo "-----------------------------------------------\n";
$testFile = $tester->createTestFile(1, 'test_direct_upload.mp4');
if ($testFile) {
    $result = $tester->testDirectUpload($testFile);
    if ($result) {
        echo "✅ Direct upload test completed successfully!\n";
    }
    // Clean up
    unlink($testFile);
    echo "🧹 Cleaned up test file\n";
}

echo "\n";
echo "🎉 Direct upload tests completed!\n";
echo "==============================\n";
