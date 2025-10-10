<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\Log;

class S3Service
{
    /**
     * Get configured S3 client
     */
    private static function getS3Client(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => config('filesystems.disks.s3.region'),
            'credentials' => [
                'key' => config('filesystems.disks.s3.key'),
                'secret' => config('filesystems.disks.s3.secret'),
            ],
            'use_path_style_endpoint' => config('filesystems.disks.s3.use_path_style_endpoint', false),
        ]);
    }

    /**
     * Upload a file to S3
     */
    public static function uploadFile(UploadedFile $file, string $folder = 'uploads'): array
    {
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 's3');
        $url = Storage::disk('s3')->url($path);

        return [
            'path' => $path,
            'url' => $url,
            'filename' => $filename,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ];
    }

    /**
     * Upload multiple files to S3
     */
    public static function uploadFiles(array $files, string $folder = 'uploads'): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = self::uploadFile($file, $folder);
            }
        }

        return $uploadedFiles;
    }

    /**
     * Delete a file from S3
     */
    public static function deleteFile(string $path): bool
    {
        try {
            return Storage::disk('s3')->delete($path);
        } catch (\Exception $e) {
            Log::error("Failed to delete file from S3: {$path}. Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete multiple files from S3
     */
    public static function deleteFiles(array $paths): array
    {
        $results = [];

        foreach ($paths as $path) {
            $results[$path] = self::deleteFile($path);
        }

        return $results;
    }

    /**
     * Get file URL from S3
     */
    public static function getFileUrl(string $path): string
    {
        return Storage::disk('s3')->url($path);
    }

    /**
     * Get file URL from S3 (alias for getFileUrl)
     */
    public static function getUrl(string $path): string
    {
        return Storage::disk('s3')->url($path);
    }

    /**
     * Check if file exists in S3
     */
    public static function fileExists(string $path): bool
    {
        return Storage::disk('s3')->exists($path);
    }

    /**
     * Check if file exists in S3 (alias for fileExists)
     */
    public static function exists(string $path): bool
    {
        return Storage::disk('s3')->exists($path);
    }

    /**
     * Get file size from S3
     */
    public static function getFileSize(string $path): int
    {
        try {
            return Storage::disk('s3')->size($path);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Generate a presigned URL for temporary access
     */
    public static function getPresignedUrl(string $path, int $expiration = 3600): string
    {
        try {
            return Storage::disk('s3')->temporaryUrl($path, now()->addSeconds($expiration));
        } catch (\Exception $e) {
            Log::error("Failed to generate presigned URL for: {$path}. Error: " . $e->getMessage());
            return self::getFileUrl($path);
        }
    }

    /**
     * Generate a temporary URL for S3 operations (FIXED VERSION)
     * Always generates direct S3 URLs (bypasses CloudFront) for uploads
     */
    public static function getTemporaryUrl(string $path, $expiration, string $method = 'GET', $contentType = null): string
    {
        try {
            if ($method === 'PUT') {
                $s3Client = self::getS3Client();

                $commandParams = [
                    'Bucket' => config('filesystems.disks.s3.bucket'),
                    'Key' => $path,
                ];

                // CRITICAL: Add ContentType to the command params
                if ($contentType) {
                    $commandParams['ContentType'] = $contentType;
                }

                $command = $s3Client->getCommand('PutObject', $commandParams);

                $request = $s3Client->createPresignedRequest($command, $expiration);

                // Get the direct S3 URL (not CloudFront)
                $directUrl = (string) $request->getUri();

                Log::info('Generated presigned URL', [
                    'path' => $path,
                    'content_type' => $contentType,
                    'url_host' => parse_url($directUrl, PHP_URL_HOST),
                    'method' => $method,
                ]);

                return $directUrl;
            }

            // For GET requests, use Laravel's method
            return Storage::disk('s3')->temporaryUrl($path, $expiration);
        } catch (AwsException $e) {
            Log::error("AWS Error generating temporary URL for: {$path}. Error: " . $e->getAwsErrorMessage());
            return self::getFileUrl($path);
        } catch (\Exception $e) {
            Log::error("Failed to generate temporary URL for: {$path}. Error: " . $e->getMessage());
            return self::getFileUrl($path);
        }
    }

    /**
     * Upload with CDN optimization
     */
    public static function uploadWithCDN(UploadedFile $file, string $folder = 'uploads'): array
    {
        $uploadResult = self::uploadFile($file, $folder);

        // Add CDN URL if configured
        if (config('filesystems.disks.s3.cdn_url')) {
            $uploadResult['cdn_url'] = str_replace(
                config('filesystems.disks.s3.url'),
                config('filesystems.disks.s3.cdn_url'),
                $uploadResult['url']
            );
        }

        return $uploadResult;
    }

    /**
     * Generate thumbnail for images
     */
    public static function generateThumbnail(string $imagePath, int $width = 300, int $height = 300): ?string
    {
        try {
            // This would require image processing library like Intervention Image
            // For now, return the original URL
            return self::getFileUrl($imagePath);
        } catch (\Exception $e) {
            Log::error("Failed to generate thumbnail for: {$imagePath}. Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get storage statistics
     */
    public static function getStorageStats(): array
    {
        try {
            $disk = Storage::disk('s3');

            return [
                'total_files' => count($disk->allFiles()),
                'total_size' => self::calculateTotalSize($disk->allFiles()),
                'bucket_name' => config('filesystems.disks.s3.bucket'),
                'region' => config('filesystems.disks.s3.region'),
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get storage stats. Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calculate total size of files
     */
    private static function calculateTotalSize(array $files): int
    {
        $totalSize = 0;

        foreach ($files as $file) {
            try {
                $totalSize += Storage::disk('s3')->size($file);
            } catch (\Exception $e) {
                // Skip files that can't be accessed
                continue;
            }
        }

        return $totalSize;
    }

    /**
     * Clean up old files
     */
    public static function cleanupOldFiles(string $folder, int $daysOld = 30): int
    {
        $deletedCount = 0;
        $cutoffDate = now()->subDays($daysOld);

        try {
            $files = Storage::disk('s3')->allFiles($folder);

            foreach ($files as $file) {
                $lastModified = Storage::disk('s3')->lastModified($file);

                if ($lastModified < $cutoffDate->timestamp) {
                    if (self::deleteFile($file)) {
                        $deletedCount++;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to cleanup old files. Error: " . $e->getMessage());
        }

        return $deletedCount;
    }
}
