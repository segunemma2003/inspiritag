<?php
// test_server_complete.php - Comprehensive server test

echo "🚀 Complete Server Test Suite\n";
echo "============================\n\n";

$baseUrl = 'http://38.180.244.178';
$testResults = [];

// Test 1: Health endpoint
echo "1. Testing health endpoint...\n";
$healthResponse = @file_get_contents($baseUrl . '/health');
if ($healthResponse === 'healthy') {
    echo "✅ Health endpoint: OK\n";
    $testResults['health'] = true;
} else {
    echo "❌ Health endpoint: FAILED\n";
    echo "Response: " . $healthResponse . "\n";
    $testResults['health'] = false;
}

// Test 2: API categories endpoint
echo "\n2. Testing API categories endpoint...\n";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Accept: application/json',
        'timeout' => 10
    ]
]);

$apiResponse = @file_get_contents($baseUrl . '/api/categories', false, $context);
if ($apiResponse !== false) {
    $data = json_decode($apiResponse, true);
    if ($data && isset($data['data'])) {
        echo "✅ API categories: OK\n";
        echo "Categories found: " . count($data['data']) . "\n";
        $testResults['api_categories'] = true;
    } else {
        echo "❌ API categories: Invalid response\n";
        echo "Response: " . substr($apiResponse, 0, 200) . "\n";
        $testResults['api_categories'] = false;
    }
} else {
    echo "❌ API categories: Connection failed\n";
    $testResults['api_categories'] = false;
}

// Test 3: Login endpoint
echo "\n3. Testing login endpoint...\n";
$loginData = json_encode([
    'email' => 'test@example.com',
    'password' => 'password'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'content' => $loginData,
        'timeout' => 10
    ]
]);

$loginResponse = @file_get_contents($baseUrl . '/api/login', false, $context);
if ($loginResponse !== false) {
    $data = json_decode($loginResponse, true);
    if ($data && isset($data['success'])) {
        echo "✅ Login endpoint: OK\n";
        echo "Response: " . ($data['success'] ? 'Success' : 'Failed') . "\n";
        $testResults['login'] = true;
    } else {
        echo "❌ Login endpoint: Invalid response\n";
        echo "Response: " . substr($loginResponse, 0, 200) . "\n";
        $testResults['login'] = false;
    }
} else {
    echo "❌ Login endpoint: Connection failed\n";
    $testResults['login'] = false;
}

// Test 4: Presigned upload endpoint (without authentication)
echo "\n4. Testing presigned upload endpoint...\n";
$uploadData = json_encode([
    'filename' => 'test.jpg',
    'content_type' => 'image/jpeg',
    'file_size' => 1024
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'content' => $uploadData,
        'timeout' => 10
    ]
]);

$uploadResponse = @file_get_contents($baseUrl . '/api/posts/upload-url', false, $context);
if ($uploadResponse !== false) {
    $data = json_decode($uploadResponse, true);
    if ($data && isset($data['success'])) {
        if ($data['success']) {
            echo "✅ Presigned upload endpoint: OK\n";
            $testResults['presigned_upload'] = true;
        } else {
            echo "⚠️ Presigned upload endpoint: Requires authentication\n";
            echo "Response: " . ($data['message'] ?? 'Unknown error') . "\n";
            $testResults['presigned_upload'] = 'auth_required';
        }
    } else {
        echo "❌ Presigned upload endpoint: Invalid response\n";
        echo "Response: " . substr($uploadResponse, 0, 200) . "\n";
        $testResults['presigned_upload'] = false;
    }
} else {
    echo "❌ Presigned upload endpoint: Connection failed\n";
    $testResults['presigned_upload'] = false;
}

// Summary
echo "\n📊 Test Results Summary\n";
echo "=======================\n";
echo "Health Endpoint: " . ($testResults['health'] ? "✅ PASS" : "❌ FAIL") . "\n";
echo "API Categories: " . ($testResults['api_categories'] ? "✅ PASS" : "❌ FAIL") . "\n";
echo "Login Endpoint: " . ($testResults['login'] ? "✅ PASS" : "❌ FAIL") . "\n";
echo "Presigned Upload: " . ($testResults['presigned_upload'] === true ? "✅ PASS" : 
    ($testResults['presigned_upload'] === 'auth_required' ? "⚠️ AUTH REQUIRED" : "❌ FAIL")) . "\n";

$totalTests = 4;
$passedTests = 0;
foreach ($testResults as $result) {
    if ($result === true || $result === 'auth_required') {
        $passedTests++;
    }
}

echo "\nOverall: $passedTests/$totalTests tests passed\n";

if ($testResults['health'] && $testResults['api_categories']) {
    echo "\n🎉 Server is working! You can now test presigned uploads.\n";
    echo "Run: php test_presigned_upload.php\n";
} else {
    echo "\n⚠️ Server has issues. Check Docker containers:\n";
    echo "1. SSH into your server\n";
    echo "2. Run: cd /var/www/inspirtag\n";
    echo "3. Run: ./fix_server_docker.sh\n";
    echo "4. Check: docker-compose ps\n";
}
