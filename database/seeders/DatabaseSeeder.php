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
        // Seed reference data first
        $this->call([
            LanguageSeeder::class,
            CurrencySeeder::class,
            CountrySeeder::class,
            ClaudeModelSeeder::class,
            PricesTableSeeder::class,
            QuestionSeeder::class,
            QuestionVariantSeeder::class,
        ]);

        // User::factory(10)->create();

        // Create admin user if it doesn't already exist
        User::firstOrCreate(
            ['email' => 'info@hiddengambia.com'],
            [
                'name' => 'Mark Thompson',
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
            ]
        );
    }
}
