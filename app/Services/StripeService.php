<?php

namespace App\Services;

use Stripe\Price;
use Stripe\Product;
use Stripe\Stripe;

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

    public function archiveProduct(string $productId): Product
    {
        return Product::update($productId, ['active' => false]);
    }
}
