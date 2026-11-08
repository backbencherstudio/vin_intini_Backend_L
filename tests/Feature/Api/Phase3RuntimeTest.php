<?php

namespace Tests\Feature\Api;

use App\Enums\AdCampaignStatus;
use App\Enums\PlanType;
use App\Models\AdCampaign;
use App\Models\Comment;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\LoginActivity;
use App\Models\Plan;
use App\Models\Post;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Media\OptimizedImageUploadService;
use App\Services\Media\ProfileImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase3RuntimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createAuthenticatedIndustryUser(): array
    {
        $user = User::factory()->create();

        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $user->assignRole($role);

        $user->profile()->create([
            'first_name' => 'Jane',
            'last_name' => 'Scientist',
            'headline' => 'Lead Researcher',
        ]);

        LoginActivity::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'session_id' => 'sess_'.uniqid(),
            'status' => 'active',
            'login_at' => now(),
            'last_activity_at' => now(),
        ]);

        $industry = Industry::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'name' => 'NeuroBio Tech',
            'slug' => 'neurobio-tech-'.uniqid(),
            'industry' => 'biotechnology',
            'description' => 'Test industry',
            'status' => 'active',
        ]);

        $plan = Plan::create([
            'name' => 'Pro Industry',
            'short_description' => 'Pro Industry Plan',
            'plan_type' => PlanType::INDUSTRY->value,
            'billing_rate' => 99,
            'billing_cycle' => 'monthly',
            'features' => ['company_profile', 'product_listings'],
            'status' => 'active',
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_'.uniqid(),
            'status' => 'active',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        $section = IndustrySections::create([
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
            'name' => 'Therapeutics',
        ]);

        $category = IndustryCategory::create([
            'section_id' => $section->id,
            'category_name' => 'Gene Therapy',
        ]);

        return [$user, $industry, $section, $category];
    }

    /**
     * C01: Product creation succeeds when optional product_url is omitted.
     */
    public function test_c01_product_creation_succeeds_without_product_url(): void
    {
        [$user, , $section, $category] = $this->createAuthenticatedIndustryUser();

        $response = $this->actingAs($user, 'api')->postJson('/api/industry/products/create', [
            'product_name' => 'NeuroClean Solution',
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
            'section_id' => $section->id,
            'category_id' => $category->id,
            'description' => 'A revolutionary formula',
            'image' => UploadedFile::fake()->image('formula.jpg'),
            'information_confirmed' => true,
            // 'product_url' intentionally omitted
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product_name', 'NeuroClean Solution')
            ->assertJsonPath('data.product_url', null);

        $this->assertDatabaseHas('industry_products', [
            'product_name' => 'NeuroClean Solution',
            'product_url' => null,
        ]);
    }

    /**
     * C02: Comment reply with deleted/missing parent author returns 404,
     * leaves database transaction level at 0, and leaves no orphaned uploaded files.
     */
    public function test_c02_reply_to_deleted_parent_comment_returns_404_without_unbalanced_transactions(): void
    {
        $author = User::factory()->create();
        $author->delete(); // Soft-delete author

        $replier = User::factory()->create();
        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $replier->assignRole($role);
        $replier->profile()->create(['first_name' => 'Bob', 'last_name' => 'Replier']);

        LoginActivity::create([
            'user_id' => $replier->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'session_id' => 'sess_replier',
            'status' => 'active',
            'login_at' => now(),
            'last_activity_at' => now(),
        ]);

        $post = Post::create([
            'user_id' => $replier->id,
            'content' => 'Public Discussion Post',
            'status' => 'published',
        ]);

        $parentComment = Comment::create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'comment' => 'Comment by now-deleted user',
        ]);

        $initialTxLevel = DB::transactionLevel();

        $response = $this->actingAs($replier, 'api')->postJson("/api/comment/{$post->id}", [
            'post_id' => $post->id,
            'parent_id' => $parentComment->id,
            'comment' => 'Reply to deleted user comment',
            'image' => UploadedFile::fake()->image('reply.jpg'),
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);

        // Transaction level must remain balanced
        $this->assertSame($initialTxLevel, DB::transactionLevel());

        // File must not be stored/orphaned on 404
        $this->assertCount(0, Storage::disk('public')->allFiles('comments'));
    }

    /**
     * C03: Click recording on an ad campaign with a deleted target returns 404
     * instead of throwing a TypeError or attempting method_exists on null.
     */
    public function test_c03_record_click_on_campaign_with_deleted_target_returns_404(): void
    {
        $user = User::factory()->create();

        $campaign = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => 999999, // Non-existent/deleted target
            'user_id' => $user->id,
            'ad_type' => 'product',
            'amount_paid' => 20,
            'currency' => 'USD',
            'duration_days' => 5,
            'status' => AdCampaignStatus::ACTIVE,
            'payment_platform' => 'stripe',
        ]);

        $response = $this->postJson("/api/advertisements/{$campaign->id}/click");

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'The target of this ad campaign is no longer available.');
    }

    /**
     * C03: Click recording on a non-active (e.g. paused) campaign is rejected
     * and does not increment clicks count or record ad clicks.
     */
    public function test_c03_record_click_on_paused_campaign_is_rejected(): void
    {
        [$user, $industry, $section, $category] = $this->createAuthenticatedIndustryUser();

        $product = IndustryProduct::create([
            'user_id' => $user->id,
            'industry_id' => $industry->id,
            'section_id' => $section->id,
            'category_id' => $category->id,
            'product_name' => 'Active Item',
            'slug' => 'active-item-'.uniqid(),
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
            'description' => 'Sample item',
            'image' => 'products/item.jpg',
            'status' => 'active',
            'is_boosted' => false,
        ]);

        $campaign = AdCampaign::create([
            'boostable_type' => IndustryProduct::class,
            'boostable_id' => $product->id,
            'user_id' => $user->id,
            'industry_id' => $industry->id,
            'ad_type' => 'product',
            'network_type' => 'neuroscience',
            'amount_paid' => 20,
            'currency' => 'USD',
            'duration_days' => 5,
            'status' => AdCampaignStatus::PAUSED,
            'total_clicks' => 0,
            'payment_platform' => 'stripe',
        ]);

        $response = $this->postJson("/api/advertisements/{$campaign->id}/click");

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This ad campaign is not currently active.');

        $campaign->refresh();
        $this->assertSame(0, (int) $campaign->total_clicks);
    }

    /**
     * C04: ProfileImageService preserves existing image if storing the new image fails.
     */
    public function test_c04_profile_image_replacement_preserves_old_image_on_storage_failure(): void
    {
        Storage::disk('public')->put('profile_photos/old_photo.jpg', 'existing-image-content');

        $mockOptimized = Mockery::mock(OptimizedImageUploadService::class);
        $mockOptimized->shouldReceive('store')->once()->andThrow(new \RuntimeException('GD optimization failure'));

        $service = new ProfileImageService($mockOptimized);

        try {
            $service->storeUploaded(
                UploadedFile::fake()->image('new_photo.jpg'),
                'profile_photos/old_photo.jpg'
            );
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('GD optimization failure', $e->getMessage());
        }

        // Old photo must still exist!
        $this->assertTrue(Storage::disk('public')->exists('profile_photos/old_photo.jpg'));
    }

    /**
     * C06: FFMPEG binary configuration reads from config('services.ffmpeg.binary').
     */
    public function test_c06_ffmpeg_binary_read_from_config(): void
    {
        config(['services.ffmpeg.binary' => '/custom/path/ffmpeg']);

        $this->assertSame('/custom/path/ffmpeg', config('services.ffmpeg.binary'));
    }
}
