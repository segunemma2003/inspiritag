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
                    // Extract the S3 path from the full URL
                    $s3Url = config('filesystems.disks.s3.url');
                    $cdnUrl = config('filesystems.disks.s3.cdn_url');

                    $oldPath = $user->profile_picture;

                    // Remove domain part to get just the path
                    if ($s3Url) {
                        $oldPath = str_replace($s3Url, '', $oldPath);
                    }
                    if ($cdnUrl) {
                        $oldPath = str_replace($cdnUrl, '', $oldPath);
                    }

                    // Remove leading slashes
                    $oldPath = ltrim($oldPath, '/');

                    if ($oldPath) {
                        S3Service::deleteFile($oldPath);
                    }
                } catch (\Exception $e) {
                    // Log but don't fail - old picture deletion is not critical
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

