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
     * only seeds countries that have BOTH:
     * - An active language (en-GB, en-US, de-DE, fr-FR, es-ES)
     * - An active currency (GBP, EUR, USD)
     * This optimises test seeding from 247 countries to ~44.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/countries.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        $activeOnly = getenv('SEED_ACTIVE_ONLY') === 'true';
        $activeLanguages = ['en-GB', 'en-US', 'de-DE', 'fr-FR', 'es-ES'];
        $activeCurrencies = ['GBP', 'EUR', 'USD'];

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            $languageId = $row[3];
            $currencyId = $row[2];

            // Skip countries without active language/currency if activeOnly mode is enabled
            if ($activeOnly &&
                (! in_array($languageId, $activeLanguages) || ! in_array($currencyId, $activeCurrencies))) {
                continue;
            }

            DB::table('countries')->insertOrIgnore([
                'id' => $row[0],
                'continent_id' => $row[1] ?: null,
                'currency_id' => $currencyId,
                'language_id' => $languageId,
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
