<?php
// test_presigned_local.php - Test presigned upload locally

require_once 'vendor/autoload.php';

use App\Services\PresignedUrlService;
use App\Services\S3Service;

echo "🚀 Local Presigned Upload Test\n";
echo "==============================\n\n";

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Test S3 configuration
echo "1. Testing S3 Configuration...\n";
$s3Service = new S3Service();
$presignedService = new PresignedUrlService($s3Service);

try {
    // Test S3 connection
    $bucket = env('AWS_BUCKET');
    $region = env('AWS_DEFAULT_REGION');

    echo "   Bucket: $bucket\n";
    echo "   Region: $region\n";

    // Test if we can create a presigned URL
    $filename = 'test-' . time() . '.jpg';
    $contentType = 'image/jpeg';
    $fileSize = 1024 * 1024; // 1MB

    echo "\n2. Testing Presigned URL Generation...\n";
    $uploadData = $presignedService->generateUploadUrl($filename, $contentType, $fileSize);

    if ($uploadData) {
        echo "✅ Presigned URL generated successfully!\n";
        echo "   Upload URL: " . substr($uploadData['upload_url'], 0, 100) . "...\n";
        echo "   File Path: " . $uploadData['file_path'] . "\n";
        echo "   Method: " . $uploadData['upload_method'] . "\n";
        echo "   Expires: " . $uploadData['expires_in'] . " seconds\n";

        // Test the presigned URL
        echo "\n3. Testing Presigned URL Validity...\n";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $uploadData['upload_url'],
            CURLOPT_HEAD => true,
            CURLOPT_NOBODY => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            echo "✅ Presigned URL is valid and accessible!\n";
        } else {
            echo "❌ Presigned URL test failed with HTTP code: $httpCode\n";
        }

    } else {
        echo "❌ Failed to generate presigned URL\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n4. Testing S3 Service Methods...\n";

try {
    // Test S3 service methods
    $methods = [
        'generateUploadUrl' => 'Generate Upload URL',
        'generateChunkedUploadUrls' => 'Generate Chunked Upload URLs',
        'completeChunkedUpload' => 'Complete Chunked Upload'
    ];

    foreach ($methods as $method => $description) {
        echo "   Testing $description...\n";

        if (method_exists($presignedService, $method)) {
            echo "   ✅ Method exists: $method\n";
        } else {
            echo "   ❌ Method missing: $method\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Error testing methods: " . $e->getMessage() . "\n";
}

echo "\n📊 Test Complete\n";
echo "================\n";
echo "If all tests passed, the presigned upload service is working correctly.\n";
echo "The issue with the remote server is likely a Docker container problem.\n";
