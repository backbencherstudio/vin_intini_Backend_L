<?php

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
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

    $this->viewer = User::factory()->create(['is_verified' => true]);
    $this->viewer->assignRole('user');
    UserProfile::create([
        'user_id' => $this->viewer->id,
        'privacy_profile_activity' => 'everyone',
    ]);

    $this->industry = Industry::create([
        'created_by' => $this->user->id,
        'name' => 'Ad Tracking Co',
        'slug' => 'ad-tracking-co',
        'industry' => 'biotechnology',
    ]);

    $this->section = IndustrySections::create([
        'name' => 'Tracking Devices',
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
    ]);

    $this->category = IndustryCategory::create([
        'section_id' => $this->section->id,
        'category_name' => 'Sensors',
    ]);

    $this->package = BoostPackage::create([
        'name' => '7-Day Starter Boost',
        'target_entity' => 'product',
        'duration_days' => 7,
        'price' => 19.99,
        'status' => 'active',
    ]);

    $this->product = IndustryProduct::create([
        'creator_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'section_id' => $this->section->id,
        'category_id' => $this->category->id,
        'network_type' => 'psychology',
        'industry_type' => 'biotechnology',
        'product_name' => 'Smart Bio-Sensor',
        'slug' => 'smart-bio-sensor',
        'description' => 'A cutting-edge smart bio sensor for research.',
        'product_url' => 'https://example.com/products/smart-sensor',
        'status' => 'active',
        'is_boosted' => true,
        'boost_expires_at' => now()->addDays(7),
    ]);

    $this->campaign = AdCampaign::create([
        'boostable_type' => IndustryProduct::class,
        'boostable_id' => $this->product->id,
        'user_id' => $this->user->id,
        'industry_id' => $this->industry->id,
        'boost_package_id' => $this->package->id,
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
});

test('client can batch report verified viewable impressions', function () {
    $response = $this->actingAs($this->viewer, 'api')
        ->postJson('/api/advertisements/impressions', [
            'campaign_ids' => [$this->campaign->id],
            'placement' => 'newsfeed',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'recorded_count' => 1,
        ]);

    $this->assertDatabaseHas('ad_impressions', [
        'ad_campaign_id' => $this->campaign->id,
        'user_id' => $this->viewer->id,
        'placement' => 'newsfeed',
        'viewed_date' => today()->toDateString(),
    ]);

    expect($this->campaign->fresh()->total_impressions)->toBe(1)
        ->and($this->product->fresh()->impressions_count)->toBe(1);
});

test('rapid repeated impressions within debounce window are throttled to prevent spam', function () {
    // First impression: recorded
    $firstResponse = $this->actingAs($this->viewer, 'api')
        ->postJson('/api/advertisements/impressions', [
            'campaign_ids' => [$this->campaign->id],
            'placement' => 'industry_product_feed',
        ]);

    $firstResponse->assertOk()
        ->assertJson([
            'success' => true,
            'recorded_count' => 1,
            'debounced_count' => 0,
        ]);

    // Second impression immediately (rapid reload): debounced, does not increment total_impressions
    $secondResponse = $this->actingAs($this->viewer, 'api')
        ->postJson('/api/advertisements/impressions', [
            'campaign_ids' => [$this->campaign->id],
            'placement' => 'industry_product_feed',
        ]);

    $secondResponse->assertOk()
        ->assertJson([
            'success' => true,
            'recorded_count' => 0,
            'debounced_count' => 1,
        ]);

    // DB must still have only 1 impression record
    expect(AdImpression::where('ad_campaign_id', $this->campaign->id)->count())->toBe(1)
        ->and($this->campaign->fresh()->total_impressions)->toBe(1)
        ->and($this->product->fresh()->impressions_count)->toBe(1);
});

test('client can record ad clicks and receive product destination url', function () {
    $response = $this->actingAs($this->viewer, 'api')
        ->postJson("/api/advertisements/{$this->campaign->id}/click", [
            'placement' => 'newsfeed',
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Click recorded successfully.',
            'data' => [
                'ad_campaign_id' => $this->campaign->id,
                'total_clicks' => 1,
                'destination_url' => 'https://example.com/products/smart-sensor',
                'clicks_count' => 1,
                'views_count' => 1,
            ],
        ]);

    $this->assertDatabaseHas('ad_clicks', [
        'ad_campaign_id' => $this->campaign->id,
        'user_id' => $this->viewer->id,
        'placement' => 'newsfeed',
    ]);

    expect($this->campaign->fresh()->total_clicks)->toBe(1)
        ->and($this->product->fresh()->clicks_count)->toBe(1)
        ->and($this->product->fresh()->views_count)->toBe(1)
        ->and($this->product->fresh()->unique_views_count)->toBe(1);
});

test('click recording returns 404 for non-existent campaign', function () {
    $response = $this->postJson('/api/advertisements/999999/click');
    $response->assertNotFound();
});

test('rapid repeated ad clicks within debounce window are throttled to prevent spam', function () {
    // First click: recorded
    $firstResponse = $this->actingAs($this->viewer, 'api')
        ->postJson("/api/advertisements/{$this->campaign->id}/click", [
            'placement' => 'newsfeed',
        ]);

    $firstResponse->assertOk()
        ->assertJson([
            'success' => true,
            'debounced' => false,
        ]);

    // Second click immediately: debounced, does not increment total_clicks
    $secondResponse = $this->actingAs($this->viewer, 'api')
        ->postJson("/api/advertisements/{$this->campaign->id}/click", [
            'placement' => 'newsfeed',
        ]);

    $secondResponse->assertOk()
        ->assertJson([
            'success' => true,
            'debounced' => true,
            'data' => [
                'total_clicks' => 1,
                'clicks_count' => 1,
            ],
        ]);

    // DB must still have only 1 click record
    expect(AdClick::where('ad_campaign_id', $this->campaign->id)->count())->toBe(1)
        ->and($this->campaign->fresh()->total_clicks)->toBe(1)
        ->and($this->product->fresh()->clicks_count)->toBe(1);
});

test('industry creator or campaign owner cannot generate self impressions or clicks', function () {
    // Attempting impression as owner/creator:
    $impressionResponse = $this->actingAs($this->user, 'api')
        ->postJson('/api/advertisements/impressions', [
            'campaign_ids' => [$this->campaign->id],
            'placement' => 'industry_product_feed',
        ]);

    $impressionResponse->assertOk()
        ->assertJson([
            'success' => true,
            'recorded_count' => 0,
            'debounced_count' => 1,
        ]);

    expect($this->campaign->fresh()->total_impressions)->toBe(0)
        ->and($this->product->fresh()->impressions_count)->toBe(0);

    // Attempting click as owner/creator:
    $clickResponse = $this->actingAs($this->user, 'api')
        ->postJson("/api/advertisements/{$this->campaign->id}/click", [
            'placement' => 'industry_product_feed',
        ]);

    $clickResponse->assertOk()
        ->assertJson([
            'success' => true,
            'debounced' => true,
        ]);

    expect($this->campaign->fresh()->total_clicks)->toBe(0)
        ->and($this->product->fresh()->clicks_count)->toBe(0);
});
