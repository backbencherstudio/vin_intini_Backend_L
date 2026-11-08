<?php

namespace Tests\Feature\Api;

use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RevenueCatBoostSecurityP0Test extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $attacker;

    private IndustryProduct $product;

    private IndustryProduct $attackerProduct;

    private BoostPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $this->user = User::factory()->create(['is_verified' => true]);
        $this->user->assignRole($role);
        UserProfile::create(['user_id' => $this->user->id]);

        $this->attacker = User::factory()->create(['is_verified' => true]);
        $this->attacker->assignRole($role);
        UserProfile::create(['user_id' => $this->attacker->id]);

        $industry = Industry::create([
            'created_by' => $this->user->id,
            'name' => 'BioHealth Labs',
            'slug' => 'biohealth-labs',
            'industry' => 'biotechnology',
        ]);

        $attackerIndustry = Industry::create([
            'created_by' => $this->attacker->id,
            'name' => 'Attacker Labs',
            'slug' => 'attacker-labs',
            'industry' => 'biotechnology',
        ]);

        $section = IndustrySections::create([
            'name' => 'Therapeutics',
            'industry_type' => 'biotechnology',
            'network_type' => 'neuroscience',
        ]);

        $category = IndustryCategory::create([
            'section_id' => $section->id,
            'category_name' => 'Nootropics',
        ]);

        $this->product = IndustryProduct::create([
            'industry_id' => $industry->id,
            'creator_id' => $this->user->id,
            'section_id' => $section->id,
            'category_id' => $category->id,
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
            'product_name' => 'Alpha Cognitive',
            'description' => 'Alpha Cognitive clinical formula',
            'slug' => 'alpha-cognitive',
            'status' => 'active',
            'is_boosted' => false,
        ]);

        $this->attackerProduct = IndustryProduct::create([
            'industry_id' => $attackerIndustry->id,
            'creator_id' => $this->attacker->id,
            'section_id' => $section->id,
            'category_id' => $category->id,
            'network_type' => 'neuroscience',
            'industry_type' => 'biotechnology',
            'product_name' => 'Attacker Product',
            'description' => 'Attacker test product description',
            'slug' => 'attacker-product',
            'status' => 'active',
            'is_boosted' => false,
        ]);

        $this->package = BoostPackage::create([
            'name' => '7-Day Surge',
            'target_entity' => 'product',
            'duration_days' => 7,
            'price' => 19.99,
            'estimated_impressions' => '5,000',
            'badge' => 'Featured',
            'priority_weight' => 2,
            'status' => 'active',
            'revenuecat_store_identifier_ios' => 'boost_product_7d',
            'revenuecat_store_identifier_android' => 'boost_product_7d_gp',
            'revenuecat_entitlement_identifier' => 'product_boost_7d',
        ]);
    }

    public function test_b01_invented_transaction_id_fails(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
                'checkout_type' => 'revenuecat',
                'boost_package_id' => $this->package->id,
                'revenuecat_transaction_id' => 'invented_store_tx_123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['revenuecat_transaction_id']);

        $this->assertDatabaseMissing('ad_campaigns', [
            'revenuecat_transaction_id' => 'invented_store_tx_123',
        ]);
    }

    public function test_b01_refunded_transaction_fails(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
                'checkout_type' => 'revenuecat',
                'boost_package_id' => $this->package->id,
                'revenuecat_transaction_id' => 'refunded_store_tx_456',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['revenuecat_transaction_id']);
    }

    public function test_b01_wrong_product_transaction_fails(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
                'checkout_type' => 'revenuecat',
                'boost_package_id' => $this->package->id,
                'revenuecat_transaction_id' => 'wrong_product_store_tx_789',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['revenuecat_transaction_id']);
    }

    public function test_b01_sandbox_transaction_in_production_fails(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
                'checkout_type' => 'revenuecat',
                'boost_package_id' => $this->package->id,
                'revenuecat_transaction_id' => 'sandbox_in_prod_store_tx_001',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['revenuecat_transaction_id']);
    }

    public function test_b01_cross_account_replay_fails(): void
    {
        // First, User legitimately activates a purchase
        $validTx = 'rc_store_trans_valid_owner_test';
        $legit = $this->actingAs($this->user, 'api')
            ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
                'checkout_type' => 'revenuecat',
                'boost_package_id' => $this->package->id,
                'revenuecat_transaction_id' => $validTx,
            ]);
        $legit->assertStatus(201);

        // Attacker attempts to replay the same transaction on Attacker's product
        $replayAttacker = $this->actingAs($this->attacker, 'api')
            ->postJson("/api/industry/products/{$this->attackerProduct->id}/boost/purchase", [
                'checkout_type' => 'revenuecat',
                'boost_package_id' => $this->package->id,
                'revenuecat_transaction_id' => $validTx,
            ]);

        $replayAttacker->assertStatus(422)
            ->assertJsonValidationErrors(['revenuecat_transaction_id']);

        // Legitimate user replaying on same product receives original campaign without duplicate
        $replayOwner = $this->actingAs($this->user, 'api')
            ->postJson("/api/industry/products/{$this->product->id}/boost/purchase", [
                'checkout_type' => 'revenuecat',
                'boost_package_id' => $this->package->id,
                'revenuecat_transaction_id' => $validTx,
            ]);

        $replayOwner->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertEquals(1, AdCampaign::where('revenuecat_transaction_id', $validTx)->count());
    }
}
