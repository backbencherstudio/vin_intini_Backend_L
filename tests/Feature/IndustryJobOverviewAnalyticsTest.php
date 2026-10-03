<?php

namespace Tests\Feature;

use App\Enums\IndustryJobPostStatus;
use App\Models\Industry;
use App\Models\IndustryJobPost;
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

    public function test_user_with_industry_gets_200_with_default_last_6_months_analytics(): void
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
                    'period',
                    'cards' => [
                        'job_views' => ['total', 'growth_percentage', 'is_positive'],
                        'total_applicants' => ['total', 'growth_percentage', 'is_positive'],
                        'active_positions' => ['total', 'growth_percentage', 'is_positive'],
                    ],
                    'graph' => [
                        'labels',
                        'series' => [
                            '*' => ['name', 'key', 'data'],
                        ],
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertSame('last_6_months', $data['period']);
        $this->assertSame(1, $data['cards']['active_positions']['total']);
        $this->assertSame(125, $data['cards']['job_views']['total']);
        $this->assertCount(6, $data['graph']['labels']);
    }

    public function test_user_can_filter_graph_by_last_3_months(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/job-overviews?filter=last_3_months');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertSame('last_3_months', $data['period']);
        $this->assertCount(3, $data['graph']['labels']);
        $this->assertCount(3, $data['graph']['series'][0]['data']);
    }

    public function test_user_can_filter_graph_by_last_12_months(): void
    {
        [$user, $industry] = $this->createTestUserWithIndustry();

        $response = $this->actingAs($user, 'api')->getJson('/api/industry/analytics/job-overviews?filter=last_12_months');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertSame('last_12_months', $data['period']);
        $this->assertCount(12, $data['graph']['labels']);
        $this->assertCount(12, $data['graph']['series'][0]['data']);
    }
}
