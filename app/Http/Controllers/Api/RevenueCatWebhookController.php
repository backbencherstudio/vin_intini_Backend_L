<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRevenueCatWebhook;
use App\Services\WebhookLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueCatWebhookController extends Controller
{
    public function __construct(
        private WebhookLoggerService $logger,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $event = $request->input('event', []);
        $eventType = $event['type'] ?? null;
        $eventId = $event['id'] ?? $event['transaction_id'] ?? $event['original_transaction_id'] ?? null;

        $log = $this->logger->logReceived(
            provider: 'revenuecat',
            eventType: $eventType,
            eventId: $eventId,
            payload: $request->all(),
        );

        // Respond quickly (RevenueCat retries up to 5x); process asynchronously.
        ProcessRevenueCatWebhook::dispatch($request->all(), $log?->id);

        return response()->json(['received' => true], 200);
    }
}
