<?php
/**
 * Test Script for Chunked Upload (> 50MB)
 * 
 * This script tests the presigned URL and chunked upload method
 * Usage: php test_upload_chunked.php
 */

require_once 'vendor/autoload.php';

class ChunkedUploadTester
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
     * Test chunked upload URL generation
     */
    public function testChunkedUploadUrl($filename, $contentType, $totalSize, $chunkSize)
    {
        echo "🔗 Testing chunked upload URL generation...\n";
        echo "📁 Filename: $filename\n";
        echo "📊 Total size: " . $this->formatBytes($totalSize) . "\n";
        echo "📦 Chunk size: " . $this->formatBytes($chunkSize) . "\n";
        
        $data = [
            'filename' => $filename,
            'content_type' => $contentType,
            'total_size' => $totalSize,
            'chunk_size' => $chunkSize
        ];
        
        $response = $this->makeRequest('POST', '/api/posts/chunked-upload-url', $data);
        
        if ($response && $response['success']) {
            echo "✅ Chunked upload URLs generated successfully!\n";
            echo "📋 File path: " . $response['data']['file_path'] . "\n";
            echo "📦 Total chunks: " . $response['data']['total_chunks'] . "\n";
            echo "⏰ Expires in: " . $response['data']['expires_in'] . " seconds\n";
            
            return $response['data'];
        } else {
            echo "❌ Chunked upload URL generation failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            return false;
        }
    }
    
    /**
     * Simulate chunked upload to S3
     */
    public function simulateChunkedUpload($chunkUrls, $testData)
    {
        echo "📤 Simulating chunked upload...\n";
        
        $successfulChunks = 0;
        $totalChunks = count($chunkUrls);
        
        foreach ($chunkUrls as $chunk) {
            $chunkNumber = $chunk['chunk_number'];
            $uploadUrl = $chunk['upload_url'];
            
            echo "📦 Uploading chunk " . ($chunkNumber + 1) . "/$totalChunks...\n";
            
            // Simulate chunk data (in real implementation, this would be actual file data)
            $chunkData = str_repeat('X', 1024 * 1024); // 1MB of test data
            
            if ($this->uploadChunk($uploadUrl, $chunkData)) {
                $successfulChunks++;
                echo "✅ Chunk " . ($chunkNumber + 1) . " uploaded successfully\n";
            } else {
                echo "❌ Chunk " . ($chunkNumber + 1) . " upload failed\n";
            }
        }
        
        echo "📊 Upload summary: $successfulChunks/$totalChunks chunks successful\n";
        return $successfulChunks === $totalChunks;
    }
    
    /**
     * Upload a single chunk to S3
     */
    private function uploadChunk($uploadUrl, $chunkData)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $uploadUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $chunkData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: video/mp4'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    /**
     * Complete chunked upload
     */
    public function completeChunkedUpload($filePath, $totalChunks)
    {
        echo "🔚 Completing chunked upload...\n";
        echo "📁 File path: $filePath\n";
        echo "📦 Total chunks: $totalChunks\n";
        
        $data = [
            'file_path' => $filePath,
            'total_chunks' => $totalChunks
        ];
        
        $response = $this->makeRequest('POST', '/api/posts/complete-chunked-upload', $data);
        
        if ($response && $response['success']) {
            echo "✅ Chunked upload completed successfully!\n";
            echo "🔗 File URL: " . $response['data']['file_url'] . "\n";
            return $response['data'];
        } else {
            echo "❌ Chunked upload completion failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            return false;
        }
    }
    
    /**
     * Create post from S3 file
     */
    public function createPostFromS3($filePath, $caption = null)
    {
        echo "📝 Creating post from S3 file...\n";
        
        $data = [
            'file_path' => $filePath,
            'caption' => $caption ?: 'Test chunked upload - ' . date('Y-m-d H:i:s'),
            'category_id' => 1,
            'tags' => ['test', 'chunked-upload'],
            'location' => 'Test Location',
            'media_metadata' => [
                'duration' => 120,
                'resolution' => '1920x1080',
                'fps' => 30
            ]
        ];
        
        $response = $this->makeRequest('POST', '/api/posts/create-from-s3', $data);
        
        if ($response && $response['success']) {
            echo "✅ Post created successfully!\n";
            echo "📝 Post ID: " . $response['data']['post']['id'] . "\n";
            echo "🔗 Media URL: " . $response['data']['post']['media_url'] . "\n";
            echo "📁 Media Type: " . $response['data']['post']['media_type'] . "\n";
            return $response['data'];
        } else {
            echo "❌ Post creation failed: " . ($response['message'] ?? 'Unknown error') . "\n";
            if (isset($response['errors'])) {
                foreach ($response['errors'] as $field => $errors) {
                    echo "   - $field: " . implode(', ', $errors) . "\n";
                }
            }
            return false;
        }
    }
    
    /**
     * Test full chunked upload workflow
     */
    public function testFullChunkedWorkflow($filename = 'large_test_video.mp4', $totalSizeMB = 100, $chunkSizeMB = 5)
    {
        echo "🚀 Testing full chunked upload workflow\n";
        echo "=====================================\n\n";
        
        $totalSize = $totalSizeMB * 1024 * 1024;
        $chunkSize = $chunkSizeMB * 1024 * 1024;
        
        // Step 1: Get chunked upload URLs
        echo "📋 Step 1: Get chunked upload URLs\n";
        echo "----------------------------------\n";
        $chunkData = $this->testChunkedUploadUrl($filename, 'video/mp4', $totalSize, $chunkSize);
        
        if (!$chunkData) {
            echo "❌ Cannot proceed without chunk URLs\n";
            return false;
        }
        
        echo "\n";
        
        // Step 2: Simulate chunked upload
        echo "📋 Step 2: Simulate chunked upload\n";
        echo "---------------------------------\n";
        $uploadSuccess = $this->simulateChunkedUpload($chunkData['chunk_urls'], $chunkData);
        
        if (!$uploadSuccess) {
            echo "❌ Chunked upload simulation failed\n";
            return false;
        }
        
        echo "\n";
        
        // Step 3: Complete chunked upload
        echo "📋 Step 3: Complete chunked upload\n";
        echo "----------------------------------\n";
        $completeResult = $this->completeChunkedUpload($chunkData['file_path'], $chunkData['total_chunks']);
        
        if (!$completeResult) {
            echo "❌ Chunked upload completion failed\n";
            return false;
        }
        
        echo "\n";
        
        // Step 4: Create post from S3
        echo "📋 Step 4: Create post from S3\n";
        echo "------------------------------\n";
        $postResult = $this->createPostFromS3($chunkData['file_path']);
        
        if (!$postResult) {
            echo "❌ Post creation failed\n";
            return false;
        }
        
        echo "\n";
        echo "🎉 Full chunked upload workflow completed successfully!\n";
        return true;
    }
    
    /**
     * Make HTTP request
     */
    private function makeRequest($method, $endpoint, $data = null)
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Accept: application/json',
            'Content-Type: application/json'
        ]);
        
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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
}

// Main execution
echo "🚀 Starting Chunked Upload Tests\n";
echo "================================\n\n";

$tester = new ChunkedUploadTester();

// Step 1: Login
if (!$tester->login()) {
    echo "❌ Cannot proceed without authentication\n";
    exit(1);
}

echo "\n";

// Step 2: Test chunked upload workflow
echo "📋 Test: Full chunked upload workflow (100MB file, 5MB chunks)\n";
echo "-------------------------------------------------------------\n";
$tester->testFullChunkedWorkflow('large_test_video.mp4', 100, 5);

echo "\n";
echo "🎉 Chunked upload tests completed!\n";
echo "================================\n";
