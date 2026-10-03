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
                    'applicants_chart' => [
                        'active',
                        'active_range',
                        'labels',
                        'series',
                        'ranges' => [
                            'weekly' => ['labels', 'series', 'total'],
                            'monthly' => ['labels', 'series', 'total'],
                            'yearly' => ['labels', 'series', 'total'],
                        ],
                    ],
                    'activity_feed',
                    'recent_applicants' => [
                        '*' => ['id', 'job_id', 'applicant_name', 'applicant_email', 'position', 'status', 'applied_on'],
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

        // Check Recent Jobs (3 jobs created)
        $this->assertCount(3, $data['recent_jobs']);

        // Check Applicants Chart
        // Weekly: 7 days, Monthly: current month 1 to 31 date-wise, Yearly: Jan to Dec 12 months
        $daysInCurrentMonth = (int) now()->daysInMonth;
        $this->assertCount(7, $data['applicants_chart']['ranges']['weekly']['labels']);
        $this->assertCount($daysInCurrentMonth, $data['applicants_chart']['ranges']['monthly']['labels']);
        $this->assertCount(12, $data['applicants_chart']['ranges']['yearly']['labels']);

        // Check default active range is weekly
        $this->assertSame('weekly', $data['applicants_chart']['active']);
        $this->assertSame('weekly', $data['applicants_chart']['active_range']);
        $this->assertCount(7, $data['applicants_chart']['labels']);

        // Check Recent Applicants
        $this->assertCount(1, $data['recent_applicants']);
        $this->assertSame('Pristia Candra', $data['recent_applicants'][0]['applicant_name']);
        $this->assertSame('shortlisted', $data['recent_applicants'][0]['status']);
    }

    public function test_recruiter_dashboard_supports_chart_range_filter(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        // 1. Yearly range
        $yearlyResponse = $this->actingAs($user, 'api')->getJson('/api/industry/recruiter-dashboard?chart_range=yearly');
        $yearlyResponse->assertOk();
        $yearlyData = $yearlyResponse->json('data');
        $this->assertSame('yearly', $yearlyData['applicants_chart']['active_range']);
        $this->assertCount(12, $yearlyData['applicants_chart']['labels']);

        // 2. Monthly range
        $daysInCurrentMonth = (int) now()->daysInMonth;
        $monthlyResponse = $this->actingAs($user, 'api')->getJson('/api/industry/recruiter-dashboard?chart_range=monthly');
        $monthlyResponse->assertOk();
        $monthlyData = $monthlyResponse->json('data');
        $this->assertSame('monthly', $monthlyData['applicants_chart']['active_range']);
        $this->assertCount($daysInCurrentMonth, $monthlyData['applicants_chart']['labels']);
    }
}
