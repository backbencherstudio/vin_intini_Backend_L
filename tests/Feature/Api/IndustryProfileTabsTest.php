<?php

namespace Tests\Feature\Api;

use App\Enums\IndustryJobPostStatus;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryJobPost;
use App\Models\IndustryProduct;
use App\Models\IndustryProductLike;
use App\Models\IndustrySections;
use App\Models\Publication;
use App\Models\PublicationLike;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryProfileTabsTest extends TestCase
{
    use RefreshDatabase;

    private User $authUser;

    private User $visitorUser;

    private Industry $industry;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $this->authUser = User::factory()->create();
        $this->authUser->assignRole('user');
        UserProfile::create(['user_id' => $this->authUser->id]);

        $this->visitorUser = User::factory()->create();
        $this->visitorUser->assignRole('user');
        UserProfile::create(['user_id' => $this->visitorUser->id]);

        $this->industry = Industry::create([
            'created_by' => $this->authUser->id,
            'name' => 'WALTON Group',
            'slug' => 'walton-group',
            'industry' => 'biotechnology',
            'status' => 'approved',
        ]);
    }

    public function test_can_fetch_industry_jobs_with_cursor_pagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            IndustryJobPost::create([
                'job_id' => "WALT0{$i}",
                'industry_id' => $this->industry->id,
                'created_by' => $this->authUser->id,
                'job_title' => "Software Engineer {$i}",
                'slug' => "software-engineer-{$i}",
                'job_description' => "We are looking for passionate Software Engineer {$i}.",
                'work_mode' => 'remote',
                'employment_type' => 'full-time',
                'email' => "careers{$i}@walton.com",
                'phone_number' => '+880123456789',
                'salary_min' => 50000,
                'salary_max' => 80000,
                'salary_type' => 'monthly',
                'status' => IndustryJobPostStatus::PUBLISHED,
                'announcement_start_date' => now()->subDay(),
                'announcement_end_date' => now()->addDays(10),
            ]);
        }

        // Test with slug and limit = 2
        $response = $this->actingAs($this->authUser, 'api')
            ->getJson("/api/industry/{$this->industry->slug}/jobs?per_page=2");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'industry' => [
                'id' => $this->industry->id,
                'slug' => 'walton-group',
            ],
            'total' => 5,
        ]);

        $this->assertCount(2, $response->json('data'));
        $this->assertTrue($response->json('pagination.has_more_pages'));
        $this->assertNotNull($response->json('pagination.next_cursor'));

        // Test next page cursor
        $nextCursor = $response->json('pagination.next_cursor');
        $secondPageResponse = $this->actingAs($this->authUser, 'api')
            ->getJson("/api/industry/{$this->industry->id}/jobs?per_page=2&cursor={$nextCursor}");

        $secondPageResponse->assertStatus(200);
        $this->assertCount(2, $secondPageResponse->json('data'));
    }

    public function test_visitor_only_sees_published_jobs_and_active_products_and_publications(): void
    {
        // Published job
        IndustryJobPost::create([
            'job_id' => 'WALT-PUB',
            'industry_id' => $this->industry->id,
            'created_by' => $this->authUser->id,
            'job_title' => 'Published Job',
            'slug' => 'published-job',
            'job_description' => 'Active job post',
            'work_mode' => 'remote',
            'employment_type' => 'full-time',
            'email' => 'careers@walton.com',
            'status' => IndustryJobPostStatus::PUBLISHED,
            'announcement_start_date' => now()->subDay(),
            'announcement_end_date' => now()->addDays(10),
        ]);

        // Draft job (should NOT be visible to visitor)
        IndustryJobPost::create([
            'job_id' => 'WALT-DFT',
            'industry_id' => $this->industry->id,
            'created_by' => $this->authUser->id,
            'job_title' => 'Draft Job',
            'slug' => 'draft-job',
            'job_description' => 'Draft job post',
            'work_mode' => 'remote',
            'employment_type' => 'full-time',
            'email' => 'careers@walton.com',
            'status' => IndustryJobPostStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->visitorUser, 'api')
            ->getJson("/api/industry/{$this->industry->slug}/jobs");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('WALT-PUB', $response->json('data.0.job_id'));
    }

    public function test_can_fetch_industry_products_with_cursor_pagination(): void
    {
        $section = IndustrySections::create([
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'name' => 'Biotech Devices',
        ]);

        $category = IndustryCategory::create([
            'section_id' => $section->id,
            'category_name' => 'General Devices',
        ]);

        for ($i = 1; $i <= 4; $i++) {
            IndustryProduct::create([
                'product_id' => "PRD0{$i}",
                'industry_id' => $this->industry->id,
                'creator_id' => $this->authUser->id,
                'section_id' => $section->id,
                'category_id' => $category->id,
                'network_type' => 'psychology',
                'industry_type' => 'biotechnology',
                'product_name' => "Walton Bio Device {$i}",
                'slug' => "walton-bio-device-{$i}",
                'description' => "Detailed specifications for Walton Bio Device {$i}",
                'status' => 'active',
            ]);
        }

        // Like the first product to verify like state
        $firstProduct = IndustryProduct::first();
        IndustryProductLike::create([
            'industry_product_id' => $firstProduct->id,
            'user_id' => $this->authUser->id,
        ]);

        $response = $this->actingAs($this->authUser, 'api')
            ->getJson("/api/industry/{$this->industry->slug}/products?per_page=2");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'industry' => [
                'id' => $this->industry->id,
                'slug' => 'walton-group',
            ],
            'total' => 4,
        ]);

        $this->assertCount(2, $response->json('data'));
        $this->assertTrue($response->json('pagination.has_more_pages'));
        $this->assertNotNull($response->json('pagination.next_cursor'));
    }

    public function test_can_fetch_industry_publications_with_cursor_pagination(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            Publication::create([
                'publication_id' => "PB00{$i}",
                'industry_id' => $this->industry->id,
                'creator_id' => $this->authUser->id,
                'network_type' => 'neuroscience',
                'publication_type' => 'professional',
                'title' => "Advancements in Walton Neuroscience {$i}",
                'slug' => "walton-neuroscience-{$i}",
                'abstract' => "Research abstract {$i}",
                'website_url' => "https://walton.example.com/publication/{$i}",
                'status' => 'active',
            ]);
        }

        // Like first publication
        $firstPub = Publication::first();
        PublicationLike::create([
            'publication_id' => $firstPub->id,
            'user_id' => $this->authUser->id,
        ]);

        $response = $this->actingAs($this->authUser, 'api')
            ->getJson("/api/industry/{$this->industry->id}/publications?per_page=2");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'industry' => [
                'id' => $this->industry->id,
                'slug' => 'walton-group',
            ],
            'total' => 3,
        ]);

        $this->assertCount(2, $response->json('data'));
        $this->assertTrue($response->json('pagination.has_more_pages'));
    }

    public function test_returns_404_for_non_existent_industry(): void
    {
        $response = $this->actingAs($this->authUser, 'api')
            ->getJson('/api/industry/99999/jobs');

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Industry not found.',
        ]);
    }
}
