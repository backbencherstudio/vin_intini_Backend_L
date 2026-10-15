<?php

use App\Contracts\BoostableInterface;
use App\Enums\AdCampaignStatus;
use App\Enums\BoostTargetEntity;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\BoostPackage;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\User;
use Database\Seeders\BoostPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('boost packages can be seeded and queried correctly', function () {
    $this->seed(BoostPackageSeeder::class);

    expect(BoostPackage::count())->toBe(3);

    $starter = BoostPackage::where('name', '7-Day Starter Boost')->first();
    expect($starter)->not->toBeNull()
        ->and($starter->duration_days)->toBe(7)
        ->and((float) $starter->price)->toBe(19.99)
        ->and($starter->target_entity)->toBe(BoostTargetEntity::PRODUCT)
        ->and($starter->isActive())->toBeTrue()
        ->and($starter->features)->toBeArray();
});

test('industry product implements boostable interface and trait', function () {
    $user = User::factory()->create();
    $industry = Industry::create([
        'name' => 'BioTech Corp',
        'slug' => 'biotech-corp',
        'created_by' => $user->id,
    ]);
    $section = IndustrySections::create([
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'name' => 'Diagnostics',
    ]);
    $category = IndustryCategory::create([
        'section_id' => $section->id,
        'category_name' => 'EEG Devices',
    ]);

    $product = IndustryProduct::create([
        'creator_id' => $user->id,
        'industry_id' => $industry->id,
        'section_id' => $section->id,
        'category_id' => $category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Wireless EEG Cap',
        'slug' => 'wireless-eeg-cap',
        'description' => 'Medical grade EEG cap for research.',
        'status' => 'active',
        'is_new_release' => true,
        'new_release_expires_at' => now()->addHours(24),
    ]);

    expect($product)->toBeInstanceOf(BoostableInterface::class)
        ->and($product->getAdTitle())->toBe('Wireless EEG Cap')
        ->and($product->getAdType())->toBe('product')
        ->and($product->getAdNetworkType())->toBe('psychology')
        ->and($product->isBoosted())->toBeFalse();

    expect(IndustryProduct::newRelease()->count())->toBe(1);
});

test('ad campaign supports pause and resume with clock freeze mechanism', function () {
    $user = User::factory()->create();
    $industry = Industry::create([
        'name' => 'Neuro Labs',
        'slug' => 'neuro-labs',
        'created_by' => $user->id,
    ]);
    $section = IndustrySections::create([
        'network_type' => 'neuroscience',
        'industry_type' => 'biotechnology',
        'name' => 'Imaging',
    ]);
    $category = IndustryCategory::create([
        'section_id' => $section->id,
        'category_name' => 'fMRI Accessories',
    ]);

    $product = IndustryProduct::create([
        'creator_id' => $user->id,
        'industry_id' => $industry->id,
        'section_id' => $section->id,
        'category_id' => $category->id,
        'network_type' => 'neuroscience',
        'industry_type' => 'biotechnology',
        'product_name' => 'High-Res Optical Sensor',
        'slug' => 'high-res-optical-sensor',
        'description' => 'High-resolution sensor for advanced imaging.',
        'status' => 'active',
        'is_boosted' => true,
        'boost_expires_at' => now()->addDays(7),
        'boost_score' => 100,
    ]);

    $package = BoostPackage::create([
        'name' => '7-Day Test',
        'target_entity' => 'product',
        'duration_days' => 7,
        'price' => 19.99,
        'priority_weight' => 1,
        'status' => 'active',
    ]);

    $campaign = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $product->id,
        'user_id' => $user->id,
        'industry_id' => $industry->id,
        'boost_package_id' => $package->id,
        'ad_type' => 'product',
        'network_type' => 'neuroscience',
        'amount_paid' => 19.99,
        'duration_days' => 7,
        'starts_at' => now(),
        'ends_at' => now()->addDays(7),
        'status' => AdCampaignStatus::ACTIVE,
    ]);

    expect($campaign->isActive())->toBeTrue()
        ->and($product->isBoosted())->toBeTrue();

    // 1. Test Pause
    $paused = $campaign->pause();
    expect($paused)->toBeTrue();
    expect($campaign->fresh()->isPaused())->toBeTrue();
    expect($product->fresh()->is_boosted)->toBeFalse();

    // 2. Simulate 2 hours of paused time
    $originalEndsAt = $campaign->ends_at->copy();
    $campaign->update(['paused_at' => now()->subHours(2)]);

    // 3. Test Resume
    $resumed = $campaign->resume();
    expect($resumed)->toBeTrue();

    $freshCampaign = $campaign->fresh();
    expect($freshCampaign->isActive())->toBeTrue()
        ->and($freshCampaign->paused_at)->toBeNull()
        ->and($freshCampaign->ends_at->greaterThan($originalEndsAt))->toBeTrue()
        ->and($product->fresh()->is_boosted)->toBeTrue();
});

test('impressions and clicks can be logged for an ad campaign', function () {
    $user = User::factory()->create();
    $industry = Industry::create([
        'name' => 'Pharma Corp',
        'slug' => 'pharma-corp',
        'created_by' => $user->id,
    ]);
    $section = IndustrySections::create([
        'network_type' => 'psychology',
        'industry_type' => 'psychotropics',
        'name' => 'Therapeutics',
    ]);
    $category = IndustryCategory::create([
        'section_id' => $section->id,
        'category_name' => 'Supplements',
    ]);

    $product = IndustryProduct::create([
        'creator_id' => $user->id,
        'industry_id' => $industry->id,
        'section_id' => $section->id,
        'category_id' => $category->id,
        'network_type' => 'psychology',
        'industry_type' => 'psychotropics',
        'product_name' => 'Cognitive Mood Capsule',
        'slug' => 'cognitive-mood-capsule',
        'description' => 'Daily cognitive support capsules.',
        'status' => 'active',
    ]);

    $campaign = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $product->id,
        'user_id' => $user->id,
        'industry_id' => $industry->id,
        'ad_type' => 'product',
        'network_type' => 'psychology',
        'amount_paid' => 15.00,
        'duration_days' => 5,
        'starts_at' => now(),
        'ends_at' => now()->addDays(5),
        'status' => AdCampaignStatus::ACTIVE,
    ]);

    AdImpression::create([
        'ad_campaign_id' => $campaign->id,
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $product->id,
        'user_id' => $user->id,
        'placement' => 'newsfeed',
        'ip_address' => '127.0.0.1',
        'viewed_date' => now()->toDateString(),
    ]);

    AdClick::create([
        'ad_campaign_id' => $campaign->id,
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $product->id,
        'user_id' => $user->id,
        'placement' => 'newsfeed',
        'ip_address' => '127.0.0.1',
    ]);

    expect($campaign->impressions()->count())->toBe(1)
        ->and($campaign->clicks()->count())->toBe(1);
});
