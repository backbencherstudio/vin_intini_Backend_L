<?php

namespace Tests\Feature\Api\Admin;

use App\Enums\PlanType;
use App\Jobs\ProvisionPlan;
use App\Models\Plan;
use App\Models\User;
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

    public function test_admin_can_create_plan_with_store_identifiers(): void
    {
        $this->mockStripe();

        Queue::fake(ProvisionPlan::class);

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/plans/create', [
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'plan_type' => 'premium',
            'features' => ['company_profile'],
            'revenuecat_store_identifier_ios' => 'com.app.pro.monthly',
            'revenuecat_store_identifier_android' => 'com.app.pro.monthly',
            'revenuecat_entitlement_identifier' => 'premium',
        ]);

        $response->assertCreated();

        $plan = Plan::first();
        $this->assertSame('com.app.pro.monthly', $plan->revenuecat_store_identifier_ios);
        $this->assertSame('com.app.pro.monthly', $plan->revenuecat_store_identifier_android);
        $this->assertSame('premium', $plan->revenuecat_entitlement_identifier);
        $this->assertTrue($plan->isRevenueCat());
        $this->assertSame(PlanType::PREMIUM, $plan->plan_type);
    }

    public function test_admin_can_update_plan_store_identifiers(): void
    {
        $this->mockStripe();

        Queue::fake(ProvisionPlan::class);

        $plan = Plan::create([
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'plan_type' => 'premium',
            'features' => ['company_profile'],
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->patchJson("/api/admin/plans/{$plan->id}", [
                'revenuecat_store_identifier_ios' => 'com.app.pro.monthly',
                'revenuecat_entitlement_identifier' => 'premium',
            ]);

        $response->assertOk();

        $plan->refresh();
        $this->assertSame('com.app.pro.monthly', $plan->revenuecat_store_identifier_ios);
        $this->assertSame('premium', $plan->revenuecat_entitlement_identifier);
        $this->assertTrue($plan->isRevenueCat());
    }

    public function test_plan_is_revenuecat_only_when_store_identifier_present(): void
    {
        $stripeOnly = Plan::create([
            'name' => 'Stripe',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'plan_type' => 'premium',
            'features' => ['company_profile'],
        ]);

        $this->assertFalse($stripeOnly->isRevenueCat());

        $stripeOnly->update(['revenuecat_store_identifier_ios' => 'com.app.pro.monthly']);

        $this->assertTrue($stripeOnly->refresh()->isRevenueCat());
    }
}
