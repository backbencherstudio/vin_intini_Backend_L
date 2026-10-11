<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Group;
use App\Models\Industry;
use App\Models\IndustryFollow;
use App\Models\IndustryProfileView;
use App\Models\Post;
use App\Models\PostIndustry;
use App\Models\PostLike;
use App\Models\User;
use App\Models\UserProfile;
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

    public function test_user_with_industry_gets_200_with_default_weekly_analytics(): void
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
                        'post_engagement' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'profile_visits' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                    ],
                    'graph' => [
                        'title',
                        'subtitle',
                        'period',
                        'active',
                        'weekly' => [
                            '*' => ['label', 'new_followers', 'post_engagement', 'profile_visits'],
                        ],
                        'monthly' => [
                            '*' => ['label', 'new_followers', 'post_engagement', 'profile_visits'],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertSame('Profile Insights', $data['graph']['title']);
        $this->assertSame('weekly', $data['graph']['active']);
        $this->assertSame('weekly', $data['graph']['period']);

        // Assert weekly items (7 days)
        $this->assertCount(7, $data['graph']['weekly']);
        $this->assertArrayHasKey('label', $data['graph']['weekly'][0]);
        $this->assertArrayHasKey('new_followers', $data['graph']['weekly'][0]);
        $this->assertArrayHasKey('post_engagement', $data['graph']['weekly'][0]);
        $this->assertArrayHasKey('profile_visits', $data['graph']['weekly'][0]);

        // Assert monthly items (12 months)
        $this->assertCount(12, $data['graph']['monthly']);
        $this->assertArrayHasKey('label', $data['graph']['monthly'][0]);
        $this->assertArrayHasKey('new_followers', $data['graph']['monthly'][0]);
        $this->assertArrayHasKey('post_engagement', $data['graph']['monthly'][0]);
        $this->assertArrayHasKey('profile_visits', $data['graph']['monthly'][0]);
    }

    public function test_user_can_filter_graph_by_monthly(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights?filter=monthly');

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

    public function test_followers_engagement_and_visits_are_counted_in_cards_and_graph(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $followerUser1 = User::factory()->create();

        // 1. Follower added this month
        IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $followerUser1->id,
        ]);

        // 2. Company Post with a Like and a Comment this month
        $post = Post::create([
            'user_id' => $user->id,
            'description' => 'Test industry post',
            'visibility' => 'public',
        ]);
        PostIndustry::create([
            'post_id' => $post->id,
            'industry_id' => $industry->id,
        ]);

        PostLike::create([
            'post_id' => $post->id,
            'user_id' => $followerUser1->id,
        ]);

        Comment::create([
            'post_id' => $post->id,
            'user_id' => $followerUser1->id,
            'comment' => 'Awesome update!',
        ]);

        // 3. Profile visit today
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

        // Cards check: 1 new follower, 2 post engagements (1 like + 1 comment), 1 profile visit
        $this->assertSame(1, $data['cards']['new_followers']['total']);
        $this->assertSame(2, $data['cards']['post_engagement']['total']);
        $this->assertSame(1, $data['cards']['profile_visits']['total']);

        // Monthly graph check
        $totalMonthlyNewFollowers = array_sum(array_column($data['graph']['monthly'], 'new_followers'));
        $totalMonthlyEngagement = array_sum(array_column($data['graph']['monthly'], 'post_engagement'));
        $totalMonthlyVisits = array_sum(array_column($data['graph']['monthly'], 'profile_visits'));
        $this->assertSame(1, $totalMonthlyNewFollowers);
        $this->assertSame(2, $totalMonthlyEngagement);
        $this->assertSame(1, $totalMonthlyVisits);
    }

    public function test_cards_always_show_month_data_regardless_of_graph_filter(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $follower1 = User::factory()->create();

        IndustryFollow::create([
            'industry_id' => $industry->id,
            'user_id' => $follower1->id,
        ]);

        $post = Post::create([
            'user_id' => $user->id,
            'description' => 'Test post',
            'visibility' => 'public',
        ]);
        PostIndustry::create([
            'post_id' => $post->id,
            'industry_id' => $industry->id,
        ]);
        PostLike::create([
            'post_id' => $post->id,
            'user_id' => $follower1->id,
        ]);

        $industry->update(['views_count' => 150]);

        $resWeekly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights?filter=weekly');
        $resWeekly->assertOk();
        $weeklyData = $resWeekly->json('data');

        $resMonthly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights?filter=monthly');
        $resMonthly->assertOk();
        $monthlyData = $resMonthly->json('data');

        // Post engagement: 1 across both
        $this->assertSame(1, $weeklyData['cards']['post_engagement']['total']);
        $this->assertSame(1, $monthlyData['cards']['post_engagement']['total']);

        // New followers this month: 1 across both
        $this->assertSame(1, $weeklyData['cards']['new_followers']['total']);
        $this->assertSame(1, $monthlyData['cards']['new_followers']['total']);

        // Profile visits: 150 across both
        $this->assertSame(150, $weeklyData['cards']['profile_visits']['total']);
        $this->assertSame(150, $monthlyData['cards']['profile_visits']['total']);

        // Graph periods differ
        $this->assertSame('weekly', $weeklyData['graph']['active']);
        $this->assertSame('monthly', $monthlyData['graph']['active']);
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

    public function test_group_posts_and_profile_posts_engagement_are_not_counted(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();
        $otherUser = User::factory()->create();

        // 1. Valid Industry Post (published on this industry page)
        $industryPost = Post::create([
            'user_id' => $user->id,
            'description' => 'Valid company post',
            'visibility' => 'public',
        ]);
        PostIndustry::create([
            'post_id' => $industryPost->id,
            'industry_id' => $industry->id,
        ]);
        // 1 like and 1 comment on this industry post
        PostLike::create(['post_id' => $industryPost->id, 'user_id' => $otherUser->id]);
        Comment::create(['post_id' => $industryPost->id, 'user_id' => $otherUser->id, 'comment' => 'Great product!']);

        // 2. Personal Profile Post (user post without any industry link)
        $profilePost = Post::create([
            'user_id' => $user->id,
            'description' => 'Personal profile status',
            'visibility' => 'public',
        ]);
        // 2 likes and 1 comment on this profile post -> MUST NOT BE COUNTED
        PostLike::create(['post_id' => $profilePost->id, 'user_id' => $otherUser->id]);
        PostLike::create(['post_id' => $profilePost->id, 'user_id' => $user->id]);
        Comment::create(['post_id' => $profilePost->id, 'user_id' => $otherUser->id, 'comment' => 'Nice picture']);

        // 3. Group Post (post associated with a group, visibility groups)
        $group = Group::create([
            'name' => 'Test Community Group',
            'slug' => 'test-community-group-'.uniqid(),
            'description' => 'Test community group description',
            'creator_id' => $user->id,
            'type' => 'public',
            'discoverability' => 'listed',
        ]);
        $groupPost = Post::create([
            'user_id' => $user->id,
            'description' => 'Discussion in group',
            'visibility' => 'groups',
        ]);
        DB::table('post_groups')->insert([
            'post_id' => $groupPost->id,
            'group_id' => $group->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // 1 like and 1 comment on group post -> MUST NOT BE COUNTED
        PostLike::create(['post_id' => $groupPost->id, 'user_id' => $otherUser->id]);
        Comment::create(['post_id' => $groupPost->id, 'user_id' => $otherUser->id, 'comment' => 'Group comment']);

        // 4. Another Company's Industry Post
        $otherIndustry = Industry::create([
            'name' => 'Other Corp',
            'slug' => 'other-corp-'.uniqid(),
            'created_by' => $otherUser->id,
        ]);
        $otherIndustryPost = Post::create([
            'user_id' => $otherUser->id,
            'description' => 'Other company post',
            'visibility' => 'public',
        ]);
        PostIndustry::create([
            'post_id' => $otherIndustryPost->id,
            'industry_id' => $otherIndustry->id,
        ]);
        PostLike::create(['post_id' => $otherIndustryPost->id, 'user_id' => $user->id]);
        Comment::create(['post_id' => $otherIndustryPost->id, 'user_id' => $user->id, 'comment' => 'Other company comment']);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/profile-insights');

        $response->assertOk();
        $data = $response->json('data');

        // Total post engagement in cards should be exactly 2 (1 like + 1 comment from the industry post ONLY)
        $this->assertSame(2, $data['cards']['post_engagement']['total']);

        // Total weekly graph engagement should also be exactly 2
        $totalWeeklyEngagement = array_sum(array_column($data['graph']['weekly'], 'post_engagement'));
        $this->assertSame(2, $totalWeeklyEngagement);

        // Total monthly graph engagement should also be exactly 2
        $totalMonthlyEngagement = array_sum(array_column($data['graph']['monthly'], 'post_engagement'));
        $this->assertSame(2, $totalMonthlyEngagement);
    }
}
