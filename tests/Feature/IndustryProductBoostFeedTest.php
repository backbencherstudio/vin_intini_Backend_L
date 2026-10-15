<?php

use App\Enums\PlanType;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

    $this->creator = User::factory()->create(['is_verified' => true]);
    $this->creator->assignRole('user');
    UserProfile::create(['user_id' => $this->creator->id]);

    $this->industry = Industry::create([
        'created_by' => $this->creator->id,
        'name' => 'BioPac Systems',
        'slug' => 'biopac-systems',
        'industry' => 'biotechnology',
    ]);

    $plan = Plan::create([
        'name' => 'Pro Industry Plan',
        'plan_type' => PlanType::INDUSTRY->value,
        'billing_rate' => 99.00,
        'billing_cycle' => 'monthly',
        'features' => ['advertisements'],
        'status' => 'active',
    ]);

    Subscription::create([
        'user_id' => $this->creator->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'current_period_start' => now()->subDay(),
        'current_period_end' => now()->addMonth(),
    ]);

    $this->section = IndustrySections::create([
        'name' => 'Neuroscientific Equipment',
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
    ]);

    $this->category = IndustryCategory::create([
        'section_id' => $this->section->id,
        'category_name' => 'EEG Devices',
    ]);
});

test('creating a product automatically grants 24h new release perk based on config', function () {
    config(['boosting.new_release_hours' => 24]);

    $payload = [
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'product_name' => 'NextGen Wireless EEG Headset',
        'description' => 'A cutting-edge 32-channel wireless EEG cap for research.',
        'product_url' => 'https://biopac.example.com/eeg',
        'poc_name' => 'Dr. Jane Smith',
        'poc_email' => 'jane@example.com',
        'poc_phone' => '+15551234567',
        'information_confirmed' => true,
        'status' => 'active',
        'image' => UploadedFile::fake()->image('headset.jpg'),
        'tags' => ['neuro', 'eeg', 'diagnostics'],
    ];

    $response = $this->actingAs($this->creator, 'api')
        ->postJson('/api/industry/products/create', $payload)
        ->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'product_name' => 'NextGen Wireless EEG Headset',
                'is_new_release' => true,
                'is_boosted' => false,
                'badge' => 'New Release',
            ],
        ]);

    $product = IndustryProduct::where('product_name', 'NextGen Wireless EEG Headset')->first();
    expect($product)->not->toBeNull()
        ->and($product->is_new_release)->toBeTrue()
        ->and((int) round(abs(now()->diffInHours($product->new_release_expires_at))))->toEqual(24);
});

test('marketplace feed places boosted products first, new releases second, organic third', function () {
    // 1. Organic Product (Standard)
    $organic = IndustryProduct::create([
        'creator_id' => $this->creator->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Standard Sensor Cable',
        'slug' => 'standard-sensor-cable',
        'description' => 'Regular replacement cable.',
        'status' => 'active',
        'is_boosted' => false,
        'is_new_release' => false,
        'created_at' => now()->subDays(5),
    ]);

    // 2. Free New Release Product
    $newRelease = IndustryProduct::create([
        'creator_id' => $this->creator->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Brand New EEG Amplifier',
        'slug' => 'brand-new-eeg-amplifier',
        'description' => 'Newly launched amplifier.',
        'status' => 'active',
        'is_boosted' => false,
        'is_new_release' => true,
        'new_release_expires_at' => now()->addHours(24),
        'created_at' => now()->subHour(),
    ]);

    // 3. Paid Boosted Product
    $boosted = IndustryProduct::create([
        'creator_id' => $this->creator->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Featured Pro Brain Scanner',
        'slug' => 'featured-pro-brain-scanner',
        'description' => 'Top tier research scanner.',
        'status' => 'active',
        'is_boosted' => true,
        'boosted_at' => now()->subDay(),
        'boost_expires_at' => now()->addDays(7),
        'boost_score' => 100,
        'created_at' => now()->subDays(10),
    ]);

    // Query flat / section products feed
    $response = $this->actingAs($this->creator, 'api')
        ->getJson("/api/industry/product-feed?network_type=psychology&industry_type=biotechnology&section_id={$this->section->id}&flat=1")
        ->assertStatus(200);

    $items = $response->json('data');
    expect(count($items))->toBe(3);

    // Assert exact ranking hierarchy:
    // 1st: Boosted product
    expect($items[0]['id'])->toBe($boosted->id)
        ->and($items[0]['badge'])->toBe('Sponsored')
        ->and($items[0]['is_boosted'])->toBeTrue();

    // 2nd: New release product
    expect($items[1]['id'])->toBe($newRelease->id)
        ->and($items[1]['badge'])->toBe('New Release')
        ->and($items[1]['is_new_release'])->toBeTrue();

    // 3rd: Organic product
    expect($items[2]['id'])->toBe($organic->id)
        ->and($items[2]['badge'])->toBeNull()
        ->and($items[2]['is_boosted'])->toBeFalse()
        ->and($items[2]['is_new_release'])->toBeFalse();
});

