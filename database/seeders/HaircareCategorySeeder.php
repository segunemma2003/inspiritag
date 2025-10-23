<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class HaircareCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hair Care',
                'slug' => 'hair-care',
                'description' => 'Hair care tips, products, and tutorials',
                'color' => '#8B4513',
                'icon' => '💇‍♀️',
                'is_active' => true,
            ],
            [
                'name' => 'Hair Styling',
                'slug' => 'hair-styling',
                'description' => 'Hair styling techniques and tutorials',
                'color' => '#D2691E',
                'icon' => '💇‍♂️',
                'is_active' => true,
            ],
            [
                'name' => 'Hair Color',
                'slug' => 'hair-color',
                'description' => 'Hair coloring techniques and trends',
                'color' => '#FF6347',
                'icon' => '🎨',
                'is_active' => true,
            ],
            [
                'name' => 'Hair Treatments',
                'slug' => 'hair-treatments',
                'description' => 'Hair treatment and repair solutions',
                'color' => '#32CD32',
                'icon' => '🌿',
                'is_active' => true,
            ],
            [
                'name' => 'Hair Extensions',
                'slug' => 'hair-extensions',
                'description' => 'Hair extensions and wigs',
                'color' => '#9370DB',
                'icon' => '👑',
                'is_active' => true,
            ],
            [
                'name' => 'Hair Tools',
                'slug' => 'hair-tools',
                'description' => 'Hair styling tools and equipment',
                'color' => '#696969',
                'icon' => '🔧',
                'is_active' => true,
            ]
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }

        $this->command->info('Haircare categories seeded successfully!');
    }
}