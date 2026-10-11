<?php

namespace Tests\Feature;

use App\Models\Industry;
use App\Models\IndustryFollow;
use App\Models\IndustryProfileView;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryProfileInsightsAnalyticsTest extends TestCase
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
            'name' => 'Biotech',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('industry_categories')->insertGetId([
            'section_id' => $sectionId,
            'category_name' => 'Tech',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $industry = Industry::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp-'.uniqid(),
            'industry_category_id' => $categoryId,
            'created_by' => $user->id,
            'views_count' => 0,
        ]);

        return [$user, $industry];
    }

    public function test_unauthenticated_user_cannot_access_profile_insights_analytics(): void
    {
        $response = $this->getJson('/api/industry/analytics/profile-insights');

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

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account is not associated with any company profile. Please create a company profile first.');
    }

    public function test_user_with_industry_gets_200_with_default_monthly_analytics(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profile insights analytics retrieved successfully.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cards' => [
                        'new_followers' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'total_followers' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'profile_visits' => ['total', 'growth_percentage', 'growth', 'is_positive'],
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
                            'monthly' => ['labels', 'series', 'total_new_followers', 'total_followers', 'total_profile_visits'],
                            'yearly' => ['labels', 'series', 'total_new_followers', 'total_followers', 'total_profile_visits'],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertSame('Profile Insights', $data['graph']['title']);
        $this->assertSame('monthly', $data['graph']['active']);
        $this->assertCount(Carbon::now()->daysInMonth, $data['graph']['labels']);
        $this->assertCount(3, $data['graph']['series']);
        $this->assertSame('new_followers', $data['graph']['series'][0]['key']);
        $this->assertSame('total_followers', $data['graph']['series'][1]['key']);
        $this->assertSame('profile_visits', $data['graph']['series'][2]['key']);
    }

    public function test_user_can_filter_graph_by_yearly(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights?filter=yearly');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.graph.active', 'yearly');

        $data = $response->json('data');
        $this->assertSame('yearly', $data['graph']['period']);
        $this->assertCount(12, $data['graph']['labels']);
        $this->assertEquals(
            ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            $data['graph']['labels']
        );
    }

    public function test_followers_and_visits_are_counted_in_cards_and_graph(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $followerUser1 = User::factory()->create();
        $followerUser2 = User::factory()->create();

        // Follower added this month
        $follow1 = IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $followerUser1->id,
        ]);

        // Follower added last year
        $follow2 = IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $followerUser2->id,
        ]);
        DB::table('industry_follows')->where('id', $follow2->id)->update([
            'created_at' => now()->subYear(),
            'updated_at' => now()->subYear(),
        ]);

        // Profile visit today
        IndustryProfileView::create([
            'industry_id' => $industry->id,
            'user_id' => $followerUser1->id,
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights?filter=monthly');

        $response->assertOk();
        $data = $response->json('data');

        // Cards check
        $this->assertSame(1, $data['cards']['new_followers']['total']);
        $this->assertSame(2, $data['cards']['total_followers']['total']);
        $this->assertSame(1, $data['cards']['profile_visits']['total']);

        // Monthly graph check
        $totalMonthlyNewFollowers = array_sum($data['graph']['series'][0]['data']);
        $totalMonthlyVisits = array_sum($data['graph']['series'][2]['data']);
        $this->assertSame(1, $totalMonthlyNewFollowers);
        $this->assertSame(1, $totalMonthlyVisits);

        // Yearly graph check via ranges
        $yearlySeries = $data['graph']['ranges']['yearly']['series'];
        $totalYearlyNewFollowers = array_sum($yearlySeries[0]['data']);
        $totalYearlyVisits = array_sum($yearlySeries[2]['data']);
        $this->assertSame(1, $totalYearlyNewFollowers);
        $this->assertSame(1, $totalYearlyVisits);
    }

    public function test_cards_always_show_lifetime_and_month_data_regardless_of_graph_filter(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $follower1 = User::factory()->create();
        $follower2 = User::factory()->create();

        $follow1 = IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $follower1->id,
        ]);
        DB::table('industry_follows')->where('id', $follow1->id)->update([
            'created_at' => now()->subYears(2),
            'updated_at' => now()->subYears(2),
        ]);

        $follow2 = IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $follower2->id,
        ]);

        $industry->update(['views_count' => 150]);

        $resMonthly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights?filter=monthly');
        $resMonthly->assertOk();
        $monthlyData = $resMonthly->json('data');

        $resYearly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights?filter=yearly');
        $resYearly->assertOk();
        $yearlyData = $resYearly->json('data');

        // Total followers: 2 across both
        $this->assertSame(2, $monthlyData['cards']['total_followers']['total']);
        $this->assertSame(2, $yearlyData['cards']['total_followers']['total']);

        // New followers this month: 1 across both
        $this->assertSame(1, $monthlyData['cards']['new_followers']['total']);
        $this->assertSame(1, $yearlyData['cards']['new_followers']['total']);

        // Profile visits: 150 across both
        $this->assertSame(150, $monthlyData['cards']['profile_visits']['total']);
        $this->assertSame(150, $yearlyData['cards']['profile_visits']['total']);

        // Graph periods differ
        $this->assertSame('monthly', $monthlyData['graph']['active']);
        $this->assertSame('yearly', $yearlyData['graph']['active']);
    }

    public function test_admin_can_view_specific_industry_analytics(): void
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

        $response = $this->actingAs($adminUser, 'api')->getJson("/api/industry/analytics/profile-insights?industry_id={$industry->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Profile insights analytics retrieved successfully.');
    }
}
