<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook;

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

    public function createCheckoutSession(Plan $plan, User $user, string $customerId): Session
    {
        return Session::create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'client_reference_id' => (string) $user->id,
            'line_items' => [
                [
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ],
            ],
            'success_url' => config('services.stripe.checkout_success_url'),
            'cancel_url' => config('services.stripe.checkout_cancel_url'),
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan_id' => (string) $plan->id,
            ],
        ]);
    }

    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = true): StripeSubscription
    {
        return StripeSubscription::update($subscriptionId, ['cancel_at_period_end' => $atPeriodEnd]);
    }

    public function constructEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, config('services.stripe.webhook_secret'));
    }

    public function retrieveSession(string $sessionId): Session
    {
        return $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => [
                'subscription',
                'subscription.latest_invoice.payment_intent.payment_method',
                'payment_intent',
                'payment_intent.payment_method',
            ],
        ]);
    }

    public function retrieveSubscription(string $subscriptionId): StripeSubscription
    {
        return $this->client()->subscriptions->retrieve($subscriptionId);
    }

    public function retrieveInvoice(string $invoiceId): Invoice
    {
        return $this->client()->invoices->retrieve($invoiceId);
    }

    public function hydrateSubscriptionDates(Subscription $subscription): void
    {
        if ($subscription->current_period_end || $subscription->platform !== 'stripe' || ! $subscription->provider_subscription_id) {
            return;
        }

        try {
            $stripeSubscription = $this->retrieveSubscription($subscription->provider_subscription_id);
        } catch (ApiErrorException) {
            return;
        }

        [$periodStart, $periodEnd] = $this->periodDatesFromSubscription($stripeSubscription);

        $subscription->update([
            'status' => $stripeSubscription->status ?? $subscription->status,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'cancel_at_period_end' => (bool) $stripeSubscription->cancel_at_period_end,
            'canceled_at' => $this->fromTimestamp(data_get($stripeSubscription, 'canceled_at')),
            'ends_at' => $this->fromTimestamp(data_get($stripeSubscription, 'ended_at')),
        ]);
    }

    public function periodDatesFromSubscription(StripeObject $stripeSubscription): array
    {
        $periodStart = $this->fromTimestamp(data_get($stripeSubscription, 'current_period_start'));
        $periodEnd = $this->fromTimestamp(data_get($stripeSubscription, 'current_period_end'));

        if ($periodEnd === null && $invoiceId = data_get($stripeSubscription, 'latest_invoice')) {
            try {
                $invoice = $this->retrieveInvoice((string) $invoiceId);
                $periodStart = $this->fromTimestamp(data_get($invoice, 'period_start'));
                $periodEnd = $this->fromTimestamp(data_get($invoice, 'period_end'));
            } catch (ApiErrorException) {
            }
        }

        return [$periodStart, $periodEnd];
    }

    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($paymentIntentId, [
            'expand' => ['payment_method'],
        ]);
    }

    public function findPaymentIntentBySession(string $sessionId, string $customerId): ?PaymentIntent
    {
        $paymentIntents = $this->client()->paymentIntents->all([
            'customer' => $customerId,
            'limit' => 10,
            'expand' => ['data.payment_method'],
        ]);

        foreach ($paymentIntents->data as $paymentIntent) {
            if (($paymentIntent->payment_details->order_reference ?? null) === $sessionId) {
                return $paymentIntent;
            }
        }

        return null;
    }

    private function client(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    private function fromTimestamp(mixed $timestamp): ?Carbon
    {
        return $timestamp ? now()->createFromTimestamp($timestamp) : null;
    }
}
