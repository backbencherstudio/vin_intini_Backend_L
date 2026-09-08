<?php

namespace App\Jobs;

use App\Models\Plan;
use App\Services\RevenueCatPlanSyncService;
use App\Services\StripeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProvisionPlan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 10;

    public function __construct(
        public Plan $plan,
        public bool $updateProduct = false,
        public array $productData = [],
        public bool $recreatePrice = false,
    ) {}

    public function handle(StripeService $stripe, RevenueCatPlanSyncService $sync): void
    {
        $this->ensureStripeProduct($stripe);
        $this->ensureStripePrice($stripe);

        if ($this->updateProduct && $this->plan->stripe_product_id) {
            $stripe->updateProduct($this->plan->stripe_product_id, $this->productData);
        }

        if ($this->recreatePrice && $this->plan->stripe_product_id && $this->plan->stripe_price_id) {
            $stripe->archivePrice($this->plan->stripe_price_id);

            $newPrice = $stripe->createPrice(
                $this->unitAmount($this->plan->billing_rate),
                'usd',
                $this->intervalFor($this->plan->billing_cycle),
                $this->plan->stripe_product_id,
                $this->priceIdempotencyOptions(),
            );

            $this->plan->update(['stripe_price_id' => $newPrice->id]);
        }

        $sync->sync($this->plan);
    }

    public function failed(?Throwable $exception): void
    {
        report($exception);
    }

    private function ensureStripeProduct(StripeService $stripe): void
    {
        if ($this->plan->stripe_product_id) {
            return;
        }

        $product = $stripe->createProduct(
            $this->plan->name,
            $this->plan->short_description,
            $this->plan->features,
            ['idempotency_key' => 'product_'.$this->plan->id],
        );

        $this->plan->update(['stripe_product_id' => $product->id]);
    }

    private function ensureStripePrice(StripeService $stripe): void
    {
        if ($this->plan->stripe_price_id) {
            return;
        }

        $price = $stripe->createPrice(
            $this->unitAmount($this->plan->billing_rate),
            'usd',
            $this->intervalFor($this->plan->billing_cycle),
            $this->plan->stripe_product_id,
            $this->priceIdempotencyOptions(),
        );

        $this->plan->update(['stripe_price_id' => $price->id]);
    }

    private function unitAmount(float|string $rate): int
    {
        return (int) (round((float) $rate, 2) * 100);
    }

    private function intervalFor(string $billingCycle): string
    {
        return $billingCycle === 'monthly' ? 'month' : 'year';
    }

    private function priceIdempotencyOptions(): array
    {
        return [
            'idempotency_key' => 'price_'.$this->plan->id.'_'.$this->unitAmount($this->plan->billing_rate).'_'.$this->plan->billing_cycle,
        ];
    }
}
