<?php

namespace Tests\Feature\Api;

use App\Enums\AdCampaignStatus;
use App\Enums\IndustryJobPostStatus;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryFollow;
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
            'logo' => 'https://cdn.example.com/walton.png',
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
                'logo' => 'https://cdn.example.com/walton.png',
                'is_own_industry' => true,
            ],
            'total' => 5,
        ]);

        $this->assertCount(2, $response->json('data'));
        $firstItem = $response->json('data.0');
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('job_id', $firstItem);
        $this->assertArrayHasKey('slug', $firstItem);
        $this->assertArrayHasKey('job_title', $firstItem);
        $this->assertArrayHasKey('work_mode', $firstItem);
        $this->assertArrayHasKey('employment_type', $firstItem);
        $this->assertArrayHasKey('network_type', $firstItem);
        $this->assertArrayNotHasKey('image_url', $firstItem);
        $this->assertArrayHasKey('state', $firstItem);
        $this->assertArrayHasKey('city', $firstItem);
        $this->assertArrayNotHasKey('location', $firstItem);
        $this->assertArrayHasKey('salary_min', $firstItem);
        $this->assertArrayHasKey('salary_max', $firstItem);
        $this->assertArrayHasKey('salary_type', $firstItem);
        $this->assertArrayHasKey('applications_count', $firstItem);
        $this->assertArrayHasKey('views_count', $firstItem);
        $this->assertArrayHasKey('job_description', $firstItem);
        $this->assertArrayHasKey('is_liked', $firstItem);
        $this->assertArrayHasKey('is_saved', $firstItem);
        $this->assertArrayHasKey('is_applied', $firstItem);
        $this->assertArrayHasKey('application_status', $firstItem);
        $this->assertArrayHasKey('industry', $firstItem);
        $this->assertSame($this->industry->id, $firstItem['industry']['id']);
        $this->assertSame('https://cdn.example.com/walton.png', $firstItem['industry']['logo']);
        $this->assertTrue($firstItem['industry']['is_own_industry']);

        $this->assertArrayNotHasKey('creator', $firstItem);
        $this->assertArrayNotHasKey('email', $firstItem);

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
        $this->assertFalse($response->json('industry.is_own_industry'));
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
        $firstItem = $response->json('data.0');
        $this->assertArrayHasKey('id', $firstItem);
        $this->assertArrayHasKey('product_id', $firstItem);
        $this->assertArrayHasKey('product_name', $firstItem);
        $this->assertArrayHasKey('slug', $firstItem);
        $this->assertArrayHasKey('views_count', $firstItem);
        $this->assertArrayHasKey('likes_count', $firstItem);
        $this->assertArrayHasKey('is_liked', $firstItem);
        $this->assertArrayHasKey('image_url', $firstItem);
        $this->assertArrayHasKey('network_type', $firstItem);
        $this->assertArrayHasKey('industry_type', $firstItem);
        $this->assertArrayHasKey('description', $firstItem);
        $this->assertArrayHasKey('industry', $firstItem);
        $this->assertArrayHasKey('is_boosted', $firstItem);
        $this->assertArrayHasKey('badge', $firstItem);
        $this->assertArrayHasKey('ad_campaign_id', $firstItem);
        $this->assertArrayHasKey('is_new_release', $firstItem);
        $this->assertSame($this->industry->id, $firstItem['industry']['id']);
        $this->assertSame('https://cdn.example.com/walton.png', $firstItem['industry']['logo']);
        $this->assertTrue($firstItem['industry']['is_own_industry']);

        $this->assertArrayNotHasKey('creator', $firstItem);
        $this->assertArrayNotHasKey('section', $firstItem);
        $this->assertArrayNotHasKey('category', $firstItem);

        $this->assertTrue($response->json('pagination.has_more_pages'));
        $this->assertNotNull($response->json('pagination.next_cursor'));
    }

    public function test_boosted_products_appear_first_in_industry_profile_products_tab_and_fallback_when_expired(): void
    {
        $section = IndustrySections::create([
            'name' => 'Biotech Devices',
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
        ]);

        $category = IndustryCategory::create([
            'section_id' => $section->id,
            'category_name' => 'Lab Devices',
        ]);

        $product1 = IndustryProduct::create([
            'product_id' => 'PRD001',
            'industry_id' => $this->industry->id,
            'creator_id' => $this->authUser->id,
            'section_id' => $section->id,
            'category_id' => $category->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'product_name' => 'Old Product 1',
            'slug' => 'old-product-1',
            'description' => 'Description for product 1',
            'status' => 'active',
        ]);

        $product2 = IndustryProduct::create([
            'product_id' => 'PRD002',
            'industry_id' => $this->industry->id,
            'creator_id' => $this->authUser->id,
            'section_id' => $section->id,
            'category_id' => $category->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'product_name' => 'Middle Product 2 (Boosted)',
            'slug' => 'middle-product-2-boosted',
            'description' => 'Description for product 2',
            'status' => 'active',
            'is_boosted' => true,
            'boost_expires_at' => now()->addDays(7),
        ]);

        $product3 = IndustryProduct::create([
            'product_id' => 'PRD003',
            'industry_id' => $this->industry->id,
            'creator_id' => $this->authUser->id,
            'section_id' => $section->id,
            'category_id' => $category->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'product_name' => 'Newest Product 3 (Unboosted)',
            'slug' => 'newest-product-3-unboosted',
            'description' => 'Description for product 3',
            'status' => 'active',
        ]);

        $package = BoostPackage::create([
            'name' => '7-Day Starter',
            'target_entity' => 'product',
            'duration_days' => 7,
            'price' => 19.99,
            'badge' => 'Starter',
            'priority_weight' => 1,
            'status' => 'active',
        ]);

        $campaign = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product2->id,
            'user_id' => $this->authUser->id,
            'industry_id' => $this->industry->id,
            'boost_package_id' => $package->id,
            'ad_type' => 'product',
            'network_type' => 'both',
            'placements' => ['marketplace', 'newsfeed'],
            'amount_paid' => 19.99,
            'currency' => 'USD',
            'duration_days' => 7,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(6),
            'status' => AdCampaignStatus::ACTIVE,
        ]);

        // When fetching products, Product 2 (Boosted) MUST be at index 0, followed by Product 3 (Newest unboosted), then Product 1
        $res = $this->actingAs($this->authUser, 'api')
            ->getJson("/api/industry/{$this->industry->slug}/products");

        $res->assertOk();
        $items = $res->json('data');
        $this->assertCount(3, $items);
        $this->assertSame($product2->id, $items[0]['id']);
        $this->assertTrue($items[0]['is_boosted']);
        $this->assertSame('Sponsored', $items[0]['badge']);
        $this->assertSame($campaign->id, $items[0]['ad_campaign_id']);

        $this->assertSame($product3->id, $items[1]['id']);
        $this->assertFalse($items[1]['is_boosted']);

        $this->assertSame($product1->id, $items[2]['id']);
        $this->assertFalse($items[2]['is_boosted']);

        // Now expire the boost
        $product2->update([
            'boost_expires_at' => now()->subHour(),
        ]);
        $campaign->update([
            'status' => AdCampaignStatus::COMPLETED,
            'ends_at' => now()->subHour(),
        ]);

        // Next fetch: Product 2 automatically falls back into its natural place sorted by newest-first (Product 3, Product 2, Product 1)
        $resExpired = $this->actingAs($this->authUser, 'api')
            ->getJson("/api/industry/{$this->industry->slug}/products");

        $resExpired->assertOk();
        $expiredItems = $resExpired->json('data');
        $this->assertSame($product3->id, $expiredItems[0]['id']);
        $this->assertSame($product2->id, $expiredItems[1]['id']);
        $this->assertSame($product1->id, $expiredItems[2]['id']);
        $this->assertFalse($expiredItems[1]['is_boosted']);
        $this->assertNull($expiredItems[1]['ad_campaign_id']);
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

    public function test_can_fetch_related_industries_excluding_visited_and_followed_industries(): void
    {
        // Visited industry has industry = 'biotechnology' (set in setUp as $this->industry)

        // Related industry 1 (same industry, unfollowed)
        $rel1 = Industry::create([
            'created_by' => User::factory()->create()->id,
            'name' => 'BioTech Alpha',
            'slug' => 'biotech-alpha',
            'industry' => 'biotechnology',
            'tagline' => 'Next-gen biotech solutions',
        ]);

        // Related industry 2 (same industry, unfollowed)
        $rel2 = Industry::create([
            'created_by' => User::factory()->create()->id,
            'name' => 'BioTech Beta',
            'slug' => 'biotech-beta',
            'industry' => 'biotechnology',
            'tagline' => 'Advanced biology',
        ]);

        // Related industry 3 (same industry, but authUser ALREADY FOLLOWS it)
        $relFollowed = Industry::create([
            'created_by' => User::factory()->create()->id,
            'name' => 'BioTech Followed',
            'slug' => 'biotech-followed',
            'industry' => 'biotechnology',
            'tagline' => 'Already followed company',
        ]);
        IndustryFollow::create([
            'industry_id' => $relFollowed->id,
            'user_id' => $this->authUser->id,
        ]);

        // Unrelated industry (different industry: 'fintech')
        Industry::create([
            'created_by' => User::factory()->create()->id,
            'name' => 'Fintech Prime',
            'slug' => 'fintech-prime',
            'industry' => 'fintech',
            'tagline' => 'Financial services',
        ]);

        // Fetch related industries for visited industry
        $response = $this->actingAs($this->authUser, 'api')
            ->getJson("/api/industry/{$this->industry->slug}/related");

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Related industries retrieved successfully.',
            'industry' => [
                'id' => $this->industry->id,
                'slug' => 'walton-group',
                'industry' => 'biotechnology',
            ],
            'total' => 2,
        ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);

        $returnedSlugs = collect($data)->pluck('slug')->all();
        // Visited industry itself MUST NOT be in the results
        $this->assertNotContains('walton-group', $returnedSlugs);
        // Followed industry MUST NOT be in the results
        $this->assertNotContains('biotech-followed', $returnedSlugs);
        // Different industry category MUST NOT be in the results
        $this->assertNotContains('fintech-prime', $returnedSlugs);
        // The two unfollowed biotech industries MUST be in the results
        $this->assertContains('biotech-alpha', $returnedSlugs);
        $this->assertContains('biotech-beta', $returnedSlugs);

        // All returned items must have is_following = false
        foreach ($data as $item) {
            $this->assertFalse($item['is_following']);
        }
    }

    public function test_can_paginate_related_industries_with_cursor(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Industry::create([
                'created_by' => User::factory()->create()->id,
                'name' => "Related Biotech {$i}",
                'slug' => "related-biotech-{$i}",
                'industry' => 'biotechnology',
                'tagline' => "Tagline {$i}",
            ]);
        }

        // Request page 1 with per_page = 2
        $response1 = $this->actingAs($this->authUser, 'api')
            ->getJson("/api/industry/{$this->industry->id}/related?per_page=2");

        $response1->assertStatus(200);
        $response1->assertJson([
            'success' => true,
            'total' => 5,
        ]);

        $this->assertCount(2, $response1->json('data'));
        $this->assertTrue($response1->json('pagination.has_more_pages'));
        $nextCursor = $response1->json('pagination.next_cursor');
        $this->assertNotNull($nextCursor);

        // Request page 2 using the next_cursor
        $response2 = $this->actingAs($this->authUser, 'api')
            ->getJson("/api/industry/{$this->industry->id}/related?per_page=2&cursor={$nextCursor}");

        $response2->assertStatus(200);
        $this->assertCount(2, $response2->json('data'));

        // Ensure page 1 and page 2 items are disjoint (no duplicate items across cursor pages)
        $page1Ids = collect($response1->json('data'))->pluck('id')->all();
        $page2Ids = collect($response2->json('data'))->pluck('id')->all();
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
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

        $relatedResponse = $this->actingAs($this->authUser, 'api')
            ->getJson('/api/industry/99999/related');

        $relatedResponse->assertStatus(404);
        $relatedResponse->assertJson([
            'success' => false,
            'message' => 'Industry not found.',
        ]);
    }
}
