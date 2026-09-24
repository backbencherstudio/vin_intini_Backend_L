<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    private const CSV_PATH = 'database/data/us_cities.csv';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statesByCode = State::pluck('id', 'code')->all();

        if (! isset($statesByCode['DC']) && isset($statesByCode['WADC'])) {
            $statesByCode['DC'] = $statesByCode['WADC'];
        }

        $rows = [];

        $handle = fopen(base_path(self::CSV_PATH), 'r');
        fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $code = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');

            if ($name === '' || ! isset($statesByCode[$code])) {
                continue;
            }

            $rows[] = ['state_id' => $statesByCode[$code], 'name' => $name];

            if (count($rows) >= 500) {
                DB::table('cities')->insertOrIgnore($rows);
                $rows = [];
            }
        }

        fclose($handle);

        if ($rows !== []) {
            DB::table('cities')->insertOrIgnore($rows);
        }
    }
}
