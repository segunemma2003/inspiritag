<?php
// debug_upload.php - Debug the upload endpoint

echo "🔍 Debugging Upload Endpoint\n";
echo "============================\n\n";

$baseUrl = 'http://38.180.244.178';

// Step 1: Login
echo "1. Logging in...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/api/login',
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'email' => 'test@example.com',
        'password' => 'password123'
    ]),
    CURLOPT_RETURNTRANSFER => true
]);
$loginResponse = curl_exec($ch);
curl_close($ch);

$loginData = json_decode($loginResponse, true);
echo "Login response: " . json_encode($loginData) . "\n";

if (!$loginData || !isset($loginData['data']['token'])) {
    echo "❌ Login failed\n";
    exit(1);
}

$token = $loginData['data']['token'];
echo "✅ Login successful, token: " . substr($token, 0, 20) . "...\n\n";

// Step 2: Test upload endpoint
echo "2. Testing upload endpoint...\n";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/api/posts/upload-url',
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'filename' => 'test.jpg',
        'content_type' => 'image/jpeg',
        'file_size' => 1024
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_VERBOSE => true
]);

$uploadResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . $uploadResponse . "\n";
if ($error) {
    echo "cURL Error: $error\n";
}

echo "\n3. Testing with different approach...\n";

// Test with file_get_contents
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ],
        'content' => json_encode([
            'filename' => 'test.jpg',
            'content_type' => 'image/jpeg',
            'file_size' => 1024
        ])
    ]
]);

$response = @file_get_contents($baseUrl . '/api/posts/upload-url', false, $context);
if ($response === false) {
    $error = error_get_last();
    echo "file_get_contents failed: " . $error['message'] . "\n";
} else {
    echo "file_get_contents response: " . $response . "\n";
}
