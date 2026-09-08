<?php

namespace Tests\Feature\Api\Admin;

use App\Jobs\ProvisionPlan;
use App\Models\Plan;
use App\Services\RevenueCatPlanSyncService;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Stripe\Price;
use Stripe\Product;
use Tests\TestCase;

class ProvisionPlanJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_provisions_stripe_product_and_price_then_syncs_revenuecat(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createProduct')->once()->andReturn(Product::constructFrom(['id' => 'prod_job']));
            $mock->shouldReceive('createPrice')->once()->andReturn(Price::constructFrom(['id' => 'price_job']));
        });

        $this->mock(RevenueCatPlanSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sync')->once()->with(Mockery::type(Plan::class));
        });

        $plan = Plan::create([
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
        ]);

        ProvisionPlan::dispatchSync($plan);

        $plan->refresh();
        $this->assertSame('prod_job', $plan->stripe_product_id);
        $this->assertSame('price_job', $plan->stripe_price_id);
    }

    public function test_job_updates_stripe_product_when_name_changes(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('updateProduct')
                ->once()
                ->with('prod_x', ['name' => 'New Name'])
                ->andReturn(Product::constructFrom(['id' => 'prod_x']));
        });

        $this->mock(RevenueCatPlanSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sync')->once();
        });

        $plan = Plan::create([
            'name' => 'Old Name',
            'billing_rate' => 10.00,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
            'stripe_product_id' => 'prod_x',
            'stripe_price_id' => 'price_x',
        ]);

        ProvisionPlan::dispatchSync($plan, true, ['name' => 'New Name'], false);
    }

    public function test_job_recreates_stripe_price_when_billing_changes(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('archivePrice')->once()->with('price_x')->andReturn(Price::constructFrom(['id' => 'price_x']));
            $mock->shouldReceive('createPrice')->once()->with(2000, 'usd', 'month', 'prod_x', Mockery::type('array'))->andReturn(Price::constructFrom(['id' => 'price_new']));
        });

        $this->mock(RevenueCatPlanSyncService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sync')->once();
        });

        $plan = Plan::create([
            'name' => 'Pro',
            'billing_rate' => 10.00,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['company_profile'],
            'stripe_product_id' => 'prod_x',
            'stripe_price_id' => 'price_x',
        ]);

        $plan->update(['billing_rate' => 20.00]);

        ProvisionPlan::dispatchSync($plan, false, [], true);

        $plan->refresh();
        $this->assertSame('price_new', $plan->stripe_price_id);
    }
}
