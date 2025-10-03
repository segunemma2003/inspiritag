<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // Photography tags
            ['name' => 'nature', 'slug' => 'nature'],
            ['name' => 'portrait', 'slug' => 'portrait'],
            ['name' => 'landscape', 'slug' => 'landscape'],
            ['name' => 'sunset', 'slug' => 'sunset'],
            ['name' => 'photography', 'slug' => 'photography'],
            
            // Travel tags
            ['name' => 'travel', 'slug' => 'travel'],
            ['name' => 'adventure', 'slug' => 'adventure'],
            ['name' => 'city', 'slug' => 'city'],
            ['name' => 'beach', 'slug' => 'beach'],
            ['name' => 'mountains', 'slug' => 'mountains'],
            
            // Food tags
            ['name' => 'food', 'slug' => 'food'],
            ['name' => 'recipe', 'slug' => 'recipe'],
            ['name' => 'cooking', 'slug' => 'cooking'],
            ['name' => 'delicious', 'slug' => 'delicious'],
            ['name' => 'healthy', 'slug' => 'healthy'],
            
            // Technology tags
            ['name' => 'tech', 'slug' => 'tech'],
            ['name' => 'innovation', 'slug' => 'innovation'],
            ['name' => 'ai', 'slug' => 'ai'],
            ['name' => 'coding', 'slug' => 'coding'],
            ['name' => 'startup', 'slug' => 'startup'],
            
            // Art tags
            ['name' => 'art', 'slug' => 'art'],
            ['name' => 'creative', 'slug' => 'creative'],
            ['name' => 'design', 'slug' => 'design'],
            ['name' => 'painting', 'slug' => 'painting'],
            ['name' => 'digital', 'slug' => 'digital'],
            
            // Lifestyle tags
            ['name' => 'lifestyle', 'slug' => 'lifestyle'],
            ['name' => 'inspiration', 'slug' => 'inspiration'],
            ['name' => 'motivation', 'slug' => 'motivation'],
            ['name' => 'wellness', 'slug' => 'wellness'],
            ['name' => 'mindfulness', 'slug' => 'mindfulness'],
            
            // Sports tags
            ['name' => 'sports', 'slug' => 'sports'],
            ['name' => 'fitness', 'slug' => 'fitness'],
            ['name' => 'workout', 'slug' => 'workout'],
            ['name' => 'running', 'slug' => 'running'],
            ['name' => 'gym', 'slug' => 'gym'],
            
            // Music tags
            ['name' => 'music', 'slug' => 'music'],
            ['name' => 'concert', 'slug' => 'concert'],
            ['name' => 'artist', 'slug' => 'artist'],
            ['name' => 'song', 'slug' => 'song'],
            ['name' => 'live', 'slug' => 'live'],
            
            // General tags
            ['name' => 'fun', 'slug' => 'fun'],
            ['name' => 'amazing', 'slug' => 'amazing'],
            ['name' => 'beautiful', 'slug' => 'beautiful'],
            ['name' => 'love', 'slug' => 'love'],
            ['name' => 'happy', 'slug' => 'happy'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }

        $this->command->info('Tags seeded successfully!');
    }
}
