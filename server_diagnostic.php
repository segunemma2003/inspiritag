<?php
// Docker Server-side diagnostic for presigned URL issues
// Run this inside your Docker container to identify the exact problem

echo "🐳 Docker Server-Side Presigned URL Diagnostic\n";
echo "==============================================\n\n";

// Check Docker environment
echo "🐳 Docker Environment Check:\n";
echo "============================\n";
echo "Container ID: " . (getenv('HOSTNAME') ?: 'Unknown') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Working Directory: " . getcwd() . "\n";
echo "User: " . (getenv('USER') ?: 'Unknown') . "\n\n";

// Check 1: Environment Variables (Docker-specific)
echo "📋 Environment Variables Check (Docker):\n";
echo "========================================\n";
echo "AWS_ACCESS_KEY_ID: " . (env('AWS_ACCESS_KEY_ID') ? 'SET' : 'NOT SET') . "\n";
echo "AWS_SECRET_ACCESS_KEY: " . (env('AWS_SECRET_ACCESS_KEY') ? 'SET' : 'NOT SET') . "\n";
echo "AWS_DEFAULT_REGION: " . (env('AWS_DEFAULT_REGION') ?: 'NOT SET') . "\n";
echo "AWS_BUCKET: " . (env('AWS_BUCKET') ?: 'NOT SET') . "\n\n";

// Check Docker environment variables directly
echo "🐳 Docker Environment Variables:\n";
echo "===================================\n";
echo "getenv AWS_ACCESS_KEY_ID: " . (getenv('AWS_ACCESS_KEY_ID') ? 'SET' : 'NOT SET') . "\n";
echo "getenv AWS_SECRET_ACCESS_KEY: " . (getenv('AWS_SECRET_ACCESS_KEY') ? 'SET' : 'NOT SET') . "\n";
echo "getenv AWS_DEFAULT_REGION: " . (getenv('AWS_DEFAULT_REGION') ?: 'NOT SET') . "\n";
echo "getenv AWS_BUCKET: " . (getenv('AWS_BUCKET') ?: 'NOT SET') . "\n\n";

// Check if .env file exists and is readable
echo "📁 .env File Check:\n";
echo "==================\n";
$envFile = '.env';
if (file_exists($envFile)) {
    echo "✅ .env file exists\n";
    echo "Readable: " . (is_readable($envFile) ? 'YES' : 'NO') . "\n";
    echo "Size: " . filesize($envFile) . " bytes\n";
    
    // Check for AWS variables in .env file
    $envContent = file_get_contents($envFile);
    echo "Contains AWS_ACCESS_KEY_ID: " . (strpos($envContent, 'AWS_ACCESS_KEY_ID') !== false ? 'YES' : 'NO') . "\n";
    echo "Contains AWS_SECRET_ACCESS_KEY: " . (strpos($envContent, 'AWS_SECRET_ACCESS_KEY') !== false ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ .env file not found\n";
}
echo "\n";

// Check 2: Laravel Config
echo "🔧 Laravel Configuration Check:\n";
echo "================================\n";
$s3Config = config('filesystems.disks.s3');
echo "Config Region: " . ($s3Config['region'] ?? 'NOT SET') . "\n";
echo "Config Bucket: " . ($s3Config['bucket'] ?? 'NOT SET') . "\n";
echo "Config Key: " . ($s3Config['key'] ? 'SET' : 'NOT SET') . "\n";
echo "Config Secret: " . ($s3Config['secret'] ? 'SET' : 'NOT SET') . "\n\n";

// Check 3: Direct AWS SDK Test
echo "🧪 Direct AWS SDK Test:\n";
echo "=======================\n";

try {
    $s3Client = new \Aws\S3\S3Client([
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
    $testKey = 'test/' . time() . '_server_test.jpg';
    $command = $s3Client->getCommand('PutObject', [
        'Bucket' => $bucket,
        'Key' => $testKey,
        'ContentType' => 'image/jpeg',
    ]);

    $request = $s3Client->createPresignedRequest($command, '+15 minutes');
    $presignedUrl = (string) $request->getUri();

    echo "✅ Presigned URL generated: " . substr($presignedUrl, 0, 100) . "...\n";

    // Test upload
    $testContent = "Server test content";
    $tempFile = tempnam(sys_get_temp_dir(), 'server_test_');
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
            'Content-Type: image/jpeg',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    unlink($tempFile);

    echo "Direct AWS SDK HTTP Code: $httpCode\n";

    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ Direct AWS SDK upload successful!\n";
    } else {
        echo "❌ Direct AWS SDK upload failed!\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Direct AWS SDK error: " . $e->getMessage() . "\n";
}

// Check 4: Laravel S3Service Test
echo "\n🔧 Laravel S3Service Test:\n";
echo "===========================\n";

try {
    $testPath = 'test/' . time() . '_laravel_test.jpg';
    $presignedUrl = \App\Services\S3Service::getTemporaryUrl(
        $testPath,
        now()->addMinutes(15),
        'PUT',
        'image/jpeg'
    );

    echo "✅ Laravel S3Service URL: " . substr($presignedUrl, 0, 100) . "...\n";

    // Test upload with Laravel-generated URL
    $testContent = "Laravel test content";
    $tempFile = tempnam(sys_get_temp_dir(), 'laravel_test_');
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
            'Content-Type: image/jpeg',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    unlink($tempFile);

    echo "Laravel S3Service HTTP Code: $httpCode\n";

    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ Laravel S3Service upload successful!\n";
    } else {
        echo "❌ Laravel S3Service upload failed!\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Laravel S3Service error: " . $e->getMessage() . "\n";
}

// Check 5: Credential Comparison
echo "\n🔍 Credential Comparison:\n";
echo "==========================\n";
echo "env() AWS_ACCESS_KEY_ID: " . substr(env('AWS_ACCESS_KEY_ID', ''), 0, 8) . "...\n";
echo "config() AWS_ACCESS_KEY_ID: " . substr(config('filesystems.disks.s3.key', ''), 0, 8) . "...\n";
echo "Match: " . (env('AWS_ACCESS_KEY_ID') === config('filesystems.disks.s3.key') ? 'YES' : 'NO') . "\n";

echo "\n📊 Docker Diagnostic Summary:\n";
echo "=============================\n";
echo "This diagnostic will help identify:\n";
echo "1. Docker environment variable issues\n";
echo "2. Laravel configuration problems\n";
echo "3. AWS credential mismatches\n";
echo "4. Direct vs Laravel SDK differences\n\n";

echo "🐳 Docker-Specific Solutions to Try:\n";
echo "=====================================\n";
echo "1. Check Docker environment variables:\n";
echo "   docker exec -it <container_name> env | grep AWS\n\n";
echo "2. Restart Docker container:\n";
echo "   docker-compose restart\n\n";
echo "3. Clear Laravel caches inside container:\n";
echo "   docker exec -it <container_name> php artisan config:clear\n";
echo "   docker exec -it <container_name> php artisan cache:clear\n";
echo "   docker exec -it <container_name> php artisan config:cache\n\n";
echo "4. Check Docker Compose environment:\n";
echo "   docker-compose config\n\n";
echo "5. Rebuild container with fresh environment:\n";
echo "   docker-compose down && docker-compose up --build\n\n";
echo "Run this inside your Docker container and share the results.\n";
