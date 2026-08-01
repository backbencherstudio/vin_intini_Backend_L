<?php

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Mockery\MockInterface;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Subscription as StripeSubscription;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $this->plan = Plan::create([
            'name' => 'Pro', 'billing_rate' => 9.99, 'billing_cycle' => 'monthly',
            'status' => 'active', 'features' => ['search_profiles'],
            'stripe_product_id' => 'prod_1', 'stripe_price_id' => 'price_1',
        ]);
    }

    private function postWebhook(array $event): TestResponse
    {
        return $this->postJson('/api/webhooks/stripe', $event, ['Stripe-Signature' => 't=1,v1=test']);
    }

    public function test_checkout_completed_creates_subscription_and_transaction(): void
    {
        $sessionData = [
            'id' => 'cs_1',
            'client_reference_id' => (string) $this->user->id,
            'metadata' => ['user_id' => (string) $this->user->id, 'plan_id' => (string) $this->plan->id],
            'customer' => 'cus_1',
            'currency' => 'usd',
            'amount_total' => 999,
            'subscription' => [
                'id' => 'sub_1',
                'current_period_start' => now()->timestamp,
                'current_period_end' => now()->addMonth()->timestamp,
                'cancel_at_period_end' => false,
                'items' => ['data' => [['price' => ['id' => 'price_1', 'product' => 'prod_1']]]],
            ],
            'payment_intent' => [
                'id' => 'pi_1',
                'payment_method' => ['id' => 'pm_1', 'card' => ['brand' => 'visa', 'last4' => '4242']],
            ],
        ];

        $event = Event::constructFrom([
            'id' => 'evt_1',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $sessionData],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event, $sessionData) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
            $mock->shouldReceive('retrieveSession')->once()->andReturn(
                Session::constructFrom($sessionData),
            );
        });

        $response = $this->postWebhook([]);

        $response->assertOk()->assertJsonPath('received', true);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'provider_subscription_id' => 'sub_1',
            'provider_customer_id' => 'cus_1',
            'status' => 'active',
            'price_id' => 'price_1',
        ]);

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'pi_1',
            'checkout_session_id' => 'cs_1',
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 9.99,
            'status' => 'succeeded',
            'card_brand' => 'visa',
            'card_last4' => '4242',
        ]);
    }

    public function test_checkout_completed_uses_billing_anchor_when_period_dates_missing(): void
    {
        $anchor = now();

        $sessionData = [
            'id' => 'cs_1b',
            'client_reference_id' => (string) $this->user->id,
            'metadata' => ['user_id' => (string) $this->user->id, 'plan_id' => (string) $this->plan->id],
            'customer' => 'cus_1',
            'currency' => 'usd',
            'amount_total' => 999,
            'subscription' => [
                'id' => 'sub_1b',
                'billing_cycle_anchor' => $anchor->timestamp,
                'cancel_at_period_end' => false,
                'items' => ['data' => [['price' => ['id' => 'price_1', 'product' => 'prod_1']]]],
            ],
            'payment_intent' => [
                'id' => 'pi_1b',
                'payment_method' => ['id' => 'pm_1b', 'card' => ['brand' => 'visa', 'last4' => '4242']],
            ],
        ];

        $event = Event::constructFrom([
            'id' => 'evt_1b',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $sessionData],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event, $sessionData) {
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('retrieveSession')->once()->andReturn(
                Session::constructFrom($sessionData),
            );
        });

        $this->postWebhook([])->assertOk();

        $subscription = Subscription::where('provider_subscription_id', 'sub_1b')->first();

        $this->assertNotNull($subscription);
        $this->assertEquals(
            $anchor->copy()->addMonth()->toDateTimeString(),
            $subscription->current_period_end->toDateTimeString(),
        );
        $this->assertGreaterThan(20, (int) ceil(
            ($subscription->current_period_end->timestamp - now()->timestamp) / 86400,
        ));
    }

    public function test_checkout_completed_records_transaction_from_subscription_invoice(): void
    {
        $sessionData = [
            'id' => 'cs_2',
            'client_reference_id' => (string) $this->user->id,
            'metadata' => ['user_id' => (string) $this->user->id, 'plan_id' => (string) $this->plan->id],
            'customer' => 'cus_1',
            'currency' => 'usd',
            'amount_total' => 1999,
            'subscription' => [
                'id' => 'sub_2',
                'current_period_start' => now()->timestamp,
                'current_period_end' => now()->addMonth()->timestamp,
                'cancel_at_period_end' => false,
                'items' => ['data' => [['price' => ['id' => 'price_1', 'product' => 'prod_1']]]],
                'latest_invoice' => [
                    'payment_intent' => [
                        'id' => 'pi_2',
                        'payment_method' => ['id' => 'pm_2', 'card' => ['brand' => 'mastercard', 'last4' => '1111']],
                    ],
                ],
            ],
        ];

        $event = Event::constructFrom([
            'id' => 'evt_2',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $sessionData],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event, $sessionData) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
            $mock->shouldReceive('retrieveSession')->once()->andReturn(
                Session::constructFrom($sessionData),
            );
        });

        $response = $this->postWebhook([]);

        $response->assertOk()->assertJsonPath('received', true);

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'pi_2',
            'checkout_session_id' => 'cs_2',
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 19.99,
            'status' => 'succeeded',
            'card_brand' => 'mastercard',
            'card_last4' => '1111',
        ]);
    }

    public function test_checkout_completed_records_transaction_via_payment_intent_lookup(): void
    {
        $sessionData = [
            'id' => 'cs_3',
            'client_reference_id' => (string) $this->user->id,
            'metadata' => ['user_id' => (string) $this->user->id, 'plan_id' => (string) $this->plan->id],
            'customer' => 'cus_1',
            'currency' => 'usd',
            'amount_total' => 1499,
            'subscription' => [
                'id' => 'sub_3',
                'current_period_start' => now()->timestamp,
                'current_period_end' => now()->addMonth()->timestamp,
                'cancel_at_period_end' => false,
                'items' => ['data' => [['price' => ['id' => 'price_1', 'product' => 'prod_1']]]],
            ],
        ];

        $event = Event::constructFrom([
            'id' => 'evt_3',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $sessionData],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event, $sessionData) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
            $mock->shouldReceive('retrieveSession')->once()->andReturn(
                Session::constructFrom($sessionData),
            );
            $mock->shouldReceive('findPaymentIntentBySession')->once()
                ->with('cs_3', 'cus_1')
                ->andReturn(PaymentIntent::constructFrom([
                    'id' => 'pi_3',
                    'amount' => 1499,
                    'currency' => 'usd',
                    'payment_method' => ['id' => 'pm_3', 'card' => ['brand' => 'visa', 'last4' => '4242']],
                ]));
        });

        $response = $this->postWebhook([]);

        $response->assertOk()->assertJsonPath('received', true);

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'pi_3',
            'checkout_session_id' => 'cs_3',
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 14.99,
            'status' => 'succeeded',
            'card_brand' => 'visa',
            'card_last4' => '4242',
        ]);
    }

    public function test_subscription_updated_syncs_plan_change_by_price_id(): void
    {
        $newPlan = Plan::create([
            'name' => 'Premium', 'billing_rate' => 19.99, 'billing_cycle' => 'yearly',
            'status' => 'active', 'features' => ['search_profiles'],
            'stripe_product_id' => 'prod_2', 'stripe_price_id' => 'price_new',
        ]);

        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
            'price_id' => 'price_1',
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_2',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_1',
                    'status' => 'active',
                    'cancel_at_period_end' => false,
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addYear()->timestamp,
                    'items' => ['data' => [['price' => ['id' => 'price_new', 'product' => 'prod_2']]]],
                ],
            ],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
        });

        $this->postWebhook([])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'provider_subscription_id' => 'sub_1',
            'plan_id' => $newPlan->id,
            'status' => 'active',
            'price_id' => 'price_new',
        ]);
    }

    public function test_subscription_deleted_marks_subscription_canceled(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_3',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_1',
                    'canceled_at' => now()->timestamp,
                    'ended_at' => now()->timestamp,
                ],
            ],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
        });

        $this->postWebhook([])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'provider_subscription_id' => 'sub_1',
            'status' => 'canceled',
        ]);
    }

    public function test_payment_intent_failed_creates_failed_transaction(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_4',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_2',
                    'subscription' => 'sub_1',
                    'amount' => 999,
                    'currency' => 'usd',
                    'payment_method' => ['id' => 'pm_2', 'card' => ['brand' => 'mastercard', 'last4' => '1234']],
                ],
            ],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
        });

        $this->postWebhook([])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'pi_2',
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 9.99,
            'status' => 'failed',
            'card_brand' => 'mastercard',
            'card_last4' => '1234',
        ]);
    }

    public function test_payment_intent_failed_resolves_subscription_via_order_reference(): void
    {
        $sessionData = [
            'id' => 'cs_4',
            'client_reference_id' => (string) $this->user->id,
            'metadata' => ['user_id' => (string) $this->user->id, 'plan_id' => (string) $this->plan->id],
            'customer' => 'cus_1',
            'amount_total' => 999,
            'subscription' => [
                'id' => 'sub_4',
                'current_period_start' => now()->timestamp,
                'current_period_end' => now()->addMonth()->timestamp,
                'cancel_at_period_end' => false,
                'items' => ['data' => [['price' => ['id' => 'price_1', 'product' => 'prod_1']]]],
            ],
        ];

        $event = Event::constructFrom([
            'id' => 'evt_5',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_4',
                    'amount' => 999,
                    'currency' => 'usd',
                    'payment_details' => ['order_reference' => 'cs_4'],
                    'payment_method' => ['id' => 'pm_4', 'card' => ['brand' => 'visa', 'last4' => '9999']],
                ],
            ],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event, $sessionData) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
            $mock->shouldReceive('retrieveSession')->once()->with('cs_4')->andReturn(
                Session::constructFrom($sessionData),
            );
            $mock->shouldReceive('retrieveSubscription')->once()->with('sub_4')->andReturn(
                StripeSubscription::constructFrom([
                    'id' => 'sub_4',
                    'customer' => 'cus_1',
                    'status' => 'active',
                    'current_period_start' => now()->timestamp,
                    'current_period_end' => now()->addMonth()->timestamp,
                    'cancel_at_period_end' => false,
                    'metadata' => ['user_id' => (string) $this->user->id, 'plan_id' => (string) $this->plan->id],
                    'items' => ['data' => [['price' => ['id' => 'price_1', 'product' => 'prod_1']]]],
                ]),
            );
        });

        $this->postWebhook([])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'provider_subscription_id' => 'sub_4',
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
        ]);

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'pi_4',
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 9.99,
            'status' => 'failed',
        ]);
    }

    public function test_invoice_paid_records_transaction_without_payment_intent(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_5',
            'provider_customer_id' => 'cus_1',
            'status' => 'active',
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_6',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_5',
                    'subscription' => 'sub_5',
                    'amount_paid' => 2999,
                    'currency' => 'usd',
                    'period_start' => now()->timestamp,
                    'period_end' => now()->addMonth()->timestamp,
                ],
            ],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
            $mock->shouldReceive('findPaymentIntentBySession')->once()
                ->with('in_5', 'cus_1')
                ->andReturn(null);
        });

        $this->postWebhook([])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'inv_in_5',
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 29.99,
            'status' => 'succeeded',
        ]);
    }

    public function test_charge_refunded_resolves_subscription_via_order_reference(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_6',
            'provider_customer_id' => 'cus_1',
            'status' => 'active',
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_7',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_6',
                    'payment_intent' => 'pi_6',
                    'amount_refunded' => 999,
                    'currency' => 'usd',
                ],
            ],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
            $mock->shouldReceive('retrievePaymentIntent')->once()->with('pi_6')->andReturn(
                PaymentIntent::constructFrom([
                    'id' => 'pi_6',
                    'payment_details' => ['order_reference' => 'cs_6'],
                    'payment_method' => ['id' => 'pm_6', 'card' => ['brand' => 'visa', 'last4' => '1111']],
                ]),
            );
            $mock->shouldReceive('retrieveSession')->once()->with('cs_6')->andReturn(
                Session::constructFrom([
                    'id' => 'cs_6',
                    'metadata' => ['user_id' => (string) $this->user->id, 'plan_id' => (string) $this->plan->id],
                    'subscription' => ['id' => 'sub_6', 'items' => ['data' => [['price' => ['id' => 'price_1', 'product' => 'prod_1']]]]],
                ]),
            );
        });

        $this->postWebhook([])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'pi_6',
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'amount' => 9.99,
            'status' => 'refunded',
        ]);
    }

    public function test_charge_refunded_marks_transaction_refunded(): void
    {
        $subscription = Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
        ]);

        Transaction::create([
            'provider_transaction_id' => 'pi_1',
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'subscription_id' => $subscription->id,
            'amount' => 9.99,
            'status' => 'succeeded',
            'paid_at' => now(),
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_5',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'id' => 'ch_1',
                    'payment_intent' => 'pi_1',
                    'amount_refunded' => 500,
                    'currency' => 'usd',
                ],
            ],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
        });

        $this->postWebhook([])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'pi_1',
            'status' => 'refunded',
            'refunded_amount' => 5.00,
        ]);
    }

    public function test_invoice_paid_creates_renewal_transaction(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => 'sub_1',
            'status' => 'active',
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_6',
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_1',
                    'subscription' => 'sub_1',
                    'payment_intent' => 'pi_3',
                    'amount_paid' => 999,
                    'currency' => 'usd',
                    'period_start' => now()->timestamp,
                    'period_end' => now()->addMonth()->timestamp,
                ],
            ],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($event) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andReturn($event);
            $mock->shouldReceive('retrievePaymentIntent')->once()->andReturn(
                PaymentIntent::constructFrom([
                    'id' => 'pi_3',
                    'payment_method' => ['id' => 'pm_3', 'card' => ['brand' => 'visa', 'last4' => '4242']],
                ]),
            );
        });

        $this->postWebhook([])->assertOk();

        $this->assertDatabaseHas('transactions', [
            'provider_transaction_id' => 'pi_3',
            'user_id' => $this->user->id,
            'status' => 'succeeded',
            'amount' => 9.99,
        ]);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('periodDatesFromSubscription')->passthru();
            $mock->shouldReceive('constructEvent')->once()->andThrow(
                new SignatureVerificationException('Signature verification failed'),
            );
        });

        $this->postWebhook([])->assertStatus(400);
    }
}
