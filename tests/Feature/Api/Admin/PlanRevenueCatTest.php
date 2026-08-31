<?php

namespace Tests\Feature\Api\Admin;

use App\Models\Plan;
use App\Models\User;
use App\Services\RevenueCatPlanSyncService;
use App\Services\RevenueCatService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stripe\Price;
use Stripe\Product;
use Tests\TestCase;

class PlanRevenueCatTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole($role);
    }

    public function test_admin_can_create_plan_without_revenuecat_fields(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createProduct')->andReturn(Product::constructFrom(['id' => 'prod_x']));
            $mock->shouldReceive('createPrice')->andReturn(Price::constructFrom(['id' => 'price_x']));
        });

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/plans/create', [
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.revenuecat_product_id', null)
            ->assertJsonPath('data.revenuecat_entitlement_id', null)
            ->assertJsonPath('data.revenuecat_store_identifier', null);
    }

    public function test_admin_can_update_plan_store_identifier_only(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createProduct')->andReturn(Product::constructFrom(['id' => 'prod_x']));
            $mock->shouldReceive('createPrice')->andReturn(Price::constructFrom(['id' => 'price_x']));
        });

        $plan = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/plans/create', [
                'name' => 'Pro',
                'billing_rate' => 9.99,
                'billing_cycle' => 'monthly',
                'status' => 'active',
                'features' => ['company_profile'],
            ])
            ->assertCreated()
            ->json('data');

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/plans/{$plan['id']}", [
                'revenuecat_store_identifier_ios' => 'com.app.pro.monthly',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.revenuecat_store_identifier_ios', 'com.app.pro.monthly')
            ->assertJsonPath('data.revenuecat_product_id', null)
            ->assertJsonPath('data.revenuecat_entitlement_id', null);
    }

    public function test_admin_plan_save_provisions_revenuecat_when_store_identifier_present(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createProduct')->andReturn(Product::constructFrom(['id' => 'prod_x']));
            $mock->shouldReceive('createPrice')->andReturn(Price::constructFrom(['id' => 'price_x']));
        });

        $this->mock(RevenueCatPlanSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sync')->once()->andReturnUsing(function (Plan $plan) {
                $plan->forceFill([
                    'revenuecat_product_id' => 'prod_rc_123',
                    'revenuecat_entitlement_id' => 'entl_rc_123',
                ])->save();
            });
        });

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/plans/create', [
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
            'revenuecat_store_identifier_ios' => 'com.app.pro.monthly',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.revenuecat_store_identifier_ios', 'com.app.pro.monthly')
            ->assertJsonPath('data.revenuecat_product_id', 'prod_rc_123')
            ->assertJsonPath('data.revenuecat_entitlement_id', 'entl_rc_123');
    }

    public function test_admin_plan_update_provisions_revenuecat(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createProduct')->andReturn(Product::constructFrom(['id' => 'prod_x']));
            $mock->shouldReceive('createPrice')->andReturn(Price::constructFrom(['id' => 'price_x']));
        });

        $plan = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/plans/create', [
                'name' => 'Pro',
                'billing_rate' => 9.99,
                'billing_cycle' => 'monthly',
                'status' => 'active',
                'features' => ['company_profile'],
            ])
            ->assertCreated()
            ->json('data');

        $this->mock(RevenueCatPlanSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sync')->once()->andReturnUsing(function (Plan $plan) {
                $plan->forceFill([
                    'revenuecat_product_id' => 'prod_rc_9',
                    'revenuecat_entitlement_id' => 'entl_rc_9',
                ])->save();
            });
        });

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/plans/{$plan['id']}", [
                'revenuecat_store_identifier_ios' => 'com.app.pro.monthly',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.revenuecat_product_id', 'prod_rc_9')
            ->assertJsonPath('data.revenuecat_entitlement_id', 'entl_rc_9');
    }

    public function test_sync_creates_a_product_per_platform(): void
    {
        config(['revenuecat.app_id_ios' => 'app_ios', 'revenuecat.app_id_android' => 'app_android']);

        $this->mock(RevenueCatService::class, function (MockInterface $mock) {
            $mock->shouldReceive('findEntitlementByLookupKey')->andReturn(null);
            $mock->shouldReceive('createEntitlement')->andReturn(['id' => 'entl_x']);
            $mock->shouldReceive('findProductByStoreIdentifier')->andReturn(null);
            $mock->shouldReceive('createProduct')->andReturnUsing(
                fn (string $store, string $app) => ['id' => 'prod_'.($app === 'app_ios' ? 'ios' : 'android')]
            );
            $mock->shouldReceive('attachProductToEntitlement')->andReturn(['id' => 'entl_x']);
            $mock->shouldReceive('findOfferingByLookupKey')->andReturn(null);
            $mock->shouldReceive('createOffering')->andReturn(['id' => 'ofr_x']);
            $mock->shouldReceive('findPackageByLookupKey')->andReturn(null);
            $mock->shouldReceive('createPackage')->andReturn(['id' => 'pkg_x']);
            $mock->shouldReceive('attachProductsToPackage')->andReturn(['id' => 'pkg_x']);
        });

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createProduct')->andReturn(Product::constructFrom(['id' => 'prod_x']));
            $mock->shouldReceive('createPrice')->andReturn(Price::constructFrom(['id' => 'price_x']));
        });

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/plans/create', [
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
        ]);

        $response->assertCreated();

        $data = $response->json('data');
        $this->assertSame('prod_ios', $data['revenuecat_product_id_ios']);
        $this->assertSame('prod_android', $data['revenuecat_product_id_android']);
        $this->assertSame('entl_x', $data['revenuecat_entitlement_id']);
        $this->assertSame('ofr_x', $data['revenuecat_offering_id']);
        $this->assertSame('pkg_x', $data['revenuecat_package_id']);
    }
}
