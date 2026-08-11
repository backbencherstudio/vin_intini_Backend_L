<?php

namespace Tests\Feature\Api;

use App\Mail\SubscriptionOtpMail;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Mockery\MockInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stripe\Customer;
use Stripe\Exception\ApiConnectionException;
use Stripe\PaymentMethod;
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

    private function makePlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['search_profiles', 'unlimited_direct_messaging'],
            'stripe_product_id' => 'prod_1',
            'stripe_price_id' => 'price_1',
        ], $overrides));
    }

    private function fakeStripeSubscription(array $overrides = []): StripeSubscription
    {
        return StripeSubscription::constructFrom(array_merge([
            'id' => 'sub_mock',
            'status' => 'active',
            'customer' => 'cus_mock',
            'cancel_at_period_end' => false,
            'items' => [
                'data' => [
                    ['price' => ['id' => 'price_1', 'product' => 'prod_1']],
                ],
            ],
            'latest_invoice' => [
                'payment_intent' => [
                    'id' => 'pi_mock',
                    'client_secret' => 'pi_mock_secret_123',
                    'status' => 'succeeded',
                    'amount' => 999,
                    'currency' => 'usd',
                    'payment_method' => ['card' => ['brand' => 'visa', 'last4' => '4242']],
                ],
            ],
        ], $overrides));
    }

    public function test_user_can_fetch_only_active_plans_with_stripe_price(): void
    {
        Plan::create([
            'name' => 'Free', 'billing_rate' => 0, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
        ]);
        Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles', 'unlimited_direct_messaging'],
            'stripe_product_id' => 'prod_1', 'stripe_price_id' => 'price_1',
        ]);
        Plan::create([
            'name' => 'NoStripe', 'billing_rate' => 5, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
        ]);
        Plan::create([
            'name' => 'Hidden', 'billing_rate' => 5, 'billing_cycle' => 'monthly',
            'status' => 'inactive', 'features' => ['search_profiles'],
        ]);

        $response = $this->actingAs($this->user, 'api')->getJson('/api/plans');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.stripe_public_key', config('services.stripe.key'))
            ->assertJsonCount(1, 'data.plans')
            ->assertJsonPath('data.plans.0.name', 'Pro')
            ->assertJsonPath('data.plans.0.stripe_price_id', 'price_1');
    }

    public function test_send_otp_sends_otp_and_requires_verification(): void
    {
        Mail::fake();

        $plan = $this->makePlan();

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/send-otp', [
            'plan_id' => $plan->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.requires_verification', true)
            ->assertJsonPath('data.email', $this->user->email);

        Mail::assertQueued(SubscriptionOtpMail::class);

        $this->assertNotNull($this->user->fresh()->otp);
        $this->assertTrue($this->user->fresh()->otp_expires_at->greaterThan(now()));
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $this->user->id]);
    }

    public function test_send_otp_returns_429_while_otp_is_still_valid(): void
    {
        $plan = $this->makePlan();

        $this->user->update([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/send-otp', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(429)->assertJsonPath('success', false);
    }

    public function test_create_without_otp_returns_validation_error(): void
    {
        $plan = $this->makePlan();

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_create_with_invalid_otp_returns_400(): void
    {
        $plan = $this->makePlan();

        $this->user->update([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
            'otp' => '9999',
            'payment_method' => 'pm_mock',
        ]);

        $response->assertStatus(400)->assertJsonPath('success', false);
    }

    public function test_create_with_expired_otp_returns_400_and_can_resend(): void
    {
        $plan = $this->makePlan();

        $this->user->update([
            'otp' => '1234',
            'otp_expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
            'otp' => '1234',
            'payment_method' => 'pm_mock',
        ]);

        $response
            ->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('data.can_resend_otp', true);
    }

    public function test_create_with_valid_otp_creates_subscription_and_transaction(): void
    {
        $plan = $this->makePlan();

        $this->user->update([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(2),
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($plan) {
            $mock->shouldReceive('getOrCreateCustomer')->once()->andReturn(
                Customer::constructFrom(['id' => 'cus_mock']),
            );
            $mock->shouldReceive('attachPaymentMethod')->once()
                ->with('pm_mock', 'cus_mock')
                ->andReturn(PaymentMethod::constructFrom(['id' => 'pm_mock']));
            $mock->shouldReceive('createSubscription')->once()
                ->withArgs(fn ($planArg, $customerId, $userId, $pmId) => $planArg->is($plan) && $customerId === 'cus_mock' && $pmId === 'pm_mock')
                ->andReturn($this->fakeStripeSubscription());
            $mock->shouldReceive('periodDatesFromSubscription')->once()
                ->andReturn([now()->subMonth(), now()->addMonth()]);
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
            'otp' => '1234',
            'payment_method' => 'pm_mock',
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.subscription.plan.name', 'Pro');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'provider_subscription_id' => 'sub_mock',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'provider_transaction_id' => 'pi_mock',
            'status' => 'succeeded',
            'amount' => 9.99,
            'card_brand' => 'visa',
            'card_last4' => '4242',
        ]);

        $this->assertNull($this->user->fresh()->otp);
    }

    public function test_create_returns_client_secret_when_payment_requires_action(): void
    {
        $plan = $this->makePlan();

        $this->user->update([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(2),
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($plan) {
            $mock->shouldReceive('getOrCreateCustomer')->once()->andReturn(
                Customer::constructFrom(['id' => 'cus_mock']),
            );
            $mock->shouldReceive('attachPaymentMethod')->once()
                ->with('pm_mock', 'cus_mock')
                ->andReturn(PaymentMethod::constructFrom(['id' => 'pm_mock']));
            $mock->shouldReceive('createSubscription')->once()
                ->withArgs(fn ($planArg, $customerId, $userId, $pmId) => $planArg->is($plan) && $customerId === 'cus_mock' && $pmId === 'pm_mock')
                ->andReturn($this->fakeStripeSubscription([
                    'id' => 'sub_3ds',
                    'status' => 'incomplete',
                    'latest_invoice' => [
                        'payment_intent' => [
                            'id' => 'pi_3ds',
                            'client_secret' => 'pi_3ds_secret_xyz',
                            'status' => 'requires_action',
                            'amount' => 999,
                            'currency' => 'usd',
                        ],
                    ],
                ]));
            $mock->shouldReceive('periodDatesFromSubscription')->once()
                ->andReturn([now()->subMonth(), now()->addMonth()]);
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
            'otp' => '1234',
            'payment_method' => 'pm_mock',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_intent_client_secret', 'pi_3ds_secret_xyz')
            ->assertJsonPath('data.payment_status', 'requires_action');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'provider_subscription_id' => 'sub_3ds',
            'status' => 'incomplete',
        ]);
    }

    public function test_user_cannot_create_subscription_when_already_active(): void
    {
        $plan = $this->makePlan();

        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_active',
            'status' => 'active',
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getOrCreateCustomer')->never();
            $mock->shouldReceive('attachPaymentMethod')->never();
            $mock->shouldReceive('createSubscription')->never();
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/send-otp', [
            'plan_id' => $plan->id,
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_create_returns_422_when_stripe_fails_and_rolls_back(): void
    {
        $plan = $this->makePlan();

        $this->user->update([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(2),
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getOrCreateCustomer')->once()->andThrow(
                new ApiConnectionException('Stripe is down'),
            );
            $mock->shouldReceive('attachPaymentMethod')->never();
            $mock->shouldReceive('createSubscription')->never();
        });

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
            'otp' => '1234',
            'payment_method' => 'pm_mock',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Subscription payment failed. Please try again.');

        $this->assertDatabaseMissing('subscriptions', ['user_id' => $this->user->id]);
        $this->assertDatabaseMissing('transactions', ['user_id' => $this->user->id]);
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
            $mock->shouldReceive('attachPaymentMethod')->never();
            $mock->shouldReceive('createSubscription')->never();
        });

        $this->user->update([
            'otp' => '1234',
            'otp_expires_at' => now()->addMinutes(2),
        ]);

        $response = $this->actingAs($this->user, 'api')->postJson('/api/subscriptions/create', [
            'plan_id' => $plan->id,
            'otp' => '1234',
            'payment_method' => 'pm_mock',
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
