<?php

namespace Tests\Feature;

use App\Models\Industry;
use App\Models\IndustryProduct;
use App\Models\IndustryProductLike;
use App\Models\IndustryProductView;
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
                        'total_products' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'active_products' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'product_views' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'total_likes' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                    ],
                    'top_performing_products' => [
                        '*' => [
                            'id',
                            'product_id',
                            'product_name',
                            'views_count',
                            'likes_count',
                        ],
                    ],
                    'graph' => [
                        'title',
                        'subtitle',
                        'period',
                        'active',
                        'labels',
                        'series' => [
                            '*' => ['name', 'key', 'data'],
                        ],
                        'ranges' => [
                            'weekly' => ['labels', 'series', 'total_views', 'total_likes'],
                            'monthly' => ['labels', 'series', 'total_views', 'total_likes'],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('period', $data);
        $this->assertSame('weekly', $data['graph']['period']);
        $this->assertSame(2, $data['cards']['total_products']['total']);
        $this->assertSame(1, $data['cards']['active_products']['total']);
        $this->assertSame(2300, $data['cards']['product_views']['total']);
        $this->assertSame(370, $data['cards']['total_likes']['total']);

        // Check top performing products order (Brain Scanner has 1500 views, Bio Sensor has 800 views)
        $this->assertCount(2, $data['top_performing_products']);
        $this->assertSame('Brain Scanner 3000', $data['top_performing_products'][0]['product_name']);
        $this->assertSame(1500, $data['top_performing_products'][0]['views_count']);
        $this->assertSame(250, $data['top_performing_products'][0]['likes_count']);
        $this->assertSame('Bio Sensor Pro', $data['top_performing_products'][1]['product_name']);
        $this->assertSame(800, $data['top_performing_products'][1]['views_count']);
        $this->assertSame(120, $data['top_performing_products'][1]['likes_count']);

        // Check default weekly graph labels (7 days)
        $this->assertSame('weekly', $data['graph']['active']);
        $this->assertCount(7, $data['graph']['labels']);
        $this->assertCount(2, $data['graph']['series']);
        $this->assertSame('Views', $data['graph']['series'][0]['name']);
        $this->assertSame('Like', $data['graph']['series'][1]['name']);
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
        $this->assertCount(12, $data['graph']['labels']); // 12 months of current year (Jan to Dec)
        $this->assertCount(12, $data['graph']['series'][0]['data']);
        $this->assertCount(12, $data['graph']['series'][1]['data']);
    }

    public function test_logged_views_and_likes_are_counted_in_graph(): void
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

        // Record a view and a like for today
        IndustryProductView::create([
            'industry_product_id' => $product->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        IndustryProductLike::create([
            'industry_product_id' => $product->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/advertisements?filter=weekly');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertSame(1, $data['cards']['product_views']['total']);
        $this->assertSame(1, $data['cards']['total_likes']['total']);

        // In weekly graph, sum of views and likes should be 1
        $totalWeeklyViews = array_sum($data['graph']['series'][0]['data']);
        $totalWeeklyLikes = array_sum($data['graph']['series'][1]['data']);
        $this->assertSame(1, $totalWeeklyViews);
        $this->assertSame(1, $totalWeeklyLikes);
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
        // Total products: 2 (lifetime)
        $this->assertSame(2, $weeklyData['cards']['total_products']['total']);
        $this->assertSame(2, $monthlyData['cards']['total_products']['total']);

        // Active products: 1 (currently active)
        $this->assertSame(1, $weeklyData['cards']['active_products']['total']);
        $this->assertSame(1, $monthlyData['cards']['active_products']['total']);

        // Product views: 700 (lifetime sum: 500 + 200)
        $this->assertSame(700, $weeklyData['cards']['product_views']['total']);
        $this->assertSame(700, $monthlyData['cards']['product_views']['total']);

        // Total likes: 150 (lifetime sum: 100 + 50)
        $this->assertSame(150, $weeklyData['cards']['total_likes']['total']);
        $this->assertSame(150, $monthlyData['cards']['total_likes']['total']);

        // Meanwhile, only the graph adapts to the filter:
        $this->assertSame('weekly', $weeklyData['graph']['active']);
        $this->assertCount(7, $weeklyData['graph']['labels']);

        $this->assertSame('monthly', $monthlyData['graph']['active']);
        $this->assertCount(12, $monthlyData['graph']['labels']);
    }
}
