<?php

namespace Tests\Feature\Api;

use App\Enums\PlanType;
use App\Models\Industry;
use App\Models\Plan;
use App\Models\Publication;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PublicationTest extends TestCase
{
    use RefreshDatabase;

    private User $industryUser;

    private Industry $industry;

    private User $premiumUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Roles
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

        // Pro Industry User & Company
        $this->industryUser = User::factory()->create();
        $this->industryUser->assignRole('user');
        UserProfile::create(['user_id' => $this->industryUser->id]);

        $this->industry = Industry::create([
            'created_by' => $this->industryUser->id,
            'name' => 'MindTech Journal Group',
            'slug' => 'mindtech-journal-group',
            'industry' => 'biotechnology',
            'status' => 'approved',
        ]);

        $industryPlan = Plan::create([
            'name' => 'Pro Industry Plan',
            'plan_type' => PlanType::INDUSTRY->value,
            'billing_rate' => 99.00,
            'billing_cycle' => 'monthly',
            'features' => ['publications'],
            'status' => 'active',
        ]);

        Subscription::create([
            'user_id' => $this->industryUser->id,
            'plan_id' => $industryPlan->id,
            'status' => 'active',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Premium User (Individual Scholar / Freelance Researcher)
        $this->premiumUser = User::factory()->create();
        $this->premiumUser->assignRole('user');
        UserProfile::create(['user_id' => $this->premiumUser->id]);

        $premiumPlan = Plan::create([
            'name' => 'Premium Pro User Plan',
            'plan_type' => PlanType::PREMIUM->value,
            'billing_rate' => 29.99,
            'billing_cycle' => 'monthly',
            'features' => ['publications'],
            'status' => 'active',
        ]);

        Subscription::create([
            'user_id' => $this->premiumUser->id,
            'plan_id' => $premiumPlan->id,
            'status' => 'active',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);
    }

    public function test_user_without_subscription_cannot_create_publication(): void
    {
        $unsubscribedUser = User::factory()->create();
        $unsubscribedUser->assignRole('user');
        UserProfile::create(['user_id' => $unsubscribedUser->id]);

        $response = $this->actingAs($unsubscribedUser, 'api')->postJson('/api/publications/create', [
            'network_type' => 'neuroscience',
            'publication_type' => 'university',
            'title' => 'Neuroplasticity in Cognitive Rehabilitation',
            'abstract' => 'Comprehensive abstract detailing recent clinical findings.',
            'website_url' => 'https://example.com/study',
            'information_confirmed' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subscription']);
    }

    public function test_pro_industry_user_creates_professional_publication_under_company(): void
    {
        $pdf = UploadedFile::fake()->create('research_overview.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->industryUser, 'api')->postJson('/api/publications/create', [
            'network_type' => 'neuroscience',
            'publication_type' => 'professional',
            'title' => 'Breakthroughs in Deep Brain Stimulation',
            'abstract' => 'A journal headline overview of next-generation neurological stimulation devices.',
            'authors' => ['Dr. Sarah Connor', 'Dr. Miles Dyson'],
            'website_url' => 'https://mindtech.org/articles/dbs-breakthrough',
            'attachment' => $pdf,
            'information_confirmed' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Breakthroughs in Deep Brain Stimulation');
        $response->assertJsonPath('data.publication_type', 'professional');
        $response->assertJsonPath('data.category_tab', 'professional');
        $response->assertJsonPath('data.industry_id', $this->industry->id);
        $response->assertJsonPath('data.creator_id', $this->industryUser->id);

        $this->assertDatabaseHas('publications', [
            'title' => 'Breakthroughs in Deep Brain Stimulation',
            'publication_type' => 'professional',
            'industry_id' => $this->industry->id,
            'creator_id' => $this->industryUser->id,
        ]);
    }

    public function test_pro_industry_user_publication_type_is_automatically_set_to_professional_when_omitted(): void
    {
        // Notice publication_type is omitted from request payload
        $response = $this->actingAs($this->industryUser, 'api')->postJson('/api/publications/create', [
            'network_type' => 'neuroscience',
            'title' => 'Automated Type Industry Paper',
            'abstract' => 'This paper was submitted without explicit publication_type field.',
            'website_url' => 'https://mindtech.org/articles/auto-type',
            'information_confirmed' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Automated Type Industry Paper');
        $response->assertJsonPath('data.publication_type', 'professional');
        $response->assertJsonPath('data.category_tab', 'professional');
        $response->assertJsonPath('data.industry_id', $this->industry->id);

        $this->assertDatabaseHas('publications', [
            'title' => 'Automated Type Industry Paper',
            'publication_type' => 'professional',
            'industry_id' => $this->industry->id,
        ]);
    }

    public function test_premium_user_must_provide_publication_type(): void
    {
        // Premium user omits publication_type
        $response = $this->actingAs($this->premiumUser, 'api')->postJson('/api/publications/create', [
            'network_type' => 'psychology',
            'title' => 'Missing Publication Type Study',
            'abstract' => 'Abstract for missing publication type test.',
            'website_url' => 'https://doi.org/10.1000/182',
            'information_confirmed' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['publication_type']);
    }

    public function test_premium_user_can_create_university_or_freelance_publication(): void
    {
        $response = $this->actingAs($this->premiumUser, 'api')->postJson('/api/publications/create', [
            'network_type' => 'psychology',
            'publication_type' => 'university',
            'title' => 'Cognitive Behavioral Therapy for Anxiety Disorders',
            'abstract' => 'Academic research paper covering methodology and patient outcomes over 36 months.',
            'authors' => ['Dr. Evelyn Reed, PhD'],
            'website_url' => 'https://doi.org/10.1000/182',
            'information_confirmed' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.title', 'Cognitive Behavioral Therapy for Anxiety Disorders');
        $response->assertJsonPath('data.publication_type', 'university');
        $response->assertJsonPath('data.category_tab', 'peer_reviewed');
        $response->assertJsonPath('data.industry_id', null);
        $response->assertJsonPath('data.creator_id', $this->premiumUser->id);

        $this->assertDatabaseHas('publications', [
            'title' => 'Cognitive Behavioral Therapy for Anxiety Disorders',
            'publication_type' => 'university',
            'industry_id' => null,
            'creator_id' => $this->premiumUser->id,
        ]);
    }

    public function test_premium_user_cannot_select_professional_publication_category(): void
    {
        $response = $this->actingAs($this->premiumUser, 'api')->postJson('/api/publications/create', [
            'network_type' => 'psychology',
            'publication_type' => 'professional',
            'title' => 'Unauthorized Industry Article',
            'abstract' => 'Trying to submit under professional category without Pro Industry subscription.',
            'website_url' => 'https://example.com',
            'information_confirmed' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['publication_type']);
    }

    public function test_deleting_premium_user_deletes_their_personal_publications(): void
    {
        $publication = Publication::create([
            'creator_id' => $this->premiumUser->id,
            'industry_id' => null,
            'network_type' => 'psychology',
            'publication_type' => 'freelance',
            'title' => 'Independent Clinical Study',
            'slug' => 'independent-clinical-study',
            'abstract' => 'Abstract for clinical study.',
            'website_url' => 'https://example.com/study',
            'information_confirmed' => true,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('publications', ['id' => $publication->id, 'deleted_at' => null]);

        // Delete user
        $this->premiumUser->delete();

        // Publication must be deleted
        $this->assertSoftDeleted('publications', ['id' => $publication->id]);
    }

    public function test_deleting_pro_industry_user_preserves_company_publication(): void
    {
        $publication = Publication::create([
            'creator_id' => $this->industryUser->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'neuroscience',
            'publication_type' => 'professional',
            'title' => 'Corporate Neuro Journal Issue 12',
            'slug' => 'corporate-neuro-journal-issue-12',
            'abstract' => 'Company publication overview.',
            'website_url' => 'https://example.com/issue-12',
            'information_confirmed' => true,
            'status' => 'active',
        ]);

        // Delete user
        $this->industryUser->delete();

        // Publication must remain alive under the industry
        $this->assertDatabaseHas('publications', [
            'id' => $publication->id,
            'industry_id' => $this->industry->id,
            'deleted_at' => null,
        ]);
    }

    public function test_public_feed_filters_by_network_and_tabs(): void
    {
        // 1 Professional publication in Neuroscience
        Publication::create([
            'creator_id' => $this->industryUser->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'neuroscience',
            'publication_type' => 'professional',
            'title' => 'Professional Neuro Journal',
            'slug' => 'professional-neuro-journal',
            'abstract' => 'Neuro journal abstract.',
            'website_url' => 'https://example.com/neuro',
            'status' => 'active',
        ]);

        // 1 University paper in Neuroscience
        Publication::create([
            'creator_id' => $this->premiumUser->id,
            'industry_id' => null,
            'network_type' => 'neuroscience',
            'publication_type' => 'university',
            'title' => 'Academic Synaptic Plasticity Paper',
            'slug' => 'academic-synaptic-plasticity-paper',
            'abstract' => 'University lab abstract.',
            'website_url' => 'https://example.com/plasticity',
            'status' => 'active',
        ]);

        // Test Professional tab (publication_type=professional)
        $responseProf = $this->actingAs($this->industryUser, 'api')
            ->getJson('/api/publications/feed?network_type=neuroscience&publication_type=professional');
        $responseProf->assertStatus(200);
        $responseProf->assertJsonCount(1, 'data');
        $responseProf->assertJsonPath('data.0.publication_type', 'professional');
        $responseProf->assertJsonStructure([
            'success',
            'message',
            'data',
            'pagination' => [
                'per_page',
                'next_cursor',
                'prev_cursor',
                'has_more_pages',
            ],
        ]);

        // Test University tab (publication_type=university)
        $responseUni = $this->actingAs($this->industryUser, 'api')
            ->getJson('/api/publications/feed?network_type=neuroscience&publication_type=university');
        $responseUni->assertStatus(200);
        $responseUni->assertJsonCount(1, 'data');
        $responseUni->assertJsonPath('data.0.publication_type', 'university');
    }

    public function test_public_feed_cursor_pagination_orders_latest_on_top(): void
    {
        $pub1 = Publication::create([
            'creator_id' => $this->premiumUser->id,
            'network_type' => 'psychology',
            'publication_type' => 'university',
            'title' => 'First Created Publication',
            'slug' => 'first-created-publication',
            'abstract' => 'First abstract.',
            'website_url' => 'https://example.com/pub1',
            'status' => 'active',
        ]);

        $pub2 = Publication::create([
            'creator_id' => $this->premiumUser->id,
            'network_type' => 'psychology',
            'publication_type' => 'university',
            'title' => 'Second Created Publication',
            'slug' => 'second-created-publication',
            'abstract' => 'Second abstract.',
            'website_url' => 'https://example.com/pub2',
            'status' => 'active',
        ]);

        $pub3 = Publication::create([
            'creator_id' => $this->premiumUser->id,
            'network_type' => 'psychology',
            'publication_type' => 'university',
            'title' => 'Third (Latest) Publication',
            'slug' => 'third-latest-publication',
            'abstract' => 'Third abstract.',
            'website_url' => 'https://example.com/pub3',
            'status' => 'active',
        ]);

        // Request page 1 with per_page=2
        $page1 = $this->actingAs($this->premiumUser, 'api')
            ->getJson('/api/publications/feed?network_type=psychology&publication_type=university&per_page=2');
        $page1->assertStatus(200);
        $page1->assertJsonCount(2, 'data');

        // Check latest is first
        $page1->assertJsonPath('data.0.id', $pub3->id);
        $page1->assertJsonPath('data.1.id', $pub2->id);
        $page1->assertJsonPath('pagination.has_more_pages', true);

        $nextCursor = $page1->json('pagination.next_cursor');
        $this->assertNotNull($nextCursor);

        // Request page 2 using next_cursor
        $page2 = $this->actingAs($this->premiumUser, 'api')
            ->getJson('/api/publications/feed?network_type=psychology&publication_type=university&per_page=2&cursor='.$nextCursor);
        $page2->assertStatus(200);
        $page2->assertJsonCount(1, 'data');
        $page2->assertJsonPath('data.0.id', $pub1->id);
        $page2->assertJsonPath('pagination.has_more_pages', false);
    }

    public function test_show_publication_records_unique_view_and_increments_views_count(): void
    {
        $publication = Publication::create([
            'creator_id' => $this->premiumUser->id,
            'industry_id' => null,
            'network_type' => 'psychology',
            'publication_type' => 'university',
            'title' => 'View Counting Test Study',
            'slug' => 'view-counting-test-study',
            'abstract' => 'Checking view tracking.',
            'website_url' => 'https://example.com/study',
            'status' => 'active',
            'views_count' => 0,
        ]);

        // First view by user A
        $this->actingAs($this->industryUser, 'api')
            ->getJson("/api/publications/{$publication->id}/details")
            ->assertStatus(200);
        $this->assertEquals(1, $publication->fresh()->views_count);

        // Same user A repeats - view should NOT increment
        $this->actingAs($this->industryUser, 'api')
            ->getJson("/api/publications/{$publication->id}/details")
            ->assertStatus(200);
        $this->assertEquals(1, $publication->fresh()->views_count);

        // Distinct user B views
        $this->actingAs($this->premiumUser, 'api')
            ->getJson("/api/publications/{$publication->id}/details")
            ->assertStatus(200);
        $this->assertEquals(2, $publication->fresh()->views_count);
    }

    public function test_toggle_like_updates_likes_count(): void
    {
        $publication = Publication::create([
            'creator_id' => $this->premiumUser->id,
            'industry_id' => null,
            'network_type' => 'psychology',
            'publication_type' => 'freelance',
            'title' => 'Liking System Test',
            'slug' => 'liking-system-test',
            'abstract' => 'Checking liking functionality.',
            'website_url' => 'https://example.com/like',
            'status' => 'active',
            'likes_count' => 0,
        ]);

        // Like
        $likeRes = $this->actingAs($this->industryUser, 'api')->postJson("/api/publications/{$publication->id}/like");
        $likeRes->assertStatus(200);
        $likeRes->assertJsonPath('data.is_liked', true);
        $likeRes->assertJsonPath('data.likes_count', 1);

        // Unlike
        $unlikeRes = $this->actingAs($this->industryUser, 'api')->postJson("/api/publications/{$publication->id}/like");
        $unlikeRes->assertStatus(200);
        $unlikeRes->assertJsonPath('data.is_liked', false);
        $unlikeRes->assertJsonPath('data.likes_count', 0);
    }

    public function test_author_can_update_and_delete_publication(): void
    {
        $publication = Publication::create([
            'creator_id' => $this->premiumUser->id,
            'industry_id' => null,
            'network_type' => 'psychology',
            'publication_type' => 'university',
            'title' => 'Original Study Title',
            'slug' => 'original-study-title',
            'abstract' => 'Original abstract.',
            'website_url' => 'https://example.com/orig',
            'status' => 'active',
        ]);

        // Update
        $updateRes = $this->actingAs($this->premiumUser, 'api')->putJson("/api/publications/{$publication->id}/update", [
            'title' => 'Updated Study Title',
            'abstract' => 'Updated abstract content.',
        ]);
        $updateRes->assertStatus(200);
        $updateRes->assertJsonPath('data.title', 'Updated Study Title');

        // Delete
        $deleteRes = $this->actingAs($this->premiumUser, 'api')->deleteJson("/api/publications/{$publication->id}/delete");
        $deleteRes->assertStatus(200);
        $this->assertSoftDeleted('publications', ['id' => $publication->id]);
    }
}
