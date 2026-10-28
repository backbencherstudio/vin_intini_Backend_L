<?php

namespace Tests\Feature;

use App\Enums\IndustryJobPostStatus;
use App\Models\Industry;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RecruiterDashboardTest extends TestCase
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
            'name' => 'Acme Hospital',
            'slug' => 'acme-hospital-'.uniqid(),
            'industry_category_id' => $categoryId,
            'created_by' => $user->id,
        ]);

        return [$user, $industry];
    }

    public function test_unauthenticated_user_cannot_access_recruiter_dashboard(): void
    {
        $response = $this->getJson('/api/industry/recruiter-dashboard');

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

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/recruiter-dashboard');

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Your account is not associated with any company profile. Please create a company profile first.');
    }

    public function test_recruiter_dashboard_returns_complete_composite_data(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        // 1. Published Job
        $publishedJob = IndustryJobPost::create([
            'job_id' => 'JOB-101',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => 'Clinical Psychologist',
            'slug' => 'clinical-psychologist-job-101',
            'job_description' => 'Help patients overcome mental hurdles.',
            'work_mode' => 'remote',
            'employment_type' => 'full-time',
            'network_type' => 'psychology',
            'email' => 'jobs@acme.com',
            'status' => IndustryJobPostStatus::PUBLISHED->value,
            'views_count' => 10,
        ]);

        // 2. Archived Job
        IndustryJobPost::create([
            'job_id' => 'JOB-102',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => 'Neuro Researcher',
            'slug' => 'neuro-researcher-job-102',
            'job_description' => 'Research neuroscience topics.',
            'work_mode' => 'on_site',
            'employment_type' => 'contract',
            'network_type' => 'neuroscience',
            'email' => 'research@acme.com',
            'status' => IndustryJobPostStatus::ARCHIVE->value,
        ]);

        // 3. Rejected Job
        IndustryJobPost::create([
            'job_id' => 'JOB-103',
            'industry_id' => $industry->id,
            'created_by' => $user->id,
            'job_title' => 'Lab Assistant',
            'slug' => 'lab-assistant-job-103',
            'job_description' => 'Support lab experiments.',
            'work_mode' => 'hybrid',
            'employment_type' => 'part-time',
            'network_type' => 'psychology',
            'email' => 'lab@acme.com',
            'status' => IndustryJobPostStatus::REJECTED->value,
            'rejection_reason' => 'Incomplete information.',
        ]);

        // 4. Candidate application
        $applicant = User::factory()->create(['is_verified' => true]);
        IndustryJobApplication::create([
            'job_id' => $publishedJob->id,
            'applicant_id' => $applicant->id,
            'application_id' => 'APP-999',
            'full_name' => 'Pristia Candra',
            'email' => 'pristia@example.com',
            'status' => 'shortlisted',
            'resume_path' => 'resumes/resume.pdf',
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/recruiter-dashboard');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Recruiter dashboard data retrieved successfully.')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cards' => [
                        'active_positions' => ['total', 'growth', 'growth_count', 'is_positive'],
                        'total_applicants' => ['total', 'growth', 'growth_count', 'is_positive'],
                        'total_job_posts' => ['total'],
                        'total_archived_jobs' => ['total'],
                        'total_rejected_jobs' => ['total'],
                    ],
                    'recent_jobs' => [
                        '*' => ['id', 'job_id', 'job_title', 'status', 'badges'],
                    ],
                    'graph' => [
                        'title',
                        'subtitle',
                        'period',
                        'active',
                        'weekly' => [
                            '*' => ['label', 'total_applicants', 'job_views', 'active_positions'],
                        ],
                        'monthly' => [
                            '*' => ['label', 'total_applicants', 'job_views', 'active_positions'],
                        ],
                    ],
                    'activity_feed',
                    'recent_applicants' => [
                        '*' => [
                            'id',
                            'application_id',
                            'job_post_id',
                            'job_id',
                            'job_title',
                            'position',
                            'applicant_name',
                            'applicant_email',
                            'applicant_avatar',
                            'network',
                            'status',
                            'applied_on',
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');

        // Check Cards counts
        $this->assertSame(1, $data['cards']['active_positions']['total']);
        $this->assertSame(1, $data['cards']['total_applicants']['total']);
        $this->assertSame(3, $data['cards']['total_job_posts']['total']);
        $this->assertSame(1, $data['cards']['total_archived_jobs']['total']);
        $this->assertSame(1, $data['cards']['total_rejected_jobs']['total']);

        // Check Recent Jobs (Archived jobs are excluded)
        $this->assertCount(2, $data['recent_jobs']);
        $recentStatuses = array_column($data['recent_jobs'], 'status');
        $this->assertNotContains(IndustryJobPostStatus::ARCHIVE->value, $recentStatuses);

        // Check Graph (weekly has 7 days, monthly has 12 months)
        $this->assertArrayNotHasKey('applicants_chart', $data);
        $this->assertArrayNotHasKey('chartData', $data['graph']);
        $this->assertCount(7, $data['graph']['weekly']);
        $this->assertCount(12, $data['graph']['monthly']);

        // Check default active range is weekly
        $this->assertSame('weekly', $data['graph']['active']);
        $this->assertSame('weekly', $data['graph']['period']);
        $this->assertSame('Jobs Overview Graph', $data['graph']['title']);
        $this->assertSame('Applications Over Time', $data['graph']['subtitle']);

        // Check Recent Applicants
        $this->assertCount(1, $data['recent_applicants']);
        $this->assertSame('APP-999', $data['recent_applicants'][0]['application_id']);
        $this->assertSame($publishedJob->id, $data['recent_applicants'][0]['job_post_id']);
        $this->assertSame('#JOB-101', $data['recent_applicants'][0]['job_id']);
        $this->assertSame('Clinical Psychologist', $data['recent_applicants'][0]['job_title']);
        $this->assertArrayNotHasKey('title', $data['recent_applicants'][0]);
        $this->assertSame('Pristia Candra', $data['recent_applicants'][0]['applicant_name']);
        $this->assertSame('shortlisted', $data['recent_applicants'][0]['status']);
    }

    public function test_recruiter_dashboard_supports_chart_range_filter(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        // 1. Monthly range with ?period=monthly
        $monthlyResponse = $this->actingAs($user, 'api')->getJson('/api/industry/recruiter-dashboard?period=monthly');
        $monthlyResponse->assertOk();
        $monthlyData = $monthlyResponse->json('data');
        $this->assertSame('monthly', $monthlyData['graph']['active']);
        $this->assertSame('monthly', $monthlyData['graph']['period']);

        // 2. Monthly range with ?chart_range=monthly
        $chartRangeResponse = $this->actingAs($user, 'api')->getJson('/api/industry/recruiter-dashboard?chart_range=monthly');
        $chartRangeResponse->assertOk();
        $chartRangeData = $chartRangeResponse->json('data');
        $this->assertSame('monthly', $chartRangeData['graph']['active']);
    }
}
