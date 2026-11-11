<?php

declare(strict_types=1);

use App\Models\AcademiaPostBaccalaureateCategory;
use App\Models\AcademiaPostBaccalaureateProgram;
use App\Models\State;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\PostBaccalaureateCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

function makePostBaccActor(string $roleName): User
{
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $role = Role::firstOrCreate([
        'name' => $roleName,
        'guard_name' => 'api',
    ]);

    $user = User::factory()->create([
        'is_verified' => true,
    ]);
    $user->assignRole($role);

    if ($roleName === 'user') {
        UserProfile::create([
            'user_id' => $user->id,
            'country' => 'United States',
        ]);
    }

    return $user;
}

test('migration seeds the default 15 post-baccalaureate categories in exact order', function (): void {
    $categories = AcademiaPostBaccalaureateCategory::query()
        ->ordered()
        ->pluck('name')
        ->all();

    expect($categories)->toBe(PostBaccalaureateCategorySeeder::DEFAULT_CATEGORIES);
});

test('guests and non-admin users cannot access admin post-baccalaureate endpoints', function (): void {
    $this->getJson('/api/admin/academia/post-baccalaureate')->assertUnauthorized();
    $this->getJson('/api/admin/academia/post-baccalaureate/categories')->assertUnauthorized();

    $user = makePostBaccActor('user');

    $this->actingAs($user, 'api')
        ->getJson('/api/admin/academia/post-baccalaureate')
        ->assertForbidden();

    $this->actingAs($user, 'api')
        ->postJson('/api/admin/academia/post-baccalaureate/category/create', ['name' => 'Custom Option'])
        ->assertForbidden();
});

