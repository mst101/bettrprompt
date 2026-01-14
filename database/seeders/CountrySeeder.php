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
     *
     * When SEED_ACTIVE_ONLY environment variable is set to 'true',
     * only seeds a whitelist of 8 test countries:
     * gb, us, de, fr, es, it, be, mx
     * This optimises test seeding from 247 countries to 8.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/countries.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        $activeOnly = getenv('SEED_ACTIVE_ONLY') === 'true';
        $whitelistedCountries = ['gb', 'us', 'de', 'fr', 'es', 'it', 'be', 'mx'];

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            $countryId = $row[0];

            // Skip countries not in whitelist if activeOnly mode is enabled
            if ($activeOnly && ! in_array(strtolower($countryId), $whitelistedCountries)) {
                continue;
            }

            DB::table('countries')->insertOrIgnore([
                'id' => $countryId,
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
