<?php
// Comprehensive AWS diagnosis test

require_once 'vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

echo "🔍 Comprehensive AWS Diagnosis Test\n";
echo "==================================\n\n";

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "📋 Environment Check:\n";
echo "AWS_ACCESS_KEY_ID: " . (env('AWS_ACCESS_KEY_ID') ? 'SET' : 'NOT SET') . "\n";
echo "AWS_SECRET_ACCESS_KEY: " . (env('AWS_SECRET_ACCESS_KEY') ? 'SET' : 'NOT SET') . "\n";
echo "AWS_DEFAULT_REGION: " . (env('AWS_DEFAULT_REGION') ?: 'NOT SET') . "\n";
echo "AWS_BUCKET: " . (env('AWS_BUCKET') ?: 'NOT SET') . "\n\n";

// Test 1: Direct AWS SDK with minimal configuration
echo "🧪 Test 1: Direct AWS SDK (Minimal Config)\n";
echo "==========================================\n";

try {
    $s3Client = new S3Client([
        'version' => 'latest',
        'region' => 'eu-north-1',
        'credentials' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);
    
    $bucket = env('AWS_BUCKET', 'inspirtag');
    
    // Test bucket access
    $result = $s3Client->headBucket(['Bucket' => $bucket]);
    echo "✅ Bucket access successful\n";
    
    // Test presigned URL generation
    $testKey = 'test/' . time() . '_diagnosis.jpg';
    $command = $s3Client->getCommand('PutObject', [
        'Bucket' => $bucket,
        'Key' => $testKey,
        'ContentType' => 'image/jpeg',
    ]);
    
    $request = $s3Client->createPresignedRequest($command, '+15 minutes');
    $presignedUrl = (string) $request->getUri();
    
    echo "✅ Presigned URL generated: " . substr($presignedUrl, 0, 100) . "...\n";
    
    // Test upload with exact Content-Type
    echo "\n📤 Testing upload with exact Content-Type...\n";
    $testContent = "Diagnosis test content";
    $tempFile = tempnam(sys_get_temp_dir(), 'diagnosis_test_');
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
        CURLOPT_HTTPHEADER => [
            'Content-Type: image/jpeg', // Exact Content-Type
        ],
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
        echo "✅ Direct AWS SDK upload successful!\n";
        echo "\n🎉 SUCCESS: The issue is NOT with AWS credentials or IAM policy!\n";
        echo "The problem is with the Laravel/Flysystem configuration.\n";
    } else {
        echo "❌ Direct AWS SDK upload failed!\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
        
        if (strpos($response, 'SignatureDoesNotMatch') !== false) {
            echo "\n🔍 Signature Mismatch Analysis:\n";
            echo "This suggests a fundamental AWS configuration issue:\n";
            echo "1. Check if the secret key contains special characters\n";
            echo "2. Verify the access key is correct\n";
            echo "3. Check if the region matches the bucket region\n";
            echo "4. Verify time synchronization between server and AWS\n";
        }
    }
    
} catch (AwsException $e) {
    echo "❌ AWS Error: " . $e->getAwsErrorMessage() . "\n";
    echo "Error Code: " . $e->getAwsErrorCode() . "\n";
} catch (\Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
}

// Test 2: Check time synchronization
echo "\n🕐 Test 2: Time Synchronization Check\n";
echo "=====================================\n";
$serverTime = time();
$awsTime = time(); // AWS uses UTC
echo "Server time: " . date('Y-m-d H:i:s T', $serverTime) . "\n";
echo "AWS expects UTC time\n";
echo "Time difference: " . ($serverTime - $awsTime) . " seconds\n";

if (abs($serverTime - $awsTime) > 300) { // 5 minutes
    echo "⚠️  WARNING: Time difference is significant. This can cause signature issues.\n";
} else {
    echo "✅ Time synchronization looks good\n";
}

// Test 3: Check Laravel configuration
echo "\n🔧 Test 3: Laravel Configuration Check\n";
echo "======================================\n";

try {
    // Test if we can access Laravel config
    $config = include 'config/filesystems.php';
    $s3Config = $config['disks']['s3'];
    
    echo "Laravel S3 Config:\n";
    echo "Region: " . ($s3Config['region'] ?? 'Not set') . "\n";
    echo "Bucket: " . ($s3Config['bucket'] ?? 'Not set') . "\n";
    echo "Key: " . ($s3Config['key'] ? 'SET' : 'NOT SET') . "\n";
    echo "Secret: " . ($s3Config['secret'] ? 'SET' : 'NOT SET') . "\n";
    
} catch (\Exception $e) {
    echo "❌ Cannot access Laravel config: " . $e->getMessage() . "\n";
}

echo "\n📊 Diagnosis Summary:\n";
echo "====================\n";
echo "If the direct AWS SDK test works, the issue is with Laravel/Flysystem configuration.\n";
echo "If the direct AWS SDK test fails, the issue is with AWS credentials or IAM policy.\n";
echo "\nNext steps will depend on the results above.\n";
