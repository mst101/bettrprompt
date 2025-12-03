<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Mark Thompson',
            'email' => 'info@hiddengambia.com',
            'password' => bcrypt('voodoo90'),
            'personality_type' => 'INTP-A',
            'trait_percentages' => [
                'mind' => 65,
                'energy' => 64,
                'nature' => 84,
                'tactics' => 57,
                'identity' => 84,
            ],
            'is_admin' => true,
        ]);
    }
}
