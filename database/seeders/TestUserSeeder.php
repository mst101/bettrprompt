<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates a test user specifically for e2e framework selection tests.
     * The user is used to test which prompt framework is selected for different personality types.
     */
    public function run(): void
    {
        // Create or update test user
        User::updateOrCreate(
            ['email' => 'test@hiddengambia.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('voodoo90'),
                'email_verified_at' => now(),
            ]
        );
    }
}
