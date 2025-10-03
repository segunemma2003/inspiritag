<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Photography',
                'slug' => 'photography',
                'description' => 'Beautiful photos and visual content',
                'color' => '#FF6B6B',
                'icon' => 'camera',
            ],
            [
                'name' => 'Travel',
                'slug' => 'travel',
                'description' => 'Travel experiences and destinations',
                'color' => '#4ECDC4',
                'icon' => 'map-pin',
            ],
            [
                'name' => 'Food',
                'slug' => 'food',
                'description' => 'Delicious food and recipes',
                'color' => '#45B7D1',
                'icon' => 'utensils',
            ],
            [
                'name' => 'Technology',
                'slug' => 'technology',
                'description' => 'Tech news and innovations',
                'color' => '#96CEB4',
                'icon' => 'cpu',
            ],
            [
                'name' => 'Art',
                'slug' => 'art',
                'description' => 'Creative art and design',
                'color' => '#FFEAA7',
                'icon' => 'palette',
            ],
            [
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'description' => 'Daily life and lifestyle content',
                'color' => '#DDA0DD',
                'icon' => 'heart',
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'description' => 'Sports and fitness content',
                'color' => '#98D8C8',
                'icon' => 'activity',
            ],
            [
                'name' => 'Music',
                'slug' => 'music',
                'description' => 'Music and entertainment',
                'color' => '#F7DC6F',
                'icon' => 'music',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('Categories seeded successfully!');
    }
}
