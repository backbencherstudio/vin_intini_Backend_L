<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Stripe\StripeObject;

class BillingWebhookService
{
    public function __construct(private StripeService $stripe) {}

    public function handleCheckoutCompleted(StripeObject $session): void
    {
        $session = $this->stripe->retrieveSession($session->id);

        $userId = $session->client_reference_id ?: data_get($session->metadata, 'user_id');
        $plan = Plan::find(data_get($session->metadata, 'plan_id'))
            ?? Plan::where('stripe_price_id', $this->priceIdFromSession($session))->first();

        if (! $userId || ! $plan) {
            return;
        }

        $stripeSubscription = $session->subscription;
        $priceId = $this->normalizeId($stripeSubscription?->items->data[0]?->price->id) ?? $plan->stripe_price_id;
        $productId = $this->normalizeId($stripeSubscription?->items->data[0]?->price->product) ?? $plan->stripe_product_id;

        $subscription = Subscription::updateOrCreate(
            [
                'provider_subscription_id' => $stripeSubscription?->id,
                'platform' => 'stripe',
            ],
            [
                'user_id' => (int) $userId,
                'plan_id' => $plan->id,
                'provider_customer_id' => $session->customer,
                'product_id' => $productId,
                'price_id' => $priceId,
                'status' => 'active',
                'current_period_start' => $stripeSubscription?->current_period_start
                    ? now()->createFromTimestamp($stripeSubscription->current_period_start)
                    : null,
                'current_period_end' => $stripeSubscription?->current_period_end
                    ? now()->createFromTimestamp($stripeSubscription->current_period_end)
                    : null,
                'cancel_at_period_end' => (bool) $stripeSubscription?->cancel_at_period_end,
                'canceled_at' => null,
                'ends_at' => null,
            ],
        );

        $this->upsertTransaction([
            'provider_transaction_id' => $session->payment_intent?->id,
            'checkout_session_id' => $session->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $plan->id,
            'subscription_id' => $subscription->id,
            'amount' => ($session->amount_total ?? 0) / 100,
            'currency' => $session->currency ?? 'usd',
            'card_brand' => data_get($session, 'payment_intent.payment_method.card.brand'),
            'card_last4' => data_get($session, 'payment_intent.payment_method.card.last4'),
            'status' => 'succeeded',
            'refunded_amount' => 0,
            'paid_at' => now(),
        ]);
    }

