<?php

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\AdImpression;
use App\Models\BoostPackage;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Advertising\FairPlayAdRotationService;
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
        'name' => 'NeuroTech Labs',
        'slug' => 'neurotech-labs',
        'industry' => 'biotechnology',
        'tagline' => 'Advanced Brain-Computer Interfaces',
    ]);

    $this->section = IndustrySections::create([
        'name' => 'Neuro Devices',
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
    ]);

    $this->category = IndustryCategory::create([
        'section_id' => $this->section->id,
        'category_name' => 'BCI Headsets',
    ]);

    $this->packageStarter = BoostPackage::create([
        'name' => '7-Day Starter Boost',
        'target_entity' => 'product',
        'duration_days' => 7,
        'price' => 19.99,
        'badge' => 'Sponsored',
        'priority_weight' => 1,
        'status' => 'active',
    ]);

    $this->packagePro = BoostPackage::create([
        'name' => '30-Day Pro Boost',
        'target_entity' => 'product',
        'duration_days' => 30,
        'price' => 69.99,
        'badge' => 'Top Reach',
        'priority_weight' => 3,
        'status' => 'active',
    ]);
});

function createNewsfeedProduct(array $overrides = []): IndustryProduct
{
    static $counter = 1;
    $counter++;

    return IndustryProduct::create(array_merge([
        'creator_id' => test()->user->id,
        'industry_id' => test()->industry->id,
        'section_id' => test()->section->id,
        'category_id' => test()->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'BCI Wireless Headset #'.$counter,
        'slug' => 'bci-wireless-headset-'.$counter,
        'description' => 'Breakthrough 64-channel EEG device for neuroscience researchers.',
        'price' => 2499.00,
        'status' => 'active',
        'is_boosted' => true,
        'boost_expires_at' => now()->addDays(7),
    ], $overrides));
}

test('newsfeed returns regular posts unmodified when no boosted ad campaigns exist', function () {
    // Create 6 public posts
    for ($i = 1; $i <= 6; $i++) {
        Post::create([
            'user_id' => $this->user->id,
            'description' => "Post #{$i} content",
            'visibility' => 'public',
        ]);
    }

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/newsfeed');

    $response->assertOk()
        ->assertJson(['success' => true]);

    $data = $response->json('data');
    expect($data)->toHaveCount(6);

    // Verify all posts have is_sponsored = false
    foreach ($data as $item) {
        expect($item['is_sponsored'])->toBeFalse();
    }
});

test('active boosted product ad is injected at slot 4 with sponsored badge and metrics tracked', function () {
    // Create 6 posts so feed qualifies for first ad slot (>= 4 posts)
    for ($i = 1; $i <= 6; $i++) {
        Post::create([
            'user_id' => $this->user->id,
            'description' => "Post #{$i} content",
            'visibility' => 'public',
        ]);
    }

    $product = createNewsfeedProduct([
        'product_name' => 'BCI Wireless Headset Pro',
        'slug' => 'bci-wireless-headset-pro',
    ]);

    $campaign = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $product->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->packageStarter->id,
        'ad_type' => 'product',
        'network_type' => 'both',
        'placements' => ['marketplace', 'newsfeed'],
        'amount_paid' => 19.99,
        'currency' => 'USD',
        'duration_days' => 7,
        'starts_at' => now(),
        'ends_at' => now()->addDays(7),
        'status' => AdCampaignStatus::ACTIVE,
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/newsfeed');

    $response->assertOk();
    $data = $response->json('data');

    // 6 regular posts + 1 injected sponsored card = 7 items
    expect($data)->toHaveCount(7);

    // Slot 4 (index 4 = 5th item in feed) must be the sponsored product card
    $injectedAd = $data[4];
    expect($injectedAd['is_sponsored'])->toBeTrue()
        ->and($injectedAd['ad_campaign_id'])->toBe($campaign->id)
        ->and($injectedAd['ad_type'])->toBe('product')
        ->and($injectedAd['title'])->toBe('BCI Wireless Headset Pro')
        ->and($injectedAd['cta_text'])->toBe('View Product')
        ->and($injectedAd['company']['name'])->toBe('NeuroTech Labs');

    // Verify impression recorded in ad_impressions table
    $this->assertDatabaseHas('ad_impressions', [
        'ad_campaign_id' => $campaign->id,
        'user_id' => $this->user->id,
        'placement' => 'newsfeed',
        'viewed_date' => today()->toDateString(),
    ]);

    // Verify total_impressions on campaign and impressions_count on product incremented
    expect($campaign->fresh()->total_impressions)->toBe(1)
        ->and($product->fresh()->impressions_count)->toBe(1);
});

