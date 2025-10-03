<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use App\Models\Tag;

class TestPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure dependencies are seeded first
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
        ]);

        // Get users for posts
        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        // Get categories
        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->command->error('No categories found. Please run CategorySeeder first.');
            return;
        }

        // Get tags
        $tags = Tag::all();
        if ($tags->isEmpty()) {
            $this->command->error('No tags found. Please run TagSeeder first.');
            return;
        }

        // Test image URLs that should return 200
        $imageUrls = [
            'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=800&h=600&fit=crop',
            'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=600&fit=crop&auto=format',
        ];

        // Test video URLs that should return 200
        $videoUrls = [
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
        ];

        $this->command->info('Verifying image URLs...');

        // Verify image URLs return 200
        foreach ($imageUrls as $index => $url) {
            $statusCode = $this->checkUrlStatus($url);
            $this->command->info("Image " . ($index + 1) . ": {$url} - Status: {$statusCode}");

            if ($statusCode === 200) {
                $randomUser = $users->random();
                $post = Post::create([
                    'user_id' => $randomUser->id,
                    'category_id' => $categories->random()->id,
                    'caption' => "Test image post " . ($index + 1) . " - Beautiful scenery captured in high quality. #nature #photography",
                    'media_url' => $url,
                    'media_type' => 'image',
                    'thumbnail_url' => $url, // Same as media_url for images
                    'media_metadata' => [
                        'width' => 800,
                        'height' => 600,
                        'size' => rand(100000, 500000),
                        'format' => 'jpeg'
                    ],
                    'location' => 'Test Location ' . ($index + 1),
                    'is_public' => true,
                    'likes_count' => rand(10, 100),
                    'saves_count' => rand(5, 50),
                    'comments_count' => rand(0, 20),
                ]);

                // Attach random tags
                $randomTags = $tags->random(rand(2, 4));
                $post->tags()->attach($randomTags->pluck('id'));
            }
        }

        $this->command->info('Verifying video URLs...');

        // Verify video URLs return 200
        foreach ($videoUrls as $index => $url) {
            $statusCode = $this->checkUrlStatus($url);
            $this->command->info("Video " . ($index + 1) . ": {$url} - Status: {$statusCode}");

            if ($statusCode === 200) {
                $randomUser = $users->random();
                $post = Post::create([
                    'user_id' => $randomUser->id,
                    'category_id' => $categories->random()->id,
                    'caption' => "Test video post " . ($index + 1) . " - Amazing video content for testing purposes. #video #content",
                    'media_url' => $url,
                    'media_type' => 'video',
                    'thumbnail_url' => 'https://picsum.photos/800/600?random=' . (10 + $index), // Generate thumbnail
                    'media_metadata' => [
                        'width' => 1280,
                        'height' => 720,
                        'duration' => rand(30, 180),
                        'size' => rand(1000000, 5000000),
                        'format' => 'mp4'
                    ],
                    'location' => 'Video Location ' . ($index + 1),
                    'is_public' => true,
                    'likes_count' => rand(20, 150),
                    'saves_count' => rand(10, 75),
                    'comments_count' => rand(5, 30),
                ]);

                // Attach random tags
                $randomTags = $tags->random(rand(2, 4));
                $post->tags()->attach($randomTags->pluck('id'));
            }
        }

        $this->command->info('Test posts seeder completed successfully!');
    }

    /**
     * Check if URL returns 200 status code
     */
    private function checkUrlStatus(string $url): int
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 10,
                    'user_agent' => 'Mozilla/5.0 (compatible; TestBot/1.0)',
                    'follow_location' => true,
                    'max_redirects' => 3,
                ]
            ]);

            $headers = @get_headers($url, 1, $context);

            if ($headers === false) {
                return 0; // Failed to get headers
            }

            // Extract status code from first header
            $statusLine = $headers[0];
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches)) {
                return (int) $matches[1];
            }

            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
