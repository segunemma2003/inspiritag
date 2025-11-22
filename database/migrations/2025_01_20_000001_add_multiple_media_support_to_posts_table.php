<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Change media_url from string to text (to support JSON strings)
            // This allows storing both single URL (backward compatible) and JSON array of URLs
        });

        // Convert existing media_url to text type to support JSON arrays
        // We'll keep storing as text/JSON string for better compatibility
        DB::statement('ALTER TABLE posts MODIFY COLUMN media_url TEXT');
        
        // Convert existing single URLs to JSON format (as JSON strings for backward compatibility)
        $posts = DB::table('posts')->whereNotNull('media_url')->get();
        foreach ($posts as $post) {
            $mediaUrl = $post->media_url;
            // If not already JSON, convert to JSON array format
            $decoded = json_decode($mediaUrl, true);
            if (!is_array($decoded)) {
                // Convert single URL to JSON array
                DB::table('posts')
                    ->where('id', $post->id)
                    ->update(['media_url' => json_encode([$mediaUrl])]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert JSON back to string (only first URL will be kept)
        // Extract first URL from JSON array if it's an array
        $posts = DB::table('posts')->whereNotNull('media_url')->get();
        foreach ($posts as $post) {
            $decoded = json_decode($post->media_url, true);
            if (is_array($decoded) && !empty($decoded)) {
                // Take the first URL
                DB::table('posts')
                    ->where('id', $post->id)
                    ->update(['media_url' => $decoded[0] ?? '']);
            }
        }
        
        // Change back to string
        DB::statement('ALTER TABLE posts MODIFY COLUMN media_url VARCHAR(255)');
    }
};

