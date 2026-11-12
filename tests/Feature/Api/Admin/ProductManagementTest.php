<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Admin;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $adminRole;

    private Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $this->userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $this->admin = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin-products@test.com',
            'is_verified' => true,
        ]);
        $this->admin->assignRole($this->adminRole);
    }

    public function test_non_admin_cannot_access_admin_products_endpoints(): void
    {
        $regularUser = User::factory()->create(['is_verified' => true]);
        $regularUser->assignRole($this->userRole);

        $this->getJson('/api/admin/products?network_type=psychology&industry_type=biotechnology')
            ->assertUnauthorized();

        $this->actingAs($regularUser, 'api')
            ->getJson('/api/admin/products?network_type=psychology&industry_type=biotechnology')
            ->assertForbidden();
    }

    public function test_verified_industries_lists_only_verified_active_non_suspended_industries(): void
    {
        $verifiedOwner = User::factory()->create(['is_verified' => true, 'is_suspended' => false]);
        $verifiedOwner->assignRole($this->userRole);
        $verifiedIndustry = Industry::create([
            'name' => 'Verified Bio Labs',
            'slug' => 'verified-bio-labs',
            'industry' => 'biotechnology',
            'status' => 'active',
            'authorization_confirmed' => true,
            'authorization_confirmed_at' => now(),
            'created_by' => $verifiedOwner->id,
        ]);

        $pausedOwner = User::factory()->create(['is_verified' => true, 'is_suspended' => false]);
        Industry::create([
            'name' => 'Paused Bio Labs',
            'slug' => 'paused-bio-labs',
            'industry' => 'biotechnology',
            'status' => 'paused',
            'authorization_confirmed' => true,
            'authorization_confirmed_at' => now(),
            'created_by' => $pausedOwner->id,
        ]);

        $unconfirmedOwner = User::factory()->create(['is_verified' => true, 'is_suspended' => false]);
        Industry::create([
            'name' => 'Unconfirmed Labs',
            'slug' => 'unconfirmed-labs',
            'industry' => 'biotechnology',
            'status' => 'active',
            'authorization_confirmed' => false,
            'created_by' => $unconfirmedOwner->id,
        ]);

        $unverifiedOwner = User::factory()->create(['is_verified' => false, 'is_suspended' => false]);
        Industry::create([
            'name' => 'Unverified Owner Labs',
            'slug' => 'unverified-owner-labs',
            'industry' => 'biotechnology',
            'status' => 'active',
            'authorization_confirmed' => true,
            'authorization_confirmed_at' => now(),
            'created_by' => $unverifiedOwner->id,
        ]);

        $suspendedOwner = User::factory()->create(['is_verified' => true, 'is_suspended' => true]);
        Industry::create([
            'name' => 'Suspended Owner Labs',
            'slug' => 'suspended-owner-labs',
            'industry' => 'biotechnology',
            'status' => 'active',
            'authorization_confirmed' => true,
            'authorization_confirmed_at' => now(),
            'created_by' => $suspendedOwner->id,
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/products/verified-industries');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $verifiedIndustry->id)
            ->assertJsonPath('data.0.name', 'Verified Bio Labs');
    }

    public function test_admin_can_fetch_section_wise_products_and_create_under_verified_industry(): void
    {
        $owner = User::factory()->create(['is_verified' => true, 'is_suspended' => false]);
        $verifiedIndustry = Industry::create([
            'name' => 'NeuroMed Systems',
            'slug' => 'neuromed-systems',
            'industry' => 'biotechnology',
            'status' => 'active',
            'authorization_confirmed' => true,
            'authorization_confirmed_at' => now(),
            'created_by' => $owner->id,
        ]);

        $unverifiedOwner = User::factory()->create(['is_verified' => false, 'is_suspended' => false]);
        $unverifiedIndustry = Industry::create([
            'name' => 'Unverified Corp',
            'slug' => 'unverified-corp',
            'industry' => 'biotechnology',
            'status' => 'active',
            'authorization_confirmed' => true,
            'created_by' => $unverifiedOwner->id,
        ]);

        $section = IndustrySections::create([
            'name' => 'Neuroimaging Tools',
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
        ]);
        $category = IndustryCategory::create([
            'section_id' => $section->id,
            'category_name' => 'MRI Systems',
        ]);

        // Reject upload under unverified industry
        $this->actingAs($this->admin, 'api')
            ->post('/api/admin/products/create', [
                'industry_id' => $unverifiedIndustry->id,
                'product_name' => 'Blocked Product',
                'network_type' => 'neuroscience',
                'industry_type' => 'biotechnology',
                'section_id' => $section->id,
                'category_id' => $category->id,
                'description' => 'Should fail verification rule.',
                'image' => UploadedFile::fake()->image('blocked.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['industry_id']);

        // Succeed upload under verified industry
        $createResponse = $this->actingAs($this->admin, 'api')
            ->post('/api/admin/products/create', [
                'industry_id' => $verifiedIndustry->id,
                'product_name' => 'Cortical MRI Pro',
                'network_type' => 'neuroscience',
                'industry_type' => 'biotechnology',
                'section_id' => $section->id,
                'category_id' => $category->id,
                'description' => 'High-resolution cortical imaging system.',
                'product_url' => 'https://neuromed.example.com/cortical-mri',
                'tags' => 'MRI, Neuroimaging',
                'image' => UploadedFile::fake()->image('mri.jpg'),
            ], ['Accept' => 'application/json']);

        $createResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product_name', 'Cortical MRI Pro')
            ->assertJsonPath('data.industry.id', $verifiedIndustry->id)
            ->assertJsonPath('data.section_id', $section->id)
            ->assertJsonPath('data.category_id', $category->id);

        /** @var int $productId */
        $productId = (int) $createResponse->json('data.id');

        // Pause / deactivate product via admin status endpoint
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/products/{$productId}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        // Section-wise admin listing returns both inactive & active summary cleanly
        $indexResponse = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/products?network_type=neuroscience&industry_type=biotechnology');

        $indexResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.network_type', 'neuroscience')
            ->assertJsonPath('data.industry_type', 'biotechnology')
            ->assertJsonPath('data.summary.total_products', 1)
            ->assertJsonPath('data.summary.inactive_products', 1)
            ->assertJsonCount(1, 'data.sections')
            ->assertJsonPath('data.sections.0.id', $section->id)
            ->assertJsonPath('data.sections.0.categories.0.products_count', 1)
            ->assertJsonPath('data.sections.0.products.0.id', $productId);

        // Update product via admin
        $this->actingAs($this->admin, 'api')
            ->post("/api/admin/products/{$productId}/update", [
                'product_name' => 'Cortical MRI Pro v2',
                'status' => 'active',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.product_name', 'Cortical MRI Pro v2')
            ->assertJsonPath('data.status', 'active');

        // Delete product via admin
        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/products/{$productId}/delete")
            ->assertOk();

        $this->assertSoftDeleted(IndustryProduct::class, ['id' => $productId]);
    }

    public function test_admin_can_reject_product_with_reason_hide_from_platform_lock_industry_status_and_notify_owner(): void
    {
        $owner = User::factory()->create(['is_verified' => true, 'is_suspended' => false]);
        $owner->assignRole($this->userRole);

        $industry = Industry::create([
            'name' => 'Compliance Lab',
            'slug' => 'compliance-lab',
            'industry' => 'biotechnology',
            'status' => 'active',
            'authorization_confirmed' => true,
            'authorization_confirmed_at' => now(),
            'created_by' => $owner->id,
        ]);

        $section = IndustrySections::create([
            'name' => 'Diagnostics',
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
        ]);

        $category = IndustryCategory::create([
            'section_id' => $section->id,
            'category_name' => 'EEG Tools',
        ]);

        $product = IndustryProduct::create([
            'creator_id' => $owner->id,
            'industry_id' => $industry->id,
            'network_type' => 'psychology',
            'industry_type' => 'biotechnology',
            'section_id' => $section->id,
            'category_id' => $category->id,
            'product_name' => 'Unverified Claim Device',
            'slug' => 'unverified-claim-device-1234',
            'description' => 'Product violating platform policies.',
            'status' => 'active',
            'is_boosted' => true,
            'boost_expires_at' => now()->addDays(5),
        ]);

        $campaign = AdCampaign::create([
            'boostable_type' => $product->getMorphClass(),
            'boostable_id' => $product->id,
            'user_id' => $owner->id,
            'industry_id' => $industry->id,
            'ad_type' => 'product',
            'network_type' => 'psychology',
            'duration_days' => 5,
            'amount_paid' => 50,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDays(4),
            'status' => AdCampaignStatus::ACTIVE,
        ]);

        // Reason is mandatory
        $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/products/{$product->id}/reject", ['reason' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $reason = 'Contains prohibited medical claims violating safety compliance.';

        $rejectResponse = $this->actingAs($this->admin, 'api')
            ->postJson("/api/admin/products/{$product->id}/reject", ['reason' => $reason]);

        $rejectResponse->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', $reason)
            ->assertJsonPath('data.is_boosted', false);

        $campaign->refresh();
        $this->assertSame(AdCampaignStatus::CANCELLED, $campaign->status);

        // Notification created for industry owner
        $notification = $owner->notifications()->latest('created_at')->first();
        $this->assertNotNull($notification);
        $this->assertSame($reason, $notification->data['rejection_reason'] ?? null);

        // Hidden from public feed & public show
        $viewer = User::factory()->create(['is_verified' => true]);
        $viewer->assignRole($this->userRole);

        $this->actingAs($viewer, 'api')
            ->getJson('/api/industry/products/feed?network_type=psychology&industry_type=biotechnology')
            ->assertOk()
            ->assertJsonMissing(['product_name' => 'Unverified Claim Device']);

        $this->actingAs($viewer, 'api')
            ->getJson("/api/industry/products/{$product->id}/details")
            ->assertNotFound();

        // Industry owner cannot reactivate or boost the rejected product
        $this->actingAs($owner, 'api')
            ->patchJson("/api/industry/products/{$product->id}/status", ['status' => 'active'])
            ->assertStatus(403)
            ->assertJsonPath('rejection_reason', $reason);

        $this->actingAs($owner, 'api')
            ->postJson("/api/industry/products/{$product->id}/boost/send-otp", [])
            ->assertStatus(422)
            ->assertJsonPath('rejection_reason', $reason);

        // Admin filter summary counts rejected product and admin can restore cleanly
        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/products?network_type=psychology&industry_type=biotechnology&status=rejected')
            ->assertOk()
            ->assertJsonPath('data.summary.rejected_products', 1)
            ->assertJsonPath('data.sections.0.products.0.id', $product->id);

        // Non-rejected status transition does not trigger rejection-restore notification
        $notificationsBefore = $owner->notifications()->count();

        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/products/{$product->id}/status", ['status' => 'active'])
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.rejection_reason', null);

        $restoreNotification = $owner->notifications()
            ->where('type', \App\Notifications\ProductStatusUpdatedNotification::class)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($restoreNotification);
        $this->assertSame($notificationsBefore + 1, $owner->notifications()->count());
        $this->assertSame('rejected', $restoreNotification->data['previous_status'] ?? null);
        $this->assertSame('active', $restoreNotification->data['status'] ?? null);
        $this->assertSame($product->id, $restoreNotification->data['product_id'] ?? null);

        // Changing status of a non-rejected product does not duplicate restore notifications
        $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/products/{$product->id}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertSame($notificationsBefore + 1, $owner->notifications()->count());
    }
}

