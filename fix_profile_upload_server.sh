#!/bin/bash
# Fix profile picture upload on server
# Run this on your server: bash fix_profile_upload_server.sh

echo "🔧 Fixing Profile Picture Upload - 500 Error"
echo "=============================================="
echo ""

cd /var/www/inspirtag

# Step 1: Check logs
echo "1️⃣ Checking current error logs..."
echo "-----------------------------------"
docker-compose exec app tail -30 /var/www/html/storage/logs/laravel.log | tail -20
echo ""

# Step 2: Check if AWS is configured
echo "2️⃣ Checking AWS configuration..."
echo "-----------------------------------"
if docker-compose exec app env | grep -q "AWS_ACCESS_KEY_ID=.*[a-zA-Z0-9]"; then
    echo "✅ AWS credentials found"
    AWS_CONFIGURED=true
else
    echo "❌ AWS credentials NOT configured"
    AWS_CONFIGURED=false
fi
echo ""

# Step 3: Offer solution
if [ "$AWS_CONFIGURED" = false ]; then
    echo "3️⃣ Solution: Switch to local storage"
    echo "-----------------------------------"
    echo "Backing up UserController..."
    
    docker-compose exec app cp /var/www/html/app/Http/Controllers/Api/UserController.php /var/www/html/app/Http/Controllers/Api/UserController.php.backup
    
    echo "Creating fixed version..."
    
    # Create the fixed version
    docker-compose exec app bash -c 'cat > /tmp/fix_profile.php << '\''PHPEOF'\''
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Follow;
use App\Models\Notification;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            '\''full_name'\'' => '\''nullable|string|max:255'\'',
            '\''username'\'' => '\''nullable|string|max:255|unique:users,username,'\'' . $user->id,
            '\''bio'\'' => '\''nullable|string|max:500'\'',
            '\''profession'\'' => '\''nullable|string|max:255'\'',
            '\''profile_picture'\'' => '\''nullable|image|mimes:jpeg,png,jpg,gif|max:2048'\'',
            '\''interests'\'' => '\''nullable|array'\'',
            '\''interests.*'\'' => '\''string|max:50'\'',
        ]);

        if ($validator->fails()) {
            return response()->json([
                '\''success'\'' => false,
                '\''message'\'' => '\''Validation errors'\'',
                '\''errors'\'' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['\''full_name'\'', '\''username'\'', '\''bio'\'', '\''profession'\'', '\''interests'\'']);

        if ($request->hasFile('\''profile_picture'\'')) {
            // Delete old profile picture
            if ($user->profile_picture) {
                $oldPath = str_replace(url('\''storage/'\''), '\'\'\'', $user->profile_picture);
                Storage::delete('\''public/'\'' . $oldPath);
            }

            // Store new profile picture in local storage
            $file = $request->file('\''profile_picture'\'');
            $filename = time() . '\''_'\'' . Str::random(10) . '\''.'\'' . $file->getClientOriginalExtension();
            $path = $file->storeAs('\''profiles'\'', $filename, '\''public'\'');
            $data['\''profile_picture'\''] = url('\''storage/'\'' . $path);
        }

        $user->update($data);

        return response()->json([
            '\''success'\'' => true,
            '\''message'\'' => '\''Profile updated successfully'\'',
            '\''data'\'' => $user
        ]);
    }

    // ... rest of the methods remain the same
}
PHPEOF'
    
    echo "⚠️  Manual fix required!"
    echo ""
    echo "The UserController.php file needs to be edited manually."
    echo "Backup created at: app/Http/Controllers/Api/UserController.php.backup"
    echo ""
    echo "Please edit: app/Http/Controllers/Api/UserController.php"
    echo "In the updateProfile method (around line 71-82):"
    echo ""
    echo "Replace:"
    echo "  if (\$request->hasFile('profile_picture')) {"
    echo "      \$oldPath = str_replace(config('filesystems.disks.s3.url'), '', \$user->profile_picture);"
    echo "      S3Service::deleteFile(\$oldPath);"
    echo "      \$file = \$request->file('profile_picture');"
    echo "      \$uploadResult = S3Service::uploadWithCDN(\$file, 'profiles');"
    echo "      \$data['profile_picture'] = \$uploadResult['url'];"
    echo "  }"
    echo ""
    echo "With:"
    echo "  if (\$request->hasFile('profile_picture')) {"
    echo "      if (\$user->profile_picture) {"
    echo "          \$oldPath = str_replace(url('storage/'), '', \$user->profile_picture);"
    echo "          Storage::delete('public/' . \$oldPath);"
    echo "      }"
    echo "      \$file = \$request->file('profile_picture');"
    echo "      \$filename = time() . '_' . Str::random(10) . '.' . \$file->getClientOriginalExtension();"
    echo "      \$path = \$file->storeAs('profiles', \$filename, 'public');"
    echo "      \$data['profile_picture'] = url('storage/' . \$path);"
    echo "  }"
    echo ""
else
    echo "3️⃣ AWS is configured - checking connection..."
    echo "-----------------------------------"
    # Test S3 connection
    docker-compose exec app php artisan tinker --execute="try { \Illuminate\Support\Facades\Storage::disk('s3')->put('test.txt', 'test'); echo 'S3 Connection: OK'; } catch (\Exception \$e) { echo 'S3 Error: ' . \$e->getMessage(); }"
fi

# Step 4: Create storage link
echo ""
echo "4️⃣ Creating storage symbolic link..."
echo "-----------------------------------"
docker-compose exec app php artisan storage:link
echo "✅ Storage link created"

# Step 5: Fix permissions
echo ""
echo "5️⃣ Fixing storage permissions..."
echo "-----------------------------------"
docker-compose exec app chown -R www-data:www-data /var/www/html/storage
docker-compose exec app chmod -R 775 /var/www/html/storage
echo "✅ Permissions fixed"

# Step 6: Clear caches
echo ""
echo "6️⃣ Clearing caches..."
echo "-----------------------------------"
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
echo "✅ Caches cleared"

# Step 7: Restart
echo ""
echo "7️⃣ Restarting application..."
echo "-----------------------------------"
docker-compose restart app
sleep 5
echo "✅ Application restarted"

echo ""
echo "=============================================="
echo "✅ Fix process complete!"
echo ""
echo "Next steps:"
echo "1. If AWS was not configured, edit UserController.php as shown above"
echo "2. Test the endpoint:"
echo "   curl -X PUT http://38.180.244.178/api/users/profile \\"
echo "     -H \"Authorization: Bearer YOUR_TOKEN\" \\"
echo "     -F \"full_name=Test User\""
echo ""
echo "3. Check logs if still failing:"
echo "   docker-compose logs app --tail 50"
echo ""

