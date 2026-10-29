<?php

namespace Tests\Feature;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\AdClick;
use App\Models\AdImpression;
use App\Models\Industry;
use App\Models\IndustryProduct;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryAdvertisementAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function createTestUserWithIndustry(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
        ]);

        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
        ]);

        $sectionId = DB::table('industry_sections')->insertGetId([
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'name' => 'Biotech Section',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('industry_categories')->insertGetId([
            'section_id' => $sectionId,
            'category_name' => 'Equipment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $industry = Industry::create([
            'name' => 'Biopac Systems',
            'slug' => 'biopac-systems-'.uniqid(),
            'industry_category_id' => $categoryId,
            'created_by' => $user->id,
        ]);

        return [$user, $industry, $sectionId, $categoryId];
    }

    public function test_unauthenticated_user_cannot_access_advertisement_analytics(): void
    {
        $response = $this->getJson('/api/industry/analytics/advertisements');

        $response->assertStatus(401);
    }

    public function test_user_without_industry_receives_422_error(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'is_verified' => true,
        ]);
        $user->assignRole($role);
        UserProfile::create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/advertisements');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account is not associated with any company profile. Please create a company profile first.');
    }

    public function test_user_with_industry_gets_200_with_default_analytics(): void
    {
        [$user, $industry, $sectionId, $categoryId] = $this->createTestUserWithIndustry();

        // Create sample products
        $product1 = IndustryProduct::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $sectionId,
            'category_id' => $categoryId,
            'product_name' => 'Brain Scanner 3000',
            'slug' => 'brain-scanner-3000-'.uniqid(),
            'description' => 'Advanced neuroimaging device',
            'status' => 'active',
            'views_count' => 1500,
            'likes_count' => 250,
        ]);

        $product2 = IndustryProduct::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $sectionId,
            'category_id' => $categoryId,
            'product_name' => 'Bio Sensor Pro',
            'slug' => 'bio-sensor-pro-'.uniqid(),
            'description' => 'Physiological monitoring sensor',
            'status' => 'inactive',
            'views_count' => 800,
            'likes_count' => 120,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/advertisements');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Advertisement analytics retrieved successfully.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cards' => [
                        'total_spend' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'total_impressions' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'total_clicks' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'avg_ctr' => ['total', 'formatted', 'growth_percentage', 'growth', 'is_positive'],
                        'active_campaigns' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                    ],
                    'graph' => [
                        'title',
                        'subtitle',
                        'period',
                        'active',
                        'weekly' => [
                            '*' => ['label', 'impressions', 'clicks', 'ctr'],
                        ],
                        'monthly' => [
                            '*' => ['label', 'impressions', 'clicks', 'ctr'],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('period', $data);
        $this->assertArrayNotHasKey('top_performing_products', $data);
        $this->assertSame('weekly', $data['graph']['period']);
        $this->assertArrayHasKey('total_spend', $data['cards']);
        $this->assertArrayHasKey('total_impressions', $data['cards']);
        $this->assertArrayHasKey('total_clicks', $data['cards']);
        $this->assertArrayHasKey('avg_ctr', $data['cards']);
        $this->assertArrayHasKey('active_campaigns', $data['cards']);
        $this->assertArrayNotHasKey('total_products', $data['cards']);
        $this->assertArrayNotHasKey('product_views', $data['cards']);

        // Check default weekly graph (7 days)
        $this->assertSame('weekly', $data['graph']['active']);
        $this->assertCount(7, $data['graph']['weekly']);
        $this->assertArrayHasKey('label', $data['graph']['weekly'][0]);
        $this->assertArrayHasKey('impressions', $data['graph']['weekly'][0]);
        $this->assertArrayHasKey('clicks', $data['graph']['weekly'][0]);
        $this->assertArrayNotHasKey('views', $data['graph']['weekly'][0]);
        $this->assertArrayNotHasKey('likes', $data['graph']['weekly'][0]);
        $this->assertCount(12, $data['graph']['monthly']);
        $this->assertArrayHasKey('label', $data['graph']['monthly'][0]);
        $this->assertArrayHasKey('impressions', $data['graph']['monthly'][0]);
        $this->assertArrayHasKey('clicks', $data['graph']['monthly'][0]);
        $this->assertArrayNotHasKey('views', $data['graph']['monthly'][0]);
        $this->assertArrayNotHasKey('likes', $data['graph']['monthly'][0]);
    }

    public function test_user_can_filter_graph_by_monthly(): void
    {
        [$user, $industry, $sectionId, $categoryId] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/advertisements?filter=monthly');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertArrayNotHasKey('period', $data);
        $this->assertSame('monthly', $data['graph']['period']);
        $this->assertSame('monthly', $data['graph']['active']);
        $this->assertCount(12, $data['graph']['monthly']);
        $this->assertArrayHasKey('label', $data['graph']['monthly'][0]);
        $this->assertArrayHasKey('impressions', $data['graph']['monthly'][0]);
        $this->assertArrayHasKey('clicks', $data['graph']['monthly'][0]);
        $this->assertArrayNotHasKey('views', $data['graph']['monthly'][0]);
        $this->assertArrayNotHasKey('likes', $data['graph']['monthly'][0]);
        $this->assertCount(7, $data['graph']['weekly']);
    }

    public function test_logged_impressions_and_clicks_are_counted_in_graph(): void
    {
        [$user, $industry, $sectionId, $categoryId] = $this->createTestUserWithIndustry();

        $product = IndustryProduct::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $sectionId,
            'category_id' => $categoryId,
            'product_name' => 'Lab Equipment A',
            'slug' => 'lab-equipment-a-'.uniqid(),
            'description' => 'Test device',
            'status' => 'active',
            'views_count' => 0,
            'likes_count' => 0,
        ]);

        $campaign = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'industry_id' => $industry->id,
            'ad_type' => 'product',
            'network_type' => 'psychology',
            'placements' => ['newsfeed'],
            'amount_paid' => 50.00,
            'currency' => 'USD',
            'duration_days' => 7,
            'status' => AdCampaignStatus::ACTIVE,
            'total_impressions' => 1,
            'total_clicks' => 1,
        ]);

        // Record an impression and a click for today
        AdImpression::create([
            'ad_campaign_id' => $campaign->id,
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'placement' => 'newsfeed',
            'ip_address' => '127.0.0.1',
            'viewed_date' => now()->toDateString(),
            'created_at' => now(),
        ]);

        AdClick::create([
            'ad_campaign_id' => $campaign->id,
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'placement' => 'newsfeed',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/advertisements?filter=weekly');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertArrayHasKey('total_spend', $data['cards']);

        // In weekly graph, sum of impressions and clicks should be 1
        $totalWeeklyImpressions = array_sum(array_column($data['graph']['weekly'], 'impressions'));
        $totalWeeklyClicks = array_sum(array_column($data['graph']['weekly'], 'clicks'));
        $this->assertSame(1, $totalWeeklyImpressions);
        $this->assertSame(1, $totalWeeklyClicks);
    }

    public function test_cards_always_show_lifetime_data_regardless_of_graph_filter(): void
    {
        [$user, $industry, $sectionId, $categoryId] = $this->createTestUserWithIndustry();

        IndustryProduct::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $sectionId,
            'category_id' => $categoryId,
            'product_name' => 'Old Product',
            'slug' => 'old-product-'.uniqid(),
            'description' => 'Product created 2 years ago',
            'status' => 'active',
            'views_count' => 500,
            'likes_count' => 100,
            'created_at' => now()->subYears(2),
        ]);

        IndustryProduct::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $sectionId,
            'category_id' => $categoryId,
            'product_name' => 'Recent Product',
            'slug' => 'recent-product-'.uniqid(),
            'description' => 'Product created today',
            'status' => 'inactive',
            'views_count' => 200,
            'likes_count' => 50,
            'created_at' => now(),
        ]);

        // 1. Default (weekly) request
        $resWeekly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/advertisements');
        $resWeekly->assertOk();
        $weeklyData = $resWeekly->json('data');

        // 2. Monthly request
        $resMonthly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/advertisements?filter=monthly');
        $resMonthly->assertOk();
        $monthlyData = $resMonthly->json('data');

        // In both requests, cards MUST show identical lifetime totals:
        $this->assertSame($weeklyData['cards']['total_spend']['total'], $monthlyData['cards']['total_spend']['total']);
        $this->assertSame($weeklyData['cards']['total_impressions']['total'], $monthlyData['cards']['total_impressions']['total']);
        $this->assertSame($weeklyData['cards']['total_clicks']['total'], $monthlyData['cards']['total_clicks']['total']);
        $this->assertSame($weeklyData['cards']['avg_ctr']['total'], $monthlyData['cards']['avg_ctr']['total']);
        $this->assertSame($weeklyData['cards']['active_campaigns']['total'], $monthlyData['cards']['active_campaigns']['total']);

        // Meanwhile, only the graph adapts to the filter:
        $this->assertSame('weekly', $weeklyData['graph']['active']);
        $this->assertCount(7, $weeklyData['graph']['weekly']);

        $this->assertSame('monthly', $monthlyData['graph']['active']);
        $this->assertCount(12, $monthlyData['graph']['monthly']);
    }

    public function test_advertisement_analytics_returns_real_ad_metrics_placements_and_campaigns(): void
    {
        [$user, $industry, $sectionId, $categoryId] = $this->createTestUserWithIndustry();

        $viewer = User::factory()->create(['is_verified' => true]);

        $product = IndustryProduct::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $sectionId,
            'category_id' => $categoryId,
            'product_name' => 'Advanced EEG Cap',
            'slug' => 'advanced-eeg-cap-'.uniqid(),
            'description' => 'Precision EEG recording cap',
            'status' => 'active',
            'is_boosted' => true,
            'boost_expires_at' => now()->addDays(10),
            'views_count' => 10,
            'likes_count' => 2,
        ]);

        $campaign = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'industry_id' => $industry->id,
            'ad_type' => 'product',
            'network_type' => 'psychology',
            'placements' => ['newsfeed', 'industry_product_feed'],
            'amount_paid' => 120.00,
            'currency' => 'USD',
            'duration_days' => 14,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(12),
            'status' => AdCampaignStatus::ACTIVE,
            'total_impressions' => 100,
            'total_clicks' => 5,
        ]);

        // Log impressions for newsfeed and product feed
        AdImpression::create([
            'ad_campaign_id' => $campaign->id,
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $viewer->id,
            'placement' => 'newsfeed',
            'ip_address' => '127.0.0.1',
            'viewed_date' => now()->toDateString(),
            'created_at' => now(),
        ]);

        AdImpression::create([
            'ad_campaign_id' => $campaign->id,
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $viewer->id,
            'placement' => 'industry_product_feed',
            'ip_address' => '127.0.0.1',
            'viewed_date' => now()->toDateString(),
            'created_at' => now(),
        ]);

        // Log click
        AdClick::create([
            'ad_campaign_id' => $campaign->id,
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $viewer->id,
            'placement' => 'newsfeed',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/advertisements');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cards' => [
                        'total_spend',
                        'total_impressions',
                        'total_clicks',
                        'avg_ctr',
                        'active_campaigns',
                    ],
                    'placements' => [
                        'newsfeed' => ['placement', 'label', 'impressions', 'clicks', 'ctr', 'percentage_share'],
                        'industry_product_feed' => ['placement', 'label', 'impressions', 'clicks', 'ctr', 'percentage_share'],
                    ],
                    'top_performing_campaigns' => [
                        '*' => [
                            'id',
                            'product_id',
                            'product_name',
                            'impressions',
                            'clicks',
                        ],
                    ],
                    'graph' => [
                        'title',
                        'subtitle',
                        'period',
                        'active',
                        'weekly' => [
                            '*' => ['label', 'impressions', 'clicks', 'ctr'],
                        ],
                        'monthly' => [
                            '*' => ['label', 'impressions', 'clicks', 'ctr'],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(120.00, $data['cards']['total_spend']['total']);
        $this->assertEquals(100, $data['cards']['total_impressions']['total']);
        $this->assertEquals(5, $data['cards']['total_clicks']['total']);
        $this->assertEquals(5.0, $data['cards']['avg_ctr']['total']);
        $this->assertEquals(1, $data['cards']['active_campaigns']['total']);
        $this->assertArrayNotHasKey('avg_cpc', $data['cards']);

        // Check placement breakdown
        $this->assertSame(1, $data['placements']['newsfeed']['impressions']);
        $this->assertSame(1, $data['placements']['newsfeed']['clicks']);
        $this->assertEquals(100.0, $data['placements']['newsfeed']['ctr']);
        $this->assertSame(1, $data['placements']['industry_product_feed']['impressions']);

        // Check top performing campaigns
        $this->assertCount(1, $data['top_performing_campaigns']);
        $this->assertSame($campaign->id, $data['top_performing_campaigns'][0]['id']);
        $this->assertSame('Advanced EEG Cap', $data['top_performing_campaigns'][0]['product_name']);
        $this->assertSame(100, $data['top_performing_campaigns'][0]['impressions']);
        $this->assertSame(5, $data['top_performing_campaigns'][0]['clicks']);
    }
}
