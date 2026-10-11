<?php

namespace Tests\Feature;

use App\Models\Industry;
use App\Models\Publication;
use App\Models\PublicationLike;
use App\Models\PublicationView;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryPublicationsAnalyticsTest extends TestCase
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

        return [$user, $industry];
    }

    public function test_unauthenticated_user_cannot_access_publications_insights(): void
    {
        $response = $this->getJson('/api/industry/analytics/publications-insights');

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

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/publications-insights');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account is not associated with any company profile. Please create a company profile first.');
    }

    public function test_user_with_industry_gets_200_with_default_weekly_analytics(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/publications-insights');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Publications insights analytics retrieved successfully.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cards' => [
                        'total_publications' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'active_publications' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'publications_views' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'total_publications_like' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                    ],
                    'graph' => [
                        'title',
                        'subtitle',
                        'period',
                        'active',
                        'weekly' => [
                            '*' => ['label', 'publications_views', 'publications_likes', 'views', 'likes'],
                        ],
                        'monthly' => [
                            '*' => ['label', 'publications_views', 'publications_likes', 'views', 'likes'],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertSame('Publications Insights', $data['graph']['title']);
        $this->assertSame('weekly', $data['graph']['active']);
        $this->assertSame('weekly', $data['graph']['period']);

        // Assert weekly items (7 days)
        $this->assertCount(7, $data['graph']['weekly']);
        $this->assertArrayHasKey('label', $data['graph']['weekly'][0]);
        $this->assertArrayHasKey('publications_views', $data['graph']['weekly'][0]);
        $this->assertArrayHasKey('publications_likes', $data['graph']['weekly'][0]);

        // Assert monthly items (12 months)
        $this->assertCount(12, $data['graph']['monthly']);
        $this->assertArrayHasKey('label', $data['graph']['monthly'][0]);
        $this->assertArrayHasKey('publications_views', $data['graph']['monthly'][0]);
        $this->assertArrayHasKey('publications_likes', $data['graph']['monthly'][0]);
    }

    public function test_user_can_filter_graph_by_monthly(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/publications-insights?filter=monthly');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.graph.active', 'monthly');

        $data = $response->json('data');
        $this->assertSame('monthly', $data['graph']['period']);
        $this->assertCount(12, $data['graph']['monthly']);
        $this->assertEquals(
            ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            array_column($data['graph']['monthly'], 'label')
        );
    }

    public function test_publications_cards_and_graph_data_are_calculated_correctly(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();
        $viewer = User::factory()->create();

        // 1. Active Publication
        $pub1 = Publication::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'publication_type' => 'professional',
            'title' => 'Clinical Neuroscience Frontiers',
            'slug' => 'clinical-neuroscience-frontiers-'.uniqid(),
            'abstract' => 'Sample abstract for research.',
            'website_url' => 'https://example.com/pub1',
            'status' => 'active',
            'views_count' => 0,
            'likes_count' => 0,
        ]);

        // 2. Draft/Inactive Publication
        $pub2 = Publication::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'publication_type' => 'professional',
            'title' => 'Draft Study On Cognition',
            'slug' => 'draft-study-on-cognition-'.uniqid(),
            'abstract' => 'Draft abstract.',
            'website_url' => 'https://example.com/pub2',
            'status' => 'draft',
            'views_count' => 0,
            'likes_count' => 0,
        ]);

        // 3. Views and Likes on pub1
        PublicationView::create([
            'publication_id' => $pub1->id,
            'user_id' => $viewer->id,
            'created_at' => now(),
        ]);

        PublicationLike::create([
            'publication_id' => $pub1->id,
            'user_id' => $viewer->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/publications-insights');

        $response->assertOk();
        $data = $response->json('data');

        // Total publications: 2 (1 active + 1 draft)
        $this->assertSame(2, $data['cards']['total_publications']['total']);
        // Active publications: 1
        $this->assertSame(1, $data['cards']['active_publications']['total']);
        // Publications Views: 1
        $this->assertSame(1, $data['cards']['publications_views']['total']);
        // Publications Likes: 1
        $this->assertSame(1, $data['cards']['total_publications_like']['total']);

        // Weekly graph check
        $totalWeeklyViews = array_sum(array_column($data['graph']['weekly'], 'publications_views'));
        $totalWeeklyLikes = array_sum(array_column($data['graph']['weekly'], 'publications_likes'));
        $this->assertSame(1, $totalWeeklyViews);
        $this->assertSame(1, $totalWeeklyLikes);
    }

    public function test_cards_always_show_month_data_regardless_of_graph_filter(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();
        $viewer = User::factory()->create();

        $pub = Publication::create([
            'creator_id' => $user->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'publication_type' => 'professional',
            'title' => 'Test Publication',
            'slug' => 'test-publication-'.uniqid(),
            'abstract' => 'Abstract content.',
            'website_url' => 'https://example.com/pub',
            'status' => 'active',
            'views_count' => 50,
            'likes_count' => 15,
        ]);

        $resWeekly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/publications-insights?filter=weekly');
        $resWeekly->assertOk();
        $weeklyData = $resWeekly->json('data');

        $resMonthly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/publications-insights?filter=monthly');
        $resMonthly->assertOk();
        $monthlyData = $resMonthly->json('data');

        // Cards data is consistent across both
        $this->assertSame(1, $weeklyData['cards']['total_publications']['total']);
        $this->assertSame(1, $monthlyData['cards']['total_publications']['total']);
        $this->assertSame(1, $weeklyData['cards']['active_publications']['total']);
        $this->assertSame(1, $monthlyData['cards']['active_publications']['total']);
        $this->assertSame(50, $weeklyData['cards']['publications_views']['total']);
        $this->assertSame(50, $monthlyData['cards']['publications_views']['total']);
        $this->assertSame(15, $weeklyData['cards']['total_publications_like']['total']);
        $this->assertSame(15, $monthlyData['cards']['total_publications_like']['total']);

        // Graph periods differ
        $this->assertSame('weekly', $weeklyData['graph']['active']);
        $this->assertSame('monthly', $monthlyData['graph']['active']);
    }

    public function test_admin_can_view_specific_industry_publications_analytics(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $userRole = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api',
        ]);

        $adminUser = User::factory()->create(['is_verified' => true]);
        $adminUser->assignRole([$userRole, $adminRole]);
        UserProfile::create(['user_id' => $adminUser->id]);

        [$regularUser, $industry] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($adminUser, 'api')->getJson("/api/industry/analytics/publications-insights?industry_id={$industry->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Publications insights analytics retrieved successfully.');
    }
}
