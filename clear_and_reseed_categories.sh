#!/bin/bash
# Clear feeds and categories data, then reseed with new categories

echo "🧹 Clearing feeds and categories data..."
echo "========================================"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Not in Laravel project directory. Please run from project root."
    exit 1
fi

echo ""
echo "⚠️  WARNING: This will delete ALL posts and categories!"
echo "Press Ctrl+C to cancel, or Enter to continue..."
read

echo ""
echo "🗑️  Clearing existing data..."

# Clear posts (feeds)
echo "Deleting all posts..."
php artisan tinker --execute="
\App\Models\Post::truncate();
echo 'Posts cleared successfully';
"

# Clear categories
echo "Deleting all categories..."
php artisan tinker --execute="
\App\Models\Category::truncate();
echo 'Categories cleared successfully';
"

echo ""
echo "📝 Updating CategorySeeder with new categories..."

# Update the CategorySeeder
cat > database/seeders/CategorySeeder.php << 'EOF'
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
                'name' => 'Men',
                'slug' => 'men',
                'description' => 'Men\'s fashion and lifestyle content',
                'color' => '#3B82F6',
                'icon' => 'user',
            ],
            [
                'name' => 'Women',
                'slug' => 'women',
                'description' => 'Women\'s fashion and lifestyle content',
                'color' => '#EC4899',
                'icon' => 'user',
            ],
            [
                'name' => 'Hair',
                'slug' => 'hair',
                'description' => 'Hair styling, care, and inspiration',
                'color' => '#8B5CF6',
                'icon' => 'scissors',
            ],
            [
                'name' => 'Skincare',
                'slug' => 'skincare',
                'description' => 'Skincare routines and beauty tips',
                'color' => '#10B981',
                'icon' => 'droplet',
            ],
            [
                'name' => 'Nails',
                'slug' => 'nails',
                'description' => 'Nail art, designs, and care',
                'color' => '#F59E0B',
                'icon' => 'hand',
            ],
            [
                'name' => 'Tattoos',
                'slug' => 'tattoos',
                'description' => 'Tattoo designs and inspiration',
                'color' => '#EF4444',
                'icon' => 'zap',
            ],
            [
                'name' => 'Make-up',
                'slug' => 'make-up',
                'description' => 'Makeup tutorials and beauty looks',
                'color' => '#F97316',
                'icon' => 'sparkles',
            ],
            [
                'name' => 'Outfits',
                'slug' => 'outfits',
                'description' => 'Fashion outfits and styling',
                'color' => '#06B6D4',
                'icon' => 'shirt',
            ],
            [
                'name' => 'Wedding',
                'slug' => 'wedding',
                'description' => 'Wedding fashion and inspiration',
                'color' => '#84CC16',
                'icon' => 'heart',
            ],
            [
                'name' => 'Fitness',
                'slug' => 'fitness',
                'description' => 'Fitness and workout content',
                'color' => '#14B8A6',
                'icon' => 'activity',
            ],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Categories seeded successfully!');
    }
}
EOF

echo "✅ CategorySeeder updated!"

echo ""
echo "🌱 Seeding new categories..."
php artisan db:seed --class=CategorySeeder

echo ""
echo "✅ Data cleared and reseeded successfully!"
echo ""
echo "📊 New categories:"
php artisan tinker --execute="
\App\Models\Category::all()->each(function(\$cat) {
    echo \$cat->name . ' (' . \$cat->slug . ')' . PHP_EOL;
});
"

echo ""
echo "🎉 All done! Your database now has:"
echo "   - All posts cleared"
echo "   - New categories: men, women, hair, skincare, nails, tattoos, make-up, outfits, wedding, fitness"
