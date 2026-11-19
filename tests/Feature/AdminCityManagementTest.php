<?php

use App\Models\City;
use App\Models\IndustryJobPost;
use App\Models\State;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    $this->userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

    $this->adminUser = User::factory()->create(['is_verified' => true]);
    $this->adminUser->assignRole([$this->userRole, $this->adminRole]);

    $this->regularUser = User::factory()->create(['is_verified' => true]);
    $this->regularUser->assignRole($this->userRole);
    UserProfile::create(['user_id' => $this->regularUser->id]);

    $this->state = State::create([
        'name' => 'Texas',
        'code' => 'TX',
        'slug' => 'texas',
    ]);

    $this->stateCa = State::create([
        'name' => 'California',
        'code' => 'CA',
        'slug' => 'california',
    ]);
});

test('guest cannot access admin cities endpoints', function (): void {
    $this->getJson('/api/admin/cities')->assertStatus(401);
});

test('non-admin user is forbidden from admin cities endpoints', function (): void {
    $this->actingAs($this->regularUser, 'api')
        ->getJson('/api/admin/cities')
        ->assertStatus(403);
});

test('admin can retrieve states list for dropdown', function (): void {
    City::create(['state_id' => $this->state->id, 'name' => 'Austin']);

    $response = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/cities/states');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonFragment([
            'name' => 'Texas',
            'code' => 'TX',
        ]);
});

test('admin can list cities with search and state filter', function (): void {
    $austin = City::create(['state_id' => $this->state->id, 'name' => 'Austin']);
    $dallas = City::create(['state_id' => $this->state->id, 'name' => 'Dallas']);
    $la = City::create(['state_id' => $this->stateCa->id, 'name' => 'Los Angeles']);

    // Filter by Texas
    $response = $this->actingAs($this->adminUser, 'api')
        ->getJson("/api/admin/cities?state_id={$this->state->id}");

    $response->assertStatus(200);
    $data = $response->json('data');
    expect(count($data))->toBe(2);

    // Search by name
    $searchResponse = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/cities?search=Angeles');

    $searchResponse->assertStatus(200);
    $searchData = $searchResponse->json('data');
    expect(count($searchData))->toBe(1);
    expect($searchData[0]['name'])->toBe('Los Angeles');
});

test('admin can create a new city and validation prevents duplicates within state', function (): void {
    $response = $this->actingAs($this->adminUser, 'api')
        ->postJson('/api/admin/cities', [
            'state_id' => $this->state->id,
            'name' => 'Houston',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Houston',
                'state_id' => $this->state->id,
            ],
        ]);

    $this->assertDatabaseHas('cities', [
        'state_id' => $this->state->id,
        'name' => 'Houston',
    ]);

    // Duplicate in same state should fail
    $dupResponse = $this->actingAs($this->adminUser, 'api')
        ->postJson('/api/admin/cities', [
            'state_id' => $this->state->id,
            'name' => 'Houston',
        ]);

    $dupResponse->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('admin can update a city name', function (): void {
    $city = City::create(['state_id' => $this->state->id, 'name' => 'Huston']);

    $response = $this->actingAs($this->adminUser, 'api')
        ->putJson("/api/admin/cities/{$city->id}", [
            'state_id' => $this->state->id,
            'name' => 'Houston',
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $city->id,
                'name' => 'Houston',
            ],
        ]);

    $this->assertDatabaseHas('cities', [
        'id' => $city->id,
        'name' => 'Houston',
    ]);
});

test('admin can delete an unused city', function (): void {
    $city = City::create(['state_id' => $this->state->id, 'name' => 'Testville']);

    $response = $this->actingAs($this->adminUser, 'api')
        ->deleteJson("/api/admin/cities/{$city->id}");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $this->assertDatabaseMissing('cities', [
        'id' => $city->id,
    ]);
});

test('admin is blocked from deleting a city linked to job posts', function (): void {
    $city = City::create(['state_id' => $this->state->id, 'name' => 'Austin']);

    // Create an industry and a job post referencing Austin
    $industry = \App\Models\Industry::create([
        'name' => 'Test Tech',
        'slug' => 'test-tech',
        'industry' => 'neuroscience',
        'created_by' => $this->adminUser->id,
    ]);

    IndustryJobPost::create([
        'job_id' => 'JOB-TEST-001',
        'industry_id' => $industry->id,
        'created_by' => $this->adminUser->id,
        'job_title' => 'Neuroscientist Researcher',
        'state_id' => $this->state->id,
        'city_id' => $city->id,
        'status' => 'published',
    ]);

    $response = $this->actingAs($this->adminUser, 'api')
        ->deleteJson("/api/admin/cities/{$city->id}");

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
        ]);

    expect($response->json('message'))->toContain('Cannot delete');
    expect($response->json('data.jobs_count'))->toBe(1);

    // City must still exist in database
    $this->assertDatabaseHas('cities', [
        'id' => $city->id,
    ]);
});
