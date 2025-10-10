<?php

namespace App\Services;

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PresignedUrlService
{
    private static $s3Client = null;

    /**
     * Get S3 client instance
     */
    private static function getS3Client(): S3Client
    {
        if (self::$s3Client === null) {
            self::$s3Client = new S3Client([
                'version' => 'latest',
                'region' => config('filesystems.disks.s3.region', 'eu-north-1'),
                'credentials' => [
                    'key' => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
                'endpoint' => 'https://s3.eu-north-1.amazonaws.com',
                'use_path_style_endpoint' => false,
            ]);
        }

        return self::$s3Client;
    }

    /**
     * Generate presigned URL for PUT request (upload)
     */
    public static function generateUploadUrl(
        string $key,
        string $contentType,
        int $expirationMinutes = 15
    ): array {
        try {
            $s3Client = self::getS3Client();
            $bucket = config('filesystems.disks.s3.bucket');

            // Create the command with all necessary parameters
            $command = $s3Client->getCommand('PutObject', [
                'Bucket' => $bucket,
                'Key' => $key,
                'ContentType' => $contentType,
                'ACL' => 'private', // Ensure private access
            ]);

            // Create presigned request
            $request = $s3Client->createPresignedRequest(
                $command,
                "+{$expirationMinutes} minutes"
            );

            $presignedUrl = (string) $request->getUri();

            // Log for debugging
            Log::info('Generated presigned URL', [
                'key' => $key,
                'content_type' => $contentType,
                'expiration_minutes' => $expirationMinutes,
                'url_host' => parse_url($presignedUrl, PHP_URL_HOST),
            ]);

            return [
                'success' => true,
                'presigned_url' => $presignedUrl,
                'expires_in' => $expirationMinutes * 60,
                'content_type' => $contentType,
                'key' => $key,
            ];

        } catch (AwsException $e) {
            Log::error('AWS Error generating presigned URL', [
                'key' => $key,
                'error' => $e->getAwsErrorMessage(),
                'code' => $e->getAwsErrorCode(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate presigned URL: ' . $e->getAwsErrorMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('General error generating presigned URL', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate presigned URL: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate presigned URL for GET request (download)
     */
    public static function generateDownloadUrl(
        string $key,
        int $expirationMinutes = 60
    ): array {
        try {
            $s3Client = self::getS3Client();
            $bucket = config('filesystems.disks.s3.bucket');

            $command = $s3Client->getCommand('GetObject', [
                'Bucket' => $bucket,
                'Key' => $key,
            ]);

            $request = $s3Client->createPresignedRequest(
                $command,
                "+{$expirationMinutes} minutes"
            );

            $presignedUrl = (string) $request->getUri();

            return [
                'success' => true,
                'presigned_url' => $presignedUrl,
                'expires_in' => $expirationMinutes * 60,
                'key' => $key,
            ];

        } catch (AwsException $e) {
            Log::error('AWS Error generating download URL', [
                'key' => $key,
                'error' => $e->getAwsErrorMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate download URL: ' . $e->getAwsErrorMessage(),
            ];
        }
    }

    /**
     * Generate multiple presigned URLs for chunked upload
     */
    public static function generateChunkedUploadUrls(
        string $key,
        string $contentType,
        int $chunkSize,
        int $totalSize,
        int $expirationMinutes = 15
    ): array {
        try {
            $chunkCount = ceil($totalSize / $chunkSize);
            $urls = [];

            for ($i = 0; $i < $chunkCount; $i++) {
                $chunkKey = "{$key}.part{$i}";
                $result = self::generateUploadUrl($chunkKey, $contentType, $expirationMinutes);

                if (!$result['success']) {
                    return $result;
                }

                $urls[] = [
                    'chunk_number' => $i + 1,
                    'chunk_key' => $chunkKey,
                    'presigned_url' => $result['presigned_url'],
                    'expires_in' => $result['expires_in'],
                ];
            }

            return [
                'success' => true,
                'chunks' => $urls,
                'total_chunks' => $chunkCount,
                'chunk_size' => $chunkSize,
                'total_size' => $totalSize,
            ];

        } catch (\Exception $e) {
            Log::error('Error generating chunked upload URLs', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to generate chunked upload URLs: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Test presigned URL generation
     */
    public static function testConfiguration(): array
    {
        try {
            $s3Client = self::getS3Client();
            $bucket = config('filesystems.disks.s3.bucket');

            // Test bucket access
            $s3Client->headBucket(['Bucket' => $bucket]);

            // Test presigned URL generation
            $testKey = 'test/' . time() . '_test.jpg';
            $result = self::generateUploadUrl($testKey, 'image/jpeg', 5);

            return [
                'success' => true,
                'bucket_access' => true,
                'presigned_url_generation' => $result['success'],
                'test_result' => $result,
            ];

        } catch (AwsException $e) {
            return [
                'success' => false,
                'error' => 'AWS Error: ' . $e->getAwsErrorMessage(),
                'code' => $e->getAwsErrorCode(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'General Error: ' . $e->getMessage(),
            ];
        }
    }
}
