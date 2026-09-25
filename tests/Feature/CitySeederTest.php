<?php

namespace Tests\Feature;

use App\Models\State;
use Database\Seeders\CitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CitySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_city_seeder_inserts_cities_for_all_mapped_states(): void
    {
        State::create(['name' => 'Texas', 'code' => 'TX', 'slug' => 'texas']);
        State::create(['name' => 'Washington, D.C.', 'code' => 'WADC', 'slug' => 'washington-dc']);

        $this->seed(CitySeeder::class);

        $this->assertGreaterThan(0, DB::table('cities')->count());

        $this->assertDatabaseHas('cities', [
            'name' => 'Houston',
            'state_id' => State::where('code', 'TX')->value('id'),
        ]);

        $this->assertDatabaseHas('cities', [
            'name' => 'Washington D.C.',
            'state_id' => State::where('code', 'WADC')->value('id'),
        ]);
    }

    public function test_city_seeder_is_idempotent(): void
    {
        State::create(['name' => 'Texas', 'code' => 'TX', 'slug' => 'texas']);

        $this->seed(CitySeeder::class);
        $firstCount = DB::table('cities')->count();

        $this->seed(CitySeeder::class);

        $this->assertSame($firstCount, DB::table('cities')->count());
    }
}
