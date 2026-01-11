<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvFile = database_path('seeders/csv/currencies.csv');
        $handle = fopen($csvFile, 'r');

        // Skip header row
        fgetcsv($handle);

        while ($row = fgetcsv($handle)) {
            DB::table('currencies')->insertOrIgnore([
                'id' => $row[0],
                'symbol' => $row[1],
                'thousands_separator' => $row[2],
                'decimal_separator' => $row[3],
                'symbol_on_left' => (bool) $row[4],
                'space_between_amount_and_symbol' => (bool) $row[5],
                'rounding_coefficient' => (int) $row[6],
                'decimal_digits' => (int) $row[7],
                'active' => (bool) $row[8],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        fclose($handle);
    }
}
