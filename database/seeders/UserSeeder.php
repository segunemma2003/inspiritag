<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'John Doe',
                'full_name' => 'John Michael Doe',
                'username' => 'johndoe',
                'email' => 'john@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'bio' => 'Photography enthusiast and travel lover. Capturing moments around the world.',
                'profile_picture' => 'https://picsum.photos/200/200?random=1',
                'profession' => 'Photographer',
                'is_business' => false,
                'is_admin' => false,
            ],
            [
                'name' => 'Jane Smith',
                'full_name' => 'Jane Elizabeth Smith',
                'username' => 'janesmith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'bio' => 'Food blogger and chef. Sharing delicious recipes and culinary adventures.',
                'profile_picture' => 'https://picsum.photos/200/200?random=2',
                'profession' => 'Chef',
                'is_business' => false,
                'is_admin' => false,
            ],
            [
                'name' => 'Mike Johnson',
                'full_name' => 'Michael Robert Johnson',
                'username' => 'mikejohnson',
                'email' => 'mike@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'bio' => 'Tech entrepreneur and AI enthusiast. Building the future one line of code at a time.',
                'profile_picture' => 'https://picsum.photos/200/200?random=3',
                'profession' => 'Software Engineer',
                'is_business' => false,
                'is_admin' => false,
            ],
            [
                'name' => 'Sarah Wilson',
                'full_name' => 'Sarah Michelle Wilson',
                'username' => 'sarahwilson',
                'email' => 'sarah@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'bio' => 'Digital artist and designer. Creating beautiful visuals and inspiring others.',
                'profile_picture' => 'https://picsum.photos/200/200?random=4',
                'profession' => 'Digital Artist',
                'is_business' => false,
                'is_admin' => false,
            ],
            [
                'name' => 'Test User',
                'full_name' => 'Test User Account',
                'username' => 'testuser',
                'email' => 'test@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'bio' => 'This is a test account for development and testing purposes.',
                'profile_picture' => 'https://picsum.photos/200/200?random=5',
                'profession' => 'Tester',
                'is_business' => false,
                'is_admin' => true,
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Users seeded successfully!');
    }
}
