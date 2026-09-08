<?php

namespace Tests\Feature\Api\Admin;

use App\Jobs\ProvisionPlan;
use App\Models\Plan;
use App\Models\User;
use App\Services\RevenueCatService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

    private function mockStripe(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createProduct')->andReturn(Product::constructFrom(['id' => 'prod_x']));
            $mock->shouldReceive('createPrice')->andReturn(Price::constructFrom(['id' => 'price_x']));
        });
    }

    public function test_admin_can_create_plan_without_revenuecat_fields(): void
    {
        $this->mockStripe();

        Queue::fake(ProvisionPlan::class);

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/plans/create', [
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('data.revenuecat_product_id')
            ->assertJsonMissingPath('data.revenuecat_entitlement_id')
            ->assertJsonMissingPath('data.revenuecat_store_identifier');

        $plan = Plan::first();
        Queue::assertPushed(ProvisionPlan::class, fn (ProvisionPlan $job) => $job->plan->is($plan));
    }

    public function test_admin_plan_save_with_store_identifier_dispatches_sync_job(): void
    {
        $this->mockStripe();

        Queue::fake(ProvisionPlan::class);

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/plans/create', [
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
            'revenuecat_store_identifier_ios' => 'com.app.pro.monthly',
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('data.revenuecat_store_identifier_ios');

        $plan = Plan::first();
        $this->assertSame('com.app.pro.monthly', $plan->revenuecat_store_identifier_ios);
        Queue::assertPushed(ProvisionPlan::class, fn (ProvisionPlan $job) => $job->plan->is($plan));
    }

    public function test_admin_can_update_plan_dispatches_sync_job(): void
    {
        $this->mockStripe();

        Queue::fake(ProvisionPlan::class);

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
            ->assertJsonMissingPath('data.revenuecat_store_identifier_ios');

        $this->assertSame('com.app.pro.monthly', Plan::find($plan['id'])->revenuecat_store_identifier_ios);

        Queue::assertPushed(ProvisionPlan::class, 2);
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

        $this->mockStripe();

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/plans/create', [
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('data.revenuecat_product_id_ios');

        $plan = Plan::first();
        $this->assertSame('prod_ios', $plan->revenuecat_product_id_ios);
        $this->assertSame('prod_android', $plan->revenuecat_product_id_android);
        $this->assertSame('entl_x', $plan->revenuecat_entitlement_id);
        $this->assertSame('ofr_x', $plan->revenuecat_offering_id);
        $this->assertSame('pkg_x', $plan->revenuecat_package_id);
    }
}
