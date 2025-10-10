<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\S3Service;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    /**
     * Debug S3 and CloudFront configuration
     */
    public function checkS3Config(Request $request)
    {
        $s3Config = config('filesystems.disks.s3');

        // Check if CloudFront is being used
        $isUsingCloudFront = !empty($s3Config['url']) &&
                            (str_contains($s3Config['url'], 'cloudfront.net') ||
                             !empty($s3Config['cdn_url']));

        return response()->json([
            'using_cloudfront' => $isUsingCloudFront,
            's3_url' => $s3Config['url'] ?? 'Not set',
            'cdn_url' => $s3Config['cdn_url'] ?? 'Not set',
            'region' => $s3Config['region'] ?? 'Not set',
            'bucket' => $s3Config['bucket'] ?? 'Not set',
            'endpoint' => $s3Config['endpoint'] ?? 'Default',
            'recommendation' => $isUsingCloudFront
                ? 'CloudFront detected! Make sure to use Managed-CORS-S3Origin policy'
                : 'Direct S3 access - no CloudFront configuration needed',
        ]);
    }

    /**
     * Test presigned URL generation
     */
    public function testPresignedUrl(Request $request)
    {
        try {
            $testPath = 'test/' . time() . '_test.jpg';
            $contentType = 'image/jpeg';

            // Generate presigned URL
            $presignedUrl = S3Service::getTemporaryUrl(
                $testPath,
                now()->addMinutes(15),
                'PUT',
                $contentType
            );

            // Parse URL to check configuration
            $parsedUrl = parse_url($presignedUrl);
            parse_str($parsedUrl['query'] ?? '', $queryParams);

            return response()->json([
                'success' => true,
                'presigned_url' => $presignedUrl,
                'url_analysis' => [
                    'host' => $parsedUrl['host'] ?? null,
                    'is_cloudfront' => str_contains($parsedUrl['host'] ?? '', 'cloudfront.net'),
                    'is_s3_direct' => str_contains($parsedUrl['host'] ?? '', 's3.amazonaws.com'),
                    'has_signature' => isset($queryParams['X-Amz-Signature']),
                    'algorithm' => $queryParams['X-Amz-Algorithm'] ?? null,
                    'expires' => $queryParams['X-Amz-Expires'] ?? null,
                ],
                'content_type' => $contentType,
                'test_path' => $testPath,
                'instructions' => 'Use this presigned URL to upload a test file with exact Content-Type: ' . $contentType,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Debug AWS configuration and credentials
     */
    public function debugAwsConfig(Request $request)
    {
        try {
            $s3Config = config('filesystems.disks.s3');
            
            return response()->json([
                'success' => true,
                'config' => [
                    'region' => $s3Config['region'] ?? 'Not set',
                    'bucket' => $s3Config['bucket'] ?? 'Not set',
                    'key' => $s3Config['key'] ? 'SET' : 'NOT SET',
                    'secret' => $s3Config['secret'] ? 'SET' : 'NOT SET',
                    'endpoint' => $s3Config['endpoint'] ?? 'Default',
                ],
                'env_vars' => [
                    'AWS_ACCESS_KEY_ID' => env('AWS_ACCESS_KEY_ID') ? 'SET' : 'NOT SET',
                    'AWS_SECRET_ACCESS_KEY' => env('AWS_SECRET_ACCESS_KEY') ? 'SET' : 'NOT SET',
                    'AWS_DEFAULT_REGION' => env('AWS_DEFAULT_REGION') ?: 'NOT SET',
                    'AWS_BUCKET' => env('AWS_BUCKET') ?: 'NOT SET',
                ],
                'recommendations' => [
                    'Check if AWS credentials are properly loaded',
                    'Verify region configuration matches S3 bucket region',
                    'Ensure secret key doesn\'t contain special characters that need escaping',
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
