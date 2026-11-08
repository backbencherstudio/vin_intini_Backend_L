<?php

namespace Tests\Feature\Api;

use App\Enums\IndustryJobPostStatus;
use App\Models\Industry;
use App\Models\IndustryJobPost;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserJobPreferenceAndRecommendationTest extends TestCase
{
    use DatabaseTransactions;

    protected function makeUser(array $attributes = []): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate([
            'name' => 'user',
            'guard_name' => 'api',
        ]);

        $user = User::factory()->create(array_merge([
            'is_verified' => true,
        ], $attributes));
        $user->assignRole($role);

        UserProfile::create([
            'user_id' => $user->id,
            'is_completed' => true,
        ]);

        return $user;
    }

    protected function makeIndustry(User $owner, array $attributes = []): Industry
    {
        return Industry::create(array_merge([
            'created_by' => $owner->id,
            'name' => 'BioTech Labs '.uniqid(),
            'slug' => 'biotech-labs-'.uniqid(),
            'email' => 'contact_'.uniqid().'@biotech.test',
            'status' => 'approved',
        ], $attributes));
    }

    public function test_user_can_get_and_save_partial_job_preferences(): void
    {
        $user = $this->makeUser();

        // 1. Initial state: no preferences
        $response = $this->actingAs($user, 'api')->getJson('/api/user/job-preference');
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'has_preference' => false,
            ]);

        // 2. Save partial preferences (only 4 fields provided)
        $payload = [
            'job_title' => 'Assistant Professor of Psychology',
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
            'level' => 'Mid-Level',
        ];

        $saveResponse = $this->actingAs($user, 'api')->postJson('/api/user/job-preference', $payload);
        $saveResponse->assertOk()
            ->assertJson([
                'success' => true,
                'has_preference' => true,
            ])
            ->assertJsonPath('data.job_title', 'Assistant Professor of Psychology')
            ->assertJsonPath('data.work_mode', 'remote')
            ->assertJsonPath('data.employment_type', 'full_time')
            ->assertJsonPath('data.level', 'Mid-Level')
            ->assertJsonPath('data.position', null);

        // 3. Retrieve saved preferences
        $getRes = $this->actingAs($user, 'api')->getJson('/api/user/job-preference');
        $getRes->assertOk()
            ->assertJsonPath('data.job_title', 'Assistant Professor of Psychology');

        // 4. Clear preferences
        $clearRes = $this->actingAs($user, 'api')->deleteJson('/api/user/job-preference');
        $clearRes->assertOk()
            ->assertJson([
                'success' => true,
                'has_preference' => false,
            ]);
    }

    public function test_recommended_jobs_performs_partial_matching_and_ranks_by_score(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);

        $jobCandidateUser = $this->makeUser();

        // Create 3 active published jobs:
        // Job A matches title word ("Professor"), work_mode ("remote"), employment_type ("full_time") -> highest score
        $jobA = IndustryJobPost::create([
            'job_id' => 'JOB-'.uniqid(),
            'industry_id' => $industry->id,
            'created_by' => $owner->id,
            'job_title' => 'Clinical Professor',
            'job_description' => 'Great job description for professor.',
            'slug' => 'clinical-professor-'.uniqid(),
            'position' => 'Senior Instructor',
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
            'level' => 'Senior-Level',
            'email' => 'apply@test.com',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);

        // Job B matches only work_mode ("remote") -> moderate score
        IndustryJobPost::create([
            'job_id' => 'JOB-'.uniqid(),
            'industry_id' => $industry->id,
            'created_by' => $owner->id,
            'job_title' => 'Laboratory Data Analyst',
            'job_description' => 'Great job description for analyst.',
            'slug' => 'lab-analyst-'.uniqid(),
            'position' => 'Data Analyst',
            'work_mode' => 'remote',
            'employment_type' => 'contract',
            'level' => 'Entry-Level',
            'email' => 'apply@test.com',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);

        // Job C matches position word ("Psychologist") -> moderate score
        IndustryJobPost::create([
            'job_id' => 'JOB-'.uniqid(),
            'industry_id' => $industry->id,
            'created_by' => $owner->id,
            'job_title' => 'Cognitive Specialist',
            'job_description' => 'Great job description for specialist.',
            'slug' => 'cognitive-specialist-'.uniqid(),
            'position' => 'Licensed Psychologist',
            'work_mode' => 'on_site',
            'employment_type' => 'part_time',
            'level' => 'Mid-Level',
            'email' => 'apply@test.com',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);

        // Set user's preference:
        // job_title = "Assistant Professor" (matches Job A via "Professor")
        // position = "Clinical Psychologist" (matches Job C via "Psychologist")
        // work_mode = "remote" (matches Job A and Job B)
        // employment_type = "full_time" (matches Job A)
        $this->actingAs($jobCandidateUser, 'api')->postJson('/api/user/job-preference', [
            'job_title' => 'Assistant Professor',
            'position' => 'Clinical Psychologist',
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
        ])->assertOk();

        $recResponse = $this->actingAs($jobCandidateUser, 'api')->getJson('/api/industry/jobsfeed/recommended');

        $recResponse->assertOk()
            ->assertJson([
                'success' => true,
                'has_preference' => true,
                'matched_preference' => true,
            ]);

        $data = $recResponse->json('data');
        $this->assertNotEmpty($data);

        // Job A has the most matches (title keyword + work_mode + employment_type) so it must rank first!
        $this->assertEquals($jobA->id, $data[0]['id']);
        $this->assertGreaterThan(0, $data[0]['match_score']);
        $this->assertNotEmpty($data[0]['matched_criteria']);
    }

    public function test_recommended_jobs_falls_back_gracefully_when_user_has_no_preferences(): void
    {
        $owner = $this->makeUser();
        $industry = $this->makeIndustry($owner);

        IndustryJobPost::create([
            'job_id' => 'JOB-'.uniqid(),
            'industry_id' => $industry->id,
            'created_by' => $owner->id,
            'job_title' => 'Research Associate',
            'job_description' => 'Research description.',
            'slug' => 'research-associate-'.uniqid(),
            'position' => 'Researcher',
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
            'email' => 'apply@test.com',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);

        $userWithoutPref = $this->makeUser();

        $response = $this->actingAs($userWithoutPref, 'api')->getJson('/api/industry/jobsfeed/recommended');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'has_preference' => false,
                'matched_preference' => false,
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }
}
