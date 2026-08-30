<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Carbon;

class RevenueCatWebhookService
{
    public function handle(array $payload): void
    {
        $event = $payload['event'] ?? [];
        $type = $event['type'] ?? null;

        match ($type) {
            'INITIAL_PURCHASE' => $this->handleInitialPurchase($event, $payload),
            'RENEWAL' => $this->handleRenewal($event, $payload),
            'CANCELLATION' => $this->handleCancellation($event),
            'UNCANCELLATION' => $this->handleUncancellation($event),
            'NON_RENEWING_PURCHASE' => $this->handleNonRenewingPurchase($event, $payload),
            'SUBSCRIPTION_PAUSED' => $this->handleStatusChange($event, 'paused'),
            'SUBSCRIPTION_RESUMED' => $this->handleStatusChange($event, 'active'),
            'PRODUCT_CHANGE' => $this->handleProductChange($event),
            'BILLING_ISSUE' => $this->handleStatusChange($event, 'past_due'),
            'REFUND' => $this->handleRefund($event),
            'SUBSCRIPTION_PERIOD_CHANGED' => $this->handlePeriodChanged($event),
            'EXPIRATION' => $this->handleExpiration($event),
            'TRANSFER' => $this->handleTransfer($event),
            default => null,
        };
    }

    private function handleInitialPurchase(array $event, array $payload): void
    {
        $user = $this->resolveUser($event, $payload);
        if (! $user) {
            return;
        }

        $isTrial = ($event['period_type'] ?? null) === 'TRIAL' || ! empty($event['is_trial_period']);
        $subscription = $this->upsertSubscription($event, $user, $isTrial ? 'trialing' : 'active');

        $this->recordTransaction($event, $subscription, 'succeeded');
    }

    private function handleRenewal(array $event, array $payload): void
    {
        $user = $this->resolveUser($event, $payload);
        if (! $user) {
            return;
        }

        $subscription = $this->upsertSubscription($event, $user, 'active');

        $this->recordTransaction($event, $subscription, 'succeeded');
    }

