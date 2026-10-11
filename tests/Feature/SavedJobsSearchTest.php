<?php

namespace Tests\Feature;

use App\Enums\IndustryJobPostStatus;
use App\Models\City;
use App\Models\Industry;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostSave;
use App\Models\State;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SavedJobsSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthenticatedUser(): User
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

        return $user;
    }

    private function createJob(array $overrides = [], ?string $companyName = null): IndustryJobPost
    {
        $creator = User::factory()->create(['is_verified' => true]);

        $industry = Industry::create([
            'created_by' => $creator->id,
            'name' => $companyName ?? 'Acme Labs',
            'slug' => 'company-'.uniqid(),
            'status' => 'approved',
        ]);

        $state = State::firstOrCreate(['name' => 'Arizona'], [
            'code' => 'AZ',
            'slug' => 'arizona',
        ]);

        $city = City::firstOrCreate(['name' => 'Phoenix', 'state_id' => $state->id]);

        return IndustryJobPost::create(array_merge([
            'job_id' => (string) random_int(100000, 999999),
            'industry_id' => $industry->id,
            'created_by' => $creator->id,
            'job_title' => 'Software Engineer',
            'slug' => 'job-slug-'.uniqid(),
            'position' => 'Senior Developer',
            'category' => 'Engineering',
            'sub_category' => 'Backend',
            'network_type' => 'technology',
            'employment_offering' => 'private',
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
            'job_description' => 'Detailed job description goes here',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'email' => 'contact@test.com',
            'phone_number' => '+1234567890',
            'salary_min' => 70000,
            'salary_max' => 95000,
            'salary_type' => 'Yearly',
            'announcement_start_date' => Carbon::yesterday()->toDateString(),
            'announcement_end_date' => Carbon::now()->addDays(30)->toDateString(),
            'status' => IndustryJobPostStatus::PUBLISHED->value,
        ], $overrides));
    }

    public function test_saved_jobs_returns_only_authenticated_user_saved_jobs(): void
    {
        $user1 = $this->createAuthenticatedUser();
        $user2 = $this->createAuthenticatedUser();

        $job1 = $this->createJob(['job_title' => 'PHP Developer']);
        $job2 = $this->createJob(['job_title' => 'Node.js Developer']);

        IndustryJobPostSave::create(['user_id' => $user1->id, 'industry_job_post_id' => $job1->id]);
        IndustryJobPostSave::create(['user_id' => $user2->id, 'industry_job_post_id' => $job2->id]);

        $response = $this->actingAs($user1, 'api')->getJson('/api/industry/saved-jobs');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $job1->id)
            ->assertJsonPath('data.0.is_saved', true);
    }

    public function test_saved_jobs_search_by_title_and_position(): void
    {
        $user = $this->createAuthenticatedUser();

        $job1 = $this->createJob([
            'job_title' => 'Laravel Lead Architect',
            'position' => 'Team Lead',
        ]);
        $job2 = $this->createJob([
            'job_title' => 'Frontend React Engineer',
            'position' => 'UI Developer',
        ]);

        IndustryJobPostSave::create(['user_id' => $user->id, 'industry_job_post_id' => $job1->id]);
        IndustryJobPostSave::create(['user_id' => $user->id, 'industry_job_post_id' => $job2->id]);

        // Search by job_title
        $resTitle = $this->actingAs($user, 'api')->getJson('/api/industry/saved-jobs?search=Laravel');
        $resTitle->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $job1->id);

        // Search by position
        $resPos = $this->actingAs($user, 'api')->getJson('/api/industry/saved-jobs?search=UI Developer');
        $resPos->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $job2->id);
    }

    public function test_saved_jobs_search_by_company_name(): void
    {
        $user = $this->createAuthenticatedUser();

        $job1 = $this->createJob(['job_title' => 'AI Scientist'], 'OpenAI Solutions');
        $job2 = $this->createJob(['job_title' => 'Data Analyst'], 'DeepMind Research');

        IndustryJobPostSave::create(['user_id' => $user->id, 'industry_job_post_id' => $job1->id]);
        IndustryJobPostSave::create(['user_id' => $user->id, 'industry_job_post_id' => $job2->id]);

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/saved-jobs?search=DeepMind');

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $job2->id);
    }

    public function test_saved_jobs_filters_by_status_and_work_mode(): void
    {
        $user = $this->createAuthenticatedUser();

        $job1 = $this->createJob([
            'job_title' => 'Remote Dev',
            'work_mode' => 'remote',
            'status' => IndustryJobPostStatus::PUBLISHED->value,
        ]);
        $job2 = $this->createJob([
            'job_title' => 'Onsite Dev',
            'work_mode' => 'on_site',
            'status' => IndustryJobPostStatus::EXPIRED->value,
        ]);

        IndustryJobPostSave::create(['user_id' => $user->id, 'industry_job_post_id' => $job1->id]);
        IndustryJobPostSave::create(['user_id' => $user->id, 'industry_job_post_id' => $job2->id]);

        // Filter by work_mode
        $resWorkMode = $this->actingAs($user, 'api')->getJson('/api/industry/saved-jobs?work_mode=remote');
        $resWorkMode->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $job1->id);

        // Filter by status
        $resStatus = $this->actingAs($user, 'api')->getJson('/api/industry/saved-jobs?status=expired');
        $resStatus->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $job2->id);
    }

    public function test_saved_jobs_supports_cursor_pagination(): void
    {
        $user = $this->createAuthenticatedUser();

        $job1 = $this->createJob(['job_title' => 'Job 1']);
        $job2 = $this->createJob(['job_title' => 'Job 2']);
        $job3 = $this->createJob(['job_title' => 'Job 3']);

        IndustryJobPostSave::create(['user_id' => $user->id, 'industry_job_post_id' => $job1->id]);
        IndustryJobPostSave::create(['user_id' => $user->id, 'industry_job_post_id' => $job2->id]);
        IndustryJobPostSave::create(['user_id' => $user->id, 'industry_job_post_id' => $job3->id]);

        // First page with limit=2
        $response1 = $this->actingAs($user, 'api')->getJson('/api/industry/saved-jobs?limit=2');

        $response1->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('pagination.limit', 2)
            ->assertJsonPath('pagination.has_more_pages', true)
            ->assertJsonCount(2, 'data');

        $nextCursor = $response1->json('pagination.next_cursor');
        $this->assertNotEmpty($nextCursor);

        // Second page using next_cursor
        $response2 = $this->actingAs($user, 'api')->getJson('/api/industry/saved-jobs?limit=2&cursor='.$nextCursor);

        $response2->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('pagination.has_more_pages', false)
            ->assertJsonCount(1, 'data');
    }
}
