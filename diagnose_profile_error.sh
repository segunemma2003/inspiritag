#!/bin/bash
# Diagnose profile update 500 error
# Run this on server: bash diagnose_profile_error.sh

echo "🔍 Diagnosing Profile Update Error"
echo "===================================="
echo ""

cd /var/www/inspirtag

# Check Laravel logs
echo "📋 RECENT ERROR LOGS:"
echo "-------------------"
docker-compose exec app tail -100 /var/www/html/storage/logs/laravel.log | grep -A 10 "ERROR\|CRITICAL\|profile\|S3\|AWS" | tail -50
echo ""

# Check AWS configuration
echo "🔧 AWS CONFIGURATION:"
echo "-------------------"
docker-compose exec app bash -c 'echo "AWS_ACCESS_KEY_ID: $(echo $AWS_ACCESS_KEY_ID | cut -c1-8)..."'
docker-compose exec app bash -c 'echo "AWS_SECRET: $(echo $AWS_SECRET_ACCESS_KEY | cut -c1-8)..."'
docker-compose exec app bash -c 'echo "AWS_REGION: $AWS_DEFAULT_REGION"'
docker-compose exec app bash -c 'echo "AWS_BUCKET: $AWS_BUCKET"'
echo ""

# Test S3 connection
echo "🧪 TESTING S3 CONNECTION:"
echo "------------------------"
docker-compose exec app php artisan tinker << 'TINKER_EOF'
try {
    \Illuminate\Support\Facades\Storage::disk('s3')->put('test-connection.txt', 'Test from server ' . date('Y-m-d H:i:s'));
    echo "✅ S3 Write: SUCCESS\n";

    if (\Illuminate\Support\Facades\Storage::disk('s3')->exists('test-connection.txt')) {
        echo "✅ S3 Read: SUCCESS\n";
        \Illuminate\Support\Facades\Storage::disk('s3')->delete('test-connection.txt');
        echo "✅ S3 Delete: SUCCESS\n";
    }
} catch (\Exception $e) {
    echo "❌ S3 Error: " . $e->getMessage() . "\n";
}
exit
TINKER_EOF
echo ""

# Check S3Service class
echo "🔍 CHECKING S3SERVICE:"
echo "---------------------"
docker-compose exec app php artisan tinker << 'TINKER_EOF'
try {
    $service = new \App\Services\S3Service();
    echo "✅ S3Service class exists\n";
} catch (\Exception $e) {
    echo "❌ S3Service Error: " . $e->getMessage() . "\n";
}
exit
TINKER_EOF
echo ""

# Check filesystems config
echo "⚙️  FILESYSTEMS CONFIG:"
echo "----------------------"
docker-compose exec app php artisan tinker << 'TINKER_EOF'
$s3Config = config('filesystems.disks.s3');
echo "Driver: " . ($s3Config['driver'] ?? 'not set') . "\n";
echo "Key: " . (isset($s3Config['key']) && $s3Config['key'] ? substr($s3Config['key'], 0, 8) . '...' : 'NOT SET') . "\n";
echo "Secret: " . (isset($s3Config['secret']) && $s3Config['secret'] ? substr($s3Config['secret'], 0, 8) . '...' : 'NOT SET') . "\n";
echo "Region: " . ($s3Config['region'] ?? 'NOT SET') . "\n";
echo "Bucket: " . ($s3Config['bucket'] ?? 'NOT SET') . "\n";
exit
TINKER_EOF
echo ""

# Check if AWS SDK is installed
echo "📦 CHECKING AWS SDK:"
echo "-------------------"
if docker-compose exec app php -r "class_exists('Aws\S3\S3Client') ? exit(0) : exit(1);" 2>/dev/null; then
    echo "✅ AWS SDK installed"
else
    echo "❌ AWS SDK NOT installed"
    echo "Fix: docker-compose exec app composer require aws/aws-sdk-php"
fi
echo ""

# Test actual profile update endpoint
echo "🧪 TESTING PROFILE UPDATE (text only):"
echo "--------------------------------------"
echo "Note: This will fail due to authentication, but we can see if S3 is the issue"
RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" -X PUT http://localhost/api/users/profile \
  -H "Content-Type: application/json" \
  -d '{"full_name":"Test User"}' 2>&1)

HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
echo "Response Code: $HTTP_CODE"
if [ "$HTTP_CODE" != "200" ] && [ "$HTTP_CODE" != "401" ] && [ "$HTTP_CODE" != "422" ]; then
    echo "❌ Still returning error"
fi
echo ""

echo "===================================="
echo "📊 SUMMARY:"
echo "===================================="
echo ""
echo "If you see S3 errors above, the issue is with AWS credentials or permissions."
echo ""
echo "Common fixes:"
echo "1. Wrong credentials - Update .env file"
echo "2. Wrong bucket name - Check AWS_BUCKET in .env"
echo "3. Wrong region - Check AWS_DEFAULT_REGION in .env"
echo "4. Bucket permissions - Ensure bucket allows PutObject, GetObject, DeleteObject"
echo "5. AWS SDK not installed - Run: composer require aws/aws-sdk-php"
echo ""
echo "To fix, edit .env file:"
echo "  nano .env"
echo ""
echo "Then restart:"
echo "  docker-compose restart app"
echo ""

