<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/countries.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle);

        while ($row = fgetcsv($handle)) {
            DB::table('countries')->insertOrIgnore([
                'id' => $row[0],
                'continent_id' => $row[1] ?: null,
                'currency_id' => $row[2],
                'language_id' => $row[3],
                'first_day_of_week' => $row[4],
                'uses_miles' => (bool) $row[5],
                'name' => $row[6],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
