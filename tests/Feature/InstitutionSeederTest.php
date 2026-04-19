<?php

namespace Tests\Feature;

use Database\Seeders\InstitutionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InstitutionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_institution_seeder_inserts_records(): void
    {
        $this->seed(InstitutionSeeder::class);

        $this->assertGreaterThan(0, DB::table('institutions')->count());

        $this->assertDatabaseHas('institutions', [
            'name' => 'Harvard University',
            'country' => 'United States',
        ]);
    }
}
