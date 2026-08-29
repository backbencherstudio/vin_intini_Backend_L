<?php

namespace App\Http\Middleware;

use App\Services\RevenueCatService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyRevenueCatWebhook
{
    public function __construct(private RevenueCatService $revenueCat) {}

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('revenuecat.webhook_secret');

        if (! $secret) {
            Log::warning('RevenueCat webhook rejected: revenuecat_webhook_secret is not configured.');

            abort(403, 'Invalid RevenueCat webhook signature.');
        }

        // RevenueCat sends the raw JSON body; verify against it exactly as received.
        $payload = $request->getContent();

        // Preferred (stronger) scheme: HMAC signature header.
        $signatureHeader = $request->header('X-RevenueCat-Webhook-Signature');
        if ($signatureHeader && $this->revenueCat->verifyWebhookSignature($payload, (string) $signatureHeader)) {
            return $next($request);
        }

        // Fallback (simpler) scheme: Authorization header value set in RC dashboard.
        $authHeader = $request->header('Authorization');
        if ($authHeader && $this->verifyAuthorizationHeader((string) $authHeader, $secret)) {
            return $next($request);
        }

        Log::warning('RevenueCat webhook rejected: no valid signature or authorization header present.', [
            'has_signature_header' => (bool) $signatureHeader,
            'has_auth_header' => (bool) $authHeader,
        ]);

        abort(403, 'Invalid RevenueCat webhook signature.');
    }

    private function verifyAuthorizationHeader(string $header, string $secret): bool
    {
        $token = str_starts_with($header, 'Bearer ') ? substr($header, 7) : $header;

        return hash_equals($secret, $token);
    }
}
