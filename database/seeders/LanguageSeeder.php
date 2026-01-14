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
     *
     * When SEED_ACTIVE_ONLY environment variable is set to 'true',
     * only seeds languages needed for whitelisted test countries:
     * en-GB, en-US, de-DE, fr-FR, es-ES, it-IT, es-MX
     * This optimises test seeding from 45 languages to 7.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/languages.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle, null, ',', '"', '\\');

        $activeOnly = getenv('SEED_ACTIVE_ONLY') === 'true';
        $whitelistedLanguages = ['en-GB', 'en-US', 'de-DE', 'fr-FR', 'es-ES', 'it-IT', 'es-MX'];

        while ($row = fgetcsv($handle, null, ',', '"', '\\')) {
            $languageId = $row[0];

            // Skip languages not in whitelist if activeOnly mode is enabled
            if ($activeOnly && ! in_array($languageId, $whitelistedLanguages)) {
                continue;
            }

            DB::table('languages')->insertOrIgnore([
                'id' => $languageId,
                'name' => $row[1],
                'active' => (bool) $row[2],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