    public function handleSubscriptionUpdated(StripeObject $stripeSubscription): void
    {
        $subscription = $this->syncSubscriptionFromStripe($stripeSubscription);

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => $stripeSubscription->status ?? $subscription->status,
            'current_period_start' => $this->fromTimestamp($stripeSubscription->current_period_start),
            'current_period_end' => $this->fromTimestamp($stripeSubscription->current_period_end),
            'cancel_at_period_end' => (bool) $stripeSubscription->cancel_at_period_end,
            'canceled_at' => $this->fromTimestamp($stripeSubscription->canceled_at ?? null),
            'ends_at' => $this->fromTimestamp($stripeSubscription->ended_at ?? null),
        ]);

        $priceId = $this->normalizeId($stripeSubscription->items?->data[0]?->price?->id);
        $productId = $this->normalizeId($stripeSubscription->items?->data[0]?->price?->product);

        if ($priceId) {
            $data = [
                'price_id' => $priceId,
                'product_id' => $productId,
            ];

            $plan = Plan::where('stripe_price_id', $priceId)->first();
            if ($plan) {
                $data['plan_id'] = $plan->id;
            }

            $subscription->update($data);
        }
    }

    public function handleSubscriptionDeleted(StripeObject $stripeSubscription): void
    {
        $subscription = Subscription::where('provider_subscription_id', $stripeSubscription->id)
            ->where('platform', 'stripe')
            ->first();

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'canceled',
            'canceled_at' => $this->fromTimestamp($stripeSubscription->canceled_at ?? null) ?? now(),
            'ends_at' => $this->fromTimestamp($stripeSubscription->ended_at ?? null) ?? now(),
            'cancel_at_period_end' => false,
        ]);
    }

    public function handlePaymentIntentFailed(StripeObject $paymentIntent): void
    {
        $subscription = $this->resolveSubscriptionForPaymentIntent($paymentIntent);

        if (! $subscription) {
            return;
        }

        $this->upsertTransaction([
            'provider_transaction_id' => $paymentIntent->id,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id,
            'subscription_id' => $subscription->id,
            'amount' => ($paymentIntent->amount ?? 0) / 100,
            'currency' => $paymentIntent->currency ?? 'usd',
            'card_brand' => data_get($paymentIntent, 'payment_method.card.brand'),
            'card_last4' => data_get($paymentIntent, 'payment_method.card.last4'),
            'status' => 'failed',
            'refunded_amount' => 0,
        ]);
    }

    public function handleChargeRefunded(StripeObject $charge): void
    {
        $transaction = Transaction::where('provider_transaction_id', $charge->payment_intent)->first();

        if (! $transaction) {
            $paymentIntent = $charge->payment_intent
                ? $this->stripe->retrievePaymentIntent($charge->payment_intent)
                : null;

            $subscription = $paymentIntent?->subscription
                ? Subscription::where('provider_subscription_id', $paymentIntent->subscription)
                    ->where('platform', 'stripe')
                    ->first()
                : null;

            if (! $subscription) {
                return;
            }

            $this->upsertTransaction([
                'provider_transaction_id' => $charge->payment_intent,
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'subscription_id' => $subscription->id,
                'amount' => $charge->amount_refunded / 100,
                'currency' => $charge->currency ?? 'usd',
                'card_brand' => data_get($paymentIntent, 'payment_method.card.brand'),
                'card_last4' => data_get($paymentIntent, 'payment_method.card.last4'),
                'status' => 'refunded',
                'refunded_amount' => $charge->amount_refunded / 100,
                'refunded_at' => now(),
            ]);

            return;
        }

        $transaction->update([
            'status' => 'refunded',
            'refunded_amount' => $charge->amount_refunded / 100,
            'refunded_at' => now(),
        ]);
    }

    public function handleInvoicePaid(StripeObject $invoice): void
    {
        $subscription = $this->resolveSubscriptionFromInvoice($invoice);

        if (! $subscription) {
            return;
        }

        $paymentIntent = $invoice->payment_intent
            ? $this->stripe->retrievePaymentIntent($invoice->payment_intent)
            : null;

        $this->upsertTransaction([
            'provider_transaction_id' => $invoice->payment_intent,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id,
            'subscription_id' => $subscription->id,
            'amount' => ($invoice->amount_paid ?? 0) / 100,
            'currency' => $invoice->currency ?? 'usd',
            'card_brand' => data_get($paymentIntent, 'payment_method.card.brand'),
            'card_last4' => data_get($paymentIntent, 'payment_method.card.last4'),
            'status' => 'succeeded',
            'refunded_amount' => 0,
            'paid_at' => now(),
        ]);

        $subscription->update([
            'status' => 'active',
            'current_period_start' => $this->fromTimestamp($invoice->period_start),
            'current_period_end' => $this->fromTimestamp($invoice->period_end),
            'canceled_at' => null,
            'ends_at' => null,
        ]);
    }

    public function handleInvoicePaymentFailed(StripeObject $invoice): void
    {
        $subscription = $this->resolveSubscriptionFromInvoice($invoice);

        if (! $subscription) {
            return;
        }

        $paymentIntent = $invoice->payment_intent
            ? $this->stripe->retrievePaymentIntent($invoice->payment_intent)
            : null;

        $this->upsertTransaction([
            'provider_transaction_id' => $invoice->payment_intent,
            'user_id' => $subscription->user_id,
            'plan_id' => $subscription->plan_id,
            'subscription_id' => $subscription->id,
            'amount' => ($invoice->amount_due ?? 0) / 100,
            'currency' => $invoice->currency ?? 'usd',
            'card_brand' => data_get($paymentIntent, 'payment_method.card.brand'),
            'card_last4' => data_get($paymentIntent, 'payment_method.card.last4'),
            'status' => 'failed',
            'refunded_amount' => 0,
        ]);

        $subscription->update([
            'status' => 'past_due',
        ]);
    }

    private function syncSubscriptionFromStripe(StripeObject $stripeSubscription): ?Subscription
    {
        $subscription = Subscription::where('provider_subscription_id', $stripeSubscription->id)
            ->where('platform', 'stripe')
            ->first();

        if ($subscription) {
            return $subscription;
        }

        $userId = data_get($stripeSubscription->metadata, 'user_id');
        $plan = Plan::find(data_get($stripeSubscription->metadata, 'plan_id'))
            ?? Plan::where('stripe_price_id', $this->normalizeId($stripeSubscription->items->data[0]?->price?->id))->first();

        if (! $userId || ! $plan) {
            return null;
        }

        return Subscription::create([
            'user_id' => (int) $userId,
            'plan_id' => $plan->id,
            'platform' => 'stripe',
            'provider_subscription_id' => $stripeSubscription->id,
            'provider_customer_id' => $stripeSubscription->customer,
            'product_id' => $this->normalizeId($stripeSubscription->items->data[0]?->price?->product),
            'price_id' => $this->normalizeId($stripeSubscription->items->data[0]?->price?->id),
            'status' => $stripeSubscription->status ?? 'active',
            'current_period_start' => $this->fromTimestamp($stripeSubscription->current_period_start),
            'current_period_end' => $this->fromTimestamp($stripeSubscription->current_period_end),
            'cancel_at_period_end' => (bool) $stripeSubscription->cancel_at_period_end,
        ]);
    }

    private function resolveSubscriptionFromInvoice(StripeObject $invoice): ?Subscription
    {
        if (! $invoice->subscription) {
            return null;
        }

        $subscription = Subscription::where('provider_subscription_id', $invoice->subscription)
            ->where('platform', 'stripe')
            ->first();

        if ($subscription) {
            return $subscription;
        }

        $stripeSubscription = $this->stripe->retrieveSubscription($invoice->subscription);

        return $this->syncSubscriptionFromStripe($stripeSubscription);
    }

    private function resolveSubscriptionForPaymentIntent(StripeObject $paymentIntent): ?Subscription
    {
        if ($paymentIntent->subscription) {
            $subscription = Subscription::where('provider_subscription_id', $paymentIntent->subscription)
                ->where('platform', 'stripe')
                ->first();

            if ($subscription) {
                return $subscription;
            }

            return $this->syncSubscriptionFromStripe(
                $this->stripe->retrieveSubscription($paymentIntent->subscription),
            );
        }

        return null;
    }

    private function upsertTransaction(array $data): void
    {
        $providerTransactionId = $data['provider_transaction_id'] ?? null;

        if (! $providerTransactionId) {
            return;
        }

        Transaction::updateOrCreate(
            ['provider_transaction_id' => $providerTransactionId],
            $data,
        );
    }

    private function priceIdFromSession(StripeObject $session): ?string
    {
        return $this->normalizeId($session->subscription?->items->data[0]?->price?->id)
            ?? $this->normalizeId($session->line_items?->data[0]?->price?->id);
    }

    private function normalizeId(mixed $value): ?string
    {
        if ($value instanceof StripeObject && isset($value->id)) {
            return $value->id;
        }

        return is_string($value) ? $value : null;
    }

    private function fromTimestamp(?int $timestamp): ?Carbon
    {
        return $timestamp ? now()->createFromTimestamp($timestamp) : null;
    }
}