test('session deduplication prevents same campaign from appearing twice in the same feed page', function () {
    // Create 15 posts (enough for 2 ad slots: slot 4 and slot 13)
    for ($i = 1; $i <= 15; $i++) {
        Post::create([
            'user_id' => $this->user->id,
            'description' => "Post #{$i} content",
            'visibility' => 'public',
        ]);
    }

    $product = createNewsfeedProduct([
        'product_name' => 'Single Active Product',
        'slug' => 'single-active-product',
    ]);

    $campaign = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $product->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->packageStarter->id,
        'ad_type' => 'product',
        'network_type' => 'both',
        'placements' => ['newsfeed'],
        'amount_paid' => 19.99,
        'currency' => 'USD',
        'duration_days' => 7,
        'starts_at' => now(),
        'ends_at' => now()->addDays(7),
        'status' => AdCampaignStatus::ACTIVE,
    ]);

    $response = $this->actingAs($this->user, 'api')
        ->getJson('/api/newsfeed?per_page=20');

    $response->assertOk();
    $data = $response->json('data');

    // Count how many times this campaign was injected
    $adOccurrences = collect($data)->filter(fn ($item) => ($item['is_sponsored'] ?? false) && $item['ad_campaign_id'] === $campaign->id);
    expect($adOccurrences)->toHaveCount(1); // Exactly 1, NOT duplicated!
});

test('fair play rotation prioritizes under-served campaigns over over-served campaigns', function () {
    $rotationService = app(FairPlayAdRotationService::class);

    $productA = createNewsfeedProduct([
        'product_name' => 'Over-served Product A',
        'slug' => 'product-a',
    ]);

    $productB = createNewsfeedProduct([
        'product_name' => 'Under-served Product B',
        'slug' => 'product-b',
    ]);

    // Campaign A has already received 800 impressions
    $campaignA = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $productA->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->packageStarter->id,
        'ad_type' => 'product',
        'network_type' => 'both',
        'placements' => ['newsfeed'],
        'amount_paid' => 19.99,
        'currency' => 'USD',
        'duration_days' => 7,
        'starts_at' => now(),
        'ends_at' => now()->addDays(7),
        'status' => AdCampaignStatus::ACTIVE,
        'total_impressions' => 800,
    ]);

    // Campaign B has only received 5 impressions
    $campaignB = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $productB->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->packageStarter->id,
        'ad_type' => 'product',
        'network_type' => 'both',
        'placements' => ['newsfeed'],
        'amount_paid' => 19.99,
        'currency' => 'USD',
        'duration_days' => 7,
        'starts_at' => now(),
        'ends_at' => now()->addDays(7),
        'status' => AdCampaignStatus::ACTIVE,
        'total_impressions' => 5,
    ]);

    // Rotation should pick Campaign B because its delivered impression ratio is far lower
    $chosenCampaign = $rotationService->resolveAdForFeed('product', 'both', 'newsfeed');
    expect($chosenCampaign->id)->toBe($campaignB->id);
});

test('frequency capping prevents showing the same ad to a user who reached max daily impressions', function () {
    $rotationService = app(FairPlayAdRotationService::class);

    $product = createNewsfeedProduct([
        'product_name' => 'High Frequency Product',
        'slug' => 'high-frequency-product',
    ]);

    $campaign = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $product->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->packageStarter->id,
        'ad_type' => 'product',
        'network_type' => 'both',
        'placements' => ['newsfeed'],
        'amount_paid' => 19.99,
        'currency' => 'USD',
        'duration_days' => 7,
        'starts_at' => now(),
        'ends_at' => now()->addDays(7),
        'status' => AdCampaignStatus::ACTIVE,
        'total_impressions' => 5,
    ]);

    // Log 5 impressions today for this user (reaches max_daily_impressions_per_user = 5)
    for ($i = 0; $i < 5; $i++) {
        AdImpression::create([
            'ad_campaign_id' => $campaign->id,
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $this->user->id,
            'placement' => 'newsfeed',
            'viewed_date' => today(),
        ]);
    }

    // Because user already saw this campaign 5 times today, it should be excluded to prevent ad fatigue
    $resolved = $rotationService->resolveAdForFeed('product', 'both', 'newsfeed', [], $this->user->id);
    expect($resolved)->toBeNull();

    // But for a different user, it should still be served
    $differentUser = User::factory()->create(['is_verified' => true]);
    $resolvedForDifferentUser = $rotationService->resolveAdForFeed('product', 'both', 'newsfeed', [], $differentUser->id);
    expect($resolvedForDifferentUser?->id)->toBe($campaign->id);
});

test('paused and expired campaigns are never served in newsfeed', function () {
    $rotationService = app(FairPlayAdRotationService::class);

    $product = createNewsfeedProduct([
        'product_name' => 'Inactive Campaign Product',
        'slug' => 'inactive-campaign-product',
    ]);

    // 1. Paused campaign
    $pausedCampaign = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $product->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->packageStarter->id,
        'ad_type' => 'product',
        'network_type' => 'both',
        'placements' => ['newsfeed'],
        'amount_paid' => 19.99,
        'currency' => 'USD',
        'duration_days' => 7,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->addDays(5),
        'status' => AdCampaignStatus::PAUSED,
        'paused_at' => now()->subDays(1),
    ]);

    expect($rotationService->resolveAdForFeed('product', 'both', 'newsfeed'))->toBeNull();

    // 2. Expired campaign
    $pausedCampaign->update([
        'status' => AdCampaignStatus::ACTIVE,
        'paused_at' => null,
        'ends_at' => now()->subMinute(), // In the past!
    ]);

    expect($rotationService->resolveAdForFeed('product', 'both', 'newsfeed'))->toBeNull();
});