test('section-wise feed window query also respects boost and new release ranking', function () {
    // 1. Organic
    $organic = IndustryProduct::create([
        'creator_id' => $this->creator->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Standard Item',
        'slug' => 'standard-item',
        'description' => 'Regular item.',
        'status' => 'active',
        'is_boosted' => false,
        'is_new_release' => false,
    ]);

    // 2. New Release
    $newRelease = IndustryProduct::create([
        'creator_id' => $this->creator->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'New Release Item',
        'slug' => 'new-release-item',
        'description' => 'New item.',
        'status' => 'active',
        'is_boosted' => false,
        'is_new_release' => true,
        'new_release_expires_at' => now()->addHours(24),
    ]);

    // 3. Boosted
    $boosted = IndustryProduct::create([
        'creator_id' => $this->creator->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Boosted Item',
        'slug' => 'boosted-item',
        'description' => 'Boosted item.',
        'status' => 'active',
        'is_boosted' => true,
        'boost_expires_at' => now()->addDays(5),
        'boost_score' => 50,
    ]);

    $response = $this->actingAs($this->creator, 'api')
        ->getJson('/api/industry/product-feed?network_type=psychology&industry_type=biotechnology')
        ->assertStatus(200);

    $sectionData = $response->json('data.0.products');
    expect(count($sectionData))->toBe(3)
        ->and($sectionData[0]['id'])->toBe($boosted->id)
        ->and($sectionData[1]['id'])->toBe($newRelease->id)
        ->and($sectionData[2]['id'])->toBe($organic->id);
});

test('expired boost automatically drops down ranking', function () {
    // 1. Expired Boosted product (boost expired 1 hour ago)
    $expiredBoost = IndustryProduct::create([
        'creator_id' => $this->creator->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Expired Boost Product',
        'slug' => 'expired-boost-product',
        'description' => 'Boost expired product.',
        'status' => 'active',
        'is_boosted' => true,
        'boost_expires_at' => now()->subHour(),
        'created_at' => now()->subDays(10),
    ]);

    // 2. Active New Release product
    $activeNewRelease = IndustryProduct::create([
        'creator_id' => $this->creator->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Active New Release Product',
        'slug' => 'active-new-release-product',
        'description' => 'Active new release.',
        'status' => 'active',
        'is_new_release' => true,
        'new_release_expires_at' => now()->addHours(20),
        'created_at' => now()->subHour(),
    ]);

    // In the feed, the Active New Release should rank higher than the Expired Boost
    $response = $this->actingAs($this->creator, 'api')
        ->getJson("/api/industry/product-feed?network_type=psychology&industry_type=biotechnology&section_id={$this->section->id}&flat=1")
        ->assertStatus(200);

    $items = $response->json('data');
    expect($items[0]['id'])->toBe($activeNewRelease->id)
        ->and($items[0]['badge'])->toBe('New Release')
        ->and($items[1]['id'])->toBe($expiredBoost->id)
        ->and($items[1]['badge'])->toBeNull()
        ->and($items[1]['is_boosted'])->toBeFalse();
});
