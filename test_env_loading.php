<?php
// Simple test to verify .env loading in Docker

echo "🔍 Docker .env Loading Test\n";
echo "==========================\n\n";

// Method 1: Check if .env file exists
echo "📁 .env File Check:\n";
echo "===================\n";
if (file_exists('.env')) {
    echo "✅ .env file exists\n";
    echo "Size: " . filesize('.env') . " bytes\n";
    echo "Readable: " . (is_readable('.env') ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ .env file not found\n";
}
echo "\n";

// Method 2: Check environment variables directly
echo "🌍 Environment Variables:\n";
echo "==========================\n";
echo "getenv('AWS_ACCESS_KEY_ID'): " . (getenv('AWS_ACCESS_KEY_ID') ? 'SET' : 'NOT SET') . "\n";
echo "getenv('AWS_SECRET_ACCESS_KEY'): " . (getenv('AWS_SECRET_ACCESS_KEY') ? 'SET' : 'NOT SET') . "\n";
echo "getenv('AWS_DEFAULT_REGION'): " . (getenv('AWS_DEFAULT_REGION') ?: 'NOT SET') . "\n";
echo "getenv('AWS_BUCKET'): " . (getenv('AWS_BUCKET') ?: 'NOT SET') . "\n\n";

// Method 3: Check Laravel env() function
echo "🔧 Laravel env() Function:\n";
echo "==========================\n";
echo "env('AWS_ACCESS_KEY_ID'): " . (env('AWS_ACCESS_KEY_ID') ? 'SET' : 'NOT SET') . "\n";
echo "env('AWS_SECRET_ACCESS_KEY'): " . (env('AWS_SECRET_ACCESS_KEY') ? 'SET' : 'NOT SET') . "\n";
echo "env('AWS_DEFAULT_REGION'): " . (env('AWS_DEFAULT_REGION') ?: 'NOT SET') . "\n";
echo "env('AWS_BUCKET'): " . (env('AWS_BUCKET') ?: 'NOT SET') . "\n\n";

// Method 4: Check Laravel config
echo "⚙️ Laravel Config:\n";
echo "==================\n";
echo "config('filesystems.disks.s3.key'): " . (config('filesystems.disks.s3.key') ? 'SET' : 'NOT SET') . "\n";
echo "config('filesystems.disks.s3.secret'): " . (config('filesystems.disks.s3.secret') ? 'SET' : 'NOT SET') . "\n";
echo "config('filesystems.disks.s3.region'): " . (config('filesystems.disks.s3.region') ?: 'NOT SET') . "\n";
echo "config('filesystems.disks.s3.bucket'): " . (config('filesystems.disks.s3.bucket') ?: 'NOT SET') . "\n\n";

// Method 5: Show first few characters (for debugging)
echo "🔍 Credential Preview (First 8 chars):\n";
echo "=======================================\n";
echo "AWS_ACCESS_KEY_ID: " . substr(env('AWS_ACCESS_KEY_ID', ''), 0, 8) . "...\n";
echo "AWS_SECRET_ACCESS_KEY: " . substr(env('AWS_SECRET_ACCESS_KEY', ''), 0, 8) . "...\n";
echo "AWS_DEFAULT_REGION: " . env('AWS_DEFAULT_REGION', 'NOT SET') . "\n";
echo "AWS_BUCKET: " . env('AWS_BUCKET', 'NOT SET') . "\n\n";

// Method 6: Check if values match between methods
echo "🔄 Comparison:\n";
echo "==============\n";
$envAccessKey = env('AWS_ACCESS_KEY_ID');
$configAccessKey = config('filesystems.disks.s3.key');
echo "env() vs config() match: " . ($envAccessKey === $configAccessKey ? 'YES' : 'NO') . "\n";

if ($envAccessKey && $configAccessKey) {
    echo "Both methods have values: YES\n";
} else {
    echo "Both methods have values: NO\n";
    echo "env() has value: " . ($envAccessKey ? 'YES' : 'NO') . "\n";
    echo "config() has value: " . ($configAccessKey ? 'YES' : 'NO') . "\n";
}

echo "\n📊 Summary:\n";
echo "===========\n";
echo "If all methods show 'SET', your .env is loading correctly.\n";
echo "If some show 'NOT SET', there's a Docker environment issue.\n";
echo "If env() and config() don't match, there's a Laravel config issue.\n";