test('admin can add, update, reorder, and safely delete post-baccalaureate categories', function (): void {
    $admin = makePostBaccActor('admin');

    $createResponse = $this->actingAs($admin, 'api')
        ->postJson('/api/admin/academia/post-baccalaureate/category/create', [
            'name' => '  MHS  ',
        ]);

    $createResponse->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'MHS')
        ->assertJsonPath('data.sort_order', 16)
        ->assertJsonPath('data.is_active', true);

    $createdCategoryId = $createResponse->json('data.id');

    $this->actingAs($admin, 'api')
        ->postJson('/api/admin/academia/post-baccalaureate/category/create', [
            'name' => 'MHS',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);

    $this->actingAs($admin, 'api')
        ->putJson("/api/admin/academia/post-baccalaureate/category/update/{$createdCategoryId}", [
            'name' => 'MHS (Bridge)',
            'is_active' => false,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'MHS (Bridge)')
        ->assertJsonPath('data.is_active', false);

    $msCategory = AcademiaPostBaccalaureateCategory::where('name', 'MS')->firstOrFail();
    $certCategory = AcademiaPostBaccalaureateCategory::where('name', 'Certificate')->firstOrFail();

    $reorderResponse = $this->actingAs($admin, 'api')
        ->patchJson('/api/admin/academia/post-baccalaureate/categories/reorder', [
            'category_ids' => [$certCategory->id, $msCategory->id],
        ]);

    $reorderResponse->assertOk()
        ->assertJsonPath('data.0.id', $certCategory->id)
        ->assertJsonPath('data.0.sort_order', 1)
        ->assertJsonPath('data.1.id', $msCategory->id)
        ->assertJsonPath('data.1.sort_order', 2);

    $state = State::create(['name' => 'Georgia', 'code' => 'GA', 'slug' => 'georgia']);
    $program = AcademiaPostBaccalaureateProgram::create([
        'state_id' => $state->id,
        'program_name' => 'Emory Post-Bacc',
        'location' => 'Atlanta',
    ]);
    $program->categories()->sync([$msCategory->id]);

    $this->actingAs($admin, 'api')
        ->deleteJson("/api/admin/academia/post-baccalaureate/category/delete/{$msCategory->id}")
        ->assertStatus(422);

    $this->actingAs($admin, 'api')
        ->deleteJson("/api/admin/academia/post-baccalaureate/category/delete/{$createdCategoryId}")
        ->assertOk();

    $this->assertDatabaseMissing('academia_post_baccalaureate_categories', ['id' => $createdCategoryId]);
});

test('admin can manage post-baccalaureate programs with validation and website normalization', function (): void {
    $admin = makePostBaccActor('admin');
    $state = State::create(['name' => 'Massachusetts', 'code' => 'MA', 'slug' => 'massachusetts']);
    $ms = AcademiaPostBaccalaureateCategory::where('name', 'MS')->firstOrFail();
    $premed = AcademiaPostBaccalaureateCategory::where('name', 'PostBacc PreMed Program')->firstOrFail();

    $this->actingAs($admin, 'api')
        ->postJson('/api/admin/academia/post-baccalaureate/create', [
            'program_name' => 'Boston University GMS',
            'state_id' => $state->id,
            'latitude' => '42.335',
            'category_ids' => [],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['longitude', 'category_ids']);

    $storeResponse = $this->actingAs($admin, 'api')
        ->postJson('/api/admin/academia/post-baccalaureate/create', [
            'program_name' => 'Boston University GMS',
            'state_id' => $state->id,
            'location' => 'Boston',
            'latitude' => '42.3355',
            'longitude' => '-71.0715',
            'website' => 'www.bu.edu/gms',
            'phone' => '617-555-0100',
            'category_ids' => [$ms->id, $premed->id],
        ]);

    $storeResponse->assertCreated()
        ->assertJsonPath('data.program_name', 'Boston University GMS')
        ->assertJsonPath('data.website', 'https://www.bu.edu/gms')
        ->assertJsonPath('data.degree_recognitions', ['PostBacc PreMed Program', 'MS'])
        ->assertJsonPath('data.map_pin.latitude', '42.3355')
        ->assertJsonPath('data.map_pin.longitude', '-71.0715');

    $programId = $storeResponse->json('data.id');

    $this->actingAs($admin, 'api')
        ->getJson("/api/admin/academia/post-baccalaureate?state_id={$state->id}&category_id={$ms->id}&search=Boston")
        ->assertOk()
        ->assertJsonPath('pagination.total', 1)
        ->assertJsonPath('data.0.id', $programId);

    $this->actingAs($admin, 'api')
        ->putJson("/api/admin/academia/post-baccalaureate/update/{$programId}", [
            'program_name' => 'Boston University Medical Sciences',
            'category_ids' => [$ms->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.program_name', 'Boston University Medical Sciences')
        ->assertJsonPath('data.degree_recognitions', ['MS']);

    $this->actingAs($admin, 'api')
        ->deleteJson("/api/admin/academia/post-baccalaureate/delete/{$programId}")
        ->assertOk();

    $this->assertDatabaseMissing('academia_post_baccalaureate_programs', ['id' => $programId]);
});

test('user endpoints return state counts, map pins, ordered active filter categories, and filtered programs', function (): void {
    $user = makePostBaccActor('user');
    $state = State::create(['name' => 'Georgia', 'code' => 'GA', 'slug' => 'georgia']);

    $ms = AcademiaPostBaccalaureateCategory::where('name', 'MS')->firstOrFail();
    $mbsOnline = AcademiaPostBaccalaureateCategory::where('name', 'MBS (Online)')->firstOrFail();
    $inactive = AcademiaPostBaccalaureateCategory::create([
        'name' => 'Archived Option',
        'sort_order' => 99,
        'is_active' => false,
    ]);

    $programA = AcademiaPostBaccalaureateProgram::create([
        'state_id' => $state->id,
        'program_name' => 'Augusta PostBacc',
        'location' => 'Augusta',
        'latitude' => '33.4709',
        'longitude' => '-81.9748',
        'website' => 'https://augusta.edu',
    ]);
    $programA->categories()->sync([$ms->id, $inactive->id]);

    $programB = AcademiaPostBaccalaureateProgram::create([
        'state_id' => $state->id,
        'program_name' => 'Morehouse PreMed Bridge',
        'location' => 'Atlanta',
        'latitude' => '33.7490',
        'longitude' => '-84.3880',
        'website' => 'https://msm.edu',
    ]);
    $programB->categories()->sync([$mbsOnline->id]);

    $this->actingAs($user, 'api')
        ->getJson('/api/states?search=GA')
        ->assertOk()
        ->assertJsonPath('data.0.total_resources', 2);

    $this->actingAs($user, 'api')
        ->getJson('/api/states/GA')
        ->assertOk()
        ->assertJsonPath('data.total_resources', 2)
        ->assertJsonCount(2, 'data.post_baccalaureate_programs')
        ->assertJsonPath('data.post_baccalaureate_programs.0.website', 'https://augusta.edu');

    $listResponse = $this->actingAs($user, 'api')
        ->getJson('/api/states/GA/post-baccalaureate-programs?sort=desc');

    $listResponse->assertOk()
        ->assertJsonPath('total', 2)
        ->assertJsonPath('data.0.program_name', 'Morehouse PreMed Bridge')
        ->assertJsonPath('data.1.program_name', 'Augusta PostBacc')
        ->assertJsonCount(15, 'categories');

    $this->actingAs($user, 'api')
        ->getJson('/api/states/GA/post-baccalaureate-programs?category='.urlencode('MBS (Online)'))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.program_name', 'Morehouse PreMed Bridge');

    $this->actingAs($user, 'api')
        ->getJson('/api/states/GA/post-baccalaureate-programs?search=Atlanta')
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.program_name', 'Morehouse PreMed Bridge');

    $this->actingAs($user, 'api')
        ->getJson('/api/states/ZZ/post-baccalaureate-programs')
        ->assertNotFound();
});
