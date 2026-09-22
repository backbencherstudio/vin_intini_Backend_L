<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class RevenueCatWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['revenuecat.webhook_secret' => 'test_secret']);
        config(['queue.default' => 'sync']);
    }

    private function makePlan(array $overrides = []): Plan
    {
        return Plan::create(array_merge([
            'name' => 'Pro',
            'billing_rate' => 9.99,
            'billing_cycle' => 'monthly',
            'status' => 'active',
            'features' => ['search_profiles'],
            'revenuecat_store_identifier_ios' => 'rc_prod_pro',
        ], $overrides));
    }

    private function postWebhook(array $payload): TestResponse
    {
        $body = json_encode($payload);
        $timestamp = (string) time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'test_secret');

        return $this->call(
            'POST',
            '/api/webhooks/revenuecat',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_REVENUECAT_WEBHOOK_SIGNATURE' => $signature],
            $body,
        );
    }

    public function test_initial_purchase_creates_subscription_and_transaction(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan();

        $this->postWebhook([
            'event' => [
                'id' => 'evt_1',
                'type' => 'INITIAL_PURCHASE',
                'app_user_id' => (string) $user->id,
                'product_id' => 'rc_prod_pro',
                'entitlement_id' => 'premium',
                'store' => 'APP_STORE',
                'transaction_id' => 'txn_1',
                'original_transaction_id' => 'orig_1',
                'price' => 9.99,
                'price_in_purchased_currency' => 9.99,
                'currency' => 'USD',
                'expiration_at_ms' => now()->addMonth()->timestamp * 1000,
                'event_timestamp_ms' => now()->timestamp * 1000,
            ],
            'subscriber' => ['app_user_id' => (string) $user->id],
        ])->assertOk()->assertJsonPath('received', true);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'revenuecat',
            'provider_subscription_id' => 'orig_1',
            'status' => 'active',
            'store' => 'APP_STORE',
        ]);

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'txn_1',
            'user_id' => $user->id,
            'status' => 'succeeded',
            'amount' => 9.99,
        ]);
    }

    public function test_webhook_resolves_plan_by_android_store_identifier(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan(['revenuecat_store_identifier_android' => 'rc_prod_android']);

        $this->postWebhook([
            'event' => [
                'id' => 'evt_android',
                'type' => 'INITIAL_PURCHASE',
                'app_user_id' => (string) $user->id,
                'product_id' => 'rc_prod_android',
                'entitlement_id' => 'premium',
                'store' => 'PLAY_STORE',
                'transaction_id' => 'txn_android',
                'original_transaction_id' => 'orig_android',
                'price' => 9.99,
                'price_in_purchased_currency' => 9.99,
                'currency' => 'USD',
                'expiration_at_ms' => now()->addMonth()->timestamp * 1000,
                'event_timestamp_ms' => now()->timestamp * 1000,
            ],
            'subscriber' => ['app_user_id' => (string) $user->id],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'revenuecat',
            'status' => 'active',
        ]);
    }

    public function test_cancellation_marks_subscription_canceled(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan();
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'revenuecat',
            'provider_subscription_id' => 'orig_1',
            'provider_customer_id' => (string) $user->id,
            'product_id' => 'rc_prod_pro',
            'status' => 'active',
        ]);

        $this->postWebhook([
            'event' => [
                'id' => 'evt_2',
                'type' => 'CANCELLATION',
                'app_user_id' => (string) $user->id,
                'product_id' => 'rc_prod_pro',
                'original_transaction_id' => 'orig_1',
                'expiration_at_ms' => now()->addDays(3)->timestamp * 1000,
            ],
            'subscriber' => ['app_user_id' => (string) $user->id],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'provider_subscription_id' => 'orig_1',
            'platform' => 'revenuecat',
            'status' => 'canceled',
        ]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->postJson('/api/webhooks/revenuecat', ['event' => ['type' => 'INITIAL_PURCHASE']])
            ->assertStatus(403);
    }

    private function postWebhookWithAuthorization(string $token, array $payload): TestResponse
    {
        $body = json_encode($payload);

        return $this->call(
            'POST',
            '/api/webhooks/revenuecat',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_AUTHORIZATION' => $token],
            $body,
        );
    }

    public function test_webhook_accepts_authorization_header(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan();

        $this->postWebhookWithAuthorization('Bearer test_secret', [
            'event' => [
                'id' => 'evt_3',
                'type' => 'INITIAL_PURCHASE',
                'app_user_id' => (string) $user->id,
                'product_id' => 'rc_prod_pro',
                'entitlement_id' => 'premium',
                'store' => 'PLAY_STORE',
                'transaction_id' => 'txn_3',
                'original_transaction_id' => 'orig_3',
                'price' => 9.99,
                'price_in_purchased_currency' => 9.99,
                'currency' => 'USD',
                'expiration_at_ms' => now()->addMonth()->timestamp * 1000,
                'event_timestamp_ms' => now()->timestamp * 1000,
            ],
            'subscriber' => ['app_user_id' => (string) $user->id],
        ])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'platform' => 'revenuecat',
            'provider_subscription_id' => 'orig_3',
            'status' => 'active',
            'store' => 'PLAY_STORE',
        ]);
    }

    public function test_webhook_rejects_wrong_authorization_header(): void
    {
        $this->postWebhookWithAuthorization('Bearer wrong_secret', [
            'event' => ['type' => 'INITIAL_PURCHASE'],
        ])->assertStatus(403);
    }

    public function test_new_purchase_cancels_previous_active_subscription(): void
    {
        $user = User::factory()->create();
        $monthlyPlan = $this->makePlan(['name' => 'Monthly', 'revenuecat_store_identifier_android' => 'monthly_pkg']);
        $yearlyPlan = $this->makePlan(['name' => 'Yearly', 'revenuecat_store_identifier_android' => 'yearly_pkg']);

        // Existing active monthly subscription
        $oldSub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $monthlyPlan->id,
            'platform' => 'revenuecat',
            'provider_subscription_id' => 'GPA.OLD-MONTHLY-1111',
            'provider_customer_id' => (string) $user->id,
            'product_id' => 'monthly_pkg',
            'status' => 'active',
            'store' => 'PLAY_STORE',
            'current_period_start' => now()->subDays(5),
            'current_period_end' => now()->addDays(25),
        ]);

        // New yearly purchase webhook
        $this->postWebhook([
            'event' => [
                'id' => 'evt_yearly',
                'type' => 'INITIAL_PURCHASE',
                'app_user_id' => (string) $user->id,
                'product_id' => 'yearly_pkg',
                'entitlement_id' => 'premium',
                'store' => 'PLAY_STORE',
                'transaction_id' => 'txn_yearly',
                'original_transaction_id' => 'GPA.NEW-YEARLY-2222',
                'price' => 99.99,
                'price_in_purchased_currency' => 99.99,
                'currency' => 'USD',
                'expiration_at_ms' => now()->addYear()->timestamp * 1000,
                'event_timestamp_ms' => now()->timestamp * 1000,
            ],
            'subscriber' => ['app_user_id' => (string) $user->id],
        ])->assertOk();

        // Old subscription should be marked canceled
        $this->assertDatabaseHas('subscriptions', [
            'id' => $oldSub->id,
            'provider_subscription_id' => 'GPA.OLD-MONTHLY-1111',
            'status' => 'canceled',
        ]);

        // New subscription should be active
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_id' => $yearlyPlan->id,
            'provider_subscription_id' => 'GPA.NEW-YEARLY-2222',
            'status' => 'active',
        ]);

        // User should have exactly 1 active subscription
        $this->assertEquals(1, $user->subscriptions()->where('status', 'active')->count());
    }
}
