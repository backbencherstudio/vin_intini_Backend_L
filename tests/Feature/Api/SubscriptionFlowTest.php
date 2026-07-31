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
use Stripe\Customer;
use Stripe\Subscription as StripeSubscription;
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

    public function test_user_can_create_subscription_for_custom_payment_form(): void
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
            $mock->shouldReceive('createSubscriptionWithPayment')->once()
                ->withArgs(fn ($planArg, $userArg, $customerId) => $planArg->is($plan) && $customerId === 'cus_mock')
                ->andReturn(StripeSubscription::constructFrom([
                    'id' => 'sub_mock',
                    'pending_setup_intent' => null,
                    'latest_invoice' => [
                        'confirmation_secret' => [
                            'client_secret' => 'cs_test_secret_123',
                        ],
                    ],
                ]));
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'payment')
            ->assertJsonPath('data.client_secret', 'cs_test_secret_123')
            ->assertJsonPath('data.subscription_id', 'sub_mock');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'provider_subscription_id' => 'sub_mock',
            'provider_customer_id' => 'cus_mock',
            'status' => 'incomplete',
        ]);
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
            $mock->shouldReceive('createSubscriptionWithPayment')->never();
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_user_cannot_create_subscription_for_another_user(): void
    {
        $other = User::factory()->create();
        $plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
            'stripe_price_id' => 'price_1',
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'user_id' => $other->id,
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(403)->assertJsonPath('success', false);
    }

    public function test_status_returns_inactive_when_no_subscription(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/subscriptions/status?user_id={$this->user->id}");

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
            ->getJson("/api/subscriptions/status?user_id={$this->user->id}");

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
