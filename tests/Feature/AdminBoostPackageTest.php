<?php

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\BoostPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    $this->userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

    $this->adminUser = User::factory()->create(['is_verified' => true]);
    $this->adminUser->assignRole([$this->userRole, $this->adminRole]);

    $this->regularUser = User::factory()->create(['is_verified' => true]);
    $this->regularUser->assignRole($this->userRole);
    UserProfile::create(['user_id' => $this->regularUser->id]);
});

test('guest cannot access admin boost package endpoints', function () {
    $this->getJson('/api/admin/boost-packages')->assertStatus(401);
});

test('non-admin user is forbidden from admin boost package endpoints', function () {
    $this->actingAs($this->regularUser, 'api')
        ->getJson('/api/admin/boost-packages')
        ->assertStatus(403);
});

test('admin can list boost packages with filtering', function () {
    $this->seed(BoostPackageSeeder::class);

    // Create an inactive job package
    BoostPackage::create([
        'name' => '3-Day Urgent Job',
        'target_entity' => 'job',
        'duration_days' => 3,
        'price' => 29.99,
        'status' => 'inactive',
        'priority_weight' => 5,
    ]);

    // 1. List all packages
    $response = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/boost-packages')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'target_entity',
                    'duration_days',
                    'price',
                    'estimated_impressions',
                    'badge',
                    'description',
                    'features',
                    'priority_weight',
                    'status',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

    expect(count($response->json('data')))->toBe(4);

    // 2. Filter by target_entity = product
    $productResponse = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/boost-packages?target_entity=product')
        ->assertStatus(200);

    expect(count($productResponse->json('data')))->toBe(3);

    // 3. Filter by status = inactive
    $inactiveResponse = $this->actingAs($this->adminUser, 'api')
        ->getJson('/api/admin/boost-packages?status=inactive')
        ->assertStatus(200);

    expect(count($inactiveResponse->json('data')))->toBe(1)
        ->and($inactiveResponse->json('data.0.name'))->toBe('3-Day Urgent Job');
});

test('admin can view single boost package', function () {
    $package = BoostPackage::create([
        'name' => '14-Day Growth',
        'target_entity' => 'product',
        'duration_days' => 14,
        'price' => 34.99,
        'badge' => 'Growth',
        'status' => 'active',
        'priority_weight' => 2,
    ]);

    $response = $this->actingAs($this->adminUser, 'api')
        ->getJson("/api/admin/boost-packages/{$package->id}")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $package->id,
                'name' => '14-Day Growth',
                'target_entity' => 'product',
                'duration_days' => 14,
                'price' => 34.99,
                'badge' => 'Growth',
                'is_active' => true,
            ],
        ]);
});

test('admin can create a new boost package', function () {
    $payload = [
        'name' => '60-Day Enterprise Visibility',
        'target_entity' => 'product',
        'duration_days' => 60,
        'price' => 119.99,
        'estimated_impressions' => '30,000 - 50,000 impressions',
        'badge' => 'Enterprise',
        'description' => 'Maximum exposure across marketplace and social feeds.',
        'features' => [
            'Pinned Marketplace Placement',
            'Sponsored Newsfeed Ingestion',
            'Full Analytics Audit',
        ],
        'priority_weight' => 10,
        'status' => 'active',
    ];

    $response = $this->actingAs($this->adminUser, 'api')
        ->postJson('/api/admin/boost-packages', $payload)
        ->assertStatus(201)
        ->assertJson([
            'success' => true,
            'message' => 'Boost package created successfully.',
            'data' => [
                'name' => '60-Day Enterprise Visibility',
                'target_entity' => 'product',
                'duration_days' => 60,
                'price' => 119.99,
                'badge' => 'Enterprise',
                'is_active' => true,
            ],
        ]);

    $this->assertDatabaseHas('boost_packages', [
        'name' => '60-Day Enterprise Visibility',
        'duration_days' => 60,
        'price' => 119.99,
    ]);
});

test('admin cannot create a boost package with invalid data', function () {
    $invalidPayload = [
        'name' => '',
        'target_entity' => 'invalid_entity_type',
        'duration_days' => -5,
        'price' => 'not-a-number',
    ];

    $this->actingAs($this->adminUser, 'api')
        ->postJson('/api/admin/boost-packages', $invalidPayload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'target_entity', 'duration_days', 'price']);
});

