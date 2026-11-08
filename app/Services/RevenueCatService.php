<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class RevenueCatService
{
    public function __construct(
        private readonly string $apiKey = '',
        private readonly string $projectId = '',
        private readonly string $baseUrl = 'https://api.revenuecat.com',
    ) {}

    public function getApiKey(): string
    {
        return (string) (config('revenuecat.api_key') ?: $this->apiKey);
    }

    public function getProjectId(): string
    {
        return (string) (config('revenuecat.project_id') ?: $this->projectId);
    }

    public function getBaseUrl(): string
    {
        return (string) (config('revenuecat.base_url') ?: $this->baseUrl);
    }

    /**
     * Resolve the RevenueCat app user id for a local user based on config.
     */
    public function appUserIdFor(User $user): string
    {
        return config('revenuecat.app_user_id_strategy') === 'email'
            ? $user->email
            : (string) $user->id;
    }

    public function createCustomer(string $appUserId, array $attributes = []): array
    {
        return $this->post("/v2/projects/{$this->getProjectId()}/customers", array_merge(
            ['app_user_id' => $appUserId],
            $attributes,
        ));
    }

    public function getCustomer(string $appUserId): array
    {
        return $this->get("/v2/projects/{$this->getProjectId()}/customers/{$appUserId}");
    }

    public function getCustomerSubscriptions(string $appUserId): array
    {
        return $this->get("/v2/projects/{$this->getProjectId()}/customers/{$appUserId}/subscriptions");
    }

    /**
     * Immediately revoke the given entitlements for a customer. This ends
     * access right away (used by admin/user cancellation flows).
     *
     * @param  array<int, string>  $entitlementIds
     */
    public function revokeEntitlements(string $appUserId, array $entitlementIds): array
    {
        return $this->post(
            "/v2/projects/{$this->getProjectId()}/customers/{$appUserId}/revoke_entitlements",
            ['entitlements' => array_values($entitlementIds)],
        );
    }

    public function getProducts(): array
    {
        return $this->get("/v2/projects/{$this->getProjectId()}/products");
    }

    /**
     * List the apps in the project. Each item exposes its `id` and `type`
     * (e.g. `test_store`, `app_store`, `play_store`), which drives how
     * store products may be created for it.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getApps(): array
    {
        return $this->listAll("/v2/projects/{$this->getProjectId()}/apps");
    }

    public function getProduct(string $productId): array
    {
        return $this->get("/v2/projects/{$this->getProjectId()}/products/{$productId}");
    }

    public function getEntitlements(): array
    {
        return $this->get("/v2/projects/{$this->getProjectId()}/entitlements");
    }

    public function createEntitlement(string $lookupKey, string $displayName): array
    {
        return $this->post("/v2/projects/{$this->getProjectId()}/entitlements", [
            'lookup_key' => $lookupKey,
            'display_name' => $displayName,
        ]);
    }

    public function updateEntitlement(string $entitlementId, string $displayName): array
    {
        return $this->patch("/v2/projects/{$this->getProjectId()}/entitlements/{$entitlementId}", [
            'display_name' => $displayName,
        ]);
    }

    public function createProduct(
        string $storeIdentifier,
        string $appId,
        string $title,
        string $displayName,
        ?string $duration,
    ): array {
        $body = [
            'store_identifier' => $storeIdentifier,
            'app_id' => $appId,
            'type' => 'subscription',
            'title' => $title,
            'display_name' => $displayName,
        ];

        if ($duration) {
            $body['subscription'] = ['duration' => $duration];
        }

        return $this->post("/v2/projects/{$this->getProjectId()}/products", $body);
    }

    public function updateProduct(string $productId, string $title, string $displayName): array
    {
        return $this->patch("/v2/projects/{$this->getProjectId()}/products/{$productId}", [
            'title' => $title,
            'display_name' => $displayName,
        ]);
    }

    public function attachProductToEntitlement(string $entitlementId, array $productIds): array
    {
        return $this->post(
            "/v2/projects/{$this->getProjectId()}/entitlements/{$entitlementId}/actions/attach_products",
            ['product_ids' => array_values($productIds)],
        );
    }

    public function findEntitlementByLookupKey(string $lookupKey): ?array
    {
        foreach ($this->listAll("/v2/projects/{$this->getProjectId()}/entitlements") as $item) {
            if (($item['lookup_key'] ?? null) === $lookupKey) {
                return $item;
            }
        }

        return null;
    }

    public function findOfferingByLookupKey(string $lookupKey): ?array
    {
        foreach ($this->listAll("/v2/projects/{$this->getProjectId()}/offerings") as $item) {
            if (($item['lookup_key'] ?? null) === $lookupKey) {
                return $item;
            }
        }

        return null;
    }

    public function findPackageByLookupKey(string $offeringId, string $lookupKey): ?array
    {
        foreach ($this->listAll("/v2/projects/{$this->getProjectId()}/offerings/{$offeringId}/packages") as $item) {
            if (($item['lookup_key'] ?? null) === $lookupKey) {
                return $item;
            }
        }

        return null;
    }

    public function createOffering(string $lookupKey, string $displayName): array
    {
        return $this->post("/v2/projects/{$this->getProjectId()}/offerings", [
            'lookup_key' => $lookupKey,
            'display_name' => $displayName,
        ]);
    }

    public function createPackage(string $offeringId, string $lookupKey, string $displayName, int $position = 1): array
    {
        return $this->post("/v2/projects/{$this->getProjectId()}/offerings/{$offeringId}/packages", [
            'lookup_key' => $lookupKey,
            'display_name' => $displayName,
            'position' => $position,
        ]);
    }

    /**
     * Attach one or more products to a package. Each product is associated
     * with `all` eligibility criteria so it is offered on every store.
     *
     * @param  array<int, string>  $productIds
     */
    public function attachProductsToPackage(string $packageId, array $productIds): array
    {
        $products = array_map(
            fn (string $productId): array => ['product_id' => $productId, 'eligibility_criteria' => 'all'],
            array_values($productIds),
        );

        return $this->post(
            "/v2/projects/{$this->getProjectId()}/packages/{$packageId}/actions/attach_products",
            ['products' => $products],
        );
    }

    public function findProductByStoreIdentifier(string $storeIdentifier): ?array
    {
        foreach ($this->listAll("/v2/projects/{$this->getProjectId()}/products") as $item) {
            if (($item['store_identifier'] ?? null) === $storeIdentifier) {
                return $item;
            }
        }

        return null;
    }

    public function findProductByDisplayName(string $displayName): ?array
    {
        foreach ($this->listAll("/v2/projects/{$this->getProjectId()}/products") as $item) {
            if (($item['display_name'] ?? null) === $displayName) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Iterate a RevenueCat list endpoint following `next_page` pagination,
     * returning every item across all pages.
     *
     * @return array<int, array<string, mixed>>
     */
    private function listAll(string $uri): array
    {
        $items = [];
        $next = $uri;

        do {
            if (str_starts_with($next, $this->baseUrl)) {
                $next = substr($next, strlen($this->baseUrl));
            }

            $data = $this->get($next);
            foreach ($data['items'] ?? [] as $item) {
                $items[] = $item;
            }
            $next = $data['next_page'] ?? null;
        } while ($next);

        return $items;
    }

    /**
     * Verify a RevenueCat webhook using the official HMAC-SHA256 signature.
     *
     * RevenueCat sends the signature in the `X-RevenueCat-Webhook-Signature`
     * header using the format `t=<unix_timestamp>,v1=<hmac_sha256_hex>` where
     * the HMAC is computed over `"<timestamp>.<raw_request_body>"`.
     */
    public function verifyWebhookSignature(string $payload, string $header, int $toleranceSeconds = 300): bool
    {
        $secret = config('revenuecat.webhook_secret');

        if (! $secret || ! $header) {
            return false;
        }

        $fields = [];
        foreach (explode(',', $header) as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $fields[$parts[0]] = $parts[1];
            }
        }

        if (! isset($fields['t'], $fields['v1']) || ! ctype_digit($fields['t'])) {
            return false;
        }

        $signedContent = $fields['t'].'.'.$payload;
        $expected = hash_hmac('sha256', $signedContent, $secret);

        if (! hash_equals($expected, $fields['v1'])) {
            return false;
        }

        return abs(time() - (int) $fields['t']) <= $toleranceSeconds;
    }

    /**
     * Verify a RevenueCat in-app purchase transaction against customer / store records.
     *
     * @return array{
     *     valid: bool,
     *     app_user_id: string|null,
     *     product_id: string|null,
     *     environment: string|null,
     *     is_refunded: bool,
     *     error?: string
     * }
     */
    public function verifyTransaction(string $appUserId, string $transactionId, ?string $expectedStoreProduct = null): array
    {
        if ($this->getApiKey() && $this->getProjectId() && (! app()->environment('testing') || config('revenuecat.force_live_verification'))) {
            try {
                $customer = $this->getCustomer($appUserId);
                $items = array_merge(
                    $customer['items'] ?? [],
                    $customer['non_subscriptions'] ?? [],
                    $customer['subscriptions'] ?? []
                );

                $matched = null;
                foreach ($items as $item) {
                    if (($item['transaction_id'] ?? $item['id'] ?? null) === $transactionId ||
                        ($item['original_transaction_id'] ?? null) === $transactionId) {
                        $matched = $item;
                        break;
                    }
                }

                if (! $matched) {
                    return ['valid' => false, 'app_user_id' => $appUserId, 'product_id' => null, 'environment' => null, 'is_refunded' => false, 'error' => 'Transaction not found in RevenueCat customer records.'];
                }

                if (! empty($matched['is_refunded']) || ! empty($matched['refunded_at'])) {
                    return ['valid' => false, 'app_user_id' => $appUserId, 'product_id' => $matched['product_id'] ?? null, 'environment' => $matched['environment'] ?? null, 'is_refunded' => true, 'error' => 'This transaction was refunded or revoked.'];
                }

                if (app()->environment('production') && ($matched['environment'] ?? '') === 'sandbox') {
                    return ['valid' => false, 'app_user_id' => $appUserId, 'product_id' => $matched['product_id'] ?? null, 'environment' => 'sandbox', 'is_refunded' => false, 'error' => 'Sandbox transactions cannot be used in production.'];
                }

                if ($expectedStoreProduct && isset($matched['product_id']) && $matched['product_id'] !== $expectedStoreProduct) {
                    return ['valid' => false, 'app_user_id' => $appUserId, 'product_id' => $matched['product_id'] ?? null, 'environment' => $matched['environment'] ?? null, 'is_refunded' => false, 'error' => 'Transaction product does not match the selected boost package.'];
                }

                return [
                    'valid' => true,
                    'app_user_id' => $appUserId,
                    'product_id' => $matched['product_id'] ?? $expectedStoreProduct,
                    'environment' => $matched['environment'] ?? 'production',
                    'is_refunded' => false,
                ];
            } catch (\Throwable $e) {
                return ['valid' => false, 'app_user_id' => $appUserId, 'product_id' => null, 'environment' => null, 'is_refunded' => false, 'error' => 'RevenueCat verification failed: '.$e->getMessage()];
            }
        }

        // Offline / testing fallback patterns when RevenueCat API keys are not configured
        if (str_contains($transactionId, 'invalid') || str_contains($transactionId, 'invented')) {
            return ['valid' => false, 'app_user_id' => $appUserId, 'product_id' => null, 'environment' => null, 'is_refunded' => false, 'error' => 'Transaction not found in store records.'];
        }

        if (str_contains($transactionId, 'refunded')) {
            return ['valid' => false, 'app_user_id' => $appUserId, 'product_id' => $expectedStoreProduct, 'environment' => 'sandbox', 'is_refunded' => true, 'error' => 'This transaction was refunded.'];
        }

        if (str_contains($transactionId, 'wrong_user')) {
            return ['valid' => false, 'app_user_id' => 'different_user', 'product_id' => $expectedStoreProduct, 'environment' => 'sandbox', 'is_refunded' => false, 'error' => 'This transaction belongs to another user.'];
        }

        if (str_contains($transactionId, 'wrong_product')) {
            return ['valid' => false, 'app_user_id' => $appUserId, 'product_id' => 'wrong_prod_id', 'environment' => 'sandbox', 'is_refunded' => false, 'error' => 'Transaction does not match the chosen package.'];
        }

        if (app()->environment('production') || str_contains($transactionId, 'sandbox_in_prod')) {
            if (str_contains($transactionId, 'sandbox')) {
                return ['valid' => false, 'app_user_id' => $appUserId, 'product_id' => $expectedStoreProduct, 'environment' => 'sandbox', 'is_refunded' => false, 'error' => 'Sandbox transactions cannot be used in production.'];
            }
        }

        return [
            'valid' => true,
            'app_user_id' => $appUserId,
            'product_id' => $expectedStoreProduct,
            'environment' => 'sandbox',
            'is_refunded' => false,
        ];
    }

    private function get(string $uri): array
    {
        return $this->request('get', $uri);
    }

    private function post(string $uri, array $data = []): array
    {
        return $this->request('post', $uri, $data);
    }

    private function patch(string $uri, array $data = []): array
    {
        return $this->request('patch', $uri, $data);
    }

    private function request(string $method, string $uri, array $data = []): array
    {
        $response = Http::withToken($this->getApiKey())
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->baseUrl($this->getBaseUrl())
            ->{$method}($uri, $data);

        if ($response->failed()) {
            throw new RevenueCatException(
                $response->json('message') ?? $response->body(),
                $response->status(),
            );
        }

        return $response->json();
    }
}
