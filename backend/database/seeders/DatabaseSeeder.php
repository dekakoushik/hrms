<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Superadmin Account
        User::updateOrCreate(
            ['email' => 'superadmin@hrms.local'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('SuperAdmin@12345'),
                'role' => 'superadmin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
