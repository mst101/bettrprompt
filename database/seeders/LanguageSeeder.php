<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/languages.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle);

        while ($row = fgetcsv($handle)) {
            DB::table('languages')->insertOrIgnore([
                'id' => $row[0],
                'name' => $row[1],
                'active' => (bool) $row[2],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
