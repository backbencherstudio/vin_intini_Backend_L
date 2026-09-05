<?php

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RevenueCatPlanSyncService
{
    public function __construct(private RevenueCatService $revenueCat) {}

    public function sync(Plan $plan): void
    {
        $platforms = ['ios', 'android'];

        $hasAnyApp = collect($platforms)->contains(
            fn (string $platform) => config("revenuecat.app_id_{$platform}") ?: config('revenuecat.app_id')
        );

        if (! $hasAnyApp) {
            return;
        }

        $lookupKey = 'plan_'.$plan->id;
        $duration = $this->durationFor($plan->billing_cycle);

        // RevenueCat requires unique display names per app, so suffix with the
        // plan id to avoid collisions when plans share a human-readable name.
        $label = $plan->name.' #'.$plan->id;

        // One shared, project-level entitlement per plan.
        $entitlement = $plan->revenuecat_entitlement_id
            ? $this->safeUpdateEntitlement($plan->revenuecat_entitlement_id, $label)
            : ($this->revenueCat->findEntitlementByLookupKey($lookupKey)
                ?? $this->revenueCat->createEntitlement($lookupKey, $label));

        $entitlementId = $entitlement['id'] ?? $plan->revenuecat_entitlement_id;

        if (! $entitlementId) {
            throw new RuntimeException('RevenueCat entitlement id missing after sync.');
        }

        $productIds = [];
        $storeIdentifiers = [];

        foreach ($platforms as $platform) {
            $appId = config("revenuecat.app_id_{$platform}") ?: config('revenuecat.app_id');

            if (! $appId) {
                continue;
            }

            $storeIdentifier = $plan->{"revenuecat_store_identifier_{$platform}"}
                ?? (($platform === 'ios') ? $plan->revenuecat_store_identifier : null)
                ?? "plan_{$plan->id}_{$platform}";
            $storeIdentifiers[$platform] = $storeIdentifier;

            // Products live inside a specific app, so their display name must
            // be unique per platform too (otherwise the idempotent fallback
            // could reuse the other platform's product).
            $productLabel = $plan->name.' #'.$plan->id.' ('.strtoupper($platform).')';

            $product = $plan->{"revenuecat_product_id_{$platform}"}
                ? $this->safeUpdateProduct($plan->{"revenuecat_product_id_{$platform}"}, $productLabel, $productLabel)
                : ($this->revenueCat->findProductByStoreIdentifier($storeIdentifier)
                    ?? $this->createProductIdempotent($storeIdentifier, $appId, $productLabel, $duration));

            $productId = $product['id'] ?? $plan->{"revenuecat_product_id_{$platform}"};

            if (! $productId) {
                throw new RuntimeException("RevenueCat product id missing after sync for {$platform}.");
            }

            $productIds[$platform] = $productId;

            // Idempotent: re-attaching an already-linked product is safe to ignore.
            try {
                $this->revenueCat->attachProductToEntitlement($entitlementId, [$productId]);
            } catch (RevenueCatException) {
            }
        }

        // One offering + package per plan so the mobile app can fetch it via
        // `getOfferings()` and purchase the package. The package references
        // every product across platforms.
        $offering = $plan->revenuecat_offering_id
            ? ['id' => $plan->revenuecat_offering_id]
            : ($this->revenueCat->findOfferingByLookupKey($lookupKey)
                ?? $this->createOfferingIdempotent($lookupKey, $label));
        $offeringId = $offering['id'] ?? $plan->revenuecat_offering_id;

        $package = $plan->revenuecat_package_id
            ? ['id' => $plan->revenuecat_package_id]
            : ($this->revenueCat->findPackageByLookupKey($offeringId, $lookupKey)
                ?? $this->createPackageIdempotent($offeringId, $lookupKey, $label));
        $packageId = $package['id'] ?? $plan->revenuecat_package_id;

        if ($packageId && $productIds) {
            try {
                $this->revenueCat->attachProductsToPackage($packageId, array_values($productIds));
            } catch (RevenueCatException) {
            }
        }

        DB::transaction(function () use ($plan, $entitlementId, $storeIdentifiers, $productIds, $offeringId, $packageId) {
            $plan->forceFill([
                'revenuecat_entitlement_id' => $entitlementId,
                'revenuecat_offering_id' => $offeringId,
                'revenuecat_package_id' => $packageId,
                'revenuecat_store_identifier' => $plan->revenuecat_store_identifier
                    ?? ($storeIdentifiers['ios'] ?? $storeIdentifiers['android'] ?? null),
                'revenuecat_store_identifier_ios' => $storeIdentifiers['ios'] ?? null,
                'revenuecat_store_identifier_android' => $storeIdentifiers['android'] ?? null,
                'revenuecat_product_id' => $productIds['ios'] ?? $productIds['android'] ?? null,
                'revenuecat_product_id_ios' => $productIds['ios'] ?? null,
                'revenuecat_product_id_android' => $productIds['android'] ?? null,
            ])->save();
        });
    }

    private function updateEntitlement(string $entitlementId, string $name): array
    {
        return $this->revenueCat->updateEntitlement($entitlementId, $name);
    }

    private function updateProduct(string $productId, string $name): array
    {
        return $this->revenueCat->updateProduct($productId, $name, $name);
    }

    /**
     * Update an entitlement, ignoring uniqueness conflicts (RevenueCat flags
     * even setting a resource to its own display name).
     */
    private function safeUpdateEntitlement(string $entitlementId, string $name): array
    {
        try {
            return $this->revenueCat->updateEntitlement($entitlementId, $name);
        } catch (RevenueCatException) {
            return ['id' => $entitlementId];
        }
    }

    /**
     * Update a product, ignoring uniqueness conflicts (self-collision on the
     * display name is harmless — the value is already set).
     */
    private function safeUpdateProduct(string $productId, string $name, string $label): array
    {
        try {
            return $this->revenueCat->updateProduct($productId, $name, $label);
        } catch (RevenueCatException) {
            return ['id' => $productId];
        }
    }

    private function createOfferingIdempotent(string $lookupKey, string $label): array
    {
        try {
            return $this->revenueCat->createOffering($lookupKey, $label);
        } catch (RevenueCatException $e) {
            $existing = $this->revenueCat->findOfferingByLookupKey($lookupKey);

            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    private function createPackageIdempotent(string $offeringId, string $lookupKey, string $label): array
    {
        try {
            return $this->revenueCat->createPackage($offeringId, $lookupKey, $label);
        } catch (RevenueCatException $e) {
            $existing = $this->revenueCat->findPackageByLookupKey($offeringId, $lookupKey);

            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    /**
     * Create a product, but if one with the same store identifier already
     * exists (e.g. a leftover from a previous partial sync), reuse it instead
     * of failing on the uniqueness conflict.
     */
    private function createProductIdempotent(string $storeIdentifier, string $appId, string $label, ?string $duration): array
    {
        try {
            return $this->revenueCat->createProduct($storeIdentifier, $appId, $label, $label, $duration);
        } catch (RevenueCatException $e) {
            $existing = $this->revenueCat->findProductByStoreIdentifier($storeIdentifier)
                ?? $this->revenueCat->findProductByDisplayName($label);

            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    private function durationFor(string $billingCycle): ?string
    {
        return match ($billingCycle) {
            'weekly' => 'P1W',
            'monthly' => 'P1M',
            'quarterly' => 'P3M',
            'semiannually' => 'P6M',
            'yearly' => 'P1Y',
            default => null,
        };
    }
}
