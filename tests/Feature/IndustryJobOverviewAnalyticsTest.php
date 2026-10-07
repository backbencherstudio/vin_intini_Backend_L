<?php

namespace Tests\Feature;

use App\Enums\IndustryJobPostStatus;
use App\Models\Industry;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostView;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryJobOverviewAnalyticsTest extends TestCase
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
        ]);

        return [$user, $industry];
    }

    public function test_unauthenticated_user_cannot_access_job_overview_analytics(): void
    {
        $response = $this->getJson('/api/industry/analytics/job-overviews');

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

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/job-overviews');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account is not associated with any company profile. Please create a company profile first.');
    }

    public function test_user_with_industry_gets_200_with_default_weekly_analytics(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        IndustryJobPost::create([
            'job_id' => 'JOB-101',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => 'Software Engineer',
            'slug' => 'software-engineer-job-101',
            'job_description' => 'Great role',
            'work_mode' => 'remote',
            'employment_type' => 'full-time',
            'email' => 'jobs@acme.com',
            'status' => IndustryJobPostStatus::PUBLISHED->value,
            'views_count' => 125,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/job-overviews');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Job overview analytics retrieved successfully.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cards' => [
                        'job_views' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'total_applicants' => ['total', 'growth_percentage', 'growth', 'is_positive'],
                        'active_positions' => ['total', 'growth_percentage', 'growth', 'is_positive'],
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
                            'weekly' => ['labels', 'series', 'total_applicants', 'total_views', 'total_positions'],
                            'monthly' => ['labels', 'series', 'total_applicants', 'total_views', 'total_positions'],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertArrayNotHasKey('period', $data);
        $this->assertSame('weekly', $data['graph']['period']);
        $this->assertSame('weekly', $data['graph']['active']);
        $this->assertSame(1, $data['cards']['active_positions']['total']);
        $this->assertSame(125, $data['cards']['job_views']['total']);
        $this->assertSame(0, $data['cards']['total_applicants']['total']);
        $this->assertCount(7, $data['graph']['labels']);
        $this->assertCount(3, $data['graph']['series']);
        $this->assertSame('Total Applicant', $data['graph']['series'][0]['name']);
        $this->assertSame('Job Views', $data['graph']['series'][1]['name']);
        $this->assertSame('Active Positions', $data['graph']['series'][2]['name']);
    }

    public function test_user_can_filter_graph_by_monthly(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/job-overviews?filter=monthly');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertArrayNotHasKey('period', $data);
        $this->assertSame('monthly', $data['graph']['period']);
        $this->assertSame('monthly', $data['graph']['active']);
        $this->assertCount(12, $data['graph']['labels']);
        $this->assertCount(12, $data['graph']['series'][0]['data']);
        $this->assertCount(12, $data['graph']['series'][1]['data']);
        $this->assertCount(12, $data['graph']['series'][2]['data']);
    }

    public function test_logged_views_and_applications_are_counted_in_graph(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $job = IndustryJobPost::create([
            'job_id' => 'JOB-202',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => 'Backend Engineer',
            'slug' => 'backend-engineer-job-202',
            'job_description' => 'Great role',
            'work_mode' => 'remote',
            'employment_type' => 'full-time',
            'email' => 'jobs2@acme.com',
            'status' => IndustryJobPostStatus::PUBLISHED->value,
            'views_count' => 0,
            'created_at' => now(),
        ]);

        IndustryJobPostView::create([
            'industry_job_post_id' => $job->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        IndustryJobApplication::create([
            'application_id' => 'APP-101',
            'job_id' => $job->id,
            'applicant_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'resume_path' => 'resumes/resume.pdf',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/job-overviews?filter=weekly');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertSame(1, $data['cards']['job_views']['total']);
        $this->assertSame(1, $data['cards']['total_applicants']['total']);
        $this->assertSame(1, $data['cards']['active_positions']['total']);

        // In weekly graph, sums should be 1
        $totalWeeklyApplicants = array_sum($data['graph']['series'][0]['data']);
        $totalWeeklyViews = array_sum($data['graph']['series'][1]['data']);
        $totalWeeklyPositions = array_sum($data['graph']['series'][2]['data']);
        $this->assertSame(1, $totalWeeklyApplicants);
        $this->assertSame(1, $totalWeeklyViews);
        $this->assertSame(1, $totalWeeklyPositions);
    }

    public function test_cards_always_show_lifetime_data_regardless_of_graph_filter(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        IndustryJobPost::create([
            'job_id' => 'JOB-OLD',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => 'Old Post',
            'slug' => 'old-post-'.uniqid(),
            'job_description' => 'Old role',
            'work_mode' => 'remote',
            'employment_type' => 'full-time',
            'email' => 'old@acme.com',
            'status' => IndustryJobPostStatus::PUBLISHED->value,
            'views_count' => 50,
            'created_at' => now()->subYears(2),
        ]);

        IndustryJobPost::create([
            'job_id' => 'JOB-NEW',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => 'New Post',
            'slug' => 'new-post-'.uniqid(),
            'job_description' => 'New role',
            'work_mode' => 'remote',
            'employment_type' => 'full-time',
            'email' => 'new@acme.com',
            'status' => IndustryJobPostStatus::EXPIRED->value,
            'views_count' => 20,
            'created_at' => now(),
        ]);

        $resWeekly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/job-overviews');
        $resWeekly->assertOk();
        $weeklyData = $resWeekly->json('data');

        $resMonthly = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/job-overviews?filter=monthly');
        $resMonthly->assertOk();
        $monthlyData = $resMonthly->json('data');

        // Total views: 70
        $this->assertSame(70, $weeklyData['cards']['job_views']['total']);
        $this->assertSame(70, $monthlyData['cards']['job_views']['total']);

        // Active positions: 1 (JOB-OLD is published, JOB-NEW is expired)
        $this->assertSame(1, $weeklyData['cards']['active_positions']['total']);
        $this->assertSame(1, $monthlyData['cards']['active_positions']['total']);

        // Meanwhile, only graph adapts
        $this->assertSame('weekly', $weeklyData['graph']['active']);
        $this->assertCount(7, $weeklyData['graph']['labels']);

        $this->assertSame('monthly', $monthlyData['graph']['active']);
        $this->assertCount(12, $monthlyData['graph']['labels']);
    }
}
