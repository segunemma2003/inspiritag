<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@inspirtag.com';

        if (User::where('email', $email)->exists()) {
            return;
        }

        User::create([
            'name' => 'Inspirtag Admin',
            'full_name' => 'Inspirtag Admin',
            'username' => 'inspirtag_admin',
            'email' => $email,
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
            'is_admin' => true,
            'is_business' => false,
            'is_professional' => false,
            'status' => 'active',
            'remember_token' => Str::random(10),
        ]);
    }
}
