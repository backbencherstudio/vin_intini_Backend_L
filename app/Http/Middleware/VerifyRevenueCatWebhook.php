<?php

namespace App\Http\Middleware;

use App\Services\RevenueCatService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRevenueCatWebhook
{
    public function __construct(private RevenueCatService $revenueCat) {}

    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-RevenueCat-Webhook-Signature');

        if (! $signature || ! $this->revenueCat->verifyWebhookSignature($request->getContent(), (string) $signature)) {
            abort(403, 'Invalid RevenueCat webhook signature.');
        }

        return $next($request);
    }
}
