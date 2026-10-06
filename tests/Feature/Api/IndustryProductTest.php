<?php

namespace Tests\Feature\Api;

use App\Enums\PlanType;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class IndustryProductTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    private Industry $industry;

    private IndustrySections $section;

    private IndustryCategory $subCategory1;

    private IndustryCategory $subCategory2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Roles
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

        // User & Industry
        $this->creator = User::factory()->create();
        $this->creator->assignRole('user');
        UserProfile::create(['user_id' => $this->creator->id]);

        $this->industry = Industry::create([
            'created_by' => $this->creator->id,
            'name' => 'BioPac Systems',
            'slug' => 'biopac-systems',
            'industry' => 'biotechnology',
            'status' => 'approved',
        ]);

        // Pro Industry Subscription for Creator
        $plan = Plan::create([
            'name' => 'Pro Industry Plan',
            'plan_type' => PlanType::INDUSTRY->value,
            'billing_rate' => 99.00,
            'billing_cycle' => 'monthly',
            'features' => ['advertisements'],
            'status' => 'active',
        ]);

        Subscription::create([
            'user_id' => $this->creator->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Section & Sub-Categories
        $this->section = IndustrySections::create([
            'name' => 'Neuroscientific and Psychophysiological Equipment',
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
        ]);

        $this->subCategory1 = IndustryCategory::create([
            'section_id' => $this->section->id,
            'category_name' => 'Brain Scanners',
        ]);

        $this->subCategory2 = IndustryCategory::create([
            'section_id' => $this->section->id,
            'category_name' => 'Physiological Monitoring Devices',
        ]);
    }

    public function test_user_without_subscription_cannot_create_product(): void
    {
        $nonSubscriber = User::factory()->create();
        $nonSubscriber->assignRole('user');
        UserProfile::create(['user_id' => $nonSubscriber->id]);

        $response = $this->actingAs($nonSubscriber, 'api')
            ->postJson('/api/industry/advertisements/create', [
                'product_name' => 'Test Product',
                'network_type' => 'psychology',
                'industry_type' => 'biotechnology',
                'section_id' => $this->section->id,
                'category_id' => $this->subCategory1->id,
                'description' => 'Test description',
                'product_url' => 'https://example.com/product',
                'image' => UploadedFile::fake()->image('product.jpg'),
                'information_confirmed' => true,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_subscribed_user_can_create_product(): void
    {
        $payload = [
            'product_name' => 'BioPac MP160',
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory1->id,
            'description' => 'High resolution functional brain imaging with real time analysis.',
            'product_url' => 'https://biopac.com/mp160',
            'image' => UploadedFile::fake()->image('mp160.jpg'),
            'tags' => ['Biotechnology', 'Brain Health'],
            'information_confirmed' => true,
        ];

        $response = $this->actingAs($this->creator, 'api')
            ->postJson('/api/industry/advertisements/create', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product_name', 'BioPac MP160')
            ->assertJsonPath('data.creator_id', $this->creator->id)
            ->assertJsonPath('data.industry_id', $this->industry->id)
            ->assertJsonPath('data.views_count', 0)
            ->assertJsonPath('data.likes_count', 0);

        $this->assertDatabaseHas('industry_products', [
            'product_name' => 'BioPac MP160',
            'creator_id' => $this->creator->id,
            'industry_id' => $this->industry->id,
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory1->id,
        ]);
    }

    public function test_feed_displays_all_tab_and_sub_category_filtering(): void
    {
        // Product in Sub-Category 1
        $prod1 = IndustryProduct::create([
            'creator_id' => $this->creator->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory1->id,
            'product_name' => 'Scanner X1',
            'slug' => 'scanner-x1-abc',
            'description' => 'Brain scanner description',
            'product_url' => 'https://example.com/scanner',
            'information_confirmed' => true,
            'status' => 'active',
        ]);

        // Product in Sub-Category 2
        $prod2 = IndustryProduct::create([
            'creator_id' => $this->creator->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory2->id,
            'product_name' => 'Monitoring Device Y2',
            'slug' => 'device-y2-def',
            'description' => 'Physiological device description',
            'product_url' => 'https://example.com/device',
            'information_confirmed' => true,
            'status' => 'active',
        ]);

        // 1. All tab: Filter by section_id only -> returns BOTH products!
        $allTabResponse = $this->actingAs($this->creator, 'api')
            ->getJson("/api/industry/products/feed?network_type=psychology&industry_type=biotechnology&section_id={$this->section->id}");

        $allTabResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        // 2. Specific Sub-category tab: Filter by section_id AND category_id -> returns ONLY prod1!
        $subCatResponse = $this->actingAs($this->creator, 'api')
            ->getJson("/api/industry/products/feed?network_type=psychology&industry_type=biotechnology&section_id={$this->section->id}&category_id={$this->subCategory1->id}");

        $subCatResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $prod1->id);

        // 3. Section-wise Feed on initial page load: Returns sections with tabs & products
        $sectionFeedResponse = $this->actingAs($this->creator, 'api')
            ->getJson('/api/industry/products/feed?network_type=psychology&industry_type=biotechnology');

        $sectionFeedResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.section_id', $this->section->id)
            ->assertJsonPath('data.0.section_name', $this->section->name)
            ->assertJsonCount(2, 'data.0.categories') // Brain Scanners, Physiological Monitoring Devices
            ->assertJsonCount(2, 'data.0.products');  // Both products under this section
    }

    public function test_unique_view_count_once_per_user(): void
    {
        $product = IndustryProduct::create([
            'creator_id' => $this->creator->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory1->id,
            'product_name' => 'View Test Product',
            'slug' => 'view-test-slug',
            'description' => 'Testing views',
            'product_url' => 'https://example.com',
            'information_confirmed' => true,
            'status' => 'active',
            'views_count' => 0,
        ]);

        $viewer = User::factory()->create();
        $viewer->assignRole('user');
        UserProfile::create(['user_id' => $viewer->id]);

        // First view by viewer -> views_count becomes 1
        $this->actingAs($viewer, 'api')
            ->getJson("/api/industry/products/{$product->id}/details")
            ->assertOk()
            ->assertJsonPath('data.views_count', 1);

        $this->assertEquals(1, $product->fresh()->views_count);

        // Second view by same viewer -> views_count remains 1 (not duplicated!)
        $this->actingAs($viewer, 'api')
            ->getJson("/api/industry/products/{$product->id}/details")
            ->assertOk()
            ->assertJsonPath('data.views_count', 1);

        $this->assertEquals(1, $product->fresh()->views_count);
    }

    public function test_like_button_toggles_like_and_records_view_if_not_viewed(): void
    {
        $product = IndustryProduct::create([
            'creator_id' => $this->creator->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory1->id,
            'product_name' => 'Like Test Product',
            'slug' => 'like-test-slug',
            'description' => 'Testing likes and views',
            'product_url' => 'https://example.com',
            'information_confirmed' => true,
            'status' => 'active',
            'views_count' => 0,
            'likes_count' => 0,
        ]);

        $user = User::factory()->create();
        $user->assignRole('user');
        UserProfile::create(['user_id' => $user->id]);

        // User clicks like without visiting details page
        $likeResponse = $this->actingAs($user, 'api')
            ->postJson("/api/industry/products/{$product->id}/like");

        $likeResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_liked', true)
            ->assertJsonPath('data.likes_count', 1)
            ->assertJsonPath('data.views_count', 1); // View was automatically counted!

        $this->assertDatabaseHas('industry_product_likes', [
            'industry_product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('industry_product_views', [
            'industry_product_id' => $product->id,
            'user_id' => $user->id,
        ]);

        // User clicks like again to unlike -> likes_count becomes 0, views_count stays 1
        $unlikeResponse = $this->actingAs($user, 'api')
            ->postJson("/api/industry/products/{$product->id}/like");

        $unlikeResponse->assertOk()
            ->assertJsonPath('data.is_liked', false)
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.views_count', 1);
    }

    public function test_advertisement_dashboard_metrics_and_listings(): void
    {
        IndustryProduct::create([
            'creator_id' => $this->creator->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory1->id,
            'product_name' => 'Dash Product 1',
            'slug' => 'dash-prod-1',
            'description' => 'Desc 1',
            'product_url' => 'https://example.com',
            'information_confirmed' => true,
            'status' => 'active',
            'views_count' => 150,
            'likes_count' => 45,
        ]);

        IndustryProduct::create([
            'creator_id' => $this->creator->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory2->id,
            'product_name' => 'Dash Product 2',
            'slug' => 'dash-prod-2',
            'description' => 'Desc 2',
            'product_url' => 'https://example.com',
            'information_confirmed' => true,
            'status' => 'active',
            'views_count' => 50,
            'likes_count' => 15,
        ]);

        $dashResponse = $this->actingAs($this->creator, 'api')
            ->getJson('/api/industry/advertisements/dashboard');

        $dashResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_advertisements', 2)
            ->assertJsonPath('data.total_likes', 60)
            ->assertJsonPath('data.total_views', 200);

        $listingsResponse = $this->actingAs($this->creator, 'api')
            ->getJson('/api/industry/advertisements/my-listings');

        $listingsResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_product_survives_when_creator_deletes_account(): void
    {
        $product = IndustryProduct::create([
            'creator_id' => $this->creator->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory1->id,
            'product_name' => 'Persistent Product',
            'slug' => 'persistent-prod',
            'description' => 'Product survives user deletion',
            'product_url' => 'https://example.com',
            'information_confirmed' => true,
            'status' => 'active',
        ]);

        // Creator deletes account (forceDelete triggers MySQL ON DELETE SET NULL)
        $this->creator->forceDelete();

        // Product still exists in database!
        $this->assertDatabaseHas('industry_products', [
            'id' => $product->id,
            'creator_id' => null,
            'industry_id' => $this->industry->id,
        ]);

        // System Admin can still update it!
        $admin = User::factory()->create();
        $admin->assignRole(['admin', 'user']);

        $this->actingAs($admin, 'api')
            ->postJson("/api/industry/advertisements/{$product->id}/update", [
                'product_name' => 'Persistent Product Updated by Admin',
            ])
            ->assertOk()
            ->assertJsonPath('data.product_name', 'Persistent Product Updated by Admin');
    }

    public function test_destroy_deletes_product_and_removes_image_from_storage(): void
    {
        Storage::fake('public');
        $imagePath = 'products/sample_product_to_delete.webp';
        Storage::disk('public')->put($imagePath, 'dummy image content');

        $product = IndustryProduct::create([
            'creator_id' => $this->creator->id,
            'industry_id' => $this->industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $this->section->id,
            'category_id' => $this->subCategory1->id,
            'product_name' => 'Delete Image Product',
            'slug' => 'delete-img-prod',
            'description' => 'Test image deletion on destroy',
            'product_url' => 'https://example.com',
            'image' => $imagePath,
            'information_confirmed' => true,
            'status' => 'active',
        ]);

        $this->assertTrue(Storage::disk('public')->exists($imagePath));

        $response = $this->actingAs($this->creator, 'api')
            ->deleteJson("/api/industry/advertisements/{$product->id}/delete");

        $response->assertOk()
            ->assertJsonPath('success', true);

        // Product is soft deleted
        $this->assertSoftDeleted('industry_products', ['id' => $product->id]);

        // Image file is completely removed from storage
        $this->assertFalse(Storage::disk('public')->exists($imagePath));
    }

    public function test_unique_6_digit_product_id_generated_and_trackable(): void
    {
        $file = UploadedFile::fake()->image('scanner.jpg', 600, 600);

        // 1. Create product via API
        $createResponse = $this->actingAs($this->creator, 'api')
            ->postJson('/api/industry/advertisements/create', [
                'network_type' => 'psychology',
                'industry_type' => 'biotechnology',
                'section_id' => $this->section->id,
                'category_id' => $this->subCategory1->id,
                'product_name' => 'Trackable 6-digit Product',
                'description' => 'Product with 6 digit unique tracking ID',
                'product_url' => 'https://example.com/item',
                'image' => $file,
                'information_confirmed' => true,
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('success', true);

        $productId = $createResponse->json('data.product_id');

        // Verify product_id is exactly 6 digits numeric string
        $this->assertNotNull($productId);
        $this->assertMatchesRegularExpression('/^[1-9][0-9]{5}$/', (string) $productId);

        // 2. Fetch details using the 6-digit product_id
        $detailsResponse = $this->actingAs($this->creator, 'api')
            ->getJson("/api/industry/products/{$productId}/details");

        $detailsResponse->assertOk()
            ->assertJsonPath('data.product_id', $productId)
            ->assertJsonPath('data.product_name', 'Trackable 6-digit Product');

        // 3. Toggle like using the 6-digit product_id
        $likeResponse = $this->actingAs($this->creator, 'api')
            ->postJson("/api/industry/products/{$productId}/like");

        $likeResponse->assertOk()
            ->assertJsonPath('data.is_liked', true);

        // 4. Search by the 6-digit product_id in feed
        $feedSearchResponse = $this->actingAs($this->creator, 'api')
            ->getJson("/api/industry/products/feed?network_type=psychology&industry_type=biotechnology&search={$productId}");

        $feedSearchResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_id', $productId);
    }

    public function test_poc_fields_can_be_stored_retrieved_and_updated(): void
    {
        $file = UploadedFile::fake()->image('poc_test.jpg', 600, 600);

        // 1. Create with POC fields
        $createResponse = $this->actingAs($this->creator, 'api')
            ->postJson('/api/industry/advertisements/create', [
                'network_type' => 'psychology',
                'industry_type' => 'biotechnology',
                'section_id' => $this->section->id,
                'category_id' => $this->subCategory1->id,
                'product_name' => 'POC Product Test',
                'description' => 'Test with Person of Contact',
                'product_url' => 'https://example.com/item',
                'image' => $file,
                'poc_name' => 'Sheikh Muhammad Ashik',
                'poc_email' => 'smashik@company.com',
                'poc_phone' => '+1 234 5678 87',
                'information_confirmed' => true,
            ]);

        $createResponse->assertCreated()
            ->assertJsonPath('success', true);

        $productId = $createResponse->json('data.id');

        $this->assertDatabaseHas('industry_products', [
            'id' => $productId,
            'poc_name' => 'Sheikh Muhammad Ashik',
            'poc_email' => 'smashik@company.com',
            'poc_phone' => '+1 234 5678 87',
        ]);

        // 2. Details response contains POC fields
        $detailsResponse = $this->actingAs($this->creator, 'api')
            ->getJson("/api/industry/products/{$productId}/details");

        $detailsResponse->assertOk()
            ->assertJsonPath('data.poc_name', 'Sheikh Muhammad Ashik')
            ->assertJsonPath('data.poc_email', 'smashik@company.com')
            ->assertJsonPath('data.poc_phone', '+1 234 5678 87');

        // 3. Update POC fields
        $updateResponse = $this->actingAs($this->creator, 'api')
            ->postJson("/api/industry/advertisements/{$productId}/update", [
                'poc_name' => 'Updated Contact Person',
                'poc_email' => 'updated@company.com',
                'poc_phone' => '+880 1712 345678',
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.poc_name', 'Updated Contact Person')
            ->assertJsonPath('data.poc_email', 'updated@company.com')
            ->assertJsonPath('data.poc_phone', '+880 1712 345678');

        $this->assertDatabaseHas('industry_products', [
            'id' => $productId,
            'poc_name' => 'Updated Contact Person',
            'poc_email' => 'updated@company.com',
            'poc_phone' => '+880 1712 345678',
        ]);
    }

    public function test_dropdown_sections_and_section_categories_for_dependent_dropdowns(): void
    {
        // 1. Get plain sections list
        $sectionsResponse = $this->actingAs($this->creator, 'api')
            ->getJson('/api/industry/advertisements/sections?network_type=psychology&industry_type=biotechnology');

        $sectionsResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => $this->section->id,
                'name' => $this->section->name,
            ]);

        // 2. Reject publications as industry_type
        $invalidTypeResponse = $this->actingAs($this->creator, 'api')
            ->getJson('/api/industry/advertisements/sections?network_type=psychology&industry_type=publications');

        $invalidTypeResponse->assertUnprocessable()
            ->assertJsonValidationErrors(['industry_type']);

        // 3. Get categories for a specific section
        $sectionCatResponse = $this->actingAs($this->creator, 'api')
            ->getJson("/api/industry/advertisements/sections/{$this->section->id}/categories");

        $sectionCatResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.section_id', $this->section->id)
            ->assertJsonCount(2, 'data.categories');
    }
}
