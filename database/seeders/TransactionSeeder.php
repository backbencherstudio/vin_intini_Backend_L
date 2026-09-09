<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $plans = Plan::whereIn('name', ['Premium Plan', 'Pro User', 'Pro Industries'])
            ->get()
            ->keyBy('name');

        $defaultPlan = Plan::orderByDesc('id')->first();

        $users = User::whereIn('email', ['user1@gmail.com', 'user2@gmail.com'])->get();

        foreach ($users as $index => $user) {
            $subscriptionId = $user->subscriptions()->latest('id')->value('id');

            $planId = match ($index) {
                0 => ($plans['Premium Plan'] ?? $plans['Pro User'] ?? $defaultPlan)?->id,
                1 => ($plans['Pro Industries'] ?? $plans['Pro User'] ?? $defaultPlan)?->id,
                default => $defaultPlan?->id,
            };

            $statuses = ['succeeded', 'succeeded', 'succeeded', 'pending', 'failed'];

            for ($i = 1; $i <= 5; $i++) {
                $status = $statuses[array_rand($statuses)];
                $isPaid = $status === 'succeeded';
                $isRefunded = $status === 'refunded';
                $amount = (float) ($planId ? ($plans['Pro Industries']?->id === $planId ? 59.99 : 29.99) : 19.99);

                Transaction::updateOrCreate(
                    [
                        'provider_transaction_id' => 'seed_txn_'.strtolower($user->first_name).'_'.$i,
                    ],
                    [
                        'checkout_session_id' => 'seed_session_'.strtolower($user->first_name).'_'.$i,
                        'user_id' => $user->id,
                        'plan_id' => $planId,
                        'subscription_id' => $subscriptionId,
                        'amount' => $amount,
                        'currency' => 'usd',
                        'card_brand' => collect(['visa', 'mastercard', 'amex'])->random(),
                        'card_last4' => (string) rand(1000, 9999),
                        'status' => $status,
                        'refunded_amount' => $isRefunded ? $amount : 0,
                        'refunded_at' => $isRefunded ? now()->subDays(rand(1, 15)) : null,
                        'paid_at' => $isPaid ? now()->subDays(rand(1, 30)) : null,
                    ]
                );
            }
        }
    }
}
