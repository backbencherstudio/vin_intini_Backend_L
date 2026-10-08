<?php

namespace Tests\Feature;

use App\Enums\IndustryJobPostStatus;
use App\Enums\JobApplicationStatus;
use App\Models\Industry;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MyJobApplicationsTest extends TestCase
{
    use RefreshDatabase;

    private function createApplicant(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'is_verified' => true,
        ]);
        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
        ]);

        return $user;
    }

    private function createJobPost(string $title = 'Software Engineer'): IndustryJobPost
    {
        $creator = User::factory()->create(['is_verified' => true]);

        $industry = Industry::create([
            'created_by' => $creator->id,
            'name' => 'Tech Corp',
            'slug' => 'tech-corp-'.uniqid(),
            'status' => 'approved',
        ]);

        return IndustryJobPost::create([
            'job_id' => 'JOB-'.rand(100, 999),
            'industry_id' => $industry->id,
            'created_by' => $creator->id,
            'job_title' => $title,
            'slug' => 'job-'.uniqid(),
            'job_description' => 'Great role.',
            'work_mode' => 'remote',
            'employment_type' => 'full-time',
            'email' => 'jobs@techcorp.com',
            'status' => IndustryJobPostStatus::PUBLISHED->value,
        ]);
    }

    public function test_my_applications_returns_all_status_counts(): void
    {
        $user = $this->createApplicant();
        $job1 = $this->createJobPost('Frontend Dev');
        $job2 = $this->createJobPost('Backend Dev');
        $job3 = $this->createJobPost('UI Designer');

        // Create applications with different statuses
        IndustryJobApplication::create([
            'application_id' => '100001',
            'job_id' => $job1->id,
            'applicant_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'resume_path' => 'resumes/test.pdf',
            'status' => JobApplicationStatus::PENDING->value,
        ]);

        IndustryJobApplication::create([
            'application_id' => '100002',
            'job_id' => $job2->id,
            'applicant_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'resume_path' => 'resumes/test.pdf',
            'status' => JobApplicationStatus::INTERVIEWED->value,
        ]);

        IndustryJobApplication::create([
            'application_id' => '100003',
            'job_id' => $job3->id,
            'applicant_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'resume_path' => 'resumes/test.pdf',
            'status' => JobApplicationStatus::REJECTED->value,
        ]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/my-job-applications');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 3)
            ->assertJsonPath('status_counts.all', 3)
            ->assertJsonPath('status_counts.pending', 1)
            ->assertJsonPath('status_counts.reviewing', 0)
            ->assertJsonPath('status_counts.shortlisted', 0)
            ->assertJsonPath('status_counts.interviewed', 1)
            ->assertJsonPath('status_counts.offered', 0)
            ->assertJsonPath('status_counts.hired', 0)
            ->assertJsonPath('status_counts.rejected', 1);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_my_applications_filters_by_specific_status(): void
    {
        $user = $this->createApplicant();
        $job1 = $this->createJobPost('Job 1');
        $job2 = $this->createJobPost('Job 2');

        IndustryJobApplication::create([
            'application_id' => '200001',
            'job_id' => $job1->id,
            'applicant_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'resume_path' => 'resumes/test.pdf',
            'status' => JobApplicationStatus::INTERVIEWED->value,
        ]);

        IndustryJobApplication::create([
            'application_id' => '200002',
            'job_id' => $job2->id,
            'applicant_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'resume_path' => 'resumes/test.pdf',
            'status' => JobApplicationStatus::PENDING->value,
        ]);

        // Filter by interviewed
        $response = $this->actingAs($user, 'api')->getJson('/api/industry/my-job-applications?status=interviewed');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('current_status', 'interviewed')
            ->assertJsonPath('status_counts.all', 2)
            ->assertJsonPath('status_counts.interviewed', 1)
            ->assertJsonPath('status_counts.pending', 1)
            ->assertJsonPath('data.0.application_id', '200001');
    }

    public function test_my_applications_handles_status_aliases(): void
    {
        $user = $this->createApplicant();
        $job = $this->createJobPost('DevOps Engineer');

        IndustryJobApplication::create([
            'application_id' => '300001',
            'job_id' => $job->id,
            'applicant_id' => $user->id,
            'full_name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'resume_path' => 'resumes/test.pdf',
            'status' => JobApplicationStatus::INTERVIEWED->value,
        ]);

        // Request with 'interview' alias
        $response = $this->actingAs($user, 'api')->getJson('/api/industry/my-job-applications?status=interview');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('current_status', 'interviewed')
            ->assertJsonPath('data.0.application_id', '300001');
    }

    public function test_my_applications_does_not_crash_on_zero_or_negative_limit(): void
    {
        $user = $this->createApplicant();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/my-job-applications?limit=0');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.limit', 1);
    }

    public function test_my_applications_supports_cursor_pagination(): void
    {
        $user = $this->createApplicant();

        for ($i = 1; $i <= 3; $i++) {
            $job = $this->createJobPost('Job '.$i);
            IndustryJobApplication::create([
                'application_id' => '40000'.$i,
                'job_id' => $job->id,
                'applicant_id' => $user->id,
                'full_name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'resume_path' => 'resumes/test.pdf',
                'status' => JobApplicationStatus::PENDING->value,
            ]);
        }

        // Page 1: limit 2
        $response1 = $this->actingAs($user, 'api')->getJson('/api/industry/my-job-applications?limit=2');

        $response1->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 3)
            ->assertJsonPath('pagination.limit', 2)
            ->assertJsonPath('pagination.has_more_pages', true);

        $this->assertCount(2, $response1->json('data'));
        $nextCursor = $response1->json('pagination.next_cursor');
        $this->assertNotEmpty($nextCursor);

        // Page 2: with cursor
        $response2 = $this->actingAs($user, 'api')->getJson('/api/industry/my-job-applications?limit=2&cursor='.$nextCursor);

        $response2->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('pagination.has_more_pages', false);

        $this->assertCount(1, $response2->json('data'));
    }
}
