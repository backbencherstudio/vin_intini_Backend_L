<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Exception\ApiConnectionException;
use Tests\TestCase;

class SubscriptionFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $this->user = User::factory()->create([
            'is_verified' => true,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $this->user->assignRole($role);
        UserProfile::create(['user_id' => $this->user->id]);
    }

    public function test_user_can_fetch_only_active_plans(): void
    {
        Plan::create([
            'name' => 'Free', 'billing_rate' => 0, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
        ]);
        Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles', 'unlimited_direct_messaging'],
        ]);
        Plan::create([
            'name' => 'Hidden', 'billing_rate' => 5, 'billing_cycle' => 'monthly',
            'status' => 'inactive', 'features' => ['search_profiles'],
        ]);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/plans');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Free')
            ->assertJsonPath('data.1.name', 'Pro');
    }

    public function test_user_can_create_subscription_checkout_session(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
            'stripe_product_id' => 'prod_1', 'stripe_price_id' => 'price_1',
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($plan) {
            $mock->shouldReceive('getOrCreateCustomer')->once()->andReturn(
                Customer::constructFrom(['id' => 'cus_mock']),
            );
            $mock->shouldReceive('createCheckoutSession')->once()
                ->withArgs(fn ($planArg, $userArg, $customerId) => $planArg->is($plan) && $customerId === 'cus_mock')
                ->andReturn(Session::constructFrom([
                    'id' => 'cs_mock',
                    'url' => 'https://checkout.stripe.com/c/pay/cs_mock',
                ]));
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.checkout_url', 'https://checkout.stripe.com/c/pay/cs_mock')
            ->assertJsonPath('data.session_id', 'cs_mock');

        $this->assertDatabaseMissing('subscriptions', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_cannot_create_subscription_when_already_active(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
            'stripe_product_id' => 'prod_1', 'stripe_price_id' => 'price_1',
        ]);

        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_active',
            'status' => 'active',
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getOrCreateCustomer')->never();
            $mock->shouldReceive('createCheckoutSession')->never();
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_create_returns_friendly_error_when_stripe_fails(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
            'stripe_product_id' => 'prod_1', 'stripe_price_id' => 'price_1',
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getOrCreateCustomer')->once()->andThrow(
                new ApiConnectionException('Stripe is down'),
            );
            $mock->shouldReceive('createCheckoutSession')->never();
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Failed to create checkout session. Please try again.');
    }

    public function test_user_cannot_subscribe_to_inactive_plan(): void
    {
        $plan = Plan::create([
            'name' => 'Old', 'billing_rate' => 5, 'billing_cycle' => 'monthly',
            'status' => 'inactive', 'features' => ['search_profiles'],
            'stripe_price_id' => 'price_1',
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getOrCreateCustomer')->never();
            $mock->shouldReceive('createCheckoutSession')->never();
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_status_returns_inactive_when_no_subscription(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/subscriptions/status');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.isActive', false)
            ->assertJsonPath('data.plan', null);
    }

    public function test_status_returns_plan_and_expiry_for_active_subscription(): void
    {
        $plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles', 'unlimited_direct_messaging'],
        ]);

        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
            'current_period_end' => now()->addDays(20),
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/subscriptions/status');

        $response
            ->assertOk()
            ->assertJsonPath('data.isActive', true)
            ->assertJsonPath('data.plan.id', $plan->id)
            ->assertJsonPath('data.plan.name', 'Pro')
            ->assertJsonPath('data.plan.features.0', 'search_profiles')
            ->assertJsonPath('data.willRenew', true);
    }

    public function test_me_returns_free_user_when_no_subscription(): void
    {
        $response = $this->actingAs($this->user, 'api')->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('subscription.is_subscribed', false)
            ->assertJsonPath('subscription.plan_name', null)
            ->assertJsonPath('subscription.expires_at', null)
            ->assertJsonPath('subscription.will_renew', null);
    }

    public function test_me_returns_plan_name_for_active_subscription(): void
    {
        $plan = Plan::create([
            'name' => 'Premium', 'billing_rate' => 19.99, 'billing_cycle' => 'yearly',
            'status' => 'active', 'features' => ['search_profiles', 'unlimited_direct_messaging'],
        ]);

        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
            'current_period_end' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonPath('subscription.is_subscribed', true)
            ->assertJsonPath('subscription.plan_id', $plan->id)
            ->assertJsonPath('subscription.plan_name', 'Premium')
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.will_renew', true);
    }
}
