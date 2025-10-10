<?php
// test_server_status.php

echo "🔍 Server Status Check\n";
echo "=====================\n\n";

$baseUrl = 'http://38.180.244.178';

// Test 1: Health endpoint
echo "1. Testing health endpoint...\n";
$healthResponse = file_get_contents($baseUrl . '/health');
if ($healthResponse === 'healthy') {
    echo "✅ Health endpoint: OK\n";
} else {
    echo "❌ Health endpoint: FAILED\n";
    echo "Response: " . $healthResponse . "\n";
}

// Test 2: API endpoint
echo "\n2. Testing API endpoint...\n";
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
        echo "✅ API endpoint: OK\n";
        echo "Categories found: " . count($data['data']) . "\n";
    } else {
        echo "❌ API endpoint: Invalid response\n";
        echo "Response: " . substr($apiResponse, 0, 200) . "\n";
    }
} else {
    echo "❌ API endpoint: Connection failed\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
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
    } else {
        echo "❌ Login endpoint: Invalid response\n";
        echo "Response: " . substr($loginResponse, 0, 200) . "\n";
    }
} else {
    echo "❌ Login endpoint: Connection failed\n";
}

echo "\n📊 Summary\n";
echo "==========\n";
echo "Server appears to be running but API endpoints are not accessible.\n";
echo "This suggests the Docker containers may not be running properly.\n";
echo "Please check the server deployment status.\n";
