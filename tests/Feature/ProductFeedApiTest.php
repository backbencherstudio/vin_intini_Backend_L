<?php

use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

    $this->user = User::factory()->create(['is_verified' => true]);
    $this->user->assignRole('user');
    UserProfile::create([
        'user_id' => $this->user->id,
        'privacy_profile_activity' => 'everyone',
    ]);

    $this->industry = Industry::create([
        'created_by' => $this->user->id,
        'name' => 'Feed Test Pharma',
        'slug' => 'feed-test-pharma',
        'industry' => 'biotechnology',
    ]);

    $this->sectionA = IndustrySections::create([
        'name' => 'Lab Devices',
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
    ]);

    $this->categoryA1 = IndustryCategory::create([
        'section_id' => $this->sectionA->id,
        'category_name' => 'EEGs',
    ]);

    $this->categoryA2 = IndustryCategory::create([
        'section_id' => $this->sectionA->id,
        'category_name' => 'Bio-Sensors',
    ]);

    $this->package = BoostPackage::create([
        'name' => 'Starter Boost',
        'target_entity' => 'product',
        'duration_days' => 7,
        'price' => 19.99,
        'status' => 'active',
        'badge' => 'Sponsored',
    ]);
});

test('product feed requires network_type and industry_type', function () {
    $response = $this->actingAs($this->user, 'api')->getJson('/api/industry/product-feed');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['network_type', 'industry_type']);
});

test('product feed returns section-wise overview by default', function () {
    IndustryProduct::create([
        'creator_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->sectionA->id,
        'category_id' => $this->categoryA1->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Organic EEG Headset',
        'slug' => 'organic-eeg-headset',
        'description' => 'A standard organic EEG headset.',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user, 'api')->getJson('/api/industry/product-feed?network_type=psychology&industry_type=biotechnology');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'section_id',
                    'section_name',
                    'network_type',
                    'industry_type',
                    'total_products',
                    'has_more',
                    'categories',
                    'products',
                ],
            ],
        ]);

    expect($response->json('data.0.section_id'))->toBe($this->sectionA->id);
    expect($response->json('data.0.total_products'))->toBe(1);
    expect($response->json('data.0.products.0.product_name'))->toBe('Organic EEG Headset');
});

test('boosted products appear first in feedOrder with badge and campaign data', function () {
    $organic = IndustryProduct::create([
        'creator_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->sectionA->id,
        'category_id' => $this->categoryA1->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Regular Device',
        'slug' => 'regular-device',
        'description' => 'Regular device description.',
        'status' => 'active',
        'is_boosted' => false,
    ]);

    $boosted = IndustryProduct::create([
        'creator_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->sectionA->id,
        'category_id' => $this->categoryA1->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Featured Device',
        'slug' => 'featured-device',
        'description' => 'Featured device description.',
        'status' => 'active',
        'is_boosted' => true,
        'boost_expires_at' => now()->addDays(5),
    ]);

    $campaign = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $boosted->id,
        'boost_package_id' => $this->package->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'network_type' => 'psychology',
        'ad_type' => 'product',
        'placements' => ['marketplace', 'newsfeed'],
        'duration_days' => 7,
        'amount_paid' => 19.99,
        'price' => 19.99,
        'starts_at' => now(),
        'ends_at' => now()->addDays(5),
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user, 'api')->getJson('/api/industry/product-feed?network_type=psychology&industry_type=biotechnology');

    $response->assertOk();
    $products = $response->json('data.0.products');

    // First item must be the boosted product
    expect($products[0]['id'])->toBe($boosted->id)
        ->and($products[0]['is_boosted'])->toBeTrue()
        ->and($products[0]['badge'])->toBe('Sponsored')
        ->and($products[0]['ad_campaign_id'])->toBe($campaign->id);

    // Second item is organic
    expect($products[1]['id'])->toBe($organic->id)
        ->and($products[1]['is_boosted'])->toBeFalse();
});

test('view=all or flat=1 returns flat cursor-paginated list of products', function () {
    for ($i = 1; $i <= 5; $i++) {
        IndustryProduct::create([
            'creator_id' => $this->user->id,
            'industry_id' => $this->industry->id,
            'section_id' => $this->sectionA->id,
            'category_id' => $this->categoryA1->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'product_name' => "Product Batch {$i}",
            'slug' => "product-batch-{$i}",
            'description' => "Description for batch item {$i}",
            'status' => 'active',
        ]);
    }

    $response = $this->actingAs($this->user, 'api')->getJson('/api/industry/product-feed?network_type=psychology&industry_type=biotechnology&view=all&per_page=2');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'total_products',
            'data',
            'pagination' => [
                'limit',
                'per_page',
                'next_cursor',
                'prev_cursor',
                'has_more_pages',
                'total',
            ],
        ]);

    expect($response->json('data'))->toHaveCount(2)
        ->and($response->json('pagination.has_more_pages'))->toBeTrue()
        ->and($response->json('pagination.next_cursor'))->not->toBeNull();

    // Fetch next page using cursor
    $nextCursor = $response->json('pagination.next_cursor');
    $response2 = $this->actingAs($this->user, 'api')->getJson("/api/industry/product-feed?network_type=psychology&industry_type=biotechnology&view=all&per_page=2&cursor={$nextCursor}");

    $response2->assertOk();
    expect($response2->json('data'))->toHaveCount(2);
});

test('section_id filter returns products for that specific section with cursor pagination', function () {
    $sectionB = IndustrySections::create([
        'name' => 'Brain Scanners',
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
    ]);

    $categoryB = IndustryCategory::create([
        'section_id' => $sectionB->id,
        'category_name' => 'Scanners',
    ]);

    IndustryProduct::create([
        'creator_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->sectionA->id,
        'category_id' => $this->categoryA1->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Device in Section A',
        'slug' => 'device-in-section-a',
        'description' => 'Device description.',
        'status' => 'active',
    ]);

    IndustryProduct::create([
        'creator_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'section_id' => $sectionB->id,
        'category_id' => $categoryB->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Scanner in Section B',
        'slug' => 'scanner-in-section-b',
        'description' => 'Scanner description.',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user, 'api')->getJson("/api/industry/product-feed?network_type=psychology&industry_type=biotechnology&section_id={$this->sectionA->id}");

    $response->assertOk()
        ->assertJsonPath('section_id', $this->sectionA->id)
        ->assertJsonPath('section_name', 'Lab Devices');

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.product_name'))->toBe('Device in Section A');
});

test('search query filters products across name and description', function () {
    IndustryProduct::create([
        'creator_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->sectionA->id,
        'category_id' => $this->categoryA1->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Transcranial Magnetic Stimulator',
        'slug' => 'tms-stimulator',
        'description' => 'Powerful clinical TMS machine',
        'status' => 'active',
    ]);

    IndustryProduct::create([
        'creator_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->sectionA->id,
        'category_id' => $this->categoryA1->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Eye Tracking Glass',
        'slug' => 'eye-tracking-glass',
        'description' => 'High speed eye tracking sensors',
        'status' => 'active',
    ]);

    $response = $this->actingAs($this->user, 'api')->getJson('/api/industry/product-feed?network_type=psychology&industry_type=biotechnology&search=Magnetic');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.product_name'))->toBe('Transcranial Magnetic Stimulator');
});
