<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRevenueCatWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevenueCatWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Respond quickly (RevenueCat retries up to 5x); process asynchronously.
        ProcessRevenueCatWebhook::dispatch($request->all());

        return response()->json(['received' => true], 200);
    }
}
