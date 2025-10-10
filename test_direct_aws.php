<?php
// Test direct AWS SDK presigned URL generation
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

try {
    echo "🔧 Direct AWS SDK Test\n";
    echo "====================\n\n";

    // Create S3 client with explicit configuration
    $s3Client = new S3Client([
        'version' => 'latest',
        'region' => 'eu-north-1',
        'credentials' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
        'use_path_style_endpoint' => false,
    ]);

    echo "AWS Access Key ID: " . substr(env('AWS_ACCESS_KEY_ID'), 0, 8) . "...\n";
    echo "Region: eu-north-1\n";
    echo "Bucket: inspirtag\n\n";

    // Test parameters
    $bucket = 'inspirtag';
    $key = 'test/direct-aws-test.jpg';
    $contentType = 'image/jpeg';
    $expiration = '+1 hour';

    // Generate presigned URL using AWS SDK
    echo "Generating presigned URL...\n";
    $command = $s3Client->getCommand('PutObject', [
        'Bucket' => $bucket,
        'Key' => $key,
        'ContentType' => $contentType,
    ]);

    $request = $s3Client->createPresignedRequest($command, $expiration);
    $presignedUrl = (string) $request->getUri();

    echo "✅ Presigned URL generated:\n";
    echo substr($presignedUrl, 0, 150) . "...\n\n";

    // Check signed headers
    $signedHeaders = $request->getHeader('x-amz-signedheaders');
    if (!empty($signedHeaders)) {
        echo "Signed Headers: " . implode(', ', $signedHeaders) . "\n";
    } else {
        echo "Signed Headers: None found\n";
    }

    // Test upload with minimal approach
    echo "\nTesting upload...\n";
    $testContent = "Test content for direct AWS upload";
    $tempFile = tempnam(sys_get_temp_dir(), 'direct_aws_test_');
    file_put_contents($tempFile, $testContent);

    // Use curl with minimal options
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
        // Don't set any headers - let the presigned URL handle everything
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
        
        // Verify the file exists
        echo "Verifying file exists...\n";
        try {
            $result = $s3Client->headObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);
            echo "✅ File verified in S3!\n";
            echo "Content-Type: " . $result['ContentType'] . "\n";
            echo "Content-Length: " . $result['ContentLength'] . "\n";
        } catch (Exception $e) {
            echo "❌ File verification failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Upload failed!\n";
        echo "Response: " . substr($response, 0, 500) . "\n";
    }

} catch (AwsException $e) {
    echo "❌ AWS Error: " . $e->getAwsErrorMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
