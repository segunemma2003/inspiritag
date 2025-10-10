<?php
// Test the new bulletproof PresignedUrlService

class BulletproofPresignedTest {
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
    
    public function testBulletproofPresigned() {
        if (!$this->token) {
            echo "❌ Please login first\n";
            return false;
        }
        
        echo "🚀 Testing Bulletproof Presigned URL Service\n";
        echo "==========================================\n\n";
        
        // Step 1: Get presigned URL from API
        echo "Step 1: Getting presigned URL from API...\n";
        $uploadResponse = $this->makeRequest('POST', '/api/posts/upload-url', [
            'filename' => 'bulletproof_test.jpg',
            'content_type' => 'image/jpeg',
            'file_size' => 1024
        ]);
        
        if (!$uploadResponse['success']) {
            echo "❌ Failed to get upload URL: " . $uploadResponse['message'] . "\n";
            return false;
        }
        
        $presignedUrl = $uploadResponse['data']['upload_url'];
        echo "✅ Got presigned URL: " . substr($presignedUrl, 0, 100) . "...\n";
        echo "File path: " . $uploadResponse['data']['file_path'] . "\n";
        echo "Expires in: " . $uploadResponse['data']['expires_in'] . " seconds\n";
        
        // Step 2: Test upload with minimal content
        echo "\nStep 2: Testing upload with minimal content...\n";
        $testContent = "Bulletproof test content";
        $tempFile = tempnam(sys_get_temp_dir(), 'bulletproof_test_');
        file_put_contents($tempFile, $testContent);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $presignedUrl,
            CURLOPT_PUT => true,
            CURLOPT_INFILE => fopen($tempFile, 'r'),
            CURLOPT_INFILESIZE => filesize($tempFile),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        unlink($tempFile);
        
        echo "HTTP Code: $httpCode\n";
        if ($error) {
            echo "cURL Error: $error\n";
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            echo "✅ Upload successful!\n";
            echo "\n🎉 SUCCESS: Bulletproof PresignedUrlService works perfectly!\n";
            return true;
        } else {
            echo "❌ Upload failed!\n";
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
}

// Usage
$baseUrl = 'http://38.180.244.178';
$test = new BulletproofPresignedTest($baseUrl);

echo "🚀 Bulletproof Presigned URL Test\n";
echo "================================\n\n";

// Login
echo "🔐 Logging in...\n";
if (!$test->login('testuser1760092397@example.com', 'password123')) {
    echo "❌ Cannot proceed without login\n";
    exit(1);
}

// Test bulletproof presigned URL
echo "\n";
$success = $test->testBulletproofPresigned();

if ($success) {
    echo "\n🎉 BULLETPROOF SUCCESS! The new PresignedUrlService works flawlessly!\n";
    echo "Your presigned URL uploads are now bulletproof! 🚀\n";
} else {
    echo "\n❌ The bulletproof service still has issues.\n";
    echo "This suggests a deeper configuration problem.\n";
}
