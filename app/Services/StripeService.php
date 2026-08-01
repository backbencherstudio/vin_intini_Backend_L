<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\Subscription;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createProduct(string $name, ?string $description, array $features): Product
    {
        return Product::create([
            'name' => $name,
            'description' => $description,
            'metadata' => [
                'features' => implode(',', $features),
            ],
        ]);
    }

    public function createPrice(
        int $unitAmount,
        string $currency,
        string $interval,
        string $productId,
    ): Price {
        return Price::create([
            'unit_amount' => $unitAmount,
            'currency' => $currency,
            'recurring' => ['interval' => $interval],
            'product' => $productId,
        ]);
    }

    public function updateProduct(string $productId, array $data): Product
    {
        return Product::update($productId, $data);
    }

    public function archivePrice(string $priceId): Price
    {
        return Price::update($priceId, ['active' => false]);
    }

    public function getOrCreateCustomer(User $user): Customer
    {
        if ($user->stripe_customer_id) {
            return Customer::retrieve($user->stripe_customer_id);
        }

        $customer = Customer::create([
            'email' => $user->email,
            'name' => trim($user->first_name.' '.$user->last_name),
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    public function createSubscriptionWithPayment(Plan $plan, User $user, string $customerId): Subscription
    {
        return Subscription::create([
            'customer' => $customerId,
            'items' => [
                [
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ],
            ],
            'payment_behavior' => 'default_incomplete',
            'payment_settings' => [
                'save_default_payment_method' => 'on_subscription',
            ],
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
            ],
            'expand' => ['latest_invoice.confirmation_secret', 'pending_setup_intent'],
        ]);
    }

    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = true): Subscription
    {
        return Subscription::update($subscriptionId, ['cancel_at_period_end' => $atPeriodEnd]);
    }

    public function constructEvent(string $payload, string $signature): Event
    {
        return Event::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));
    }

    public function retrieveSession(string $sessionId): Session
    {
        return Session::retrieve($sessionId, [
            'expand' => ['subscription', 'payment_intent', 'payment_intent.payment_method'],
        ]);
    }

    public function retrieveSubscription(string $subscriptionId): Subscription
    {
        return Subscription::retrieve($subscriptionId);
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId, [
            'expand' => ['payment_method'],
        ]);
    }
}
