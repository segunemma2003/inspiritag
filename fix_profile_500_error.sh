#!/bin/bash
# Fix profile picture 500 error on server
# Run on server: bash fix_profile_500_error.sh

echo "🔧 Fixing Profile Picture 500 Error"
echo "===================================="
echo ""

cd /var/www/inspirtag

# Backup the original file
echo "1️⃣ Backing up UserController..."
docker-compose exec app cp app/Http/Controllers/Api/UserController.php app/Http/Controllers/Api/UserController.php.backup
echo "✅ Backup created: UserController.php.backup"
echo ""

# Create the fixed version
echo "2️⃣ Creating fixed version with error handling..."

docker-compose exec app bash << 'BASH_EOF'
cat > /tmp/update_profile_fix.php << 'PHP_EOF'
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'bio' => 'nullable|string|max:500',
            'profession' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['full_name', 'username', 'bio', 'profession', 'interests']);

        if ($request->hasFile('profile_picture')) {
            try {
                // Delete old profile picture (with error handling)
                if ($user->profile_picture) {
                    try {
                        $s3Url = config('filesystems.disks.s3.url');
                        $cdnUrl = config('filesystems.disks.s3.cdn_url');

                        $oldPath = $user->profile_picture;

                        if ($s3Url) {
                            $oldPath = str_replace($s3Url, '', $oldPath);
                        }
                        if ($cdnUrl) {
                            $oldPath = str_replace($cdnUrl, '', $oldPath);
                        }

                        $oldPath = ltrim($oldPath, '/');

                        if ($oldPath) {
                            S3Service::deleteFile($oldPath);
                        }
                    } catch (\Exception $e) {
                        \Log::warning("Failed to delete old profile picture: " . $e->getMessage());
                    }
                }

                // Store new profile picture using S3Service
                $file = $request->file('profile_picture');
                $uploadResult = S3Service::uploadWithCDN($file, 'profiles');
                $data['profile_picture'] = $uploadResult['url'];

            } catch (\Exception $e) {
                \Log::error("Profile picture upload failed: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload profile picture',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }
PHP_EOF

echo "Fixed version created in /tmp/update_profile_fix.php"
BASH_EOF

echo "✅ Fix file created"
echo ""

echo "3️⃣ Instructions to apply the fix:"
echo "=================================="
echo ""
echo "The issue is in the updateProfile method around line 47-91"
echo ""
echo "You need to manually replace the updateProfile method in:"
echo "  app/Http/Controllers/Api/UserController.php"
echo ""
echo "The main changes:"
echo "  1. Added try-catch around file upload"
echo "  2. Better handling of old file deletion"
echo "  3. Proper error messages returned"
echo "  4. Won't crash if old file deletion fails"
echo ""
echo "After editing, run:"
echo "  docker-compose restart app"
echo "  docker-compose exec app php artisan config:clear"
echo ""
echo "OR you can apply via git if this code is in your repo"
echo ""

# Check what config values are set
echo "4️⃣ Checking S3 configuration..."
echo "=================================="
docker-compose exec app php artisan tinker << 'TINKER_EOF'
echo "S3 URL: " . config('filesystems.disks.s3.url') . "\n";
echo "CDN URL: " . config('filesystems.disks.s3.cdn_url') . "\n";
echo "Bucket: " . config('filesystems.disks.s3.bucket') . "\n";
exit
TINKER_EOF
echo ""

echo "5️⃣ Common Issues:"
echo "================="
echo ""
echo "If config values above show 'null' or empty:"
echo "  1. Check your .env file has these values:"
echo "     AWS_URL=https://your-bucket.s3.region.amazonaws.com"
echo "     AWS_CDN_URL=https://your-cdn-url.com (optional)"
echo ""
echo "  2. Clear config cache:"
echo "     docker-compose exec app php artisan config:clear"
echo ""
echo "  3. Restart:"
echo "     docker-compose restart app"
echo ""

echo "===================================="
echo "✅ Analysis complete!"
echo ""
echo "The fix adds proper error handling so you'll see"
echo "actual error messages instead of just 500."
echo ""