    private function handleCancellation(array $event): void
    {
        $subscription = $this->findSubscription($event);
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'canceled',
            'cancel_at_period_end' => false,
            'canceled_at' => now(),
            'ends_at' => $this->fromTimestamp($event['expiration_at_ms'] ?? $event['expiration_at'] ?? $event['cancel_at']) ?? $subscription->current_period_end,
        ]);
    }

    private function handleUncancellation(array $event): void
    {
        $subscription = $this->findSubscription($event);
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'active',
            'cancel_at_period_end' => false,
            'canceled_at' => null,
            'ends_at' => null,
        ]);
    }

    private function handleNonRenewingPurchase(array $event, array $payload): void
    {
        $user = $this->resolveUser($event, $payload);
        if (! $user) {
            return;
        }

        $this->upsertSubscription($event, $user, 'active');
    }

    private function handleStatusChange(array $event, string $status): void
    {
        $subscription = $this->findSubscription($event);
        if (! $subscription) {
            return;
        }

        $subscription->update(['status' => $status]);
    }

    private function handleProductChange(array $event): void
    {
        $subscription = $this->findSubscription($event);
        if (! $subscription) {
            return;
        }

        $plan = $this->resolvePlan($event);

        $subscription->update([
            'plan_id' => $plan?->id,
            'product_id' => $event['product_id'] ?? $subscription->product_id,
            'status' => 'active',
        ]);
    }

    private function handleBillingIssue(array $event): void
    {
        $subscription = $this->findSubscription($event);
        if (! $subscription) {
            return;
        }

        $subscription->update(['status' => 'past_due']);
    }

    private function handleRefund(array $event): void
    {
        $transactionId = $event['transaction_id'] ?? null;

        if ($transactionId) {
            $transaction = Transaction::where('provider_transaction_id', $transactionId)->first();
            $transaction?->update([
                'status' => 'refunded',
                'refunded_amount' => $transaction->amount,
                'refunded_at' => now(),
            ]);
        }

        $subscription = $this->findSubscription($event);
        $subscription?->update([
            'status' => 'canceled',
            'canceled_at' => now(),
            'ends_at' => now(),
        ]);
    }

    private function handlePeriodChanged(array $event): void
    {
        $subscription = $this->findSubscription($event);
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'current_period_start' => $this->fromTimestamp($event['purchased_at_ms'] ?? $event['purchase_date_ms'] ?? $event['start_at'] ?? $event['period_start']),
            'current_period_end' => $this->fromTimestamp($event['expiration_at_ms'] ?? $event['expiration_at'] ?? $event['period_end']),
        ]);
    }

    private function handleExpiration(array $event): void
    {
        $subscription = $this->findSubscription($event);
        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'expired',
            'ends_at' => $this->fromTimestamp($event['expiration_at_ms'] ?? $event['expiration_at'] ?? null) ?? $subscription->current_period_end,
        ]);
    }

    private function handleTransfer(array $event): void
    {
        $from = $event['transferred_from'] ?? [];
        $to = $event['transferred_to'] ?? [];

        if (empty($from) || empty($to)) {
            return;
        }

        $targetUser = $this->resolveUserByIdentifier($to[0]);
        if (! $targetUser) {
            return;
        }

        Subscription::whereIn('provider_customer_id', $from)
            ->where('platform', 'revenuecat')
            ->update(['user_id' => $targetUser->id, 'provider_customer_id' => $to[0]]);
    }

    private function upsertSubscription(array $event, User $user, string $status): Subscription
    {
        $providerId = $event['original_transaction_id'] ?? $event['transaction_id'] ?? null;
        $plan = $this->resolvePlan($event);
        $appUserId = $event['app_user_id'] ?? null;

        return Subscription::updateOrCreate(
            [
                'provider_subscription_id' => $providerId,
                'platform' => 'revenuecat',
            ],
            [
                'user_id' => $user->id,
                'plan_id' => $plan?->id,
                'provider_customer_id' => $appUserId,
                'product_id' => $event['product_id'] ?? null,
                'status' => $status,
                'store' => $event['store'] ?? null,
                'current_period_start' => $this->fromTimestamp($event['event_timestamp_ms'] ?? $event['event_timestamp'] ?? $event['purchased_at_ms'] ?? $event['purchase_date_ms'] ?? $event['purchase_date'] ?? null),
                'current_period_end' => $this->fromTimestamp($event['expiration_at_ms'] ?? $event['expiration_at'] ?? null),
                'cancel_at_period_end' => false,
                'canceled_at' => null,
                'ends_at' => null,
            ],
        );
    }

    private function recordTransaction(array $event, Subscription $subscription, string $status): void
    {
        $transactionId = $event['transaction_id'] ?? null;
        if (! $transactionId) {
            return;
        }

        Transaction::updateOrCreate(
            ['provider_transaction_id' => $transactionId],
            [
                'user_id' => $subscription->user_id,
                'plan_id' => $subscription->plan_id,
                'subscription_id' => $subscription->id,
                'amount' => (float) ($event['price_in_purchased_currency'] ?? $event['price'] ?? 0),
                'currency' => strtolower($event['currency'] ?? 'usd'),
                'status' => $status,
                'refunded_amount' => 0,
                'paid_at' => now(),
            ],
        );
    }

    private function findSubscription(array $event): ?Subscription
    {
        $providerId = $event['original_transaction_id'] ?? $event['transaction_id'] ?? null;

        if ($providerId) {
            $subscription = Subscription::where('provider_subscription_id', $providerId)
                ->where('platform', 'revenuecat')
                ->first();

            if ($subscription) {
                return $subscription;
            }
        }

        $appUserId = $event['app_user_id'] ?? null;
        $productId = $event['product_id'] ?? null;

        if ($appUserId && $productId) {
            return Subscription::where('provider_customer_id', $appUserId)
                ->where('product_id', $productId)
                ->where('platform', 'revenuecat')
                ->latest()
                ->first();
        }

        return null;
    }

    private function resolveUser(array $event, array $payload): ?User
    {
        $appUserId = $event['app_user_id'] ?? $payload['subscriber']['app_user_id'] ?? null;

        return $this->resolveUserByIdentifier($appUserId);
    }

    private function resolveUserByIdentifier(?string $appUserId): ?User
    {
        if (! $appUserId) {
            return null;
        }

        if (config('revenuecat.app_user_id_strategy') === 'email') {
            return User::where('email', $appUserId)->first();
        }

        return User::find($appUserId);
    }

    private function resolvePlan(array $event): ?Plan
    {
        $productId = $event['product_id'] ?? null;
        if (! $productId) {
            return null;
        }

        return Plan::where('revenuecat_product_id', $productId)
            ->orWhere('revenuecat_product_id_ios', $productId)
            ->orWhere('revenuecat_product_id_android', $productId)
            ->first();
    }

    private function fromTimestamp(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestampMs((int) $value);
        }

        return Carbon::parse($value);
    }
}