test('admin can update a boost package', function () {
    $package = BoostPackage::create([
        'name' => 'Old Title',
        'target_entity' => 'product',
        'duration_days' => 7,
        'price' => 10.00,
        'status' => 'active',
    ]);

    $updatePayload = [
        'name' => 'Updated 7-Day Flash Boost',
        'price' => 14.99,
        'badge' => 'Flash',
    ];

    $this->actingAs($this->adminUser, 'api')
        ->patchJson("/api/admin/boost-packages/{$package->id}", $updatePayload)
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $package->id,
                'name' => 'Updated 7-Day Flash Boost',
                'price' => 14.99,
                'badge' => 'Flash',
            ],
        ]);

    $this->assertDatabaseHas('boost_packages', [
        'id' => $package->id,
        'name' => 'Updated 7-Day Flash Boost',
        'price' => 14.99,
    ]);
});

test('admin can toggle boost package status', function () {
    $package = BoostPackage::create([
        'name' => 'Toggleable Package',
        'target_entity' => 'product',
        'duration_days' => 7,
        'price' => 19.99,
        'status' => 'active',
    ]);

    // 1. Toggle to inactive
    $this->actingAs($this->adminUser, 'api')
        ->patchJson("/api/admin/boost-packages/{$package->id}/status")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $package->id,
                'status' => 'inactive',
                'is_active' => false,
            ],
        ]);

    expect($package->fresh()->status)->toBe('inactive');

    // 2. Toggle back to active
    $this->actingAs($this->adminUser, 'api')
        ->patchJson("/api/admin/boost-packages/{$package->id}/status")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $package->id,
                'status' => 'active',
                'is_active' => true,
            ],
        ]);

    expect($package->fresh()->status)->toBe('active');
});

test('admin cannot delete a boost package if active campaigns exist', function () {
    $user = User::factory()->create();
    $industry = Industry::create(['name' => 'Test Corp', 'slug' => 'test-corp']);
    $section = IndustrySections::create(['network_type' => 'psychology', 'industry_type' => 'biotechnology', 'name' => 'Section']);
    $category = IndustryCategory::create(['section_id' => $section->id, 'category_name' => 'Category']);
    $product = IndustryProduct::create([
        'creator_id' => $user->id,
        'industry_id' => $industry->id,
        'section_id' => $section->id,
        'category_id' => $category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'EEG Device',
        'slug' => 'eeg-device',
        'description' => 'Medical grade EEG Device',
        'status' => 'active',
    ]);

    $package = BoostPackage::create([
        'name' => 'Locked Package',
        'target_entity' => 'product',
        'duration_days' => 7,
        'price' => 19.99,
        'status' => 'active',
    ]);

    AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $product->id,
        'user_id' => $user->id,
        'industry_id' => $industry->id,
        'boost_package_id' => $package->id,
        'ad_type' => 'product',
        'network_type' => 'psychology',
        'amount_paid' => 19.99,
        'duration_days' => 7,
        'status' => AdCampaignStatus::ACTIVE,
    ]);

    $this->actingAs($this->adminUser, 'api')
        ->deleteJson("/api/admin/boost-packages/{$package->id}")
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Cannot delete boost package with active campaigns. Please deactivate it instead.',
        ]);

    $this->assertDatabaseHas('boost_packages', ['id' => $package->id]);
});

test('admin can delete a boost package if no active campaigns exist', function () {
    $package = BoostPackage::create([
        'name' => 'Unused Package',
        'target_entity' => 'product',
        'duration_days' => 7,
        'price' => 19.99,
        'status' => 'inactive',
    ]);

    $this->actingAs($this->adminUser, 'api')
        ->deleteJson("/api/admin/boost-packages/{$package->id}")
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Boost package deleted successfully.',
        ]);

    $this->assertDatabaseMissing('boost_packages', ['id' => $package->id]);
});

test('advertiser can view active boost packages and custom slider configuration', function () {
    $this->seed(BoostPackageSeeder::class);

    // Add an inactive package to confirm it is excluded from advertiser view
    BoostPackage::create([
        'name' => 'Draft Package',
        'target_entity' => 'product',
        'duration_days' => 5,
        'price' => 9.99,
        'status' => 'inactive',
    ]);

    $response = $this->actingAs($this->regularUser, 'api')
        ->getJson('/api/industry/boost-packages?target_entity=product')
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'preset_packages' => [
                    '*' => [
                        'id',
                        'name',
                        'duration_days',
                        'price',
                        'is_active',
                    ],
                ],
                'custom_slider' => [
                    'enabled',
                    'daily_rate',
                    'currency',
                    'min_days',
                    'max_days',
                    'daily_estimated_impressions_min',
                    'daily_estimated_impressions_max',
                ],
            ],
        ]);

    $presetPackages = $response->json('data.preset_packages');
    expect(count($presetPackages))->toBe(3); // Only the 3 active seeded packages, excluding inactive

    $slider = $response->json('data.custom_slider');
    expect($slider['enabled'])->toBeTrue()
        ->and((float) $slider['daily_rate'])->toEqual(3.0)
        ->and($slider['min_days'])->toBe(3)
        ->and($slider['max_days'])->toBe(60);
});
