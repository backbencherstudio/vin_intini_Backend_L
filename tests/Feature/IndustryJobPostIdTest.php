<?php

namespace Tests\Feature;

use App\Enums\IndustryJobPostStatus;
use App\Enums\PlanType;
use App\Models\City;
use App\Models\Industry;
use App\Models\IndustryJobPost;
use App\Models\Plan;
use App\Models\State;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryJobPostIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_unique_job_id_format_and_lengths(): void
    {
        // 1-digit industry: 1 + 5 = 6 characters
        $id1 = IndustryJobPost::generateUniqueJobId(7);
        $this->assertEquals(6, strlen($id1));
        $this->assertStringStartsWith('7', $id1);
        $this->assertMatchesRegularExpression('/^7[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{5}$/', $id1);

        // 2-digit industry: 2 + 4 = 6 characters
        $id2 = IndustryJobPost::generateUniqueJobId(42);
        $this->assertEquals(6, strlen($id2));
        $this->assertStringStartsWith('42', $id2);
        $this->assertMatchesRegularExpression('/^42[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{4}$/', $id2);

        // 3-digit industry: 3 + 3 = 6 characters
        $id3 = IndustryJobPost::generateUniqueJobId(500);
        $this->assertEquals(6, strlen($id3));
        $this->assertStringStartsWith('500', $id3);
        $this->assertMatchesRegularExpression('/^500[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{3}$/', $id3);

        // 4-digit industry: 4 + 3 = 7 characters
        $id4 = IndustryJobPost::generateUniqueJobId(1234);
        $this->assertEquals(7, strlen($id4));
        $this->assertStringStartsWith('1234', $id4);
        $this->assertMatchesRegularExpression('/^1234[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{3}$/', $id4);

        // 5-digit industry: 5 + 3 = 8 characters
        $id5 = IndustryJobPost::generateUniqueJobId(56789);
        $this->assertEquals(8, strlen($id5));
        $this->assertStringStartsWith('56789', $id5);
        $this->assertMatchesRegularExpression('/^56789[23456789ABCDEFGHJKLMNPQRSTUVWXYZ]{3}$/', $id5);
    }

    public function test_model_boot_creates_job_id_automatically_when_omitted(): void
    {
        $creator = User::factory()->create(['is_verified' => true]);
        $industry = Industry::create([
            'created_by' => $creator->id,
            'name' => 'Tech Corp',
            'slug' => 'tech-corp',
            'status' => 'approved',
        ]);

        $jobPost = IndustryJobPost::create([
            'industry_id' => $industry->id,
            'created_by' => $creator->id,
            'job_title' => 'Software Engineer',
            'slug' => 'software-engineer',
            'job_description' => 'Build amazing stuff.',
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
            'email' => 'jobs@techcorp.com',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);

        $this->assertNotNull($jobPost->job_id);
        $this->assertStringStartsWith((string) $industry->id, $jobPost->job_id);
        $this->assertEquals(6, strlen($jobPost->job_id));
    }

    public function test_explicit_job_id_is_preserved_when_provided(): void
    {
        $creator = User::factory()->create(['is_verified' => true]);
        $industry = Industry::create([
            'created_by' => $creator->id,
            'name' => 'Tech Corp 2',
            'slug' => 'tech-corp-2',
            'status' => 'approved',
        ]);

        $jobPost = IndustryJobPost::create([
            'job_id' => 'CUSTOM-ID-99',
            'industry_id' => $industry->id,
            'created_by' => $creator->id,
            'job_title' => 'Product Manager',
            'slug' => 'product-manager',
            'job_description' => 'Lead product strategy.',
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
            'email' => 'pm@techcorp.com',
            'status' => IndustryJobPostStatus::PUBLISHED,
        ]);

        $this->assertEquals('CUSTOM-ID-99', $jobPost->job_id);
    }

    public function test_controller_store_generates_industry_scoped_job_id_and_slug(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $user = User::factory()->create(['is_verified' => true]);
        $user->assignRole($role);
        UserProfile::create(['user_id' => $user->id]);

        $plan = Plan::create([
            'name' => 'Pro Industry Plan',
            'plan_type' => PlanType::INDUSTRY->value,
            'status' => 'active',
            'billing_rate' => 99.99,
            'billing_cycle' => 'monthly',
            'features' => ['post_unlimited_jobs'],
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        $industry = Industry::create([
            'created_by' => $user->id,
            'name' => 'Neural Innovations',
            'slug' => 'neural-innovations',
            'status' => 'approved',
        ]);

        $state = State::create(['name' => 'California', 'code' => 'CA', 'slug' => 'california']);
        $city = City::create(['name' => 'San Francisco', 'state_id' => $state->id]);

        $payload = [
            'job_title' => 'AI Scientist',
            'position' => 'Senior Researcher',
            'category' => 'Machine Learning',
            'job_description' => 'Working on next gen models.',
            'work_mode' => 'hybrid',
            'employment_type' => 'full_time',
            'network_type' => 'neuroscience',
            'employment_offering' => 'private_practice',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'email' => 'careers@neural.io',
            'phone_number' => '+15551234567',
            'salary_min' => 120000,
            'salary_max' => 180000,
            'salary_type' => 'Yearly',
            'information_confirmed' => true,
            'status' => 'published',
        ];

        $response = $this->actingAs($user, 'api')->postJson('/api/industry/job-post/create', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $createdJob = IndustryJobPost::where('created_by', $user->id)->first();
        $this->assertNotNull($createdJob);

        $expectedPrefix = (string) $industry->id;
        $this->assertStringStartsWith($expectedPrefix, $createdJob->job_id);
        $this->assertEquals(6, strlen($createdJob->job_id));
        $this->assertStringEndsWith($createdJob->job_id, $createdJob->slug);
    }

    public function test_all_six_employment_offerings_are_accepted_on_create_and_update(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $user = User::factory()->create(['is_verified' => true]);
        $user->assignRole($userRole);

        UserProfile::create([
            'user_id' => $user->id,
            'is_profile_completed' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Pro Industry Plan',
            'plan_type' => PlanType::INDUSTRY->value,
            'status' => 'active',
            'billing_rate' => 99.99,
            'billing_cycle' => 'monthly',
            'features' => ['post_unlimited_jobs'],
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        $industry = Industry::create([
            'created_by' => $user->id,
            'name' => 'Biotech Labs',
            'slug' => 'biotech-labs',
            'status' => 'approved',
        ]);

        $offerings = [
            'state_institutions',
            'academic_institutions',
            'private_practice',
            'biotech_industry',
            'psychotropics_industry',
            'publications_industry',
        ];

        $basePayload = [
            'job_title' => 'Lab Researcher',
            'position' => 'Researcher',
            'category' => 'Research',
            'job_description' => 'Research description.',
            'work_mode' => 'remote',
            'employment_type' => 'full_time',
            'network_type' => 'neuroscience',
            'email' => 'hr@biotech.io',
            'phone_number' => '+15551234567',
            'salary_min' => 90000,
            'salary_max' => 110000,
            'salary_type' => 'Yearly',
            'information_confirmed' => true,
            'status' => 'published',
        ];

        // 1. Verify all 6 offerings succeed on create
        foreach ($offerings as $index => $offering) {
            $payload = array_merge($basePayload, [
                'job_title' => "Job Offering {$index}",
                'employment_offering' => $offering,
            ]);

            $response = $this->actingAs($user, 'api')->postJson('/api/industry/job-post/create', $payload);
            $response->assertCreated()
                ->assertJsonPath('success', true)
                ->assertJsonPath('data.employment_offering', $offering);
        }

        // 2. Verify offering update
        $firstJob = IndustryJobPost::where('created_by', $user->id)->first();
        $this->assertNotNull($firstJob);

        $updateResponse = $this->actingAs($user, 'api')->putJson("/api/industry/job-post/{$firstJob->id}/update", array_merge($basePayload, [
            'job_title' => 'Updated Offering Job',
            'employment_offering' => 'publications_industry',
        ]));

        $updateResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.employment_offering', 'publications_industry');

        // 3. Verify invalid offering is rejected with 422
        $invalidResponse = $this->actingAs($user, 'api')->postJson('/api/industry/job-post/create', array_merge($basePayload, [
            'job_title' => 'Invalid Offering Job',
            'employment_offering' => 'unsupported_offering',
        ]));

        $invalidResponse->assertStatus(422)
            ->assertJsonValidationErrors(['employment_offering']);
    }
}
